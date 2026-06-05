import os
import sys
import types

# Make the engine package importable as `shared.*`, `monitoring.*`, etc.
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

# panoramisk and pydantic_settings are not installed in the local/CI unit-test
# environment (they pull in heavy / native deps). The pure-logic units under
# test don't need a real AMI connection or env loading, so stub the modules
# before any test imports `monitoring.ami_listener` or `shared.config`. Loaded
# here in conftest so it applies to every test regardless of collection order.
if "panoramisk" not in sys.modules:
    _panoramisk = types.ModuleType("panoramisk")
    _panoramisk.Manager = object  # only referenced as a type hint
    sys.modules["panoramisk"] = _panoramisk

if "pydantic_settings" not in sys.modules:
    _ps = types.ModuleType("pydantic_settings")

    class _BaseSettings:
        """Minimal stand-in: apply class-level defaults, ignore env/dotenv."""

        def __init__(self, **kwargs):
            for name, default in type(self).__dict__.items():
                if (
                    not name.startswith("_")
                    and not callable(default)
                    and not isinstance(default, (classmethod, staticmethod, property))
                ):
                    setattr(self, name, default)
            for k, v in kwargs.items():
                setattr(self, k, v)

    _ps.BaseSettings = _BaseSettings
    sys.modules["pydantic_settings"] = _ps
