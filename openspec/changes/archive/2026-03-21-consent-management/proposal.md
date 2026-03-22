# Consent Management

## Problem
Provides GDPR-compliant publication consent tracking for entities (persons and organizations) detected in documents. When a document is destined for publication under the Wet Open Overheid (WOO), affected entities must be notified and given an objection period (minimum 4 weeks per WOO). This feature manages the full consent lifecycle: creation, notification tracking, objection handling, and publication decision-making. All consent records are stored as OpenRegister objects using the PublicationConsent schema.

## Proposed Solution
Implement Consent Management following the detailed specification. Key requirements include:
- See full spec for detailed requirements

## Scope
This change covers all requirements defined in the consent-management specification.

## Success Criteria
- Create consent for a detected person
- Create consent with extra data
- Custom objection period
- Update consent status to consent_given
- Record an objection
