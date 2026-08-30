# Tasks: pdfua-verapdf-matterhorn

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 13.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register & seed data

- [ ] 1.1 Add the `accessibilityConformanceReport` schema (`fileId`, `flavour`, `compliant`, `failedCheckCount`, `failedChecks` clause/test/checkpoint references, `validatorVersion`, `validatedAt`, `trigger`) to `lib/Settings/filinq_register.json` — additive, union-merge only; references only, never document content (REQ-DDPUM-002)
- [ ] 1.2 Seed two `tests/sample-documents/` fixtures (generated, no personal data): one genuinely PDF/UA-1-conformant tagged PDF; one that passes the wave-1 heuristics but fails Matterhorn checkpoints (untagged content + figure without alt) — the "heuristics ≠ conformance" fixture

## 2. Shared backend (verapdf-validation)

- [ ] 2.1 Extend `verapdf-validation`'s `VeraPdfService` with `validateUa(File): array` invoking the same probed binary with `--flavour ua1`, parsing the same JSON shape (flavour/compliant/failedChecks/validatorVersion), typed error on timeout/crash/unparseable — NO second binary, probe, config or admin row (REQ-DDPUM-001)
  - Hard build dependency on `verapdf-validation`; verify `VeraPdfService` exists at apply time — apply the two together (see design D1 / Open Questions).

## 3. Backend

- [ ] 3.1 Add `accessibility`-category validator checks to `DocumentValidationService`: `pdfua-conformance-failed`, `accessibility-validator-unavailable` — PDF-only, per-profile opt-in, default `off`, validator-presence-gated, references only; wave-1 heuristic checks untouched (REQ-DDPUM-005)
- [ ] 3.2 New accessibility conformance endpoint `POST /api/validation/accessibility/{fileId}` — auth attribute, IDOR-safe user-folder resolution (404, no existence disclosure), persists/updates the `accessibilityConformanceReport` distinct from the PDF/A `conformanceReport` (REQ-DDPUM-002)
- [ ] 3.3 Remediation guidance from failure shape (Filinq-generated → regenerate accessible; imported/mPDF → re-author from accessible source, no retag of imported pages), i18n-keyed; verdict never claims certification (REQ-DDPUM-003)
- [ ] 3.4 Surface the validator verdict as the authoritative accessibility conformance when available; heuristics remain the labelled floor when absent; heuristic-pass + validator-fail surfaces as fail (REQ-DDPUM-004)

## 4. Frontend

- [ ] 4.1 PDF/UA conformance card on document detail (flavour, verdict, failed checkpoints, guidance); validator findings rendered in the existing `accessibility` findings group, source-labelled (REQ-DDPUM-002, REQ-DDPUM-004)
- [ ] 4.2 Heuristic-vs-validator labelling in the accessibility panel; "not validated" state when the binary is absent (REQ-DDPUM-001, REQ-DDPUM-004)

## 5. Quality

- [ ] 5.1 PHPUnit for `validateUa()` parsing/degradation, the two checks, endpoint resolution + dual-report persistence, guidance classification, verdict-hierarchy — 75% coverage on new code (ADR-009)
  - Run in container: `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.
  - Live-verify on Postgres (8080) with veraPDF installed: validate the heuristic-pass/Matterhorn-fail fixture → non-conformant verdict, findings + card; remove the binary → honest heuristic-floor degradation.
- [ ] 5.2 Playwright spec `tests/e2e/spec-coverage/pdfua-verapdf-matterhorn.spec.ts` for the `@e2e`-referenced scenarios
- [ ] 5.3 i18n EN + NL for all new UI + guidance strings (keys in English); nldesign theme check; new UI itself WCAG 2.1 AA (ADR-005, ADR-003)
- [ ] 5.4 Docs: `docs/features/pdfua-verapdf-matterhorn.md` with Playwright screenshots (conformance card, source-labelled findings, degradation) and the heuristic→validator hierarchy explainer (ADR-010)
- [ ] 5.5 Validate: `openspec validate pdfua-verapdf-matterhorn --type change --strict` passes; hydra gates green

## Quality checklist

- GDPR: reports and findings carry checkpoint/clause references only — no document content or personal data (AVG Art. 5(1)(c)); processing stays 100% local.
- Shared integration: reuses `verapdf-validation`'s `VeraPdfService`, probe, `filinq.verapdf.*` config and admin status row — no second validator surface.
- `pdfua-accessible-output` (heuristics) and `verapdf-validation` (PDF/A backend + `archival` category) specs are referenced/consumed, not modified; the profiles-defaults requirement is not re-modified here.
