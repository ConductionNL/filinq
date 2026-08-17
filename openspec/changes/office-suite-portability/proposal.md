---
kind: code
---

## Why

ADR-087 decides that office-suite divergence is **brokered, not driven**: conversion
goes through `IConversionManager`, format manipulation is suite-independent, editing
sessions use WOPI and only WOPI, and availability is *capability-probed per instance,
never assumed*. DocuDesk was built to that decision. None of it has been measured
against a second suite.

Two of the four clauses already hold, and were verified rather than assumed:

| ADR-087 clause | State at HEAD |
|---|---|
| §1 conversion brokers through `IConversionManager` | **Holds.** `Service/Conversion/OfficeAppBackend.php` dispatches through it, with a declared cascade behind it. |
| §2 manipulation is suite-independent | **Holds.** `Service/Editing/PackageCodec.php` edits package XML in place; no suite is in the call path. |
| §3 availability is capability-probed | **Absent.** There is no probe of any kind. `grep -rl 'CheckFileInfo\|wopi' lib/` returns nothing. |
| §5 no hard dependency on a suite's app id | **Holds, unguarded.** Verified across `lib/` **and** `src/` by a tokeniser-based scan, not a grep. Nothing stops the next change reintroducing it. |

> The §5 row originally said "verified in `lib/`". That was an incomplete check — the
> conformance test, once written, immediately flagged `src/views/settings/Settings.vue`,
> which `lib/`-only grepping never looked at. The flag turned out to be a false positive
> (a translated `t()` label listing which suites give the best fidelity), but the
> narrower evidence would have supported the claim for the wrong reason.

So the gap is not the architecture. It is that **the portability claim has never been
exercised**, and that ADR-087 §3 names the exact trap: *"ONLYOFFICE ships WOPI
disabled, so 'the app is installed' is not the probe — a successful `CheckFileInfo`
is."* A codebase with no probe at all cannot make that distinction, and the failure
mode is the one this project keeps meeting — a capability that reports available and
then does nothing.

ADR-087 also records anchor stability as a **known unknown**: whether a suite
preserves paragraph identity across a save round-trip *"has not been measured.
Measure before building on §2."* DocuDesk already builds on §2, using content-hash
anchors rather than `w14:paraId` precisely because the latter was expected not to
survive. That expectation is still an expectation.

Finally, neither suite has any setup documentation in this repo, so a developer
cannot reproduce the environment the claim is about.

## What Changes

- **`OfficeSuiteCapabilityService`** probing WOPI by issuing a real `CheckFileInfo`
  and treating anything short of a well-formed success as *absent*. Installed-but-
  disabled resolves absent, which is ONLYOFFICE's shipped default.
- The probe result is surfaced as a capability so the feature degrades **visibly**
  per ADR-075 §4, rather than failing at use time.
- **A conformance test locking §5**: no `lib/` or `src/` path may name a suite's app
  id outside a comment. This currently passes; the test is what keeps it passing.
- **`docker-compose.office.yml`** in this repo — an overlay adding both
  `onlyoffice/documentserver` and `collabora/code`, each behind its own profile.
  Deliberately NOT added to the fleet `.github/docker-compose.yml`: an office suite
  is a DocuDesk concern, and `.github` is a separate repository.
- **`docs/office-suite-setup.md`** covering Collabora **and** ONLYOFFICE
  end to end — bring-up, connector configuration, the `wopi.enable` default that
  makes ONLYOFFICE look installed-but-inert, and how to verify with the probe rather
  than by eye.
- **The measurement ADR-087 asks for**: open a `.docx` in a non-Collabora suite, save
  it through that suite, re-read it with `PackageCodec`, and record whether
  content-hash anchors survive. The result is reported whichever way it lands.

## What was measured (2026-08-16, live ONLYOFFICE 3.32 GB image)

### ADR-087 §3 is correct, and the failure mode is exactly as described

