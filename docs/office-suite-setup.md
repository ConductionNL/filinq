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

### 2. Install the connector

The app is **not on the Nextcloud appstore for NC 34**. Install it from the
upstream release:

```bash
curl -sL -o onlyoffice.tar.gz \
  https://github.com/ONLYOFFICE/onlyoffice-nextcloud/releases/download/v10.1.2/onlyoffice.tar.gz
tar -xzf onlyoffice.tar.gz
docker cp onlyoffice nextcloud:/var/www/html/custom_apps/
docker exec nextcloud chown -R www-data:www-data /var/www/html/custom_apps/onlyoffice
docker exec nextcloud php occ app:enable onlyoffice
```

### 3. Three URLs, three directions — this is the step that bites

There are **three** connections, and they do not share an origin. Setting one and
assuming the others follow is the single most common way to end up staring at
*"ONLYOFFICE cannot be reached"*:

```bash
# 1. BROWSER  -> document server. The user's browser loads the editor from here,
#    so it must be an origin the HOST can reach. NOT a container name.
docker exec nextcloud php occ config:app:set onlyoffice DocumentServerUrl \
    --value="http://localhost:8092/"

# 2. NEXTCLOUD -> document server. Resolved inside the container, so it IS the
#    container name. `localhost` here is Nextcloud itself.
docker exec nextcloud php occ config:app:set onlyoffice DocumentServerInternalUrl \
    --value="http://docudesk-onlyoffice/"

# 3. DOCUMENT SERVER -> Nextcloud, to fetch and save the file.
docker exec nextcloud php occ config:app:set onlyoffice StorageUrl \
    --value="http://nextcloud/"
```

Measured 2026-08-16: with only `DocumentServerUrl` set to the container name, the
editor page renders and immediately shows **"ONLYOFFICE cannot be reached. Please
contact admin"** — because the *browser* cannot resolve `docudesk-onlyoffice`. The
container is healthy, Nextcloud can reach it, and the document server is serving
WOPI. Everything is right except the direction.

This is the same shape as the Hermiq `mcp_run_base_url` trap, and the inverse
mistake: there, a browser-facing origin was used where a container-internal one was
needed. Here a container-internal one was used where the browser needed it. Both
fail without saying which way round the problem is.

### 4. Enable WOPI — the step that is not optional

```bash
docker exec nextcloud php occ config:app:set onlyoffice enableSharing --value="true"
```

For a Euro-Office deployment proper, set `wopi.enable` to `true` in the server's
`local.json` and restart it. On the ONLYOFFICE image used here, WOPI is served at
`/hosting/wopi` once the app is connected.

### 5. Verify with the probe

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
| **"ONLYOFFICE cannot be reached" in the browser, container healthy** | `DocumentServerUrl` is a container name. The *browser* must be able to resolve it — see the three-URL section. |
| Editor loads but cannot save | `StorageUrl` wrong: the document server cannot reach Nextcloud to write the file back. |
| Admin page green, editing fails | WOPI disabled. Euro-Office's default. Run the probe. |
| Probe says absent, container healthy | `DocumentServerInternalUrl` points at a browser origin. From inside Nextcloud, `localhost` is Nextcloud. |
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

---

## Verified end to end, 2026-08-16

The full path — Nextcloud document, opened in Euro-Office, altered from the Hermiq
chat window — was exercised on `localhost:8080`. Recorded here because each step
below is a place it can fail silently.

| Step | Evidence |
|---|---|
| Document server up | `docker inspect docudesk-onlyoffice` → `healthy`; `/healthcheck` → `true` |
| WOPI actually served | `/hosting/discovery` → **200** with `WOPI_ENABLED=true`; **404** without it |
| Editor opens the file | page title becomes `subsidiebesluit.docx - Nextcloud`, editor iframe with toolbar |
| Tools reachable by the model | `tools/list` → 120 tools, 6 of them `docudesk.*Document*` |
| Chat drove the edit | runner log: `provider=anthropic model=claude-opus-4-8 governed=yes`, `exit=0` |
| The bytes changed | `word/document.xml` contains `vier weken`, no longer `zes weken` |
| Euro-Office reads the edit | converting the edited file through the document server yields text containing *"binnen vier weken"* |
| It is accountable | file carries the `Agent authored` tag; the pre-edit version is restorable |

### Two failures hit during that run, both worth knowing

**The LLM runner container must be up.** `hermiq-llm-runner` had stopped. The chat
accepted the message, showed no error, and simply never replied. There is no
"runner unavailable" surface — the message just sits there. Check
`docker ps --filter name=hermiq-llm-runner` before debugging anything else.

**The version precondition will refuse a second write** that reuses the version
returned by the first. That is correct: writing the file changes its etag, and the
tag write changes it again. Re-read before each write rather than caching a version
across calls.
