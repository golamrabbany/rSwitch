from monitoring.listen_audit import build_listen_stop_params


def test_build_listen_stop_params():
    params = build_listen_stop_params(uid=15192, linked_id="100.1", seconds=42)
    assert params["user_id"] == 15192
    assert params["action"] == "call.listen.stop"
    assert params["auditable_type"] == "system"
    assert params["auditable_id"] == 0
    assert '"linked_id": "100.1"' in params["new_values"]
    assert '"duration_seconds": 42' in params["new_values"]
    assert params["ip_address"] == "0.0.0.0"
    assert params["user_agent"] == "rswitch-engine"
