# woo-request-workflow Specification (delta)

---
status: proposed
---

## Purpose

Passive-disclosure (Woo-verzoek) case workflow: intake a Woo request with
statutory deadline tracking (Woo Art. 4.4), collect candidate documents into
a request dossier from Nextcloud folders and — via the zgw-document-bridge
when present — case systems, dedupe identical documents by content hash, tag
per-document and per-passage exemption grounds against the existing Woo
Art. 5 grondslagen (`base`) register, generate the inventarislijst from a
template, assemble the disclosure package (redacted PDFs + inventory +
besluit letter via correspondence generation), and track the request
lifecycle through decision, disclosure and publication. This is the
ZyLAB/INDICA/Octobox competitive category; Filinq orchestrates its existing
anonymisation, generation and publication capabilities — it adds no new
engines.

## ADDED Requirements

### Requirement: Woo-request and request-document schemas (REQ-DDWRW-001)

The app MUST declare two schemas in the `dossier` register: `wooRequest`
(`requestNumber`, `subject`, `scopeDescription`, `requesterReference` —
an opaque case/contact reference; the schema MUST NOT carry requester name,
email or address fields (data minimisation, AVG Art. 5(1)(c)) —
`receivedAt`, `decisionDeadlineAt`, `extendedAt`, `extensionReason`,
`status`, `dossierRef`, `inventoryFileRef`, `decisionFileRef`,
`packageFolderRef`, `publicationRecordRef`, `closedAt`) and `requestDocument`
(`wooRequestRef`, `origin` enum `nc-folder`|`zgw-bridge`, `fileRef`,
`externalDocumentRef`, `title`, `documentDate`, `contentHash`,
`dedupeStatus` enum `unique`|`duplicate`, `duplicateOfRef`, `assessment`
enum `pending`|`disclose`|`partially_disclose`|`withhold`,
`exemptionGrounds[]` of `base` slugs, `passageTags[]` of
`{locator, ground, note}`, `redactedFileRef`, `inventoryNumber`). All data
MUST be stored as OpenRegister objects (ADR-001) with a register version
bump for boot import.

#### Scenario: Register import creates the workflow schemas

- GIVEN Filinq and OpenRegister installed
- WHEN `ConfigurationService::importFromApp()` runs on boot
- THEN `wooRequest` and `requestDocument` exist in the `dossier` register and the seeded demo request is queryable
- AND neither schema declares a requester name, email or address property
- @e2e exclude boot-time register import with no UI surface of its own — covered by PHPUnit register-import assertions (tests/unit/Settings/)

### Requirement: Intake with statutory deadline (Woo Art. 4.4) (REQ-DDWRW-002)

Registering a Woo request MUST compute `decisionDeadlineAt = receivedAt + 4
weeks` (Woo Art. 4.4). Exactly one extension MUST be possible, requiring a
non-empty reason and adding at most 2 weeks (Woo Art. 4.4 lid 2), recording
`extendedAt` + `extensionReason`; a second extension attempt MUST be
refused. The computation MUST be clock-injected and app-owned — it MUST NOT
be delegated to OpenRegister's GDPR Art. 12(3) deadline helper, whose terms
belong to a different law. The UI MUST show a deadline indicator (on-track /
due soon / overdue) on index and detail.

#### Scenario: Deadline computed at intake

- GIVEN an operator registering a request received on 2026-06-20
- WHEN the request is created
- THEN `decisionDeadlineAt` is 2026-07-18 and the detail shows the deadline indicator
- @e2e tests/e2e/workflows/woo-request-workflow.spec.ts

#### Scenario: Second extension refused

- GIVEN a request already extended by 2 weeks with reason
- WHEN a second extension is attempted
- THEN it is refused and the deadline is unchanged
- @e2e exclude single-guard date arithmetic — covered exhaustively by clock-injected PHPUnit tests (tests/unit/Service/WooRequestServiceTest.php); the extension UI happy path is covered in the workflow e2e

### Requirement: Candidate-document collection into a request dossier (REQ-DDWRW-003)

