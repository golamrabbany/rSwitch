from sqlalchemy import Column, BigInteger, String, Enum, Text
from shared.database import Base


class RateGroup(Base):
    __tablename__ = "rate_groups"

    id = Column(BigInteger, primary_key=True, autoincrement=True)
    name = Column(String(100), nullable=False)
    description = Column(Text, nullable=True)
    type = Column(Enum("admin", "reseller"), nullable=False)
    parent_rate_group_id = Column(BigInteger, nullable=True)
    created_by = Column(BigInteger, nullable=False)
