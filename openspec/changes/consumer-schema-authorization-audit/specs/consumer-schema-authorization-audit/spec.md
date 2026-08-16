## ADDED Requirements

### Requirement: Every owned schema MUST carry an explicit authorisation decision

Every schema DocuDesk declares in `lib/Settings/docudesk_register.json` MUST either
declare an `authorization` cascade, or carry a recorded justification for being
readable by any authenticated user in the organisation.

OpenRegister treats an **unconfigured** cascade as OPEN. `_rbac: true` means "apply
the cascade"; it does not mean "a cascade exists". With `authorization: null`,
applying the cascade permits everything — so absence of an opt-out is not presence of
a guard.

Measured 2026-08-16: **20 of 21** schemas had no cascade.

A schema added without a decision MUST fail a test. The count returning to 20 by
accretion is the failure mode this requirement exists to prevent, and it happens one
un-thought-about schema at a time.

#### Scenario: A new schema without an authorisation decision fails

- **GIVEN** a schema added to the register with neither an `authorization` cascade nor a recorded justification
- **WHEN** the authorisation-coverage test runs
- **THEN** it MUST fail, naming the schema

#### Scenario: A justified open schema passes

- **GIVEN** a schema holding reference data legitimately readable across the organisation
- **AND** a recorded justification saying so
- **WHEN** the test runs
- **THEN** it MUST pass, because "open" is a decision the app is allowed to make deliberately

#### Scenario: The cascade change is deployable

- **GIVEN** an `authorization` block added to a schema
- **WHEN** the register is imported
- **THEN** the register's `info.version` MUST have been bumped, or the import is skipped and the change never reaches a running instance

### Requirement: Authentication MUST NOT be presented as authorisation

A controller method reachable by any authenticated user, which acts on an object
identified by a request parameter, MUST verify that the caller may act on **that
object**. Returning `401` when no user is present is an authentication check and
satisfies nothing here.

`DocumentController::preview()` reads `templateId` from the request, returns `401`
when unauthenticated, and proceeds with no per-object check, against a schema with no
cascade.

This is the defect class that turned **0 gate-7 findings across 18 apps into 167 real
IDORs** once the gate stopped accepting a `401` as a guard.

#### Scenario: Another user's object is refused

- **GIVEN** an object belonging to user `alice`, under a schema with no authorisation cascade
- **AND** an authenticated user `bob` in the same organisation
- **WHEN** `bob` requests the endpoint with `alice`'s object id
- **THEN** the request MUST be refused
- **AND** the refusal MUST NOT reveal whether the id exists

#### Scenario: A 401 does not satisfy the requirement

- **GIVEN** an endpoint whose only check is that a session user exists
- **WHEN** the authorisation test for that endpoint runs
- **THEN** it MUST fail, because every authenticated user in the organisation passes that check

### Requirement: A guard MUST be justified by a threat model, not by a gate

Each guard added under this change MUST record which actor it excludes and from
what. A guard written only to make a gate green MUST NOT be added.

`ConsentCrudService` already carries the evidence for why this matters: its comment
ends *"Do not delete either as 'redundant with OpenRegister RBAC' — measured, it is
not redundant."* Someone had already reasoned their way to deleting a real control
because they believed the data layer covered it. An unjustified guard is the same
control with the reasoning missing, and it will be deleted the same way.

#### Scenario: A guard records what it excludes

- **GIVEN** a controller guard added under this change
- **WHEN** the code is reviewed
- **THEN** it MUST state which actor it refuses and why the data layer does not already refuse them

### Requirement: The exposure bound MUST be stated, not implied

Documentation of this finding MUST state that multitenancy remains enforced and that
the exposure is to authenticated users **within the same organisation**.

An unqualified "IDOR" reads as anonymous or cross-tenant and drives the wrong
priority; an unqualified "it's fine, RBAC covers it" is what produced this finding in
the first place. Both are wrong in the same way — they replace a measurement with a
summary.

#### Scenario: Cross-organisation access is still refused

- **GIVEN** an object in organisation A
- **AND** an authenticated user in organisation B
- **WHEN** the object is requested by id
- **THEN** the request MUST be refused by the multitenancy layer
