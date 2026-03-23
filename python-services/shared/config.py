from pydantic_settings import BaseSettings
from functools import lru_cache


class Settings(BaseSettings):
    # Database
    database_url: str = "mysql+pymysql://sail:password@mysql:3306/rswitch"
    async_database_url: str = "mysql+aiomysql://sail:password@mysql:3306/rswitch"

    # Redis
    redis_url: str = "redis://redis:6379/0"

    # Asterisk AMI
    asterisk_ami_host: str = "asterisk"
    asterisk_ami_port: int = 5038
    asterisk_ami_user: str = "admin"
    asterisk_ami_secret: str = "admin"

    # App
    debug: bool = False
    log_level: str = "info"

    # Rate cache
    rate_trie_ttl: int = 300  # seconds
    rate_cache_ttl: int = 300  # seconds

    class Config:
        env_file = ".env"


@lru_cache
def get_settings() -> Settings:
    return Settings()
