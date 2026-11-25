# metadata-enrichment Specification (delta)

## Purpose

Extends the enrichment pipeline with the inbound-classification hook:
document-type and correspondent classification run where enrichment runs
and behind the same classifier class-boundary discipline as REQ-META-11,
but — unlike the existing skip-if-populated enrichment fields — their
outputs are suggestion records that a human confirms, never direct field
writes. See the `inbound-auto-classification` capability for the
suggestion workflow itself.

## ADDED Requirements

### Requirement: Classification extends enrichment as suggestions, not field writes (REQ-DDIAC-006)

The enrichment pipeline MUST invoke inbound classification (REQ-DDIAC-001
and -002) after `MetadataService::enhanceMetadata()` on the same triggers,
gated by `enable_inbound_classification` like the other `enable_*`
toggles. Classification outputs MUST be persisted as `classificationResult`
suggestions and MUST NOT be written onto the enriched object's fields;
the existing direct-write enrichment fields MUST keep their
skip-if-populated semantics.

#### Scenario: Enrichment triggers classification without touching fields

- GIVEN classification enabled and a new inbound document object with text content
- WHEN the ObjectCreatedEvent enrichment runs
- THEN language/keywords/topic are enriched exactly as before
- AND a `classificationResult` suggestion exists for the document
- AND the object's `documentType` was not set by classification
- @e2e tests/e2e/spec-coverage/inbound-classification.spec.ts

#### Scenario: Topic classification is unchanged

- GIVEN a document containing legal-domain vocabulary
- WHEN enrichment runs with classification enabled
- THEN `LanguageClassifier::classifyTopic()` still classifies the topic per REQ-META-03
- AND the document-type suggestion is produced independently by `DocumentTypeClassifier`
- @e2e exclude regression guard on existing classifier boundary; covered by PHPUnit (tests/unit/Service/TextAnalysisServiceTest.php, DocumentTypeClassifierTest.php)

#### Scenario: Classification disabled leaves enrichment untouched

- GIVEN `enable_inbound_classification` off and all other enrichment toggles on
- WHEN enrichment runs
- THEN all existing enrichment outputs behave exactly as at HEAD and no suggestion record is created
- @e2e exclude toggle-isolation contract; covered by PHPUnit (tests/unit/Service/InboundClassificationServiceTest.php)

#### Scenario: The triggers and the classifier boundary
- GIVEN the enrichment pipeline
- WHEN an OpenRegister object event fires or `POST /api/metadata/enrich` is called
- THEN classification runs after `enhanceMetadata()`
- AND its vocabularies and scoring live in the stateless `DocumentTypeClassifier` consumed through dependency injection, while `LanguageClassifier`'s vocabularies, thresholds and REQ-META-03 topic scoring stay unchanged
- AND the direct-write fields (language, keywords, topic, documentType standardisation, dates) keep their skip-if-populated behaviour
- @e2e exclude pipeline wiring; covered by PHPUnit (tests/unit/Service/DocumentTypeClassifierTest.php::testLanguageClassifierIsUntouched, tests/unit/Service/InboundClassificationServiceTest.php::testNoSuggestionEverAutoApplies)

