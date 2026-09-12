## Context

Filinq delegates detection to OpenRegister. `AnonymizationService::runExtraction()`
calls `OCA\OpenRegister\Service\TextExtractionService::extractFile()` and then
reads the entity relations back. OpenRegister owns the backend choice, the
probes and the availability, in
`lib/Service/Anonymisation/AnonymisationBackendService.php`. Its `getState()`
returns a `BackendState` with `entityRecognitionEnabled`, `activeMethod`,
`effectiveMethod` and a per-backend probe result for regex, presidio,
openanonymiser, llm and hybrid.

So the state filinq needs already exists, one app away, fully modelled. Filinq
built a client for it, `AnonymiserBackendStateClient`, and the client has
never read it: it resolves `OCA\OpenRegister\Service\AnonymisationBackendService`
while OpenRegister declares the class under a `\Anonymisation` segment.

The run path never asks. `AnonymizationService::runAnonymize()` runs the
prohibition gate and calls `DocumentAnonymizeRunner::run()`. Entities come in
as an argument, so an empty list is a legitimate input and the runner writes a
file from it.

## Goals / Non-Goals

**Goals:**

- Filinq knows which detector is live and says so on the run, not only in a
  settings panel.
- A run with no live detector produces no file.
- A run with a live detector that found nothing is a distinct, named outcome.
- The admin warning reflects the instance.

**Non-Goals:**

- Adding a detection backend. OpenRegister owns the backends and ships five.
- Deciding whether a specific backend is good enough for a specific Woo
  request. That is a policy judgement in the calling app.
- Changing the prohibition gate, the grondslagen summary, the review workbench
  or the batch pipeline beyond carrying the new field through.
- Retro-marking anonymisation records already written. They cannot be
  corrected and the change is forward only.

## Decisions

**D1. Unknown is a value.** The client returns three states, not two: a named
backend, detection off, or unknown. Today's fallback collapses unknown onto
`regex`, which is the strongest possible lie in this direction because `regex`
is a real backend that finds real BSNs.

**D2. Refuse on unknown, not just on off.** An anonymisation is a legal act. A
caller that cannot be told which detector ran cannot defend the output. The
cost of refusing is an error a human sees. The cost of proceeding is a
document published with names in it.

**D3. Zero entities from a live backend still writes a file.** A document can
genuinely contain nothing to redact. Refusing that would break the clean case
and teach operators to distrust the refusal. The result says which backend
looked and that it found nothing, and the caller decides. Dossiq already fails
closed on this outcome because a document assessed as deels openbaar has
something to remove.

**D4. The backend is read once per run and carried on the result.** Probing
per document would put an HTTP probe inside a batch loop.
`AnonymisationBackendService` already caches its probes, and the run reads the
state at the start and reports the value it acted on.

**D5. The settings warning is derived from the same read.** One source, one
value, one way to be wrong. The current warning is computed from a key that
does not exist in the payload it reads.

## Risks / Trade-offs

**A refusal is a behaviour change on a live path.** An instance running today
with detection disabled produces files. After this change it produces errors.
That is the correct direction and it must be in the release note, because an
operator who has been filing those outputs has a backlog to re-run.

**OpenRegister's probes can be stale.** They are cached, so a backend that
died a minute ago can still read as available and the run proceeds. This
change does not make filinq a monitor. It makes filinq report what
OpenRegister said at the time of the run, which is auditable, rather than
report nothing.

**A batch run can now fail part way.** The state is read once per run, so a
batch either starts or does not. A backend that fails mid-batch surfaces as
the per-document failures it already does.
