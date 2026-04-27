"""
FastAPI application — Python billing and monitoring service for rSwitch.

Endpoints:
- POST /api/rate-cdr/{id}     — Rate a single CDR
- POST /api/rate-batch         — Trigger batch rating
- GET  /api/balance/{user_id}  — Get user balance info
- GET  /api/active-calls       — Get active calls from AMI
- WS   /ws/live-calls          — WebSocket for real-time call monitoring
- GET  /api/health             — Health check
- POST /api/cache/clear        — Clear rate trie cache
"""

import asyncio
import logging
import os
from contextlib import asynccontextmanager
from decimal import Decimal
from typing import Optional

from fastapi import FastAPI, HTTPException, WebSocket, WebSocketDisconnect
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel

# Configure root logger so logger.info() in handlers (AGI, billing, AMI) is visible.
# Override with LOG_LEVEL env (default INFO).
logging.basicConfig(
    level=os.environ.get("LOG_LEVEL", "INFO"),
    format="%(asctime)s %(levelname)s %(name)s: %(message)s",
)

from shared.config import get_settings
from shared.redis_client import get_redis
from shared.database import get_session
from shared.models.user import User
from billing.rating import RatingService
from billing.balance import BalanceService
from billing.tasks import rate_and_charge, rate_batch
from monitoring.ami_listener import get_ami_listener

logger = logging.getLogger(__name__)

