# Design — document-editing-tools

Two curated agent-reachable operations on documents that already exist:
convert one, and edit one. Both suite-independent per ADR-087; both write a new
file rather than mutating the source.

## Declarative-vs-imperative decision (ADR-031)

| Behaviour | Path | Rationale |
|---|---|---|
| Format conversion | **Imperative** — `ConversionService` (existing cascade) | External integration: dispatches into Nextcloud's `IConversionManager` and, below it, into an office app or a headless `soffice` process. Owns no schema, no derived value, no lifecycle. Explicit ADR-031 external-integration exception, and the same one ADR-075 already grants document generation. |
| WOPI editing session | **Imperative** — `Editing/WopiClient` + `Editing/EditSessionService` | External integration across an instance boundary (HTTP + a stateful lock protocol). Same exception. |
| Document codec | **Imperative** — `Editing/PackageCodec` | Pure byte manipulation of ODF/OOXML packages. Not expressible declaratively at all. |
| Record of what an agent produced | **Declarative** — reuse `generatedDocument` | A converted or edited output is a generated document. It gets a `generatedDocument` row through the existing declarative path; this change adds **no** new schema and **no** new lifecycle, aggregation, calculation, notification or relation. |

No `x-openregister-{lifecycle,aggregations,calculations,notifications,relations,widgets}`
block is added or modified by this change.

## Where the office-suite divergence goes (ADR-087)

It mostly does not exist. The layers, and who brokers each:

| Layer | Brokered by | Per-suite code here? |
|---|---|---|
| Format conversion | `IConversionManager` (NC 31+) — Collabora, ONLYOFFICE and Euro Office all register as providers | **No.** `OfficeAppBackend` already dispatches uniformly. |
| Package read/write (ODF, OOXML) | Nothing — they are file formats | **No.** One codec, no suite in the call path. |
| Editing session (get bytes, lock, put bytes) | WOPI | **No.** One client. Availability probed by a real `CheckFileInfo`, never by an installed app id — Euro-Office ships `wopi.enable` false. |
| Live in-editor insertion | Nothing. Collabora `Send_UNO_Command`; ONLYOFFICE a plugin API | **Not built in this change** (ADR-087 §4). |

## The two curated tools

### `filinq.convertDocument`

Wraps the existing cascade. Input: a file id the acting user can read, plus a
target format. Output: a new file node plus a `generatedDocument` record.

- `scope: 'create'`, `readOnlyHint: false`, `destructiveHint: false`,
  `idempotentHint: false`.
- Backend selection is **not** an agent-supplied argument. The cascade picks;
  the response reports which backend claimed the conversion (`backend` field), so
  an operator reading the invocation log can tell an `IConversionManager`
  conversion from an mPDF fallback.
- Refuses when no backend claims the source MIME → target pair, with a structured
  error naming the source format. It never silently returns a lower-fidelity
  result without saying so.

### `filinq.editDocument`

Input: a file id, plus a list of anchored edits. Output: a **new document
version**, never a mutation of the input.

Session lifecycle, entirely inside `EditSessionService` — none of these steps is
an agent-callable tool, and the model never sees a lock id:

```
CheckFileInfo   -> capability probe + user's permissions on this file
Lock            -> X-WOPI-Lock held by the service
GetFile         -> package bytes
  codec: parse -> anchored blocks -> apply edits -> serialise in place
PutRelativeFile -> the NEW version
Unlock          -> in a finally, on every exit path
```

**Output mode: in-place by default, sibling on request.**

| Mode | WOPI call | Undo | Default |
|---|---|---|---|
| In-place | `PutFile` on the source | Nextcloud file versioning | **yes** |
| Sibling | `PutRelativeFile` | source never touched | on request |

In-place keeps one document identity and one history, and puts the undo where
users already look for it — the file's version list — rather than scattering
near-duplicate files through a folder. Review still works: Filinq has
`DocumentComparisonService`, and Collabora Online 26.04 renders version-to-version
redlines natively, so "what did the agent change?" is answered by diffing versions.

**`PutFile` has no merge semantics**, so in-place needs three guards, and all three
are required — none of them substitutes for another:

