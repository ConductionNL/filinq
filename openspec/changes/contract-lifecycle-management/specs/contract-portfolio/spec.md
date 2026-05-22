## ADDED Requirements

### Requirement: Contract portfolio search is fast and supports multi-faceted filtering

The system SHALL expose a `GET /api/contracts/search` endpoint that returns matching contracts within 2 seconds for portfolios up to 50,000 records. The endpoint SHALL support filtering by:
- `status` (enum: draft, in_review, awaiting_signature, active, expiring_soon, expired, terminated, superseded)
- `counterpartyOrg` (string, substring match or FK match)
- `contractType` (enum)
- `value` (numeric range, e.g., `value_min=50000&value_max=100000`)
- `startDate` and `endDate` (date range, e.g., `starts_after=2024-01-01&expires_before=2026-12-31`)
- `owner` (FK user)
- `department` (string)
- `tags` (array, match if contract has any tag)
- `clause` (string, full-text search on contract body or referenced clause codes)
- `sortBy` (default: endDate ascending; options: contractNumber, counterpartyOrg, value, startDate, endDate, status)

#### Scenario: Search returns contracts matching filters
- **WHEN** a user GETs `/api/contracts/search?status=active&counterpartyOrg=Company%20A&value_min=50000`
- **THEN** the response is 200 with matching contracts
- **AND** the response time is < 2 seconds for 50,000 records
- **AND** results are sorted by endDate ascending (default)

#### Scenario: Empty search returns all contracts (respecting ACL)
- **WHEN** a user GETs `/api/contracts/search` (no filters)
- **THEN** the response lists all contracts the user has access to
- **AND** the list is sorted by endDate ascending

#### Scenario: Combination filters narrow results
- **WHEN** a user GETs `/api/contracts/search?status=active&expires_before=2025-12-31&owner=alice`
- **THEN** the response includes only active contracts expiring before 2025-12-31 owned by alice

#### Scenario: Search is paginated for large result sets
- **WHEN** a search returns > 100 results
- **THEN** the response includes pagination: `{ items: [...], total: <count>, limit: 100, offset: 0, hasMore: true }`
- **AND** the user can paginate with `?limit=50&offset=50`

### Requirement: Full-text search on clause content finds contracts by legal text

The system SHALL support a `clause` filter on the search endpoint that searches the contract body for a phrase (full-text search) or searches for a specific clause code reference.

#### Scenario: Search by clause phrase
- **WHEN** a user GETs `/api/contracts/search?clause=confidential%20information`
- **THEN** the response lists all contracts whose body contains the phrase "confidential information"
- **AND** the matching text is highlighted in a preview (if available)

#### Scenario: Search by clause code
- **WHEN** a user GETs `/api/contracts/search?clause=CONF-2025-STANDARD-NL`
- **THEN** the response lists all contracts using the referenced clause (via ContractClauseUsage)

#### Scenario: Full-text search is case-insensitive
- **WHEN** a user searches for "Confidential" or "confidential"
- **THEN** both return the same results

### Requirement: Contract portfolio export to CSV/Excel respects per-row ACL

The system SHALL support an export endpoint `GET /api/contracts/search/export?format=csv` that returns search results in CSV format. The export SHALL respect the same ACL as the search endpoint — only contracts the user can read are included. The CSV SHALL include columns: contractNumber, title, counterpartyOrg, counterpartyContact, contractType, value, currency, startDate, endDate, status, owner, department, and optional columns for custom fields.

#### Scenario: Contracts are exported to CSV
- **WHEN** a user GETs `/api/contracts/search/export?format=csv&status=active`
- **THEN** the response is 200 with Content-Type text/csv
- **AND** the CSV includes a header row and one row per contract
- **AND** the file is downloadable with filename like "contracts_export_2025-06-15.csv"

#### Scenario: ACL is respected in export
- **WHEN** user alice exports contracts
- **THEN** the export includes only contracts alice has access to (own contracts, shared contracts)
- **AND** contracts alice cannot read are excluded (no error, just silent omission)

