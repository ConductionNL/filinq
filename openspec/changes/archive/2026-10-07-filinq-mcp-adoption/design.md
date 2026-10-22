# Design — filinq-mcp-adoption

Context: ADR-063 (MCP as Platform Abstraction, hydra #102) + the fleet MCP wave,
2026-07-13. Filinq at HEAD `6344a616` has **no** MCP provider, **no** dialect and **no**
`IMcpScannableServices` — this is a greenfield adoption, not a migration. There is therefore
no stale hand-written CRUD tool that could shadow a derived one (ADR-063's rule 1 hazard
does not apply here).

## Where the dialect goes

OpenRegister reads the block from `schema.configuration['x-openregister-mcp']`
(`SchemaMapper::validateMcpAnnotation()`, `SchemaDerivedToolProvider`), **not** from the
schema root. Every allowlisted Filinq schema already has a `configuration` object (with
`objectNameField` / `objectDescriptionField`); the MCP block is added as a sibling key.
No existing `configuration` key, property, or `required` entry is touched.

Verb set is closed: `search | get | create | update | delete`. Scopes are closed:
`read | create | update | delete`. Hints are `readOnlyHint | destructiveHint |
idempotentHint`. `filters` is legal on `search` only and **every entry must be a real
property of that schema** or `McpAnnotationValidator` fails the import.

## Curation table — 8 ON of 18

All eight are **read-only** (`search` + `get`, `scope: read`, `readOnlyHint: true`). No
schema gets a write verb.

| # | Schema | Verbs | Search filters (all verified real properties) | Why a human would ask an assistant this |
|---|--------|-------|-----------------------------------------------|------------------------------------------|
| 1 | `template` | search, get | `category`, `namespace`, `format` | "Which letter template do we have for a Woo decision?" — the entry point to every generation flow; `content` is organisational boilerplate, not personal data. |
| 2 | `huisstijl` | search, get | `name` | The agent must be able to name a valid `huisstijlId` before it may call `generateCorrespondence`; the object is branding config (logo, colours, header/footer HTML) with zero personal data. |
| 3 | `correspondence` | search, get | `status`, `templateId`, `caseReference`, `recipientType` | "Did we already send a letter on case Z-2026-114?" — the audit log of what was produced; holds references and status only, never document bytes. |
| 4 | `generatedDocument` | search, get | `status`, `templateId`, `zaakId`, `format` | "Was the decision document for this zaak generated, and did it warn about anything?" — `warnings` / `errorMessage` make this the app's most useful diagnostic read. |
| 5 | `batchCorrespondenceJob` | search, get | `status`, `templateId`, `initiatedBy` | "Did the mail-merge finish?" — progress counters (`recipientCount` / `completedCount` / `errorCount`), no recipient identities. |
| 6 | `signingRequest` | search, get | `status`, `signatureLevel`, `provider`, `initiatorUserId` | **"Has that contract been signed yet?"** — the single highest-value Filinq question. Process metadata only: no signature material, no signer emails, no IPs (those live on the excluded `signerRecord`). |
| 7 | `dossier` | search, get | `name` | "Which grondslagen are attached to dossier X, and when was it last checked?" — Woo administration, `name` + `description` + `bases` + `checkedOn`, zero personal data. |
| 8 | `base` (Grondslag) | search, get | `name` | Pure lookup table (`name`, `description`) — lets the agent explain what a Woo legal basis *means* instead of echoing an opaque id. Zero risk. |

## Exclusions — 10 OFF of 18

| Schema | Why OFF |
|--------|---------|
| `signerRecord` | Holds `signatureData` (the signature material itself), `email` and `ipAddress`. Exposing it hands an agent the cryptographic artefact and the signer's network location. **Never.** |
| `signingSession` | Holds `signatures` and `signedDocumentPath`. Same reason as `signerRecord`. |
| `signingAuditEntry` | The tamper-evident signing trail: actor IPs, timestamps, provider metadata. An audit trail exists to be authoritative; it is read through the audited UI path (`SigningAuditService::getAuditTrail()`), not through a general-purpose agent. |
| `publicationConsent` | `contactEmail`, `contactAddress`, `entityText`, `objectionReason`, `consentStatus` — citizen contact details **and the reasons they objected to publication**. A `search` here is a bulk read of citizen personal data plus their stated objections. OFF. |
| `publicationProhibition` | A list of named individuals (`primaryName`) with the legal reason (`reason`, `legalAuthority`, `severity`) they may not be published — typically safety- or threat-related. Enumerating this list is itself the harm. **OFF, hardest exclusion in the app.** |
| `anonymizationLink` | Maps an anonymised file back to `sourceFileId` / `sourceFilePath` — the un-redacted original. Exposing it is a **re-identification primitive** that undoes the redaction the object records. OFF. |
| `financialExtraction` | `fields` holds the extracted *contents* of a third party's invoice (amounts, account numbers, supplier identity) plus per-field confidence. Content leak; also an AI-artefact table, not a thing humans ask about. OFF. |
| `glAccountBooking` | `supplierIdentity` (a named person or company) plus the account it was booked to. Bookkeeping side-domain; no assistant question needs it. OFF. |
| `glAccountMappingRule` | Bookkeeping configuration. Marginal value; ADR-063 rule 3 says bias to fewer. OFF (deferred, see below). |
| `templateVersion` | Historical bodies of every template. The agent can already `get` the current `template`; version history is a diffing concern for the UI. Adds tool count and content surface for no assistant value. OFF. |

## The one curated tool

This adoption baseline adds exactly one curated tool — the document-*generation*
tool below. It is **not** a closed set: the scannable-services path
(`FilinqScannableServices`) is the extension point, and sibling changes MAY add
further curated tools through it (e.g. `mcp-generation-tools` adds the read-only
`getDocumentStatus` aggregate and the gate-safe `anonymizeDocument` intake).
`generateCorrespondence` remains the sole *generation* tool, and every added
curated tool inherits every refusal in the §Refusals section unchanged.

`CorrespondenceService::generate(string $templateId, array $dataRefs, array $options)` →
`filinq.generateCorrespondence`.

It qualifies as curated (ADR-063 rule 2/4) because it is **not** derivable CRUD: it fetches
a template, resolves OpenRegister data references through `DataResolverService`, applies a
huisstijl, renders through `TemplateRenderer`, produces PDF/DOCX/HTML/email output, and then
logs a `correspondence` register entry. Five steps across four services — exactly the
"curated multi-step action" the ADR carves out. It stays in `CorrespondenceService`
(rule 4: business logic never lives in an MCP class); the attribute is metadata only and the
method's logic and signature do not change.

**Honest annotation** (read from the method body, not assumed):

| Hint | Value | Justification from the code |
|------|-------|------------------------------|
| `scope` | `create` | It calls `logCorrespondence()`, which persists a new `correspondence` object. |
| `readOnlyHint` | `false` | It writes. |
| `destructiveHint` | `false` | It only ever *adds*: a new document artefact and a new register row. It never mutates or deletes an existing object. |
| `idempotentHint` | `false` | Two identical calls produce two documents and two register rows. Hermiq must treat a repeat as a new write, not a retry. |

This annotation is load-bearing, not decoration: hermiq #57 established that a curated
two-segment tool that declares **no** hints previously **failed open** in the
write/destructive classifier — never stripped by default-deny, never gated for approval.
An unannotated curated write tool is a governance hole.

## Refusals

**Signing.** No `#[McpTool]` on `SigningService`, `SigningVerificationService`,
`SigningAuditService`, `SigningProviderInterface` or any provider implementation
(`NativeSigningProvider`, `ValidSignProvider`), and `signingRequest` gets no write verb.
An electronic signature is an act with legal effect under eIDAS. A tool that could
`initiateSigning`, `cancelSigning` or `produceSignedArtifact` would let an agent bind the
organisation — or a citizen — to a document no human read. There is no annotation, no
approval gate and no hint value that makes that acceptable; the answer is that the tool must
not exist. What *is* exposed is `signingRequest.search|get` — the agent can tell you a
contract is `status: pending` with a `deadline` next Friday, which is the actual question
people ask, and it can do so without touching a signature.

**Batch mail-merge.** `generateBatch()` gets no attribute. A single tool call that renders a
template against N recipient objects and produces N documents is a bulk personal-data
operation and a mass-mailing primitive. Single-document generation is bounded and reviewable;
batch is not.

**All writes on the eight allowlisted schemas.** `template` and `huisstijl` define the
organisation's official letterhead and legal boilerplate — an agent silently editing the
footer of every future decision letter is a governance failure, not a convenience.
`correspondence` / `generatedDocument` / `batchCorrespondenceJob` are audit records whose
whole value is that nothing rewrites them. `dossier` / `base` are the Woo legal-basis
administration.

## AVG / sensitivity analysis

Filinq processes three categories of protected material. The allowlist is drawn so that
**none of them crosses the MCP boundary**.

1. **Citizen personal data (AVG art. 4(1))** — `publicationConsent` holds `contactEmail`,
   `contactAddress` and `objectionReason`; `publicationProhibition` holds `primaryName` plus
   the legal reason for the prohibition. The derived `search` verb has **no field-level
   projection**: it returns whole objects. Exposing either schema therefore means exposing
   citizen contact details and objection reasons in bulk to an LLM context window. Both are
   OFF. `publicationProhibition` additionally names individuals who are barred from
   publication *for their safety* — enumeration is the harm, so it is OFF even for `get`.
   *Purpose limitation (art. 5(1)(b))*: these registers were collected to run a Woo
   objection procedure, not to answer assistant questions.

2. **Signature material and its metadata** — `signerRecord.signatureData`,
   `signingSession.signatures`, and the signer IP addresses on `signerRecord` /
   `signingAuditEntry`. An IP address is personal data (AVG rec. 30); a signature is the
   instrument of legal consent. All three schemas are OFF, and no read verb is granted on
   them. `signingRequest` — process metadata, no signature, no signer contact details — is
   the only signing-domain schema exposed.

3. **Re-identification risk** — `anonymizationLink` is the join table between a redacted
   document and its original. Filinq's entire anonymisation feature exists to break that
   join for downstream readers; handing an agent the table that restores it would defeat the
   control. OFF. This is a *data-minimisation* (art. 5(1)(c)) and *integrity* (art. 5(1)(f))
   argument, and it is why `anonymizationLink` is OFF even though it looks innocuous
   (it is just file ids and paths — which is exactly the problem).

**Document content.** The two schemas an agent will actually search — `correspondence` and
`generatedDocument` — hold **references only** (`templateId`, `dataRefs`, `zaakId`,
`status`, `warnings`), never the rendered bytes. That is a property of the existing model,
and this change relies on it. `templateVersion` and `financialExtraction` are the two
schemas that *do* carry content (template bodies; extracted invoice fields) and both are OFF.
The one content-bearing property inside the allowlist is `template.content` — organisational
boilerplate with placeholders, not personal data.

**Legal effect.** `generateCorrespondence` produces a draft on official letterhead. That is a
real capability with real weight, which is why it is (a) the *only* write in the app's
surface, (b) single-recipient, (c) non-dispatching, (d) non-signing, and (e) fully logged as
a `correspondence` object with `generatedBy`. The audit trail is the mitigation: every
agent-generated document leaves a row.

## Verification

- `python3 -m json.tool lib/Settings/filinq_register.json` after every edit; schema count
  MUST stay 18 and no key may be dropped.
- Cross-check each `search.filters` entry against that schema's `properties` map before
  import — `McpAnnotationValidator` rejects an unknown filter and the failure takes down the
  **whole register import**, not just the tool.
- After import, assert the derived surface is exactly 16 tools + 1 curated tool, and that no
  tool name ends in `.create`, `.update` or `.delete`.
- Scoped `phpcs` clean on `lib/Service/CorrespondenceService.php` and
  `lib/Mcp/FilinqScannableServices.php`; zero new PHPUnit failures against a
  self-measured baseline.

## DEFERRED_QUESTIONS

1. **Should `glAccountMappingRule` (and read-only `glAccountBooking`) be exposed later?**
   "Why was this invoice booked to 4300?" is a legitimate finance question, and
   `glAccountMappingRule` is pure config with no personal data. Left OFF now on ADR-063's
   bias-to-fewer rule and because the GL feature landed only days ago
   (`ai-gl-account-suggestion`). Revisit once the feature has users.
2. **`generateCorrespondence` resolves arbitrary `dataRefs` (register/schema/id).** OpenRegister
   RBAC gates the resolution, so an agent cannot read an object its principal may not read —
   but a template plus a data-ref is, in effect, a *rendering* channel for any readable
   object. Should the tool constrain `dataRefs` to the template's declared
   register/schema, rather than accepting any triple? Worth a follow-up in
   `letter-correspondence-generation`.
3. **`get`-only exposure of `publicationConsent`?** "Has this person consented to
   publication?" is a real Woo-officer question, and a `get` by id (no `search`, no
   enumeration) would answer it. Left fully OFF for now because the object still returns
   `contactEmail` / `contactAddress` / `objectionReason` in one blob — it needs field-level
   projection in the dialect first (see 4).
4. **Field-level projection is missing from the dialect.** `x-openregister-mcp` is
   all-or-nothing per schema: you cannot expose a schema minus its sensitive properties.
   Several of Filinq's exclusions (and hrmq's `Employee`) would become safe, high-value
   reads if the dialect gained a `properties` allowlist per verb. This is the single
   highest-leverage OpenRegister follow-up from this fleet wave.
