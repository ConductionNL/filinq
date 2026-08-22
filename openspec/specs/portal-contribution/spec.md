---
capability: portal-contribution
status: in-progress
built_by: openspec/changes/portal-contribution
---

# portal-contribution Specification

**Status**: in-progress
**Scope**: filinq
**OpenSpec changes**:
- [portal-contribution](../../changes/portal-contribution/) _(active)_ — ADR-046 v2.1 provider class contributing `data-subject` and `signer` read surfaces with field projection and reference/email claim scoping (kind: code)

## Purpose

Filinq contributes a `data-subject` and a `signer` section to **portaliq**,
the shared external portal for people without a Nextcloud account (hydra
ADR-046, contribution contract v2.1). The contribution is one plain,
dependency-free provider class
(`OCA\Filinq\Portal\PortalContributionProvider`, duck-typed by FQCN — inert
without portaliq) that returns a pure-data manifest per audience: the data
subject reads their own publication-consent / objection record; the signer reads
their own signature records and — through a one-hop `via` join — the documents
awaiting their signature. All reads are scoped by a server-managed claim (a
contact-record reference for the data subject, the verified invitation email for
the signer) and projected server-side to subject-safe fields only.

## Requirements

Detailed requirements (REQ-DDPORT-001 … REQ-DDPORT-006) are defined in the active
change's delta spec —
[`openspec/changes/portal-contribution/specs/portal-contribution/spec.md`](../../changes/portal-contribution/specs/portal-contribution/spec.md)
— and are merged here by `openspec sync` when the change is archived. The
umbrella requirement below anchors the capability until then.

### Requirement: Filinq ships the ADR-046 portal contribution (REQ-DDPORT-000)

The app MUST serve its entire portal contribution through the single artefact
this capability owns: the plain, dependency-free
`OCA\Filinq\Portal\PortalContributionProvider` class (duck-typed by FQCN,
inert without portaliq). It declares the `data-subject` and `signer` read
manifests — reference/email claim scoping, server-side field projection,
`minTrust` gating and the one-hop `via` join — and no other portal
*contribution* logic, UI, dependency, create-action or endpoint-action may ship
outside it in this wave.

#### Scenario: The provider is the sole portal-contribution artefact

- GIVEN Filinq installed with portaliq present
- WHEN portaliq resolves `OCA\Filinq\Portal\PortalContributionProvider` by FQCN and calls `getAudiences()` / `getContribution()`
- THEN the `data-subject` and `signer` read manifests are served from that class alone
- AND no route, controller, service, frontend or `info.xml` dependency is added for the contribution
- @e2e exclude backend-only contribution class rendered inside portaliq, not Filinq — covered by PHPUnit (tests/unit/Portal/PortalContributionProviderTest.php)
