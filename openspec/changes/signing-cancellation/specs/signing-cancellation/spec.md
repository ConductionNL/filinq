## ADDED Requirements

### Requirement: A provider MUST NOT report a cancellation it did not perform

A signing provider's cancellation MUST either complete against the provider or fail
loudly. It MUST NOT return success without having contacted the provider.

`ValidSignProvider::cancelSigning()` currently consists of `return true;`. Connected
to a UI as it stands, a user would cancel a request, be told it succeeded, and the
request would remain live at ValidSign — signatories could still open and sign a
document the user believes withdrawn, producing a legally valid signature the user
never expected. Filinq would show no trace of the discrepancy.

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

### Requirement: Only the creator of a signing request may cancel it

The system MUST permit cancellation **only** by the user who created the signing
request. An app administrator MUST NOT be able to cancel. A user holding write
access to the underlying document MUST NOT be able to cancel on that basis.

Cancelling withdraws a legal process from every signatory. Write permission on a
file is not authority to do that — the two coincide often, which is precisely what
makes the conflation easy and wrong. An administrator administers an application;
they are not a party to an agreement between a requester and its signatories.

The rule MUST live in one place and MUST NOT be reachable by any other path, so
adding a second caller cannot accidentally introduce a second, laxer rule.

#### Scenario: An app administrator is refused

- **GIVEN** a signing request created by user `alice`
- **AND** an app administrator `root` who did not create it
- **WHEN** `root` attempts to cancel it
- **THEN** the attempt MUST be refused
- **AND** the provider MUST NOT be contacted

#### Scenario: Write access to the document does not confer cancellation

- **GIVEN** a signing request created by `alice` over a document shared with `bob` with write permission
- **WHEN** `bob` attempts to cancel it
- **THEN** the attempt MUST be refused

#### Scenario: The creator may cancel

- **GIVEN** a signing request created by `alice`
- **WHEN** `alice` cancels it
- **THEN** the cancellation MUST proceed

#### Scenario: An unauthorised actor is refused before the provider is contacted

- **GIVEN** an actor who is not the creator
- **WHEN** they attempt to cancel
- **THEN** the provider MUST NOT be contacted, so no partial cancellation can occur

#### Scenario: The authorisation check runs before the request is resolved

- **GIVEN** a cancellation that is both unauthorised and names an unknown request id
- **WHEN** it is processed
- **THEN** the authorisation refusal MUST take precedence, so an unauthorised caller cannot use error messages to learn which request ids exist

### Requirement: A blocked cancellation MUST name the creator

When cancellation is refused because the actor is not the creator, the refusal MUST
name the creator.

An absent creator — someone who has left the organisation or is on long leave —
permanently blocks cancellation of their requests. That is the accepted consequence
of the creator-only rule, not a defect. But a bare "not permitted" leaves the user
concluding the feature is broken, when what they actually need is to know who to
ask.

The system MUST NOT provide an administrative override for this case. An escape
hatch for an absent creator is a separate change with its own authorisation
argument; adding one here on operational grounds is how the administrator path
returns through the back door.

#### Scenario: The refusal says who can do it

- **GIVEN** a signing request created by `alice`
- **WHEN** `bob` attempts to cancel it
- **THEN** the refusal MUST name `alice` as the only user who can cancel it

#### Scenario: No administrative override exists

- **GIVEN** a signing request whose creator's account is disabled
- **WHEN** an administrator attempts to cancel it
- **THEN** the attempt MUST be refused
- **AND** no configuration option MUST exist that would permit it

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
