---
status: in-progress
---

# office-suite-portability Specification

**OpenSpec changes**
- office-suite-portability

## Purpose

Defines how DocuDesk detects an office suite, what it refuses to assume about one,
and what is guaranteed to work with no suite present at all.

The governing decision is ADR-087: office-suite divergence is **brokered, not
driven**. Conversion goes through `IConversionManager`; format manipulation is
suite-independent in-package XML editing; editing sessions use WOPI and only WOPI;
and availability is capability-probed per instance, never assumed.

## Requirements

### Requirement: WOPI availability MUST be probed, never inferred from installation

Availability MUST be determined by a real `CheckFileInfo` whose response parses and
carries the fields WOPI requires. Anything less — connection refused, timeout,
non-2xx, unparseable body, missing `BaseFileName` or `Size` — MUST resolve absent.

Measured on `onlyoffice/documentserver` 2026-08-16 with WOPI at its shipped default:
container `healthy`, `/healthcheck` returning `true`, `/` returning 302, and
`/hosting/discovery` returning **404**. Every instinctive check says yes while WOPI
serves nothing.

#### Scenario: An installed suite with WOPI disabled resolves absent

- **GIVEN** a running, healthy, reachable suite whose WOPI is disabled
- **WHEN** the capability is probed
- **THEN** it MUST resolve absent
- **AND** the reason MUST record the probe failure rather than claiming no suite is installed

#### Scenario: A genuine CheckFileInfo success resolves available

- **GIVEN** the endpoint returns 200 with `BaseFileName` and `Size`
- **WHEN** the capability is probed
- **THEN** it MUST resolve available

### Requirement: No document capability may require a specific office suite

Every capability DocuDesk exposes over a document MUST be reachable through the
suite-independent path. No file under `lib/` or `src/` may reference a suite's app
id in executable code; the same identifier in a comment or in human-readable prose
is documentation, not a dependency.

#### Scenario: Reading and editing work with no office suite installed

- **GIVEN** no office suite is installed
- **WHEN** a document is read and a paragraph edited
- **THEN** both MUST succeed through the in-package codec

#### Scenario: Prose naming a suite is not a dependency

- **GIVEN** a translated label listing which suites give the best conversion fidelity
- **WHEN** the conformance test runs
- **THEN** it MUST NOT flag that label, because a check that pressures the removal of true documentation is measuring the wrong thing

### Requirement: Anchor stability across a suite round-trip MUST be measured, not assumed

A test MUST round-trip a document through a real suite and assert which content-hash
anchors still resolve. It MUST first assert the suite genuinely rewrote
`word/document.xml`, because a same-format conversion can be a passthrough and a
comparison of a document with itself proves nothing.

When no suite is reachable the test MUST report **not run** and MUST NOT pass.

#### Scenario: A vacuous round-trip is rejected

- **GIVEN** the suite returned a package whose `word/document.xml` is byte-identical to the input
- **WHEN** the test runs
- **THEN** it MUST fail, naming the round-trip as vacuous, rather than reporting that anchors survived

#### Scenario: An absent suite yields "not run", never a pass

- **GIVEN** no suite is reachable
- **WHEN** the test runs
- **THEN** it MUST report that the measurement did not run, distinguishably from a pass

### Requirement: Both supported suites MUST be reproducibly documented

The repository MUST document bring-up, connection and probe-based verification for
Collabora and ONLYOFFICE, MUST state ONLYOFFICE's WOPI-disabled
default, and MUST start neither suite by default.

#### Scenario: Neither suite starts by default

- **GIVEN** the environment is started with no profile selected
- **WHEN** running containers are listed
- **THEN** neither office suite MUST be running

### Requirement: A suite MUST NOT be claimed as supported until it has been run

A suite may be named as a portability *target* freely. It MUST NOT be described as
supported, verified, or tested unless that specific product has been started and
probed. A measurement taken against one suite MUST be attributed to the suite it was
taken against.

Two products being related — even correctly — does not transfer a measurement from
one to the other. Whether they are related is itself a claim that needs a source
outside the document making it.

Measured failure, 2026-08-16: the portability work ran
`onlyoffice/documentserver`, labelled it "Euro-Office / ONLYOFFICE" throughout its
documentation, compose file and pull-request text, and justified the substitution by
citing ADR-087 — which is where the "Euro-Office is an ONLYOFFICE fork" claim had
been introduced by the same programme days earlier. **At the time of that
publication Euro-Office had never been installed or run**, and the WOPI 404→200
flip, the anchor round-trip and the chart render published under its name were all
ONLYOFFICE results.

Later the same day Euro-Office WAS started and probed on its own image
(`ghcr.io/euro-office/documentserver`), with its own connector app and its own
verification run — recorded in `docs/office-suites/eurooffice.md`. That does not
retroactively support the earlier claims, and it does not transfer the ONLYOFFICE
anchor round-trip or chart-fidelity results, which remain unrepeated on
Euro-Office and are listed there as unverified. It is stated here because leaving
"never installed or run" standing after the product has been run would be the same
defect in the opposite direction: a document reporting something other than what
was measured.

#### Scenario: A measurement names the product it was taken on

- **GIVEN** a finding measured against a specific office suite image
- **WHEN** it is written into documentation or an ADR
- **THEN** it MUST name that image or product
- **AND** MUST NOT be attributed to a different suite believed to be related

#### Scenario: An unexercised suite is listed as unexercised

- **GIVEN** a suite named as a portability target that has never been started
- **WHEN** the supported-suite status is documented
- **THEN** it MUST be recorded as not installed, not configured and not verified
- **AND** MUST NOT appear in a list of verified suites

#### Scenario: A document may not be its own evidence

- **GIVEN** a claim introduced by an ADR in this programme
- **WHEN** that same programme relies on the claim to justify a substitution
- **THEN** the reliance MUST be treated as unsupported, because the ADR is where the claim entered rather than a source corroborating it
