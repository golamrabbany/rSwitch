from sqlalchemy import Column, BigInteger, String, Enum, Numeric, Integer, DateTime
from shared.database import Base


class CallRecord(Base):
    __tablename__ = "call_records"

    id = Column(BigInteger, primary_key=True, autoincrement=True)
    uuid = Column(String(36), nullable=True)
    sip_account_id = Column(BigInteger, nullable=True)
    user_id = Column(BigInteger, nullable=False)
    reseller_id = Column(BigInteger, nullable=True)
    call_flow = Column(
        Enum("sip_to_trunk", "sip_to_sip", "trunk_to_sip", "trunk_to_trunk"),
        nullable=False,
    )
    call_type = Column(
        Enum("regular", "broadcast"),
        default="regular",
    )
    caller = Column(String(40), nullable=False)
    callee = Column(String(40), nullable=False)
    caller_id = Column(String(80), nullable=True)
    src_ip = Column(String(45), nullable=True)
    dst_ip = Column(String(45), nullable=True)
    incoming_trunk_id = Column(BigInteger, nullable=True)
    outgoing_trunk_id = Column(BigInteger, nullable=True)
    did_id = Column(BigInteger, nullable=True)
    broadcast_id = Column(BigInteger, nullable=True)
    destination = Column(String(100), default="")
    matched_prefix = Column(String(20), default="")
    rate_per_minute = Column(Numeric(10, 6), default=0)
    connection_fee = Column(Numeric(10, 6), default=0)
    rate_group_id = Column(BigInteger, nullable=True)
    call_start = Column(DateTime, nullable=False)
    call_end = Column(DateTime, nullable=True)
    duration = Column(Integer, default=0)
    billsec = Column(Integer, default=0)
    billable_duration = Column(Integer, default=0)
    total_cost = Column(Numeric(10, 4), default=0)
    reseller_cost = Column(Numeric(10, 4), default=0)
    trunk_cost = Column(Numeric(10, 4), default=0)
    disposition = Column(
        Enum("ANSWERED", "NO ANSWER", "BUSY", "FAILED", "CANCEL"),
        nullable=True,
    )
    hangup_cause = Column(String(50), nullable=True)
    status = Column(
        Enum("in_progress", "rated", "charged", "failed", "unbillable", "completed"),
        default="in_progress",
    )
    ast_channel = Column(String(80), nullable=True)
    ast_dstchannel = Column(String(80), nullable=True)
    ast_context = Column(String(40), nullable=True)
    rated_at = Column(DateTime, nullable=True)
    created_at = Column(DateTime, nullable=True)
