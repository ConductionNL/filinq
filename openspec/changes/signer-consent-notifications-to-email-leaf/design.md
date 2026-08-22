# Design: signer-consent-notifications-to-email-leaf

## Context

The OR email leaf (`integration-email`) registers an `EmailProvider`
(id=`email`, group=`comms`, requiredApp=`mail`, storage=`link-table`). It is a **link-only**
integration: NC Mail owns compose/send; the leaf surfaces + links messages on an OR object and
tracks them in OR's integration link-table, rendering them on the object's comms surface
(`CnEmailTab` chips, subject/sender/date cached at link time). Filinq consumes this leaf as
the comms surface for its two notification flows instead of a bespoke notifier with its own
state table.

## Why link-only is sufficient here

The notifications themselves are short, templated, transactional messages. NC's Mail /
notification subsystem performs the actual delivery (the link-only nature of the leaf means
"Mail owns send"). What Filinq currently re-invents is the *tracking + surfacing*: a private
notifier with its own `notificationStatus` table, not visible on the object's comms surface and
not query-able cross-app. That tracking is exactly what the email leaf's link-table provides.
So the migration is: keep NC Mail/notification as the transport, drop the bespoke tracking, and
link the resulting message to the OR object through the email leaf.

## File-by-File Mapping

### Signing flow — signer/initiator notifications

| Existing | New |
|---|---|
| Ad-hoc NC notification / provider email raised inline in the signing flow when a step becomes the active signer | Notification still sent via NC Mail/notification; the resulting message is linked to the signing-request OR object via the email leaf (`POST /api/objects/{register}/{schema}/{id}/email`) so it appears on the document's comms surface |
| Initiator-on-decline notification raised inline | Same — linked to the signing-request object via the email leaf |
| (no comms surface) | `CnEmailTab` on the document/signing detail page lists the notifications |

The sequential-vs-parallel signer ordering is unchanged (it is driven by OR's
approval-workflow per `migrate-signing-to-or-approval-workflow`); only the
notification *surface* moves to the email leaf.

### Consent flow — affected-entity notification (CONS-011)

| Existing | New |
|---|---|
| Bespoke notifier sends the objection-period notification; private `notificationStatus` state | Notification sent via NC Mail; message linked to the consent OR object via the email leaf; `notificationStatus` derived from the linked message lifecycle |
| `notificationStatus`: `pending → sent → delivered/failed/skipped` tracked internally | `sent` set when the email-leaf link is created; `delivered`/`failed` updated from the delivery signal; `skipped` retained for the no-channel case |

`ConsentService` stops owning a private notifier state machine for delivery tracking and reads
the linked-message status from the email leaf instead. The CONS-011 transition contract is
preserved — its *source of truth* moves to the leaf.

## Concept Mapping Reference

| Notification concept | Email leaf equivalent |
|---|---|
| Signer "your turn to sign" notice | NC Mail message linked to signing-request object via email leaf |
| Initiator "declined" notice | NC Mail message linked to signing-request object via email leaf |
| Consent objection-period notice | NC Mail message linked to consent object via email leaf |
| `notificationStatus` tracking | linked-message lifecycle on the email-leaf comms surface |
| Comms history per document | `CnEmailTab` chips on the detail page |

## Kept-in-app (documented ADR-022 exception)

PDF/letter generation, eIDAS signing crypto, and anonymisation stay in Filinq — **no leaf
exists for these and Filinq IS the partner service** that provides them. This change does not
touch them; it only moves notification *tracking/surfacing*. External QTSP-sent signing emails
(e.g. ValidSign's own "sign here" mail) remain the provider's responsibility and are explicitly
out of scope.

## DEFERRED_QUESTIONS

1. **Delivery signal**: confirm whether NC Mail / the email leaf exposes a delivery callback to
   drive the `delivered`/`failed` transition, or whether `delivered` is best-effort
   (set on successful send) until Mail surfaces a bounce signal. Resolved before `opsx-apply`.
2. **Link payload**: confirm the email-leaf `POST /api/objects/{register}/{schema}/{id}/email`
   payload (`mailAccountId`, `mailMessageId`) once `integration-email` is `implemented`
   (currently `proposed`).

## Seed Data

No new OR schema is introduced. The consent schema's `notificationStatus` enum is unchanged; its
source of truth moves to the email-leaf-linked message.

## Related ADRs

- **ADR-022** (primary) — consume the OR email/comms abstraction over a bespoke notifier.
- **ADR-019** — integration registry; the email leaf is the comms surface mechanism.
- **ADR-001** (filinq) — the document/consent detail page is where the comms tab lands.
- **Email leaf** — `openregister/openspec/changes/integration-email/specs/integration-email/spec.md`.
- **Signing migration** — `filinq/openspec/changes/migrate-signing-to-or-approval-workflow`
  (ordering is OR approval-workflow; this change moves only the notification surface).
