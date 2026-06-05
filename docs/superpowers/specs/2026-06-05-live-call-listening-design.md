# Live Call Listening — Design Spec

**Date:** 2026-06-05
**Status:** Approved design, pending implementation plan
**Feature:** Super-admin can silently listen to a live call's audio, in-browser, from the Active Calls page — with stereo separation (caller = left, callee = right) and a live sound-bar visualizer.

---

## 1. Summary

Add the ability for a **super admin** to click a live call on the Active Calls page, open a modal, and **listen to the live conversation in the browser**. Listening is:

- **Super-admin only** (mirrors recording playback, which is admin-only).
- **Fully silent** — neither party hears anything (covert monitoring via ChanSpy `q`/one-directional flags; the spy never injects audio toward the call).
- **Stereo-separated** — the **source/caller** party plays on the **left** channel, the **destination/callee** party on the **right**.
- **Listen-only** — no whisper, no barge, no recording controls in this feature.
- **Audited** — every listen start/stop is written to the audit log.

Chosen transport: **AudioSocket → WebSocket → Web Audio** (Approach 1). Rejected alternatives: WebRTC/SIP.js endpoint (new WebRTC stack on a production switch) and ARI External Media (new ARI subsystem) — both bring whole new subsystems for no benefit on a silent listen-only feature.

---

## 2. Why stereo requires two spy legs

AudioSocket is a **mono** transport — one TCP stream carries one channel of audio. A single `ChanSpy` returns both directions *already mixed* into one mono stream, which cannot be un-mixed into left/right afterward.

To achieve true L/R separation, each listen session originates **two** spy legs, each capturing one direction via the ChanSpy `o` flag ("only audio coming from this channel"):

- **Left stream** = `ChanSpy(<caller-leg-channel>, qoE)` → the source party's voice only.
- **Right stream** = `ChanSpy(<callee-leg-channel>, qoE)` → the destination party's voice only.

The engine already tracks both call legs via `linked_id` and knows `call_flow` (inbound/outbound), so it maps source → Left and destination → Right correctly in both directions. The browser writes the two mono streams into a **stereo** AudioWorklet (output channel 0 = left, channel 1 = right).

**ChanSpy flags:** `q` = quiet (no spoken announcement on the spy leg; ChanSpy never sends audio toward the monitored parties), `o` = one-directional capture, `E` = exit when the spied channel hangs up.

**Cost:** each listen consumes **2 Asterisk channels + 2 AudioSocket connections**. The concurrent-listen cap is set with this doubling in mind.

**Drift:** the two spy legs are independent and may drift a few ms; imperceptible for human listening and absorbed by the per-channel jitter buffer. No sample-accurate alignment required.

---

## 3. Components (isolated units)

### 3.1 Asterisk (config only — no new dialplan)

- **Re-enable 3 modules** currently `noload`'d by the optimization (76 modules disabled): `res_audiosocket.so`, `chan_audiosocket.so`, `app_audiosocket.so`.
- Listening is triggered entirely via **AMI Originate** — no dialplan context needed. Each spy leg:
  - `Channel: AudioSocket/127.0.0.1:4574/<uuid>`
  - `Application: ChanSpy`
  - `Data: <target-channel>,qoE`
- Asterisk is the AudioSocket **client** — it connects *out* to the engine's TCP server on `127.0.0.1:4574`. Engine and Asterisk share the same box (split-server: both on the switch/DB box `.67`), so localhost binding needs no firewall change.

### 3.2 Python engine (`python-services/`)

- **`AudioSocketServer`** — asyncio TCP listener on `127.0.0.1:4574`. Parses AudioSocket framing: 1-byte type, 2-byte big-endian length, payload.
  - `0x01` = 16-byte UUID handshake (first frame; identifies which session/leg)
  - `0x10` = audio payload — SLIN, 8 kHz, 16-bit signed LE, 20 ms frames (320 bytes)
  - `0x00` = terminate
  - Emits `(uuid, pcm_bytes)` to the session manager; handles connection close.
