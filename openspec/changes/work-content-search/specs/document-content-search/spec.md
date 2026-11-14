# document-content-search Specification (delta)

## Purpose

A clerk finds a document by the words in it, from the lists she works in.
The text and the index are OpenRegister's, and only files she can open
come back. Matrix row `wk-fulltext` (filinq).

## ADDED Requirements

### Requirement: A list finds documents by words in their content (REQ-WCS-001)

My documents and the documents widget of a record MUST offer a "Name and
content" search. In that mode the list MUST show name matches first, then
documents whose extracted text contains the words, each with a short
snippet in which the words are marked. The search MUST run over
OpenRegister's extracted text. Filinq MUST NOT read chunks, keep text or
build an index of its own.

Rows: `wk-fulltext` (filinq matrix)

#### Scenario: A Woo coordinator finds a letter by a street name

- GIVEN a Woo coordinator on `/my-documents` whose folder holds a letter that mentions "Lindenlaan" in its text but not in its name
- WHEN she switches the search to "Name and content" and types "Lindenlaan"
- THEN the letter is listed with a snippet in which "Lindenlaan" is marked
- @e2e tests/e2e/document-content-search.spec.ts

#### Scenario: A clerk searches the documents of a record

- GIVEN a clerk on a record detail page whose documents widget lists five documents
- WHEN the clerk types a word that occurs in one of them only
- THEN the widget shows that one document with its snippet
- @e2e tests/e2e/document-content-search.spec.ts

### Requirement: A list says what it cannot search yet (REQ-WCS-002)

A content search MUST report how many documents in the list have no
extracted text. The list MUST say so and MUST offer to read them, which
asks OpenRegister to extract their text on the server.

Rows: `wk-fulltext` (filinq matrix)

#### Scenario: Unread documents are named, not hidden

- GIVEN a list of eight documents of which three were never extracted
- WHEN a clerk runs a content search
- THEN the list says "3 documents have not been read yet and cannot be found by content" and offers "Read them now"
- @e2e tests/e2e/document-content-search.spec.ts

### Requirement: Only files the user can open come back (REQ-WCS-003)

Content results MUST come only from OpenRegister's query scoped to the
calling user, which returns a file only when that user can read it. No
result, snippet or count MUST reveal a file the user cannot open. When
OpenRegister offers no scoped query, the list MUST say "Content search is
not available on this server" and MUST keep the name search working.

Rows: `wk-fulltext` (filinq matrix)

#### Scenario: A colleague's private document stays out

- GIVEN an employee's private document that contains "Lindenlaan" and is not shared with the Woo coordinator
- WHEN the Woo coordinator searches for "Lindenlaan" by content
- THEN that document, its snippet and its name appear nowhere in her results
- @e2e tests/e2e/document-content-search.spec.ts

#### Scenario: An older OpenRegister

- GIVEN an OpenRegister without the scoped content query
- WHEN a clerk switches to "Name and content"
- THEN the list says "Content search is not available on this server" and the name search still works
- @e2e exclude depends on the OpenRegister version; covered by PHPUnit with the query absent in task 1.1

### Requirement: Unified search finds document content through OpenRegister (REQ-WCS-004)

Filinq MUST NOT register a search provider of its own (REQ-DDUSP-003).
It MUST give OpenRegister a deep link for its document hits, and My
documents MUST open the viewer on the document named by a `file` route
parameter.

Rows: `wk-fulltext` (filinq matrix)

#### Scenario: A hit in the top bar opens in filinq

- GIVEN an employee who types a word from a filinq document into Nextcloud's unified search
- WHEN the employee picks the document hit
- THEN filinq's My documents opens with that document in the viewer
- @e2e exclude cross-app path; needs openregister's file hits in unified search, and the `file` parameter is covered by task 2.4
