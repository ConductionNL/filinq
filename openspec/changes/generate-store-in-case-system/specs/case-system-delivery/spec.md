# case-system-delivery Specification (delta)

## Purpose

A document filinq generates can go to the organisation's case system
through the bridge, not only to Nextcloud Files. filinq records the
delivery; integriq makes the call. Matrix row `td-dms-link` (humaniq
matrix, owned by filinq), demand
https://www.tenderned.nl/aankondigingen/overzicht/415705.

## ADDED Requirements

### Requirement: An admin declares where generated documents go in the case system (REQ-GSC-001)

Filinq MUST let an admin declare a `caseSystemDestination` per template
namespace or per template, naming a `bridgeSource`, an
`informatieobjecttype`, a `vertrouwelijkheidaanduiding` and a
`bronorganisatie`, and switch it on or off. A destination for a template
MUST win over one for its namespace. At most one active destination MAY
exist per template and per namespace; a second MUST be refused with "An
active destination for this template already exists."

Rows: `td-dms-link` (humaniq matrix)

#### Scenario: An admin sends HR documents to the DMS

- GIVEN an admin on the filinq admin settings with an active bridge source "Zaaksysteem Delft"
- WHEN the admin adds a destination for namespace `hrmq` with document type "Arbeidsovereenkomst" and confidentiality "vertrouwelijk"
- THEN the bridge panel lists the destination as active for `hrmq`
- @e2e tests/e2e/case-system-delivery.spec.ts

### Requirement: A stored document gets a delivery for its destination (REQ-GSC-002)

When `generateDocument()` stores a document (mode `files` or `both`) from
a template with an active destination, or with `options.output.caseSystem`
on the request, filinq MUST write one `caseSystemDelivery` in status
`ready_for_writeback` referencing the stored file and the
`generatedDocument`, fill `titel`, `bestandsnaam`, `formaat`, `taal`,
`creatiedatum` and `auteur`, and set `caseSystemDeliveryId` on the
`generatedDocument`. A request with `options.output.caseSystem` and mode
`return` MUST answer 400 "A document sent to the case system must be
stored first. Use output mode files or both." Recording the delivery MUST
NOT fail the generation: a failure becomes a warning on the result. Filinq
MUST NOT call a ZGW or StUF endpoint itself.

Rows: `td-dms-link` (humaniq matrix)

#### Scenario: An HR contract is queued for the DMS

- GIVEN an active destination for namespace `hrmq`
- WHEN humaniq generates an employment contract through filinq with output mode `both`
- THEN the contract is in Files, and a delivery in `ready_for_writeback` points at that file with the document type and confidentiality of the destination
- @e2e tests/e2e/case-system-delivery.spec.ts

#### Scenario: Nothing is queued without a stored file

- GIVEN a generate request with `options.output.caseSystem` and output mode `return`
- WHEN the request is sent
- THEN the answer is 400 with "A document sent to the case system must be stored first. Use output mode files or both." and no delivery exists
- @e2e exclude API contract; covered by a Newman request and PHPUnit on `DocumentService`

### Requirement: A delivery's outcome is visible and a failure can be retried (REQ-GSC-003)

`caseSystemDelivery.deliveryStatus` MUST follow the bridge's outbound
states as an `x-openregister-lifecycle`: `ready_for_writeback` to
`written_back` or `writeback_failed`, and `writeback_failed` back to
`ready_for_writeback` on retry. The admin bridge panel MUST list waiting
and failed deliveries with the document name and the `writeBackError`.
The stored file's row on `/my-documents` MUST show "In the case system"
once its delivery is `written_back`, and `/reports/documents` MUST count
waiting and failed deliveries.
`POST api/bridge/deliveries/{id}/retry` MUST move a failed delivery back
to `ready_for_writeback` on the same object and MUST NOT create a second
delivery. A move to `writeback_failed` MUST notify the user in the
document's `generatedBy`.

Rows: `td-dms-link` (humaniq matrix)

#### Scenario: An admin retries a failed delivery

- GIVEN a delivery in `writeback_failed` with the error "informatieobjecttype unknown"
- WHEN the admin fixes the destination and chooses "Retry" on the bridge panel
- THEN the same delivery shows "Waiting" again and no second delivery for that document exists
- @e2e tests/e2e/case-system-delivery.spec.ts

#### Scenario: A delivered document shows where it went

- GIVEN a delivery that integriq set to `written_back` with a `resultExternalId`
- WHEN the clerk who generated it opens `/my-documents`
- THEN the row of the stored file shows the badge "In the case system", and `/reports/documents` counts no failed delivery
- @e2e tests/e2e/case-system-delivery.spec.ts
