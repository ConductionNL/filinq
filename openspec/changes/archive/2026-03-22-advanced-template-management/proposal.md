---
status: proposed
source: market-intelligence
clusters: [73]
total_tenders: 96
total_requirements: 240
---

# Advanced Template Management

## Summary

Extend DocuDesk's existing template management with versioning, categories, search, a WYSIWYG template editor, and conditional section support. Current template management provides basic CRUD for Twig/HTML templates; this change adds the lifecycle and editing capabilities that government organizations require for managing large template collections.

## Demand Evidence

### Cluster 73: Document templates
- **96 tenders**, **240 requirements** (primarily Dutch government via TenderNed)
- Country distribution: TenderNed 103 reqs, Belgium 3 reqs

### Sample Requirements from Tenders
- **Gemeente Den Helder**: "De mogelijkheid om een vorige versie van het sjabloon terug zetten." (template version rollback)
- **Rijkswaterstaat**: "De Opdrachtgever streeft naar uniformiteit van documentatie, verslaglegging, rapportages, plannen e.d. door het toepassen van templates/documentsjablonen."
- **Gemeente Meppel**: "De gemeente Meppel beschikt niet over een externe documentcreatietool. Hierdoor ziet de gemeente Meppel de interne documentcreatietool als een belangrijk middel om de gebruiker te ondersteunen bij het creeren."
- **Gemeente Medemblik**: "De interne documentgenerator is in staat om alle in de Oplossing gebruikte kenmerken toe te passen in een sjabloon."
- **Ministerie van VWS**: "Als gebruiker, wil ik emailtemplates kunnen creeren conform CIBG-huisstijl."

## What Docudesk Already Does

- **Template Management** (implemented): CRUD API for templates (`GET/POST/PUT/DELETE /api/templates`), namespace scoping, format and orientation settings, OpenRegister storage, search support
- **Template Renderer** (implemented): `TemplateRenderer` service and `TemplateRequestHandler` for rendering templates with data
- **PDF Generation** (implemented): Twig sandbox with security policy for safe template rendering

### What Is Missing
- No template versioning (overwrite = permanent)
- No template categories or tagging beyond namespace
- No visual template editor (templates are raw HTML/Twig)
- No conditional section management UI
- No template preview with sample data

## Scope

### In Scope
1. **Template versioning** -- store version history for each template; ability to view, compare, and rollback to previous versions
2. **Template categories and tags** -- categorize templates (e.g., "beschikkingen", "brieven", "notities") with tags for filtering and search
3. **WYSIWYG template editor** -- browser-based rich text editor (e.g., TipTap/ProseMirror) that generates Twig-compatible HTML, with merge field insertion via UI
4. **Conditional sections** -- UI for defining conditional blocks (show/hide sections based on data values) without requiring Twig syntax knowledge
5. **Template preview** -- render a template with sample/test data to preview output before publishing
6. **Template duplication** -- clone an existing template as starting point for a new one
7. **Template locking** -- prevent concurrent edits; lock template while being edited

### Out of Scope
- Template sharing across Nextcloud instances (federation)
- Template marketplace/store
- AI-assisted template generation

## Acceptance Criteria

1. GIVEN a template that has been edited 5 times, WHEN the version history is viewed, THEN all 5 versions are listed with timestamp and editor identity, AND any version can be restored
2. GIVEN 50 templates across 5 categories, WHEN filtering by category "beschikkingen", THEN only templates in that category are returned
3. GIVEN a non-technical user, WHEN they open the template editor, THEN they can compose a letter with formatting (bold, italic, tables, lists) without writing HTML or Twig code
4. GIVEN a template with conditional sections, WHEN the user defines a condition "show if zaaktype == omgevingsvergunning", THEN the section only appears in output when the condition is met
5. GIVEN a template, WHEN the user clicks "preview", THEN the template is rendered with sample data and shown in-browser before saving
6. GIVEN a template being edited by user A, WHEN user B tries to edit the same template, THEN user B sees a lock indicator and cannot overwrite user A's changes

## Risks and Dependencies

- WYSIWYG editor must output clean Twig-compatible HTML (complex integration)
- Version storage increases OpenRegister object size; may need separate version objects
- Conditional section UI needs careful UX design to remain accessible to non-technical users
- Template locking requires WebSocket or polling mechanism for real-time lock status
