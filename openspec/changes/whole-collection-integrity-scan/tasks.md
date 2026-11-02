# Tasks: whole-collection-integrity-scan

### Task 1: The scan service finds the three kinds
- **spec_ref**: `openspec/changes/whole-collection-integrity-scan/specs/document-versions/spec.md#requirement-a-scan-finds-missing-changed-and-orphaned-files-across-the-collection-req-dis-01`
- **files**: `lib/Service/IntegrityScanService.php` (new; reuses `FinalDocumentService::verifyChecksum()`, `DossierContextService::members()`, `AnonymizationLinkReader::forAnonymized()`, and the correspondence log), `lib/Settings/filinq_register.json` (`integrityScan` schema), `tests/unit/Service/IntegrityScanServiceTest.php`
- **acceptance_criteria**:
  - GIVEN a final document with a mismatching checksum THEN one `changed` finding and no write to the file
  - GIVEN a dossier member whose file id does not resolve THEN one `missing` finding naming the dossier
  - GIVEN a link whose source is gone THEN one `orphaned` finding
  - GIVEN a clean set THEN a stored run with zero counts
- [ ] Implement
- [ ] Test

### Task 2: Nightly job, occ command and notification
- **spec_ref**: `openspec/changes/whole-collection-integrity-scan/specs/document-versions/spec.md#requirement-the-scan-runs-nightly-and-on-demand-and-administrators-see-the-result-req-dis-02`
- **files**: `lib/BackgroundJob/IntegrityScanJob.php` (new, `TimedJob`, daily), `lib/Command/IntegrityScanCommand.php` (new, `filinq:integrity:scan`, prints the counts, exit 1 when there are findings), `appinfo/info.xml` (job and command), `lib/Notification/IntegrityScanNotifier.php` (new, registered the way `LegalHoldNotifier` is) (one notification per run with findings), `tests/unit/BackgroundJob/IntegrityScanJobTest.php`, `tests/unit/Command/IntegrityScanCommandTest.php`
- **acceptance_criteria**:
  - GIVEN a run with three `changed` findings THEN one notification per administrator
  - GIVEN a clean run THEN no notification and the command exits 0
- [ ] Implement
- [ ] Test

### Task 3: The admin section reads and starts a scan
- **spec_ref**: `openspec/changes/whole-collection-integrity-scan/specs/document-versions/spec.md#requirement-the-scan-runs-nightly-and-on-demand-and-administrators-see-the-result-req-dis-02`
- **files**: `lib/Controller/SettingsController.php` (`integrityScanLast()`, `integrityScanStart()`, admin only), `appinfo/routes.php`, `src/views/settings/Settings.vue` ("Integriteitscontrole" section: time, counts, findings with links, "Nu controleren"), `l10n/nl.json`, `l10n/en.json`, `tests/unit/Controller/SettingsControllerIntegrityScanTest.php`
- **acceptance_criteria**:
  - GIVEN a non-administrator WHEN the start endpoint is called THEN 403 and no run
  - GIVEN a stored run with two missing files THEN the section shows "2 ontbrekend" and two links
- [ ] Implement
- [ ] Test

### Task 4: Verification and the row
- **files**: `tests/e2e/spec-coverage/whole-collection-integrity-scan.spec.ts` (seed a dossier with a deleted member, run "Nu controleren", read the finding), `openspec/parity/capabilities.json`
- [ ] `@spec` tags on changed methods (gate 16) and the e2e scenarios (gate 19)
- [ ] When tasks 1 to 3 are done: set row op-integrity `filinq` to `yes`, cite REQ-DIS-01, archive this change
