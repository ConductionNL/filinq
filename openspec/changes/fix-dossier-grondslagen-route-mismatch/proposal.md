# Proposal: fix-dossier-grondslagen-route-mismatch

kind: code

## Why

`appinfo/routes.php:57` registers the route

```php
['name' => 'dossier#generateGrondslagenPdf', 'url' => 'api/anonymization/dossier/{dossierId}/grondslagen-pdf', 'verb' => 'POST'],
```

Nextcloud's router resolves `dossier#generateGrondslagenPdf` to
`\OCA\DocuDesk\Controller\DossierController::generateGrondslagenPdf()`. That method
**does not exist**. The controller's only public action is
`generateGrondslagenSummary()` (`lib/Controller/DossierController.php:96`) — a
different, unrelated method name from an earlier rename that never propagated to
the route table.

This is exactly the ADR-029 (route-reachability gate) Invariant 2 failure mode:
*"A route IS registered, but the controller class named in the route entry
doesn't expose the method... Calling the URL throws `ReflectionException` and
500s."* The bug is invisible to the existing unit tests because they call the
controller method directly, in-process, bypassing the router entirely —
`tests/unit/Controller/DossierControllerTest.php:166,195,228` all call
`$this->controller->generateGrondslagenSummary('d-1')`, never
`generateGrondslagenPdf`.

The endpoint is not dead code — it is actively wired from the frontend:
`src/store/modules/folderAnonymization.js:288` calls
`generateUrl('/apps/docudesk/api/anonymization/dossier/' + this.dossier.uuid + '/grondslagen-pdf')`.
Every real click on "Append a grondslagen-summary page" (Wave 4a,
`src/views/anonymization/FolderAnonymizationView.vue:113`) currently 500s at
the Nextcloud router layer before `DossierController` code ever runs.

## What Changes

- Fix `appinfo/routes.php:57` so the route name matches the real controller
  method: `'name' => 'dossier#generateGrondslagenSummary'` (URL and verb
  unchanged — this is a routing-table correction only, not a URL/API
  contract change; the frontend keeps calling the same URL).
- Add a route-level regression test (or extend the existing Newman/curl smoke
  coverage per ADR-008) that exercises the real HTTP path — not just the
  controller method in-process — so a future rename cannot silently
  reintroduce this class of bug.
- No BREAKING change: the public URL and request/response shape are
  unaffected; only the internal route-to-method binding is corrected.

## Out of Scope

- Any change to `GrondslagenSummaryService` or the PDF-rendering logic itself.
- Renaming the controller method (keeping `generateGrondslagenSummary` as the
  canonical name since it is what the tests and docblocks already reference).

## Success Criteria

- `POST /apps/docudesk/api/anonymization/dossier/{dossierId}/grondslagen-pdf`
  reaches `DossierController::generateGrondslagenSummary()` instead of
  throwing `ReflectionException` / 500.
- `hydra-gate-route-reachability` (Invariant 2) passes for this route.
- Existing unit tests continue to pass unchanged.
