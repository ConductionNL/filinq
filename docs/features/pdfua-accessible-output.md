# Accessible PDFs and accessibility checks

Government documents have to be readable with a screen reader (Besluit
digitale toegankelijkheid overheid, EN 301 549, WCAG 2.1 AA). For a PDF that
means tags that give it structure, a document language and a title: PDF/UA
(ISO 14289-1). Filinq can generate such PDFs, checks every PDF for the basics,
warns before a document goes to publication, and points out problems in a
template while you write it.

## Generate an accessible PDF

Ask for it per generation with `pdfOptions.accessible`:

```json
POST /apps/filinq/api/documents/generate
{ "templateId": "…", "dataRefs": [], "options": { "pdfOptions": { "accessible": true, "title": "Besluit parkeervergunning", "lang": "nl-NL" } } }
```

- The PDF is made by LibreOffice with tags and PDF/UA switched on, never by
  the standard PDF engine, which cannot write tags. With `pdfa: true` as well
  it keeps PDF/A-3.
- The language comes from `lang`, else from the template's language, else
  from the Nextcloud `default_language`. With none of these, nothing is made
  and the answer says a language is missing.
- The title comes from `title`, else from the template name.
- Without LibreOffice on the server the request fails (503) and lists what
  was tried. You never get an untagged PDF in its place.
- Filinq checks what LibreOffice returns. A PDF without tags, language or
  title is refused, not handed out as accessible.

How good the tags are depends on the template: real headings, alternative
text on images and header cells in tables carry over into the PDF.

Files can be converted the same way: `PdfConversionService::convertToPdf($file, ['accessible' => true])`.

## Accessibility checks

Document validation checks every PDF for four things:

| Check | Fires when |
|---|---|
| `pdf-not-tagged` | the PDF has no structure tree, or is not marked as tagged |
| `pdf-language-missing` | the PDF does not name its language |
| `pdf-title-missing` | the PDF has no title in its metadata |
| `pdfua-identifier-missing` | a tagged PDF does not say it follows PDF/UA |

They show under **Accessibility checks** in the validation result, as
warnings by default. An admin can make one blocking or switch it off in the
`validation.profiles` app config, like any other check.

These checks look for the presence of these parts in the PDF. They do not
certify PDF/UA: a PDF can pass them and still be hard to read. For a full
check against the PDF/UA rules, use a dedicated validator.

## Before publishing

**Publish** on a document first runs the checks. If the document has open
accessibility findings, you see them with the choice to publish anyway. If
your organisation made one of those checks blocking, the document cannot be
published until it is fixed.

## While writing a template

The template preview shows an **Accessibility checklist**:

- images without alternative text, by number and file name
- a heading that skips a level, such as a h3 straight after a h1
- tables without header cells, by number and first cell
- no language for the template or for this Nextcloud

It is advice: you can always save and preview.
