from sqlalchemy import Column, BigInteger, String, Enum, Numeric, Integer, Date
from shared.database import Base


class Rate(Base):
    __tablename__ = "rates"

    id = Column(BigInteger, primary_key=True, autoincrement=True)
    rate_group_id = Column(BigInteger, nullable=False)
    prefix = Column(String(20), nullable=False)
    destination = Column(String(100), nullable=True)
    rate_per_minute = Column(Numeric(10, 6), nullable=False)
    connection_fee = Column(Numeric(10, 6), default=0)
    min_duration = Column(Integer, default=0)
    billing_increment = Column(Integer, default=6)
    effective_date = Column(Date, nullable=False)
    end_date = Column(Date, nullable=True)
    status = Column(Enum("active", "disabled"), default="active")

    def to_cache_dict(self) -> dict:
        return {
            "id": self.id,
            "rate_group_id": self.rate_group_id,
            "prefix": self.prefix,
            "destination": self.destination,
            "rate_per_minute": str(self.rate_per_minute),
            "connection_fee": str(self.connection_fee),
            "min_duration": self.min_duration,
            "billing_increment": self.billing_increment,
            "effective_date": str(self.effective_date),
            "end_date": str(self.end_date) if self.end_date else None,
        }
