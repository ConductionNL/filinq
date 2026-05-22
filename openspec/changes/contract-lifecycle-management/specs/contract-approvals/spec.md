## ADDED Requirements

### Requirement: Contract approval routing is configurable per contract type

The system SHALL define a `contractApproval` schema and a tenant-level configuration `contract.approval_policies` (in config.php or admin settings UI) that specifies approval chains per contract type. Each approval policy SHALL be a sequence of roles + optional value thresholds (e.g., "if value > €50k, require CFO approval after Legal"). The system SHALL enforce that a contract cannot transition from `in_review` to `awaiting_signature` until all approvals in the chain are recorded.

#### Scenario: Approval policy is configured per contract type
- **WHEN** a tenant's admin sets `contract.approval_policies['SLA'] = [{ role: 'legal', required: true }, { role: 'procurement', value_threshold: '€25k', required: if_above_threshold }]`
- **THEN** contracts of type SLA require Legal approval; if value > €25k, Procurement approval is also required
- **AND** the policy is visible via `GET /admin/contract-settings`

#### Scenario: Approval chain is resolved when contract enters in_review
- **WHEN** a contract of type SLA with value €75k transitions to `in_review`
- **THEN** the system creates two ContractApproval records: one for Legal (sequenceOrder: 1), one for Procurement (sequenceOrder: 2)
- **AND** each approval is visible via `GET /api/contracts/<id>/approvals`

#### Scenario: Approver is resolved based on contract owner's department or role
- **WHEN** a contract owned by the Procurement department is submitted for Legal approval
- **THEN** the system assigns the approval to a user with the Legal role (e.g., the organisation's legal counsel) — assignment mechanism: LDAP/AD group, or explicit admin assignment per role

#### Scenario: Transition in_review → awaiting_signature is blocked until all approvals granted
- **GIVEN** a contract with two pending approvals (Legal, Procurement)
- **WHEN** a user tries to transition the contract to `awaiting_signature`
- **THEN** the response is 400 with a message indicating "Pending approvals: Legal, Procurement"
- **AND** status remains in_review

### Requirement: Approval decisions are recorded with decision, timestamp, and optional comment

The system SHALL define a `contractApproval` schema with properties: `contractId` (FK contract), `versionId` (FK contractVersion), `approverRole` (string, e.g., legal, finance, procurement), `approverUser` (FK user, the actual user making the decision), `decision` (enum: pending, approved, rejected, delegated), `decidedAt` (ISO-8601 timestamp, null if pending), `comment` (optional rich text for rejection reasons or delegation notes), `sequenceOrder` (integer, defines approval order). Approval records MUST be immutable once the contract is signed.

#### Scenario: Approver approves a contract
- **WHEN** the assigned Legal approver PUTs `/api/contracts/<id>/approvals/<approvalId>` with `decision: "approved"`, `decidedAt: <now>`
- **THEN** the response is 200, the approval is recorded
- **AND** the next approver in the chain (if any) can now be notified that their approval is pending

#### Scenario: Approver rejects with a reason
- **WHEN** the Finance approver PUTs with `decision: "rejected"`, `comment: "Value not justified for this term"`, `decidedAt: <now>`
- **THEN** the response is 200, the approval is recorded
- **AND** the contract transitions back to `draft` (or in_review with a rejection flag)
- **AND** the contract owner is notified via docudesk notification + email

#### Scenario: Approver delegates approval to another user
- **WHEN** the assigned Legal approver PUTs with `decision: "delegated"`, `delegatedTo: <user-uuid>`, `comment: "On leave; delegating to backup counsel"`, `decidedAt: <now>`
- **THEN** the response is 200, the approval's `approverUser` is updated to the delegated user
- **AND** the delegated user receives a notification to approve

#### Scenario: Approvals are immutable post-signature
- **GIVEN** a contract with status active (signatures collected)
- **WHEN** a user tries to PUT an approval record to change the decision
- **THEN** the response is 403 indicating the approval is locked
- **AND** the approval is unchanged

### Requirement: Rejection returns contract to draft; rejection reason is visible to owner

When an approval is rejected, the contract SHALL transition to `draft` status, a rejection flag SHALL be set (visible as part of the contract metadata or via a separate field), and the contract owner SHALL be notified via docudesk notification + email with the rejection reason (from the approver's comment).

#### Scenario: Rejected contract returns to draft
- **GIVEN** a contract in status in_review with a pending Procurement approval
- **WHEN** the Procurement approver rejects with comment "Counterparty not on approved vendor list"
- **THEN** the contract transitions to `draft`
- **AND** a notification is sent to the contract owner with the rejection reason
- **AND** the contract owner can edit the contract and resubmit

#### Scenario: Contract can be resubmitted after rejection
- **GIVEN** a contract that was rejected, now in draft with editing complete
- **WHEN** the owner transitions to in_review again
- **THEN** a new approval chain is created (resetting prior rejections)
- **AND** approvers are notified of the resubmission

### Requirement: Approval notifications are sent to approvers and logged

The system SHALL send notifications to approvers (via docudesk notification channel + email) when:
1. A contract enters `in_review` and they have a pending approval (sequenceOrder matches current sequence)
2. An approval is delegated to them
3. The previous approver in the chain completes their approval (triggering notification to the next)

#### Scenario: Legal approver is notified when contract enters in_review
- **WHEN** a contract transitions to in_review
- **THEN** a ContractApproval record with sequenceOrder 1 is created
- **AND** the assigned Legal approver receives a docudesk notification: "Contract 'SLA 2025' from [owner] awaits your approval"
- **AND** an email is sent to the approver's email address with the same message + a link to the approval UI

#### Scenario: Next approver in chain is notified after prior approver decides
- **GIVEN** Legal approver has already approved a contract, Procurement is next
- **WHEN** the Legal approver's decision is recorded as approved
- **THEN** the Procurement approver receives a notification: "Your approval is now pending for Contract 'SLA 2025'"

### Requirement: Sequential approval ordering is enforced

Approvals are processed sequentially by `sequenceOrder`. An approver at sequenceOrder N cannot make a decision until all approvers at sequenceOrder < N have approved.

#### Scenario: Lower-sequence approval must complete before higher-sequence approval can be requested
- **GIVEN** a contract with approvals: Legal (sequenceOrder 1), Procurement (sequenceOrder 2), Finance (sequenceOrder 3)
- **WHEN** Finance approver tries to approve before Legal has decided
- **THEN** the response is 400 indicating "Awaiting approvals: Legal"
- **AND** the decision is not recorded

#### Scenario: Sequential approval is advanced after each decision
- **GIVEN** Legal approves (sequenceOrder 1)
- **WHEN** the system evaluates the approval chain
- **THEN** the "current" sequenceOrder advances to 2 (Procurement)
- **AND** Procurement approver is notified
