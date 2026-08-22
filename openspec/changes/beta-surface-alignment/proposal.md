# Beta surface alignment — Filinq

## Why

Filinq is Technical Core, already on `/connext`, but its four public/code
surfaces (info.xml, manifest nav, conduction.nl product page, docudesk.conduction.nl
docs) disagreed with each other and, in several cases, with what `lib/` actually
implements. Per the fleet beta-readiness pass, an unverified marketing/compliance
claim is a beta blocker. This change reconciles vocabulary across all four
surfaces and corrects or removes every claim that could not be verified against
HEAD.

## Canonical feature list (source of truth: `lib/Controller`, `lib/Service`,
`docs/features.json`, `src/manifest.json`)

1. **Document generation & templating** — Twig/HTML templates rendered to
   **PDF, ODF (.odt), or HTML** (`DocumentService::VALID_FORMATS`), bound to
   Open Register fields, huisstijl-aware, nested resolution up to 3 levels.
2. **PII anonymisation** — entity detection routed through Open Register to a
   **configurable backend**: `regex` (default/fallback), `openanonymiser`,
   `presidio`, or an LLM (`AnonymiserBackendStateClient`). Folder- and
   dossier-level batch anonymisation with Woo Art. 5 grondslagen (legal-basis)
   tracking; anonymisation-link records; publication-prohibition gate.
3. **Digital signing** — eIDAS signature levels SES/AdES/QES, sequential or
   parallel multi-signer workflows, native + ValidSign providers, immutable
   signing audit trail. No "per-instance certificate" concept exists in code.
4. **Publication consent management** — GDPR + Wet Open Overheid, configurable
   objection period (`publication_objection_period_days`, default 28 days).
5. **Document metadata enrichment** — automatic language detection, keyword
   extraction, topic classification, event-driven off Open Register saves.
6. **OCR document scanning** — Tesseract, 100% local.
7. **PDF conversion** — cascading backends: Nextcloud office-app integration
   (Collabora/OnlyOffice/Euro Office via `IConversionManager`) → LibreOffice
   headless → PhpWord → mPDF.
8. **Document comparison** — structural diff between two files or file
   versions; no persistence of either subject's content.
9. **Document validation & quality checks** — format, integrity, encryption,
   text-layer presence, metadata completeness.
10. **Dashboard + Nextcloud dashboard widgets** — AnonymizationWidget,
    FileEntitiesWidget.
11. **Processing-activity export (AVG Art. 30)** — consumes Open Register's
    per-access read-logging (requires OpenRegister >= 0.2.14).
12. **Admin settings** — feature toggles, Open Register register/schema import
    on boot.

## Reconciliation — per surface

### 1. `appinfo/info.xml`
- Added `<summary lang="en">` + `<summary lang="nl">` (real Dutch, was a
  single English-only summary) — ADR-007.
- Rewrote `<description>` to name the full shipped feature set (generation,
  anonymisation, signing, consent) instead of only "GDPR consent + metadata
  enrichment" (the old text under-described the app by ~8 features).
- Corrected `<description>` template claim from "Word/PDF/Excel" to
  "Twig/HTML templates output as PDF/ODF/HTML" (verified — see Claims below).
- Changed `<documentation>` from the dead `conduction.gitbook.io/docudesk-nextcloud/`
  to the live `docudesk.conduction.nl` (verified live via WebFetch).
- Changed `<licence>` from `agpl` to `EUPL-1.2` to match the actual
  `SPDX-License-Identifier: EUPL-1.2` headers in every `lib/` file and the
  product page's "Released under EUPL-1.2" claim. `agpl` was simply wrong.
- Version left at `0.0.34-unstable.18` (matches the fleet's existing
  `x.y.z-unstable.N` pre-release convention, e.g. openregister
  `0.2.17-unstable.12`) — not a "dirty" string by fleet convention, just
  misrepresented externally as "v1.8/Stable" (see product page fix below).
