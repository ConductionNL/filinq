# Tasks: specify-capability-rows

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 11. -->

## 1. Signing

- [ ] 1.1 Add a delegation period to the mandate in `lib/Controller/SigningFolderController.php` and a screen to declare and withdraw one in `src/views/signing/` (REQ-SCR-003). Verify: PHPUnit refuses a delegate after the end date.
- [ ] 1.2 Add a phone code provider behind the signer-authentication seam in `lib/Service/Signing/` (REQ-SCR-004). Verify: PHPUnit for a right, a wrong and an expired code.

## 2. Documents

- [ ] 2.1 Make the columns of `src/integrations/CnFilinqDocumentsWidget.vue` sortable (REQ-SCR-005). Verify: Vitest sorts on date both ways.
- [ ] 2.2 Show `GET api/case-documents/mine` and a Waiting for me view on `src/views/myDocuments/MyDocumentsIndex.vue` (REQ-SCR-006). Verify: Playwright on `/my-documents`.
- [ ] 2.3 Write an audit entry for every action on a document (open, download, edit, version, share, anonymise, sign, delete) through OpenRegister's audit trail from the services in `lib/Service/`, and show it on the document's activity tab (REQ-SCR-007). Verify: PHPUnit that each action writes one entry with actor and moment.

## 3. Woo and reports

- [ ] 3.1 Add the active publication list and the withdraw-on-destruction listener to `lib/Service/Publication/PublicationPipelineService.php` (REQ-SCR-008, REQ-SCR-009). Verify: PHPUnit for a destroyed source.
- [ ] 3.2 Add the personal data report page to `src/manifest.json` (REQ-SCR-010). Verify: Playwright on `/reports`.

## 4. Anonymisation and templates

- [ ] 4.1 Store several profiles keyed by document type in `lib/Service/WooProfileService.php` (REQ-SCR-013). Verify: PHPUnit picks the profile of the document type.
- [ ] 4.2 Add a draft and published status to the template schema in `lib/Settings/filinq_register.json` and offer only published versions in `lib/Service/TemplateService.php` (REQ-SCR-015). Verify: PHPUnit renders the last published version.

## 5. Portal

- [ ] 5.1 Add a data-subject documents manifest to `lib/Portal/PortalContributionProvider.php` (REQ-SCR-016). Verify: PHPUnit lists only released documents of the requester.

## 6. Copy to another record

- [ ] 6.1 Add a copy action to the documents list and to a finished merge job, with the read and write checks on the server before the file is written, and an audit entry on both records (REQ-SCR-018). Verify: PHPUnit for a copy that lands and for a refused target.
