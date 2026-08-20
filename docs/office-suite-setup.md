<!--
SPDX-License-Identifier: EUPL-1.2
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
-->

# Setting up an office suite for DocuDesk

> **This page has moved to per-suite documentation.**
>
> It previously covered ONLYOFFICE and Collabora together, and — wrongly — presented
> an ONLYOFFICE setup under a "Euro-Office / ONLYOFFICE" heading as though the two
> were one product. Four suites are now documented and verified **separately**:
>
> | Suite | Page |
> |---|---|
> | ONLYOFFICE | [office-suites/onlyoffice.md](office-suites/onlyoffice.md) |
> | Euro-Office | [office-suites/eurooffice.md](office-suites/eurooffice.md) |
> | Collabora Online | [office-suites/collabora.md](office-suites/collabora.md) |
> | LibreOffice | [office-suites/libreoffice.md](office-suites/libreoffice.md) |
>
> Start at [office-suites/README.md](office-suites/README.md), which explains the
> deployment split (document servers are sidecar containers; connector apps live
> inside the `nextcloud` container and are **not** bind-mounted) and how to run the
> per-suite verification.

## DocuDesk needs none of them

Reading a document, editing a paragraph, changing style, writing metadata and
embedding a chart all go through DocuDesk's in-package codec. No office suite is in
that call path (ADR-087 §2). If you install nothing, those capabilities still work.

A suite adds format conversion providers (via `IConversionManager`) and editing
sessions over WOPI. A feature that only works when one named suite is present would
be lock-in we authored ourselves — see `tests/unit/Service/Office/SuiteIndependenceTest.php`.
