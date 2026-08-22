---
kind: code
---

# Proposal: inbound-auto-classification

## Why

Every serious document-management competitor auto-classifies inbound
documents and Filinq classifies almost nothing. The market evidence
(theme 11 of the gap research): **Paperless-ngx** ships ML auto-tagging
of document type and correspondent as its headline feature; **BCT Corsa**
sells Intelligent Document Tooling (IDT) classification; **Visma Circle
(Djuma)** advertises auto-registration of inbound post. The intelligence
DB carries the matching user stories verbatim: "Add subject
classification on intake", "Classify document within dossier" (x2),
"Route classified correspondence to the responsible department", "Accept
or override AI-suggested classification", "View dashboard of unclassified
records". Dutch municipal intake (postkamer) is exactly this workload:
every inbound brief/besluit/factuur/rapport must be typed, attributed to
a correspondent, and filed to a dossier — today by hand.

Filinq already has the substrate, verified at HEAD `9cc14407`:

- `MetadataService::enhanceMetadata()` enriches OR objects on
  create/update events (language, keywords, topic, documentType
  standardisation, dates) — the `metadata-enrichment` capability, status
  done.
- `LanguageClassifier` owns the vocabulary/scoring boundary (REQ-META-11):
  stateless keyword classification with a minimum-match threshold —
  exactly the mechanism document-type classification needs, but its
  `TOPIC_KEYWORDS` cover subject domains (legal/financial/medical/
  technical), not Dutch document types.
- The anonymisation backend already runs NER over extracted text
  (PERSON/ORGANIZATION entities via OpenRegister's detection pipeline) —
  the model leg a correspondent extractor needs is already computed and
  stored; nobody reads it for intake.
- `GlAccountSuggestionService` (`suggest()` + `recordBooking()`)
  establishes the app's suggest-then-approve pattern with a corrections
  corpus; `anonymization-review-workbench` (wave 1) establishes the same
  philosophy for entity decisions.

What is missing is the intake layer: no document-type suggestion, no
correspondent suggestion, no dossier routing, and no confirmation surface.
The existing enrichment writes fields *directly* (skip-if-populated) —
acceptable for mechanical facts like language, but classification that
drives filing decisions must be human-confirmed (AI Act human-oversight
posture, mirroring the review workbench: suggest, never silently decide).

## What

- **Document-type classification** of inbound documents into Dutch
  intake types (`brief`, `besluit`, `factuur`, `rapport`, `contract`,
  `formulier`, `overig`) via rule/keyword vocabularies behind a dedicated
  stateless classifier class (same class boundary discipline as
  REQ-META-11), with a confidence score and the classification method
  recorded. Learned/trainable classification is explicitly deferred
  (paperless-ngx-style model training is a later wave); the corrections
  corpus this change records is its future training set.
- **Correspondent extraction**: a suggested correspondent (name + PERSON/
  ORGANIZATION taxonomy) derived from the document's already-detected
  entities (OpenRegister NER — the "backend model" leg) plus
  letterhead-position heuristics.
- **Suggestions, never silent auto-file**: every classification lands as
  a `classificationResult` OR object in status `suggested`; a
  confirmation surface lets a human confirm, correct (the corrected value
  is recorded against the suggestion) or reject. No canonical metadata
  field and no file location changes until a human confirms.
- **Dossier routing as a suggestion**: when a suggested correspondent or
  content matches an existing dossier, the suggestion carries a
  `suggestedDossier` reference; confirming it attaches/moves the document
  via the dossier's existing folder binding. Never automatic.
- **Wired into the existing enrichment path**: classification runs where
  enrichment already runs (OR object events + on-demand API), governed by
  its own admin toggle, and consumes the same extracted text the pipeline
  already produces (including wave-1 OCR-recovered text when present).

## Capabilities

### New Capabilities

- `inbound-auto-classification`: document-type + correspondent
  classification suggestions for inbound documents, the human
  confirmation workflow, suggestion-only dossier routing, and the
  `classificationResult` record with its corrections corpus.

### Modified Capabilities

- `metadata-enrichment`: gains the classification extension requirement —
  document-type/correspondent classification runs inside the enrichment
  pipeline behind the established classifier class boundary, but its
  outputs are suggestion records, never direct field writes (a deliberate
  contrast with the existing skip-if-populated enrichment fields).

## Impact

- **Backend**: new `DocumentTypeClassifier` (stateless vocabularies +
  scoring, sibling of `LanguageClassifier`) and
  `InboundClassificationService` (orchestration: classify, extract
  correspondent, match dossiers, persist suggestions, handle
  confirm/correct/reject); hooks in the existing enrichment path
  (`EnrichmentRunner`/`MetadataService`) and a small
  `ClassificationController` for the confirmation API.
- **Register JSON**: new `classificationResult` schema (additive,
  union-merge).
- **Frontend**: a classification review surface (inbox-style list of
  documents with pending suggestions + a confirmation card on the
  document detail) using standard components.
- **AI Act / GDPR posture**: rule-based + reuse of already-computed NER;
  suggestions require human confirmation (Art. 14-style oversight);
  correspondent names are personal data stored for the intake purpose
  with declared retention; no external services, all local.
- **No new engines**: no model training, no embeddings, no external
  classify APIs — vocabularies + existing NER only in this wave.
