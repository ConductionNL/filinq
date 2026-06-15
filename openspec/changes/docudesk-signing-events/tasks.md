# Tasks: docudesk-signing-events

All tasks are `[docudesk]`. Mirrors decidesk `decidesk-decision-events`. Backend-only event
contract — no new user-facing string, no UI.

## [docudesk] Event contract classes

### T-1. DocumentSigningRequestedEvent (S)

- [x] T-1.1 Add `lib/Event/DocumentSigningRequestedEvent.php`
  (`OCA\DocuDesk\Event\DocumentSigningRequestedEvent` extends `OCP\EventDispatcher\Event`) with
  immutable constructor getters for `sourceApp`, `subjectRegister`, `subjectSchema`, `subjectId`,
  `subjectLabel`, `documentReference`, `signers`, `signatureLevel`, `signingMode`,
  `externalReference`, `correlationId`, plus the writable result slot
  `setSigningRequestId()/getSigningRequestId():?string` and `setHandled()/isHandled():bool`.
  SPDX + PHPDoc headers per the app convention.
  - **Acceptance:** `php -l` passes; getters return constructed values; only the result slot is
    writable.

### T-2. SigningConcludedEvent (S)

- [x] T-2.1 Add `lib/Event/SigningConcludedEvent.php` (`OCA\DocuDesk\Event\SigningConcludedEvent`
  extends `OCP\EventDispatcher\Event`) with immutable getters for provenance (`sourceApp`,
  `subjectRegister`, `subjectSchema`, `subjectId`, `externalReference`, `correlationId`) and the
  outcome envelope (`signingRequestId`, `status`, `signedDocumentRef`, `signers`, `signedAt`).
  Provide a `fromRequest(array $request, string $status, ?string $signedDocumentRef)` factory that
  builds the event from a persisted signing-request array.
  - **Acceptance:** `php -l` passes; fully immutable; factory maps request fields correctly.

## [docudesk] Listener + registration

### T-3. DocumentSigningRequestedListener (S)

- [x] T-3.1 Add `lib/EventListener/DocumentSigningRequestedListener.php`
  (`OCA\DocuDesk\EventListener\DocumentSigningRequestedListener` implements
  `OCP\EventDispatcher\IEventListener`) that, on a `DocumentSigningRequestedEvent`, builds the
  `createRequest()` data array (document reference, signers, signature level, signing mode + the
  provenance fields) and calls `SigningService::createRequest($data)` **positionally**, then
  writes `setSigningRequestId()` + `setHandled(true)` on success. On failure it logs and leaves
  the event unhandled; no exception escapes.
  - **Acceptance:** `php -l` passes; handler ignores non-matching events; failure path is
    swallowed and logged.

### T-4. Register the listener (S)

- [x] T-4.1 In `lib/AppInfo/Application.php` `register()`, add
  `registerEventListener(DocumentSigningRequestedEvent::class, DocumentSigningRequestedListener::class)`
  with the two `use` imports.
  - **Acceptance:** `php -l` passes; registration present.

## [docudesk] Provenance persistence + conclusion emission

### T-5. Persist provenance in createRequest (S)

- [x] T-5.1 Extend `SigningService::createRequest()` so that when the data array carries provenance
  keys (`sourceApp`, `subjectRegister`, `subjectSchema`, `subjectId`, `subjectLabel`,
  `externalReference`, `correlationId`) they are copied onto the persisted signing-request object.
  Additive/optional — internal callers that omit them are unaffected.
  - **Acceptance:** `php -l` passes; a request created with provenance stores `sourceApp`; one
    without is unchanged.

### T-6. Inject IEventDispatcher and emit conclusion (M)

- [x] T-6.1 Inject `OCP\EventDispatcher\IEventDispatcher` into `SigningService` and add a private
  `emitConclusionIfDelegated(array $request, string $status, ?string $signedDocumentRef)` helper
  that fires (fail-soft) only when `$request['sourceApp']` is non-empty, building the
  `SigningConcludedEvent` via `SigningConcludedEvent::fromRequest()` and dispatching it via
  `dispatchTyped()`.
  - **Acceptance:** `php -l` passes; no exception from the helper rolls back the transition.
- [x] T-6.2 Call the helper at the terminal points: COMPLETED (in `updateRequestStatus()` after
  the request persists COMPLETED, with the signed document reference), DECLINED (in `decline()`),
  CANCELLED (in `cancelRequest()`), and EXPIRED (wherever a request is persisted EXPIRED through
  the service). Re-fetch the persisted request so the provenance is read from storage.
  - **Acceptance:** `php -l` passes; a delegated request emits at each terminal point; an internal
    request emits nothing.

## [docudesk] Verify

### T-7. Validate + gates (S)

- [x] T-7.1 `openspec validate docudesk-signing-events --strict` exits 0.
- [x] T-7.2 `php -l` passes on every new/changed PHP file; self-check the key hydra gates (SPDX
  headers on new lib/*.php, no forbidden debug helpers, no stubs, notification dialect unchanged).
  No new user-facing English l10n key is added (the l10n parity gate stays green).
