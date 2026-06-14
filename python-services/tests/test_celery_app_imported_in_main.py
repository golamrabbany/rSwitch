"""
Regression guard for the celery dispatch `[Errno 111] Connection refused` bug.

main.py dispatches billing work via `rate_and_charge.delay()`. Those are
`@shared_task`s — they bind to whatever the *current* Celery app is at dispatch
time. If the api process never instantiates the configured app (celery_app.py),
the current app is Celery's library default whose broker is RabbitMQ
(`amqp://localhost:5672`), which isn't running here → every dispatch is refused
and silently falls back to synchronous in-process rating.

The fix is for main.py to `import celery_app` at startup (mirrors how the worker
is launched with `-A celery_app`). This test fails if that import is removed.
"""

import pathlib
import re

MAIN = pathlib.Path(__file__).resolve().parent.parent / "main.py"


def test_main_imports_celery_app():
    src = MAIN.read_text()
    assert re.search(r"^\s*import celery_app\b", src, re.MULTILINE), (
        "main.py must `import celery_app` so shared_task.delay() uses the "
        "configured redis broker instead of the RabbitMQ default"
    )
