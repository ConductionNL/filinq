# Register Content Internationalization

## Purpose
Enable multi-language support for Docudesk's register objects, allowing users to view and manage document templates in their preferred language. Built on OpenRegister's register-i18n foundation (see `openregister/openspec/specs/register-i18n/spec.md`).

## Requirements

### REQ-I18N-001: Language-Tagged Fields
The following Docudesk-specific fields MUST support multi-language content via OpenRegister's `translatable` flag:

**Templates:**
- `title` — display name of the template (e.g., "Beschikking omgevingsvergunning" / "Environmental permit decision")
- `description` — explanation of the template's purpose and when to use it

**Template fields/placeholders:**
- `label` — display label shown to the user when filling in the template field
- `helpText` — guidance text explaining what value to enter in the field

**NOT translatable:** Generated documents themselves are NOT translatable via this mechanism. The output language of a generated document depends on which language version of the template was used to generate it. A template may exist in multiple languages, but each generated document is a single-language artifact.

### REQ-I18N-002: Language Fallback Chain
- MUST follow the Nextcloud user's language preference
- MUST fall back: user language -> app default language -> nl -> en -> first available
- MUST display fallback indicator when showing non-preferred language

### REQ-I18N-003: Frontend Language Switching
- MUST show language selector on detail pages when translated content exists
- MUST preserve current language selection across navigation within the app
- Language switching MUST NOT require page reload

### REQ-I18N-004: API Language Support
- API responses MUST accept `Accept-Language` header
- API responses MUST include `Content-Language` header
- `?lang=nl` query parameter MUST override Accept-Language
- Listing endpoints MUST return content in requested language with fallback

## Current Implementation Status
Not implemented. No multi-language content support exists in Docudesk. All content is stored in a single language (typically Dutch). Template definitions and their field labels are all single-language.

## Standards & References
- OpenRegister register-i18n spec (foundation)
- BCP 47 language tags (nl, en, de, fr, etc.)
- W3C Internationalization best practices
- Nextcloud l10n framework (for UI strings -- separate from register content i18n)
- WCAG 2.1 SC 3.1.1 (Language of Page) and SC 3.1.2 (Language of Parts)

## Specificity Assessment
Depends on OpenRegister's register-i18n being implemented first. App-level work is primarily frontend (language selector, fallback display) and API layer (Accept-Language routing). Docudesk has a small translation surface -- only template metadata and field labels need translation. The key distinction is that generated documents are single-language outputs, not translatable register content.
