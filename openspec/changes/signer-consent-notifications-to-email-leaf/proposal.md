# Proposal: signer-consent-notifications-to-email-leaf

## Why

Filinq sends outbound notifications from two flows through bespoke, app-local notifier code
instead of the shared comms abstraction:

- **Signer notifications** (`digital-signing-integration` change, `document-signing` capability):
  each signer is notified when it is their turn to sign (sequential), or all signers are notified
  at once (parallel); the initiator is notified on decline. Today these are raised as ad-hoc
  Nextcloud notifications / provider emails wired directly into the signing flow.
- **Consent notifications** (`consent-management` spec, CONS-011 "Notification System"): the
  affected entity is notified of a pending WOO publication with an objection deadline;
  `notificationStatus` tracks `pending → sent → delivered/failed/skipped`. The send itself is a
  bespoke notifier.

This duplicates a comms mechanism OR already provides via the **email** integration leaf
(`openregister/openspec/changes/integration-email`, ADR-019): an `EmailProvider`
(id=`email`, group=`comms`, requiredApp=`mail`) that surfaces and links NC Mail messages on OR
objects through the registry, so every outbound/linked message is registry-tracked and visible
on the object's comms surface. Routing notifications through this leaf — rather than a
per-app notifier — is what **ADR-022** mandates (anti-pattern: "app-local 'linked
bookmarks/files/notes/...' that mirror an OR integration").

## What

Route signer and consent notifications through the email-leaf comms surface rather than
bespoke notifier code:

1. The signing flow raises signer/initiator notifications via the email-leaf comms path; the
   resulting message is linked to the signing-request OR object through the email leaf's
   link-table so it appears on the document's comms surface.
2. The consent flow raises affected-entity notifications via the same path; the linked message
   drives `notificationStatus` transitions (`sent` on send, `delivered`/`failed` on delivery
   signal) instead of a private notifier tracking its own state.
3. The email leaf is **link-only** (NC Mail owns compose/send): Filinq continues to use NC's
   Mail / notification capability to perform the actual send, but the *registry linkage and
   comms surface* are the email leaf — not an app-local notifier with its own state table.

## Capabilities

### Modified Capabilities

- `consent-management`: consent notification (CONS-011) routes through the email-leaf comms
  path; `notificationStatus` is driven by the linked message rather than a bespoke notifier.
- `digital-signing-integration`: signer/initiator notifications route through the email-leaf
  comms path and are linked to the signing-request object on its comms surface.

## Affected Projects

- [x] Project: `filinq` — all implementation work is in this repo
- Reference: `openregister/openspec/changes/integration-email/` (the email leaf consumed)
- Reference: `hydra/openspec/architecture/adr-022-apps-consume-or-abstractions.md` (policy)
- Reference: `hydra/openspec/architecture/adr-019-*` (integration registry mechanism)

## Out of Scope

- Replacing NC Mail's compose/send (the email leaf is link-only; Mail owns the send).
- The contacts-leaf re-pointing of recipients (covered by
  `migrate-consent-recipients-to-contacts-leaf`).
- eIDAS / external-provider signing emails sent by the QTSP itself (e.g. ValidSign's own
  "sign here" email) — those are the provider's responsibility and stay as-is.
- Modifying the OR email leaf or the integration registry.

## Success Criteria

- `openspec validate --strict signer-consent-notifications-to-email-leaf` exits 0.
- A signer notification and a consent notification both produce a message linked to the
  relevant OR object via the email leaf's link-table, visible on the object's comms surface.
- Consent `notificationStatus` transitions are driven by the linked message lifecycle, not a
  private notifier state table.
- No bespoke per-app notifier maintains its own duplicate of OR's comms-surface state for these
  two flows after migration.
