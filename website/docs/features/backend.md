## Services

DocuDesk's backend is built around several key services:

### ReportingService
Handles document analysis and report generation:
- Text extraction from various file formats
- Presidio integration for entity detection
- Risk assessment and scoring
- Report storage and management
- Enhanced entity processing with deduplication

### EntityService
Manages entity objects across documents:
- Entity object creation and retrieval
- Statistics tracking (occurrence count, confidence scores)
- Entity deduplication by text content
- Integration with reporting for consistent entity handling

### ExtractionService

### AnonymizationService
Handles document anonymization with enhanced entity processing:
- Document anonymization using Presidio integration
- Word document structure-aware anonymization
- Anonymization results stored directly on report objects
- Entity-level anonymization control
- File hash-based caching for performance 