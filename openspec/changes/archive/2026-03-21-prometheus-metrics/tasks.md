# Tasks: prometheus-metrics

## Task 1: Metrics Endpoint
- [x] Implement `GET /api/metrics` with Prometheus text format
- [x] Set Content-Type to `text/plain; version=0.0.4; charset=utf-8`
- [x] Require admin authentication

## Task 2: Standard Metrics
- [x] Add `docudesk_info` gauge with version labels
- [x] Add `docudesk_up` gauge (health indicator)
- [x] Add `docudesk_documents_total` and `docudesk_templates_total` gauges
- [x] Add `docudesk_pdf_generations_total` and `docudesk_anonymizations_total` counters

## Task 3: Metrics Collector
- [x] Extract `MetricsCollector` from controller
- [x] Query OpenRegister for document/template counts via DB

## Task 4: Health Endpoint
- [x] Implement `GET /api/health` for infrastructure monitoring
- [x] Return JSON with status and version info

## Task 5: Unit Tests (ADR-009)
- [x] Write `MetricsControllerTest` for Prometheus format output
- [x] Test metrics collector count methods

## Task 6: Documentation (ADR-010)
- [x] Write feature documentation at `docs/features/prometheus-metrics.md`

## Task 7: i18n (ADR-005)
- [x] No user-facing strings (API-only endpoints)
