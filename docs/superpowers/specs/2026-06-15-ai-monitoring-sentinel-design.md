# rSwitch Sentinel — AI-assisted Auto-Monitoring & Alerting

**Status:** Design approved 2026-06-15. Implementation deferred ("implement later").
**Author:** brainstormed with operator (golamrabbany).

## 1. Goal

An automatic monitoring system that detects abnormal behaviour, system-down,
intrusion/hack, and billing fraud across the rSwitch VoIP platform and **notifies
the system admin**. "AI-assisted": layered detection = deterministic rules +
statistical anomaly detection + LLM incident triage.

## 2. Decisions (locked)

- **Notification channels:** Email (SMTP), SMS (critical-only, cost control),
  In-app dashboard + bell. *(Telegram intentionally NOT chosen.)*
- **Detection depth:** Layered — rules + statistical anomaly + LLM triage.
- **Domains:** all four — Infra/down, Telephony, Security/intrusion, Billing/fraud.
- **Build approach:** EXTEND the existing Prometheus + Alertmanager + fail2ban
  stack; add app-side incident store/notifier + anomaly sidecar + LLM triage +
  in-app Monitoring page. Do NOT rebuild metrics/alerting.

## 3. Current state (what already exists — reuse, don't reinvent)

- **Prometheus + Grafana**: active on the box; rswitch-api exposes call/AGI/trunk
  metrics; node/mysqld/redis exporters; installer ships the full stack.
- **Alertmanager + 13 alert rules** (`/etc/prometheus/rules/rswitch.yml`):
  rules exist BUT Alertmanager is **inactive** and receiver = `"null"` →
  **alerts currently notify nobody. This is the #1 gap.**
- **fail2ban**: active (SIP/SSH brute-force blocking).
- **App checks**: `CheckLowBalances`, `TrunkHealthCheck` artisan commands.
- Grafana dashboards bundled; alert rules bundled in `installer/templates/prometheus/`.

## 4. Architecture

```
 metrics sources                 brain                       delivery
 node_exporter ─┐                                         ┌─ Email (SMTP, warning+)
 mysqld/redis  ─┤                ┌───────────────┐        ├─ SMS (gateway, critical only)
 rswitch-api   ─┼─► Prometheus ──► alert+recording│        ├─ In-app bell + Monitoring page
 asterisk      ─┘   (baselines)  └──────┬────────┘        └─ daily Email digest
 fail2ban/auth.log ───────────────┐     ▼                       ▲
 call_records/transactions ─┐     │  Alertmanager ─webhook─┐    │
                            ▼     ▼  (ACTIVATE)             ▼    │
                   Anomaly Sidecar (Python) ───────► Incident Store (Laravel)
                   baselines + z-score/%drop          monitoring_incidents
                                                       dedup・group・severity・ack
                                                            │
                                                       LLM Triage (Claude API)
                                                       summarize・correlate・cause・action
```

New components: **Anomaly Sidecar**, **Incident Store + Notifier** (Laravel),
**LLM Triage**, **in-app Monitoring page**. Everything else already runs.

## 5. Three detection layers

1. **Rules (reliable base).** Prometheus alert rules + service-up + static/dynamic
   thresholds. Never misses hard failures (service down, disk full, trunk NonQual).
2. **Anomaly sidecar (statistical).** Python service; learns per-hour-of-week
   baselines for ASR, ACD, CPS, spend, registrations, unbillable-rate; flags
   deviations (z-score / % drop), e.g. "ASR -45% vs same hour last week",
   "spend rate 6× baseline". No API cost; deterministic.
3. **LLM triage (reasoning).** On each NEW/ESCALATING incident only, Claude
   correlates related signals into one plain-language incident: what happened,
   severity, likely cause, recommended action; dedups noise. Cost-controlled
   (per-incident, never per-metric). Model: latest Claude (e.g. claude-sonnet for
   cost/latency; escalate to opus for complex correlations). Hard monthly token cap.

## 6. Domains → concrete detections

| Domain | Detections |
|---|---|
| Infra/down | CPU/RAM/disk/load; MySQL·Redis·Asterisk·engine(uvicorn/celery)·nginx·supervisor down; CDR partition fullness; DB connection saturation; cert expiry |
| Telephony | ASR/ACD anomaly vs baseline; trunk down/NonQual; SIP registration-count drop; CPS spike; channel exhaustion; RTP-loss/one-way audio; 900s session-timer drop pattern |
| Security/hack | fail2ban bans → incidents; SIP registration-attack rate; SSH intrusion; admin login anomaly (new IP/geo, off-hours); config-file tampering (integrity hash) |
| Billing/fraud | spend-rate spike per reseller/client; sudden high-cost/international destinations (toll fraud); balance-drain rate; unbillable surge; abnormal CLI-rotation/CPS |

## 7. Notification routing

