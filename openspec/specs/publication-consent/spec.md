# publication-consent Specification

**Status**: in-progress
**Scope**: docudesk
**OpenSpec changes**:
- [woo-publicatie-pipeline](../../changes/woo-publicatie-pipeline/) _(active)_ — adds the read-only document consent-clearance signal (REQ-DDWPP-020) consumed by the publication-readiness gate; objection-window rules and consent CRUD unchanged (kind: code)

## Purpose
TBD - created by archiving change docudesk-consent-to-or-gdpr. Update Purpose after archive.
## Requirements
### Requirement: Publication Consent Is A Distinct Domain From OR GDPR Data-Subject Rights

The docudesk publication-consent stack SHALL remain app-owned and SHALL NOT be
re-pointed at OpenRegister's `DataSubjectRequestService`. docudesk tracks consent to
publish a document or entity under the Wet open overheid (WOO) active-disclosure regime;
OR's service performs GDPR data-subject requests (access, rectification, erasure,
restriction, objection to processing, portability). These are different legal domains,
and docudesk exposes none of OR's data-subject-rights verbs.

#### Scenario: Consent CRUD stays on the app-local publication-consent service

- GIVEN a consent officer creating, listing, viewing, or updating a publication-consent
  record via `/api/consents/*`
- WHEN the request is handled
- THEN it SHALL be served by `ConsentController` → `ConsentCrudService` / `ConsentService`
- AND it SHALL NOT be routed to `OCA\OpenRegister\Service\Gdpr\DataSubjectRequestService`
- AND no `OrGdprBridge` SHALL be introduced into docudesk for this workflow

#### Scenario: docudesk exposes no GDPR data-subject-rights leg

- GIVEN the docudesk backend
- WHEN its services and routes are inspected
- THEN there SHALL be no subject-data discovery, access export, erasure, rectification,
  restriction, or objection-to-processing endpoint
- AND the only objection concept present SHALL be the WOO publication objection window

### Requirement: WOO Publication Objection Period Is Not Delegated To OR's Art-12(3) Deadline

The WOO publication objection window computed by `ObjectionDeadlineChecker` SHALL NOT be
replaced by OpenRegister's `DataSubjectDeadline`. The docudesk window is a configurable
period (default 28 days, set via the `publication_objection_period_days` app setting),
whereas OR's helper is the EU GDPR art-12(3) fixed one-month base term plus a single
two-month extension. The two periods have different durations and different legal bases,
and OR's helper has no notion of a configurable WOO objection period; substituting it
would change a legal control.

#### Scenario: Objection deadline keeps the configurable WOO period

- GIVEN a new publication-consent record being created
- WHEN its `objectionDeadline` is computed
- THEN it SHALL be `now + publication_objection_period_days` (default 28) via
  `ObjectionDeadlineChecker::calculateDeadline()`
- AND it SHALL NOT be computed from `DataSubjectDeadline::computeDueAt()` (art-12(3) one month)

#### Scenario: NER anonymisation pipeline is unaffected

- GIVEN docudesk's document anonymisation pipeline (`AnonymizationService`,
  `BatchAnonymizeService`, `AnonymiserBackendStateClient`)
- WHEN the publication-consent boundary is recorded
- THEN the anonymisation pipeline SHALL remain present and unchanged

