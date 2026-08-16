## ADDED Requirements

### Requirement: WOPI availability MUST be probed, never inferred from installation

The system MUST determine WOPI availability by issuing a real `CheckFileInfo`
request and treating only a well-formed success as *available*. The presence of an
office app — its app id being enabled, its container running, its port answering —
MUST NOT be accepted as evidence.

This is not a hypothetical distinction. Euro-Office ships `wopi.enable` **false** in
`local.json`, so an installed, running, reachable Euro-Office serves no WOPI at all.
An "is the app installed" check reports available and every subsequent editing
session fails at use time.

A probe that cannot complete — connection refused, timeout, non-2xx, unparseable
body, or a 2xx body missing the required `BaseFileName` / `Size` fields — MUST
resolve **absent**. Absent is the safe answer: it degrades the feature visibly per
ADR-075 §4, whereas a wrong "available" produces a capability that exists in the UI
and fails in the hands of a user.

#### Scenario: An installed suite with WOPI disabled resolves absent

- **GIVEN** an office suite app is installed and enabled
- **AND** its WOPI endpoint is disabled, as Euro-Office ships by default
- **WHEN** the capability is probed
- **THEN** the capability MUST resolve absent
- **AND** the reason MUST record that the probe failed, not that the app was missing

#### Scenario: A reachable endpoint returning a malformed body resolves absent

- **GIVEN** the WOPI endpoint answers `200 OK` with a body that is not valid JSON
- **WHEN** the capability is probed
- **THEN** the capability MUST resolve absent
- **AND** the system MUST NOT treat the 2xx status alone as success

#### Scenario: A 2xx body missing required fields resolves absent

- **GIVEN** the WOPI endpoint returns valid JSON lacking `BaseFileName`
- **WHEN** the capability is probed
- **THEN** the capability MUST resolve absent, because a `CheckFileInfo` response without the fields the protocol requires cannot support a session

#### Scenario: A genuine CheckFileInfo success resolves available

- **GIVEN** the WOPI endpoint returns `200 OK` with a JSON body carrying `BaseFileName` and `Size`
- **WHEN** the capability is probed
- **THEN** the capability MUST resolve available
- **AND** the probe MUST record which suite answered, when the suite identifies itself

#### Scenario: The probe never blocks the request that triggered it

- **GIVEN** a WOPI endpoint that does not answer
- **WHEN** the capability is probed during a user-facing request
- **THEN** the probe MUST time out within a bounded interval and resolve absent
- **AND** MUST NOT propagate the failure as an error to the caller

### Requirement: No document capability may require a specific office suite

Every capability DocuDesk exposes over a document MUST be reachable through the
suite-independent path — `IConversionManager` for conversion, in-package XML editing
for manipulation. A capability that works only when one named suite is present is
prohibited (ADR-087 §4, §5).

No file under `lib/` or `src/` may reference a suite's app id (`richdocuments`,
`onlyoffice`, `documentserver`) outside a comment. This holds at HEAD and the
requirement exists to keep it holding: nothing currently prevents a future change
from reintroducing the dependency, and the failure would be invisible until a
deployment without that suite.

#### Scenario: Reading and editing work with no office suite installed

- **GIVEN** no office suite is installed on the instance
- **WHEN** a document is read and a paragraph is edited
- **THEN** both MUST succeed through the in-package codec
- **AND** no capability MUST report itself unavailable on account of the missing suite

#### Scenario: A suite app id in executable code is rejected

- **GIVEN** a source file under `lib/` or `src/` names a suite app id in executable code
- **WHEN** the conformance test runs
- **THEN** it MUST fail and name the file and line
- **AND** the same identifier inside a comment MUST NOT fail, because explaining why a suite is not depended upon is not a dependency

### Requirement: Anchor stability across a suite round-trip MUST be measured, not assumed

ADR-087 records anchor stability as a known unknown and instructs that it be
measured before building on §2. DocuDesk already builds on §2 with content-hash
anchors, chosen on the expectation that `w14:paraId` would not survive a save.

The system MUST carry a test that opens a document, has a real office suite save it,
re-reads it through `PackageCodec`, and asserts which anchors resolved.

When no suite is available to perform the round-trip, the test MUST report itself as
**not run** and MUST NOT pass. A skipped measurement that reports green is
indistinguishable from a measurement that succeeded, and that is the specific
failure this requirement exists to prevent.

#### Scenario: The round-trip is measured when a suite is present

- **GIVEN** an office suite is reachable and its WOPI probe resolves available
- **WHEN** a document is saved through that suite and re-read
- **THEN** the test MUST record, per paragraph, whether its content-hash anchor still resolves
- **AND** MUST fail if an anchor for unmodified content no longer resolves

#### Scenario: An absent suite yields "not run", never a pass

- **GIVEN** no office suite is reachable
- **WHEN** the round-trip test runs
- **THEN** it MUST report explicitly that the measurement did not run
- **AND** MUST NOT report success
- **AND** the result MUST be distinguishable in output from a run that measured and passed

### Requirement: Both supported suites MUST be reproducibly documented

The repository MUST document bring-up for Collabora **and** Euro-Office/ONLYOFFICE:
how to start each, how to connect it to Nextcloud, and how to confirm it works using
the capability probe rather than by looking at the admin UI.

The Euro-Office documentation MUST state that WOPI is disabled by default and show
how to enable it. Omitting that leaves a reader with a running suite, a green admin
page, and a feature that does not work — which is the exact confusion ADR-087 §3
calls out.

Both suites MUST be startable from a compose overlay in this repository, each behind
its own profile so neither runs by default.

#### Scenario: A developer can bring up either suite from the documentation alone

- **GIVEN** a developer with the standard development environment
- **WHEN** they follow the setup document for either suite
- **THEN** they MUST reach a state where the capability probe resolves available
- **AND** the document MUST give them a command whose output distinguishes available from absent

#### Scenario: Neither suite starts by default

- **GIVEN** the development environment is started with no profile selected
- **WHEN** the running containers are listed
- **THEN** neither office suite MUST be running
