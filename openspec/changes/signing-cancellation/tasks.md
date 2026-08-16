# Tasks

## 0. Settle the authorisation rule — BLOCKING

- [ ] Get a human decision on who may cancel a signing request (see the proposal's table) and record it in the spec

Acceptance criteria:
- Nothing below ships until this is answered. A guessed rule is an authorisation hole in a legal process, and the refusal in task 2 is the deliberate placeholder until then.

## 1. Make the contract honest

- [ ] Change `SigningProviderInterface::cancelSigning()` to void-or-throw
- [ ] Add a named "cancellation not supported by this provider" exception
- [ ] Implement `ValidSignProvider::cancelSigning()` against ValidSign's real API, or throw unsupported if it has none
- [ ] Update `NativeSigningProvider::cancelSigning()` to the new return shape

Acceptance criteria:
- `ValidSignProvider::cancelSigning()` no longer returns success without contacting ValidSign. This is the defect: as written it tells a user their request is withdrawn while signatories can still sign.
- A provider contract test fails against an implementation that always succeeds without a backend call.

## 2. The cancellation service

- [ ] Add `SigningCancellationService` — authorise, resolve, call provider, record, transition
- [ ] Authorise BEFORE contacting the provider and before resolving the request id
- [ ] Refuse when the authorisation rule is unsettled, rather than defaulting permissive
- [ ] Make an already-cancelled request idempotent, and a completed request a refusal
- [ ] Check whether the signing request schema carries `x-openregister-lifecycle`; if it does, the transition belongs there (ADR-031), not in a hand-set status field

Acceptance criteria:
- An unauthorised caller cannot distinguish "no such request" from "not allowed", so request ids cannot be enumerated from error text.
- A cancelled request's signing link refuses a subsequent signature. A state change that leaves the link working is not a cancellation.

## 3. Reachability and audit

- [ ] Add the controller method, the route, and the UI action
- [ ] Record every attempt with actor, request, timestamp and outcome — including failures

Acceptance criteria:
- A failed cancellation is recorded as failed and the request is NOT shown as cancelled. That record is what stops someone concluding a document was withdrawn when it was not.
- Verify the route is registered; an unrouted controller method is exactly the orphan class that produced this change.

## 4. Verify

- [ ] Unit tests for each requirement, including the provider contract test and the enumeration guard
- [ ] Playwright E2E: cancel a native signing request, then confirm the signing link refuses a signature
- [ ] Re-run gate-57 and confirm `cancelSigning` is no longer orphaned on either provider

Acceptance criteria:
- The E2E asserts the signing surface REFUSES afterwards, not merely that the status field changed. The status is what the app believes; the refusal is what the signatory experiences.
