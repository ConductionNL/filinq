<!--
SPDX-License-Identifier: EUPL-1.2
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
-->

# Euro-Office

The sovereign office suite launched by a European industry initiative (IONOS,
Nextcloud, EuroStack, XWiki, OpenProject, Soverin, Abilian, BTactic and others),
generally available 9 June 2026, and the engine behind Nextcloud Office.

**This page is about Euro-Office only.** It is a separate product with its own image,
its own connector app and its own container. Nothing here is inherited from the
ONLYOFFICE page.

> ### A retraction
>
> Earlier revisions of this repository asserted Euro-Office *"is the ONLYOFFICE fork
> … API-compatible with ONLYOFFICE Document Server, which is what the overlay runs"*
> and then ran ONLYOFFICE while reporting the results as Euro-Office. **Euro-Office
> had never been started.** Everything below was measured on Euro-Office itself.

## Deployment shape

| | |
|---|---|
| Document server | **sidecar container** `docudesk-eurooffice`, image `ghcr.io/euro-office/documentserver:latest` (**4.35 GB**), host port **8093** |
| Nextcloud connector | app id **`eurooffice`**, inside the `nextcloud` container — **not bind-mounted**, see [README](README.md) |
| Config root in image | `/etc/euro-office/` (ONLYOFFICE uses `/etc/onlyoffice/`) |

## 1. Start the server

```bash
docker compose -f docker-compose.office.yml --profile eurooffice up -d
docker inspect docudesk-eurooffice --format '{{.State.Health.Status}}'   # want: healthy
```

First boot takes 2–4 minutes; its nginx answers **502** until the backend is up.

## 2. WOPI is OFF by default — measured on THIS image

`/etc/euro-office/documentserver/default.json` ships:

```json
"wopi": { "enable": false }
```

Measured directly on Euro-Office 2026-08-16, not inferred from another suite. The
overlay sets `WOPI_ENABLED=true`, which writes `"enable": true` into `local.json` and
makes `/hosting/discovery` serve.

## 3. Install the connector — needs building

There is **no prebuilt asset** and it is **not on the NC 34 appstore**
(`occ app:install eurooffice` fails). Build from source:

```bash
curl -sL -o eo-app.tar.gz \
  https://api.github.com/repos/Euro-Office/eurooffice-nextcloud/tarball/v11.0.2
mkdir -p eoapp && tar -xzf eo-app.tar.gz -C eoapp
cd eoapp/*/

npm install            # NOT `npm ci` — the shipped lockfile is missing picomatch@4.0.5
npm run build          # produces js/
composer install --no-dev

# strip build-only trees, then deploy
rm -rf node_modules src
docker cp . nextcloud:/var/www/html/custom_apps/eurooffice
docker exec nextcloud chown -R www-data:www-data /var/www/html/custom_apps/eurooffice
docker exec nextcloud php occ app:enable eurooffice
```

### Three things the tarball does not give you

Each produced a **500** with a different cause, and none is obvious from the error:

1. **`vendor/autoload.php` missing** → `composer install --no-dev`.
2. **`assets/document-formats/onlyoffice-docs-formats.json` missing.** It is a
   submodule the tarball omits. Copy it from an ONLYOFFICE install, or fetch it from
   the upstream document-formats repository.
   *(That the file carries this name inside Euro-Office is real lineage evidence —
   unlike the unverified claim this page retracts.)*
3. **The failed read is CACHED for 6 hours.** `AppConfig::getFormats()` caches the
   result, so after fixing (2) the app keeps failing with the same error. Clear APCu:
   `docker exec nextcloud apachectl graceful`.

## 4. Three URLs, three directions

```bash
docker exec nextcloud php occ config:app:set eurooffice DocumentServerUrl \
    --value="http://localhost:8093/"          # BROWSER -> server
docker exec nextcloud php occ config:app:set eurooffice DocumentServerInternalUrl \
    --value="http://docudesk-eurooffice/"     # NEXTCLOUD -> server
docker exec nextcloud php occ config:app:set eurooffice StorageUrl \
    --value="http://nextcloud/"               # SERVER -> NEXTCLOUD
```

## 5. Verified 2026-08-16

```
container running                          PASS  running
container health                           PASS  healthy
reachable from nextcloud                   PASS  HTTP 302
WOPI discovery                             PASS  200 at /hosting/discovery
conversion (docx->odt)                     PASS  ok at /ConvertService.ashx (json/url)
self-report (/healthcheck)                 ----  true
docudesk:office:probe                      ----  eurooffice available at /hosting/discovery
```

**In the browser:** `/index.php/apps/eurooffice/<fileId>` opens the document with the
full editor — toolbar, Headings, Paragraph/Table/Chart settings, Track changes,
"Page 1 of 1".

## What is NOT verified for Euro-Office

- **Anchor stability across a Euro-Office save.** The `docx → odt → docx` round-trip
  was run on ONLYOFFICE. It has not been repeated here, and its result does not
  transfer.
- **Chart rendering fidelity.** Measured on ONLYOFFICE only.
- **The relationship to ONLYOFFICE.** The install guide does not state Euro-Office's
  codebase origin. Shared endpoint paths (`/ConvertService.ashx`,
  `/hosting/discovery`) and the `onlyoffice-docs-formats.json` asset name suggest a
  shared lineage. That is an observation, not a conclusion, and nothing here depends
  on it.
