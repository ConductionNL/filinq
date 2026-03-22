# PDF Generation

## Problem
Provides a shared, reusable PDF rendering service that any co-installed Nextcloud app can call. Accepts a Twig template string and data context, renders HTML, converts to PDF via mPDF, and returns the binary content. The service is stateless -- callers provide template content directly. Includes a Twig sandbox with strict security policy and an HTTP API endpoint for PDF generation.

## Proposed Solution
Implement PDF Generation following the detailed specification. Key requirements include:
- See full spec for detailed requirements

## Scope
This change covers all requirements defined in the pdf-generation specification.

## Success Criteria
- Render PDF from template and data
- Static HTML template without data
- Invalid Twig syntax
- Service is injectable via DI
- A4 portrait with default margins
