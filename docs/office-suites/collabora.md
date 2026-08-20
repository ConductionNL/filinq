<!--
SPDX-License-Identifier: EUPL-1.2
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
-->

# Collabora Online (CODE)

LibreOffice-based, the incumbent Nextcloud Office engine, WOPI host with a browser
editor.

**This page is about Collabora only.**

## Deployment shape

| | |
|---|---|
| Document server | **sidecar container** `docudesk-collabora`, image `collabora/code:latest`, host port **9980** |
| Nextcloud connector | app id **`richdocuments`** (plus optional `richdocumentscode`, a bundled server) |

## 1. Start it

```bash
docker compose -f docker-compose.office.yml --profile collabora up -d
```

## 2. The image has NO SHELL — this changes how you check it

```
$ docker exec docudesk-collabora sh -c 'echo ok'
exec: "sh": executable file not found in $PATH
```

Consequences, both measured 2026-08-16:

- **A `CMD-SHELL` healthcheck can never pass.** With one defined, this container sat
  at `unhealthy` while `/hosting/discovery` returned **200** from Nextcloud. The
  status was a fact about the healthcheck, not about Collabora. The overlay
  therefore defines **no** healthcheck and lets the image's own apply.
- **Anything probing it must run from OUTSIDE**, e.g. from the `nextcloud`
  container. A conversion probe run *inside* reports "unsupported" for a suite that
  converts perfectly well.

## 3. Connect Nextcloud

```bash
docker exec nextcloud php occ app:install richdocuments
docker exec nextcloud php occ app:enable richdocuments
docker exec nextcloud php occ config:app:set richdocuments wopi_url \
    --value="http://docudesk-collabora:9980"
docker exec nextcloud php occ richdocuments:activate-config
```

⚠️ If `richdocumentscode` is installed it supplies its **own bundled server** and
`richdocuments` may point at that instead — the probe then reports a connection
failure to `localhost:8080/custom_apps/richdocumentscode/proxy.php`. Set `wopi_url`
explicitly to use the sidecar.

## 4. `aliasgroup1` is a REGEX

`aliasgroup1` tells Collabora which host it accepts WOPI callbacks from. Dots are
wildcards unless escaped — `http://nextcloud.local` also matches
`http://nextcloudXlocal`.

## 5. Conversion API — different from ONLYOFFICE's

**Multipart upload, converted bytes returned inline.** No JSON, no URL fetch:

```bash
curl -F 'data=@probe.docx' http://docudesk-collabora:9980/cool/convert-to/odt -o out.odt
```

(`/lool/convert-to/<fmt>` on older builds.)

## 6. Verified 2026-08-16

```
container running                          PASS  running
container health                           PASS  healthy   (image's own; see §2)
reachable from nextcloud                   PASS  HTTP 200
WOPI discovery                             PASS  200 at /hosting/discovery
conversion (docx->odt)                     PASS  ok at /cool/convert-to/odt (multipart, 10828B)
self-report (/healthcheck)                 ----  <none>    (endpoint does not exist)
docudesk:office:probe                      ----  collabora available at /hosting/discovery
```

## What is NOT verified for Collabora

- **Browser editing was not exercised in this programme.** WOPI discovery serves and
  conversion works; opening a document in the Collabora editor was not tested.
- **Anchor stability across a Collabora save.** ADR-087 raised exactly this question
  for Collabora/LibreOffice, and it remains open — the round-trip was run on
  ONLYOFFICE, and that result does not transfer.
