# Tasks: register-i18n

## Task 1: Core Implementation
- [~] Implement service classes — DEFERRED: cross-repo handoff. OpenRegister must ship the `register-i18n` foundation (`openregister/openspec/specs/register-i18n/spec.md`) including the `translatable` field flag, the language-negotiation contract, and the storage shape for tagged values. DocuDesk's service layer is a thin consumer; until OR exposes the API, there is no foundation to delegate to.
- [~] Add API endpoints — DEFERRED with above. OR's `i18n-api-language-negotiation` ships the `Accept-Language` contract that DocuDesk endpoints would respect. Adding a DocuDesk endpoint that bypasses OR would violate ADR-022 (apps consume OR abstractions).
- [~] Add configuration settings — DEFERRED with above. Tenant-default-language settings will live alongside the OR-shipped admin UI.

## Task 2: Testing
- [~] Unit tests — DEFERRED with above. Service-class tests cannot be written until the service classes themselves are scaffolded against the OR foundation.
- [~] Integration tests — DEFERRED with above. Newman + Playwright coverage requires a running OR instance with `register-i18n` enabled.

## Task 3: Documentation
- [~] API documentation — DEFERRED with above. The endpoint shapes derive from OR's language-negotiation contract.
- [~] Admin guide — DEFERRED with above. The admin surface is OR-owned; DocuDesk will cross-link once OR ships its admin guide.

> All seven tasks are P2-gated on OpenRegister shipping `register-i18n` + `i18n-api-language-negotiation` per docudesk-adopt-or-abstractions task 14. Declared in `openspec/manifest.yaml` `consumes` array; this change unblocks once both OR specs land.