- No `<dependency>` app element added: NC's info.xsd has no such element in
  the entire fleet (grepped all `apps-extra/*/appinfo/info.xml` — zero hits);
  app-to-app requirements are runtime-checked, not schema-declared. The
  existing "Requires: OpenRegister" description text already covers this.

### 2. `src/manifest.json` / nav
No changes needed — nav labels (Dashboard, Consent Management, Anonymization,
My Documents, Folder Analysis, Templates, Signing Requests) already matched
real routes/components and were used as the canonical vocabulary source.

### 3. Product page — `conduction-website/src/pages/apps/docudesk.mdx` (+ nl)
- `status`: `Stable` → `Beta` (matches sibling pre-1.0 apps pipelinq/shillinq
  convention; Filinq is 0.0.x and has features marked
  `@e2e exclude ... not yet shipped` in `docs/features.json`, e.g.
  anonymization-entity-review).
- `version`: fabricated `v1.8` → `v0.0.34` (matches info.xml truth).
- Removed/replaced every unverified or false claim (see Claims verified vs
  removed below): Presidio-exclusivity, "Word/PDF templates, edit in Office",
  "Twelve templates", "per-instance signing certificate", "TMLO-conforme
  archivering met bewaarregels per recordtype", "SharePoint/Office 365"
  integration, the fabricated MCP "two-pass" LLM+Presidio AgentTrace panel,
  the fabricated "Mail and Files sidebar" Showcase item and its dead CTA link.
- Fixed three dead CTA links (`/mail-files`, `/windmill-n8n`, `/llm-redaction`
  all 404 live) by pointing to real pages or removing the specific slugs.
- NL page (`i18n/nl/...`) brought into vocabulary parity with the same fixes;
  its structural section set (missing RotatingCards/WidgetShelf/Showcase/
  AppCrossLinks present on EN) was **not** rebuilt — flagged as a remaining
  gap below, out of scope for a vocabulary-alignment pass.

### 4. Docs — `filinq/docs/` (Docusaurus, served at docudesk.conduction.nl)
- `docs/intro.md`: removed SharePoint/Office 365/WCAG claims, fixed
  Word/Excel → PDF/ODF/HTML, fixed install category ("Office & Text" → the
  actual `organization` category), fixed Presidio framing to "configurable
  backend, regex works out of the box."
- `docs/features/external-integration.md`: was 100% fabricated — a
  `$integrationService->connect('sharepoint', …)` code sample for a service
  that does not exist anywhere in `lib/`. Rewritten to describe the two real
  integration paths: Nextcloud office-app conversion (`IConversionManager`)
  and API-driven workflow automation (OpenConnector/Windmill/n8n).
- `docs/features/workflow-automation.md`: was 100% fabricated — a
  `$workflowService` with a visual designer, FTP/SharePoint/Office365
  monitoring, approval chains — none of which exist in `lib/`. Rewritten to
  describe the real event-driven enrichment/validation listeners
  (`lib/EventListener/*`) and the same external-API automation path.
- `docs/features/wcag-compliance.md`: was 100% fabricated — a `$wcagService`
  claiming WCAG 2.1 AAA + PDF/UA document-content checking and auto-fix. No
  WCAG code exists anywhere in `lib/` (grepped, zero hits). Rewritten to an
  honest accessibility page: UI-level WCAG AA via standard Nextcloud/nc-vue
  components (real), no document-content accessibility checker (does not
  exist) — this was flagged as a **beta blocker** given it was framed as a
  government-procurement compliance claim.
- `docs/GOVERNMENT-FEATURES.md`: corrected the licence line (AGPL → EUPL-1.2)
  and rows A-01/A-02/A-06 from "Beschikbaar" (own WCAG checks / document WCAG
  checking) to "Via platform" / "N.v.t." with an honest toelichting. Rows
  A-03/A-04/A-05 and the rest of the table (F-*, P-*, I-*, AR-*, BO-*) were
  already accurate against code and were left unchanged — AR-04 (TMLO/MDTO)
  already correctly said "Via platform / Via OpenRegister", which is why the
  product page's TMLO claim (see below) was the actual bug, not this table.

