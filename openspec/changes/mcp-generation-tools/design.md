# Design: mcp-generation-tools

## Context

Verified at HEAD `9cc14407`:

- **`filinq-mcp-adoption` (in-flight, this change's dependency)**: 8
  read-only schemas via `configuration.x-openregister-mcp` (16 derived
  tools), one curated `#[McpTool]` on
  `CorrespondenceService::generate(string $templateId, array $dataRefs,
  array $options)` → `filinq.generateCorrespondence` (scope `create`,
  `readOnlyHint: false`, `destructiveHint: false`,
  `idempotentHint: false`), `lib/Mcp/FilinqScannableServices.php`
  returning `[CorrespondenceService::class]`, and refusals: signing,
  batch, `signerRecord`/`signingSession`/`signingAuditEntry`,
  `publicationConsent`, `publicationProhibition`, `anonymizationLink`,
  `financialExtraction`, `templateVersion`, GL schemas.
- `FileListingService::listProcessedFiles()` already aggregates per-file
  status (entity stats, status, ocrProcessed) via a private
  `buildFileInfo()`; there is no public per-file variant yet.
- `AnonymizationService::extractAndDetectEntities(int $fileId)` is the
  intake seam; `anonymizeDocument(...)` takes `$entities`,
  `$acknowledgedOverrides`, runs the prohibition gate first and throws
  `ProhibitionGateException`. Wave-1 `anonymization-review-workbench`
  adds the `documentReview` checked gate (`filinq.review.checked_gate`)
  that anonymise-commit and batch respect.
- OpenRegister tool governance (the "OR tool-whitelist model"): tool
  grants are administered in OR per agent (default-deny; the grants
  editor and `grants` PUT contract live in OR/hermiq); Filinq's job is
  to declare tools honestly, not to enforce grants.
- Wave-1/wave-2 siblings whose posture binds here:
  `anonymization-review-workbench` (suggest-then-approve, checked gate),
  `flow-operations` (the same "trigger intake, never bypass gates,
  counts-only result summaries" contract for its anonymise operation).

## Goals / Non-Goals

**Goals:**

- Close the Carbone-MCP feature comparison: generate, status, templates —
  plus governed anonymisation triggering as the differentiator.
- Zero new data exposure: everything an agent learns through these tools
  is status/counts/references.

**Non-Goals:**

- No new schema exposure (`x-openregister-mcp` untouched) — deferred
  questions about `publicationConsent` `get`-only etc. stay with the
  adoption change.
- No signing, batch, publication, destruction or review-decision tools
  (refusals inherited and restated).
- No retrieval of generated document bytes over MCP (the generation tool
  returns references/metadata per the adoption change; content delivery
  stays in Nextcloud's authenticated file surface).
- No Filinq MCP server or transport work — OR owns the registry and
  JSON-RPC endpoint.

## Decisions

### D1 — Map first, add tools only where derivation genuinely fails (ADR-063)

`list_templates` and `generate_document` already exist on the adoption
surface; redeclaring them as hand-written tools is exactly what ADR-063
forbids and what the shadow-tool hazard warns about. **Decision:** the
capability spec binds the four market-named operations to their homes:
two references (template reads; generateCorrespondence) and two new
curated tools (status aggregate; anonymise intake). An agent-facing
description update is the only touch the referenced tools get, and only
if wording needs to point at the new siblings.

### D2 — `getDocumentStatus` lives on `FileListingService` as a real method

The aggregate (file/pipeline status + entity counts + OCR state + review
checked state + validation verdict + generation/signing references) is
genuine non-CRUD behaviour spanning several stores — not derivable from
any single schema, and the schemas that partially cover it are OFF for
good reasons (`anonymizationLink`). **Decision:** a new public
`FileListingService::getFileStatus(int $fileId): array` (reusing the
existing `buildFileInfo()` internals) carries
`#[McpTool(name: 'getDocumentStatus', scope: 'read',
readOnlyHint: true, destructiveHint: false, idempotentHint: true)]`.
ADR-063 rule 4 honoured: business logic on a real service; the MCP class
only lists services. Rejected: a dedicated `McpStatusService` (a second
aggregation copy of `buildFileInfo()` — drift by construction).

### D3 — `anonymizeDocument` is intake-only and structurally gate-safe

**Decision:** the tool is a narrow attribute-bearing method
`AnonymizationService::anonymizeViaAgent(int $fileId): array` that (a)
runs `extractAndDetectEntities()` (detection + proposals + policy
matching → review queue), (b) proceeds to anonymise-commit ONLY under the
identical checked-gate rules every other surface obeys (valid
`documentReview`, gate mode respected), and (c) exposes NO
`acknowledgedOverrides`, NO entity list, NO output-format/steering
parameters — the signature is the guardrail: what is not a parameter
cannot be smuggled by a prompt-injected agent. A
`ProhibitionGateException` maps to a refused result with the gate reason.
Hints: `scope: 'create'` (it persists detection artifacts and enqueues
review work), `readOnlyHint: false`, `destructiveHint: false` (the source
file is never modified; outputs are additive), `idempotentHint: false`
(re-runs re-detect and supersede). Rejected: exposing
`anonymizeDocument(...)` itself (its `$entities`/`$acknowledgedOverrides`
parameters are precisely what an agent must never control) and a
"commit" boolean (same reason).

### D4 — The entity-value firewall is a response-shape contract

Both new tools return counts/statuses/references ONLY:
`getDocumentStatus` reports entity counts per type (from the existing
entity stats), never entity text; `anonymizeViaAgent` returns
`{status, entityCounts, reviewRequired, prohibitionRefused?}` — never the
detected values, never the placeholder map, never text lengths of
recovered content beyond what wave-1's route already exposes. This is
the same data-minimisation line `flow-operations` draws for its run
summaries and the adoption change draws for schema exposure ("no
document content, no citizen data over MCP") — restated here as a
MUST-NOT requirement with a shape-pinning test, because it is the single
most likely regression: any helpful refactor that "just includes the
entities" in the tool result quietly rebuilds the leak. The review UI
(authorised, audited) remains the only place entity values render.

### D5 — Grants and logging are consumed, not reinvented

Tool authorisation is OR's tool-grant whitelist (default-deny per agent;
administered in OR). Filinq's obligations: honest complete hints on
every curated tool (hermiq's write/destructive classifier fails open on
hintless curated tools — the adoption change documents the same hazard),
and attributable logging: generation already logs a `correspondence` row
(`generatedBy`); anonymise intake runs are OR-audited like every other
detection run and carry an `mcp` attribution the same way
`flow-operations` runs carry `flow` (and wave-1 `ocrResult` carries its
`triggeredBy`). No Filinq-side grant enforcement code — duplicating the
authz path is the ADR-022 violation the fleet keeps re-learning.

### D6 — ScannableServices reconciliation (already reconciled per F4)

`FilinqScannableServices::getScannableServices()` becomes
`[CorrespondenceService::class, FileListingService::class,
AnonymizationService::class]`. This wording collision with
`filinq-mcp-adoption` has been **reconciled in the build phase (decision
F4)**: the adoption delta no longer pins the list as exactly
`[CorrespondenceService::class]` — it now requires the list to *include*
`CorrespondenceService::class` and explicitly permits sibling changes (this
one) to add further curated services via the same scannable-services path,
with `generateCorrespondence` remaining the sole *generation* tool. None of
the adoption change's testable guarantees (16 derived read tools, the
generate tool's registration and hints, every refusal) is weakened — the
former "exactly one write" narrative is now "every write is a curated,
fully-hinted, gate-safe tool; derived write verbs remain zero". No
archive-time amendment of the adoption delta is required.

### D7 — Declarative vs imperative (ADR-031) and ADR-001/ADR-011

Nothing declarative changes (no register JSON edit). The two attributes +
one aggregate method + one narrow intake method are imperative code on
existing services — the ADR-063-sanctioned shape. ADR-011 check: no new
validation/formatting utilities; the aggregate reuses `buildFileInfo()`
internals; no custom tables (ADR-001 untouched).

## Seed Data

No new register objects or schemas — this change persists nothing new
(runs land in existing OR audit/detection stores; generation logs the
existing `correspondence` row). Unit tests construct synthetic subjects
and fixture fileIds (nil-pattern) directly; the MCP e2e probe uses the
seeded sample documents that already ship
(`tests/sample-documents`) plus the adoption change's imported dialect.

## Security Considerations

- **Prompt-injection resistance by signature**: the anonymise tool takes
  one integer. No overrides, no entity control, no format steering, no
  path parameters.
- **Gates hold on every surface**: prohibition gate (thrown, mapped to a
  refusal result), review checked gate (never created/updated by the
  tool), OR RBAC on all underlying reads/writes under the invoking
  principal.
- **No content over MCP**: entity values, placeholder maps, document
  text and file bytes are non-goals of the response shapes and pinned by
  tests (D4).
- **Honest hints** on both curated tools — an unhinted curated write
  fails open in the agent-side classifier (hermiq #57 lesson).
- **Attribution**: every invocation leaves an audited, attributable
  record (`generatedBy`, `mcp`-attributed detection runs).

## Risks / Trade-offs

- [An agent floods anonymise intake (one call per file, many calls)] →
  each call creates bounded review work, nothing irreversible;
  rate/volume governance lives in the OR/hermiq grant + approval layer
  where it belongs. Municipal-scale bulk remains `redaction-at-scale`.
- [`getDocumentStatus` reveals that a file exists and its processing
  state] → resolved under the invoking principal's file access only; a
  file the principal cannot read yields not-found, mirroring wave-1's
  route contract.
- [Sequencing with `filinq-mcp-adoption`] → depends_on declared; if
  this change is applied first by mistake, the ScannableServices class
  does not exist yet — the task list makes creating/extending it
  explicitly conditional on the adoption change's artifact.
- [Two in-flight changes touching one wording] → reconciled in the build
  phase (decision F4): the adoption delta's wording now permits this
  extension (D6); mechanical, not semantic, conflict — resolved, no
  archive-time amendment.

## Migration Plan

1. Land after `filinq-mcp-adoption` (depends_on).
2. Add the two attribute-bearing methods + extend the ScannableServices
   list; re-import/rescan so OR registers the tools.
3. Verify the surface: 16 derived read tools + 3 curated tools; no tool
   name ends in `.create`/`.update`/`.delete`; both new tools carry
   complete hints.
4. Rollback: remove the attributes/list entries — tools disappear from
   the registry; no data to unwind.

## Open Questions

- Should `getDocumentStatus` also accept a document *name* lookup
  (agents rarely hold fileIds)? Provisional: no — name→id resolution is
  already served by the derived `search` tools on the read surface;
  keeping the status tool id-keyed avoids a second search path.
- Where should agent-call rate limits live (OR registry vs hermiq PDP)?
  Out of Filinq's hands; tracked with the OR tool-grant follow-ups.
