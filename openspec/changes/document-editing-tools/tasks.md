# Tasks — document-editing-tools

## 0. Phase 0 — measure before building (hard gate)

- [x] 0.1 Measure `w14:paraId` survival. **NEGATIVE** — round-tripped a `.docx` through Collabora's `soffice`: `['1A2B3C4D','5E6F7A8B','9C0D1E2F']` → `[]`. Every paragraph survived, zero `w14:` attributes did. ⚠️ The `w14` **namespace declaration survives**, so checking for the namespace would wrongly conclude the extension round-trips. Content-hash anchors selected.
- [x] 0.2 Determine whether a background job can obtain a file-scoped WOPI token for its initiating user. **PASSED** — `TokenManager::generateWopiToken($fileId, null, $editoruid)` with `$editoruid = $this->userId ?? $editoruid` needs no session and no service user. No ADR escalation. **Superseded in practice:** the session is in-process (see 0.4), so no token is minted at all.
- [x] 0.3 Stand up a WOPI host in the dev environment. `richdocuments` 11.1.0 + `richdocumentscode` 26.4.104 side-loaded.
- [x] 0.4 **NEW, and it reverses the design.** Measured `richdocuments`' WOPI lock: `WopiController::lock()` ignores `X-WOPI-Lock` and takes an `ILockManager` `TYPE_APP` lock owned by `richdocuments`; `files_lock` **extends** a lock whose type and owner match. A WOPI client's lock is therefore indistinguishable from Collabora's own — the contention refusal is unachievable through that route. The session takes an in-process `TYPE_APP` lock owned by `docudesk` instead. See design.md §MEASURED.

## 1. Conversion tool

- [x] 1.1 `PdfConversionService::convertToPdfReporting()` wraps the existing cascade and reports the claiming backend. `convertToPdf()` delegates to it, unchanged for existing callers.
- [x] 1.2 Annotate `convertDocumentToPdf` `#[McpTool(scope: 'create', destructiveHint: false, ...)]`; class added to `DocudeskScannableServices`.
- [x] 1.3 Refuse with a structured error naming the source file when no backend claims it. The backend is never an agent-supplied argument, so the tool cannot be steered onto `soffice` as a process-execution primitive.

## 2. Editing session

- [x] 2.1 `lib/Service/Editing/EditSessionService.php` — in-process session (supersedes the WopiClient of the original task, per 0.4). `PutFile`-equivalent refuses unless the lock is held AND the file etag is re-confirmed unchanged immediately before the write. `UnlockAndRelock` has no analogue and no lock is ever taken that the service did not create.
- [x] 2.2 Lock lifetime owned by the session, released in a `finally` on every exit path including refusals and thrown exceptions.
- [x] 2.3 Structured refusal on lock contention — no polling, no queueing, no retry.
- [x] 2.4 `lib/Service/Editing/PackageCodec.php` + `XmlBlockScanner.php` — parse the ODF/OOXML package, expose anchored blocks, mutate only the targeted paragraph's byte range and rewrite only that part, leaving every other entry byte-identical.
- [x] 2.5 `editForAgent()` supporting both output modes, configuration as the ceiling and the argument able only to narrow. Annotated `#[McpTool(scope: 'update', destructiveHint: true, ...)]`.
- [x] 2.6 Refusals enforced: a file under a non-cancelled `signingRequest` (fails **closed** when the register is unreachable), anonymisation output (fails open), and no document/attachment/signature bytes in any response.
- [x] 2.7 ADR-088 marking via `ISystemTagManager`/`ISystemTagObjectMapper` in the same code path as the write — applied **before** the write and rolled back if the write fails, so neither an unmarked artefact nor a mark on an unchanged file survives. Produced file id returned as an `artefact` descriptor, which Hermiq lifts into the trace.
- [x] 2.8 **NEW:** `readDocument`. The spec's two tools were unusable without it — `editDocument` takes anchors, and nothing else could produce them. Read-only, `scope: read`.

