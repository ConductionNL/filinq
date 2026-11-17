## Context

`SigningProviderInterface::cancelSigning(string $externalId): bool` is declared,
implemented twice, and called nowhere. Gate-57 found it on 2026-08-16 as one of
three genuine orphaned write capabilities in filinq (the gate's other twelve
findings were routed controllers it could not see callers for — fixed separately in
`.github#475`).

The two implementations are not equivalent, and that asymmetry is the whole reason
this needs a spec rather than a wiring commit:

- `NativeSigningProvider` loads the session, sets `status = 'cancelled'`, persists.
- `ValidSignProvider` is `return true;`.

## Goals / Non-Goals

**Goals:**
- A cancellation that is either real or loudly absent.
- An authorisation rule stated once, decided by a human.
- An audit record of who withdrew what, including failed attempts.

**Non-Goals:**
- `BatchStateService::deleteBatch`, the third orphan. Unrelated, and it needs its own
  decision about what deleting a batch does to the documents in it.
- Partial cancellation (withdrawing one signatory from a multi-party request). A
  different feature with a different legal shape.
- Reinstating a cancelled request.

## Decisions

### D1 — void-or-throw, not `bool`

The current `: bool` contract is what let `return true;` pass for an implementation.
A boolean encourages one caller to write `if ($ok)` and the next to ignore it, and
neither is wrong under the type.

Void-or-throw removes the option: a provider either completes or raises something a
caller must handle. A provider that genuinely cannot cancel throws a named
"unsupported" error, which is information the user can act on — unlike `false`,
which is indistinguishable from a transient failure.

### D2 — Refuse rather than default the authorisation rule

The rule is unsettled (see the proposal's table). The implementation must refuse
until it is settled rather than pick a permissive default.

The asymmetry is the reason: a default that lets too many people cancel is an
authorisation hole discovered after someone withdraws a colleague's legal process. A
refusal is an inconvenience discovered immediately, by the person who needed the
feature, who will then ask for the decision.

### D3 — Authorise before contacting the provider

Two reasons, and the second is the less obvious one:

1. An unauthorised call must not produce a partial cancellation.
2. If the request-lookup ran first, an unauthorised caller could distinguish "no such
   request" from "not allowed" and enumerate valid request ids from the error
   messages.

### D4 — Idempotent on an already-cancelled request, refused on a completed one

Cancelling twice is a double-click, not an error, and must not call the provider a
second time.

Cancelling a *completed* request is different: the signatures exist and the process
is over. Accepting it would let the UI display "cancelled" over a document that is,
in fact, signed — a claim the system cannot make good on.

### D5 — Record failures, not just successes

The interesting audit case is the cancellation that did **not** work. "Cancelled at
14:02 by Ruben" is easy; "attempted at 14:02 by Ruben, provider refused, request
still live" is the record that prevents someone concluding a document was withdrawn
when it was not.

## Seed Data (ADR-001)

**None.** No new OpenRegister schemas. The signing request schema already carries a
status; `cancelled` is an existing value in the native provider's own usage.

## Declarative-vs-imperative decision (ADR-031)

| Behaviour | Path | Rationale |
|---|---|---|
| Provider cancellation call | **Imperative** | A side-effecting call across an instance boundary — the ADR-031 external-integration exception. |
| Request state transition | **Declarative candidate** | If the signing request schema carries `x-openregister-lifecycle`, `cancelled` belongs there as a transition with a guard rather than as a service-set field. **Check the schema register before implementing**; ADR-031's default path is declarative and a hand-set status would be the wrong one. |
| Audit record | **Imperative** | An explicit record of an attempt and its outcome. |

## Risks / Trade-offs

**The ValidSign implementation is unknown work.** Its cancellation API has not been
read. It may require the original submission token, may not support cancellation
after the first signature, or may not support it at all — in which case D1's
"unsupported" throw is the honest answer and the UI must say so per provider.

**A cancelled-but-still-live request is the failure to design against.** Every
requirement here points at it: fail loudly, record failures, refuse when
unauthorised, and never claim a state the provider has not confirmed.

**Blocking on a human decision has a cost.** The capability stays unavailable until
the authorisation rule is settled. That is the right trade: it has been unavailable
since it was written, and shipping it with a guessed rule is how it becomes
unavailable *and* wrong.
