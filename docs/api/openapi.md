---
sidebar_position: 1
title: API Documentation
---

# Filinq API Documentation

Every Filinq function has a JSON endpoint: anonymisation, signing, templates and document generation, intake, extraction, consent, dossiers, archiving and retention, legal holds, settings and the setup wizard. This page explains where the reference is, how to authenticate and where the stored records are documented.

## The reference

The [API reference](/api) renders Filinq's OpenAPI document (OpenAPI 3.0). It lists every endpoint with its parameters, answers and status codes, grouped by function.

- On the docs site: [/api](/api), or download the [OpenAPI document (JSON)](/oas/filinq-api.json).
- On your own instance: `GET https://<your-nextcloud>/index.php/apps/filinq/api/v1/openapi.json` serves the document of the version you run.

The document is `openapi.json` in the repository. A unit test keeps it in step with the routes in `appinfo/routes.php`: a route that is not documented, or a documented path that is not routed, fails the build. The docs site copies that same file when it is built, so the site and the app describe the same API.

You can import the document into Postman, Insomnia or the Swagger Editor to try the endpoints.

## Base path and versions

Every endpoint is served under `/index.php/apps/filinq/api/v1/`. The older unversioned paths under `/api/` still answer, are deprecated, and name their `/api/v1/` successor in a `Link` header next to a `Deprecation` header. Every answer carries an `API-Version` header with the installed version.

## Authentication

Filinq uses Nextcloud's own authentication. For a script or another system, create an **app password** in Nextcloud under *Personal settings → Security → Devices & sessions*, and send it with the user name as HTTP basic authentication. Keep both in environment variables rather than in the script:

```bash
curl -u "$NC_USER:$NC_APP_PASSWORD" \
  -H 'OCS-APIRequest: true' \
  https://cloud.example.com/index.php/apps/filinq/api/v1/templates
```

The request runs as that user, with that user's rights: an endpoint refuses what the user may not do in the app either. A Nextcloud OAuth2 access token can be sent as a bearer token instead. Do not put the account's own password in a script.

## Errors

A failed request answers with a standard HTTP status code and a problem details body (RFC 9457, `application/problem+json`) with `type`, `title`, `status` and `detail`. Some endpoints add their own members, such as a `reason`, which the reference names per endpoint.

## Stored records: OpenRegister

Filinq keeps its records (templates, signing requests, consent records, dossiers, publications and the rest) as objects in OpenRegister. The Filinq reference documents the endpoints Filinq adds on top; the records themselves are documented by OpenRegister, which serves an OpenAPI document per register:

- `GET https://<your-nextcloud>/index.php/apps/openregister/api/registers/{id}/oas` for one register (for Filinq, the `filinq` register);
- `GET https://<your-nextcloud>/index.php/apps/openregister/api/registers/oas` for all registers.

Read and write those records through OpenRegister's API, with the same authentication.

## External standards

Endpoints that implement an external standard keep that standard's own formats and are not in the Filinq reference. Where Filinq documents them, `openapi.standards.json` in the repository does.
