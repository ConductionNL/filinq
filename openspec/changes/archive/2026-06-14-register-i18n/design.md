---
status: pr-created
---

# Design: register-i18n

## Architecture Overview

DocuDesk's register-i18n implementation builds on OpenRegister's existing i18n infrastructure:
- `OCA\OpenRegister\Middleware\LanguageMiddleware` — reads `Accept-Language` header and populates `LanguageService`
- `OCA\OpenRegister\Service\Object\TranslationHandler` — resolves/saves translatable property values based on the `translatable: true` schema flag
- `OCA\OpenRegister\Service\LanguageService` — request-scoped language state

## What DocuDesk adds

### Schema layer (REQ-I18N-01, REQ-I18N-05)
`lib/Settings/docudesk_register.json` — `template` schema v1.1.0:
- `name`: `"translatable": true` — template title in nl + en
- `description`: `"translatable": true` — template description in nl + en
- `content` (HTML/Twig): NOT translatable per REQ-I18N-041 — generate separate language variants
- Templates register upgraded to v2.1.0 with `defaultLanguage: "nl"` and `languages: ["nl","en"]`
- Consent, dossier, correspondence schemas: NOT translatable per REQ-I18N-042

### Service layer (REQ-I18N-02, REQ-I18N-06)
`lib/Service/TemplateLanguageService.php`:
- `resolveUserLanguage(IUser|null)` — reads Nextcloud user lang (`core.lang` config key), strips region subtag, falls back to `nl`
- `getAvailableLanguages(mixed)` — extracts BCP 47 keys from OR's language-keyed field format
- `resolveFieldValue(mixed, string)` — implements REQ-I18N-011 fallback chain (preferred → nl → en → first available)
- `buildAcceptLanguageHeader(string)` — builds quality-weighted RFC 9110 header for OR's LanguageMiddleware

### Configuration (REQ-I18N-06)
`lib/Service/SettingsService.php` extended with `template_default_language` IAppConfig key (default: `nl`)

### Frontend (REQ-I18N-03, REQ-I18N-04)
`src/store/modules/template.js`:
- `selectedLanguage` state — user's explicit language choice (persisted in Pinia store across navigation)
- `setLanguage(lang)` action
- `buildLanguageHeaders()` — returns `{Accept-Language: "nl, en;q=0.9"}` when language is set
- `recordResponseLanguage(response)` — reads `Content-Language` + `X-Content-Language-Fallback` response headers
- All fetch actions pass the language header so OR's LanguageMiddleware resolves content correctly

`src/components/LanguageSelector.vue`:
- Dropdown listing available content languages for the current template
- Shows fallback badge when `X-Content-Language-Fallback: true` response header is set

`src/views/templates/TemplateDetail.vue`:
- Integrates `LanguageSelector` in the header actions area
- `availableLanguages` computed — detects language-keyed structure in `name` field
- `onLanguageChange(lang)` — calls `templateStore.setLanguage()` then re-fetches the template in the new language

### l10n (ADR-007)
Added 15 new strings to `l10n/en.json` and `l10n/nl.json` (zero gap between files).

## API language support (REQ-I18N-04)
The existing `TemplatesController` endpoints (`/api/templates`, `/api/templates/:id`) already support
`Accept-Language` via OR's LanguageMiddleware — no new endpoints are needed. The frontend
passes the header explicitly when the user has selected a language.

## Declarative-vs-imperative decision
Schema-level i18n is handled declaratively via `translatable: true` in `docudesk_register.json`.
`TemplateLanguageService` is legitimate domain service code: it implements the app-specific
fallback chain and user-language resolution that cannot be expressed as schema metadata.

## Reuse Analysis
- `OCA\OpenRegister\Middleware\LanguageMiddleware` — reused for Accept-Language parsing
- `OCA\OpenRegister\Service\Object\TranslationHandler` — reused for translatable field storage/retrieval
- No custom translation storage, no custom DB tables (ADR-001 compliance)