- **`ListenSessionManager`** — owns listen sessions. A session = `{ browser_ws, left_uuid, right_uuid, left_channel, right_channel, started_at }`. Maps each `uuid → (session, side)`. Lifecycle: originate both legs → relay frames tagged L/R → on teardown issue AMI `Hangup` for both spy channels and close the TCP conns.
- **New WS route `/ws/listen`** on the existing FastAPI (port 8001, already covered by the nginx `/ws/` proxy — **no nginx change**). Flow:
  1. Accept connection, read `token` query param.
  2. Validate token (HMAC, expiry, `super_admin` role, channel binding). Reject with close code `4401` on failure.
  3. Resolve the two leg channels for the target (from AMI listener `linked_id` tracking; query AMI bridge/status if needed).
  4. Generate `left_uuid` / `right_uuid`; register both in the session manager.
  5. AMI Originate the two spy legs.
  6. Relay each incoming PCM frame to the browser as a binary message prefixed with a 1-byte side marker (`0x00` = left, `0x01` = right).
  7. On browser WS close → hang up both spy legs. On `0x00` terminate / target hangup → send `{"type":"call_ended"}`, close WS.

### 3.3 Laravel

- **`LiveListenController@token`** — `POST /admin/active-calls/{uniqueId}/listen-token`:
  - `abort_unless(auth()->user()->isSuperAdmin(), 403)`.
  - `AuditService::logAction('call.listen.start', …, {channel, caller, callee, unique_id})`.
  - Mint a short-lived signed token; return `{ token, wsUrl }`.
- **`ListenTokenService`** — HMAC-SHA256 sign/verify over `{channel, exp, uid, role}` using a new shared secret `LISTEN_TOKEN_SECRET` present in **both** the Laravel and engine `.env` (same private subnet, never exposed publicly). TTL ~30 s — long enough for the WS handshake; the live stream then persists on the open socket.
  - Note: there is **no existing Laravel↔engine token scheme** to reuse — the current `/ws/live-calls` socket is **unauthenticated**. This introduces the first one. (Hardening `/ws/live-calls` itself is out of scope here but noted as a follow-up.)
- **Audit stop**: on session teardown the engine notifies Laravel (existing engine→Laravel callback path) to write `call.listen.stop` with listen duration. If a direct callback is impractical, the engine logs stop locally and a lightweight reconcile records duration; final mechanism decided in the plan.

### 3.4 Frontend (`active-calls.blade.php` + JS/AudioWorklet)

