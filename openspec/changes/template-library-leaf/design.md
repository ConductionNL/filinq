# Design: template-library-leaf

Kind: code. A schema property, two leaf registrations, one dialog reuse.

## Context

`TemplateService` (REQ-TMPL-04) already answers "which templates exist" and
the merge path (`REQ-DCS-02`) already renders one against OpenRegister data.
Nothing exposes either to a sibling app on an object. ADR-066 gives the shape:
a data-provider leaf for the list, a render-surface leaf for the dialog, both
under filinq's own DI and bundle, no verb into the consumer.

## D1. Template scope

`template` gains `scope`: an array of `{register, schema}`. `TemplateService::
listForObject(register, schema)` returns templates whose scope is empty or
contains the pair. Namespace enforcement (REQ-TMPL-03) still applies first.

## D2. `filinq-templates`, kind data-provider

- `lib/Integration/TemplatesLeafProvider.php` implements OpenRegister's
  `IntegrationProvider` with `getStorageStrategy() === 'app-local'`.
- `list(register, schema, objectId)` returns the scoped templates as
  `{id, name, format, description}`. No `create`.
- Registered from an `IEventListener` on `RegisterLeafProvidersEvent` behind a
  `class_exists()` guard so filinq boots without OpenRegister's leaf API.

## D3. `filinq-generate-document`, kind render-surface

- Descriptor id `filinq-generate-document`, `renderMode: component`.
- JS `registerIntegration()` with a `widget` (the template list plus a
  Generate button) and a `tab` (the same list plus the generated documents on
  this object). Both live in `src/integrations/`.
- Generate opens the existing merge dialog seeded with the host object as the
  data source and the host's folder as the target. The call goes to filinq's
  own `POST /api/documents/generate` (REQ-DCS-07). Nothing calls the consumer.

## D4. Result on the host

The generated file is a filinq `document` object with `sourceObject`
`{register, schema, id}` and lands in the host object's folder when it has
one. The consumer sees it through the files leaf. dossiq needs no code for
that.

## Risks

- A template scoped to `dossiq/case` breaks when dossiq renames its schema.
  Scope matches on slugs, which are frozen per app.
- Parity (gate-24): both halves must agree on id and render pair. The parity
  check runs in CI.
