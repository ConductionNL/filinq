## ADDED Requirements

### Requirement: Contract reminders are trigger-based and fired by nightly cron

The system SHALL define a `contractReminder` schema with properties: `contractId` (FK contract), `triggerType` (enum: expiry, notice, review, custom), `triggerOffsetDays` (integer, days before endDate when reminder fires), `recipients[]` (array of email addresses or FK users), `lastFiredAt` (ISO-8601 timestamp or null), `nextFireAt` (ISO-8601 timestamp, calculated from contract endDate − triggerOffsetDays). A nightly background job (`app:background:cron:contract:reminders`) SHALL evaluate all contracts with active reminders and fire notifications when the trigger condition is met (today ≥ nextFireAt − 1 day tolerance).

#### Scenario: Reminder is configured for contract
- **WHEN** a contract is created with `endDate: "2025-12-31"` and a reminder is configured with `triggerType: "expiry"`, `triggerOffsetDays: 60`
- **THEN** the system calculates `nextFireAt = "2025-11-01"` (60 days before endDate)
- **AND** the reminder is visible via `GET /api/contracts/<id>/reminders`

#### Scenario: Nightly job fires reminder at the right time
- **GIVEN** a contract with reminder configured for 60 days before expiry (nextFireAt = 2025-11-01)
- **WHEN** the nightly cron runs on 2025-11-01 or later
- **THEN** the system sends a notification to all recipients
- **AND** the reminder's `lastFiredAt` is updated to the current timestamp
- **AND** `nextFireAt` is recalculated (next year, if autoRenew = true; or marked as fired if not)

#### Scenario: Missing nightly cron run doesn't skip reminders
- **WHEN** the nightly cron is delayed and runs after the nextFireAt date
- **THEN** the system detects that a reminder should have fired and fires it retroactively
- **AND** the `lastFiredAt` is recorded as the actual fire time

### Requirement: Reminders are configurable per contract

A contract can have zero, one, or many reminders. The default reminder set (based on tenant configuration) is applied when a contract is created, but users can add custom reminders via the API.

#### Scenario: Default reminders are applied on contract creation
- **WHEN** a contract is created and the tenant has default reminder rules (e.g., expiry 60 days, notice 90 days)
- **THEN** two ContractReminder records are created automatically
- **AND** they are visible in `/api/contracts/<id>/reminders`

#### Scenario: User adds a custom reminder
- **WHEN** a user POSTs `/api/contracts/<id>/reminders` with `triggerType: "custom"`, `triggerOffsetDays: 30`, `recipients: ["alice@domain.nl", "bob@domain.nl"]`
- **THEN** a new ContractReminder is created
- **AND** `nextFireAt` is calculated

#### Scenario: User disables a reminder
- **WHEN** a user DELETEs `/api/contracts/<id>/reminders/<reminderId>`
- **THEN** the reminder is deleted
- **AND** no further notifications are sent for this trigger

### Requirement: Reminders trigger notifications via docudesk channels + email

When a reminder fires, the system SHALL send:
1. A docudesk notification (visible in the notification center) to all recipients
2. An email to all email addresses in the recipients[] array

The message SHALL include the contract number, title, endDate, and (if applicable) the notice period.

#### Scenario: Notification is sent to contract owner
- **WHEN** a reminder fires for a contract with owner "alice"
- **THEN** alice receives a docudesk notification: "Contract 'SLA 2025' expires in 60 days (2025-12-31)"
- **AND** alice receives an email with the same message + a link to the contract

#### Scenario: Notification is sent to custom recipients
- **WHEN** a reminder is configured with `recipients: ["alice@domain.nl", "compliance@domain.nl"]`
- **AND** the reminder fires
- **THEN** both alice and compliance@domain.nl receive an email notification

#### Scenario: Overdue reminder is sent if system detects a missed fire date
- **WHEN** a nightly cron run detects that a reminder should have fired days ago
- **THEN** a notification is sent with a note indicating the contract is already past the threshold (e.g., "Contract EXPIRED 5 days ago — action required")

### Requirement: System transitions contract to expiring_soon when notice period is reached

When a contract has status `active` and a notice-period reminder fires (triggerType: "notice" and the notice period threshold is crossed), the system SHALL automatically transition the contract to status `expiring_soon`.

#### Scenario: Contract status is updated to expiring_soon
- **GIVEN** a contract with status `active`, `endDate: "2025-12-31"`, `noticePeriodDays: 90`
- **WHEN** today reaches 2025-10-02 (90 days before endDate)
- **THEN** a nightly job evaluates the contract and transitions it to `expiring_soon`
- **AND** a reminder notification is sent (if configured)

#### Scenario: Expiring_soon contracts are filterable
- **WHEN** a user GETs `/api/contracts?status=expiring_soon`
- **THEN** all contracts in that status are returned (sorted by endDate ascending)

### Requirement: Renewal trigger reminders can auto-initiate renewal workflow

A reminder with `triggerType: "renewal"` and `triggerOffsetDays` set to a value before endDate (e.g., 120 days before expiry) can be configured to auto-trigger a renewal (if autoRenew = true). Alternatively, the notification alerts the owner to manually initiate renewal.

#### Scenario: Auto-renewal reminder for autoRenew contracts
- **GIVEN** a contract with `autoRenew: true`, `renewalTermMonths: 12`, and a renewal reminder 120 days before expiry
- **WHEN** the renewal reminder fires
- **THEN** the system initiates the renewal workflow (creates a new draft contract cloning the parent)
- **AND** the owner is notified: "Contract renewed; new draft SLA 2026 created for your review"

#### Scenario: Manual renewal reminder for contracts requiring approval
- **GIVEN** a contract with `autoRenew: false` and a renewal reminder 90 days before expiry
- **WHEN** the reminder fires
- **THEN** the owner is notified: "Contract expires in 90 days — initiate renewal or termination decision"
- **AND** no automatic renewal is triggered

### Requirement: Reminder configuration includes recipient escalation (optional)

A reminder can define escalation recipients: if the primary recipient doesn't acknowledge the reminder within N days, the reminder is re-sent to escalation recipients.

#### Scenario: Escalation recipient is notified if primary doesn't acknowledge
- **GIVEN** a reminder configured with `recipients: ["alice@domain.nl"]` and `escalation_recipients: ["manager@domain.nl"]`, `escalation_days: 5`
- **WHEN** the reminder fires, alice is notified
- **AND** 5 days later, if alice has not acknowledged or acted on the contract
- **THEN** the reminder is re-sent to manager@domain.nl

#### Scenario: Acknowledging a reminder clears escalation
- **WHEN** alice receives the initial reminder and updates the contract (e.g., transitions to renewal)
- **THEN** the escalation timer is cleared
- **AND** the escalation recipient is not notified
