# entity-publication-policies Specification (delta)

---
status: proposed
---

## Purpose

Clarify that scope=entity (standing) `publicationConsent` records have a single
canonical create path, and remove a superseded duplicate create method that was
never wired to any caller (issue #176, gate-52 orphaned-write-capability).

## MODIFIED Requirements

### Requirement: RBAC MUST govern writes to both policy surfaces

The system MUST govern writes to `publicationProhibition` records and to
`scope: "entity"` `publicationConsent` records by OpenRegister's standard
schema-level authorization, augmented by service-level enforcement for the
scope-discriminated case. There MUST be no formal approval workflow at this
version — privileged users MAY write directly.

Standing-consent creation MUST flow through exactly one service entry point,
`PolicyCrudService::createStandingConsent()` (reached over HTTP via
`PolicyController::createStandingConsent`). There MUST NOT be a second,
divergent create path for the same records: any superseded duplicate (e.g. a
never-called `ConsentService::createEntityConsent()`) MUST be removed so the
scope-write RBAC contract is enforced in exactly one place.

#### Scenario: Standing-consent write requires standing-consent permission

- **GIVEN** a user with write permission on `publicationConsent` for
  `scope: "document"` only (i.e., the consent-officer role) and NOT for
  `scope: "entity"`
- **WHEN** they attempt to POST a `publicationConsent` record with
  `scope: "entity"`
- **THEN** the create path (`PolicyCrudService::createStandingConsent`) rejects
  the write with a 403-equivalent error citing missing standing-consent
  permission
- **AND** the same user CAN still write `scope: "document"` records normally

#### Scenario: No orphaned duplicate create path exists

- **GIVEN** the docudesk service layer
- **WHEN** the codebase is scanned for scope=entity consent create methods
- **THEN** exactly one wired create path exists
  (`PolicyCrudService::createStandingConsent`)
- **AND** no unreferenced duplicate create method (such as
  `ConsentService::createEntityConsent`) remains in `lib/Service/`
