<!--
SPDX-License-Identifier: EUPL-1.2
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
-->

# Setting up an office suite for DocuDesk

## Read this first: DocuDesk does not need one

Reading a document, editing a paragraph, changing style, writing metadata and
embedding a chart all go through DocuDesk's in-package codec. No office suite is in
that call path (ADR-087 §2). If you install nothing from this document, those
capabilities still work.

An office suite adds two things:

1. **Format conversion providers.** Each suite registers with Nextcloud's
   `IConversionManager`, and DocuDesk's cascade picks up whatever is registered
   (ADR-087 §1). Adding a suite is zero code.
2. **Editing sessions over WOPI**, plus a realistic target for measuring what a real
   save does to a document.

A feature that only works when one named suite is present would be lock-in we
authored ourselves, which is the thing Euro-Office exists to escape. If you find
one, it is a bug — see the conformance test in
`tests/Unit/Office/SuiteIndependenceTest.php`.

---

## The one thing that will waste your afternoon

> **Euro-Office ships with WOPI disabled.**

`wopi.enable` is `false` by default in Euro-Office's `local.json`. This means:

- the container starts,
- the port answers,
- Nextcloud's admin page shows the server connected and **green**,
- and WOPI serves nothing at all.

Every "is it working?" check you would reach for by instinct — is the app enabled,
is the container up, does the URL load — returns **yes** in exactly this state.
That is why ADR-087 §3 says the probe is a successful `CheckFileInfo` and not
anything else, and why DocuDesk's capability resolves **absent** unless it gets one.

Verify with the probe (below), not with the admin page.

---

## Quick start

Both suites live in `docker-compose.office.yml` in this repository, each behind its
own profile. Neither starts by default.

```bash
# Euro-Office / ONLYOFFICE
docker compose -f docker-compose.office.yml --profile onlyoffice up -d

# Collabora
docker compose -f docker-compose.office.yml --profile collabora up -d

# Stop either
docker compose -f docker-compose.office.yml --profile onlyoffice down
```

Both services join `conduction-network`, so `nextcloud` resolves by name from inside
them and vice versa. If your stack uses a different network, change it in the
overlay — check yours rather than assuming:

```bash
docker inspect nextcloud --format '{{range $k,$v := .NetworkSettings.Networks}}{{$k}} {{end}}'
```

---

## Euro-Office / ONLYOFFICE

Euro-Office is the ONLYOFFICE fork announced 2026-03-27 by a nine-company European
consortium (IONOS, Nextcloud, Proton, XWiki, OpenProject, EuroStack, Soverin,
Abilian, BTactic), GA 2026-06-09, and is now the engine behind Nextcloud Office. It
is API-compatible with ONLYOFFICE Document Server, which is what the overlay runs —
the upstream image is public, the consortium build is not separately published for
local development.

### 1. Start it

```bash
docker compose -f docker-compose.office.yml --profile onlyoffice up -d
docker inspect docudesk-onlyoffice --format '{{.State.Health.Status}}'   # want: healthy
```

First boot takes 1–3 minutes. The port answers well before the converters are ready,
which is why the healthcheck greps for the literal `true` from `/healthcheck` rather
than accepting a 200.

### 2. Connect Nextcloud

```bash
docker exec nextcloud php occ app:install onlyoffice
docker exec nextcloud php occ app:enable onlyoffice
docker exec nextcloud php occ config:app:set onlyoffice DocumentServerUrl \
    --value="http://docudesk-onlyoffice/"
```

`DocumentServerUrl` is resolved **from inside the Nextcloud container**, so it is the
container name — not `localhost:8092`, which from Nextcloud's perspective is
Nextcloud itself. This is the same class of mistake as the Hermiq
`mcp_run_base_url` trap: a browser-facing origin used where a container-internal one
is needed, failing silently.

### 3. Enable WOPI — the step that is not optional

```bash
docker exec nextcloud php occ config:app:set onlyoffice enableSharing --value="true"
```

For a Euro-Office deployment proper, set `wopi.enable` to `true` in the server's
`local.json` and restart it. On the ONLYOFFICE image used here, WOPI is served at
`/hosting/wopi` once the app is connected.

### 4. Verify with the probe

```bash
docker exec nextcloud php occ docudesk:office:probe
```

Expected when it works:

```
WOPI: available (suite reported: ONLYOFFICE/Euro-Office)
```

Expected when the suite is installed but WOPI is off — the state this document
exists to warn about:

```
WOPI: absent (probe failed: CheckFileInfo returned 404)
```

Note the second is a **success** of the probe, not a failure of it. Absent is the
correct answer and the feature degrades visibly rather than failing in a user's
hands.

---

## Collabora Online

LibreOffice-based, the incumbent Nextcloud Office engine, and unlike Euro-Office it
serves WOPI out of the box.

### 1. Start it

```bash
docker compose -f docker-compose.office.yml --profile collabora up -d
docker inspect docudesk-collabora --format '{{.State.Health.Status}}'    # want: healthy
```

### 2. Connect Nextcloud

```bash
docker exec nextcloud php occ app:install richdocuments
docker exec nextcloud php occ app:enable richdocuments
docker exec nextcloud php occ config:app:set richdocuments wopi_url \
    --value="http://docudesk-collabora:9980"
docker exec nextcloud php occ richdocuments:activate-config
```

### 3. The `aliasgroup1` regex

`aliasgroup1` in the overlay tells Collabora which host it will accept WOPI
callbacks from. It is a **regular expression**, so dots are wildcards unless
escaped. An unescaped `aliasgroup1=http://nextcloud.local` also matches
`http://nextcloudXlocal`. Escape them.

### 4. Verify

```bash
docker exec nextcloud php occ docudesk:office:probe
curl -s http://localhost:9980/hosting/discovery | head -5     # should be WOPI XML
```

---

## Troubleshooting

| Symptom | Cause |
|---|---|
| Admin page green, editing fails | WOPI disabled. Euro-Office's default. Run the probe. |
| Probe says absent, container healthy | `DocumentServerUrl` / `wopi_url` points at a browser origin. From inside Nextcloud, `localhost` is Nextcloud. |
| Probe times out | The suite is on a different docker network. Check with `docker inspect`. |
| Conversion works, sessions do not | Expected and fine. Conversion goes through `IConversionManager`; sessions need WOPI. |
| `healthcheck` never goes healthy | First boot can take 3 minutes. After that, check `docker logs docudesk-onlyoffice`. |

---

## What is measured, and what is not

DocuDesk carries a round-trip test that opens a document, has a real suite save it,
re-reads it through `PackageCodec` and asserts which content-hash anchors still
resolve. ADR-087 lists anchor stability as a **known unknown** and says to measure it
before building on §2.

That test reports three outcomes: `passed`, `failed`, and **`not run`**. The third is
deliberate. A test that silently skips when no suite is present reports green, and a
green "anchors survive" that never opened a document is worse than no test — it
converts an open question into a false answer.

If you are reading this because you want the measurement, start a suite first and
check the test output says it ran.
