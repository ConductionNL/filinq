<!--
SPDX-License-Identifier: EUPL-1.2
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
-->

# ONLYOFFICE

Ascensio System SIA's document server. WOPI host with a browser editor.

**This page is about ONLYOFFICE only.** Nothing here is evidence about Euro-Office,
Collabora or LibreOffice, even where the products are related.

## Deployment shape

| | |
|---|---|
| Document server | **sidecar container** `filinq-onlyoffice`, image `onlyoffice/documentserver:latest` (3.32 GB), host port **8092** |
| Nextcloud connector | app id **`onlyoffice`**, inside the `nextcloud` container at `/var/www/html/custom_apps/onlyoffice` — **not bind-mounted**, see [README](README.md) |

## 1. Start the server

```bash
docker compose -f docker-compose.office.yml --profile onlyoffice up -d
docker inspect filinq-onlyoffice --format '{{.State.Health.Status}}'   # want: healthy
```

First boot takes 1–3 minutes. The port answers well before the converters are ready,
so the healthcheck greps for the literal `true` from `/healthcheck` rather than
accepting a 200.

## 2. WOPI is OFF by default — measured on this image

`default.json` in `/etc/onlyoffice/documentserver/` ships:

```json
"wopi": { "enable": false }
```

With it unset, measured 2026-08-16:

| Signal | Result |
|---|---|
| container health | `healthy` |
| `GET /healthcheck` | `200`, body `true` |
| `GET /` | `302` |
| **`GET /hosting/discovery`** | **404** |

Container up, port answering, admin page green — and no WOPI at all. The overlay
sets `WOPI_ENABLED=true`, which flips discovery to **200**.

## 3. Install the connector

**Not on the Nextcloud appstore for NC 34** (`occ app:install onlyoffice` fails with
"not found on the appstore"). From the upstream release:

```bash
curl -sL -o onlyoffice.tar.gz \
  https://github.com/ONLYOFFICE/onlyoffice-nextcloud/releases/download/v10.1.2/onlyoffice.tar.gz
tar -xzf onlyoffice.tar.gz
docker cp onlyoffice nextcloud:/var/www/html/custom_apps/
docker exec nextcloud chown -R www-data:www-data /var/www/html/custom_apps/onlyoffice
docker exec nextcloud php occ app:enable onlyoffice
```

## 4. Three URLs, three directions

```bash
# BROWSER -> server. Must be reachable from the HOST. Not a container name.
docker exec nextcloud php occ config:app:set onlyoffice DocumentServerUrl \
    --value="http://localhost:8092/"

# NEXTCLOUD -> server. Resolved inside the container, so it IS the container name.
docker exec nextcloud php occ config:app:set onlyoffice DocumentServerInternalUrl \
    --value="http://filinq-onlyoffice/"

# SERVER -> NEXTCLOUD, to fetch and save the file.
docker exec nextcloud php occ config:app:set onlyoffice StorageUrl \
    --value="http://nextcloud/"
```

Setting only the first to the container name renders the editor and immediately
shows **"ONLYOFFICE cannot be reached"** — the *browser* cannot resolve
`filinq-onlyoffice`. Everything else is correct; only the direction is wrong.

## 5. Verified 2026-08-16

```
container running                          PASS  running
container health                           PASS  healthy
reachable from nextcloud                   PASS  HTTP 302
WOPI discovery                             PASS  200 at /hosting/discovery
conversion (docx->odt)                     PASS  ok at /ConvertService.ashx (json/url)
self-report (/healthcheck)                 ----  true
```

Additionally, end to end through the UI:

- opened `subsidiebesluit.docx` in the ONLYOFFICE editor at `/apps/onlyoffice/<fileId>`
- the Hermiq chat changed *"binnen zes weken"* → *"binnen vier weken"*
- verified on the **bytes**, and by having ONLYOFFICE convert the edited file to text
- anchor round-trip `docx → odt → docx` rewrote `word/document.xml` (1599 → 2590 B) and **all five content-hash anchors survived**, ordinal included
- a document with an embedded chart rendered to a 51,777 B PDF against 25,793 B without it

## Conversion API

JSON body naming a URL the server fetches itself:

```
POST /ConvertService.ashx
{"async":false,"filetype":"docx","key":"...","outputtype":"odt","url":"http://..."}
```

⚠️ `docx → docx` is a **passthrough** on this server: it returns a package whose
parts are byte-identical, only the ZIP container differs. Use a format change when
measuring whether the engine actually re-serialises.
