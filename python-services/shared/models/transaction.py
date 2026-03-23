from sqlalchemy import Column, BigInteger, String, Numeric, DateTime, Text
from shared.database import Base


class Transaction(Base):
    __tablename__ = "transactions"

    id = Column(BigInteger, primary_key=True, autoincrement=True)
    user_id = Column(BigInteger, nullable=False)
    type = Column(String(50), nullable=False)
    amount = Column(Numeric(12, 4), nullable=False)
    balance_after = Column(Numeric(12, 4), nullable=False)
    reference_type = Column(String(50), nullable=True)
    reference_id = Column(BigInteger, nullable=True)
    description = Column(String(255), nullable=True)
    source = Column(String(50), nullable=True)
    remarks = Column(Text, nullable=True)
    created_by = Column(BigInteger, nullable=True)
    created_at = Column(DateTime, nullable=True)
