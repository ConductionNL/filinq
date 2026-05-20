## Context

DocuDesk already provides PDF generation via `PdfService` and template management via `TemplateService` (introduced in `pdf-template-service`). However, there is no way to preview rendered output in-browser before committing to a PDF download. Municipal employees working with document workflows need to verify layout, typography, and data substitution before archiving or printing official correspondence, vergunningen, and besluiten.

### Current Rendering Flow (before this change)

1. Caller provides Twig template content and data context to `PdfService`
2. `PdfService` renders Twig → HTML → mPDF → PDF binary
3. Caller receives binary via `DataDownloadResponse`

There is no intermediate step to inspect the rendered HTML; the user sees the final PDF only after download.

### Template Data Model (OpenRegister — no new entities)

Templates are stored as OpenRegister objects via `TemplateService`. The schema (defined in `pdf-template-service`) has these fields relevant to print preview:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| id | string (UUID) | Yes | OpenRegister object UUID |
| name | string | Yes | Human-readable template name (used as document title) |
| content | string | Yes | Twig/HTML template content |
| namespace | string | Yes | App identifier that owns the template |
| format | string | No | Default page format (A4, A3, Letter) |
| orientation | string | No | Default orientation (P/L) |

No new entities are introduced by this change.

## Goals / Non-Goals

**Goals:**
- Provide `POST /api/print/preview` that returns rendered HTML with print-optimized CSS injected
- Provide `POST /api/print/pdf-a` that returns a PDF/A-3b compliant download (compliance always enforced)
- Support both `templateId` (stored template lookup) and inline `template` content with a `data` context
- Return a 400 JSON error when neither `templateId` nor `template` is provided
- Expose `PrintPreview.vue` with sandboxed iframe, Print button, and Download PDF/A button
- Route at `/print-preview/:templateId?` for deep linking from other views
- Use NL Design System CSS variables for theming compatibility with `nldesign` app

**Non-Goals:**
- Template editing inside the preview component
- Unauthenticated / public preview access
- Non-PDF/A output from the `/api/print/pdf-a` endpoint (always PDF/A-3b)
- Server-side caching of rendered previews
- Supporting PDF formats other than those already available in PdfService (A4/A3/Letter)

## Decisions

### 1. JSON response with rendered HTML, not a streaming HTML endpoint

**Decision**: `POST /api/print/preview` returns `{"html": "...", "title": "..."}` as JSON. `PrintPreview.vue` injects this via iframe `srcdoc`.

**Rationale**: `srcdoc` is safer than serving raw HTML at a separate URL — no additional authenticated GET route to secure, no bookmarkable URL that bypasses auth context. The component controls the iframe environment entirely and can trigger `contentWindow.print()` without cross-origin restrictions.

**Alternative considered**: Serving rendered HTML at a dedicated URL loaded via iframe `src` — rejected because it requires a separate authentication-aware GET route and creates a shareable URL that leaks document content.

### 2. Print-optimized CSS injected server-side

**Decision**: The `preview()` controller wraps rendered HTML in a full `<!DOCTYPE html>` document and injects `@page` CSS, `page-break-inside: avoid`, margin normalization, and navigation element hiding before returning.

**Rationale**: Injection on the server ensures print styles are always present regardless of which consumer calls the API. Consumer apps do not need to manage print stylesheets. The injected CSS is intentionally minimal and does not conflict with template-defined styles.

**Injected CSS includes**:
- `@page { size: A4; margin: 20mm; }`
- `body { margin: 0; font-family: sans-serif; }`
- `nav, header, footer, .no-print { display: none !important; }`
- `* { page-break-inside: avoid; }`

### 3. PDF/A-3b compliance enforced unconditionally

**Decision**: `downloadPdfA()` always passes `pdfa: true` to `PdfService` regardless of the request body. No caller option can override this.

**Rationale**: Dutch archival standards (NEN-ISO 14721, GEMMA dossiervorming) require PDF/A for document retention. The endpoint name `pdf-a` makes the contract explicit. Allowing opt-out creates a path to non-compliant archives.

### 4. Template resolution via injected TemplateService

**Decision**: When `templateId` is provided, `PrintController` resolves it via `TemplateService` (DI-injected). The document title in the response uses the template's `name` field. Inline `template` content uses title `"document"`.

**Rationale**: Consistent with the existing controller pattern in DocuDesk. DI injection avoids HTTP overhead for same-server calls.

### 5. Vue component uses `@NoAdminRequired @NoCSRFRequired` annotations

**Decision**: Both endpoints annotate `@NoAdminRequired @NoCSRFRequired`. The component calls them via `@nextcloud/axios` (which attaches the CSRF token automatically) from an authenticated session.