#### Scenario: Export to Excel (XLSX) is available
- **WHEN** a user GETs `/api/contracts/search/export?format=xlsx`
- **THEN** the response is a spreadsheet file with the same data as CSV
- **AND** the file includes formatting (headers bolded, currency values right-aligned)

### Requirement: Contract list view shows contract status badges and expiry countdown

The system SHALL provide a list view (or dashboard widget) that displays active contracts with visual indicators:
- Status badge (draft, in_review, awaiting_signature, active, expiring_soon, expired, terminated, superseded)
- Expiry countdown (e.g., "Expires in 45 days" for active contracts; "EXPIRED 10 days ago" for expired)
- Counterparty name
- Contract value
- Owner

Contracts with status `expiring_soon` or `expired` are sorted to the top for visibility.

#### Scenario: Contract list is sorted by expiry date
- **WHEN** a user opens the contract list view
- **THEN** the list is sorted by endDate ascending
- **AND** contracts expiring within 60 days are highlighted with a warning badge (orange/red)
- **AND** expired contracts are grayed out or shown with an "EXPIRED" badge

#### Scenario: Status badges are color-coded
- **WHEN** a user views the contract list
- **THEN** status badges are color-coded:
  - `draft` = gray, `in_review` = yellow, `awaiting_signature` = blue, `active` = green, `expiring_soon` = orange, `expired` = red, `terminated` = dark gray, `superseded` = light gray

#### Scenario: Expiry countdown updates daily
- **GIVEN** a contract expiring on 2025-07-15
- **WHEN** today is 2025-06-15, the list shows "Expires in 30 days"
- **WHEN** today is 2025-06-20, the list shows "Expires in 25 days"
- **AND** the countdown is client-side (no API call needed for every refresh)

### Requirement: Dashboard widget shows key contract metrics

The system SHALL provide an optional dashboard widget that displays:
- Total contract count (by status: active, expiring_soon, expired)
- Total contract value (sum of active contract values)
- Contracts expiring within 90 days (count + list)
- Contracts by counterparty (top 5 by total value)
- Contracts by type (pie chart or bar chart)

#### Scenario: Dashboard widget loads contract metrics
- **WHEN** a user opens the dashboard
- **THEN** a contract summary widget displays:
  - "23 active contracts, 5 expiring in 90 days, 2 expired"
  - "Total value: €2.5M"
  - "Top counterparty: Company A (€850k)"

#### Scenario: Widget metrics update on contract changes
- **WHEN** a contract transitions to `expiring_soon`
- **THEN** the dashboard widget count updates (within 1 minute, via refresh or live push)

### Requirement: Portfolio search supports saved searches and filters

Users can save filter combinations as named searches (e.g., "My Active Contracts", "Expiring This Quarter") for quick access.

#### Scenario: User saves a search
- **WHEN** a user applies filters and clicks "Save this search" with a name "My High-Value Active Contracts"
- **THEN** the search is saved with the filter criteria
- **AND** it appears in a dropdown list under "Saved Searches"

#### Scenario: Saved search is re-applied
- **WHEN** a user selects a saved search from the dropdown
- **THEN** the same filters are applied and results are returned

#### Scenario: Saved search is shared with team (optional)
- **WHEN** a user clicks "Share" on a saved search
- **THEN** they can grant read access to other users or teams
- **AND** shared searches appear in those users' saved-search list (optional for v1, deferred if needed)

### Requirement: Contract search is exposed via REST API with pagination

The search endpoint `GET /api/contracts/search` SHALL return paginated results with standard REST pagination: `limit` (default 50, max 500), `offset` (default 0), `total` (total count matching filters, not just page count).

#### Scenario: API returns paginated results
- **WHEN** a client GETs `/api/contracts/search?limit=20&offset=0`
- **THEN** the response includes:
  ```json
  {
    "data": [...20 contracts...],
    "total": 157,
    "limit": 20,
    "offset": 0,
    "hasMore": true
  }
  ```
- **AND** the client can fetch the next page with `?limit=20&offset=20`

#### Scenario: Total count is accurate
- **WHEN** a search returns 157 matching contracts
- **THEN** `total: 157` even if only 50 are returned in the current page
- **AND** the client can calculate pages: `Math.ceil(total / limit)`
