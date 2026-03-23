# Admin Settings

## Problem
Provides configuration management for DocuDesk, including OpenRegister integration setup, GDPR consent period configuration, and metadata enrichment feature toggles. Settings are exposed both via the Nextcloud Admin Settings panel (under a dedicated DocuDesk section) and via a REST API. On application boot, DocuDesk automatically initializes its OpenRegister configuration by importing the register/schema definitions from `docudesk_register.json`.

## Proposed Solution
Implement Admin Settings following the detailed specification. Key requirements include:
- See full spec for detailed requirements

## Scope
This change covers all requirements defined in the admin-settings specification.

## Success Criteria
- Admin opens DocuDesk settings section
- Non-admin cannot access settings
- Settings page renders Vue component
- Settings section registration
- Configure consent register and schema
