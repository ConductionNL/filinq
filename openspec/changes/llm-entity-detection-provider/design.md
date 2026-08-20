# Design: llm-entity-detection-provider

## Context

Verified at HEAD (DocuDesk `spec/market-gap-wave3-2026-07`, OpenRegister and
hermiq HEAD):

- **OR models `llm` but has not built it.** `EntityRecognitionHandler`
  (`lib/Service/TextExtraction/`) has `METHOD_LLM = 'llm'`,
  `detectEntities()` dispatches to `detectWithLLM()`, and that method's whole
  body is a TODO that returns `detectWithRegex(...)` — i.e. requesting `llm`
  today **silently produces regex results**. The active method is a **global**
  `IAppConfig` setting resolved by `AnonymisationBackendService::getState()`
  (`effectiveMethod`); there is no per-organisation selection.
- **hermiq runs graphs, admin-only, over objects.** `GraphController::run()`
  (`POST /api/graph/run`) requires an authenticated **admin**
  (`groupManager->isAdmin()` → 403 otherwise), takes `graph` (object) +
  `subjectUuid`/`subjectRegister`/`subjectSchema`, resolves that OR object,
  runs `GraphExecutor::run(graph, object)` and returns
  `{subjectUuid, state, trace}`. It is not a text→entities API; there is no
  raw-text detection endpoint at HEAD. hermiq's model runs locally
  (Ollama/Qwen per project setup) — the sovereignty guarantee.
- **DocuDesk owns the detection entry point.**
  `AnonymizationService::extractAndDetectEntities()` drives OR extraction,
  reads back entities and returns them to every DocuDesk flow; it also reads
  document text directly via `readNodeTextSafely()`. Entities are written to
  OR's catalogue through `EntityRelationMapper`.

The consequence for design: DocuDesk must NOT implement OR's `detectWithLLM`
(OR-owned) and must NOT modify hermiq's engine. It formalises a DocuDesk-side
provider seam whose default just delegates to OR, and an LLM provider that
integrates with hermiq via an agentflow — failing closed to the default with a
visible warning whenever the LLM path cannot honestly run.

## Goals / Non-Goals

**Goals:**

- A per-organisation choice between OR's existing detection (default) and a
  local LLM provider, with the LLM output landing in OR's exact entity shape.
- Honest function: the operator always knows which engine produced the
  entities; an LLM failure never silently downgrades quality.
- Strict on-prem: no cloud calls; a non-local hermiq endpoint is refused.
- A unit-testable resolver/mapper seam provable without a live NC or hermiq.

**Non-Goals:**

- No implementation of OR's `detectWithLLM` stub and no change to OR's global
  method setting or `EntityRecognitionHandler` (OR-owned; declared dependency).
- No new hermiq engine code; the text→entities agentflow / app-callable run
  endpoint is a hermiq-side dependency (Open Questions).
- No anonymisation-output changes — output modes are `anonymise-*` /
  `reversible-pseudonymization` territory.
- No cloud LLM support of any kind.

## Decisions

### D1 — The DetectionProvider seam

```
interface DetectionProviderInterface {
    // Returns entities in OR's shape:
    //   [ { value, type, category, positionStart, positionEnd,
    //       confidence(0..1), detectionMethod }, ... ]
    public function detect(int $fileId, string $text, ?array $entityTypes): DetectionResult;
    public function id(): string;                 // 'default' | 'llm'
    public function isAvailable(): bool;          // config/endpoint sanity
}
```

`DetectionResult` = `{ entities: array, providerUsed: string,
fellBack: bool, warning: ?string }`.

- `DefaultDetectionProvider` (`id = default`): delegates to OR's existing
  detection exactly as today — no behaviour change when the org is on the
  default. This is the fallback target.
- `LlmDetectionProvider` (`id = llm`): calls the configured local hermiq
  agentflow (D3), maps its output into OR's shape (D4).

### D2 — Per-organisation selection + resolver

`DetectionProviderResolver::forOrganisation(?string $orgUuid): DetectionProviderInterface`
reads `docudesk.detection.provider` as a per-organisation overlay
(default `default`). The resolver is the single place the provider is chosen
and the single place fallback happens (D5). OR's global method setting is left
untouched — DocuDesk's overlay decides which DocuDesk provider drives the run;
`DefaultDetectionProvider` still honours OR's global method (regex/presidio/…).

### D3 — LLM provider ↔ hermiq contract (local only)

`LlmDetectionProvider` POSTs to `docudesk.detection.hermiq_endpoint` with the
configured `hermiq_agentflow` reference and the document text as the graph
input, expecting a response whose `state` contains an `entities` array. The
integration contract this change targets:

- **Request**: agentflow reference + `{ text, entityTypes }` (the document's
  extracted text and the enabled-type whitelist).
- **Response**: `state.entities[]` = `{ value, type, start, end, score }`.

HEAD reality (documented, not assumed away): hermiq's current
`POST /api/graph/run` is admin-only and object-centric (`subjectUuid` over an
OR object), so it cannot be called as-is with raw text by a non-admin request
path. This change therefore **depends on** a hermiq agentflow-run endpoint
that (a) accepts document text (or an OR chunk/object reference DocuDesk
already has) and (b) is callable by DocuDesk's service context. Until that
endpoint exists the LLM provider reports itself unavailable
(`isAvailable() === false`) and the resolver uses the default provider with a
warning — i.e. the feature ships fail-closed and lights up when the hermiq
endpoint lands. `docudesk.detection.require_local` (default true) makes the
provider **refuse** any endpoint that is not a loopback/private-network host —
no document text may leave the perimeter.

