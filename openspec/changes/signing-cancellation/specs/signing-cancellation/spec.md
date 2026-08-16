## ADDED Requirements

### Requirement: A provider MUST NOT report a cancellation it did not perform

A signing provider's cancellation MUST either complete against the provider or fail
loudly. It MUST NOT return success without having contacted the provider.

`ValidSignProvider::cancelSigning()` currently consists of `return true;`. Connected
to a UI as it stands, a user would cancel a request, be told it succeeded, and the
request would remain live at ValidSign — signatories could still open and sign a
document the user believes withdrawn, producing a legally valid signature the user
never expected. DocuDesk would show no trace of the discrepancy.

Cancellation MUST therefore be typed as void-or-throw rather than returning a
boolean. A boolean return invites one call site to write `if ($ok)` and another to
ignore it, and is what made an unconditional `return true` look like an
implementation.

A provider that genuinely cannot cancel MUST throw a named "not supported by this
provider" error, so the caller can tell the user the truth.

#### Scenario: A provider that cannot reach its backend fails loudly

- **GIVEN** a provider whose backend is unreachable
- **WHEN** a cancellation is attempted
- **THEN** the operation MUST throw
- **AND** MUST NOT report the request as cancelled
- **AND** the request's state MUST remain whatever it was before the attempt

#### Scenario: A stub implementation cannot satisfy the contract

- **GIVEN** a provider implementation that performs no call to its backend
- **WHEN** the contract test for that provider runs
- **THEN** it MUST fail, because a provider that always succeeds is indistinguishable from one that never does anything

#### Scenario: A provider that does not support cancellation says so

- **GIVEN** a provider with no cancellation capability
- **WHEN** a cancellation is attempted
- **THEN** it MUST throw an error naming the provider and the unsupported operation
- **AND** MUST NOT silently succeed

### Requirement: Cancellation MUST be authorised, and the rule MUST be explicit

Every cancellation MUST pass an authorisation check before the provider is called.
The rule MUST be stated in one place and MUST NOT be inferred from file permissions
by accident.

Cancelling a signing request withdraws a legal process from every signatory. The
authority to do that is a domain decision, not a consequence of holding write
permission on a file.

Until the rule is settled by a human (see the proposal), the implementation MUST
refuse rather than default to a permissive answer. A default that lets too many
people cancel is an authorisation hole; refusing is merely an inconvenience.

#### Scenario: An unauthorised actor is refused before the provider is contacted

- **GIVEN** an actor who does not satisfy the authorisation rule
- **WHEN** they attempt to cancel a signing request
- **THEN** the attempt MUST be refused
- **AND** the provider MUST NOT be contacted, so no partial cancellation can occur

#### Scenario: The authorisation check runs first

- **GIVEN** a cancellation request that is both unauthorised and names an unknown request id
- **WHEN** it is processed
- **THEN** the authorisation refusal MUST take precedence, so an unauthorised caller cannot use error messages to learn which request ids exist

### Requirement: A cancelled request MUST be visibly cancelled and MUST NOT be re-signable

After a successful cancellation the request's state MUST be `cancelled`, and any
signing surface derived from it MUST stop accepting signatures.

A state change that leaves the signing link working is not a cancellation.

#### Scenario: A cancelled request refuses a subsequent signature

- **GIVEN** a signing request that has been cancelled
- **WHEN** a signatory opens their signing link and submits a signature
- **THEN** the submission MUST be refused
- **AND** the refusal MUST say the request was withdrawn

#### Scenario: Cancelling an already-cancelled request is not an error

- **GIVEN** a request already in `cancelled`
- **WHEN** cancellation is attempted again
- **THEN** the operation MUST succeed without contacting the provider a second time

#### Scenario: A completed request cannot be cancelled

- **GIVEN** a signing request every signatory has already signed
- **WHEN** cancellation is attempted
- **THEN** it MUST be refused, naming the completed state
- **AND** the existing signatures MUST be untouched

### Requirement: Every cancellation MUST be recorded with its actor and outcome

A cancellation MUST be recorded with who performed it, when, which request, and
whether the provider confirmed it. A failed attempt MUST be recorded too.

A withdrawn signing process is exactly the event someone will later need to
reconstruct, and "the request is cancelled but nobody knows who did it" is not an
acceptable end state for a legal artefact.

#### Scenario: A failed cancellation is recorded as failed

- **GIVEN** a cancellation whose provider call fails
- **WHEN** the failure is handled
- **THEN** the attempt MUST be recorded with its actor, the request, and the failure
- **AND** the record MUST NOT show the request as cancelled
