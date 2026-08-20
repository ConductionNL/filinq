# Tasks — hermiq-ai-tooling

## 1. The signing-request initiation tool (code)

- [ ] 1.1 Add `#[McpTool(name: 'startSigningRequest', scope: 'create', readOnlyHint:
  false, destructiveHint: false, idempotentHint: false)]` to
  `SigningService::createRequest()`; logic and signature unchanged.
- [ ] 1.2 Append `SigningService::class` to
  `DocudeskScannableServices::getScannableServiceClasses()`, updating the class docblock
  that currently documents "nothing under `Service/Signing/`, no `SigningService`" to
  state the narrowed boundary (initiation gated, the act never).
- [ ] 1.3 Extend `DocumentAgentServiceTest::testNoSigningServiceIsReachableByAnAgent` to
  assert exactly one signing-domain method carries `#[McpTool]` and that it is
  `createRequest()` — `sign()`, `decline()`, `bulkSign()`, `cancelRequest()`,
  `SigningVerificationService`, `SigningAuditService`, and everything under
  `Service/Signing/` stay attribute-free.

## 2. The consent-decision tool (code)

- [ ] 2.1 Add `#[McpTool(name: 'recordConsentDecision', scope: 'update', readOnlyHint:
  false, destructiveHint: false, idempotentHint: true)]` to the consent status-update
  path (`ConsentUpdateHandler::updateConsentStatus()`), reusing the in-app transition
  validation unchanged.
- [ ] 2.2 Strip the tool's result to status fields — assert by unit test that
  `contactEmail`, `contactAddress`, `entityText`, and `objectionReason` never appear in
  a tool result, and that an inaccessible id yields a not-found identical in shape to a
  nonexistent id.
- [ ] 2.3 Append the consent service class to `DocudeskScannableServices`.

## 3. Attribution and governance wiring

- [ ] 3.1 Record `mcp` attribution with the invoking principal on agent-initiated signing
  requests and consent updates (mirror the anonymisation-intake attribution contract).
- [ ] 3.2 Verify in Hermiq's tool-governance grant editor that both tools classify as
  approval-required writes (hint-driven; no DocuDesk-side gate code), and that an
  ungranted agent does not see them.

## 4. Verify

- [ ] 4.1 MCP surface probe: the surface is exactly the derived read tools plus the eight
  curated tools; no tool name ends in `.create`/`.update`/`.delete`; no tool exposes
  `publicationConsent`, `signerRecord`, `signingSession`, or `signingAuditEntry`.
- [ ] 4.2 Live chat walkthrough on the dev instance: "generate the contract from the
  template and send it for signing" completes with exactly one human approval; rejecting
  the approval leaves no `signingRequest` object and no notification.
- [ ] 4.3 Scoped `composer check:strict` clean on touched files; zero new PHPUnit
  failures vs a self-measured baseline.
- [ ] 4.4 CHANGELOG entry.

## Acceptance criteria

- `startSigningRequest` and `recordConsentDecision` are discoverable, approval-gated,
  attributable writes; the signing act and every other standing refusal remain
  agent-unreachable.
- The modified "Signing is never agent-writable" requirement matches the shipped code and
  is asserted by the extended reachability test, not by a one-off grep.
