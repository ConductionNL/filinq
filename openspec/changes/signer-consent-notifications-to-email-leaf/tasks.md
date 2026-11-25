# Tasks: signer-consent-notifications-to-email-leaf

All tasks are `[filinq]`. Estimates: S = half-day, M = 1–2 days, L = 3+ days.
NO apply in this change — implementation runs through Hydra later.

## [filinq] Route notifications through the email leaf

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
    ships and Filinq can swap NativeSigningProvider / ValidSignProvider
    notifications to call it.
  - Checked again (2026-11-22): OpenRegister `development` now has the email
    leaf, as `emails#index|create|destroy` on
    `/api/objects/{register}/{schema}/{id}/emails/{list,send,direct/{emailId}}`
    (`lib/Notifications/Controller/EmailsController.php`). `create` LINKS an
    existing Nextcloud Mail message to the object and answers 501 without the
    Mail app; it does not send. Filinq sends these notifications through
    Nextcloud's system mailer (`IMailer`, e.g. `ConsentRequestSender`), which
    leaves no Mail message to link. So this task needs a decision first:
    send through the Mail app (a mailbox per organisation, so the message can
    be linked), keep `IMailer` and link nothing, or have OpenRegister accept
    a sent-message record that is not a Mail message. Not built until then.
  - Decision (2026-11-24, delegated product decision): the third option, in the
    shape OpenRegister already planned. filinq does not open a Mail mailbox per
    organisation and does not keep sending through `IMailer` unlinked. Once
    OpenRegister's messaging leaf has an e-mail channel bound to an object
    (`openregister/send-at-on-the-messaging-leaf`) and that channel writes an
    `EmailLink` with `direction: outbound` and the minted `Message-ID`
    (`openregister/reply-threading-by-headers` task 1.3), filinq sends the
    signer and initiator notifications through that channel, bound to the
    signing-request object, instead of through `IMailer`. The link then exists
    without a Mail account, and the record's Emails tab (D-3.1) shows it.
    Reason: one outbound path that both sends and records is what the email
    leaf's own roadmap builds, and it keeps filinq from holding comms state.
    Until both OpenRegister changes are on `main`, the `IMailer` path stays
    as it is. Signer ordering is untouched.
  - Built (2026-11-25): both OpenRegister changes are on `main`. The in-process
    seam is `EmailSenderInterface::sendMailTo()` / `sendMail()` with a
    `MailContent` carrying a `MailObjectBinding`; `EmailSender::linkSent()`
    writes the `outbound` link with the minted `Message-ID`.
    `lib/Service/ObjectBoundMail.php` sends through it (register and schema by
    id or slug). `lib/Service/Signing/SigningMail.php` mails each signer whose
    turn starts (`SigningTurn::due()`: every pending signer of a parallel
    request at creation, the next position of a sequential one after each
    signature) and the initiator on a decline with the reason, bound to the
    signing request. Only native requests: an external provider mails its own
    signers. No comms state is kept in filinq. Tests:
    `tests/unit/Service/Signing/SigningMailTest.php`,
    `tests/unit/Service/ObjectBoundMailTest.php`.

### D-2. Consent notification via the email leaf, drive notificationStatus (M)

- [x] D-2.1 Update `ConsentService` so the objection-period notification is linked to the
  consent OR object via the email leaf, and `notificationStatus` (`pending → sent →
  delivered/failed`, plus `skipped`) is driven by the linked message lifecycle rather than a
  private notifier. Preserve the CONS-011 transition contract.
  - **Acceptance:** Unit test: sending sets `notificationStatus='sent'` and creates an
    email-leaf link; a delivery signal moves it to `delivered`; a failure to `failed`; no
    channel yields `skipped` with no link.
  - DEFERRED with D-1.1: needs the same OR email-leaf endpoint.
  - Decided with D-1.1: the objection-period notification goes out through the
    same object-bound e-mail channel, bound to the consent object, and
    `notificationStatus` follows that channel's send result (`sent`, or
    `failed` with the cause; `skipped` when the consent names no channel).
    `delivered` waits for a delivery signal the channel does not give yet and
    stays unset until it does.
  - Built (2026-11-25): `ConsentRequestSender` sends the email through
    `ObjectBoundMail` bound to the consent object; `dispatched` stores `sent`,
    OpenRegister's `failed` / `no-address` / `kill-switch` store `failed` with
    the cause in words, and a record with neither an email nor a postal
    address is `skipped` (the CONS-011 move `pending -> skipped`). Without
    OpenRegister's sender the mail goes through Nextcloud's mailer, unlinked.
    Tests: `tests/unit/Service/ConsentRequestSenderTest.php`.

### D-3. Render the comms surface on the detail page (S)

- [ ] D-3.1 Render the email-leaf `CnEmailTab` (linked-message chips) on the document/signing and
  consent detail pages (ADR-001) as the notification history surface. Do NOT register a bespoke
  comms-tab system (ADR-019/ADR-022 anti-pattern).
  - **Acceptance:** Detail pages show linked notifications via the registry tab; no duplicate
    comms-tab system introduced.
  - DEFERRED: cross-repo handoff to `@conduction/nextcloud-vue` shipping
    `CnEmailTab`. Tracked alongside the universal-shared-integration-registry
    change; unblocks once the component ships.
  - Checked again (2026-11-22): `@conduction/nextcloud-vue` 2.180.0 (the locked
    version) ships `CnEmailTab` as the built-in `email` integration
    (`integrations/builtin/email.js`, `requiredApp: mail`, group `comms`).
    Both detail pages already publish their record into the registry-driven
    object sidebar (`recordLeafSidebarMixin` on `SigningRequestDetail.vue` and
    `ConsentDetail.vue`, hosted by `RecordLeafSidebar.vue` with
    `useRegistry`), so the Emails tab shows there wherever Mail is installed,
    with no comms tab of filinq's own. What is left is the live check, which
    needs an instance with Mail, and linked messages to show (D-1.1, D-2.1).

### D-4. i18n + tests (M)

- [x] D-4.1 Provide nl + en translations for any new UI strings (comms-tab labels, status
  labels) per ADR-007 / ADR-025.
  - **Acceptance:** Both `l10n/en.json` and `l10n/nl.json` carry the new keys.
  - DEFERRED with D-3.1: tab/status labels can only be added once the leaf-supplied display strings are finalised.
  - Done (2026-11-25): the Emails tab and its chips are nextcloud-vue's
    `CnEmailTab` and carry its own strings, so filinq adds none for them. The
    strings filinq does add, the signer, initiator and consent mails and the
    consent outcomes, are in `l10n/en.json` and `l10n/nl.json`.
- [ ] D-4.2 Integration test: trigger a signer notification and a consent notification; assert
  each produces an email-leaf link on the relevant OR object and appears on the comms surface;
  assert consent `notificationStatus` reflects the linked-message lifecycle.
  - **Acceptance:** Tests pass against a dev instance with filinq + OR + Mail installed;
    `composer check:strict` passes.
  - DEFERRED with D-1.1 / D-2.1: requires a live OR with the email leaf + NC Mail; integration runs against the dev environment after the OR endpoint ships.