## 3. Verify

- [x] 3.1 Capability degrades visibly rather than silently: an unsupported format refuses **naming the editable formats**, and an instance with no lock provider still edits but reports the absent guard in `warnings[]`.
- [x] 3.2 Conversion reports the claiming backend (asserted by test); the cascade itself is unchanged and already covered.
- [ ] 3.3 Portability run against Euro-Office. **NOT DONE** — no Euro-Office instance available. Per the spec's own instruction the portability claim must be dropped rather than shipped unmeasured; ADR-087's claim remains unverified.
- [x] 3.4 Both tool ids appear in the agent detail page's Tool governance grant editor, classified **Write** / **Requires explicit grant**, default-denied. Verified in the browser: all 20 `docudesk.*` tools render; `docudesk.editDocument` shows "Write" + "Requires explicit grant" with a Grant button; granting through the UI persisted.
- [x] 3.5 Concurrency + recovery, all three run live against a real file: (a) a moved version **refuses** the write and nothing lands (proven with a fresh anchor + stale version); (b) a prior version exists and was **restored**, bringing the original text back — verified by restoring, not by observing that versioning is enabled; (c) no lock is ever stolen.
- [x] 3.6 Round-trip fidelity: a `.docx` carrying a comment, a tracked insertion, a tracked deletion, a header, a table, a text box and a PNG — every other package entry byte-identical, and the edited part still carries its comment ranges, `w:ins`/`w:delText`, `pStyle`, run formatting and `w14:paraId`.
- [x] 3.7 Scoped `phpcs`/`phpmd`/`psalm` clean on new and touched files; zero new PHPUnit failures vs a self-measured baseline; CHANGELOG entry.

## Acceptance criteria

- An agent can read a document's anchored text, convert it (new file, source untouched) and edit it (in place by default with a restorable prior version; sibling on request). **Met and exercised live** through the MCP endpoint against a real `.docx`.
- An in-place write is refused when the file changed between read and write. **Met, proven live.**
- Every produced file carries the agent-authored system tag at the moment it becomes visible. **Met, proven live** (tag id 153, user-visible, on the file in Files).
- Every produced file's id appears in Hermiq's invocation record; no document content appears there. **Met** — the tool returns `artefact: {type: file, id}` and `FacadeToolInvoker` lifts exactly `type` and `id`.
- No per-suite backend class is added. **Met.**
- Lock contention, missing lock provider, unsupported format, signed document, anonymisation output and a stale version each produce a distinct structured refusal. **Met.**
- Both tools are default-denied and visible with correct classification in Tool governance. **Met, verified in the browser.**
- Comments, tracked changes and styles survive an edit round-trip. **Met.**

## Not delivered

- **3.3 Euro-Office portability** — untestable here; the ADR-087 claim stays unverified.
- **An end-to-end run driven by the chat window's LLM.** The tools are live, granted and correctly classified, and the full read → edit → tag → version → refusal loop is proven against a real file through the real MCP endpoint. What is not proven is a *model* choosing to call them: this instance's `chatProvider` is `anthropic` with `executionMode: cli`, and in that mode the model is offered the CLI transport's own tool namespace rather than Hermiq's registry. Switching to Ollama for a test produced an empty reply from `qwen2.5:3b` (the only tool-capable model present). Neither is a defect in this change; both are instance/provider configuration.
- **The `generatedDocument` audit row is not being written on this instance.** `GeneratedDocumentLogger::log()` fails with "The required properties (documentType, employeeId) are missing" although the only schema slugged `generatedDocument` (id 5023) requires neither — a register-resolution problem that predates this change. `DocumentAgentService::record()` logs and swallows it by design (the file is already written and tagged, so throwing would report a failure that did not happen), and the authoritative half of ADR-088 — Hermiq's `artefact` record — is unaffected. Needs its own fix.
