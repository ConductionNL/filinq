# Tasks — hermiq-ai-tooling

## 1. The signing-request initiation tool (code)

- [x] 1.1 Add `#[McpTool(name: 'startSigningRequest', scope: 'create', readOnlyHint:
  false, destructiveHint: false, idempotentHint: false)]` to
  `SigningService::createRequest()`; logic and signature unchanged.
- [x] 1.2 Append `SigningService::class` to
  `FilinqScannableServices::getScannableServiceClasses()`, updating the class docblock
  that currently documents "nothing under `Service/Signing/`, no `SigningService`" to
  state the narrowed boundary (initiation gated, the act never).
- [x] 1.3 Extend `DocumentAgentServiceTest::testNoSigningServiceIsReachableByAnAgent` to
  assert exactly one signing-domain method carries `#[McpTool]` and that it is
  `createRequest()` — `sign()`, `decline()`, `bulkSign()`, `cancelRequest()`,
  `SigningVerificationService`, `SigningAuditService`, and everything under
  `Service/Signing/` stay attribute-free.

## 2. The consent-decision tool (code)

- [x] 2.1 Add `#[McpTool(name: 'recordConsentDecision', scope: 'update', readOnlyHint:
  false, destructiveHint: false, idempotentHint: true)]` to the consent status-update
  path (`ConsentUpdateHandler::updateConsentStatus()`), reusing the in-app transition
  validation unchanged.
- [x] 2.2 Strip the tool's result to status fields — assert by unit test that
  `contactEmail`, `contactAddress`, `entityText`, and `objectionReason` never appear in
  a tool result, and that an inaccessible id yields a not-found identical in shape to a
  nonexistent id.
- [x] 2.3 Append the consent service class to `FilinqScannableServices`.
  - Built as a guarded tool method, as `anonymizeViaAgent()` is for the anonymisation intake:
    `ConsentUpdateHandler::updateConsentStatus()` runs with RBAC off, and the access check
    (consent officer, the record exists) lives in `ConsentController::update()`, so putting the
    attribute on the handler would have let an agent past that check. The tool is
    `ConsentDecisionAgent::recordConsentDecision(consentId, consentStatus, evidence)`. It checks the
    principal is a consent officer (the officer groups are shared through `ConsentOfficers`), reads the
    record, and calls the same `ConsentCrudService::updateConsentStatus()` the controller calls, so the
    transition validation and the standing-consent check are unchanged. It answers status fields only.
    PHPUnit `ConsentDecisionAgentTest` (status fields only, one not-found shape, the rule's refusal, the
    hints, exactly one consent tool).

## 3. Attribution and governance wiring

- [x] 3.1 Record `mcp` attribution with the invoking principal on agent-initiated signing
  requests and consent updates (mirror the anonymisation-intake attribution contract).
  - Consent half: each status event an agent writes carries `via: mcp` beside `by` (the
    invoking principal), threaded from the tool through `ConsentCrudService`, `ConsentService` and
    `ConsentStatusChange` to `ConsentStatusEvents`, and not settable through the HTTP API
    (`publicationConsent` 1.6.0, register 8.55.0; `ConsentUpdateFlowTest::testAnAgentsStatusEventSaysItCameViaMcp`).
    The decision is also logged with `triggeredBy: mcp` and the uid, as the anonymisation intake does.
  - Signing half: `createRequest()` cannot tell an agent's call from the form's, so the tool moved
    to a separate entry point, `SigningService::startSigningRequest()`, which runs the same creation
    (one private `persistRequest()`, every check unchanged) and marks the request `initiatedVia: mcp`
    and its CREATED audit entry `extra.via: mcp` beside the actor (the invoking principal).
    `createRequest()` keeps its signature and carries no attribute; the request data cannot set the
    attribution (`signingRequest` 1.12.0, register 8.56.0;
    `SigningServiceTest::testAnAgentStartedRequestIsAttributedToMcp`,
    `::testTheRequestDataCannotClaimMcpAttribution`). 1.1 and 1.3 now pin `startSigningRequest()`
    as the one signing tool, and the spec delta names it.
- [ ] 3.2 Verify in Hermiq's tool-governance grant editor that both tools classify as
  approval-required writes (hint-driven; no Filinq-side gate code), and that an
  ungranted agent does not see them.

## 4. Verify

- [ ] 4.1 MCP surface probe: the surface is exactly the derived read tools plus the eight
  curated tools; no tool name ends in `.create`/`.update`/`.delete`; no tool exposes
  `publicationConsent`, `signerRecord`, `signingSession`, or `signingAuditEntry`.
- [ ] 4.2 Live chat walkthrough on the dev instance: "generate the contract from the
  template and send it for signing" completes with exactly one human approval; rejecting
  the approval leaves no `signingRequest` object and no notification.
- [x] 4.3 Scoped `composer check:strict` clean on touched files; zero new PHPUnit
  failures vs a self-measured baseline.
  - phpstan, psalm, phpmd and phpcs (two `@spec` class warnings already on development) on the touched
    lib files; PHPUnit 6695 -> 6701, 0 failures.
- [x] 4.4 CHANGELOG entry.

## Acceptance criteria

- `startSigningRequest` and `recordConsentDecision` are discoverable, approval-gated,
  attributable writes; the signing act and every other standing refusal remain
  agent-unreachable.
- The modified "Signing is never agent-writable" requirement matches the shipped code and
  is asserted by the extended reachability test, not by a one-off grep.