- **Listen button** per row, wrapped in `@if(auth()->user()->isSuperAdmin())` — renders for nobody else.
- **Modal**: call header (caller → callee, trunk, direction, running duration), **Listen / Stop** toggle, connection-status line (Connecting → Live → Ended), and the **stereo sound-bar visualizer** (below). Minimal chrome — no waveform, no recording controls (matches the project's "subtract decorations" polish preference).
- **Stereo player**: an `AudioWorklet` with 2 output channels. Incoming binary frames are routed by the 1-byte side marker into the left or right ring buffer. Each channel: SLIN Int16 → Float32, resample 8 kHz → AudioContext rate, ~200 ms jitter buffer for smooth playback. `AudioContext` is created on the click (satisfies the browser autoplay-gesture rule).
- **Sound-bar animation**: a live **stereo level visualizer** — two bar groups (animated equalizer look), each driven by the RMS of its channel's PCM. Left bars react to the caller, right to the callee — doubling as an "audio is flowing" health indicator.

---

## 4. End-to-end data flow

```
Active Calls page              Laravel                Python engine                   Asterisk
─────────────────              ───────                ─────────────                   ────────
1. click Listen (row has  ──▶  2. authz super_admin
   unique_id + channel)           audit call.listen.start
                                  mint HMAC token (TTL ~30s,
                                  bound to channel)
                          ◀──     return {token, wsUrl}
3. open wss://…/ws/listen ───────────────────────▶ 4. verify token (HMAC+exp+role+channel)
   ?token=…                                          resolve caller-leg + callee-leg channels
                                                      gen left_uuid, right_uuid; register
                                                      AMI Originate ×2 ─────────────▶ 5. AudioSocket/127.0.0.1:4574/<uuid>
                                                                                          ×2, each ChanSpy(leg,qoE)
                                                   6. ◀── TCP connect + uuid handshake ×2 (Asterisk → :4574)
                                                      match uuid → (session, side)
                          ◀── binary PCM (side+pcm) ◀ 7. ◀── SLIN PCM, one stream per leg/direction
8. AudioWorklet: L=caller,
   R=callee; stereo bars animate
9a. Stop / close modal → WS close ────────────────▶ AMI Hangup both spy chans; close TCP; audit stop
9b. parties hang up → ChanSpy E exits → AudioSocket terminate (×2) → engine sends
    {"type":"call_ended"} → browser closes modal
```

The target `unique_id`/channel already arrives in the browser via the existing live-calls snapshot, so no extra lookup is needed to start.

---

## 5. Authorization & audit

- **Authorization**: `isSuperAdmin()` gate on the token endpoint (mirrors `RecordingController`). Non-super-admins get 403 and never receive a token. The engine independently re-checks the `role` claim in the token.
- **Token**: HMAC-SHA256 over `{channel, exp, uid, role}`, ~30 s TTL, bound to the specific target channel so a token can't be replayed against a different call. Engine rejects invalid/expired/role-mismatch/channel-mismatch with WS close code `4401`.
- **Audit**: `call.listen.start` on token issue (channel, caller, callee, unique_id, super_admin uid); `call.listen.stop` with listen duration on teardown. Tamper-evident record of every covert listen — required for this capability.

---

## 6. Error handling

| Condition | Behavior |
|-----------|----------|
| Call ended between snapshot and click | Originate fails → engine sends `{"type":"error","reason":"call_ended"}` → modal: "Call already ended." |
| Only one leg present (ringing, not yet bridged) | Stream the available leg; the missing side is silent until bridge/answer. No two-way audio exists pre-answer anyway. |
| AudioSocket modules not loaded | Originate/channel error → engine returns `{"type":"error","reason":"module_unavailable"}`; documented install requirement. |
| Token invalid / expired / wrong role / wrong channel | WS rejected with close code `4401`. |
| Concurrent listens | Each = its own pair of uuids/WS/spy channels. Concurrent-listen cap (default 3 sessions = up to 6 channels) protects the switch; over-cap returns `{"type":"error","reason":"capacity"}`. |
| Engine / AudioSocket server down | WS open fails; modal shows connection error and offers retry. |

---

## 7. Testing

**Python unit**
- AudioSocket frame parser: UUID handshake, audio frames, terminate, partial/split frames across TCP reads.
- `ListenSessionManager`: uuid→(session, side) mapping, two-leg pairing, teardown hangs up both legs and unregisters.
- Token verify: valid, expired, bad signature, wrong role, wrong channel.
- Leg resolution: correct caller/callee → L/R mapping for inbound vs outbound vs internal P2P.

**Laravel**
- Token endpoint: 403 for non-super-admin; 200 + audit row for super_admin.
- Token signature & TTL; channel binding.

**Manual integration**
- Place a test call → listen → confirm: caller audible on **left**, callee on **right**; parties hear **nothing** (silent); stereo bars animate per side; **Stop** hangs both spy legs; target hangup ends the session cleanly; audit start+stop recorded.

---

## 8. Installer / deploy changes

- Un-`noload` the 3 AudioSocket modules in `installer/install.sh`, `installer/install-engine.sh`, and the docker `modules.conf` so fresh installs retain the feature.
- Add `LISTEN_TOKEN_SECRET` to both `.env` files (Laravel + engine) — generated during install, surfaced in the credentials file.
- Port `4574` stays **localhost-only** (engine + Asterisk co-located) — no firewall/nginx change.
- Production rollout via SCP (no outbound HTTPS / no git on prod boxes), per deploy conventions; restart engine + reload Asterisk modules.

---

## 9. Out of scope (YAGNI)

- Whisper / barge modes.
- Reseller/client listening.
- Recording or downloading the live stream.
- Authenticating the existing `/ws/live-calls` metadata socket (noted as a separate follow-up).
- Sample-accurate L/R alignment.