**Rationale**: `@NoCSRFRequired` is necessary for API clients and inter-app calls that don't carry Nextcloud's CSRF token. `@NoAdminRequired` makes the preview accessible to all authenticated users, not just admins — municipal employees need this feature in daily document workflows.

## Seed Data

### Templates (Dutch values, for testing print preview)

```json
[
  {
    "id": "bb000001-0001-0001-0001-000000000001",
    "name": "Besluit op bezwaar",
    "description": "Standaard sjabloon voor bezwaarbesluiten gemeente",
    "content": "<!DOCTYPE html><html><body><h1>{{ titel }}</h1><p>Geachte {{ aanhef }},</p><p>{{ inhoud }}</p><p>Met vriendelijke groet,<br>{{ gemeente }}</p></body></html>",
    "namespace": "docudesk",
    "format": "A4",
    "orientation": "P"
  },
  {
    "id": "bb000002-0002-0002-0002-000000000002",
    "name": "Omgevingsvergunning",
    "description": "Sjabloon voor omgevingsvergunningsbeschikkingen",
    "content": "<!DOCTYPE html><html><body><h1>Omgevingsvergunning</h1><p>Zaaknummer: {{ zaaknummer }}</p><p>Aanvrager: {{ naam }}</p><p>{{ beschrijving }}</p></body></html>",
    "namespace": "docudesk",
    "format": "A4",
    "orientation": "P"
  },
  {
    "id": "bb000003-0003-0003-0003-000000000003",
    "name": "Informatiebrief burger",
    "description": "Informele informatieve brief aan burger",
    "content": "<!DOCTYPE html><html><body><h1>Informatiebrief</h1><p>Geachte {{ naam }},</p><p>{{ bericht }}</p><p>Datum: {{ datum }}</p></body></html>",
    "namespace": "docudesk",
    "format": "A4",
    "orientation": "P"
  },
  {
    "id": "bb000004-0004-0004-0004-000000000004",
    "name": "Subsidieaanvraagbevestiging",
    "description": "Bevestiging ontvangst subsidieaanvraag",
    "content": "<!DOCTYPE html><html><body><h1>Bevestiging ontvangst</h1><p>Geachte {{ aanhef }} {{ achternaam }},</p><p>Wij bevestigen de ontvangst van uw subsidieaanvraag met kenmerk {{ kenmerk }}.</p></body></html>",
    "namespace": "docudesk",
    "format": "A4",
    "orientation": "P"
  }
]
```

### Preview request examples

```json
[
  {
    "templateId": "bb000001-0001-0001-0001-000000000001",
    "data": {
      "titel": "Beslissing op uw bezwaar",
      "aanhef": "mevrouw De Jong",
      "inhoud": "Uw bezwaar van 1 mei 2026 is ontvangen en beoordeeld. Het bezwaar is ongegrond verklaard.",
      "gemeente": "Gemeente Amsterdam"
    }
  },
  {
    "template": "<h1>{{ titel }}</h1><p>{{ inhoud }}</p>",
    "data": {
      "titel": "Testdocument",
      "inhoud": "Dit is een proefafdruk van een inline sjabloon."
    }
  },
  {
    "templateId": "bb000003-0003-0003-0003-000000000003",
    "data": {
      "naam": "Dhr. P. Janssen",
      "bericht": "Uw aanvraag voor parkeervergunning is in behandeling.",
      "datum": "20 mei 2026"
    }
  }
]
```

## Risks / Trade-offs

**[Iframe sandbox flags vs. print capability]** → `window.print()` requires the iframe to allow script execution. Using `sandbox="allow-scripts allow-same-origin"` on a `srcdoc` iframe is safe because no external resources load. Mitigation: srcdoc content is server-rendered and sanitized — no user-controlled HTML injection paths exist in the component.

**[PDF/A-3b generation time for large templates]** → mPDF PDF/A mode embeds ICC profiles and XMP metadata, adding ~200–500ms over standard PDF. Mitigation: Acceptable for user-triggered downloads; not a batch or automated path.

**[TemplateService returns 404 for unknown templateId]** → If a caller provides a non-existent `templateId`, `TemplateService` throws a not-found exception. Mitigation: `PrintController` catches this and returns a 404 JSON response.

## Open Questions

1. **Should the `/print-preview/:templateId?` route be accessible to unauthenticated users?** Recommendation: No — keep it authentication-gated via Nextcloud middleware. Deep linking from external systems should use a share-token pattern if needed.
2. **Should the rendered HTML be sanitized before injection into the iframe srcdoc?** Recommendation: No additional sanitization on top of Twig sandbox — the template content is already controlled by authenticated users who manage templates.
