# Tasks

## 1. The pages tell the companion what they show

- [x] File viewer: `pageKind: "document"` and the open file id; released on leaving
- [x] Dossier: `pageKind: "folder"` and the home folder id; released on leaving
- [x] Tests: `tests/vitest/companionContext.spec.js`

## 2. The dossier detail names its folder

- [x] `DossierManagementService::detail()` returns `folderId`
- [x] Tests: `testDetailNamesTheHomeFolder`, `testDetailWithoutAHomeFolderNamesNone`

## 3. Verify

- [ ] A live check: open a document in filinq, the companion shows "Over dit document" and its answer cites that document
