# Tasks: connect-document-webhooks

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 8. -->

## 1. Events

- [ ] 1.1 Add `lib/Event/DocumentWebhookEvent.php` and the four subclasses, each returning its envelope (D1, D4) (REQ-CDW-001, REQ-CDW-002). Verify: PHPUnit asserts each envelope holds exactly the listed keys and no file content, entity, e-mail address or IP address.
- [ ] 1.2 Add `lib/EventListener/DocumentEventTranslator.php` and subscribe it in `ObjectEventRegistrar::boot()` for `signingRequest`, `anonymizationLink`, `generatedDocument` and `documentRegistration` (D2) (REQ-CDW-001). Verify: PHPUnit that constructs the real `ObjectTransitionedEvent` and `ObjectCreatedEvent` classes asserts one dispatch per event, none for a `generatedDocument` with status `failed`, and none for another schema.
- [ ] 1.3 Prove the path end to end once openregister's half is in (D3) (REQ-CDW-001, REQ-CDW-004). Verify: Newman creates a webhook for `DocumentSignedEvent` pointing at the test receiver, completes a signing request through OpenRegister's objects API, and the receiver shows one signed delivery with a valid signature.

## 2. Settings section

- [ ] 2.1 Add `src/views/settings/DocumentWebhooksSection.vue` to `Settings.vue`: list, add, send a test, show the last delivery, delete, over OpenRegister's `/api/webhooks` endpoints (D5) (REQ-CDW-003). Verify: Playwright as admin adds a receiver for "Document signed", sends a test and deletes it.
- [ ] 2.2 Refuse to save without a secret, and show "Delivery is not available on this server" when OpenRegister's catalogue lists no filinq event (D6) (REQ-CDW-003, REQ-CDW-004). Verify: Playwright saves with an empty secret and reads the refusal; a PHPUnit or Vitest case covers the unavailable state.

## 3. Strings, docs and the sibling half

- [ ] 3.1 Dutch and English strings (ADR-005) and `@spec` tags on the new classes and methods. Verify: `npm run check:l10n`, `npm run lint` and `composer check:strict` pass; PHPUnit coverage on the new PHP is at least 75% (ADR-009).
- [ ] 3.2 Add a section in `docs/features/` with a screenshot of the settings section and one example payload per event (ADR-010). Verify: the page renders in the docs build and each example matches the PHPUnit envelope fixture.
- [ ] 3.3 File openregister's half (app-declared webhook events in the listener and the catalogue) as an issue on ConductionNL/openregister and link it in this change's proposal. Verify: the issue URL is in `proposal.md` under Cross-app dependencies.
