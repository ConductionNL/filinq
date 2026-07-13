---
kind: code
depends_on: []
---

## Why

ADR-063 ("MCP as Platform Abstraction", 2026-07-12, hydra #102) rules that apps MUST NOT
hand-write MCP tool code for behaviour OpenRegister can derive. Instead an app declares a
per-schema `x-openregister-mcp` block (under `schema.configuration`) and OpenRegister
derives `{appId}.{schema}.{verb}` CRUD tools; genuine non-CRUD behaviour lives on a real
service method carrying `#[McpTool]`.

DocuDesk today has **no MCP surface at all** — no provider class, no dialect, no
`IMcpScannableServices` opt-in (verified at HEAD `6344a616`, 2026-07-13). It is therefore
invisible to Hermiq, even though "which letter template do we have for X", "did the
mail-merge finish", and **"has that contract been signed yet"** are among the most
obvious things a user would ask an assistant. This change is DocuDesk's greenfield
adoption of ADR-063.

DocuDesk is also the fleet's highest-consequence app for getting the *boundary* right: it
holds **document content, signature material and citizen contact data**, and it can
**produce and sign legally binding artefacts**. A naive "expose all 18 schemas" adoption
would hand an agent the signature blobs, the signer IP addresses, the un-anonymised source
path behind every redacted document, and the publication-prohibition list of protected
individuals. This change therefore adopts MCP with a deliberately **narrow, read-biased**
surface: **8 of 18 schemas ON, all read-only**, plus **exactly one curated write tool**.

## What Changes

- **Declare `x-openregister-mcp` on 8 curated schemas** in `lib/Settings/docudesk_register.json`,
  all `enabled: true` with **`search` + `get` only** (`scope: read`, `readOnlyHint: true`):
  `template`, `huisstijl`, `correspondence`, `generatedDocument`, `batchCorrespondenceJob`,
  `signingRequest`, `dossier`, `base`.
- **No derived `create` / `update` / `delete` verb is enabled on any DocuDesk schema.**
  Templates and huisstijl are governance artefacts (they set the organisation's official
  letterhead and legal boilerplate); correspondence/generatedDocument/batch rows are
  *audit records* of what was produced; `signingRequest` is the legal spine of a signature
  process. None of them is safe for an agent to mutate.
- **Add exactly one curated `#[McpTool]`**: `CorrespondenceService::generate()` becomes
  `docudesk.generateCorrespondence` — a genuinely non-CRUD, multi-step action (fetch
  template → resolve OpenRegister data refs → apply huisstijl → render → produce PDF/DOCX/HTML
  → log a `correspondence` register entry). Annotated honestly: `scope: 'create'`,
  `readOnlyHint: false`, `destructiveHint: false`, `idempotentHint: false`.
- **Add `lib/Mcp/DocudeskScannableServices.php`** implementing OpenRegister's
  `IMcpScannableServices` and returning `[CorrespondenceService::class]` — the per-app
  opt-in that tells OR which classes to scan.
- **Explicitly REFUSE a signing tool.** No `#[McpTool]` is added to `SigningService`,
  `SigningVerificationService`, `SigningAuditService`, or any
  `Service/Signing/*Provider`. Applying an electronic signature is an act with legal
  effect and MUST remain a deliberate human action. See design.md §Refusals.
- **Explicitly REFUSE the batch variant.** `CorrespondenceService::generateBatch()` is
  **not** exposed: a mail-merge over N recipient objects is a bulk personal-data operation
  and an agent-triggerable one is a spam/exfil primitive.
- **10 schemas stay OFF** (signature material, citizen contact data, re-identification
  links, extracted invoice content, the GL side-domain). Exclusion rationale per schema in
  design.md.
- CHANGELOG entry.

## Capabilities

### New Capabilities
- `docudesk-mcp-surface` — DocuDesk's agent-facing tool surface: the curated schema
  allowlist and its verb/scope/hint declarations, the single curated generation tool, and
  the standing refusals (signing, batch mail-merge, signature material, consent/prohibition
  registers).

### Modified Capabilities
_None._ No existing DocuDesk requirement changes behaviour: the dialect is additive
metadata on schemas that already exist, and `CorrespondenceService::generate()` gains an
attribute without any change to its logic or signature. `document-signing`,
`letter-correspondence-generation` and `signing-audit-via-or` are *referenced* by this
change (as the things it deliberately does not expose) but none of their requirements move.

## Impact

- **Config:** `lib/Settings/docudesk_register.json` — 8 schemas gain a
  `configuration.x-openregister-mcp` block. No property, no `required`, no existing
  `configuration` key is touched.
- **Code:** `lib/Service/CorrespondenceService.php` (one attribute + `use` import),
  new `lib/Mcp/DocudeskScannableServices.php`.
- **Runtime dependency:** the derived tools only materialise on an OpenRegister that ships
  `SchemaDerivedToolProvider` + `McpAnnotationValidator` + `McpTool` (all at
  openregister `origin/development` today). If OR is older, the dialect block is inert
  metadata — it does not break schema import.
- **Consumers:** Hermiq is the sole agent consumer; it picks the tools up from OR's
  registry (JSON-RPC `/api/mcp` + the chat facade). No DocuDesk controller, route or
  frontend changes.
- **Data protection:** this change *reduces* the blast radius relative to a naive adoption;
  see design.md §AVG / sensitivity analysis.
