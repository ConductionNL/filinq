<!--
SPDX-License-Identifier: EUPL-1.2
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
-->

# LibreOffice (headless)

**A different shape from the other three, and the difference is the point.**
ONLYOFFICE, Euro-Office and Collabora are WOPI hosts with browser editors.
LibreOffice here is a **converter only**: no WOPI, no discovery endpoint, no
in-browser editing.

Its verification row therefore fails the WOPI checks. That is correct, not a defect —
a suite is not worse for being a different shape, and a harness that forced it to
look the same would be hiding what it is.

## Deployment shape

| | |
|---|---|
| Converter | **sidecar container** `docudesk-libreoffice`, image `libreofficedocker/libreoffice-unoserver:3.19` (1.49 GB), host port **8094** → container 2004 |
| Nextcloud connector | **none** — LibreOffice has no server-side Nextcloud integration of this kind |

```bash
docker compose -f docker-compose.office.yml --profile libreoffice up -d
```

## ⚠️ DocuDesk cannot currently use this container

`LibreOfficeHeadlessBackend` invokes `soffice --headless` through `proc_open` as a
**local subprocess**, configured by
`docudesk.conversion.libreoffice_binary_path` (default `soffice`).

Measured 2026-08-16:

```
$ docker exec nextcloud sh -c 'command -v soffice libreoffice'
(nothing)
```

**`soffice` is not present in the nextcloud container**, so that tier of the
conversion cascade is dead on this deployment. It fails at conversion time rather
than at startup, so nothing reports it — the cascade simply falls through to the
next backend.

Two ways to close it, and they are not equivalent:

1. **Install LibreOffice inside the nextcloud container.** Makes the existing
   backend work unchanged; adds ~1 GB to that container and mutates the shared
   instance.
2. **Add an HTTP conversion backend** beside the subprocess one, pointing at this
   sidecar. This is a **code change**, not configuration — the current backend has
   no remote mode.

This container exists to verify LibreOffice itself and to make that gap visible.

## Conversion API

Multipart upload, `convert-to` as a **form field** — not a query parameter:

```bash
curl -F 'file=@probe.docx' -F 'convert-to=pdf' \
     http://docudesk-libreoffice:2004/request -o out.pdf
```

Passing `?convert-to=pdf` in the query string returns **400**:
`Field validation for 'ConvertTo' failed on the 'required' tag`.

## Verified 2026-08-16

```
container running                          PASS  running
reachable from nextcloud                   PASS  HTTP 200 (on :2004)
WOPI discovery                             FAIL  no path answered 200   <- EXPECTED: not a WOPI host
conversion (docx->pdf)                     PASS  HTTP 200, 18890 bytes, %PDF- magic
docudesk:office:probe                      ----  not listed: the probe covers WOPI suites only
```

## What is NOT verified for LibreOffice

- **Anything through DocuDesk.** The app cannot reach this container (see above), so
  no DocuDesk conversion has gone through LibreOffice on this deployment.
- **Anchor stability.** Not applicable in the same way — there is no editing session
  to round-trip through. A conversion is one-way.
