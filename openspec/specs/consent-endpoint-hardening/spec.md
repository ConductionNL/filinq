---
status: done
---

# consent-endpoint-hardening Specification

## Purpose
The Filinq-owned residual of GH #283: consent ownership guards, the mutable-field whitelist and CSRF-annotation removal have shipped, and tenant/organisation binding is owned by the sibling change `multi-tenant-hardening`. This capability closes the remaining information-leak oracle: `ConsentController::errorResponse()` must never interpolate raw exception text into an error response body — the same defect already fixed on `SigningController` (filinq#100 / Wilco #6).

@e2e exclude Backend error-response hardening; no navigable UI surface. Covered by PHPUnit (tests/unit/Controller/ConsentControllerTest.php).
## Requirements
### Requirement: Consent error responses are oracle-free (REQ-DDSTR-009)
Consent API error responses MUST carry only a generic translated message — never exception text, record identifiers, or any detail that differs by failure class — while full detail goes to the logger. Response bodies for not-found, access-denied and internal-failure classes MUST be indistinguishable except for the HTTP status code, and not-found vs access-denied MUST continue to collapse to a single 404 (no 404-vs-403 split, no existence-probing oracle). Legitimate 4xx codes carried on the exception MUST still be honoured so client errors are not masked as 500s.

#### Scenario: Exception text never reaches the consent response body
- **GIVEN** a consent endpoint whose service layer throws an exception containing a record UUID in its message
- **WHEN** the endpoint returns its error response
- **THEN** the body contains only the generic translated message
- **AND** the UUID and exception text appear only in the server log
- @e2e exclude response-body content assertion — covered by PHPUnit (tests/unit/Controller/ConsentControllerTest.php), no browser-visible surface

#### Scenario: Non-owner probe and true not-found are indistinguishable
- **GIVEN** a consent record owned by user A and a random non-existent consent id
- **WHEN** user B requests both via GET `/api/consents/{id}`
- **THEN** both responses are 404 with byte-identical bodies
- @e2e exclude negative-authz API probe — covered by PHPUnit (tests/unit/Controller/ConsentControllerTest.php), no UI surface
