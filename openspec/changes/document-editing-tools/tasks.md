# Tasks — document-editing-tools

## 0. Phase 0 — measure before building (hard gate)

- [ ] 0.1 Measure `w14:paraId` survival: author a `.docx` with known ids, round-trip it through Collabora (open, edit elsewhere in the doc, save), diff the attributes. Repeat for ODF `xml:id`. Record the result in design.md §Anchors and select native-id or content-hash anchoring accordingly.
- [ ] 0.2 Determine from richdocuments' WOPI token issuance whether a background job can obtain a file-scoped token for its initiating user. If the only route is a service user with broad file access, STOP and raise an ADR before writing editing code.
- [ ] 0.3 Stand up a WOPI host in the dev environment (richdocuments + an office app profile in `.github/docker-compose.yml`) — nothing below is testable without one.

## 1. Conversion tool (no unknowns)

- [ ] 1.1 Add `ConversionService::convertForAgent()` wrapping the existing `ConversionBackendInterface` cascade: resolve the file for the acting user, run the cascade, write the result as a new file, log a `generatedDocument` record. Report the claiming backend in the result.
- [ ] 1.2 Annotate it `#[McpTool(name: 'convertDocument', scope: 'create', readOnlyHint: false, destructiveHint: false, idempotentHint: false, description: ...)]`; add the class to `DocudeskScannableServices`.
- [ ] 1.3 Refuse with a structured error naming the source format when no backend claims the source→target pair. Never return a lower-fidelity result without reporting which backend produced it.

## 2. WOPI editing session (gated on task 0)

- [ ] 2.1 Add `lib/Service/Editing/WopiClient.php` — `CheckFileInfo`, `GetFile`, `PutFile`, `PutRelativeFile`, `Lock`, `RefreshLock`, `Unlock`. `UnlockAndRelock` is NOT implemented. `PutFile` MUST refuse unless the session's own lock is held AND `CheckFileInfo`'s `Version` is re-confirmed unchanged immediately before the write.
- [ ] 2.2 Add `lib/Service/Editing/EditSessionService.php` owning lock lifetime with release in a `finally` on every exit path, including timeout and thrown-exception paths.
- [ ] 2.3 Return a structured refusal on lock contention — no polling, no queueing, no retry loop.
- [ ] 2.4 Add `lib/Service/Editing/PackageCodec.php` — parse the ODF/OOXML package, expose anchored blocks, apply edits by mutating only targeted nodes and rewriting only that package part, leaving every other entry byte-identical.
- [ ] 2.5 Add `EditSessionService::editForAgent()` supporting both output modes — in-place (`PutFile`, default) and sibling (`PutRelativeFile`) — where configuration sets the ceiling and a tool argument may only narrow to sibling, never widen to in-place. Annotate `#[McpTool(name: 'editDocument', scope: 'update', readOnlyHint: false, destructiveHint: true, idempotentHint: false, description: ...)]`; extend `DocudeskScannableServices`.
- [ ] 2.6 Enforce the refusals: reject a file referenced by a non-cancelled `signingRequest`; reject anonymisation output; return no document, attachment or signature bytes in any response.
- [ ] 2.7 ADR-088 marking: apply a Nextcloud system tag via `ISystemTagManager` / `ISystemTagObjectMapper` in the SAME code path that writes the file (both tools), record the produced file id on the `generatedDocument` row and on Hermiq's `tool` trace step, and return FAILURE if the tag cannot be applied — never success on an unmarked file.

## 3. Verify

- [ ] 3.1 Probe test: on an instance with no reachable WOPI host, `CheckFileInfo` fails and the capability resolves absent with a visible degrade — not a silent success. Assert the probe is a real `CheckFileInfo`, not an installed-app-id check.
- [ ] 3.2 Conversion verified twice: once with an office app registered in `IConversionManager`, once with the cascade falling through to PhpWord/mPDF. Assert the reported backend differs between the two runs.
- [ ] 3.3 Portability run: exercise conversion against Collabora AND against Euro-Office with `wopi.enable` set true in `local.json`. If Euro-Office cannot be tested, remove the portability claim from the spec rather than ship it unmeasured.
- [ ] 3.4 Assert both tool ids appear in the agent detail page's Tool governance grant editor (`ToolOversightController::toolCatalog()`) classified write, and that both are default-denied without an explicit exact-id grant.
- [ ] 3.5 Concurrency + recovery controls for the in-place default, all three run: (a) a `Version` that moved between `GetFile` and `PutFile` REFUSES the write, proven with a forced mid-session change; (b) after an in-place edit a restorable prior Nextcloud file version exists — verified by restoring it, not by observing that versioning is enabled; (c) `grep -rn "UnlockAndRelock" lib/` returns nothing outside comments.
- [ ] 3.6 Round-trip fidelity test: edit one paragraph of a document carrying comments, tracked changes and a header; assert all three survive byte-identical in the output package.
- [ ] 3.7 Scoped `phpcs` clean on new/touched files; zero new PHPUnit failures vs a self-measured baseline; CHANGELOG entry.

## Acceptance criteria

- An agent can convert an existing document (producing a new file, source untouched) and edit an existing document (in place by default, with a restorable prior version; sibling output on request).
- An in-place write is refused when the file changed between read and write, and the other writer's change survives intact.
- Every produced file carries the agent-authored system tag at the moment it becomes visible, and a tagging failure surfaces as a failed operation rather than a silent success (verified by forcing the tag write to fail).
- Every produced file's id appears in Hermiq's invocation record, so an operator can follow the record to the artefact; no document content appears there.
- No per-suite backend class is added; conversion routes through `IConversionManager` and editing through one WOPI client (ADR-087).
- Lock contention, missing WOPI host, unsupported format, signed document and anonymisation output each produce a distinct structured refusal.
- Both tools are default-denied and approval-gated, and are visible with correct classification in Tool governance.
- Comments, tracked changes and styles survive an edit round-trip.
