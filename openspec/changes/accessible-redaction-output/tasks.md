# Tasks: accessible-redaction-output

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 9.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register + seed data

- [ ] 1.1 Add a `structurePreservation` sub-object to the `anonymizationLink` schema in `lib/Settings/filinq_register.json` (REQ-DDARO-004)
  - Fields `requested`, `preserved`, `tagCountBefore`, `tagCountAfter`, `lossReasons[]`, `veraPdfVerified?` (design.md D3); no entity values (Art. 5(1)(c)); register-i18n tags on user-facing strings; register version bump with changelog entry; extend one existing anonymizationLink seed with a preserved outcome; schema refs use slugs.

## 2. Backend

- [ ] 2.1 Pass `preserveTags` to OR's redaction engine on single + batch/folder runs (REQ-DDARO-001)
  - `AnonymizationService` and `BatchAnonymizeService`/`FolderBatchService` pass `preserveTags` (default ON for PDF via `filinq.redaction.preserve_tags_default`) to the OR processing call; additive, no signature break; non-PDF passes through and relies on engine `lossReasons`.

- [ ] 2.2 Implement `RedactionAccessibilityService` mapping the engine outcome (REQ-DDARO-002, REQ-DDARO-004)
  - Read `structurePreservation` from the processing result; map to `preserved`/`degraded`/`not-applicable`/`unknown` (fail-safe: absent block → `unknown` → treated as degraded, never crash, never false-preserved); write the outcome onto the run's `anonymizationLink`.

- [ ] 2.3 Add the accessibility clearance gate (REQ-DDARO-003)
  - Config `filinq.redaction.accessibility_gate` = `warn` (default) | `block` | `off`; on `degraded`/`unknown` warn+prominent-flag (default) or block-until-reason-override (recorded); wired next to the existing prohibition/consent clearance checks; never a hard block by surprise.

- [ ] 2.4 Add the presence-gated veraPDF verification hook (REQ-DDARO-005)
  - When `verapdf-validation` is present, validate the redacted output and set `veraPdfVerified`; validator contradiction downgrades `preserved`→`degraded`; absent → outcome labelled engine-reported-only; never invoke veraPDF directly (no duplication).

## 3. Frontend

- [ ] 3.1 Surface the accessibility outcome in the document report + review UI (REQ-DDARO-002)
  - Accessibility state chip (preserved/degraded/not-applicable) with tag counts and human-readable loss reasons; prominent flag when degraded; on the clearance decision surface too; manifest V2 shell; ADR-012 Cn components, NL Design tokens.

## 4. Quality

- [ ] 4.1 PHPUnit unit tests for `RedactionAccessibilityService` + the gate (REQ-DDARO-002, REQ-DDARO-003, REQ-DDARO-004)
  - Mapping matrix incl. fail-safe absent-block→unknown; degraded blocks under `block` and warns under `warn`; outcome recorded on `anonymizationLink` with no entity values; veraPDF-present downgrade-on-contradiction; min 75% on new code; run in the container (`docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`).

- [ ] 4.2 Playwright e2e `tests/e2e/spec-coverage/accessible-redaction-output.spec.ts` (REQ-DDARO-001, REQ-DDARO-002, REQ-DDARO-003)
  - Redact a tagged fixture PDF through the UI with OpenRegister on the Postgres dev instance; assert the preserved chip + tag counts on the report; assert the degraded warn-flag path; test through the UI; nldesign-theme accessibility pass.

- [ ] 4.3 i18n (EN + NL) for the chip/loss-reason/flag/gate strings; docs `docs/features/accessible-redaction-output.md` with MCP screenshots; run `openspec validate accessible-redaction-output --strict`
  - Keys in English; document the OR `tag-preserving-redaction` dependency, the `verapdf-validation` presence gate, the RVIHH/EAA evidence, and the explicit boundary vs `pdfua-accessible-output`.
