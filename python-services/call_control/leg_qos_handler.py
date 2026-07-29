"""Trunk-leg RTP QoS -> CDR.

Runs from the [leg-qos] hangup handler that [set-jb] pushes onto the callee
(trunk) channel at pre-dial time. Deliberately separate from CallEndHandler: this
path updates only the six rtp_trunk_* columns and must never be able to alter
disposition, duration, billsec, status or any cost column.

The call is already over by the time this runs, so every failure is swallowed --
but logged at warning, never debug.
"""

import logging

from sqlalchemy import text
from sqlalchemy.orm import Session

from call_control.agi_protocol import AgiConnection
from call_control.rtp_qos import read_rtp_qos

logger = logging.getLogger(__name__)


class LegQosHandler:
    """Records the carrier-leg RTP quality for a completed call."""

    async def handle(self, agi: AgiConnection, session: Session) -> None:
        try:
            await self._process(agi, session)
        except Exception as e:
            logger.warning(f"LegQos handler error: {e}", exc_info=True)

    async def _process(self, agi: AgiConnection, session: Session) -> None:
        cdr_uuid = await agi.get_variable("CDR_UUID")
        if not cdr_uuid:
            # Expected when the trunk leg never carried a CDR (e.g. rejected
            # before Dial). Nothing to attach stats to.
            return

        trunk = await read_rtp_qos(agi, "TRUNK")

        session.execute(
            text("""
                UPDATE call_records SET
                    rtp_trunk_rx_count = :trunk_rx_count,
                    rtp_trunk_tx_count = :trunk_tx_count,
                    rtp_trunk_rx_loss = :trunk_rx_loss,
                    rtp_trunk_tx_loss = :trunk_tx_loss,
                    rtp_trunk_rx_jitter = :trunk_rx_jitter,
                    rtp_trunk_rtt = :trunk_rtt
                WHERE uuid = :uuid
            """),
            {
                "uuid": cdr_uuid,
                "trunk_rx_count": trunk["rx_count"],
                "trunk_tx_count": trunk["tx_count"],
                "trunk_rx_loss": trunk["rx_loss"],
                "trunk_tx_loss": trunk["tx_loss"],
                "trunk_rx_jitter": trunk["rx_jitter"],
                "trunk_rtt": trunk["rtt"],
            },
        )
        session.commit()

        logger.info(
            f"CDR {cdr_uuid}: trunk RTP rx={trunk['rx_count']} tx={trunk['tx_count']} "
            f"rxloss={trunk['rx_loss']} jitter={trunk['rx_jitter']}"
        )