1. **The lock is held across the whole read-modify-write.** Not re-acquired for the
   write. A gap between `GetFile` and `PutFile` is a window in which a human's
   change is silently lost.
2. **`CheckFileInfo`'s `Version` is re-read immediately before `PutFile` and the
   write is refused if it moved.** This is optimistic concurrency using a field the
   protocol already returns, and it is what closes the residual race the lock alone
   does not: a change made outside a WOPI session. Refusing is correct here —
   merging is not something this codec can do, and guessing would be worse than
   stopping.
3. **The ADR-088 tag makes the change visible in Files**, so an in-place edit is
   something the user sees rather than something they must notice by reading the
   document.

**Configuration sets the ceiling; the tool argument may only narrow it.** An agent
may request sibling output when configuration says in-place; it may never request
in-place when configuration says sibling. An agent that can widen its own blast
radius has no blast radius.

**Verify versioning rather than assume it.** That a WOPI `PutFile` produces a
Nextcloud file version is the entire recovery story for the default mode. It is
asserted by test — a restorable prior version must exist after an agent edit —
because a recovery path nobody has watched work is not a recovery path.

**Lock contention is a refusal, not a wait.** If the document is open in an
editor the lock is already held. The tool returns a structured error naming the
condition. It does **not** poll, queue, retry, or use `UnlockAndRelock` to take a
lock it did not create — an agent stealing a human's editing lock is a data-loss
primitive.

## MEASURED 2026-08-15: the WOPI route cannot deliver the lock guard

**The session is in-process, not a WOPI client.** This reverses §The two curated
tools' `Lock`/`GetFile`/`PutFile` sequence and tasks 2.1–2.3. It is a measurement,
not a preference — read out of `richdocuments` 11.1.0 before any code was written:

- `WopiController::lock()` **ignores the `X-WOPI-Lock` value entirely** and takes
  an `ILockManager` lock of `ILock::TYPE_APP` owned by the literal string
  `richdocuments` (`richdocuments/lib/Controller/WopiController.php`).
- `files_lock`'s `LockService::lock()` **EXTENDS** an existing lock when
  `$known->getType() === $lockScope->getType() && $known->getOwner() === $lockScope->getOwner()`,
  and throws `OwnerLockedException` only otherwise.

So a WOPI client's lock is **indistinguishable from Collabora's own**. Guard 1
("the lock is held across the whole read-modify-write") and the lock-contention
refusal both become unachievable through that route: a document open in the
editor would have its lock *silently extended* rather than refusing — precisely
the data-loss case the lock exists to prevent.

An in-process `LockContext($file, ILock::TYPE_APP, 'filinq')` conflicts with
Collabora's lock (the refusal we want) **and** stays distinct from it (which WOPI
cannot give us). It also drops a self-addressed HTTP call carrying a bearer
token, which ADR-041's in-process posture argues against anyway.

What is *not* lost: the in-screen editor still interoperates, because
`ILockManager` is the same registry `richdocuments` writes its WOPI locks into.
`richdocuments` stops being a hard dependency for agent editing.

Consequences for the rest of this document: guard 2 (the `Version`
precondition) is unchanged in spirit and implemented as a **file etag** re-read
immediately before the write; guard 3 (the ADR-088 tag) is unchanged. Task 3.1's
`CheckFileInfo` probe is replaced by a format-support check plus a visible
`warnings[]` entry when no lock provider is installed. Phase 0.2's finding stands
but is no longer load-bearing: no WOPI token is minted at all.

## Anchors

Edits address **stable block anchors**, never array indexes: a human inserting a
paragraph shifts every index, and an agent's edit would land in the wrong place
with no error.

Preference order, resolved at Phase 0 (see §Verification):

1. ~~**Native persistent ids** — OOXML `w14:paraId`, ODF `xml:id`.~~ **RULED OUT
   BY MEASUREMENT, 2026-08-15.**
2. **Content-hash anchors** with a re-anchoring pass on every `open` — now the
   only option, not the fallback.

### 🔴 Phase 0.1 result: `paraId` does NOT survive Collabora

