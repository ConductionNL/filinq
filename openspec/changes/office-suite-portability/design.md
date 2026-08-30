## Context

Filinq's document path does not use an office suite. `PackageCodec` opens the
ODF/OOXML package, rewrites the targeted paragraph's byte range, and leaves every
other part byte-identical. Nothing in that path talks to Collabora, ONLYOFFICE or
LibreOffice. Conversion is the only place a suite participates, and it participates
through `IConversionManager`, which Nextcloud brokers.

That means portability is mostly a property the code *already has* — and an
unguarded, unmeasured one. Three concrete gaps:

1. **No probe exists.** `grep -rl 'CheckFileInfo\|wopi' lib/` returns nothing, so
   there is no code that could tell an installed-but-disabled suite from a working
   one.
2. **Nothing prevents regression.** `richdocuments` appears in `lib/` only in
   comments today. That is a fact about HEAD, not a constraint.
3. **The round-trip has never happened.** ADR-087 lists anchor stability as a known
   unknown and says to measure it. Content-hash anchors were chosen *because*
   `w14:paraId` was expected to be regenerated on save — but "expected" is the
   operative word.

One further constraint shapes where things go: `.github/` is a **separate
repository**, mounted here as a submodule. Adding office-suite services to the fleet
compose would put a Filinq concern into a shared repo and split this change across
two review surfaces.

## Goals / Non-Goals

**Goals:**

- A capability that is honest about whether WOPI actually works on this instance.
- A guard that keeps §5 true rather than merely observing that it is true.
- A reproducible environment for both suites, opt-in.
- The anchor-stability measurement ADR-087 asked for, reported honestly either way.

**Non-Goals:**

- Building a WOPI client. Filinq edits packages directly; it does not need a WOPI
  session, and ADR-087 §3 governs sessions we do not currently open. The probe
  reports availability for the *feature-gating* purpose ADR-075 §4 describes.
- Live in-editor manipulation (Collabora `postMessage` / ONLYOFFICE plugin API).
  ADR-087 §4 permits it as a non-portable enhancement; it is not required, and
  building it would create the lock-in the ADR exists to avoid.
- Supporting a fourth suite explicitly. Under §1 that is no work: a suite that
  registers with `IConversionManager` is picked up by the cascade.
- Changing `PackageCodec` or the conversion cascade.

## Decisions

### D1 — The probe is `CheckFileInfo`, and everything short of it is "absent"

Availability is the conjunction of: the endpoint answers, the status is 2xx, the
body parses as JSON, and the body carries the fields `CheckFileInfo` is defined to
return (`BaseFileName`, `Size`).

Each weaker check was considered and rejected:

- *App id enabled* — ONLYOFFICE ships `wopi.enable: false`; installed proves nothing.
- *Port reachable* — a running document server with WOPI off answers the port.
- *Any 2xx* — an error page or a login redirect can be a 200.

Failing to absent rather than to available is deliberate. A wrong "absent" hides a
feature that would have worked; a wrong "available" ships a control that fails in a
user's hands. This project has met the second failure repeatedly and it is the more
expensive one.

### D2 — The §5 guard is a test, not a gate

A hydra gate would live in `.github`, a separate repo, and would apply fleet-wide to
apps that legitimately *are* an office integration. The constraint is Filinq's, so
it lives in Filinq's suite.

The check must ignore comments. A file explaining *why* it does not depend on
`richdocuments` — which `EditSessionService` does at length, because the WOPI lock
reasoning is not obvious — must not be flagged. A naive `grep richdocuments lib/`
would fail today against correct code, and the natural response to that would be to
delete the explanation, making the codebase worse.

### D3 — Compose overlay in this repo, both suites, neither by default

`docker-compose.office.yml` here rather than in `.github/`. Two profiles,
`collabora` and `onlyoffice`, so a developer opts into one, the other, or neither.
Nothing changes for anyone who does not ask for it — which matters because
ONLYOFFICE is ~1.5 GB and this host runs 30 containers already.

### D4 — The round-trip test reports three outcomes, not two

`passed` / `failed` / **`not run`**. The third is the point.

A test that silently skips when no suite is present reports green, and a green
"anchors survive the round-trip" that never opened a document is worse than no test:
it converts an open question into a false answer. The distinction must survive into
the output a human reads, not only into an internal state.

This is the same shape as the E2E session gate already in this repo, which skips
only on a specific, named cause and throws on every other.

### D5 — Measure, then report, including a negative result

If content-hash anchors do **not** survive an ONLYOFFICE round-trip, that is a
finding about `PackageCodec`'s anchoring strategy and it gets written down as one.
The purpose of the measurement is to replace an assumption, and an assumption is
equally replaced by a disappointing answer.

If ONLYOFFICE cannot be brought up on this host at all — a real possibility, since
Docker-on-WSL has silently emptied bind mounts here before — the arm is recorded as
unmeasured. ADR-087's own instruction for an unverified portability claim is to drop
the claim, not to ship it.

## Seed Data (ADR-001)

**None.** This change introduces and modifies no OpenRegister schemas. Its fixtures
are a single `.docx` used by the round-trip test, already present in the E2E suite.

## Declarative-vs-imperative decision (ADR-031)

| Behaviour | Path | Rationale |
|---|---|---|
| WOPI capability probe | **Imperative** | A side-effecting call across an instance boundary — the ADR-031 external-integration exception, named explicitly in ADR-087's own closing note. |
| §5 conformance check | **Imperative** | A test over source files; not a runtime behaviour at all. |

No behaviour here matches a declarative category (lifecycle, aggregation, derived
field, notification, relation, widget), so no `lib/Settings/filinq_register.json`
patch is appropriate.

## Risks / Trade-offs

**ONLYOFFICE may not come up on this host.** ~1.5 GB image, 30 containers already
running, and a Docker-on-WSL history of silently emptying bind mounts. Mitigation is
D4: the test distinguishes *not run* from *passed*, so a failure to launch produces
an honest gap rather than a false green.

**The probe adds a network call to a capability resolution.** Bounded by an explicit
timeout and resolving absent on expiry. It must never sit on a hot path
uncached — and note that no app in this fleet resolves configuration on app load, so
the probe must not either.

**A negative anchor-stability result would be expensive.** If anchors do not survive,
the anchored-edit design needs a re-anchoring pass. That cost is real, but it is
already owed — ADR-087 has been carrying it as a known unknown, and discovering it
in a test is far cheaper than discovering it in a user's document.

**Two suites doubles the setup surface to keep current.** Accepted: the alternative
is documenting one and claiming portability to the other, which is the claim this
change exists to stop making.
