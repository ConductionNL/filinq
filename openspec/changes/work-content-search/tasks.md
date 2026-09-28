# Tasks: work-content-search

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 8. -->

## 1. Server

- [ ] 1.1 Add `lib/Service/ContentSearchClient.php`: resolve OpenRegister's scoped content query by name, pass words and file ids, return file id, rank and snippet, and report "not available" on an older OpenRegister (D1, D2) (REQ-WCS-001, REQ-WCS-003). Verify: PHPUnit with a stub query and with the query absent; coverage on the new PHP is at least 75% (ADR-009).
- [ ] 1.2 Add a content mode to `FlatFileListService::listFor()` and `caseDocuments#files`: name matches first, then content matches with snippet, plus the count of documents without extracted text (D3, D4) (REQ-WCS-001, REQ-WCS-002). Verify: PHPUnit asserts ordering, the count, and that a file the stub query leaves out never appears.

## 2. Screens

- [ ] 2.1 Add the "Name" or "Name and content" switch to My documents; in content mode call OpenRegister's query with the listed file ids and show snippets with the words marked (D2, D3) (REQ-WCS-001). Verify: Playwright finds a sample document by a word that is not in its name.
- [ ] 2.2 Add the same search field to `CnFilinqDocumentsWidget.vue` over the content mode of `caseDocuments#files` (REQ-WCS-001). Verify: Playwright on a record detail page finds a linked document by a word in it.
- [ ] 2.3 Show "N documents have not been read yet and cannot be found by content" with "Read them now", which calls OpenRegister's extract route for those files (D4) (REQ-WCS-002). Verify: Playwright reads the count, triggers the reading, and the document is then found.
- [ ] 2.4 Open the viewer on My documents from a `file` route parameter, and declare the file deep link to OpenRegister once its hook exists (D5) (REQ-WCS-004). Verify: Playwright opens `/apps/filinq/my-documents?file=<id>` and the viewer shows that document.

## 3. Strings, docs and siblings

- [ ] 3.1 Dutch and English strings (ADR-005), `@spec` tags on new methods, and a section in `docs/features/` with a screenshot of a content hit (ADR-010). Verify: `npm run check:l10n`, `npm run lint` and `composer check:strict` pass.
- [ ] 3.2 File openregister's half (scoped chunk query, file hits in unified search, a file deep-link hook) and the unscoped `/api/search/files/*` read exposure as issues on ConductionNL/openregister, and link them in this change's proposal. Verify: the issue URLs are in `proposal.md` under Cross-app dependencies.
