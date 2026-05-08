# Metadata Enrichment

## Problem
Provides automatic metadata enrichment for documents stored in OpenRegister. When documents are created or updated, DocuDesk detects language, extracts keywords, classifies topics, standardizes document types, and normalizes date fields. Enrichment runs both on-demand via the API and automatically via the OpenRegister event listener. All processing is performed locally using heuristic algorithms -- no external NLP services are required.

## Proposed Solution
Implement Metadata Enrichment following the detailed specification. Key requirements include:
- See full spec for detailed requirements

## Scope
This change covers all requirements defined in the metadata-enrichment specification.

## Success Criteria
- Detect Dutch document
- Detect English document
- Insufficient text for detection
- Skip detection for pre-populated field
- Feature toggle disabled
