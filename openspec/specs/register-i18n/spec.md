---
status: done
---

# register-i18n Specification

## Purpose
Enables multi-language content for Filinq-specific register fields using OpenRegister's `translatable` flag, so template titles, descriptions, field labels, and help text can be stored and displayed in the viewing user's language. Generated documents are explicitly single-language artifacts, not translatable register objects. This lets Filinq present its templates and forms in Dutch or English while keeping each produced document in one language.
## Requirements
### Requirement: REQ-I18N-01 Language-Tagged Fields (Priority: Must)

Filinq-specific fields MUST support multi-language content via OpenRegister's `translatable` flag.

#### Scenario: Template title in Dutch and English
- GIVEN a template with title "Beschikking omgevingsvergunning"
- AND a Dutch and English translation are stored
- WHEN a Dutch user views the template
- THEN the Dutch title is displayed
- AND an English user sees "Environmental permit decision"

#### Scenario: Template description translation
- GIVEN a template with a Dutch description
- AND an English translation exists
- WHEN the user's language is English
- THEN the English description is shown

#### Scenario: Field label translation
- GIVEN a template with placeholder field label "Naam aanvrager"
- AND English translation "Applicant name" exists
- WHEN the user fills in the template in English
- THEN the field label shows "Applicant name"

#### Scenario: Help text translation
- GIVEN a template field with helpText "Vul de volledige naam in"
- AND English translation "Enter the full name" exists
- WHEN the user views the field help text
- THEN the translated help text is shown

#### Scenario: Generated documents are NOT translatable
- GIVEN a template exists in Dutch and English
- WHEN a document is generated from the Dutch version
- THEN the output document is in Dutch only
- AND it is a single-language artifact, not a translatable register object

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| I18N-001 | Template `title` supports multi-language content | MUST | Planned |
| I18N-002 | Template `description` supports multi-language content | MUST | Planned |
| I18N-003 | Template field `label` supports multi-language content | MUST | Planned |
| I18N-004 | Template field `helpText` supports multi-language content | MUST | Planned |
| I18N-005 | Generated documents are single-language artifacts (NOT translatable) | MUST | Planned |

### Requirement: REQ-I18N-02 Language Fallback Chain (Priority: Must)

The system MUST follow Nextcloud user language preference with a defined fallback chain.

#### Scenario: User language available
- GIVEN a user with language preference "en"
- AND the template has an English translation
- WHEN the template is displayed
- THEN the English version is shown

#### Scenario: User language not available, fallback to nl
- GIVEN a user with language preference "de" (German)
- AND the template has no German translation but has Dutch
- WHEN the template is displayed
- THEN the Dutch version is shown (fallback: nl)
- AND a fallback indicator is displayed

#### Scenario: Full fallback chain
- GIVEN a user with language preference "fr"
- AND the template has no French, Dutch, or English translations
- AND the only available translation is Spanish
- WHEN the template is displayed
- THEN the Spanish version is shown (first available)
- AND a fallback indicator shows the displayed language

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| I18N-010 | Follow Nextcloud user language preference | MUST | Planned |
| I18N-011 | Fallback chain: user language -> app default -> nl -> en -> first available | MUST | Planned |
| I18N-012 | Display fallback indicator when showing non-preferred language | MUST | Planned |

### Requirement: REQ-I18N-03 Frontend Language Switching (Priority: Must)

Users MUST be able to switch between available translations on detail pages without page reload.

#### Scenario: Language selector on detail page
- GIVEN a template with Dutch and English translations
- WHEN the user opens the template detail page
- THEN a language selector dropdown appears
- AND Dutch and English are listed as options

#### Scenario: Switch language without reload
- GIVEN a template detail page is open in Dutch
- WHEN the user selects English from the language selector
- THEN the template content updates to English immediately
- AND no page reload occurs

