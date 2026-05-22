## ADDED Requirements

### Requirement: Clause library provides reusable legal text by category, jurisdiction, language

The system SHALL define a `contractClause` schema with properties: `clauseCode` (unique string identifier, e.g., "NDA-2025-CONF-01"), `title` (string, e.g., "Confidentiality Obligation"), `category` (enum: confidentiality, limitation_of_liability, indemnification, term_and_termination, payment_terms, warranty, ip_ownership, governing_law, dispute_resolution, other), `body` (rich text or markdown, the actual clause text), `jurisdiction` (enum: NL, BE, DE, other), `language` (enum: nl, en, fr, de), `deprecated` (boolean, default false), `lastReviewedBy` (FK user), `lastReviewedAt` (ISO-8601 timestamp). Clauses are immutable after creation (updated via new revisions with suffix in the code, e.g., "NDA-2025-CONF-01" → "NDA-2025-CONF-02").

#### Scenario: Clauses are seeded on install
- **WHEN** DocuDesk is installed
- **THEN** the system loads 6–10 seed clause objects covering common categories (confidentiality, payment, liability)
- **AND** each clause has a stable clauseCode, category, jurisdiction, and language
- **AND** they are visible via `GET /api/contract-clauses`

#### Scenario: Legal team adds a new clause to the library
- **WHEN** a legal user POSTs `/api/contract-clauses` with clauseCode, title, category, body, jurisdiction, language
- **THEN** the response is 201 with the new clause uuid
- **AND** the clause is visible in list views

#### Scenario: Clause is filtered by category and jurisdiction
- **WHEN** a user GETs `/api/contract-clauses?category=confidentiality&jurisdiction=NL&language=nl`
- **THEN** all clauses matching that category, jurisdiction, and language are returned

#### Scenario: Clause body is immutable; revisions use new clauseCode
- **GIVEN** a clause with clauseCode "NDA-2025-CONF-01"
- **WHEN** a legal user needs to update the clause text (e.g., law changed)
- **THEN** they create a new clause with clauseCode "NDA-2025-CONF-02" and the updated body
- **AND** the old clause is marked `deprecated: true`
- **AND** the old clause can still be read (for audit purposes)

### Requirement: Contract versions reference library clauses via ContractClauseUsage

The system SHALL define a `contractClauseUsage` schema with properties: `contractId` (FK contract), `clauseCode` (string, the referenced clause code), `versionId` (FK contractVersion), `position` (integer, order within the contract). When a contract version is created, the system records which library clauses are used in it (if the clause body is identified as matching a library clause). Users can also explicitly tag a clause in the contract body as referencing a specific library clause.

#### Scenario: Contract version references a library clause
- **WHEN** a user creates/edits a contract version and includes the exact text of library clause "NDA-2025-CONF-01"
- **THEN** the system creates a ContractClauseUsage record linking the version to the clause code
- **AND** the clause is visible in `/api/contracts/<id>/versions/<versionId>/clause-usage`

#### Scenario: User manually links a clause to a contract
- **WHEN** a user POSTs `/api/contracts/<id>/clause-usage` with `clauseCode: "NDA-2025-CONF-01"`, `versionId: <vid>`, `position: 3`
- **THEN** a ContractClauseUsage record is created
- **AND** the clause is visible as a tagged reference in the contract

#### Scenario: Contract can use multiple clauses
- **WHEN** a contract version uses clauses "NDA-2025-CONF-01", "LIABILITY-2025-LIM-01", and "PAYMENT-2025-TERMS-01"
- **THEN** three ContractClauseUsage records are created
- **AND** the list view shows all three with their positions

### Requirement: Deprecated clause usage is flagged with a warning

When a contract is viewed and contains a reference to a deprecated clause, the system SHALL display a warning to the owner/editor indicating that the clause is outdated and suggesting the newer version.

#### Scenario: Deprecation warning is shown on contract view
- **GIVEN** a contract using clause "NDA-2025-CONF-01" and that clause is marked `deprecated: true` (because "NDA-2025-CONF-02" is now current)
- **WHEN** the user views the contract
- **THEN** a warning banner is displayed: "This contract uses deprecated clause NDA-2025-CONF-01. Recommended replacement: NDA-2025-CONF-02"
- **AND** the user can click to review the newer version

#### Scenario: Deprecation does not block contract usage
- **WHEN** a contract uses a deprecated clause
- **THEN** the contract can still be edited, approved, and signed
- **AND** the warning is informational, not blocking

### Requirement: Clause usage is queryable and auditable

The system SHALL support queries to find all contracts using a specific clause and to view clause-usage history across versions.

#### Scenario: Find all contracts using a clause
- **WHEN** a legal user GETs `/api/contract-clauses/<clauseCode>/usage?status=active`
- **THEN** the response lists all contracts (filtered by status) that reference this clause
- **AND** the list includes contract number, counterparty, and date of usage

#### Scenario: View clause usage across versions
- **WHEN** a user views contract clause usage via `/api/contracts/<id>/clause-usage?include=versions`
- **THEN** the response shows all clauses referenced in each version of the contract
- **AND** the list is ordered by version (earliest to latest)

### Requirement: Clause library supports bulk import/export

The system SHALL support exporting the entire clause library to JSON/CSV and importing a JSON file to add multiple clauses at once (e.g., tenant-specific standard clauses).

#### Scenario: Clause library is exported
- **WHEN** a legal user GETs `/api/contract-clauses/export?format=json`
- **THEN** the response is a JSON file containing all clause definitions
- **AND** the file is downloadable

#### Scenario: Clauses are imported from JSON
- **WHEN** a legal user POSTs `/api/contract-clauses/import` with a JSON file containing clause definitions
- **THEN** the system validates the JSON schema
- **AND** creates new clause records for each entry (skipping if clauseCode already exists)
- **AND** returns a summary: "Imported 5 clauses, 2 skipped (already exist)"

### Requirement: Clause library includes seeded standard clauses

Per ADR-016, the clause library SHALL be seeded with 6–10 standard clauses covering common contract categories. These clauses are tailored for Dutch contexts (jurisdiction: NL, language: nl) with English translations available for international contracts.

#### Scenario: Seeded clauses are available after install
- **WHEN** DocuDesk is installed
- **THEN** `GET /api/contract-clauses` returns at least 6 clauses with codes like:
  - "CONF-2025-STANDARD-NL" (Confidentiality, NL jurisdiction)
  - "LIAB-2025-LIMIT-NL" (Limitation of Liability, NL)
  - "IP-2025-OWNER-NL" (IP Ownership, NL)
  - "TERM-2025-TERMINATION-NL" (Term and Termination, NL)
  - "PAY-2025-PAYMENT-NL" (Payment Terms, NL)
  - "GOVERN-2025-LAW-NL" (Governing Law — Dutch law, NL)
- **AND** English translations are available with language: en
