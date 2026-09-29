## Why

Dossiq's Woo redaction hand-off reached filinq on 2026-09-09 (dossiq #2059).
Before that, `WOORedactionService` wrote `status: 'queued'` on each document
and made no call at all. It now calls
`AnonymizationService::extractAndDetectEntities()` and then
`anonymizeDocument()`, as the acting user, on the file id dossiq resolved.

On the dev instance the document came back byte-identical to the one that went
in. Filinq detected nothing, anonymised nothing, wrote an output file anyway,
and reported a successful anonymisation. A Woo officer had already assessed
that document as deels openbaar, so it has something to remove. Zero
detections did not mean the document was clean. It meant no detection backend
answered.

Filinq cannot currently tell anyone that, and the one place it tries is
broken in three ways.

**The backend lookup names a class that does not exist.**
`AnonymiserBackendStateClient::OR_SERVICE` is
`OCA\OpenRegister\Service\AnonymisationBackendService`. OpenRegister declares
that class at `OCA\OpenRegister\Service\Anonymisation\AnonymisationBackendService`.
The container resolve throws, the `catch (\Throwable)` returns
`['method' => 'regex', 'appApiInstalled' => false]`, and filinq never reads
OpenRegister's real state.

**The shape is wrong even if the name were right.** OpenRegister's
`getState()` returns a `BackendState` object whose `jsonSerialize()` carries
`entityRecognitionEnabled`, `activeMethod`, `effectiveMethod` and `backends`.
Filinq's client declares an array return and reads a `method` key that does
not exist in it.

**The result is a warning that is always on.**
`SettingsController` computes
`'showWarning' => ($backendState['method'] ?? 'regex') === 'regex'`. With the
fallback above that is true on every instance, whatever backend is actually
configured. A warning that never changes tells an admin nothing.

**And nothing on the run path consults it at all.** The only consumer of
`AnonymiserBackendStateClient` is `SettingsController`. `runAnonymize()` runs
the prohibition gate and hands straight to `DocumentAnonymizeRunner`. An
anonymisation with an empty entity list writes an output file and returns a
result that looks like every successful run.

Filinq is the fleet's anonymisation app. When it cannot detect, it has to say
so, and it has to say so on the path a caller uses, not only in an admin
settings panel.

## What Changes

- `AnonymiserBackendStateClient` resolves OpenRegister's real class and
  returns its real shape. When OpenRegister cannot answer, the client reports
  that it does not know, instead of reporting `regex`.
- The anonymisation result carries the detection backend that was in force
  for the run, so a caller can tell an empty detection from a disabled
  detector.
- An anonymisation run whose effective detection is off or unavailable is
  refused. It does not write an output file, because a copy of the input
  filed as an anonymised document is the failure this change removes.
- A run where detection was live and found nothing reports that outcome
  distinctly from a run that redacted something. It still writes the file,
  because a genuinely clean document is a real result, and it says which
  backend looked.
- The admin warning reads the real state and names the active method.

## Capabilities

### New Capabilities

- `anonymisation-detector-honesty`: an anonymisation run reports which
  detection backend was in force, and refuses rather than producing an
  unredacted output file when none was.

### Modified Capabilities

<!-- None. The anonymization requirements are unchanged; this capability adds
     the detector-state contract around them. -->

## Impact

- `lib/Service/AnonymiserBackendStateClient.php`,
  `lib/Service/AnonymizationService.php`,
  `lib/Service/DocumentAnonymizeRunner.php`,
  `lib/Controller/SettingsController.php` and
  `lib/Controller/AnonymizationController.php`.
- Consumers: dossiq's `FilinqRedactionClient` already fails closed on a
  zero-entity run and names it `no_entities_detected`, but it infers that from
  the entity count because filinq tells it nothing else. It reads the backend
  from the result once this ships. Filinq's own review workbench and batch
  runs gain the same signal.
- No schema change and no migration. Behaviour changes for one case: a run
  with no live detector now fails instead of producing a file. That is the
  point of the change and it must be called out in the release note.
- Depends on nothing. OpenRegister's `AnonymisationBackendService` already
  ships the state, the probes and the per-backend availability.