# Service instances
rating_service: Optional[RatingService] = None
balance_service: Optional[BalanceService] = None


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Startup/shutdown lifecycle."""
    global rating_service, balance_service

    # Initialize services
    redis_client = get_redis()
    rating_service = RatingService(redis_client)
    balance_service = BalanceService()

    # Connect AMI listener
    ami = get_ami_listener()
    try:
        await ami.connect()
    except Exception as e:
        logger.warning(f"AMI connection failed (will retry): {e}")

    # Start Redis subscriber for Laravel events
    asyncio.create_task(_listen_laravel_events())

    # Start FastAGI server for call control
    from call_control.agi_server import start_agi_server
    asyncio.create_task(start_agi_server("0.0.0.0", 4573))

    logger.info("Python billing + call control service started")
    yield

    # Shutdown
    await ami.disconnect()
    logger.info("Python billing service stopped")


app = FastAPI(
    title="rSwitch Billing Service",
    version="1.0.0",
    lifespan=lifespan,
)

# Allow cross-origin WebSocket connections from Laravel app
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


# ─────────────────────────────────────────────────────
# Request/Response models
# ─────────────────────────────────────────────────────


class RateCdrResponse(BaseModel):
    status: str
    call_record_id: Optional[int] = None
    matched_prefix: Optional[str] = None
    total_cost: Optional[str] = None
    reseller_cost: Optional[str] = None
    billable_duration: Optional[int] = None
    charged: Optional[bool] = None
    reason: Optional[str] = None


class BalanceResponse(BaseModel):
    user_id: int
    balance: str
    credit_limit: str
    available: str
    billing_type: str


class BatchResponse(BaseModel):
    status: str
    task_id: Optional[str] = None


class CacheClearRequest(BaseModel):
    rate_group_id: Optional[int] = None


class HealthResponse(BaseModel):
    status: str
    services: dict


# ─────────────────────────────────────────────────────
# Billing endpoints
# ─────────────────────────────────────────────────────


@app.post("/api/rate-cdr/{call_record_id}", response_model=RateCdrResponse)
async def rate_cdr(call_record_id: int, sync: bool = False):
    """
    Rate a single CDR.

    - sync=False (default): Queue via Celery for async processing
    - sync=True: Process immediately and return result
    """
    if sync:
        # Synchronous rating (for testing or urgent needs)
        try:
            result = rating_service.rate_call(call_record_id)
        except Exception as e:
            logger.error(f"rate_cdr sync error: {e}", exc_info=True)
            raise HTTPException(status_code=500, detail=str(e))
        if result["status"] == "rated":
            total_cost = Decimal(result["total_cost"])
            if total_cost > Decimal("0"):
                try:
                    balance_service.charge_call(call_record_id)
                    result["charged"] = True
                except Exception as e:
                    result["charged"] = False
                    result["reason"] = str(e)
            else:
                result["charged"] = True
        return RateCdrResponse(**result)

    # Async via Celery (default — production flow)
    try:
        task = rate_and_charge.delay(call_record_id)
        return RateCdrResponse(
            status="queued",
            call_record_id=call_record_id,
            reason=f"task_id={task.id}",
        )
    except Exception as e:
        logger.error(f"Celery dispatch failed, falling back to sync: {e}")
        # Fallback to sync if Celery is unavailable
        try:
            result = rating_service.rate_call(call_record_id)
            return RateCdrResponse(**result)
        except Exception as e2:
            raise HTTPException(status_code=500, detail=str(e2))


@app.post("/api/rate-batch", response_model=BatchResponse)
async def trigger_rate_batch():
    """Trigger batch rating of unrated CDRs (safety net)."""
    task = rate_batch.delay()
    return BatchResponse(status="queued", task_id=task.id)


@app.get("/api/balance/{user_id}", response_model=BalanceResponse)
async def get_balance(user_id: int):
    """Get user balance information."""
    with get_session() as session:
        user = session.query(User).get(user_id)
        if not user:
            raise HTTPException(status_code=404, detail="User not found")

        available = BalanceService.get_available_balance(user)
        return BalanceResponse(
            user_id=user.id,
            balance=str(user.balance),
            credit_limit=str(user.credit_limit),
            available=str(available),
            billing_type=user.billing_type,
        )


@app.post("/api/cache/clear")
async def clear_cache(request: CacheClearRequest):
    """Clear rate trie cache. Call after rate imports/updates."""
    rating_service.clear_trie_cache(request.rate_group_id)
    return {"status": "cleared", "rate_group_id": request.rate_group_id}


@app.post("/tasks/restore-cdr-archive")
async def trigger_restore_archive(request: dict):
    """Trigger CDR archive restore via Celery task."""
    from billing.tasks import restore_cdr_archive
    year = request.get("year")
    month = request.get("month")
    if not year or not month:
        return {"error": "year and month required"}, 400
    task = restore_cdr_archive.delay(int(year), int(month))
    return {"status": "queued", "task_id": task.id, "year": year, "month": month}


# ─────────────────────────────────────────────────────
# Monitoring endpoints
# ─────────────────────────────────────────────────────


@app.get("/api/active-calls")
async def get_active_calls():
    """Get active calls from Asterisk AMI (REST fallback)."""
    ami = get_ami_listener()
    return {
        "calls": ami.get_active_calls_list(),
        "stats": ami.get_stats(),
        "ami_connected": ami.is_connected,
    }


@app.get("/api/contacts")
async def get_contacts():
    """Get all registered SIP contacts from Asterisk AMI (real-time)."""
    ami = get_ami_listener()
    return ami.get_registered_contacts()


@app.post("/api/contacts/status")
async def get_contacts_status(request: dict):
    """
    Check registration status for specific SIP usernames.
    Replaces the PHP shell_exec('sudo asterisk -rx "pjsip show contacts"') approach.

    Request: {"usernames": ["100090", "100001", ...]}
    Response: {"100090": {"ip": "37.111.242.90", "status": "Avail"}, ...}
    """
    usernames = request.get("usernames", [])
    if not usernames:
        return {}

    ami = get_ami_listener()
    all_contacts = ami.get_registered_contacts()

    result = {}
    for username in usernames:
        if username in all_contacts:
            result[username] = {
                "ip": all_contacts[username]["ip"],
                "status": all_contacts[username].get("status", "Avail"),
            }

    return result


@app.websocket("/ws/live-calls")
async def websocket_live_calls(websocket: WebSocket):
    """
    WebSocket endpoint for real-time call monitoring.

    On connect: sends current snapshot of all active calls.
    Then pushes events as they happen:
    - call_start: new call initiated
    - call_answered: call connected
    - call_end: call hung up
    - snapshot: periodic full state refresh (every 30s)

    Each message includes updated stats (total, answered, ringing, inbound, outbound).
    """
    await websocket.accept()

    ami = get_ami_listener()
    ami.register_client(websocket)

    try:
        # Send initial snapshot
        await websocket.send_json({
            "type": "snapshot",
            "calls": ami.get_active_calls_list(),
            "stats": ami.get_stats(),
            "ami_connected": ami.is_connected,
        })

        # Keep connection alive and send periodic snapshots
        while True:
            try:
                # Wait for client messages (ping/pong or filters)
                data = await asyncio.wait_for(
                    websocket.receive_text(),
                    timeout=30.0,
                )
                # Client can send "ping" to keep alive
                if data == "ping":
                    await websocket.send_json({"type": "pong"})
            except asyncio.TimeoutError:
                # Send periodic snapshot every 30 seconds
                await websocket.send_json({
                    "type": "snapshot",
                    "calls": ami.get_active_calls_list(),
                    "stats": ami.get_stats(),
                    "ami_connected": ami.is_connected,
                })

    except WebSocketDisconnect:
        pass
    except Exception as e:
        logger.debug(f"WebSocket error: {e}")
    finally:
        ami.unregister_client(websocket)


# ─────────────────────────────────────────────────────
# Health check
# ─────────────────────────────────────────────────────


@app.get("/api/health", response_model=HealthResponse)
async def health_check():
    """Health check for all services."""
    services = {}

    # Check Redis
    try:
        redis = get_redis()
        redis.ping()
        services["redis"] = "ok"
    except Exception:
        services["redis"] = "error"

    # Check MySQL
    try:
        with get_session() as session:
            session.execute(
                __import__("sqlalchemy").text("SELECT 1")
            )
        services["mysql"] = "ok"
    except Exception:
        services["mysql"] = "error"

    # Check AMI
    ami = get_ami_listener()
    services["asterisk_ami"] = "ok" if ami.is_connected else "disconnected"

    overall = "ok" if all(v == "ok" for v in services.values()) else "degraded"
    return HealthResponse(status=overall, services=services)


# ─────────────────────────────────────────────────────
# Prometheus metrics
# ─────────────────────────────────────────────────────


@app.get("/metrics")
async def metrics():
    """Prometheus scrape endpoint.

    Refreshes AMI-driven gauges (active calls, registered contacts, WS clients,
    AMI connectivity, trunk reachability) on each scrape; AGI-handler counters
    and latency histograms are updated in-place by the request handler.

    Behind the firewall — :8001 is restricted to APP_SERVER_IP only.
    """
    from fastapi.responses import Response
    from monitoring.metrics import render_metrics
    body, content_type = render_metrics()
    return Response(content=body, media_type=content_type)


# ─────────────────────────────────────────────────────
# Redis subscriber — listen for Laravel events
# ─────────────────────────────────────────────────────


async def _listen_laravel_events():
    """
    Subscribe to Redis pub/sub channels for events from Laravel.

    Laravel publishes events when:
    - Rate groups are updated → clear trie cache
    - SIP accounts change → refresh routing
    """
    try:
        redis = get_redis()
        pubsub = redis.pubsub()
        pubsub.subscribe(
            "rswitch:rate.updated",
            "rswitch:sip.updated",
            "rswitch:broadcast.start",
        )

        logger.info("Listening for Laravel events on Redis pub/sub")

        while True:
            message = pubsub.get_message(ignore_subscribe_messages=True)
            if message and message["type"] == "message":
                channel = message["channel"]
                data = message.get("data", "")

                if channel == "rswitch:rate.updated":
                    # Clear trie cache when rates change
                    import json
                    try:
                        payload = json.loads(data)
                        group_id = payload.get("rate_group_id")
                        rating_service.clear_trie_cache(group_id)
                        logger.info(
                            f"Rate cache cleared for group {group_id} "
                            f"(triggered by Laravel)"
                        )
                    except Exception as e:
                        logger.error(f"Error processing rate event: {e}")

                elif channel == "rswitch:broadcast.start":
                    # Start broadcast processing via Celery
                    import json
                    try:
                        payload = json.loads(data)
                        broadcast_id = payload.get("broadcast_id")
                        if broadcast_id:
                            from broadcast.tasks import process_broadcast
                            process_broadcast.delay(broadcast_id)
                            logger.info(
                                f"Broadcast {broadcast_id} queued for processing "
                                f"(triggered by Laravel)"
                            )
                    except Exception as e:
                        logger.error(f"Error processing broadcast event: {e}")

            await asyncio.sleep(0.1)

    except Exception as e:
        logger.error(f"Redis subscriber error: {e}", exc_info=True)