Measured, not inferred. A `.docx` carrying three known `w14:paraId` values was
round-tripped through Collabora's own LibreOffice core (the `soffice` binary
inside `richdocumentscode`'s `Collabora_Online.AppImage`, i.e. exactly the filter
a save goes through):

```
BEFORE: ['1A2B3C4D', '5E6F7A8B', '9C0D1E2F']
AFTER : []
```

All three paragraphs survived; **zero** `w14:` attributes did. The `w14`
namespace is still *declared* in the output — which is the trap: a reader
checking for the namespace would conclude the extension round-trips. It does not.
Collabora's OOXML export drops Microsoft's extension attributes wholesale.

**Consequences for the implementation:**

- The codec MUST use content-hash anchors, and `open` MUST re-anchor every time.
  There is no native id to fall back to.
- An anchor computed before a Collabora save is void after it, so an edit session
  cannot span a save it did not perform.
- ODF `xml:id` is untested and cannot be assumed to behave differently — but it
  does not rescue the `.docx` path either way, so it changes nothing about the
  design.

This is precisely why the gate existed: discovering it after the codec was built
would have meant rewriting the addressing model rather than choosing it.

This is the single largest unknown in the change and it is measured before any
codec code is written. It is recorded as a known unknown in ADR-087.

## The codec edits in place

Parse the package, mutate only the `w:p` / `text:p` nodes an edit targets,
rewrite that one part, leave every other entry in the package byte-identical.

The alternative — parse to a model and re-serialise — is what a general-purpose
document library does, and it silently drops comments, tracked changes, styles,
headers and embedded objects. Those losses are invisible in a diff of the visible
text and no test asserts on them, which is precisely why they must be structurally
impossible rather than guarded.

## Refusals

Extending the standing refusals in `filinq-mcp-adoption` §Refusals, which
remain in force unchanged:

- **No unguarded in-place write.** `PutFile` is called only while the session's own
  lock is held and only after `CheckFileInfo`'s `Version` has been re-confirmed
  unchanged. A version that moved is a refusal, never a merge attempt.
- **No escalation of output mode.** A tool argument may narrow to sibling output;
  it may never widen to in-place against configuration.
- **No lock stealing.** `UnlockAndRelock` is never used to acquire a lock the
  service does not already hold.
- **No editing a document under signature.** A file referenced by a
  `signingRequest` in any state other than cancelled is refused — editing the
  artefact behind a signature process invalidates the signature's meaning.
- **No editing anonymisation output.** A redacted document is a deliberate
  artefact; re-editing it risks re-identification.
- **No bytes in responses.** No tool returns document bytes, attachment bytes, or
  signature material. Responses carry file ids, metadata and structured status.
- **No live in-editor streaming** in this change (ADR-087 §4 permits it later,
  behind a probe, never as the only path).
- **No backend override.** The agent cannot pick a conversion backend, so it
  cannot be steered onto `soffice` as a process-execution primitive.

## Marking and recording (ADR-088)

Every file either tool produces is marked and recorded, in both directions:

- **On the artefact** — a Nextcloud system tag applied via `ISystemTagManager` /
  `ISystemTagObjectMapper` in the same code path that writes the file. Files is
  where a user actually looks, and a system tag is visible and filterable there, so
  "did an agent write this?" is answerable without opening an audit page.
- **In the record** — the `generatedDocument` row carries the invoking agent, and
  Hermiq's `tool` trace step for the invocation carries the produced **file id**.
  Without that, the oversight surface says `filinq.editDocument` succeeded and
  cannot say on what.

Two rules that are easy to get wrong:

- **Tagging is not a follow-up job.** It happens at write time. An artefact that
  exists untagged even briefly is one a user can mistake for their own, and a
  background pass that fails leaves it that way permanently.
- **A tagging failure is a failed operation.** If the file is written but the tag
  cannot be applied, the tool reports failure. Returning success on an unmarked
  file is the single outcome nothing downstream will ever re-examine.

