## ADDED Requirements

### Requirement: Contract register is installed in DocuDesk

The system SHALL define a new `contract` register in `lib/Settings/docudesk_register.json` under `components.registers`, alongside the existing `document`, `consent`, `signing`, and `templates` registers. The register SHALL be applied to OpenRegister on app install/upgrade via the existing `ConfigurationService::importFromApp()` path — no new loader code is required.

#### Scenario: Register is installed on fresh install
- **WHEN** DocuDesk is installed on a Nextcloud instance that has OpenRegister enabled
- **THEN** `RegistersLoader` creates a register with slug `contract`, Dutch title "Contracten Register", and schemas `contract`, `contractVersion`, `contractApproval`, `contractSignature`, `contractReminder`, `contractClause`, `contractClauseUsage`
- **AND** the register is visible via `GET /api/registers?_extend=schemas`

#### Scenario: Register is idempotent on upgrade
- **WHEN** DocuDesk is upgraded on an instance that already has the `contract` register installed
- **THEN** the register is not duplicated; the loader upserts by slug
- **AND** existing contract objects are preserved

### Requirement: Contract entity captures lifecycle state and contract metadata

The system SHALL define a `contract` schema with the following required properties: `contractNumber` (unique string identifier), `title` (string), `counterpartyOrg` (string or `$ref` to organisation), `counterpartyContact` (string or `$ref` to person), `contractType` (enum: dienstverlening, inkoop, arbeidsovereenkomst, NDA, licentie, SLA, raamovereenkomst, other), `value` (decimal, currency), `startDate` (ISO-8601 date), `endDate` (ISO-8601 date), `status` (enum: draft, in_review, awaiting_signature, active, expiring_soon, expired, terminated, superseded). Optional properties: `description` (rich text), `noticePeriodDays` (integer, days before endDate when notice must be given), `autoRenew` (boolean), `renewalTermMonths` (integer), `owner` (FK user), `department` (string), `tags[]` (array of strings).

#### Scenario: A contract can be created with all required fields
- **WHEN** a client POSTs `POST /api/contracts` with contractNumber, title, counterpartyOrg, counterpartyContact, contractType, value, startDate, endDate, and status set
- **THEN** the response is 201 with a `uuid`, and subsequent `GET /api/contracts/<uuid>` returns the same data
- **AND** the contract is listed in `GET /api/contracts?status=draft`

#### Scenario: Contract status is validated against allowed transitions
- **WHEN** a client tries to PUT a contract with an illegal status transition (e.g., draft → active without passing awaiting_signature)
- **THEN** the response is 400 with a message indicating the invalid transition
- **AND** the contract status is unchanged

#### Scenario: Missing required contractNumber is rejected
- **WHEN** a client POSTs a contract without contractNumber
- **THEN** the response is a validation error citing the missing required property

### Requirement: Contract versioning preserves prior versions immutable and produces redline diffs

The system SHALL define a `contractVersion` schema with the following properties: `contractId` (FK contract), `versionNumber` (integer, auto-incremented per contract), `createdBy` (FK user), `createdAt` (ISO-8601 timestamp), `body` (rich text or file reference, immutable), `changeSummary` (optional string describing changes from prior version), `redlineFromVersion` (optional FK to prior version). When a contract in draft or in_review status is edited, the system MUST create a new ContractVersion before saving changes, preserving the prior version immutable. The system SHALL produce a redline diff viewable in the API response (e.g., `GET /api/contracts/<id>/versions/<versionNumber>?include=redline`).

#### Scenario: Editing a contract creates a new version
- **WHEN** a contract in status draft is edited and saved
- **THEN** a new ContractVersion is created with versionNumber = previous + 1
- **AND** prior version's body is immutable; subsequent reads of prior version show original text
- **AND** the new version's body contains the updated text

