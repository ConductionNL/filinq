## Context

Filinq owns document generation for the fleet. Since 2026-09-09 dossiq renders
every beschikking through `DocumentService::generateDocument()` rather than
through a mock, so filinq is now on the legal path for a besluit that a citizen
can appeal.

Three facts about the code, verified on `development`:

1. `TemplateService::getTemplate()` returns `ObjectEntity::jsonSerialize()`.
   That merges the object body with a `@self` block, and OpenRegister keeps the
   object version inside `@self`. The `template` schema in
   `lib/Settings/filinq_register.json` declares no `version` property, so
   `$template['version']` is null.
2. `DocumentService::generateDocument()` logs
   `'version' => (int)($template['version'] ?? 1)` at line 219. Given fact 1
   the coalesce always fires.
3. `TemplateVersionService` already stores the full `content` of every version
   and exposes `getVersion()`, `getVersions()`, `getDiff()` and
   `restoreVersion()`. The generation path never touches it.

So the version chain filinq needs already exists. Nothing on the generation
path is connected to it.

## Goals / Non-Goals

**Goals:**

- A generated document can be traced to the template version that produced it.
- A document can be produced again from that version, byte for byte, after the
  template has moved on.
- Filinq can answer which version was in force at a moment, so a caller does
  not have to keep its own copy of the template history.
- A caller stops re-deriving from the output file what filinq already knows
  about it.

**Non-Goals:**

- Effective dating as a stored property. The version chain carries creation
  timestamps and that is enough to answer the question. A `validFrom` property
  on `templateVersion` would be a second source of truth for the same fact.
- A template approval or publication workflow. Which version is legally
  correct is a policy decision in the calling app.
- The output folder. `DEFAULT_OUTPUT_FOLDER_PREFIX = 'DocuDesk'` is a
  pre-rename literal and generated documents land in the acting user's own
  Files rather than the case folder. Both are real and both are separate.

## Decisions

**D1. Read the version from `@self`, do not add a schema property.**
OpenRegister already versions the object. Declaring `version` on the `template`
schema would give two numbers that can disagree, and the one a human edits
would win. `getTemplate()` lifts `@self.version` to the top level and leaves
`@self` untouched.

**D2. An absent version is absent, not 1.** The defect this change removes is a
default that reads as a fact. Every path that cannot establish a version says
so. This is the same rule dossiq applied when it stopped calling an unredacted
document `queued`.

**D3. Pinning renders stored content, it does not restore.**
`restoreVersion()` moves the template's head. Generation must not move
anything. `options.templateVersion` resolves through
`TemplateVersionService::getVersion()` and passes that version's `content` to
the existing `TemplateRenderer`, so the render path is unchanged.

**D4. A missing pinned version fails.** Falling back to the head would produce
a document that claims a version it was not rendered from. That is worse than
no document.

**D5. The in-force query reads the chain, it does not store a window.**
`templateVersion` rows carry a creation timestamp. The version in force at a
moment is the newest row created at or before it. When no row qualifies,
filinq says no version was in force. It does not round down to the oldest,
because a beschikking dated before the first template version is a data
problem the caller must see.

**D6. Checksum and page count come from the producer.** Filinq has the bytes
in hand at the end of `produceOutput()`. Dossiq currently counts pages by
matching `/Type /Page` against the PDF bytes. That is a heuristic in the wrong
app.

## Risks / Trade-offs

**A caller that relied on version 1 will see a different number.** Nothing
should, because the number was never real, but the `generatedDocument` records
already written all say 1 and cannot be corrected. They stay as they are and
the change is forward only.

**Creation timestamps are not effective dates.** A template edited on 1 June
and intended to apply from 1 July will answer 1 June. That is a policy
question, and D5 answers the mechanical one honestly rather than guessing.
When a real effective date is needed later, it goes on `templateVersion` and
supersedes the timestamp read.

**Page counting stays format-specific.** Only PDF output has a page structure
filinq can count. Other formats report no page count, and a caller that wants
one must convert first.

## Resolved at apply (2026-09-30)

The design did not fit the code at HEAD in two places. Both are corrected here
and in the spec delta.

**D1 revised: the version is the chain number, not `@self.version`.**
OpenRegister's object version is a semver string (`0.0.1`, patch bumped on
every save), so it is not "version 4", and it moves on saves that make no
template version (a restore's head write, a lock release). The
`generatedDocument.templateVersion` property is an integer, and a pin resolves
against `templateVersion` rows, which are numbered 1, 2, 3. So the one number
that a record can store and a caller can pin is the chain's:
`getTemplate()` sets `version` to `getNextVersionNumber()` (snapshots + 1) and
leaves `@self` alone. The update paths drop `version` before saving, so the
number is never stored on the template.

**D2 needed a schema change.** `generatedDocument.templateVersion` was
required; an unknown version must be absent, so it is optional from
`generatedDocument` 1.5.0 (register 8.34.0). The multi-format producer and
the agent's generation record wrote 1 and 0; both now write nothing.

**D5 revised: a snapshot marks the END of its version.** `updateTemplate()`
snapshots the old state before saving the new one, so snapshot N's creation
time is when version N stopped being the head. The version in force at a
moment is the lowest N whose snapshot is newer than the moment, else the head.
Version 1 starts at the template's own `@self.created`; a moment before that
returns null.

**D3 addition.** `options.templateVersion` together with `options.formats` is
refused (400): the multi-format producer renders the head once for every
format, and ignoring the pin there would produce exactly the document D4
forbids.

**D6.** The page count comes from FPDI's parser (already used by
`GrondslagenPdfWriter`) in `DocumentRenderPipeline::describeOutput()`. A PDF the
free parser cannot read (a compressed cross-reference stream) reports no page
count and logs a warning, rather than a guessed one.

`DocumentService` gained six lines; the logic lives in `TemplateService`,
`TemplateVersionService` and `DocumentRenderPipeline`, keeping DocumentService
under phpmd's class-length limit.
