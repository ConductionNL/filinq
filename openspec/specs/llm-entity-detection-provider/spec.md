---
status: implemented
---

# llm-entity-detection-provider Specification

**OpenSpec changes**:
- [llm-entity-detection-provider](../../changes/archive/2026-11-24-llm-entity-detection-provider/) _(archived 2026-11-24)_

## Purpose

Offer a per-organisation choice of entity-detection provider over
OpenRegister's existing detection: a default provider that delegates to OR's
Presidio/regex path unchanged, and an LLM provider that calls a **local**
hermiq agentflow (no cloud calls — the on-prem sovereignty guarantee). LLM
output maps into OpenRegister's exact entity/confidence shape so the rest of
the pipeline is identical regardless of engine. Because degrading detection
quality without telling the operator is dishonest, any LLM-provider failure
MUST fall back to the default provider **with an explicit warning surfaced in
the report** — never a silent downgrade. Verified at HEAD: OR's `detectWithLLM`
is a stub that silently returns regex, OR's method is a global setting, and
hermiq's `POST /api/graph/run` is admin-only + object-centric — so this change
formalises a Filinq-side provider seam and depends on (does not modify) OR's
engine and a hermiq text→entities agentflow endpoint.

## Requirements

### Requirement: A pluggable detection-provider seam with a default over OR (REQ-DDLED-001)

The app MUST define a `DetectionProviderInterface` whose implementations return
detected entities in OpenRegister's persisted shape (value, type, category,
positions, confidence 0..1, detection method), and MUST ship a
`DefaultDetectionProvider` that delegates to OpenRegister's existing detection
with no behaviour change. The default provider MUST remain the fallback target
and MUST always be a working engine. The seam MUST NOT implement or modify
OpenRegister's own detection engine.

#### Scenario: Default provider reproduces current detection

- GIVEN an organisation left on the default detection provider
- WHEN a document is extracted and detected
- THEN the entities returned are exactly those OpenRegister's existing path produces, with no added or dropped entities
- @e2e exclude equivalence to the existing OR path — covered by PHPUnit (tests/unit/Service/Detection/DefaultDetectionProviderTest.php)

#### Scenario: Providers report a stable id

- GIVEN the default and LLM providers
- WHEN their `id()` is read
- THEN they return `default` and `llm` respectively
- @e2e exclude trivial identity accessor — covered by PHPUnit (tests/unit/Service/Detection/)

### Requirement: Per-organisation provider selection (REQ-DDLED-002)

The app MUST resolve the detection provider per organisation from
`filinq.detection.provider` (default `default`), through a single resolver
that is the only place the provider is chosen. Selecting a provider for one
organisation MUST NOT change another organisation's provider, and MUST NOT
modify OpenRegister's global detection-method setting. The provider-selection
configuration routes MUST be admin-gated and fail closed.

#### Scenario: One organisation opts into the LLM provider

- GIVEN organisation A set to provider `llm` and organisation B left on default
- WHEN a document of each organisation is detected
- THEN organisation A's run uses the LLM provider (or its honest fallback) and organisation B's run uses the default provider
- @e2e tests/e2e/spec-coverage/llm-entity-detection-provider.spec.ts

#### Scenario: Non-admin cannot change the provider config

- GIVEN a non-admin user
- WHEN they call the provider-configuration route
- THEN the response is HTTP 403 and no configuration changes
- @e2e exclude authorization matrix — covered by PHPUnit (tests/unit/Controller/DetectionProviderSettingsControllerTest.php)

### Requirement: Local hermiq LLM provider with no cloud calls (REQ-DDLED-003)

The `LlmDetectionProvider` MUST detect entities by calling the hermiq
agentflow configured at `filinq.detection.hermiq_endpoint`, sending the
document's extracted text and enabled entity types and reading the entities
from the returned agent state. With `filinq.detection.require_local` on
(default true) it MUST refuse any endpoint that is not a loopback or
private-network host. It MUST NOT call any third-party or cloud service.

#### Scenario: A non-local endpoint is refused

- GIVEN `require_local` is true and `hermiq_endpoint` points at a public host
- WHEN the LLM provider is asked whether it is available
- THEN it reports unavailable and no document text is transmitted
- @e2e exclude endpoint-locality guard — covered by PHPUnit (tests/unit/Service/Detection/LlmDetectionProviderTest.php::testNonLocalEndpointRefused)

