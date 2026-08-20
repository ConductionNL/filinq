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
- ~~An end-to-end run driven by the chat window's LLM.~~ **DELIVERED 2026-08-16, and the blocker was worth finding.**

  The first attempt failed with the model reporting it had no document tools and naming its own CLI built-ins instead. Cause: `executionMode: cli` hands the CLI a governed MCP config whose URL comes from `linkToRouteAbsolute()` — the origin Nextcloud publishes to BROWSERS. On this instance that is `http://localhost:8080`, and inside the runner container `localhost` is the container itself. The CLI connected to nothing, `tools/list` never served Hermiq's tools, and the run exited 0 with an empty stderr. Fixed by setting `occ config:app:set hermiq mcp_run_base_url --value="http://nextcloud"`, the container-facing origin.

  Verified end to end afterwards: asked in the chat window to change "binnen acht weken" to "binnen zes weken" in a real `.docx`, Claude called `readDocument` then `editDocument` itself, and the bytes on disk changed on exactly that paragraph with `pStyle` and the bold run intact, the `Agent authored` tag present, and a restorable prior version created.

  ⚠️ The silent-degradation itself is now fixed upstream: hermiq PR #318 makes a governed turn PREFLIGHT its MCP endpoint and refuse to spawn when it is unreachable, naming `mcp_run_base_url` in the message. A governed turn that cannot reach its governance is not degraded, it is ungoverned.
- **The `generatedDocument` audit row is not being written on this instance — ROOT CAUSE FOUND, and it is not DocuDesk's code.** `GeneratedDocumentLogger::log()` fails with "The required properties (documentType, employeeId) are missing", which DocuDesk's own `generatedDocument` (schema id 5023) requires neither.

  Measured on the dev instance:
  1. `lib/Settings/docudesk_register.json` correctly declares the `document` register with all ten of its schemas, `generatedDocument` among them.
  2. The LIVE `document` register (id 6) has **zero schemas attached**. So has 29 of the instance's 200 registers.
  3. With no register scope to resolve within, OpenRegister falls back to resolving the schema slug **globally and case-insensitively** — and the instance carries a foreign `GeneratedDocument` (id 1467, title "Generated document", `required: [documentType, employeeId, status]`) belonging to another app's fixtures. That is what the write hits.

  ⚠️ **The dangerous half is not the failure — it is that the fallback resolves ANOTHER APP'S SCHEMA BY SLUG.** This write failed only because the foreign schema happened to have stricter `required` fields. A foreign schema with looser ones would have accepted DocuDesk's audit row silently, into another app's register.

  Two fixes, neither of them here: OpenRegister's slug resolution should be register-scoped (or at least case-sensitive) rather than falling back to a global match, and the register→schema linkage needs repairing on this instance. Not attempted from this branch because the shared checkout was switched to another workstream mid-session, so re-running the import would have imported THEIR register file, not this one.

  `DocumentAgentService::record()` logs and swallows the failure by design — the file is already written and tagged, so throwing would report a failure that did not happen — and ADR-088's authoritative half, Hermiq's `artefact` record, is unaffected.

## Pre-existing debt cleared alongside this change

Two long-standing failures that predate this branch and were failing on
`development` itself:

- **22 PHPUnit errors** — `ObjectServiceInterface` and `ObjectEntityInterface`
  (ADR-084) had no test stub, so `createMock()` raised `UnknownTypeException`
  and every test touching `PolicyMatchService`, `PolicyRetroactiveService` and
  `MetadataService` errored out. Both interfaces are now stubbed **verbatim**
  from `openregister/lib/Contract/`, defaults included, rather than reduced to
  the methods today's tests happen to call: a mock built from a narrower
  signature accepts calls the real service would reject. Suite: **1361 tests,
  0 errors** (was 1361 / 22).
- **9 phpcs errors in `lib/`** — all docblock defects (a line comment between a
  docblock and an attribute list, a lower-case long description, and constructor
  promoted properties added without their `@param` lines). `lib/` now reports
  **0 errors**.

## Hydra-gate debt NOT cleared, and why

Four gates fail on this branch and fail **identically on `development`** — same
four names, same finding counts, so this change adds nothing to any of them.
They are a separate programme, deliberately not attempted here:

| Gate | Finding | Why not tonight |
|---|---|---|
| gate-7 `no-admin-idor` | 9 methods | Security-sensitive, and the gate's own text says the checker sees ONLY the controller body — a guard enforced in a service or query builder reads as a miss. It also warns that a `401` preamble is NOT the fix. Each candidate needs the storage path traced; guessing risks either a false fix or a real regression. |
| gate-19 `e2e-coverage` | 396 scenarios | A multi-day authoring programme, not a defect. |
| gate-26 `visual-coverage` | 6 components | Baselines are host-font/GPU specific; the repo's own config comments say a CI Linux runner will not byte-match a dev-container baseline. Generating them here would produce baselines that fail in CI. |
| gate-57 `orphaned-write-capability` | 3 methods | Small, but not reproducible locally without CI's diff scope, so a fix could not be verified before pushing. |
