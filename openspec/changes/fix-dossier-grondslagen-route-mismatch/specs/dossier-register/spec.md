# dossier-register Specification (delta)

---
status: proposed
---

## Purpose

Correct the route-to-controller-method binding for the per-dossier grondslagen
summary regeneration endpoint so it is actually reachable over HTTP, per
ADR-029 (route-reachability gate).

## MODIFIED Requirements

### Requirement: Grondslagen Summary Endpoint Is Reachable

The `POST api/anonymization/dossier/{dossierId}/grondslagen-pdf` route SHALL
resolve to a controller method that exists on `DossierController`. The route
entry in `appinfo/routes.php` MUST name the controller's actual public method
(`generateGrondslagenSummary`), not a stale or renamed method name.

#### Scenario: The route resolves without a ReflectionException

- GIVEN Filinq is installed with the `dossier` register configured
- AND a valid dossier UUID exists
- WHEN a client sends `POST /apps/filinq/api/anonymization/dossier/{dossierId}/grondslagen-pdf`
- THEN the request SHALL reach `DossierController::generateGrondslagenSummary()`
- AND the response SHALL be the generated file's metadata (or a handled error
  payload), never a router-level `ReflectionException` / HTTP 500

#### Scenario: Frontend regeneration action succeeds end-to-end

- GIVEN a user viewing `FolderAnonymizationView.vue` with "Append a
  grondslagen-summary page" enabled
- WHEN the user triggers the regeneration action
- THEN the underlying HTTP call SHALL succeed (not 500) and the dossier's
  `configuration.grondslagen.{fileId, lastGeneratedAt}` SHALL be updated
