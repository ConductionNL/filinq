---
kind: code
---

# Proposal: llm-entity-detection-provider

## Why

"Anonimiseren met LLM" is the narrative the Dutch government is actively
writing, and DocuDesk should own it before a competitor does:

- The rijksbrede innovatieproject **"Anonimiseren met LLM"** (Digitale
  Overheid) and Binnenlands Bestuur's "Anonimiseren in de Woo met kunstmatige
  intelligentie" (Reveal/ZyLAB) are pushing LLM-based anonymisation into
  procurement conversations (R3 section C, demand_score 4).
- The NL flagship **"anonimiseren bij de bron"** tool — a Conduction project
  with Hoeksche Waard + 5 municipalities, built on **Nextcloud + LLM** — is
  the exact position DocuDesk holds in market (R2 C2). Its selling point is
  **on-prem sovereignty**: the model runs inside the organisation's perimeter,
  no document ever leaves it.
- The **Hoeksche Waard tool** and the MinBZK **maskeringsscript** (R2 A3/A5)
  prove fully-local LLM/NLP anonymisation is real; ZyLAB/Reveal already
  *market* the LLM angle. DocuDesk is Presidio/regex today (R2 D:
  "COVERED — on-prem is core positioning" for sovereignty, but no LLM engine
  option is specced).

Regex and Presidio miss context-dependent PII an LLM catches — a person
referred to only by role-plus-context, an address split across a sentence, an
implicit identifier. Offering an LLM detection provider (running locally via
hermiq, no cloud) is the differentiator; doing it **honestly** — never
silently degrading to a weaker engine when the LLM fails — is the fleet's
non-negotiable.

Verified at HEAD (why this is a thin provider seam, not an engine):

- OpenRegister already models an LLM detection method:
  `EntityRecognitionHandler` has `METHOD_LLM = 'llm'`, `BackendState::METHODS`
  includes `'llm'`, and `detectEntities()` dispatches to `detectWithLLM()` —
  **but that method is a stub**: its body is `// TODO: Implement LLM-based
  entity extraction. For now, fall back to regex.` and it silently returns the
  regex result (`lib/Service/TextExtraction/EntityRecognitionHandler.php`).
  The detection method is chosen by a **global** `IAppConfig` setting
  (`AnonymisationBackendService`), not per organisation.
- hermiq ships `POST /api/graph/run` (`lib/Controller/GraphController.php`)
  which runs an agent graph and returns `{subjectUuid, state, trace}` — but at
  HEAD it is **admin-only** and **object-centric**: it requires `graph`
  (object), `subjectUuid`, `subjectRegister`, `subjectSchema` and runs over an
  existing OpenRegister object, not raw text. There is no text→entities
  endpoint yet.
- DocuDesk already owns the detection entry point
  (`AnonymizationService::extractAndDetectEntities`), reads document text
  itself (`readNodeTextSafely()`) and writes entities into OR's catalogue via
  `EntityRelationMapper`.

So DocuDesk formalises a small **DetectionProvider** seam whose default
implementation delegates to OpenRegister's existing detection (unchanged), and
adds an LLM provider that calls a local hermiq agentflow, maps its output into
OR's entity/confidence shape, and — critically — falls back to the default
provider with an **explicit report warning** on any failure. The provider is
chosen **per organisation** (the config surface OR does not have). We do not
touch OR's stub or hermiq's engine; we own the DocuDesk-side selection,
mapping and honest fallback.

## What Changes

- **DetectionProvider seam (DocuDesk)**: an interface with two
  implementations — `DefaultDetectionProvider` (delegates to OR's existing
  `extractAndDetectEntities` path; the default) and `LlmDetectionProvider`
  (calls a configured local hermiq agentflow). Both return the same
  entity/confidence shape OR persists.
- **Per-organisation provider selection**: `docudesk.detection.provider`
  resolved per organisation (default `default`), with the hermiq endpoint +
  agentflow reference held in admin config. OR's global method setting is
  unchanged; this is a DocuDesk overlay.
- **LLM provider over hermiq (local only)**: `LlmDetectionProvider` sends the
  document's extracted text to the configured hermiq graph endpoint and reads
  detected entities from the returned state, mapping `{value, type, positions,
  confidence}` into OR's `EntityRelationMapper` rows with
  `detectionMethod = llm`. No cloud calls — hermiq runs the model on-prem
  (Ollama/Qwen), and the provider refuses a non-local endpoint by config.
- **Honest fallback (no silent degradation)**: on any LLM-provider failure
  (endpoint down, timeout, malformed output, non-local endpoint) the run
  falls back to `DefaultDetectionProvider` AND records an explicit warning that
  is surfaced in the anonymisation report / review — the operator always knows
  which engine actually produced the entities. Success also records which
  provider ran.
- **Admin config UI**: a settings section (Manifest-V2 shell) to pick the
  provider per organisation, set the hermiq endpoint + agentflow reference, and
  test connectivity.

## Capabilities

### New Capabilities

- `llm-entity-detection-provider`: a per-organisation, pluggable
  entity-detection provider seam over OpenRegister's existing detection, with a
  local hermiq LLM provider, OR-shape entity mapping, and honest
  fallback-with-warning to the default provider (no silent degradation, no
  cloud calls).

### Modified Capabilities

<!-- none in DocuDesk. OR's detection engine, catalogue and the global
     backend-method setting are consumed unchanged; OR's own detectWithLLM
     stub and a hermiq text->entities agentflow endpoint are declared as
     dependencies below, not modified here. -->

## Impact

- New `lib/Service/Detection/DetectionProviderInterface.php`,
  `DefaultDetectionProvider.php` (delegates to OR),
  `LlmDetectionProvider.php` (hermiq call + OR-shape mapping),
  `DetectionProviderResolver.php` (per-organisation selection + fallback).
- Hook the resolver into `AnonymizationService::extractAndDetectEntities` so
  the chosen provider runs and the provider-used / fallback warning lands in
  the returned payload and the report.
- New `lib/Controller/DetectionProviderSettingsController.php` +
  `api/detection-provider/*` (get/set per-org config, test connectivity);
  fail-closed admin auth.
- Admin config keys: `docudesk.detection.provider` (per-org overlay),
  `docudesk.detection.hermiq_endpoint`, `docudesk.detection.hermiq_agentflow`,
  `docudesk.detection.require_local` (default true).
- `src/manifest.json` + settings view: provider picker + hermiq wiring.
- Consumes / depends on (declared, not modified):
  - OpenRegister `EntityRelationMapper`, `TextExtractionService`,
    `AnonymisationBackendService` (global method) — presence-gated.
  - **hermiq** `POST /api/graph/run` (admin-only, object-centric at HEAD) —
    this change **depends on** a hermiq agentflow that accepts document text
    and returns entities in OR's shape (or an app-callable variant of
    `/api/graph/run`); the current admin-only/object-centric shape is the HEAD
    reality and is documented as the integration contract + open question in
    design.md. DocuDesk never assumes it can call the engine directly without
    that endpoint — it fails closed to the default provider.
- Non-overlap (declared dependencies, not re-specced):
  `custom-dictionary-recognition` (sibling; both add recognizers into OR's
  catalogue — orthogonal), `anonymization-review-workbench` (active; renders
  the warning), `anonymise-*` output-mode changes (untouched).
- Evidence: "Anonimiseren met LLM" (R3 C); Hoeksche Waard on-prem LLM tool +
  Conduction (R2 C2); LLM-Anonymizer OSS / maskeringsscript (R2 A3/A5); R2 D
  local/on-prem LLM anonymisation row.
