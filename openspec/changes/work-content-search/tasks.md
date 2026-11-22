# Tasks: work-content-search

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 8. -->

## 1. Server

- [x] 1.1 Add `lib/Service/ContentSearchClient.php`: resolve OpenRegister's scoped content query by name, pass words and file ids, return file id, rank and snippet, and report "not available" on an older OpenRegister (D1, D2) (REQ-WCS-001, REQ-WCS-003). Verify: PHPUnit with a stub query and with the query absent; coverage on the new PHP is at least 75% (ADR-009).
- [x] 1.2 Add a content mode to `FlatFileListService::listFor()` and `caseDocuments#files`: name matches first, then content matches with snippet, plus the count of documents without extracted text (D3, D4) (REQ-WCS-001, REQ-WCS-002). Verify: PHPUnit asserts ordering, the count, and that a file the stub query leaves out never appears.
  - Built: `ContentSearchClient` resolves `OCA\OpenRegister\Service\File\ScopedContentSearchService::searchFiles(string $words, int[] $fileIds, string $userId)` through the container. That is the name and shape filinq asks OpenRegister for in task 3.2; until it exists the container throws and the client answers `available: false`. The answer may be a list of hits or `{results, unextracted}`; a hit is `fileId` (or `file_id`), `rank`, `snippet`. Hits outside the asked file ids are dropped, one hit per file, best rank first. Pinned by `ContentSearchClientTest`.
  - Built: `FlatFileListService::listFor()` takes `mode` (`name`, the default, or `content`), and so does `caseDocuments#files` (`openapi.json` and the docs copy document it). In content mode the page lists name matches (`match: name`), then content matches by rank with `snippet` (`match: content`), and carries `content: {available, unread}`. A file the query leaves out, or a hit on a file outside the list, never appears. Pinned by `FlatFileListServiceTest::testContentModeListsNameMatchesThenContentMatchesByRank`, `::testAFileTheQueryLeavesOutNeverAppears`, `::testContentModeWithoutTheQueryKeepsTheNameSearch`, `::testNameModeAsksNoContentQuery`, and `CaseDocumentsControllerFilesTraceTest::testFilesPassesTheContentMode`.

## 2. Screens

- [ ] 2.1 Add the "Name" or "Name and content" switch to My documents; in content mode call OpenRegister's query with the listed file ids and show snippets with the words marked (D2, D3) (REQ-WCS-001). Verify: Playwright finds a sample document by a word that is not in its name.
- [ ] 2.2 Add the same search field to `CnFilinqDocumentsWidget.vue` over the content mode of `caseDocuments#files` (REQ-WCS-001). Verify: Playwright on a record detail page finds a linked document by a word in it.
- [ ] 2.3 Show "N documents have not been read yet and cannot be found by content" with "Read them now", which calls OpenRegister's extract route for those files (D4) (REQ-WCS-002). Verify: Playwright reads the count, triggers the reading, and the document is then found.
- [ ] 2.4 Open the viewer on My documents from a `file` route parameter, and declare the file deep link to OpenRegister once its hook exists (D5) (REQ-WCS-004). Verify: Playwright opens `/apps/filinq/my-documents?file=<id>` and the viewer shows that document.
  - Built so far: My documents reads the `file` route parameter on mount and when it changes (`MyDocumentsIndex::openFromRoute()`), resolves the id through a WebDAV SEARCH in the caller's own files (`src/services/fileDeepLink.js`), and opens the viewer on it; a file the caller has no access to is not opened and the page says so. Pinned by `tests/vitest/fileDeepLink.spec.js` and `tests/vitest/myDocumentsFileDeepLink.spec.js`. Left: declaring the deep link to OpenRegister, which waits for its file deep-link hook (task 3.2), and the Playwright check, which needs a live instance.

## 3. Strings, docs and siblings

- [ ] 3.1 Dutch and English strings (ADR-005), `@spec` tags on new methods, and a section in `docs/features/` with a screenshot of a content hit (ADR-010). Verify: `npm run check:l10n`, `npm run lint` and `composer check:strict` pass.
- [ ] 3.2 File openregister's half (scoped chunk query, file hits in unified search, a file deep-link hook) and the unscoped `/api/search/files/*` read exposure as issues on ConductionNL/openregister, and link them in this change's proposal. Verify: the issue URLs are in `proposal.md` under Cross-app dependencies.
