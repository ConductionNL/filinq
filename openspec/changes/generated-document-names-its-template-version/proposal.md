## Why

Dossiq now renders a beschikking through filinq. That landed on 2026-09-09
(dossiq #2059): `FilinqTemplateEngineAdapter` calls
`DocumentService::generateDocument()` and reports the file filinq actually
wrote, replacing a mock that hashed its own arguments. The seam works. The
document it produces cannot say which template version made it.

Three things are wrong, and all three are in filinq.

**Filinq reads a template version off a property no schema declares.**
`DocumentService::generateDocument()` logs
`'version' => (int)($template['version'] ?? 1)` (lib/Service/DocumentService.php:219).
`TemplateService::getTemplate()` returns `ObjectEntity::jsonSerialize()`, which
puts the OpenRegister version under `@self.version`, not at the top level, and
the `template` schema in `lib/Settings/filinq_register.json` declares no
`version` property at all. The coalesce therefore always fires. Every generated
document in every install carries template version 1 in its audit record. A
version number that is always 1 is not a version number.

**A caller cannot ask for a specific version.** `generateDocument()` renders
`$template['content']`, which is always the head. `TemplateVersionService`
already stores the full `content` of every version and can fetch one
(`getVersion()`), diff two (`getDiff()`) and restore one (`restoreVersion()`).
Nothing on the generate path can reach it. So a beschikking cannot be
regenerated from the template that produced it, and an appeal against a
beschikking issued in March gets whatever the template says today.

**Nobody can ask which version was in force on a date.** Dossiq's adapter
carries a `resolveVersion(templateId, effectiveDate)` method because a
beschikking must record the template that applied when it took effect. Filinq
can only answer with the current one, so the adapter echoes the date back and
returns `v1`. That is the mock behaviour the adapter was written to remove,
surviving in the one method that had no filinq answer to call.

A caseworker who has to defend a beschikking at a bezwaarcommissie needs to
show the letter that was sent and the template it came from. Today filinq can
show neither.

## What Changes

- `TemplateService::getTemplate()` surfaces the OpenRegister object version at
  the top level, so a caller and the audit log read a real number.
- `DocumentService::generateDocument()` accepts an optional
  `options.templateVersion`. When given, it renders that version's stored
  content through the existing pipeline instead of the head. When absent it
  renders the head, as it does today, and records which version that was.
- `TemplateVersionService` answers which version was in force at a moment,
  from the version chain's own creation timestamps. No new effective-dating
  model, no schema property, no migration.
- The generation result reports the page count and a SHA-256 over the produced
  bytes. Filinq holds the bytes; today every caller re-derives both, and
  dossiq's adapter counts PDF pages with a regular expression over the file.
- The generated-document audit record carries the template version that was
  rendered, so an existing record can be traced back to a template.

Out of scope: effective dating as a stored property on `template` or
`templateVersion`, template approval workflow, and the default output folder
(`DEFAULT_OUTPUT_FOLDER_PREFIX = 'DocuDesk'`, a pre-rename literal, tracked
separately).

## Capabilities

### New Capabilities

- `template-version-provenance`: a generated document names the template
  version it was rendered from, a caller can pin that version, and filinq can
  answer which version was in force on a date.

### Modified Capabilities

<!-- None. The generation and template-management requirements are unchanged;
     this capability adds provenance on top of them. -->

## Impact

- `lib/Service/DocumentService.php`, `lib/Service/TemplateService.php`,
  `lib/Service/TemplateVersionService.php`, `lib/Service/DocumentLogger` and
  the `generatedDocument` audit record.
- Consumer: dossiq's `FilinqTemplateEngineAdapter::resolveVersion()` and
  `countPages()` both become calls instead of guesses. That is a separate
  change in the dossiq repo.
- No schema change, no migration, no new dependency. Backwards compatible:
  `options.templateVersion` is optional and its absence keeps today's
  behaviour.
