# Tasks: redaction-four-eyes-setting

### Task 1: The setting exists, defaults to `woo` and refuses unknown values
- **spec_ref**: `openspec/changes/redaction-four-eyes-setting/specs/admin-settings/spec.md#requirement-the-admin-chooses-when-four-eyes-are-required-req-set-12`
- **files**: `lib/Service/SettingsService.php` (constant, default `woo`, `WRITABLE_KEYS`, value check in `updateSettings`, a `getFourEyesMode(): string` helper), `lib/Controller/SettingsController.php` (400 on an unknown value), `tests/unit/Service/SettingsServiceTest.php`
- **acceptance_criteria**:
  - GIVEN no stored key WHEN `getFourEyesMode()` is read THEN `woo`
  - GIVEN `sometimes` WHEN settings are updated THEN 400 and the stored value is unchanged
- [ ] Implement
- [ ] Test

### Task 2: The gate refuses the checker as anonymiser when the setting applies
- **spec_ref**: `openspec/changes/redaction-four-eyes-setting/specs/redaction-output-guarantee/spec.md#requirement-the-person-who-anonymises-differs-from-the-person-who-checked-req-rwb-09`
- **files**: `lib/Service/AnonymizeRun.php` (`wooCase` option and `isWooRun()`: `wooCase` true or scope `dossier`), `lib/Service/Redaction/RedactionReviewGate.php` (`same_person` and `anonymiser_unknown` refusals with Dutch messages), `lib/Service/Redaction/RedactionOutputGuard.php` (`assertMayWrite` takes the acting user and the Woo flag), `lib/Service/AnonymizationService.php` (`runAnonymize` passes them), `tests/unit/Service/Redaction/RedactionReviewGateTest.php`, `tests/unit/Service/Redaction/RedactionOutputGuardTest.php`
- **acceptance_criteria**:
  - GIVEN `woo`, a Woo run and checker = actor WHEN the guard runs THEN `RedactionNotReviewedException` with reason `same_person`
  - GIVEN `woo`, a non-Woo run and checker = actor THEN no refusal
  - GIVEN `always` and checker = actor THEN `same_person`; GIVEN `off` THEN no refusal
  - GIVEN the setting applies and an empty actor THEN `anonymiser_unknown`
- [ ] Implement
- [ ] Test

### Task 3: The batch and folder paths report `same_person` per document
- **spec_ref**: `openspec/changes/redaction-four-eyes-setting/specs/redaction-output-guarantee/spec.md#requirement-the-person-who-anonymises-differs-from-the-person-who-checked-req-rwb-09`
- **files**: `lib/Service/BatchAnonymizeService.php`, `lib/BackgroundJob/FolderExtractionJob.php` (pass the starter as `userId`), `lib/Service/Redaction/RedactionReviewGate.php` (`screenBatch` takes the actor), `tests/unit/Service/BatchAnonymizeServiceTest.php`
- **acceptance_criteria**:
  - GIVEN a dossier run by Fenna over one document Fenna checked and one Daan checked THEN one written, one refused with `same_person`
- [ ] Implement
- [ ] Test

### Task 4: The admin page offers the choice
- **spec_ref**: `openspec/changes/redaction-four-eyes-setting/specs/admin-settings/spec.md#requirement-the-admin-chooses-when-four-eyes-are-required-req-set-12`
- **files**: `src/views/settings/Settings.vue` (Anonymisation section: `NcCheckboxRadioSwitch` radio group "Vier ogen bij anonimiseren"), `l10n/nl.json`, `l10n/en.json`
- **acceptance_criteria**:
  - GIVEN a fresh install WHEN the section opens THEN "Alleen bij Woo-zaken" is selected; picking "Uit" saves `off`
- [ ] Implement
- [ ] Test

### Task 5: The review card says a colleague must anonymise
- **spec_ref**: `openspec/changes/redaction-four-eyes-setting/specs/redaction-output-guarantee/spec.md#requirement-the-person-who-anonymises-differs-from-the-person-who-checked-req-rwb-09`
- **files**: `src/components/ReviewMarkCard.vue`, `src/store/modules/anonymization.js` (a 409 with `same_person` shows the message instead of a generic error), the spec next to the card
- **acceptance_criteria**:
  - GIVEN the setting applies and the current user checked the document THEN the anonymise button is disabled with "Een collega moet dit document anonimiseren"
- [ ] Implement
- [ ] Test

### Task 6: dossiq flags Woo cases and shows the hint (built in ConductionNL/dossiq)
- **spec_ref**: `openspec/changes/redaction-four-eyes-setting/specs/redaction-output-guarantee/spec.md#requirement-the-person-who-anonymises-differs-from-the-person-who-checked-req-rwb-09`
- **files**: dossiq `lib/Service/FilinqRedactionClient.php` (`anonymize()` passes `wooCase`), dossiq `lib/Service/Redaction/DocumentRedactionService.php` (reads the case type's Woo flag; maps `same_person` to an outcome, not an exception), the DqLakken page
- **acceptance_criteria**:
  - GIVEN a Woo case and the same officer for check and redact THEN the outcome is `same_person` and the page asks for a colleague
- [ ] Implement
- [ ] Test

### Task 7: Verification and the row
- **files**: `tests/e2e/spec-coverage/redaction-four-eyes-setting.spec.ts` (two users: Fenna checks, Fenna is refused, Daan anonymises), `openspec/parity/capabilities.json`
- [ ] `@spec` tags on changed methods (gate 16) and the e2e scenarios (gate 19)
- [ ] When tasks 1 to 6 are done: set row red-four-eyes `filinq` to `yes`, cite REQ-RWB-09, archive this change
