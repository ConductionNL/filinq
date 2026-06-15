---
status: accepted
source: market-intelligence
clusters: [45, 55]
total_tenders: 245
total_requirements: 555
---

# Letter/Correspondence Generation

## Summary

Add a dedicated letter and correspondence generation workflow to DocuDesk, enabling government users to generate letters, beschikkingen, and other correspondence from templates with merge fields populated from case/citizen data. This extends the existing template-management and pdf-generation capabilities with a higher-level correspondence-specific workflow including batch generation for multiple recipients.

## Demand Evidence

### Cluster 45: Letter/correspondence generation
- **129 tenders**, **257 requirements** (primarily Dutch government via TenderNed)
- Country distribution: TenderNed 155 reqs, Belgium 15 reqs
- Municipalities explicitly require letter generation from case data with merge fields

### Cluster 55: Document creation/generation
- **116 tenders**, **298 requirements**
- Country distribution: TenderNed 131 reqs, Belgium 2 reqs

### Sample Requirements from Tenders
- **Gemeente Aa en Hunze**: "De Oplossing beschikt over documentcreatiefunctionaliteit om documenten op basis van sjablonen te creeren."
- **Gemeente Zuidplas**: "De Oplossing beschikt over documentcreatiefunctionaliteit om documenten en e-mails op basis van sjablonen te creeren."
- **Gemeente Molenlanden**: "Als de Oplossing een eigen documentcreatiefunctionaliteit heeft, is het mogelijk om centraal emailsjablonen inclusief voet- en kopteksten te configureren."
- **Gemeente Hilversum**: "De Oplossing moet kunnen koppelen met Xential via de ESB van de Opdrachtgever. De Opdrachtgever ziet een documentcreatietool als een belangrijk middel."
- **Ministerie van VWS**: "Als gebruiker, wil ik emailtemplates kunnen creeren conform CIBG-huisstijl."

## What Docudesk Already Does

- **Template Management** (implemented): CRUD for Twig/HTML templates stored as OpenRegister objects, with namespace scoping, format/orientation config
- **PDF Generation** (implemented): Stateless PDF rendering via mPDF with Twig sandbox, page config options, injectable via DI
- **Document Creatie Sjablonen** (planned spec): Data resolution from OpenRegister, template merge execution, output format support (PDF/ODF/HTML), huisstijl enforcement -- this spec covers the foundation but not correspondence-specific workflows

## Scope

### In Scope
1. **Correspondence workflow API** -- dedicated endpoint for generating letters/correspondence with pre-configured defaults (margins, huisstijl, headers/footers)
2. **Merge field resolution** -- populate templates with data from OpenRegister objects (zaak, persoon, adres) and external sources via OpenConnector (e.g., BRP)
3. **Batch generation** -- generate the same letter for multiple recipients in one request (e.g., send beschikking to all aanvragers in a batch)
4. **Multiple output formats** -- PDF (default), DOCX (via LibreOffice conversion), and ODF for editable output
5. **Email template support** -- generate email body content from templates with the same merge logic
6. **Correspondence register** -- log all generated correspondence with metadata (template used, recipient, date, case reference) in the document register

### Out of Scope
- Actual email sending (handled by n8n or notification service)
- Physical mail dispatch (handled by external print/mail services)
- Template creation/editing UI (covered by advanced-template-management change)

## Relation to Existing Specs

- Builds on **document-creatie-sjablonen** spec (data resolution, merge execution)
- Uses **template-management** spec (template CRUD)
- Uses **pdf-generation** spec (PDF rendering)
- Integrates with **document-register** spec (audit trail)

## Acceptance Criteria

1. GIVEN a template with merge fields and a case object UUID, WHEN the correspondence API is called, THEN a letter is generated with all merge fields populated from the case data
2. GIVEN a batch request with 50 recipient UUIDs, WHEN the batch correspondence endpoint is called, THEN 50 individual letters are generated, each with recipient-specific data
3. GIVEN a correspondence template, WHEN output format "docx" is requested, THEN an editable DOCX file is returned
4. GIVEN a generated letter, WHEN it is stored, THEN a correspondence register entry is created with template ID, recipient, date, and case reference
5. GIVEN a template references huisstijl configuration, WHEN the letter is generated, THEN the organization's logo, header, footer, and styling are applied automatically

## Risks and Dependencies

- Depends on **document-creatie-sjablonen** spec being implemented first (data resolution layer)
- DOCX output requires LibreOffice or equivalent server-side conversion
- Batch generation needs queue/background job support for large volumes
- OpenConnector integration needed for external data sources (BRP, BAG)
