# Retrofit — openregister-bridge

Mints a new `openregister-bridge` capability describing the observed behavior of 3 methods in `OpenRegisterResolver`. The methods are consumed by both `TemplateService` and `TemplateVersionService` and surface a config-translation contract that does not naturally fit inside any one consumer's spec.

## Affected code units

- `lib/Service/OpenRegisterResolver.php` — `getRegisterAndSchema`, `getVersionRegisterAndSchema`, `validateNamespace`

## Approach

- Read `OpenRegisterResolver` and its two consumers (`TemplateService`, `TemplateVersionService`) to confirm that the resolver owns the contract for translating IAppConfig keys into register/schema slugs and for the namespace-validation regex.
- Mint a new capability (`openregister-bridge`) rather than extending `template-management` — the resolver is reused beyond the template path (it also gates the version schema), and future register-backed features (document register, signing register) will likely consume the same translator pattern. Keeping it standalone surfaces the seam.
- Draft a single REQ describing the three observable behaviors as a cohesive contract: resolve template register/schema, resolve version register/schema, validate namespace.
- Surface in Notes: the configuration keys (`template_register`, `template_schema`, `templateVersion_register`, `templateVersion_schema`) are exposed via `SettingsService::getAllSettings()` — coupling that the resolver inherits but does not re-document.

Source: `openspec/coverage-report.json` generated 2026-05-24. See [retrofit playbook](../../../.github/docs/claude/retrofit.md).
