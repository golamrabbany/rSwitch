from sqlalchemy import Column, BigInteger, String, Enum, Numeric, Integer, Boolean
from shared.database import Base


class User(Base):
    __tablename__ = "users"

    id = Column(BigInteger, primary_key=True, autoincrement=True)
    name = Column(String(255), nullable=False)
    email = Column(String(255), nullable=False, unique=True)
    role = Column(Enum("super_admin", "admin", "recharge_admin", "reseller", "client"), nullable=False)
    parent_id = Column(BigInteger, nullable=True)
    hierarchy_path = Column(String(500), nullable=True)
    status = Column(Enum("active", "suspended", "disabled"), default="active")
    billing_type = Column(Enum("prepaid", "postpaid"), default="prepaid")
    balance = Column(Numeric(12, 4), default=0)
    credit_limit = Column(Numeric(12, 4), default=0)
    currency = Column(String(3), default="USD")
    rate_group_id = Column(BigInteger, nullable=True)
    min_balance_for_calls = Column(Numeric(10, 4), default=0)
    low_balance_threshold = Column(Numeric(10, 4), default=0)
    max_channels = Column(Integer, default=1)

    def is_prepaid(self) -> bool:
        return self.billing_type == "prepaid"

    def is_postpaid(self) -> bool:
        return self.billing_type == "postpaid"
