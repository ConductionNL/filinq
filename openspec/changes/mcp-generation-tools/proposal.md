---
kind: code
depends_on:
  - docudesk-mcp-adoption
---

# Proposal: mcp-generation-tools

## Why

**Carbone is the only document-generation competitor shipping an official
MCP server** (intelligence DB competitor row `carbone`; the
AI-assistant-driven-workflows trend runs through the whole 2026 research
set). Conduction owns the infrastructure to answer that: OpenRegister's
MCP provider + tool registry, Hermiq as the governed agent runtime, and
ADR-063's derive-don't-hand-write discipline. An assistant that can be
asked "maak een ontvangstbevestiging voor zaak Z-2026-114", "is dat
document al geanonimiseerd?", or "welke sjablonen hebben we voor een
besluit?" is the workflow the market is converging on — and the moat is
doing it *governed*: grants, logging, and a hard line on what never
crosses the agent boundary.

The in-flight sibling change **`docudesk-mcp-adoption`** (read at HEAD
`9cc14407`, proposal/design/specs/tasks complete) establishes DocuDesk's
baseline: 8 read-only schemas via `x-openregister-mcp`
(`docudesk.template.search|get`, `docudesk.generatedDocument.search|get`,
`docudesk.signingRequest.search|get`, ...), the curated generation write
tool `docudesk.generateCorrespondence` (its sole *generation* tool, which
the adoption delta explicitly permits sibling changes to extend, per
decision F4), and standing refusals (no signing, no batch mail-merge, no
`publicationConsent`/`publicationProhibition`/`anonymizationLink`/
signature-material exposure). This change EXTENDS that surface with two
further curated tools and contradicts none of it.

Measured against the market's four assistant-facing document operations
(the Carbone MCP feature set: generate document, get status, list
templates — plus DocuDesk's differentiator, anonymisation):

- **generate_document** and **list_templates** are already covered by
  the adoption change (`generateCorrespondence`; `template.search|get`) —
  this change binds them into the documented operation map instead of
  duplicating them (ADR-063: never hand-write what exists).
- **get_document_status** is NOT derivable: a document's processing
  status is an aggregate across services (file/entity status, OCR state,
  review checked state, validation verdict, generation/signing records) —
  no single OR schema answers it, and the schemas that partially could
  (e.g. `anonymizationLink`) are rightly OFF.
- **anonymize_document** does not exist as a tool at all, and a naive one
  would be dangerous: anonymisation results contain the detected entity
  values — exactly what must never flow into an LLM context window.

## What

- **An operation map, not four new tools** (ADR-063 discipline):
  `list_templates` ≡ the derived `docudesk.template.search|get`;
  `generate_document` ≡ the curated `docudesk.generateCorrespondence`
  (both from `docudesk-mcp-adoption`, referenced — not redeclared).
- **One curated read tool** `docudesk.getDocumentStatus` — a genuine
  non-CRUD aggregate on a real service method (`FileListingService`),
  returning per-file processing status: pipeline status, entity COUNTS,
  OCR state/confidence, review checked state, validation verdict, and
  references to generation/signing records. Read-only, honestly hinted.
- **One curated write tool** `docudesk.anonymizeDocument` — triggers the
  anonymisation intake pipeline (`AnonymizationService`) for one file:
  extraction, detection, grondslag proposals, policy matching, review
  queue. It can NEVER bypass the human gates: no `acknowledgedOverrides`
  parameter exists on the tool (prohibition gate always enforced), it
  never creates/updates `documentReview` (checked gate untouched), and
  anonymise-commit follows the same gate rules as every other surface.
- **The entity-value firewall**: no MCP response from any DocuDesk tool
  ever contains detected entity values, anonymisation placeholder maps,
  or document text — counts, types, statuses and references only.
- **Governance rails**: both curated tools carry complete honest
  `#[McpTool]` hints (hermiq's write/destructive classifier fails open on
  hintless curated tools); tool availability is governed by OpenRegister's
  tool-grant whitelist (default-deny per agent, grants administered in
  OR — DocuDesk only declares); every invocation is attributable and
  logged (a `correspondence` row for generation — existing behaviour —
  and OR-audited processing records with an `mcp` attribution for
  anonymisation intake).
- **All `docudesk-mcp-adoption` refusals restated as binding here**: this
  change adds no schema exposure, no signing surface, no batch surface.

## Capabilities

### New Capabilities

- `mcp-generation-tools`: the assistant-facing document-operations
  surface — the operation map onto the adoption baseline, the
  `getDocumentStatus` and `anonymizeDocument` curated tools, the
  entity-value firewall, and the grant/logging rails.

### Modified Capabilities

- None in this repo's canonical specs. The `docudesk-mcp-adoption` delta
  now requires `DocudeskScannableServices` to *include*
  `CorrespondenceService::class` and explicitly permits sibling changes to
  extend the list; this change adds the services carrying the two new
  curated tools. The wording collision was reconciled in the build phase
  (decision F4) directly in the adoption delta — no archive-time amendment
  and no landed requirement move.

## Impact

- **Code**: `#[McpTool]` attribute + small public aggregate method on
  `lib/Service/FileListingService.php`; `#[McpTool]` on a new narrow
  `AnonymizationService` intake seam (attribute-bearing method only — the
  pipeline logic is untouched); `lib/Mcp/DocudeskScannableServices.php`
  list extended.
- **Config**: no `x-openregister-mcp` changes — zero new schema
  exposure; the register JSON is untouched by this change.
- **Runtime dependency**: same as the adoption change (OpenRegister with
  `McpTool` + scannable-services scanning + the tool-grant model);
  Hermiq is the consuming agent runtime.
- **Data protection**: strictly narrows what an agent path can see
  relative to the UI (counts/status vs entity review data); the
  anonymise tool creates review work for humans, it cannot publish,
  export, or override.
- **Out of scope**: no MCP tool for signing, batch operations,
  publication, destruction, or entity review decisions; no DocuDesk-side
  MCP server (OR owns the JSON-RPC surface); no n8n/OpenConnector tools.
