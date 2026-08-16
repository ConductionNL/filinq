# Tasks

## 1. The capability probe

- [ ] Add `lib/Service/Office/OfficeSuiteCapabilityService.php` probing WOPI via a real `CheckFileInfo`
- [ ] Resolve absent on every non-success: refused, timeout, non-2xx, unparseable body, or a 2xx body missing `BaseFileName`/`Size`
- [ ] Bound the probe with an explicit timeout and never propagate its failure to the caller
- [ ] Surface the result as a capability so the feature degrades visibly (ADR-075 §4)

Acceptance criteria:
- An installed-but-WOPI-disabled suite — Euro-Office's shipped default — resolves ABSENT.
- The probe does not run on app load; no app in this fleet resolves configuration there.
- The recorded reason distinguishes "probe failed" from "no suite installed".

## 2. Keep §5 true

- [ ] Add a conformance test rejecting any suite app id (`richdocuments`, `onlyoffice`, `documentserver`) in executable code under `lib/` and `src/`
- [ ] Make the test comment-aware, and prove it by pointing it at `EditSessionService`, which names `richdocuments` in prose and MUST pass

Acceptance criteria:
- The test passes at HEAD — it is a ratchet, not a fix.
- Deliberately introducing the identifier in executable code makes it fail, naming file and line. Verify this rather than assume it.

## 3. Both suites, reproducibly

- [ ] Add `docker-compose.office.yml` with `onlyoffice` and `collabora` profiles, neither running by default
- [ ] Write `docs/office-suite-setup.md` covering bring-up, connector config and verification for BOTH suites
- [ ] Document Euro-Office's `wopi.enable: false` default and how to turn it on
- [ ] Give the reader one command whose output distinguishes available from absent

Acceptance criteria:
- Starting the environment with no profile leaves both suites stopped.
- Following the doc from scratch reaches a probe that resolves available.

## 4. The measurement ADR-087 asked for

- [ ] Bring up ONLYOFFICE and confirm its WOPI probe resolves available
- [ ] Round-trip a `.docx`: save through the suite, re-read with `PackageCodec`, record which content-hash anchors still resolve
- [ ] Make the test report `not run` distinctly from `passed` when no suite is reachable, and verify the distinction appears in output a human reads
- [ ] Write the result into the change — including a negative one

Acceptance criteria:
- With no suite reachable the test reports NOT RUN and does not pass. A skipped measurement reporting green is the failure this task exists to prevent.
- If anchors do not survive, that is recorded as a finding about `PackageCodec` anchoring, not quietly dropped.
- If ONLYOFFICE cannot be brought up on this host, the arm is reported UNMEASURED and the portability claim is narrowed to match.

## 5. Verify

- [ ] Playwright E2E: read and edit a document with NO suite installed, proving no capability depends on one
- [ ] `composer check:strict` clean; hydra gates measured against the base run, not against zero

Acceptance criteria:
- The no-suite E2E is the real portability proof — it is the configuration Euro-Office users who have not enabled WOPI actually run.
