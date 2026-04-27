import asyncio
from typing import Awaitable, Callable, TypeVar

from sqlalchemy import create_engine
from sqlalchemy.ext.asyncio import create_async_engine, async_sessionmaker, AsyncSession
from sqlalchemy.orm import sessionmaker, Session, DeclarativeBase
from contextlib import contextmanager, asynccontextmanager

from shared.config import get_settings


class Base(DeclarativeBase):
    pass


# Sync engine (for Celery workers and AGI handler threads)
_sync_engine = None
_sync_session_factory = None

# Async engine (for FastAPI)
_async_engine = None
_async_session_factory = None


def get_sync_engine():
    global _sync_engine
    if _sync_engine is None:
        settings = get_settings()
        # Pool sized for: 1 API process running ~40 AGI threads concurrently
        # (db_thread executor) + Celery workers' own connections.
        # 50 connections per process × (1 API + 12 celery workers) ≤ 650,
        # comfortably under MySQL max_connections=800.
        _sync_engine = create_engine(
            settings.database_url,
            pool_size=30,
            max_overflow=20,
            pool_pre_ping=True,
            pool_recycle=300,
        )
    return _sync_engine


def get_async_engine():
    global _async_engine
    if _async_engine is None:
        settings = get_settings()
        _async_engine = create_async_engine(
            settings.async_database_url,
            pool_size=30,
            max_overflow=20,
            pool_pre_ping=True,
            pool_recycle=300,
        )
    return _async_engine


def get_sync_session_factory():
    global _sync_session_factory
    if _sync_session_factory is None:
        _sync_session_factory = sessionmaker(bind=get_sync_engine())
    return _sync_session_factory


def get_async_session_factory():
    global _async_session_factory
    if _async_session_factory is None:
        _async_session_factory = async_sessionmaker(
            bind=get_async_engine(),
            expire_on_commit=False,
        )
    return _async_session_factory


@contextmanager
def get_session() -> Session:
    """Sync session context manager for Celery workers."""
    factory = get_sync_session_factory()
    session = factory()
    try:
        yield session
        session.commit()
    except Exception:
        session.rollback()
        raise
    finally:
        session.close()


@asynccontextmanager
async def get_async_session() -> AsyncSession:
    """Async session context manager for FastAPI."""
    factory = get_async_session_factory()
    session = factory()
    try:
        yield session
        await session.commit()
    except Exception:
        await session.rollback()
        raise
    finally:
        await session.close()


T = TypeVar("T")


async def db_thread(fn: Callable[[Session], T]) -> T:
    """Run a sync DB callable in a thread with its own freshly-checked-out
    session, so the asyncio event loop is not blocked while the query runs.

    Each call gets its own Session from the connection pool — never share a
    Session across threads (SQLAlchemy Session is not thread-safe). The
    contextmanager commits on success and rolls back on exception.

    Usage:
        result = await db_thread(lambda s: s.execute(text("SELECT ...")).first())

        # or for multi-statement transactions:
        def _work(s):
            row = s.execute(text("SELECT ..."), {...}).first()
            s.execute(text("UPDATE ..."), {...})
            return row.id
        new_id = await db_thread(_work)

    Returns whatever `fn(session)` returns. Bind detached ORM objects to
    plain dicts/tuples before returning — the session is closed after the
    call and lazy-loaded attributes will fail.
    """
    def _runner() -> T:
        with get_session() as session:
            return fn(session)
    return await asyncio.to_thread(_runner)
