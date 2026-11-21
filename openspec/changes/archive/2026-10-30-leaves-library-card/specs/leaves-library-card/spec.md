# leaves-library-card Delta

**Status**: proposed
**Scope**: filinq

## ADDED Requirements

### Requirement: The documents leaf reports to the library card and the linked card

The `filinq-documents` leaf SHALL declare `chrome: "card"`. Given `onHeader` it SHALL report its count and empty state
once read and SHALL NOT draw its own empty line. Given `onSummary` it SHALL draw nothing and SHALL report
`{ count, noun, rows }` once read, a row per document linking to it, and nothing while loading or after a failed read.

#### Scenario: a case with three documents in the Gekoppeld card

- **GIVEN** a case with three documents
- **THEN** the leaf reports count 3 and three rows linking to the files
