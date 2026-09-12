# template-management Specification (delta)

## ADDED Requirements

### Requirement: Template scope per register and schema (REQ-TMPL-13)

The `template` schema MUST carry an optional `scope` array of `{register,
schema}` pairs. `TemplateService::listForObject(register, schema)` MUST
return templates whose scope is empty or contains the pair, after namespace
enforcement (REQ-TMPL-03).

#### Scenario: A scoped template only appears on its schema

- GIVEN a template scoped to `dossiq/case` and one with an empty scope
- WHEN `listForObject('dossiq', 'contact')` runs
- THEN only the unscoped template is returned
- @e2e exclude service-level filter; covered by PHPUnit on `TemplateService::listForObject()`

### Requirement: Templates are a data-provider leaf (REQ-TMPL-14)

Filinq MUST register a leaf `filinq-templates` of kind `data-provider` with
storage strategy `app-local` through `RegisterLeafProvidersEvent`. Its
`list(register, schema, objectId)` MUST return the scoped templates as
`{id, name, format, description}`. It MUST NOT offer `create` and MUST NOT
call any action in the consuming app (ADR-066 decision 2).

#### Scenario: A sibling reads the templates for an object

- GIVEN filinq is installed with two templates scoped to `dossiq/case`
- WHEN OpenRegister asks the `filinq-templates` provider to `list` for a case object
- THEN both templates are returned, and the consumer's manifest holds no query on filinq's register
- @e2e exclude provider read in filinq's DI context; covered by PHPUnit on `TemplatesLeafProvider::list()`

### Requirement: Generate from template is a render-surface leaf (REQ-TMPL-15)

Filinq MUST register a leaf `filinq-generate-document` of kind
`render-surface` on both halves under one id, with a `widget` and a `tab`.
The widget MUST list the scoped templates and open filinq's merge dialog
seeded with the host object. The merge MUST run through filinq's own
generation API (REQ-DCS-07) and the result MUST be a filinq document with
`sourceObject` set to the host, placed in the host's folder when it has one.

#### Scenario: A clerk generates a letter on a case

- GIVEN dossiq places `filinq-generate-document` on its case detail page
- WHEN a clerk picks a template in the widget and confirms
- THEN a new document with `sourceObject` pointing at the case appears in the case folder
- e2e: `tests/e2e/template-leaf.spec.ts`

#### Scenario: Both halves agree

- GIVEN the leaf is registered
- WHEN gate-24 inspects the app
- THEN the descriptor and the JS registration share the id and both `widget` and `tab` exist
- @e2e exclude parity is checked mechanically by gate-24
