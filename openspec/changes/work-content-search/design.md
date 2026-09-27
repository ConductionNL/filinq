# Design: work-content-search

Kind: code. A content mode on two document lists, one client for
OpenRegister's content query, one deep link. The index and the access
check are OpenRegister's.

## Context

Read at development `2088cc1f`.

- `src/views/myDocuments/MyDocumentsIndex.vue:40` puts a `DdSearchBar` on
  `searchQuery`; `filteredDocuments` (:348) keeps rows whose `fileName`
  contains the text. The viewer is hosted on this page
  (`src/manifest.json`, page `MyDocuments`), and the page reads no route
  query.
- `src/store/modules/myDocuments.js:321` lists My documents with a WebDAV
  `PROPFIND` in the browser, and reads OpenRegister objects directly
  (:539).
- `lib/Service/FlatFileListService.php:82` `listFor()` takes a `search`
  that matches the file name only (:90). It backs `caseDocuments#files`
  (`appinfo/routes.php:89`), which `src/integrations/CnFilinqDocumentsWidget.vue`
  (:107) shows as the documents of a record, with no search field.
- `lib/Service/AnonymizationService.php:216` sends a file to OpenRegister's
  `TextExtractionService::extractFile()`. filinq holds no text of its own.
- `openspec/specs/unified-search-provider/spec.md` REQ-DDUSP-003: filinq
  registers no `OCP\Search\IProvider` and takes part in unified search only
  through OpenRegister's provider. `src/manifest.json` `deepLinks` maps
  five schemas to filinq routes; none maps a file.
- The open change `entity-search` lists "No full-text content search" as a
  non-goal of its own scope (design.md:58); it searches detected entities.
  This change is where content search lands.
- OpenRegister (read-only clone at `ae898b0`):
  - `lib/Db/ChunkMapper.php:513` `searchByKeyword()` ranks chunks with
    `ts_rank` on PostgreSQL and returns `[]` elsewhere unless asked for the
    unranked `LIKE` fallback. It takes no user and checks no access.
  - `lib/Controller/FileSearchController.php:84` `semanticSearch()` and
    `:154` `hybridSearch()` are `@NoAdminRequired` and return chunk results
    without checking that the caller may read the file.
  - `lib/Service/Object/ContentSearchHandler.php` widens an object search
    with objects whose attached files match (`_content_search`), and OR's
    unified search provider uses it. It returns objects, never files.
  - `appinfo/routes.php:1821` `POST /api/files/{fileId}/extract` reads a
    file's text on request.

New: `ContentSearchClient`, the content mode on both lists, the `file`
query parameter.

## Goals / Non-goals

Goals: find a document by its words from the two lists a clerk works in;
see why it matched; never learn anything about a file one cannot open;
see which documents cannot be found this way yet.

Non-goals: an index of filinq's own, semantic search, a unified search
provider in filinq, search operators beyond words and phrases.

## Decisions

### D1. The query is OpenRegister's, scoped to the caller

filinq asks OpenRegister's content query with the words and, where a list
has a scope, the file ids in that scope. OpenRegister returns file id,
rank and snippet for files the caller can read. filinq does not read
chunks, keep text or rank anything.

Alternative considered: use `/api/search/files/hybrid` as it is. Rejected.
It checks no file access, so a snippet of a file the clerk may not open
could reach her screen.

### D2. Each list asks where its list is built

The record documents list is built on the server, so `FlatFileListService::listFor()`
asks through `ContentSearchClient`, the one filinq class that calls the
query. It resolves OpenRegister's service by name and reports "content
search is not available" when OpenRegister is too old, instead of failing
the list. My documents is built in the browser from a WebDAV listing
(`src/store/modules/myDocuments.js:321`), so the page calls OpenRegister's
query route directly with the listed file ids, the way the store already
reads `anonymizationLink` objects from OpenRegister (:539). No filinq route
is added (ADR-022).

Alternative considered: one filinq route for both lists. Rejected. For My
documents it would only pass the call through.

### D3. Name and content in one bar

The search bar gets a switch: "Name" (today's behaviour, the default) or
"Name and content". In content mode the list shows name matches first,
then content matches with a two-line snippet, the matched words marked.
Switching needs no second field. The documents widget gets the same bar,
its first search field. Alternative considered: a separate search page.
Rejected; the clerk searches where she works.

### D4. Say what cannot be found yet

A content search also reports how many documents in the list have no
extracted text. The list says "12 documents have not been read yet and
cannot be found by content", with "Read them now", which calls
OpenRegister's extract route for those files. The reading runs on the
server, as all document processing does.

### D5. Unified search stays OpenRegister's

filinq registers no provider (REQ-DDUSP-003). It gives OpenRegister a deep
link for its document hits, `/apps/filinq/my-documents?file={fileId}`,
through the file deep-link hook openregister adds, and My documents opens
the viewer when it sees `file` in the route. Until the hook exists, a hit
opens in Files.

## Declarative-vs-imperative decision (ADR-031)

| concern | choice | why |
|---|---|---|
| index and ranking | OpenRegister | ADR-022; the chunks are OpenRegister's |
| access check | OpenRegister's query | the check must sit where the text is |
| list scope and snippet display | imperative, in the list service and the page | a presentation of an answer, no object behaviour |
| unified search | OpenRegister's provider, filinq deep link declared | REQ-DDUSP-003 |

## Seed data

No schema changes. The e2e fixture uploads two sample documents from
`tests/sample-documents/` and extracts them, so a content search has
something to find.

## Risks / trade-offs

- A document that was never extracted is invisible to content search. D4
  says so on the list rather than letting an empty result look complete.
- On MySQL or SQLite the fallback is unranked. Matches come back in chunk
  order; the docs say so.
- Search terms can themselves be personal data. filinq keeps none; what
  OpenRegister's search trail keeps follows its own settings.

## Open questions

- Should a Woo coordinator be able to search across every dossier she can
  read at once, not only one list? That is a wider page; it waits for a
  user asking for it.
