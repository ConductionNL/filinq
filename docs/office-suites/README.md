<!--
SPDX-License-Identifier: EUPL-1.2
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
-->

# Office suites — four products, four verifications

Filinq works with **no office suite at all**. Reading, editing, styling, metadata
and charts go through the in-package codec with nothing else in the call path
(ADR-087 §2). Everything here is additive.

Four suites are set up and verified **separately**. Nothing on one page is evidence
for another.

| Suite | Page | Shape | Sidecar | Verified |
|---|---|---|---|---|
| ONLYOFFICE | [onlyoffice.md](onlyoffice.md) | WOPI host + browser editor | `filinq-onlyoffice` :8092 | ✅ |
| Euro-Office | [eurooffice.md](eurooffice.md) | WOPI host + browser editor | `filinq-eurooffice` :8093 | ✅ server + connector; anchors/charts **not** repeated here |
| Collabora Online | [collabora.md](collabora.md) | WOPI host + browser editor | `filinq-collabora` :9980 | ✅ |
| LibreOffice | [libreoffice.md](libreoffice.md) | **converter only** — no WOPI, no editor | `filinq-libreoffice` :8094 | ✅ as converter |

## Why they are documented separately

On 2026-08-16 this repository ran `onlyoffice/documentserver`, labelled it
"Euro-Office / ONLYOFFICE", and reported the resulting measurements under a
Euro-Office heading. Euro-Office had never been started. The justification was a
claim in ADR-087 — written in the same programme days earlier — which was then cited
as the authority for treating the two products as one.

Separate pages, separate containers, separate connector apps and separate
verification runs are the smallest arrangement in which that cannot happen quietly.

**A measurement belongs to the product it was taken on.** Two suites being related —
even correctly — does not transfer a result between them.

## Where things actually run

This is two different deployment shapes, and mixing them up causes most setup
confusion:

**Document servers → sidecar containers.** Each has its own profile in
`docker-compose.office.yml` and joins `conduction-network`, so `nextcloud` resolves
by name in both directions. None starts by default:

```bash
docker compose -f docker-compose.office.yml --profile onlyoffice  up -d
docker compose -f docker-compose.office.yml --profile eurooffice  up -d
docker compose -f docker-compose.office.yml --profile collabora   up -d
docker compose -f docker-compose.office.yml --profile libreoffice up -d
```

**Connector apps → inside the `nextcloud` container**, at
`/var/www/html/custom_apps/<app>`.

> ⚠️ **The connector apps are NOT bind-mounted.** They live in the `nextcloud`
> Docker volume, not in `apps-extra`, and not in any git repository. `clean-env.sh`
> removes volumes and therefore removes them, and nothing reports the loss — the
> editor simply stops working. Re-run the install steps on each suite's page after
> an environment reset.
>
> This departs from the fleet convention where apps live in `apps-extra` and are
> bind-mounted through `.github/docker-compose.yml`. Making these bind mounts needs
> a change in that separate repository; until then, treat them as reproducible from
> the documented commands rather than as persistent.

## Verifying

One script, run once per suite, same eight checks each time:

```bash
bash docs/office-suites/verify-suite.sh onlyoffice  filinq-onlyoffice  http://filinq-onlyoffice
bash docs/office-suites/verify-suite.sh eurooffice  filinq-eurooffice  http://filinq-eurooffice
bash docs/office-suites/verify-suite.sh collabora   filinq-collabora   http://filinq-collabora:9980
bash docs/office-suites/verify-suite.sh libreoffice filinq-libreoffice http://filinq-libreoffice:2004
```

It needs a fixture the suites can fetch:

```bash
docker exec nextcloud sh -c 'mkdir -p /tmp/officefx'
docker exec nextcloud sh -c 'php -S 0.0.0.0:8123 -t /tmp/officefx >/tmp/officefx.log 2>&1 &'
# place any .docx at /tmp/officefx/probe.docx
```

And from Nextcloud's side, per suite:

```bash
docker exec nextcloud php occ filinq:office:probe
```

### Reading the output honestly

**Some checks are expected to fail for some suites, and that is not a defect.**
LibreOffice has no WOPI discovery because it is a converter, not a WOPI host.
Collabora's image ships no shell, so an in-container healthcheck cannot run.

Twice while building this harness a check failed and the failure was **mine, not the
suite's**:

- Collabora was reported "conversion unsupported" because the probe only tried
  ONLYOFFICE's `/ConvertService.ashx`. Collabora converts at `/cool/convert-to/<fmt>`
  with a multipart upload and works fine.
