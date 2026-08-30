# pdf-conversion Specification (delta)

---
status: proposed
---

## Purpose

Extend the conversion cascade with a non-throwing capability introspection
surface: the per-backend `{name, available, supports}` report that today only
exists inside `ConversionFailedException` becomes queryable without failing a
conversion, so the multi-format-output capability matrix (and any other
consumer) can ask "what can this instance produce?" up front. Existing
cascade behaviour is unchanged; this delta only ADDs.

## ADDED Requirements

### Requirement: The cascade exposes non-throwing capability introspection (REQ-DDMFO-005)

`PdfConversionService` MUST expose a public `getCapabilities(): array` that
returns, without performing or failing any conversion, one entry per
configured backend in cascade order with at least `name` (the backend's
`name()`), `available` (the backend's live `isAvailable()` result), and
`supports` (the input types/extensions the backend can handle) — reusing the
exact report shape already defined for the `ConversionFailedException`
payload, so consumers and test fixtures share one structure. The method MUST
respect the tenant configuration for backend availability and order (a
disabled backend appears as unavailable with its reason, or per the same
visibility rules as the exception report), MUST NOT mutate any state, and
MUST NOT throw when a backend probe fails — a probe failure is reported as
`available: false` with a reason.

#### Scenario: Capability report matches the exception report shape

- GIVEN a configured cascade with at least one available and one unavailable backend
- WHEN `getCapabilities()` is called
- THEN it returns entries in cascade order with `name`, `available`, and `supports` per backend
- AND the entry structure equals the per-backend structure of a `ConversionFailedException` payload
- @e2e exclude pure backend introspection with no UI surface — covered by PHPUnit (tests/unit/Service/PdfConversionServiceTest.php::testGetCapabilitiesShape)

#### Scenario: A failing backend probe degrades to unavailable, not an exception

- GIVEN a backend whose availability probe throws
- WHEN `getCapabilities()` is called
- THEN the method returns normally
- AND that backend is reported `available: false` with a reason
- @e2e exclude fault-injection on a backend probe; covered by PHPUnit (tests/unit/Service/PdfConversionServiceTest.php::testProbeFailureDegrades)