An operator MUST be able to collect candidate documents into the request's
dossier folder from Nextcloud folder selections and, when the
zgw-document-bridge is installed, from staged `externalDocument` objects
(recorded with `origin = zgw-bridge` + `externalDocumentRef`). Each
collected file MUST produce a `requestDocument` row with a sha256
`contentHash`, and the copy MUST land in the request's dossier folder so the
existing folder-batch anonymisation runs unchanged. Without the bridge, the
case-system option MUST be hidden (not broken).

#### Scenario: Collect from a Nextcloud folder

- GIVEN a request in `collecting` and a folder with three documents
- WHEN the operator collects the folder
- THEN three `requestDocument` rows exist with hashes and copies in the request dossier folder
- @e2e tests/e2e/workflows/woo-request-workflow.spec.ts

#### Scenario: Bridge option is presence-gated

- GIVEN an instance without the zgw-document-bridge configured
- WHEN the collection surface renders
- THEN only the folder option is offered
- @e2e tests/e2e/spec-coverage/woo-requests.spec.ts

### Requirement: Hash-based dedupe of the collected set (REQ-DDWRW-004)

Collection MUST deduplicate within the request by content hash: rows sharing
a `contentHash` collapse to one `unique` row (first collected) with the
others marked `duplicate` + `duplicateOfRef`. Duplicates MUST be excluded
from assessment, the inventarislijst and the disclosure package, but MUST
remain listed on the request for accountability. Near-identical and
email-thread deduplication is out of scope for this change and MUST be
recorded as future work (the `dedupeStatus` enum remains extensible).

#### Scenario: Identical documents collapse

- GIVEN two collected files with identical content
- WHEN dedupe runs
- THEN one row is `unique` and the other is `duplicate` pointing at it
- AND the duplicate is not offered for assessment
- @e2e tests/e2e/workflows/woo-request-workflow.spec.ts

### Requirement: Exemption-ground tagging reuses the grondslagen register (REQ-DDWRW-005)

Per-document assessment MUST record `assessment`
(`disclose`/`partially_disclose`/`withhold`) with `exemptionGrounds[]`
referencing `base` objects by slug — the existing Woo Art. 5 grondslagen
register, not a new taxonomy. Per-passage tagging MUST be possible via
`passageTags[]` entries `{locator, ground, note}` whose `ground` is a `base`
slug. A `withhold` or `partially_disclose` assessment MUST require at least
one exemption ground. Grounds MUST render via the existing
`BasesResolverService`; an unknown slug MUST render as a visible warning,
never be silently dropped. The `base` schema itself MUST NOT be modified;
the additional Woo Art. 5.1/5.2 grounds ship as new seed `base` objects with
article references in name and description.

#### Scenario: Withholding requires a ground

- GIVEN a unique request document under assessment
- WHEN the operator sets `withhold` without selecting a ground
- THEN the assessment is refused with a message requiring an exemption ground
- @e2e tests/e2e/spec-coverage/woo-requests.spec.ts

#### Scenario: Per-passage tag with a seeded Art. 5 ground

- GIVEN a document assessed `partially_disclose`
- WHEN the operator adds a passage tag "p. 2, alinea 3" with ground "Art. 5.1.2e — Eerbiediging van de persoonlijke levenssfeer"
- THEN the tag is stored with the `base` slug and rendered with the ground's name
- @e2e tests/e2e/workflows/woo-request-workflow.spec.ts

#### Scenario: Entity-level grounds deep-link to the existing review

- GIVEN a request dossier with a completed batch extraction
- WHEN the operator opens entity-level tagging
- THEN they are deep-linked to the existing batch entity review for the dossier and no duplicate entity-review UI is rendered
- @e2e tests/e2e/spec-coverage/woo-requests.spec.ts

### Requirement: Inventarislijst generation from a template (REQ-DDWRW-006)

The app MUST generate the inventarislijst through the existing
document-generation capability (`api/documents/generate`) from a seeded
`woo-inventarislijst` template, listing every `unique` request document with
a stable `inventoryNumber` (assigned in collection order on first
generation), title, document date, assessment and exemption grounds. The
output MUST be stored and recorded in `inventoryFileRef`. No new rendering
engine may be introduced.

