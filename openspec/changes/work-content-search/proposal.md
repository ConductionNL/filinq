---
kind: code
depends_on: []
---

# Proposal: work-content-search

Matrix row `wk-fulltext` in filinq's `openspec/parity/capabilities.json`,
rated no, built.state none. Written in the OpenSpec pass of 27 September 2026.

## Why

A Woo coordinator has to find every document that mentions "Lindenlaan
parking". filinq can only match file names. The search bar on My documents
filters the loaded rows on `fileName`
(`src/views/myDocuments/MyDocumentsIndex.vue:348`). The documents widget on
a record has no search at all, and the list behind it matches names only
(`lib/Service/FlatFileListService.php:90`). So she opens documents one by
one.

Much of the text is already there. OpenRegister reads a document's text
when filinq sends it for entity detection
(`lib/Service/AnonymizationService.php:216` calls
`TextExtractionService::extractFile()`), and keeps it in chunks. OpenRegister
can also read any file on request (`POST /api/files/{fileId}/extract`). Nothing lets a filinq user search those chunks,
and the archived `unified-search-provider` change made only `template` and
`signingRequest` objects findable, by their own fields.

### Matrix rows (filinq openspec/parity/capabilities.json)

| row | capability | filinq today |
|---|---|---|
| `wk-fulltext` | Find documents by words in their content. | no: My documents filters on the file name only, the record documents list matches names only; unified search finds templates and signing requests by their fields |

### Competitors rated yes

- ZyLAB ONE (docs read 2026-09-26): "search queries over the collected
  documents' text". Evidence:
  https://docs.zylab.com/01/ZyLAB/Woo-Dutch/050523.html
- Paperless-ngx (source read at v3.2.1): "Tantivy full-text index over
  content, notes and custom fields ... global search
  src/documents/views.py:3738". Evidence:
  https://github.com/paperless-ngx/paperless-ngx/blob/v3.2.1/docs/usage.md
- Decos JOIN (intelligence row recorded 2026-04-10): "Full-text Search:
  Full-text search across all cases, documents and metadata". Evidence:
  intelligence database, `competitor_features` row 25506; the matrix cell
  records no public URL.

## What changes

- "Name and content" search on My documents and on the documents widget
  a record shows. A match shows the words around the hit.
- The search runs over the text OpenRegister already extracted. filinq
  reads no chunks itself and builds no index.
- Only files the user can open come back. A list also says how many of its
  documents have no extracted text yet, and offers to read them.
- In Nextcloud's unified search, document content comes through
  OpenRegister's provider, as filinq's other results do. A hit on a filinq
  document opens in filinq.

## Capabilities

### New capabilities

- `document-content-search`: find filinq documents by the words in them,
  over OpenRegister's extracted text, limited to files the user can read.

### Modified capabilities

None. `unified-search-provider` keeps REQ-DDUSP-003: filinq registers no
search provider of its own.

## Impact

- `src/views/myDocuments/MyDocumentsIndex.vue`: a content mode for the
  search bar, hit snippets, the not-yet-read count, and opening the viewer
  from a `file` query parameter.
- `lib/Service/FlatFileListService.php` and
  `lib/Controller/CaseDocumentsController.php`: a content mode on the
  record documents list. `src/integrations/CnFilinqDocumentsWidget.vue`:
  a search field over it.
- New `lib/Service/ContentSearchClient.php`: the one PHP class that calls
  OpenRegister's content query, duck-typed. My documents calls the same
  query from the browser, as it already reads OpenRegister objects.
- `docs/features/`: a section with a screenshot.

## Out of scope

- An index, an OCR run or a text store of filinq's own.
- Searching inside anonymised originals a user may not open.
- Search operators beyond words and quoted phrases.
- Semantic (vector) search.

## Cross-app dependencies

- **openregister**: three things. (1) A keyword query over file chunks for
  the calling user that returns file id, rank and a short snippet, only
  for files that user can read, optionally limited to a list of file ids,
  with the unranked fallback on databases other than PostgreSQL. Today
  `ChunkMapper::searchByKeyword()` (`lib/Db/ChunkMapper.php:513`) is
  unscoped, and `FileSearchController::semanticSearch()` and
  `hybridSearch()` (`/api/search/files/*`, `#[NoAdminRequired]`) return
  chunk results with no check on the file. That last point is a read
  exposure in openregister and is reported to that lane. (2) File content
  hits in its unified search provider, scoped the same way; today
  `ContentSearchHandler` only maps chunks to their owning objects. (3) A
  way for an app to give a file hit its deep link, as `deepLinks` does for
  objects.
