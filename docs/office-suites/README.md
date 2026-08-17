<!--
SPDX-License-Identifier: EUPL-1.2
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
-->

# Office suites — four products, four verifications

DocuDesk works with **no office suite at all**. Reading, editing, styling, metadata
and charts go through the in-package codec with nothing else in the call path
(ADR-087 §2). Everything here is additive.

Four suites are set up and verified **separately**. Nothing on one page is evidence
for another.

| Suite | Page | Shape | Sidecar | Verified |
|---|---|---|---|---|
| ONLYOFFICE | [onlyoffice.md](onlyoffice.md) | WOPI host + browser editor | `docudesk-onlyoffice` :8092 | ✅ |
| Euro-Office | [eurooffice.md](eurooffice.md) | WOPI host + browser editor | `docudesk-eurooffice` :8093 | ✅ server + connector; anchors/charts **not** repeated here |
| Collabora Online | [collabora.md](collabora.md) | WOPI host + browser editor | `docudesk-collabora` :9980 | ✅ |
| LibreOffice | [libreoffice.md](libreoffice.md) | **converter only** — no WOPI, no editor | `docudesk-libreoffice` :8094 | ✅ as converter |

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
bash docs/office-suites/verify-suite.sh onlyoffice  docudesk-onlyoffice  http://docudesk-onlyoffice
bash docs/office-suites/verify-suite.sh eurooffice  docudesk-eurooffice  http://docudesk-eurooffice
bash docs/office-suites/verify-suite.sh collabora   docudesk-collabora   http://docudesk-collabora:9980
bash docs/office-suites/verify-suite.sh libreoffice docudesk-libreoffice http://docudesk-libreoffice:2004
```

It needs a fixture the suites can fetch:

```bash
docker exec nextcloud sh -c 'mkdir -p /tmp/officefx'
docker exec nextcloud sh -c 'php -S 0.0.0.0:8123 -t /tmp/officefx >/tmp/officefx.log 2>&1 &'
# place any .docx at /tmp/officefx/probe.docx
```

And from Nextcloud's side, per suite:

```bash
docker exec nextcloud php occ docudesk:office:probe
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
