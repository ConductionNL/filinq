# Tasks — english-vocabulary (docudesk)

Scan: **2 schemas / 1 Dutch property**, **2 files / 2 classes / 12 methods**. The code
layer is twelve times the schema layer here, which is the reverse of most apps.

## 1. Measure and classify

- [ ] 1.1 Count stored `dossier` objects, excluding soft-deleted rows and reading the
      per-schema shard table rather than the shared objects table. The count decides
      whether task 4 exists.
- [ ] 1.2 Determine whether `GrondslagenSummaryService` emits Woo article identifiers as
      data. If it does, those values are preserved and only the identifiers are renamed.

## 2. Rename the schema

- [ ] 2.1 `dossier` → `RedactionDossier`, with a marker recording the Woo Art. 5 basis.
      Its four properties (`name`, `description`, `bases`, `checkedOn`) are already
      English and are not touched.
- [ ] 2.2 Confirm this does not collide with procest's `Case` — the two model different
      things, and the design records the evidence.

## 3. Rename code identifiers, PHP and frontend

- [ ] 3.1 Rename `DossierController` and `DossierCheckedOnListener`, and their files.
- [ ] 3.2 Rename `GrondslagenSummaryService` → `LegalBasesSummaryService`, preserving any
      Woo article identifiers it emits as values.
- [ ] 3.3 Rename the remaining Dutch methods across `lib/`, including `looksLikeDossier`,
      `isDossierObject`, `loadDossierBasesForFolder`, `walkDossierFiles`,
      `computeDossierPlaceholderMap`, `aggregateForDossier`, `saveDossierSummary`,
      `updateDossierConfiguration`, `resolveDossierFolder`, `loadDossierContext`,
      `renderDossierSummary`.
- [ ] 3.4 Rename `inferDossier` in `src/store/modules/anonymization.js`. A rename that
      stops at PHP leaves the Vue store naming the concept differently from the backend.
- [ ] 3.5 Update any register fragment that wires a listener or guard **by class name** —
      a renamed class silently stops being wired, and nothing raises.

## 4. Migrate stored data

- [ ] 4.1 If task 1.1 counted more than zero objects, migrate them to the new schema
      name. If zero, record the measurement — an evidenced skip, not an assumed one.

## 5. Hold the cross-app key

- [ ] 5.1 Do **not** rename `generatedDocument.zaakId`. Record it as blocked on procest,
      which owns `Case`; openconnector holds the same key and moves in the same window.

## 6. Translations and verification

- [ ] 6.1 Update `l10n/nl.json`, re-pointing existing keys rather than re-extracting;
      run `check-l10n`.
- [ ] 6.2 Re-run the token-aware scan; the only residual Dutch SHALL be the held `zaakId`.
- [ ] 6.3 Full test suite plus hydra gates 46 / 53 / 54 / 55 / 57 / 61.
- [ ] 6.4 Run one anonymisation through the UI end to end and confirm the redaction bases
      still resolve — a listener that stopped being wired produces no error, just no
      redaction.

## Acceptance criteria

- Token-aware scan shows only the deliberately-held `zaakId`.
- `RedactionDossier` exists with its Woo Art. 5 marker; no schema named `Case`.
- Every Woo article identifier is byte-identical to before the change.
- The Vue store and PHP layer use the same vocabulary.
- A UI anonymisation run produces the same redactions as before the rename.