#### Scenario: Language selection persists across navigation
- GIVEN the user selects English on a template detail page
- WHEN they navigate to another template
- THEN the English language selection is preserved
- AND the new template is shown in English (if available)

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| I18N-020 | Language selector on detail pages when translations exist | MUST | Planned |
| I18N-021 | Preserve language selection across navigation | MUST | Planned |
| I18N-022 | Language switching without page reload | MUST | Planned |

### Requirement: REQ-I18N-04 API Language Support (Priority: Must)

API responses MUST respect language preferences via headers and query parameters.

#### Scenario: Accept-Language header
- GIVEN an API request with `Accept-Language: en`
- WHEN GET /api/templates is called
- THEN template content is returned in English (if available)
- AND the response includes `Content-Language: en` header

#### Scenario: Query parameter override
- GIVEN an API request with `Accept-Language: nl` and `?lang=en`
- WHEN the API processes the request
- THEN the `?lang=en` parameter takes precedence
- AND English content is returned

#### Scenario: Listing endpoint language filtering
- GIVEN 5 templates with Dutch content and 3 with English translations
- WHEN GET /api/templates?lang=en is called
- THEN all 5 templates are returned
- AND 3 show English content, 2 fall back to Dutch with fallback indicator

#### Scenario: No Accept-Language header
- GIVEN an API request with no language preference
- WHEN the request is processed
- THEN the default language (nl) is used

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| I18N-030 | Accept `Accept-Language` header on API requests | MUST | Planned |
| I18N-031 | Include `Content-Language` header in API responses | MUST | Planned |
| I18N-032 | `?lang=` query parameter overrides Accept-Language | MUST | Planned |
| I18N-033 | Listing endpoints return content in requested language with fallback | MUST | Planned |

### Requirement: REQ-I18N-05 Translation Surface Definition (Priority: Must)

Filinq MUST have a small, well-defined translation surface limited to template metadata and field labels.

#### Scenario: Identify translatable fields
- GIVEN the Filinq data model
- WHEN the translatable surface is defined
- THEN only template title, description, field label, and helpText are translatable
- AND template content (HTML/Twig) is NOT translatable via this mechanism
- AND consent records are NOT translatable
- AND `filinq` register report data is NOT translatable

#### Scenario: Template content language variants
- GIVEN a template needs to generate documents in Dutch and English
- WHEN multi-language support is implemented
- THEN two separate templates are created (one per language)
- AND each template has its own content in the target language
- AND the template metadata (title, description) is translated via i18n

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| I18N-040 | Translatable fields limited to template metadata and field labels | MUST | Planned |
| I18N-041 | Template content (HTML/Twig) NOT translatable via register-i18n | MUST | Planned |
| I18N-042 | Consent records and report data NOT translatable | MUST | Planned |

### Requirement: REQ-I18N-06 Minimum Language Support (Priority: Must)

All apps MUST support Dutch and English as minimum languages.

#### Scenario: Dutch as primary language
- GIVEN Filinq is deployed in a Dutch municipality
- WHEN all templates are created
- THEN Dutch is the primary language for all content
- AND all UI strings are available in Dutch via Nextcloud l10n

#### Scenario: English as secondary language
- GIVEN an English-speaking user accesses Filinq
- WHEN template metadata is displayed
- THEN English translations are available for template titles and descriptions
- AND UI strings are available in English via Nextcloud l10n

#### Scenario: Distinction between register-i18n and l10n
- GIVEN Filinq uses both Nextcloud l10n and register-i18n
- WHEN the systems are compared
- THEN l10n handles UI strings (buttons, labels, error messages) via gettext
- AND register-i18n handles register object content (template titles, descriptions) via OpenRegister

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| I18N-050 | Dutch (nl) as primary/default language | MUST | Planned |
| I18N-051 | English (en) as minimum secondary language | MUST | Planned |
| I18N-052 | Register-i18n is separate from Nextcloud l10n | MUST | Planned |