#### Scenario: Redline diff is available between versions
- **WHEN** a client GETs `/api/contracts/<id>/versions/<versionNumber>?include=redline`
- **THEN** the response includes a `redline` object showing insertions/deletions between this version and `redlineFromVersion`
- **AND** the diff is human-readable (e.g., {+inserted text+}, {-deleted text-})

#### Scenario: Version history is immutable after contract moves to awaiting_signature
- **WHEN** a contract in status awaiting_signature is edited
- **THEN** the edit is rejected with a message indicating the contract is locked for signature
- **AND** no new version is created

### Requirement: Contract entity is linked to containing folder via `@self.folder`

Contracts SHALL be attached to a Nextcloud folder by setting the `@self.folder` metadata field to the folder's node ID (as a string) in the POST/PUT payload. The system SHALL reuse OpenRegister's existing `@self.folder` handling — no new DocuDesk endpoint or folder-attachment code is required.

#### Scenario: Creating a contract with an existing folder ID binds to that folder
- **WHEN** a client POSTs a contract with `@self.folder: "<existing-folder-node-id>"`
- **THEN** the created contract's stored `folder` matches the supplied node ID
- **AND** no new folder is created
- **AND** the contract is readable at `GET /api/contracts/<uuid>`

#### Scenario: `@self.folder` can be updated on an existing contract
- **WHEN** a client PUTs a contract with a different `@self.folder` value
- **THEN** the stored folder reference is updated to the new node ID without side-effects on other fields

### Requirement: Contract state machine validates allowed transitions

The system SHALL enforce a state machine that defines allowed transitions between contract statuses. The transitions SHALL be:
- `draft` → `in_review` (user submits for review)
- `in_review` → `draft` (user rejects review, returns to editing)
- `in_review` → `awaiting_signature` (all approvals granted)
- `awaiting_signature` → `in_review` (user returns to review)
- `awaiting_signature` → `active` (all signatures collected, or manual transition)
- `active` → `expiring_soon` (system-triggered when endDate − noticePeriodDays ≤ today)
- `active`, `expiring_soon` → `expired` (system-triggered when endDate ≤ today)
- `active`, `expiring_soon`, `expired` → `terminated` (user terminates early)
- `active` → `superseded` (when a renewal is activated; user cannot directly transition)

#### Scenario: Transition from draft to in_review succeeds
- **WHEN** a user PUTs `/api/contracts/<id>/status` with `status: "in_review"`
- **THEN** the response is 200, status changes to in_review
- **AND** ContractApproval records are created per the configured approval policy for this contractType

#### Scenario: Transition draft → active is rejected
- **WHEN** a user tries to PUT status directly from draft to active
- **THEN** the response is 400 with a message indicating the transition is not allowed
- **AND** status remains draft

#### Scenario: System transitions active → expiring_soon based on noticePeriodDays
- **GIVEN** a contract with status active, endDate 2026-07-15, noticePeriodDays 60
- **WHEN** today reaches 2026-05-16 (60 days before endDate)
- **THEN** a nightly system job evaluates the contract and sets status to expiring_soon
- **AND** a reminder is fired (per contract-reminders spec)

### Requirement: Contract countersignatory metadata is captured and immutable post-signature

The contract schema SHALL capture `counterpartyOrg` and `counterpartyContact` (at contract creation) and preserve them immutable once a signature envelope is sent. If the counterparty organisation/contact is updated in the source system, the contract record MUST NOT be updated; instead, a new contract for the updated counterparty is created for renewal or successor workflows.

#### Scenario: Counterparty fields are captured at contract creation
- **WHEN** a contract is created with counterpartyOrg "Company A" and counterpartyContact "john@companya.nl"
- **THEN** those fields are stored and visible in all subsequent reads

#### Scenario: Counterparty fields are locked post-signature
- **GIVEN** a contract with an active signature envelope
- **WHEN** a user tries to PUT the contract with a different counterpartyOrg
- **THEN** the response is 400 indicating the field is immutable post-signature
- **AND** counterpartyOrg remains unchanged