#### Scenario: Generate the inventory

- GIVEN a request in `assessing` with three unique assessed documents
- WHEN the operator generates the inventarislijst
- THEN a document is produced listing the three documents with numbers, assessments and grounds, and `inventoryFileRef` is set
- @e2e tests/e2e/workflows/woo-request-workflow.spec.ts

### Requirement: Disclosure-package assembly (REQ-DDWRW-007)

The app MUST assemble the disclosure package into a package folder
(`packageFolderRef`): the redacted PDFs of every `disclose` and
`partially_disclose` document, the inventarislijst and the besluit letter
(generated via the existing correspondence capability from a seeded
`woo-besluit` template, recorded in `decisionFileRef`). Assembly MUST refuse
to include a `partially_disclose` document without a `redactedFileRef`
(never the unredacted original), and MUST exclude `withhold` documents and
duplicates. Package delivery (mail/portal) is out of scope (NC Mail / OR
email leaf boundary).

#### Scenario: Package refuses unredacted partial disclosures

- GIVEN a `partially_disclose` document without a redacted derivative
- WHEN package assembly is attempted
- THEN assembly is refused naming the document, and no package folder is produced
- @e2e tests/e2e/workflows/woo-request-workflow.spec.ts

#### Scenario: Complete package

- GIVEN all disclosable documents have redacted derivatives and inventory + besluit exist
- WHEN the package is assembled
- THEN the package folder contains the redacted PDFs, the inventarislijst and the besluit, and `packageFolderRef` is set
- @e2e tests/e2e/workflows/woo-request-workflow.spec.ts

### Requirement: Guarded request lifecycle (REQ-DDWRW-008)

The `wooRequest` schema MUST declare an `x-openregister-lifecycle`
annotation (canonical `initial: registered`) with transitions
`registered → collecting`, `collecting → assessing`, `assessing →
collecting` (reopen), `assessing → decision`, `decision → disclosed`,
`disclosed → published`, `disclosed → closed`, `published → closed`.
Guard conditions MUST hold before the service requests a transition:
`assessing → decision` requires every `unique` document to have a
non-`pending` assessment; `decision → disclosed` requires
`inventoryFileRef` and `decisionFileRef`; `disclosed → published` requires a
`publicationRecordRef` (woo-publicatie-pipeline handoff; the step MUST be
skippable to `closed` when the pipeline is not installed). Direct
out-of-order status writes MUST be rejected by OpenRegister's lifecycle
guard.

#### Scenario: Decision blocked while assessments are pending

- GIVEN a request in `assessing` with one unique document still `pending`
- WHEN the operator attempts to move to `decision`
- THEN the transition is refused naming the unassessed document
- @e2e tests/e2e/workflows/woo-request-workflow.spec.ts

#### Scenario: Direct status write cannot skip stages

- GIVEN a request in `collecting`
- WHEN a save attempts `status = disclosed`
- THEN OpenRegister rejects the transition
- @e2e exclude server-side lifecycle guard — covered by PHPUnit transition tests (tests/unit/Service/WooRequestServiceTest.php)

### Requirement: Woo-verzoeken UI (REQ-DDWRW-009)

The app MUST provide a Woo-verzoeken index (manifest page; `CnIndexPage` +
`CnDataTable` with request number, subject, status chip and deadline chip)
and a request detail with collection, assessment (grounds multi-select bound
to the `base` register, passage-tag editor), document/package actions and
the lifecycle header, per ADR-012 (`@conduction/nextcloud-vue` components)
and ADR-003 (NL Design tokens via Nextcloud CSS variables, no hardcoded
colors). Modals/dialogs MUST live in their own files under
`src/modals/`/`src/dialogs/`, and every `NcSelect` MUST carry an
`inputLabel`.

#### Scenario: Index shows deadline state

- GIVEN the seeded demo request due within a week
- WHEN the Woo-verzoeken index renders
- THEN the request row shows its status chip and a "due soon" deadline chip
- @e2e tests/e2e/spec-coverage/woo-requests.spec.ts
