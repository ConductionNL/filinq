# Design: templates-house-style-from-thematiq

Read at filinq development `9fd042f8` and thematiq development on
2026-09-28.

## Context

- `DocumentRenderPipeline::loadHuisstijl(?string $huisstijlId)`
  (`lib/Service/DocumentRenderPipeline.php:70`) returns null when no id is
  given, and otherwise reads the `huisstijl` object from register `filinq`.
- `CorrespondenceService::buildPdfOptions()` (`lib/Service/CorrespondenceService.php:585`)
  applies `defaultMargins`; the header and footer render (:620-) adds
  `headerHtml` and `footerHtml`.
- `huisstijl` 1.1.0 (`lib/Settings/filinq_register.json`) has `name`, `logo`,
  `primaryColor`, `headerHtml`, `footerHtml`, `defaultMargins`; read by any
  authenticated user, written by `docudesk-template-editors`.
- `openspec/specs/letter-correspondence-generation/spec.md` requirement
  "Huisstijl default configuration" already says a default huisstijl applies
  when the template names none; nothing implements the default.
- thematiq profile (`surfaces-document-house-style` D1):
  `{ tokenSet: {id, name}, organisation, logo: {url, mime}, cover|null,
  colours: {primary, primaryText, text, background, accent}, fonts: {heading,
  body} with family and url, footer: {lines, accessibilityUrl, privacyUrl} }`,
  per user through `DocumentStyleService::forUser(?string $uid)`.

## Decisions

### D1. One managed house style per token set

`HuisstijlProfileSync::syncFor(?string $uid)` resolves thematiq's
`DocumentStyleService` only when the class exists and thematiq is enabled,
reads the profile, and upserts a `huisstijl` with `managedBy: thematiq`,
`tokenSetId`, `profileHash` and the mapped values: `name` from the token
set, `logo` from the logo URL, `primaryColor` from `colours.primary`,
`fonts`, `coverImage`, `footerLines` and a generated `footerHtml` from the
footer lines and links. When the hash is unchanged it writes nothing.

Alternative considered: read the profile at every render and keep no
object. Rejected: a generated document records the house style it used, and
that record must still exist after the profile changes.

### D2. The default at render time

When a request names no `huisstijlId`, `loadHuisstijl()` calls
`syncFor(<requesting user>)` and uses that managed house style. A named
`huisstijlId` wins. Without thematiq the behaviour is today's.

### D3. Managed means read-only here

A `huisstijl` with `managedBy: thematiq` is refused on update in filinq's
own API with "This house style follows thematiq; change it in Theming". The
schema's authorization stays as it is; the refusal is in the service that
writes it.

### D4. Fonts and cover reach the PDF

`buildPdfOptions()` registers the profile's font URLs with mPDF when present
and falls back to filinq's fonts; a template that asks for a cover page gets
`coverImage`.

## Declarative-vs-imperative decision (ADR-031)

| concern | choice | why |
|---|---|---|
| house style values | declarative schema fields | data |
| syncing from thematiq | imperative service | reading another app's profile |
| default at render | imperative, in the render pipeline | a lookup at generation time |

## Seed data

None: a managed house style appears on first use. The demo data gains one
managed house style for the default token set so the render test has one.

## Risks

- thematiq renames a profile key. The mapping lives in one method with a
  test against the documented shape.