Same container, same health, same port. The only variable is `wopi.enable`:

| Signal | `WOPI_ENABLED` unset (shipped default) | `WOPI_ENABLED=true` |
|---|---|---|
| container health | `healthy` | `healthy` |
| `GET /healthcheck` from Nextcloud | `200`, body `true` | `200`, body `true` |
| `GET /` | `302` (serving) | `302` (serving) |
| `GET /hosting/discovery` | **404** | **200** |
| `GET /hosting/capabilities` | **404** | **200** |
| `default.json` | `"wopi": {"enable": false}` | `"enable": true` |

Every instinctive "is it working?" check returns yes in the left-hand column while
WOPI serves nothing. That is the case for probing with a `CheckFileInfo`, now
demonstrated rather than argued.

### Anchor stability: content-hash anchors SURVIVE a real re-serialisation

ADR-087's known unknown, measured.

**First attempt was vacuous and is reported because it is the more useful half.** A
`docx → docx` conversion returned a file 1172 → 1394 bytes, which looks like a
rewrite. Comparing every package part by md5 showed **all four identical** — the
size delta was ZIP container overhead. ONLYOFFICE had passed the file through
without parsing it. "Anchors survived" was true and meaningless.

A `docx → odt → docx` round-trip forces the engine to parse and re-serialise:

```
word/document.xml before: 1599 bytes  md5 9a714c548b
word/document.xml after : 2590 bytes  md5 bfbd74955b     REWRITTEN
package parts: 14 -> 17
```

Against that genuinely-rewritten document, read back through the real
`PackageCodec`:

```
before                              after
b9f931ca9-1  Subsidiebesluit 2026   b9f931ca9-1  Subsidiebesluit 2026
b1a7a4f16-1  ...eight weeks.        b1a7a4f16-1  ...eight weeks.
bd36e1657-1  ...bold run...         bd36e1657-1  ...bold run...
b94c4021b-1  Kind regards,          b94c4021b-1  Kind regards,
b1a7a4f16-2  ...eight weeks.        b1a7a4f16-2  ...eight weeks.
```

All five identical, **including the occurrence ordinal** on the deliberately
duplicated paragraph. The bold run survived.

### `w14:paraId` is not merely unstable — it is often absent

Neither the input nor the output carried a single `w14:paraId`. PhpWord does not
emit them and ONLYOFFICE did not add them. So this run does **not** answer "does
`paraId` survive a save"; it answers something more decisive for the design — an
anchor scheme built on `paraId` would have had **nothing to anchor to at all** on an
ordinarily-generated document.

### The honest limits of this measurement

- It used the **conversion service**, not a WOPI `PutFile` from an editing session.
  That is the engine genuinely parsing and re-serialising, which is the strongest
  proxy available without minting per-file WOPI tokens — but it is not literally a
  user pressing save.
- One document, one suite, one format pair. Collabora is **not** measured here.

## Capabilities

### New Capabilities
- `office-suite-portability`: how DocuDesk detects an office suite, what it refuses
  to assume, and what is guaranteed to work without any suite present.

### Modified Capabilities

None. ADR-087 §1/§2 already hold in code, and this change adds their guard rather
than changing their behaviour.

## Impact

- **Code**: new `lib/Service/Office/OfficeSuiteCapabilityService.php`; capability
  registration in the existing bootstrap; no change to `PackageCodec` or the
  conversion cascade.
- **Infra**: new `docker-compose.office.yml` in this repo. ~1.5 GB image for
  ONLYOFFICE, opt-in behind a profile, off by default.
- **Docs**: new `docs/office-suite-setup.md`.
- **Risk carried explicitly**: the round-trip measurement depends on the ONLYOFFICE
  container running on this host. If it cannot be brought up, the anchor-stability
  result MUST be recorded as *unmeasured* — ADR-087's own instruction is to drop an
  unverified claim rather than ship it, and an unmeasured arm reported as passing is
  the failure this change exists to prevent.
