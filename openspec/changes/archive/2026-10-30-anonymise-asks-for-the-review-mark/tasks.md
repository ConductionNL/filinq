# Tasks: anonymise-asks-for-the-review-mark

### Task 1: A refused anonymise answers 409 with a message the reviewer can act on
- **spec_ref**: `openspec/changes/anonymise-asks-for-the-review-mark/specs/redaction-output-guarantee/spec.md#requirement-a-refused-anonymise-says-what-to-do-req-rwb-05`
- **files**: `lib/Controller/AnonymizationController.php`, `lib/Service/Redaction/RedactionReviewGate.php`, `lib/Exception/RedactionNotReviewedException.php`, `tests/unit/Controller/AnonymizationControllerReviewGateTest.php`
- **acceptance_criteria**:
  - GIVEN no mark WHEN anonymise is requested THEN 409, `reviewRequired: never_checked`, "Controleer eerst de gevonden gegevens", and the runner never runs
  - GIVEN a mark recorded over the sent entities WHEN anonymise is requested THEN it proceeds
- [x] Implement
- [x] Test

### Task 2: Recording the mark checks access first
- **spec_ref**: `openspec/changes/anonymise-asks-for-the-review-mark/specs/redaction-output-guarantee/spec.md#requirement-the-screens-record-the-check-before-they-anonymise-req-rwb-06`
- **files**: `lib/Controller/RedactionOutputController.php`, `tests/unit/Controller/RedactionOutputControllerTest.php`
- **acceptance_criteria**:
  - GIVEN a file the caller cannot open WHEN they mark it checked THEN 404 and nothing is written
  - GIVEN the store fails WHEN a mark is recorded THEN 500 with a translated message and no internals
- [x] Implement
- [x] Test

### Task 3: The checked status can be read per file
- **spec_ref**: `openspec/changes/anonymise-asks-for-the-review-mark/specs/redaction-output-guarantee/spec.md#requirement-my-documents-shows-who-checked-a-file-req-rwb-08`
- **files**: `lib/Controller/RedactionOutputController.php`, `lib/Service/Redaction/RedactionOutputGuard.php`, `appinfo/routes.php`, `tests/unit/Controller/RedactionOutputControllerTest.php`, `tests/unit/Service/Redaction/RedactionOutputGuardTest.php`
- **acceptance_criteria**:
  - GIVEN three ids, one the caller cannot open WHEN the status is read THEN only the two readable ids are looked up
- [x] Implement
- [x] Test

### Task 4: The screens ask for the check, then anonymise
- **spec_ref**: `openspec/changes/anonymise-asks-for-the-review-mark/specs/redaction-output-guarantee/spec.md#requirement-the-screens-record-the-check-before-they-anonymise-req-rwb-06`
- **files**: `src/services/reviewMark.js`, `src/components/ReviewMarkCard.vue`, `src/store/modules/anonymization.js`, `src/sidebars/FileViewerSidebar.vue`, `src/store/modules/folderAnonymization.js`, `src/views/anonymization/FolderAnonymizationView.vue`, specs next to the stores and the service
- **acceptance_criteria**:
  - GIVEN no check WHEN anonymise is clicked THEN no request is sent and the card asks for a check
  - GIVEN a check WHEN anonymise runs THEN the mark and the anonymise call carry the same entities
  - GIVEN a dossier or folder with checked and unchecked files WHEN the batch runs THEN only checked files are sent and the unchecked ones are listed
- [x] Implement
- [x] Test

### Task 5: My documents shows the checked status
- **spec_ref**: `openspec/changes/anonymise-asks-for-the-review-mark/specs/redaction-output-guarantee/spec.md#requirement-my-documents-shows-who-checked-a-file-req-rwb-08`
- **files**: `src/views/myDocuments/MyDocumentsIndex.vue`, `src/services/reviewMark.js`, `src/services/reviewMark.spec.js`
- **acceptance_criteria**:
  - GIVEN a page of files WHEN the list renders THEN one status read covers the page and each row says Gecontroleerd or Niet gecontroleerd
- [x] Implement
- [x] Test
