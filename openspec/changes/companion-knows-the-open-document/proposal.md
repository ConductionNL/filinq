---
kind: code
---

## Why

The AI companion can scope a question to one document or one folder and cite
the passages it used (hermiq `chat-about-a-document-cites-its-sources`;
nextcloud-vue shows the "About this document" chip from the page context). It
can only do that when the page says which file it shows. Filinq's file viewer
and dossier pages knew the file, but never told the companion, so a question
asked there searched everything.

## What Changes

- The file viewer sets the companion context to `pageKind: 'document'` and the open file's id.
- A dossier sets `pageKind: 'folder'` and its home folder's id; the dossier detail API now returns `folderId`.
- Leaving either page takes the file back out of the context.

## Impact

- `src/utils/companionContext.js`, `src/views/fileViewer/FileViewerPage.vue`, `src/views/dossier/DossierDetail.vue`
- `lib/Service/DossierManagementService.php` (`detail()` gains `folderId`)
