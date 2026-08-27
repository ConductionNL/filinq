# Tasks: tmlo-mdto-metadata

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 14.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register + seed data

- [ ] 1.1 Add the `mdtoSupplement` schema to the document register in `lib/Settings/filinq_register.json` (REQ-DDTMM-002, REQ-DDTMM-020)
  - All properties/enums per design.md D1; `hardValidation: true`; no retention-owned attribute duplicated; no `x-openregister-archival`; register version bump.

- [ ] 1.2 Seed one demo supplement (design.md Seed Data)
  - Nil-UUID `objectRef`; one `avg-persoonsgegevens` beperkingGebruik; validates on import.

## 2. Backend

- [ ] 2.1 Implement `lib/Service/MdtoMappingService.php` — prefill (REQ-DDTMM-003)
  - `aggregatieniveau` from record type; `dekkingInTijd` from document/dossier dates; `beperkingGebruik` proposals from consent (`objection_received`/unresolved), prohibition matches and anonymised derivatives; proposals flagged, never exported unconfirmed; `betrokkene` values are references only.

- [ ] 2.2 Implement completeness validation (REQ-DDTMM-004)
  - `{complete, missing[]}` against the MDTO minimum set (+ dekkingInTijd for bewaren); verify against OR HEAD that the gate is a superset of `MdtoXmlGenerator::validateRequiredFields()` (pin with a unit test, not this spec's snapshot); wire the gate into the overbrenging surface from archiefwet-retention-engine.

- [ ] 2.3 Implement sidecar assembly + JSON projection (REQ-DDTMM-005)
  - Core elements fixture-pinned against OR's generator output for identical input; reuse `TmloService::MDTO_NAMESPACE`; bestand section with sha256; deterministic output; dossier aggregation reads through the dossier capability's surface (no dossier-register schema change — sibling ownership).

- [ ] 2.4 Implement the overbrenging handoff via OR's `EdepotTransferService` (REQ-DDTMM-006)
  - Gate → sidecar → SIP transfer with configured transport/profile; on confirm: `archiefstatus = overgebracht` + sidecar attached as OR file attachment; degradation to explicit manual-export download when no transport is configured; zero transport/HTTP code in Filinq (architecture test).

- [ ] 2.5 File the OpenRegister issues surfaced by this change (design.md)
  - (a) `tmlo` vs `retention` duplication — the two MDTO generators read different fields; (b) extension-element support in `MdtoXmlGenerator` so app supplements need no app-side XML assembly. Link both on tracking issue #237.

## 3. Frontend

- [ ] 3.1 MDTO panel on document detail (REQ-DDTMM-001/002/003)
  - Read-only core block from `retention`; supplement editing via `CnFormDialog`; proposal chips accept/edit/reject; NC CSS variables; NcSelect with `inputLabel`; modals in `src/modals/`.

- [ ] 3.2 Completeness banner + export/overbrengen actions on the overbrenging surface (REQ-DDTMM-004/005/006)
  - Missing-field list rendered verbatim; export XML + JSON; manual-delivery state when no transport configured.

## 4. Quality

- [ ] 4.1 PHPUnit unit tests — minimum 75% coverage on new code (ADR-009)
  - Run inside the container: `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.
  - Includes: prefill matrix (consent/prohibition/anonymisation states), gate-implies-generator pin, sidecar determinism (byte-identical rerun), dossier aggregation fixture, fake-TransportInterface transfer, register-lint (no retention duplication, no archival annotation), architecture tests (no retention/tmlo writes, no transport code).

- [ ] 4.2 Playwright e2e `tests/e2e/workflows/tmlo-mdto-metadata.spec.ts` covering the `@e2e`-referenced scenarios
  - MDTO panel, proposal confirmation, blocked transfer with missing-field banner, sidecar export, manual-delivery degradation; verify on the Postgres dev instance (port 8080); test through the UI.

- [ ] 4.3 i18n: EN + NL translations for all new UI strings (ADR-005)
  - Keys in English; MDTO domain vocabulary (waardering, beperkingGebruik, overbrenging, archiefvormer) preserved in both locales.

- [ ] 4.4 Documentation `docs/features/tmlo-mdto-metadata.md` with Playwright MCP screenshots (ADR-010)
  - Covers the metadata model split (retention core vs supplement), proposal workflow, completeness gate and both delivery paths.

- [ ] 4.5 Gates + validation
  - `composer check:strict` zero new violations; `openspec validate tmlo-mdto-metadata --strict` exits 0; fix pre-existing quality issues encountered on touched files.
