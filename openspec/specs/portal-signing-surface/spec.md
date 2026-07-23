---
capability: portal-signing-surface
status: in-progress
built_by: openspec/changes/portal-signing-surface
---

# portal-signing-surface Specification

**Status**: in-progress
**Scope**: docudesk
**OpenSpec changes**:
- [portal-signing-surface](../../changes/portal-signing-surface/) _(active)_ — contract-v2.2 `rowActions` on the signer manifest + portal-subject evidence binding (kind: code)

## Purpose

DocuDesk gives an external, accountless **signer** a real signing surface
through **portaliq** (hydra ADR-046, contribution contract v2.2): `sign` and
`decline` rendered as per-document `rowActions` on the documents-awaiting-me
collection, at eIDAS-aligned substantial trust. Closing the forgeable-signer
class of bug filed as portaliq#3, a portal-originated signature's evidence is
meant to cryptographically bind the PORTAL signer's identity so it can never be
re-attributed. This change consumes the receiver, verifier, invited-signer
guard and verified-actor entrypoint from `portal-signing-actions` and the
`v: 2` identity-bound MAC from `signing-trust-rebuild`; it does not
re-implement them.

## Requirements

Detailed requirements (REQ-DDPSS-001 … REQ-DDPSS-005) are defined in the active
change's delta spec —
[`openspec/changes/portal-signing-surface/specs/portal-signing-surface/spec.md`](../../changes/portal-signing-surface/specs/portal-signing-surface/spec.md)
— and are merged here by `openspec sync` when the change is archived. The
umbrella requirement below anchors the capability until then and records the
**apply-time dependency state honestly**: at HEAD, `portal-signing-actions`
(the receiver, `PortalAssertionVerifier`, invited-signer guard, verified-actor
`SigningService` entrypoint) and `signing-trust-rebuild` (the `v: 2`
identity-bound assertion MAC) are BOTH spec-only — zero of either change's
tasks are implemented, no receiver/verifier code exists in this repo. Only
REQ-DDPSS-001 (the declarative `rowActions` on the manifest) is implemented by
this capability so far; REQ-DDPSS-002/003/004/005 remain unimplemented pending
those two sibling changes and are NOT claimed as done.

### Requirement: Signer manifest declares sign and decline as substantial-gated rowActions (REQ-DDPSS-000)

`OCA\DocuDesk\Portal\PortalContributionProvider`'s `signer` manifest MUST
reference exactly two contract-v2.2 `rowActions` — `sign` and `decline` — on
the `signerSigningRequests` collection (the documents-awaiting-me rows), each
gated at `minTrust: substantial`, each resolving to an instance-local relative
receiver endpoint. The `signerRecords` collection and the entire `data-subject`
manifest MUST carry no write action. The provider MUST stay a plain,
dependency-free class (no portaliq import, no `implements`, no constructor).
The declared endpoints target the `portal-signing-actions` receiver, which is
NOT YET implemented at HEAD — calling the declared endpoints 404s until that
change lands; declaring them now is forward-compatible, inert, pure-data and
introduces no I/O.

#### Scenario: The signer collection carries the sign and decline rowActions

- GIVEN a constructed `PortalContributionProvider` and a subject with `audience: 'signer'`
- WHEN `getContribution($subject)` is called
- THEN the `signerSigningRequests` collection references exactly `sign` and `decline` as `rowActions`, each `minTrust: substantial`, each pointing at an instance-local relative receiver endpoint
- AND the `signerRecords` collection and the `data-subject` manifest carry no write action
- @e2e exclude backend-only manifest rendered inside portaliq, not DocuDesk — covered by PHPUnit (tests/unit/Portal/PortalContributionProviderTest.php)
