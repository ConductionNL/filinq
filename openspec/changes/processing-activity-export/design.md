## Context

Reworked per the 2026-06-11 abstraction decision: OpenRegister owns the Art. 30 verwerkingsregister mechanics (`processing-activity-register`, OR-PA-1..9); docudesk contributes content and a UI window.

## Division of labour

| Concern | Owner |
|---|---|
| Activity entity, controller identity, lifecycle | OpenRegister (OR-PA-1) |
| Catalogue dialect, seed-as-draft, attribution | OpenRegister (OR-PA-2) — docudesk supplies the four categories |
| Unclassified / no-grondslag bucket | OpenRegister (OR-PA-4) — fed by `EntityRelation.bases` absence |
| Art. 30 export (JSON/CSV/PDF), no-literal-PII contract, per-subject extract | OpenRegister (OR-PA-7) |
| Admin/FG gating, register-slice scoping | OpenRegister (OR-PA-8) |
| Category semantics, grondslag source, retention references, UI surfacing | **docudesk (this change)** |

## D1 — Categories are catalogue content, not an engine

The four docudesk activity categories exist as `x-openregister-processing` entries whose data-category fields reference the OR NER entity types and whose retention fields are filled from the schemas' existing `x-openregister-archival` annotations at authoring time ("not declared" stays visible when absent). OR's export renders them; docudesk computes nothing.

## D2 — Grondslagen flow through existing rows

`EntityRelation.bases[]` is already the grondslag record. OR's log/export aggregates it; rows without bases surface in the unclassified bucket (OR-PA-4) rather than disappearing — same precedent as the grondslagen summary cluster.

## D3 — UI is a scoped window

The docudesk admin compliance section renders OR's export surface scoped to docudesk's registers and shows the OR-maintained controller identity with a configure prompt. No docudesk endpoint serves aggregate data (ADR-022).
