## Why

Government organizations handling WOO disclosure requests need to anonymize dozens or hundreds of documents in a single batch. The current single-file pipeline forces repeated manual cycles.

## What Changes

- Batch file selection and processing
- Entity review step with WOO profiles
- Batch progress tracking
- Batch anonymization endpoint
- CSV audit report generation
- Confidence threshold filtering

## Capabilities

### New Capabilities
- batch-anonymization: Batch file processing pipeline
- anonymization-entity-review: Interactive entity review/selection UI

### Modified Capabilities
- anonymization: Extended with excludeTypes, minConfidence, riskLevel

## Impact

- Backend: New services and controller for batch operations
- Frontend: New batch view, entity review table, batch store
- API: New batch endpoints under /api/anonymization/batch/
