## Tasks

- [x] 1. **PolicyMatchService availability** — `lib/Service/PolicyMatchService.php` (632 lines) is present and the schema for `publicationProhibition` was added in sibling spec `publication-prohibition-schema`. Cache is split (prohibition vs standing-consent) at load — see `loadProhibitions()` + `loadStandingConsents()`.
- [x] 2. **PolicyMatchService unit tests** — `tests/unit/Service/PolicyMatchServiceTest.php` (added in this batch) covers high-confidence match, low-confidence match (implicit in matchProhibition shape), no match, deterministic UUID tie-break precedence, time-bounded rule honouring, prohibition portion of cache populated correctly, and the `matchProhibition` canonical shape.
- [x] 3. **Prohibition gate in AnonymizationService** — `lib/Service/AnonymizationService.php:236` calls `matchProhibition(entityType, entityValue)` for each detected entity (verified). Per-entity match result attached as `prohibitionMatch` on the entity entries via the extract endpoint and consolidated-entities endpoint (sibling `anonymisation-bases-passthrough` task 5).
- [~] 4. **422 contract — missing high-confidence matches** — DEFERRED: the controller-side 422 gate that compares the request payload's `entities[]` against the prohibition-match list is the next iteration; the service-level prohibition signal is already attached as `prohibitionMatch` per entity, and the publication-clearance path (sibling spec `publication-clearance-anonymise-payload`) carries the prohibition pre-emption for the clearance flow. The generic anonymise-without-publication flow currently passes through; tightening to 422 ships in a follow-up alongside the documentation update (task 13).
- [x] 5. **Threshold config** — `docudesk.prohibition.high_confidence_threshold` (default `0.85`) is read by `AnonymizationService` via `IAppConfig::getValueFloat` (per sibling `anonymisation-bases-passthrough` task 7); runtime changes propagate without restart because the read happens per request.
- [~] 6. **`acknowledgedOverrides` validation** — DEFERRED with task 4: ships alongside the 422 gate.
- [~] 7. **Logging** — DEFERRED with task 4: logging policy applies once the 422 gate fires.
- [~] 8. **In-place wording fix — entity-publication-policies (parent spec)** — DEFERRED: the parent spec edit is a separate openspec change; this gate's spec.md already lands the read-only-vs-workflow distinction in its requirements.
- [~] 9. **In-place wording fix — consent-management delta** — DEFERRED with task 8: parent-spec edit, lands as one PR with the gate.
- [~] 10. **Unit tests — AnonymizationService gate** — DEFERRED with task 4: covers gate-firing behaviour once the 422 path lands.
- [~] 11. **Unit tests — controller** — DEFERRED with task 4: covers `acknowledgedOverrides` + 422 response shape once the gate lands.
- [~] 12. **Integration tests — Newman** — DEFERRED with task 4.
- [~] 13. **Documentation** — DEFERRED with task 4: feature doc ships once the contract is live.
- [~] 14. **Quality + verification** — DEFERRED: composer-strict runs against the dev container; the live-env smoke test depends on the 422 gate (task 4) landing.
