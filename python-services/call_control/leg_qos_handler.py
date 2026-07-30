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

        # call_records is PARTITION BY RANGE (TO_DAYS(call_start)) with ~125 daily
        # partitions and growing (DROP is deliberately revoked from this DB user,
        # so they accumulate). WHERE uuid alone can't prune and probes every
        # partition; bound by call_start so MySQL only scans recent ones. This
        # handler runs at hangup, so the row is always well within the last day --
        # worst case a stats write is skipped for a call somehow older than that,
        # which cannot happen here.
        session.execute(
            text("""
                UPDATE call_records SET
                    rtp_trunk_rx_count = :trunk_rx_count,
                    rtp_trunk_tx_count = :trunk_tx_count,
                    rtp_trunk_rx_loss = :trunk_rx_loss,
                    rtp_trunk_tx_loss = :trunk_tx_loss,
                    rtp_trunk_rx_jitter = :trunk_rx_jitter,
                    rtp_trunk_rtt = :trunk_rtt,
                    rtp_trunk_rx_mes = :trunk_rx_mes
                WHERE uuid = :uuid AND call_start >= NOW() - INTERVAL 1 DAY
            """),
            {
                "uuid": cdr_uuid,
                "trunk_rx_count": trunk["rx_count"],
                "trunk_tx_count": trunk["tx_count"],
                "trunk_rx_loss": trunk["rx_loss"],
                "trunk_tx_loss": trunk["tx_loss"],
                "trunk_rx_jitter": trunk["rx_jitter"],
                "trunk_rtt": trunk["rtt"],
                "trunk_rx_mes": trunk["rx_mes"],
            },
        )
        session.commit()

        logger.info(
            f"CDR {cdr_uuid}: trunk RTP rx={trunk['rx_count']} tx={trunk['tx_count']} "
            f"rxloss={trunk['rx_loss']} jitter={trunk['rx_jitter']}"
        )
