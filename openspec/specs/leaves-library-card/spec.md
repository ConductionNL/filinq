# leaves-library-card Specification

## Purpose
How filinq's documents leaf sits inside a host app's case page: it reports
its count and empty state to nextcloud-vue's library card and lists a case's
documents in the linked card, as dossiq's DqZaak board draws them. Created by
archiving the change `leaves-library-card` (30 October 2026); it holds one
requirement, the documents leaf reporting to both cards.

## Requirements

### Requirement: The documents leaf reports to the library card and the linked card

The `filinq-documents` leaf SHALL declare `chrome: "card"`. Given `onHeader` it SHALL report its count and empty state
once read and SHALL NOT draw its own empty line. Given `onSummary` it SHALL draw nothing and SHALL report
`{ count, noun, rows }` once read, a row per document linking to it, and nothing while loading or after a failed read.

#### Scenario: a case with three documents in the Gekoppeld card

- **GIVEN** a case with three documents
- **THEN** the leaf reports count 3 and three rows linking to the files
