---
kind: code
---

# A template renders through a vendor engine

## Why

ConductionNL/filinq#1253. Dossiq's decision templates carry an `engine`
(`filinq`, or `vendor:<sourceSlug>`), and dossiq's
`FilinqTemplateEngineAdapter` passes it to filinq's
`DocumentService::generateDocument()` as `options.engine` (dossiq change
`decision-templates-render-through-a-vendor`, tasks 2.1, 2.2, 2.4). Integriq
renders through SmartDocuments and Xential: it handles
`OCA\Integriq\Event\DocumentRenderRequestedEvent` by creating a tracked
`documentGenerationJob`, and dispatches `DocumentRenderedEvent` on every
terminal state (integriq spec `document-generation-vendor-adapter`,
REQ-DGV-002). Filinq is the missing middle link: it ignores `options.engine`
today, so no decision reaches a vendor from a case.

## What changes

- `generateDocument()` reads `options.engine`. Absent or `filinq`: the
  current render, unchanged. `vendor:<sourceSlug>`: the template's merge data
  goes to integriq as a `DocumentRenderRequestedEvent` for the source of that
  slug, with the template's vendor template id, the acting user and
  `requestedByApp: filinq`. Any other value is refused (400).
- A vendor render never falls back to filinq's own engine. Integriq absent,
  the slug naming no source, or the event answered with a refusal or with
  nothing: the call is refused with a message naming the source.
- The render is asynchronous on the vendor side: filinq records a
  `generatedDocument` in state `pending` with integriq's job id and answers it
  at once. A listener on `DocumentRenderedEvent` stores the vendor's file the
  way a filinq render stores its output (same folder, same record fields) and
  moves the entry to `generated`, or to `failed` with the vendor's error.
- A template names its vendor template id (`vendorTemplateId`, optional);
  a vendor render of a template without one is refused.
- `GET /api/templates/engines` lists the engines this instance can render
  with: `filinq` always, plus `vendor:<slug>` for each active document
  generation source integriq reports. Dossiq's template editor reads it
  (dossiq task 2.3).

## Impact

- `lib/Service/DocumentService.php` (`options.engine`), a new vendor render
  class beside it, a listener for `DocumentRenderedEvent`, `TemplatesController`
  (engines endpoint), `lib/Settings/filinq_register.json`
  (`template.vendorTemplateId`, `generatedDocument` state `pending` and
  `vendorJobId`).
- The integriq events are reached by class name at runtime; filinq keeps
  working without integriq installed.
