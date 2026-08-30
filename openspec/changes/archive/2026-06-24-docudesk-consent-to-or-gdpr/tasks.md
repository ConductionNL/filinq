# Tasks: docudesk-consent-to-or-gdpr

## 1. Assess the consent ↔ OR-GDPR boundary

- [x] 1.1 Enumerate docudesk's consent surface (`ConsentService`, `ConsentCrudService`,
  `ConsentController` `/api/consents/*`, `ObjectionDeadlineChecker`,
  `ConsentUpdateHandler`, `ConsentScopeValidator`, `ConsentNotesHelper`,
  `PolicyMatchService`).
- [x] 1.2 Compare against OR's `DataSubjectRequestService`
  (`findSubjectData` / `assembleAccessExport` / `rectify` / `erase` /
  `setRestriction` / `setObjection`) and `DataSubjectDeadline` (art-12(3) 1mo/+2mo).
- [x] 1.3 Scan docudesk for any data-subject-rights leg (subject-data discovery,
  access export, erasure, rectification, restriction, objection-to-processing).
  Result: NONE. The only "objection" is the WOO publication objection window.

## 2. Decision

- [x] 2.1 Conclude: docudesk consent = WOO publication-disclosure clearance, a
  different legal domain from OR's GDPR data-subject rights. No leg duplicates OR.
- [x] 2.2 Conclude: the only delegation candidate (`ObjectionDeadlineChecker` →
  `DataSubjectDeadline`) would change a legal control (28-day configurable WOO period
  vs fixed 1-month art-12(3) term). STOP — no `OrGdprBridge`, no re-point.
- [x] 2.3 KEEP the NER anonymisation pipeline untouched.

## 3. Safe partial — pre-existing l10n fix

- [x] 3.1 Reproduce the `test:l10n` failure (missing English source strings across
  the frontend, including `src/views/consent/StandingConsentIndex.vue`).
- [x] 3.2 Run docudesk's own l10n extraction (`npm run test:l10n:write`) so every used
  `t('docudesk', '…')` / `n(...)` key is present in `l10n/en.json` as
  `"<source>": "<source>"` (English source === key, NC convention).
- [x] 3.3 Confirm `npm run test:l10n` PASSES and `l10n/en.json` is valid JSON.

## 4. Verify

- [x] 4.1 `npm ci` + `npm run build` green.
- [x] 4.2 `npm run test:unit` green.
- [x] 4.3 Hydra mechanical gates green (esp. gate-17 redundant-controller,
  gate-27 no-phantom-cross-app-rpc, gate-3 stub-scan — nothing introduced).
- [x] 4.4 No PHP changed; no new cross-app RPC introduced.
