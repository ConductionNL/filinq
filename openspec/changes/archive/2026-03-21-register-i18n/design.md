# Design: Register Content Internationalization

## Status: Proposed (Not Yet Implemented)

## Architecture (Planned)

### Approach
- Built on OpenRegister's register-i18n foundation
- Multi-language support for template fields via `translatable` flag
- Generated documents are single-language artifacts (not translatable)

### Translatable Fields (Planned)
- Template `title`: e.g., "Beschikking" / "Decision"
- Template `description`: Full description in multiple languages
- Field labels: e.g., "Naam aanvrager" / "Applicant name"
- Help text: e.g., "Vul de volledige naam in" / "Enter the full name"

### Language Resolution
- Use user's Nextcloud language preference
- Fall back to default language when translation not available
- Store translations in OpenRegister's i18n format

## ADR Compliance
- ADR-001: All translations stored via OpenRegister
- ADR-005: Dutch and English as minimum languages