### 5. Icon — `img/app.svg`
Already correct: 24×24 viewBox, single `#fff` fill path, matches app-icon
convention. No change needed.

## Claims verified vs removed (step 5)

| Claim | Verdict | Evidence |
|---|---|---|
| "Microsoft Presidio under the hood" (exclusive) | **Corrected** | `AnonymiserBackendStateClient` supports `method` ∈ {regex, openanonymiser, presidio, llm}; regex is the fallback default. Presidio is one option, not the engine. |
| "Word and PDF templates... Edit in Office" | **Removed/corrected** | `DocumentService::VALID_FORMATS = ['pdf','odf','html']`; template schema description says "Twig/HTML templates voor PDF-generatie." No DOCX/XLSX output path exists. |
| "Twelve templates... beschikkingen, jaarverslagen, bezwaarbrieven, subsidy decisions, permits" | **Removed** | Only 3 seed templates exist in `lib/Settings/filinq_register.json` (`beschikking-standaard`, `brief-algemeen`, `rapportage-kwartaal`) — no jaarverslag/bezwaarbrief/subsidy/permit templates ship. |
| "Per-instance signing certificate" | **Removed** | Zero matches for "certificate" anywhere in `lib/Service/Signing/`; signing is level-based (SES/AdES/QES) via `NativeSigningProvider`/`ValidSignProvider`, not a certificate object. |
| "TMLO-conforme archivering for inbound, with retention rules per recordtype" | **Removed** | No retention/archival/TMLO code in `lib/` — `docs/GOVERNMENT-FEATURES.md` itself already correctly marks TMLO/MDTO as "Via platform / Via OpenRegister," not a Filinq feature. |
| "SharePoint, Office 365... integration" | **Removed** | Zero matches for SharePoint/Office365/`$integrationService` in `lib/` or `src/`. "Office App" backend in code means Collabora/OnlyOffice/EuroOffice via NC's `IConversionManager`, not Microsoft 365. |
| Two-pass Presidio+LLM MCP redaction pipeline (product page AgentTrace) | **Removed** | Zero matches for MCP/`context_check`/"two-pass" in `lib/`. |
| WCAG 2.1 AAA / PDF-UA document compliance checking + auto-fix | **Removed, replaced with honest "not implemented"** | Zero matches for "wcag" anywhere in `lib/`. |
| GDPR/WOO objection period "minimum 4-week" | **Verified, kept** | `SettingsService::loadFeatureToggles()` defaults `publication_objection_period_days` to `28`. |
| "Released under EUPL-1.2" (product page) vs `<licence>agpl</licence>` (info.xml) | **info.xml corrected to EUPL-1.2** | Every `lib/` file's SPDX header says `EUPL-1.2`; product page already said EUPL-1.2; info.xml was the outlier. |
| Docs served at docudesk.conduction.nl | **Verified live** | WebFetch confirmed a real Docusaurus site with EN/NL toggle; info.xml `<documentation>` was pointing at a dead gitbook.io URL and has been corrected. |

## Remaining / needs a decision

- **NL product page structural parity**: `i18n/nl/.../docudesk.mdx` lacks the
  RotatingCards/WidgetShelf/Showcase/PartnersForApp/AppCrossLinks sections the
  EN page has. Vocabulary was aligned; rebuilding the NL page to full
  structural parity is a larger follow-up, not done here.
- **Docs-wide audit**: `docs/features/` has ~40 files; this pass fixed the
  three most severely fabricated ones (external-integration,
  workflow-automation, wcag-compliance) plus intro.md and
  GOVERNMENT-FEATURES.md. Files like `document-classification.md`,
  `reports-interface.md`, `print-functionality.md` etc. were not individually
  re-verified against `lib/` in this pass — recommend a dedicated
  docs-audit follow-up change before treating the whole docs site as
  beta-trustworthy.
- **`<dependency>` on OpenRegister**: not schema-representable (see above);
  worth a fleet-wide follow-up if NC's info.xsd ever adds app-dependency
  support.
