## ADDED Requirements

### Requirement: Renewal workflow clones active contract as new draft with parent linkage

The system SHALL support a renewal workflow via `POST /api/contracts/<id>/renew` that:
1. Creates a new Contract (new uuid) in status `draft`
2. Clones the latest ContractVersion from the parent contract
3. Prefills `counterpartyOrg`, `counterpartyContact`, `department`, `owner` from the parent
4. Resets the term dates: `startDate = today`, `endDate = today + renewalTermMonths`
5. Generates a new `contractNumber` (with a renewal counter or date suffix, e.g., "GD-2024-SLA-001-REN1")
6. Links parent → child via a `parentContractId` field on the new contract
7. Sets the parent contract status to `superseded`
8. Creates a ContractVersion on the new contract with a changeSummary indicating it's a renewal

#### Scenario: User initiates renewal
- **WHEN** a user with contract ownership POSTs `/api/contracts/<id>/renew`
- **THEN** the response is 201 with the new contract uuid
- **AND** the new contract has status `draft`, prefilled counterparty/owner/department fields
- **AND** the parent contract transitions to `superseded`
- **AND** the parent is still readable (not deleted)

#### Scenario: Renewal contract inherits prior version body
- **WHEN** a renewal is created
- **THEN** the new contract's initial ContractVersion.body contains a copy of the parent's latest version
- **AND** ContractVersion.changeSummary = "Renewal of [parent contract number]"
- **AND** the owner can edit the new version before resubmission

#### Scenario: Parent contract is marked superseded
- **GIVEN** a contract with status `active`
- **WHEN** a renewal is created from it
- **THEN** the parent's status changes to `superseded`
- **AND** subsequent GETs of the parent return status = superseded
- **AND** the parent is still readable and auditable

#### Scenario: Parent and child are linked bidirectionally
- **GIVEN** a renewal contract (child) created from an active contract (parent)
- **WHEN** the user views the child contract
- **THEN** a `parentContractId` field is visible, linking to the parent
- **AND** when viewing the parent, a `renewalContractId` field is visible, linking to the child

#### Scenario: Only one active renewal per contract is allowed
- **GIVEN** a contract with one active renewal in draft status
- **WHEN** a user tries to create another renewal from the same parent
- **THEN** the response is 400 indicating "A renewal in draft status already exists for this contract"
- **AND** no second renewal is created

### Requirement: Renewal contract cannot be created if parent is not in a renewablestate

Renewal is only allowed if the parent contract has status `active` or `expiring_soon`. Contracts in `draft`, `in_review`, `awaiting_signature`, `expired`, `terminated`, or `superseded` status cannot be renewed.

#### Scenario: Renewal is rejected for draft contract
- **WHEN** a user tries to renew a contract in status `draft`
- **THEN** the response is 400 with message "Contract must be active or expiring_soon to renew"
- **AND** no renewal is created

#### Scenario: Renewal is allowed for expiring_soon contract
- **WHEN** a user tries to renew a contract in status `expiring_soon`
- **THEN** the response is 201 and the renewal is created

### Requirement: Renewal term dates are calculated from renewalTermMonths

When a renewal is created, the system SHALL calculate:
- `startDate = today`
- `endDate = today + renewalTermMonths` (as months, preserving day-of-month where possible)

If the parent contract has `autoRenew = false`, the `renewalTermMonths` defaults to the term length of the original contract (calculated as endDate − startDate).

#### Scenario: Renewal dates are calculated correctly
- **GIVEN** a contract with `renewalTermMonths: 12`
- **WHEN** a renewal is created on 2025-06-15
- **THEN** the new contract has `startDate: "2025-06-15"` and `endDate: "2026-06-15"`

#### Scenario: Month boundary is preserved
- **GIVEN** a contract with original startDate 2024-01-31, renewalTermMonths 12
- **WHEN** renewal is created on 2025-01-31
- **THEN** the new contract `endDate: "2026-01-31"` (not 2026-02-28, preserving day-of-month)

### Requirement: Renewal contract resets approval and signature state

When a renewal contract is created, it starts in `draft` status with no approvals or signatures. If the parent had approval policies, those MUST NOT be inherited; the renewal goes through the same approval flow as any new contract of the same type.

#### Scenario: Renewal contract has no pre-existing approvals
- **WHEN** a renewal is created
- **THEN** `GET /api/contracts/<renewalId>/approvals` returns an empty array
- **AND** when the renewal transitions to `in_review`, a fresh approval chain is created per the approval policy for this contractType

#### Scenario: Renewal contract has no pre-existing signatures
- **WHEN** a renewal is created
- **THEN** `GET /api/contracts/<renewalId>/signatures` returns an empty array

### Requirement: Renewal parent-child relationship is queryable

The system SHALL support queries to find all renewals of a contract and to find the parent of a renewal.

#### Scenario: List all renewals of a contract
- **WHEN** a user GETs `/api/contracts/<parentId>/renewals`
- **THEN** the response lists all contracts with `parentContractId: <parentId>` (regardless of status)

#### Scenario: Find parent of a renewal
- **WHEN** a user GETs `/api/contracts/<renewalId>`
- **THEN** the response includes a `parentContractId` field with the UUID of the parent contract
- **AND** the parent is also accessible via `GET /api/contracts/<parentId>`

### Requirement: User is notified when renewal is created

When a renewal is automatically triggered (via auto-renewal reminder) or manually created, the contract owner is notified via docudesk notification + email with a link to the new draft renewal contract.

#### Scenario: Manual renewal sends notification
- **WHEN** a user POSTs to create a renewal
- **THEN** the contract owner receives a docudesk notification: "New renewal draft created for [parent contract]. Review and submit for approval."
- **AND** an email is sent with the same message + link to the renewal contract

#### Scenario: Auto-renewal sends notification
- **WHEN** a renewal reminder fires and auto-triggers a renewal (because `autoRenew: true`)
- **THEN** the owner is notified: "Contract [parent] has been automatically renewed. Draft [renewal] is ready for your review."