#### Scenario: An unconfigured or unreachable provider reports unavailable

- GIVEN no hermiq endpoint is configured, or the configured endpoint does not answer
- WHEN the LLM provider is asked whether it is available
- THEN it reports unavailable, so the resolver falls back deterministically
- @e2e exclude availability reasons — covered by PHPUnit (tests/unit/Service/Detection/LlmDetectionProviderTest.php::testUnconfiguredAndRequireLocalOff, ::testConnectionReportsReachability)

#### Scenario: A configured local endpoint detects entities

- GIVEN a reachable local hermiq agentflow returning entities in state
- WHEN a document is detected through the LLM provider
- THEN entities from the agent state are returned and persisted with detection method `llm`
- @e2e exclude requires a live hermiq agentflow — covered by PHPUnit with a faked hermiq client (tests/unit/Service/Detection/LlmDetectionProviderTest.php)

### Requirement: LLM output maps into OpenRegister's entity/confidence shape (REQ-DDLED-004)

The LLM provider MUST map each returned entity into an OpenRegister
`EntityRelationMapper` write: value verbatim; type mapped to an OpenRegister
type constant where recognised, else kept verbatim; positions carried through,
position-less occurrences still stored; confidence clamped to 0..1 (a
configured default when absent); category derived from the type;
`detectionMethod = llm`. Mapped entities MUST match Presidio entities in
shape, so threshold and prohibition logic behave identically.

#### Scenario: Confidence is preserved and clamped

- GIVEN the LLM returns an entity with score 1.4 and another with no score
- WHEN they are mapped
- THEN the first is stored with confidence 1.0 and the second with the configured default confidence
- @e2e exclude pure mapping logic — covered by PHPUnit (tests/unit/Service/Detection/LlmDetectionProviderTest.php::testConfidenceClampAndDefault)

#### Scenario: An unknown LLM type is kept, not dropped

- GIVEN the LLM returns an entity of an unrecognised type
- WHEN it is mapped
- THEN the entity is persisted with its type kept verbatim and category `contextual_data`
- @e2e exclude pure mapping logic — covered by PHPUnit (tests/unit/Service/Detection/LlmDetectionProviderTest.php)

### Requirement: Honest fallback with an explicit report warning (REQ-DDLED-005)

When an organisation is on the LLM provider and that provider is
unavailable, or its call throws, times out or returns malformed output, the
resolver MUST fall back to the `DefaultDetectionProvider` and mark the result
as fallen back, naming the provider used and carrying an explicit warning. The
warning MUST appear in the detection payload and in the anonymisation report
and review. Default-provider results MUST NOT be presented as LLM results.

#### Scenario: LLM unavailable falls back with a visible warning

- GIVEN an organisation on the LLM provider and an unreachable hermiq endpoint
- WHEN a document is detected
- THEN the default provider's entities are returned, the result is marked as fallen back to the default method, and the report shows an explicit warning
- @e2e tests/e2e/spec-coverage/llm-entity-detection-provider.spec.ts

#### Scenario: Malformed LLM output triggers fallback, not a crash

- GIVEN the hermiq agentflow returns output that is not a valid entity list
- WHEN a document is detected through the LLM provider
- THEN detection falls back to the default provider with a warning and no error is raised to the operator
- @e2e exclude fault-injection on the LLM response — covered by PHPUnit (tests/unit/Service/Detection/DetectionProviderResolverTest.php::testMalformedOutputFallsBack)

### Requirement: Provider configuration UI (REQ-DDLED-006)

The app MUST provide an admin-only settings section, registered in the
Manifest-V2 shell (`src/manifest.json` + `registry.js`, never
`src/router/index.js`), to select the provider per organisation and set the
hermiq endpoint, agentflow reference and require-local toggle, with a
connectivity test. Every `NcSelect` MUST carry an `inputLabel`; colours and
spacing MUST use Nextcloud CSS variables or NL Design tokens.

#### Scenario: Admin configures and tests the LLM provider

- GIVEN an admin on the detection-provider settings section
- WHEN they set the provider to LLM, enter a local hermiq endpoint and run the connectivity test
- THEN the selection is saved and the test reports whether the provider is available
- @e2e tests/e2e/spec-coverage/llm-entity-detection-provider.spec.ts
