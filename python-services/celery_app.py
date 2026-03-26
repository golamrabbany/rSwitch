"""
Celery application configuration.

Replaces Laravel's scheduled commands:
- billing:rate-calls  → rate_and_charge task (triggered per CDR, real-time)
- billing:generate-invoices → generate_invoices task (scheduled)
- cdr:aggregate → aggregate_cdr task (scheduled)
"""

from celery import Celery
from celery.schedules import crontab
from shared.config import get_settings

settings = get_settings()

app = Celery(
    "rswitch",
    broker=settings.redis_url,
    backend=settings.redis_url,
)

app.conf.update(
    # Serialization
    task_serializer="json",
    accept_content=["json"],
    result_serializer="json",
    timezone="UTC",
    enable_utc=True,

    # Worker settings
    worker_prefetch_multiplier=4,
    worker_max_tasks_per_child=1000,
    worker_concurrency=4,

    # Task routing
    task_routes={
        "billing.tasks.rate_and_charge": {"queue": "billing"},
        "billing.tasks.rate_batch": {"queue": "billing"},
        "billing.credit_control.check_balances": {"queue": "billing"},
        "billing.tasks.daily_call_summary": {"queue": "billing"},
        "billing.tasks.hangup_reseller_calls": {"queue": "billing"},
        "billing.tasks.partition_maintenance": {"queue": "billing"},
        "billing.tasks.restore_cdr_archive": {"queue": "billing"},
        "billing.tasks.cleanup_restored_partitions": {"queue": "billing"},
        "broadcast.tasks.*": {"queue": "broadcast"},
        "monitoring.tasks.*": {"queue": "monitoring"},
    },

    # Scheduled tasks (replaces Laravel cron)
    beat_schedule={
        # Rate any missed CDRs every 2 minutes (safety net)
        "rate-unrated-cdrs": {
            "task": "billing.tasks.rate_batch",
            "schedule": 120.0,  # every 2 minutes
        },
        # Credit control: check active prepaid calls every 30 seconds
        "credit-control-check": {
            "task": "billing.credit_control.check_balances",
            "schedule": 30.0,
        },
        # Broadcast: check scheduled broadcasts every 60 seconds
        "check-scheduled-broadcasts": {
            "task": "broadcast.tasks.check_scheduled_broadcasts",
            "schedule": 60.0,
        },
        # Broadcast: cleanup stuck numbers every 2 minutes
        "cleanup-stuck-broadcast-numbers": {
            "task": "broadcast.tasks.cleanup_stuck_broadcast_numbers",
            "schedule": 120.0,
        },
        # Daily call charge aggregation at 00:05 UTC
        "daily-call-summary": {
            "task": "billing.tasks.daily_call_summary",
            "schedule": crontab(hour=0, minute=5),
        },
        # Partition maintenance at 00:10 UTC (create future, archive+drop old)
        "partition-maintenance": {
            "task": "billing.tasks.partition_maintenance",
            "schedule": crontab(hour=0, minute=10),
        },
        # Cleanup restored archive partitions (hourly check)
        "cleanup-restored-partitions": {
            "task": "billing.tasks.cleanup_restored_partitions",
            "schedule": 3600.0,  # every hour
        },
    },
)

# Auto-discover tasks in these modules
app.autodiscover_tasks(["billing", "broadcast", "monitoring"])