### D4 — Mapping LLM output into OR's shape

The mapper converts each `state.entities[]` item into an OR
`EntityRelationMapper` write:

| LLM field | OR field | Rule |
|---|---|---|
| `type` | entity `type` | mapped to an OR type constant where known (PERSON, LOCATION, …); unknown types kept verbatim so nothing is dropped |
| `value` | entity `value` | verbatim |
| `start`/`end` | relation `positionStart`/`positionEnd` | verbatim; occurrences without positions are still stored (position-less) so redaction's literal-match still applies |
| `score` | relation `confidence` | clamped to `0.0..1.0`; missing → a configured default confidence |
| — | relation `detectionMethod` | constant `llm` |
| — | entity `category` | derived from the mapped type (OR's category map), else `contextual_data` |

Confidence is preserved end-to-end so DocuDesk's existing
confidence-threshold filters (`minConfidence`, the high-confidence prohibition
tier) behave identically whether the entity came from Presidio or the LLM.

### D5 — Honest fallback (no silent degradation)

The resolver wraps the chosen provider:

1. If the org is on `default`, run it, done.
2. If on `llm`: if `LlmDetectionProvider::isAvailable()` is false, OR the
   `detect()` call throws / times out / returns malformed output, the resolver
   runs `DefaultDetectionProvider` and sets
   `fellBack = true`, `providerUsed = 'default'`,
   `warning = "LLM detection unavailable — fell back to <default method>; results may miss context-dependent entities."`
3. The `DetectionResult` (`providerUsed`, `fellBack`, `warning`) is threaded
   into the payload `extractAndDetectEntities()` returns and into the
   anonymisation report, so the review UI shows an explicit banner. A silent
   downgrade — returning regex results while the operator believes the LLM
   ran — is exactly the honest-function defect this change forbids.

The default provider is **always** a working engine, so fallback degrades
recall, never availability.

### D6 — Config / admin UI

Admin settings section (Manifest-V2 shell): per-organisation provider picker
(`NcSelect` with `inputLabel`), hermiq endpoint URL, agentflow reference, a
"require local endpoint" toggle (default on), and a "test connectivity" action
that calls the provider's `isAvailable()` and reports the result. No secrets
are stored beyond the endpoint URL/reference; if a hermiq auth token is later
required it MUST use OR's writeOnly/`_render:false` secret boundary (out of
scope here — this change stores no token).

## OpenRegister / hermiq service usage (ADR-001)

| Operation | Service |
|---|---|
| Default detection | OR path via `DefaultDetectionProvider` (unchanged) |
| Catalogue writes | OR `EntityRelationMapper` (both providers), `detectionMethod` = `llm`/existing |
| Document text | DocuDesk `readNodeTextSafely()` (already reads the node) |
| LLM inference | hermiq agentflow via configured local endpoint (dependency) |

ADR-011: no crypto in this change (no token stored). All processing is local;
`require_local` enforces it.

## Declarative vs imperative

- **Declarative**: the per-org provider config; the manifest settings page.
- **Imperative (justified)**: the resolver's selection + fallback decision, the
  hermiq call, and the OR-shape mapping (all on the detection request path).

## Seed Data

None. Providers are code; config defaults to `default` (OR's existing
behaviour), so an instance that never configures the LLM provider is
byte-identical to today.

## Security Considerations

- `require_local` (default true) refuses any non-loopback/private endpoint —
  document text never leaves the perimeter (the whole sovereignty pitch).
- No cloud calls; no third-party SaaS.
- Fallback is fail-closed to a working engine with a visible warning — never a
  silent quality downgrade.
- Admin-only config routes, explicit auth attributes, in-method guard.
- No token stored in v1; a future token uses OR's `_render:false` boundary.

## Risks / Trade-offs

- [hermiq's `/api/graph/run` is admin-only/object-centric at HEAD] → the LLM
  provider ships fail-closed (`isAvailable() === false`) until the hermiq
  text→entities agentflow endpoint exists; the feature is honest and inert
  rather than broken, and the default provider always runs.
- [LLM latency vs Presidio] → accepted; detection is a deliberate step, and the
  provider has a timeout that triggers the honest fallback.
- [LLM output shape drift] → the mapper is defensive (unknown types kept,
  missing positions/score handled) and malformed output triggers fallback, not
  a crash.
- [Per-org overlay vs OR's global method] → documented: DocuDesk's overlay
  chooses the DocuDesk provider; `default` still honours OR's global method, so
  there is one clear precedence.

## Migration Plan

Additive: new provider classes + resolver + one detection hook + config keys +
a settings view. Default config = `default` = current behaviour, so no
existing flow changes until an org opts in. Rollback = remove the hook and
config; no data migration.

## Open Questions

- **hermiq text→entities agentflow / app-callable run endpoint** — the primary
  dependency. Current `/api/graph/run` is admin-only + object-centric; a
  variant accepting document text (or an OR chunk/object reference) and
  callable from DocuDesk's service context is required for the LLM provider to
  activate. Filed as a hermiq dependency.
- **OR's `detectWithLLM` stub** — could alternatively be implemented OR-side so
  `method=llm` works globally; this change deliberately keeps the choice
  per-organisation in DocuDesk and leaves OR's stub as an OR follow-up.
- **hermiq auth for a service-context call** — if a token is needed, store it
  via OR's `_render:false` secret boundary (parallel to
  reversible-pseudonymization's mapping store); not built here.