- **In-app bell + Monitoring page:** every incident; ack/resolve/escalate; unread badge.
- **Email:** all warning+; plus daily digest.
- **SMS:** critical only (system down, suspected hack, fraud spike).
- **Severity:** info / warning / critical. **Quiet hours.** **Escalation:** critical
  unacked in N min → SMS / repeat. **Dedup & grouping:** one outage = one incident.

## 8. Data model (Laravel)

`monitoring_incidents`:
- `id`, `source` (prometheus|anomaly|fail2ban|app-check|security), `domain`
  (infra|telephony|security|billing), `severity` (info|warning|critical),
  `status` (open|acked|resolved|auto_resolved), `fingerprint` (for dedup),
  `title`, `summary` (LLM), `cause` (LLM), `recommended_action` (LLM),
  `payload` (json: raw labels/metrics), `count` (occurrences), `first_seen_at`,
  `last_seen_at`, `acked_by`, `acked_at`, `resolved_at`, `notified_channels` (json).

`monitoring_baselines` (optional, or computed in-sidecar): metric, bucket
(hour-of-week / dow), mean, stddev, sample window.

`monitoring_settings`: channel toggles, recipient list (emails/phones), quiet
hours, per-severity routing, escalation timers, LLM monthly token cap.

## 9. Components (new + extended)

- **Extend Prometheus rules** (`installer/templates/prometheus/`): add recording
  rules (baselines) + alert rules per domain. Keep bundled so fresh installs get them.
- **Activate + configure Alertmanager**: receiver = webhook → `POST /api/internal/monitoring/alert`
  (HMAC-signed, localhost/allowlist). Installer change to enable the service + template the receiver.
- **Incident Store + Notifier (Laravel):**
  - migration `monitoring_incidents` (+ settings/baselines)
  - `App\Services\Monitoring\IncidentService` (create/dedup/group/ack/resolve)
  - `App\Services\Monitoring\NotifierService` (Email via Mail, SMS via gateway adapter, in-app via existing notification/bell)
  - internal webhook controller (Alertmanager + anomaly sidecar post here)
  - admin **Monitoring** page (incident feed, filters, ack/resolve, health tiles; reuse Grafana panels via iframe or native cards) + bell badge
  - artisan `monitoring:escalate` (scheduled) for unacked-critical escalation + digest
- **Anomaly Sidecar (Python, in python-services):** scheduled (celery beat or its
  own loop); reads Prometheus (PromQL) + DB; computes baselines; posts anomalies to
  the app webhook. Reuses `shared.config`/db session patterns.
- **LLM Triage:** invoked by `IncidentService` on new/escalating incidents; calls
  Claude API with the incident payload + recent correlated signals; writes
  summary/cause/action back. Token budget + caching by fingerprint.
- **Security feeders:** fail2ban action → webhook (or log tail) → incident; auth.log
  watcher; admin-login observer (new IP/geo); file-integrity check (hash key configs:
  pjsip, extensions, .env, nginx) via scheduled artisan.

## 10. Build phases (incremental; value early)

**Phase 1 — Delivery (make existing alerts actually notify).**
Activate Alertmanager; webhook receiver; `monitoring_incidents` table;
IncidentService + NotifierService (Email + in-app bell); admin Monitoring page +
bell. The existing 13 rules start notifying. *Ship this first.*

**Phase 2 — Coverage + SMS.**
Add alert + recording rules across all 4 domains; SMS adapter + critical routing;
quiet hours + escalation + daily digest.

**Phase 3 — Anomaly sidecar.**
Baselines (ASR/ACD/CPS/spend/registrations/unbillable) + deviation detection →
anomaly incidents.

**Phase 4 — LLM triage.**
Per-incident summarization/correlation/cause/action; dedup intelligence; token cap.

**Phase 5 — Security + fraud depth.**
fail2ban→incidents, SSH/auth watcher, admin-login anomaly, file-integrity,
toll-fraud detector (high-cost destination + spend-rate).

## 11. Cost / safety notes

- LLM only on new/escalating incidents, fingerprint-cached, hard monthly cap →
  predictable spend.
- SMS critical-only.
- Webhook auth (HMAC + localhost allowlist) so the incident endpoint can't be spoofed.
- All new alert/anomaly logic must be self-throttling (dedup) to avoid alert storms.
- Installer parity: bundle new rules + Alertmanager receiver template + migrations so
  fresh installs get Sentinel; existing installs get it via `update.sh` (migrate) + a
  one-time Alertmanager enable step.

## 12. Open items for implementation time

- Pick the **SMS gateway** (BD provider — e.g. an HTTP SMS API) and the email SMTP.
- Decide anomaly sidecar host: celery-beat task vs standalone supervisor service.
- Confirm Claude model + monthly token budget.
- Map Grafana panels to embed vs. native admin tiles for the Monitoring page.

## 13. Out of scope (YAGNI for v1)

- Auto-remediation/self-healing (only detect + notify; manual ack/resolve).
- Multi-tenant per-reseller alert routing (admin-only first).
- Mobile push app (in-app bell + email/SMS cover it).