- Collabora was reported `unhealthy` because the compose healthcheck used
  `CMD-SHELL` against an image with no shell. The status was a fact about the
  healthcheck.

Before recording a suite as failing something, check that the instrument can succeed
on a suite of that shape.

---

## Which document types each suite edits

Measured on this instance, not copied from any suite's feature list. Re-measure
after a suite upgrade: these tables go stale silently, which is why the probe
that produces them ships with the app (`SupportedTypeProbe`).

| Type | Collabora | Euro-Office | ONLYOFFICE | LibreOffice\* |
|---|:---:|:---:|:---:|:---:|
| `odt` | ✅ | ✅ | ✅ | ✅ |
| `docx` | ✅ | ✅ | ✅ | ✅ |
| `doc` | ✅ | ❌ | ❌ | ✅ |
| `ods` | ✅ | ✅ | ✅ | ✅ |
| `xlsx` | ✅ | ✅ | ✅ | ✅ |
| `xls` | ✅ | ❌ | ❌ | ✅ |
| `odp` | ✅ | ✅ | ✅ | ✅ |
| `pptx` | ✅ | ✅ | ✅ | ✅ |
| `ppt` | ✅ | ❌ | ❌ | ✅ |
| `odg` | ✅ | ❌ | ❌ | ❌ |
| `csv` | ✅ | ✅ | ✅ | ✅ |
| `pdf` | ❌ | ✅ | ✅ | ✅ |

Measured 2026-08-18 · Collabora (LibreOffice lineage) · Euro-Office 1.0 ·
ONLYOFFICE 1.0 · LibreOffice 7.6.7.2

### Read the columns carefully — they do not mean the same thing

⚠️ **The LibreOffice column is a different measurement.** LibreOffice desktop has
**no server seam**: it exposes no WOPI discovery, so Filinq cannot open an
editing session against it at all. Its ticks are *conversion filters* — what
`soffice --convert-to` produces — which is useful for format conversion and
useless for in-place editing. A ✅ in that column never means "an agent can edit
this here".

The other three columns come from each suite's own WOPI discovery document,
counting `<action name="edit">` entries. That is the suite stating what it
edits, which is the closest thing to an authoritative answer available.

### What the differences actually cost you

**Legacy Microsoft formats (`doc`, `xls`, `ppt`) are Collabora-only.** A tenant on
Euro-Office or ONLYOFFICE cannot edit them, so no workflow, template or feature
may require them (ADR-087 §4). They resolve absent, visibly.

**Draw (`odg`) is Collabora-only** — the ONLYOFFICE lineage ships its own diagram
model rather than the ODF one.

**PDF editing exists only on the ONLYOFFICE lineage**, and Filinq restricts it
to annotation and form-fill regardless. A PDF is a final-form artefact; silently
rewriting its text produces something forgery-shaped.

**Euro-Office and ONLYOFFICE report identical sets.** That is expected —
Euro-Office is ONLYOFFICE lineage — and it is worth knowing that choosing between
them is a sovereignty and support decision, not a capability one.

### What Filinq itself can edit, which is less

The table above is what the *suite* can open. Filinq's own in-package codecs
now cover text, spreadsheets and presentations:

| Kind | Formats | Read | Edit | Style & layout |
|---|---|:---:|:---:|:---:|
| Text | `docx`, `odt` | ✅ | ✅ | ✅ (`list` is docx-only) |
| Spreadsheet | `ods`, `xlsx` | ✅ | ✅ cells | — |
| Presentation | `pptx`, `odp` | ✅ | ✅ shape text | — |

Addressing differs by kind, because the durable identity differs:

- **Text** uses content-derived anchors, so an anchor from an out-of-date read
  is refused rather than applied to the wrong paragraph.
- **Spreadsheets** use `Sheet!Cell`. A cell address is already a durable
  identity — insert a row and everything below shifts in a way the file format
  and the reader's mental model agree on.
- **Presentations** use slide id and shape id, **never position**. Slide order
  changes; ids do not.

⚠️ Three refusals worth knowing before you plan around them:

- Writing a literal over a cell holding a **formula** is refused unless that
  edit sets `replaceFormula`. The flag is per cell and is not carried across a
  bulk write.
- Dependent cells are reported **stale**, not recalculated. Filinq has no
  formula engine, and the difference is observable: after one write the same
  file showed `1218` in ODS (LibreOffice recalculated on open) and `290` in
  XLSX (it served the cached value).
- Macro-bearing packages, `.odb`, and PDF content rewriting are refused
  outright — and the macro check reads the **bytes**, so a package carrying
  VBA is refused even when it is named `.docx`.

`.odg` (Draw) has no codec, and Draw is Collabora-only in any case.

