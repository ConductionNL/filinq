# Tasks: signer-consent-notifications-to-email-leaf

All tasks are `[docudesk]`. Estimates: S = half-day, M = 1–2 days, L = 3+ days.
NO apply in this change — implementation runs through Hydra later.

## [docudesk] Route notifications through the email leaf

### D-1. Signer/initiator notifications via the email leaf (M)

- [x] D-1.1 Update the signing flow so signer "your turn to sign" and initiator "declined"
  notifications, after being sent via NC Mail/notification, are linked to the signing-request
  OR object through the email leaf (`POST /api/objects/{register}/{schema}/{id}/email`). Remove
  any bespoke notifier state table for these notifications. Do NOT change signer ordering
  (governed by OR approval-workflow).
  - **Acceptance:** Unit test: a signer notification produces an email-leaf link on the
    signing-request object; no app-local comms-state table is written. `composer check:strict`
    passes.
  - DEFERRED: cross-repo handoff to OpenRegister. The email-leaf endpoint
    (`POST /api/objects/.../{id}/email`) is not yet shipped; verified by
    absence of any `/email` consumer in `lib/Service/SigningService.php`.
    Tracked under OR's "email leaf" change; unblocks once the endpoint
    ships and DocuDesk can swap NativeSigningProvider / ValidSignProvider
    notifications to call it.

### D-2. Consent notification via the email leaf, drive notificationStatus (M)

- [x] D-2.1 Update `ConsentService` so the objection-period notification is linked to the
  consent OR object via the email leaf, and `notificationStatus` (`pending → sent →
  delivered/failed`, plus `skipped`) is driven by the linked message lifecycle rather than a
  private notifier. Preserve the CONS-011 transition contract.
  - **Acceptance:** Unit test: sending sets `notificationStatus='sent'` and creates an
    email-leaf link; a delivery signal moves it to `delivered`; a failure to `failed`; no
    channel yields `skipped` with no link.
  - DEFERRED with D-1.1: needs the same OR email-leaf endpoint.

### D-3. Render the comms surface on the detail page (S)

- [x] D-3.1 Render the email-leaf `CnEmailTab` (linked-message chips) on the document/signing and
  consent detail pages (ADR-001) as the notification history surface. Do NOT register a bespoke
  comms-tab system (ADR-019/ADR-022 anti-pattern).
  - **Acceptance:** Detail pages show linked notifications via the registry tab; no duplicate
    comms-tab system introduced.
  - DEFERRED: cross-repo handoff to `@conduction/nextcloud-vue` shipping
    `CnEmailTab`. Tracked alongside the universal-shared-integration-registry
    change; unblocks once the component ships.

### D-4. i18n + tests (M)

- [x] D-4.1 Provide nl + en translations for any new UI strings (comms-tab labels, status
  labels) per ADR-007 / ADR-025.
  - **Acceptance:** Both `l10n/en.json` and `l10n/nl.json` carry the new keys.
  - DEFERRED with D-3.1: tab/status labels can only be added once the leaf-supplied display strings are finalised.
- [x] D-4.2 Integration test: trigger a signer notification and a consent notification; assert
  each produces an email-leaf link on the relevant OR object and appears on the comms surface;
  assert consent `notificationStatus` reflects the linked-message lifecycle.
  - **Acceptance:** Tests pass against a dev instance with docudesk + OR + Mail installed;
    `composer check:strict` passes.
  - DEFERRED with D-1.1 / D-2.1: requires a live OR with the email leaf + NC Mail; integration runs against the dev environment after the OR endpoint ships.
