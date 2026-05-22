## ADDED Requirements

### Requirement: Dutch-language NLP entity recognition

The system SHALL integrate a Dutch-language Named Entity Recognition (NER) model to detect PERSON, ORG, and LOC entities in document text. When a profile includes entity types and a job runs, the system MUST run the NER model over the document text, produce candidate annotations at the confidence threshold configured in the profile, and suppress candidates that appear in the profile's allowList.

#### Scenario: NLP model detects named entities in Dutch text
- **GIVEN** a profile with `entityTypes: ["PERSON", "ORG"]` and `entityConfidence: 0.85`
- **WHEN** the job processes a Dutch document containing "Jan de Vries, directeur van Gemeente Utrecht, te Rotterdam"
- **THEN** the system MUST create `RedactionAnnotation` entries for "Jan de Vries" (PERSON), "Gemeente Utrecht" (ORG), and "Rotterdam" (LOC)
- **AND** each annotation has `originEntityType` set to the detected type and confidence ≥ 0.85

#### Scenario: Confidence threshold filters low-quality candidates
- **GIVEN** a profile with `entityConfidence: 0.90`
- **WHEN** the NER model identifies a potential entity with confidence 0.87
- **THEN** the annotation is not created (confidence below threshold)
- **AND** higher-confidence detections (≥0.90) proceed

#### Scenario: Allow list suppresses known entities
- **GIVEN** a profile with `allowList: ["Gemeente Utrecht", "CIBG", "RDW"]`
- **WHEN** the job detects "Gemeente Utrecht" as an ORG entity
- **THEN** no annotation is created (entity is in allowList)
- **AND** other entities are annotated normally

#### Scenario: NLP recognises multiple entity types per profile setting
- **GIVEN** a profile with `entityTypes: ["PERSON", "ORG"]` (not LOC)
- **WHEN** the NER model processes text containing "Amsterdam" (LOC)
- **THEN** LOC entities are not annotated (type not enabled in profile)

#### Scenario: Deny list forces redaction of specific entities
- **GIVEN** a profile with `denyList: ["Particuliere Bedrijven B.V."]`
- **WHEN** the job processes text containing this organisation name
- **THEN** an annotation is created even if confidence is below threshold or entity is not detected by NER
- **AND** the annotation's `originEntityType` is `custom_deny_list`

### Requirement: NLP integration with async job processing

NLP entity recognition runs as part of the async job pipeline. The job SHALL track NLP processing separately from pattern matching, with a status and progress indicator. Failed NLP runs SHALL not block pattern-matching results.

#### Scenario: NLP processing is optional and non-blocking
- **GIVEN** a profile with NLP enabled but a NER service is unavailable
- **WHEN** the job runs
- **THEN** pattern matching completes and produces annotations
- **AND** the job status transitions to `completed` with warnings (not failed)
- **AND** the job summary notes "NLP skipped due to service unavailability"

#### Scenario: NLP results are stored separately from patterns
- **WHEN** a job completes
- **THEN** annotations from pattern matching and from NLP are both stored with distinct `originPattern` / `originEntityType` values
- **AND** both sets of annotations are visible in the review UI and export