The mark is a **hint, not a guarantee** — a user can remove a system tag. Hermiq's
record is the authoritative account; the tag is what makes that record discoverable
from the file. Neither the spec nor the tool description claims tamper-resistance.

No document content enters the record — file id, agent identity and outcome only,
consistent with the no-bytes refusal below.

## Grant-surface correctness

Both tools are two-segment curated ids (`filinq.convertDocument`,
`filinq.editDocument`), not three-segment derived ones. Per
`hermiq-prefer-tool-hints`, classification **fails closed** on a hint-less
non-3-segment id — a missing `scope` does not produce an unclassified tool, it
produces a write/destructive one. Both are write-classified either way, so the
hints are declared for *accuracy in the grant editor and the oversight log*, not
to unlock anything.

Consequence, which is intended: both tools are **default-denied**. An agent
reaches them only through an explicit exact-id grant, and each invocation passes
`FacadeToolInvoker`'s approval gate unless a grant waives it. They appear in the
agent detail page's Tool governance grant editor automatically —
`ToolOversightController::toolCatalog()` enumerates `ToolRegistryFacade::listTools([])`,
so nothing needs adding to the UI, but it MUST be verified rather than assumed.

## Verification

Phase 0 gates the rest. Both are measurements, not reviews:

1. **`w14:paraId` survival.** Author a `.docx` with known `paraId` values, open
   it in Collabora, save, diff the attributes. Repeat for ODF `xml:id`. A negative
   result selects content-hash anchors and adds a re-anchoring pass — it does not
   block the change, but discovering it after the codec is written does.
2. **Headless WOPI token issuance.** Determine, from richdocuments' token
   issuance, whether a background job can obtain a file-scoped token for its
   initiating user. If the only route is a service user with broad file access,
   **stop and escalate to an ADR** — that is a privilege concentration, not an
   implementation detail.

Then: `CheckFileInfo` probe returns absent on an instance with no WOPI host and
the capability degrades visibly; conversion verified against `IConversionManager`
with an office app installed *and* with the cascade falling through to PhpWord;
lock contention returns the structured refusal; both tool ids appear in the Tool
governance grant editor with write classification; scoped `phpcs` clean; zero new
PHPUnit failures against a self-measured baseline.

Portability is verified, not asserted: the conversion path is exercised on an
instance running Collabora **and** on one running Euro-Office (with
`wopi.enable` explicitly set true in `local.json`). If Euro-Office is not
available to test against, the portability claim is dropped from the spec rather
than shipped unmeasured.

## Seed data

None. This change introduces and modifies no OpenRegister schema; outputs are
recorded through the existing `generatedDocument` schema.

## DEFERRED_QUESTIONS

All three resolved 2026-08-15.

1. ~~Ship `editDocument` before Phase 0 returns, or split?~~ **RESOLVED: one
   change, Phase 0 as a hard gate inside it.** Splitting would cost a full review
   cycle for ~80 lines around an existing cascade. Two consequences to watch, since
   they are the price of not splitting: the conversion tool cannot merge until the
   editing work clears Phase 0, and this change sits **exactly at the 20-task cap
   with no headroom** — any further scope requires an ADR-032 split rather than an
   extra checkbox.
2. ~~**Where does the new version land?**~~
   **RESOLVED (2026-08-15): into the source file by default, creating a Nextcloud
   file version; sibling output selectable.** This reverses the original
   never-overwrite position. It is defensible now for reasons that did not hold
   when that position was written: the ADR-088 tag makes an in-place change visible
   in Files, the lock refusal already blocks writing under a live editing session,
   and the `Version` precondition closes the remaining out-of-session race. The
   undo moves from "a second file the user must find" to Nextcloud's own version
   list. Affects: proposal, design §Output mode, tasks 2.x/3.x, spec requirement.
3. ~~**Does an agent-produced version need a distinguishing marker?**~~
   **RESOLVED (2026-08-15): yes — a Nextcloud system tag applied at write time,
   plus the file id in Hermiq's record.** Both directions are required, not one:
   a tag with no log entry is unattributable, and a log entry with no file id is
   unactionable. Generalised to the fleet as ADR-088. See §Marking and recording.
