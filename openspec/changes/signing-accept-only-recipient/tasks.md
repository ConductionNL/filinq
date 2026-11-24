# Tasks: signing-accept-only-recipient

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 10. -->

## 1. Register

- [x] 1.1 Add `role` and `acceptedAt` to `signerRecord`, add `ACCEPTED` to its status, make the `signingRequested` subject neutral, add a seed request with one signer and one accept-only recipient, and bump the register version (D1, D7) (REQ-SAO-001). Verify: `occ maintenance:repair` imports it, and the seed request shows an "Accept" row in the signing folder of the seeded accepter.

## 2. Service and routes

- [x] 2.1 Store `role` in `SigningService::createRequest()` from each `signers` entry and from `DocumentSigningRequestedEvent`'s signer list; refuse a request with no `sign` recipient (D1, D6) (REQ-SAO-001). Verify: PHPUnit covers a mixed request, a request without roles (all `sign`), and the refused accept-only request.
- [x] 2.2 Add `SigningService::accept()`, refuse `sign()` for an accept-only recipient and `accept()` for a signer, skip the mandate check for accepting, and write the audit entry `ACCEPTED` (D2, D4) (REQ-SAO-002). Verify: PHPUnit covers each refusal and asserts `signatureData` stays empty after an acceptance.
- [x] 2.3 Count acceptances in `updateRequestStatus()`, keep the order across roles in sequential mode, and list accepters as accepted in the artifact evidence and in `SigningConcludedEvent` (D3) (REQ-SAO-003). Verify: PHPUnit completes a request with one signature and one acceptance, and a sequential request refuses an early acceptance.
  - Built: completion counts `ACCEPTED` and sequential order holds across roles (first commit, `testAnAcceptanceIsRecordedAndCompletesTheRequest`, `testASequentialRequestRefusesAnEarlyAcceptance`). The artifact context and `SigningConcludedEvent` now list accept-only recipients under `accepted` and never among `signers` (`RecipientRole::split()`, `SigningConcludedEvent::getAccepted()`; `SigningEventsTest::testAnAccepterIsListedAsAcceptedNeverAsASigner`, `RecipientRoleSplitTest`).
- [x] 2.4 Add `POST api/signing/requests/{id}/accept` on `SigningController` with `#[NoAdminRequired]` and the session user as actor (REQ-SAO-002). Verify: Newman accepts as the invited user, and another user's accept answers 403.

## 3. Portal

- [x] 3.1 Declare the `accept` row action and action in `PortalContributionProvider` at `minTrust: substantial`, and add `acceptDocument()` to `PortalSigningReceiverController` with the same assertion and scope checks as `signDocument()`, answering 422 for the wrong act (D5) (REQ-SAO-004). Verify: PHPUnit on the provider asserts three row actions, and PHPUnit on the receiver covers a valid acceptance, a wrong role, a foreign recipient and a missing assertion.
  - Built: `PortalContributionProvider` declares `accept` as a row action and an endpoint action (`rowField` `signingRequestId`, `scopeClaim` `signerEmail`, `minTrust: substantial`); `PortalSigningReceiverController::acceptDocument()` on `POST api/portal/signing/accept` runs the same `authoriseAct()` guard as `signDocument()`, and both acts answer 422 `wrong_act` with the service's message for the wrong role (`PortalContributionProviderTest`, `PortalSignerManifestGoldenTest`, `PortalSigningReceiverControllerTest::testAcceptDocument*`, `testSignDocumentByAccepterAnswers422`).

## 4. Screens

- [x] 4.1 Add recipient rows with name, user or e-mail and role to `SigningRequestForm.vue` (D6) (REQ-SAO-001). Verify: Playwright creates a request with a signer and an accept-only recipient and reads both roles on the detail page.
  - Built: each recipient row on `SigningRequestForm.vue` has a Role select (Sign / Accept only); `signerRows.js` sends `role: 'accept'` for an accept-only recipient and refuses to submit without a recipient who signs (`tests/vitest/signerRows.spec.js`). The Playwright check is left for a live run.
- [x] 4.2 Show "Accept" instead of "Sign" on accept-only rows in `SigningFolder.vue`, and add a recipient list with role, state and time to `SigningRequestDetail.vue`, which lists no recipients today (REQ-SAO-001, REQ-SAO-002, REQ-SAO-003). Verify: Playwright as the seeded accepter accepts from the folder and the request then reads completed.
  - Built: folder entries carry `role` (`SigningActorResolver::recipientRole()`), an accept-only entry needs no signing mandate and shows an Accept button instead of the sign checkbox (`acceptDocument` store action); `showRequest` returns `recipients` from `SigningService::recipientsOf()` (id, name, role, status, order, time; no address, IP or signature data), listed on `SigningRequestDetail.vue` (`recipientList.js`). Tests: `SigningFolderServiceTest`, `SigningServiceTest::testRecipientsOf*`, `SigningControllerScopeGoldenTest`, `recipientList.spec.js`, `signingFolderStore.spec.js`. The Playwright check is left for a live run.

## 5. Strings and docs

- [ ] 5.1 Dutch and English strings (ADR-005), `@spec` tags on new methods, and a section in `docs/features/` with a screenshot of the form and the folder (ADR-010). Verify: `npm run check:l10n`, `npm run lint` and `composer check:strict` pass; PHPUnit coverage on the new PHP is at least 75% (ADR-009).
- [x] 5.2 Link the portaliq issue for the row-action normaliser and the `signerEmail` claim in this change's proposal. Verify: the issue URL is in `proposal.md` under Cross-app dependencies.
  - ConductionNL/portaliq#804 carries both gaps; its URL is in `proposal.md` under Cross-app dependencies.
