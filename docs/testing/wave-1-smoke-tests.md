# Wave 1 Smoke-Test Checklist

Use this to validate all four Wave 1 changes against a live dev stack before promoting to Wave 2. Commands assume the standard dev compose (`master-nextcloud-1` container, host port 8080). Set the following env vars before running any HTTP example below — never commit real credentials into a snippet:

```bash
export NC_USER="<your-test-admin-user>"
export NC_PASS="<your-test-admin-password>"
export NC_BASE="http://localhost:8080"
```

Any reference to `admin` / `admin` in this document below refers to the **local dev defaults** of the example compose stack and must NOT be used against a production deployment.

**Branches under test:**
- DD `feat/110/add-dossier-schema-impl` (Wave 1.1)
- DD `feat/112/entity-publication-policies-impl` (Wave 1.2)
- DD `feat/123/anonymisation-grondslagen-and-prohibition-gate-amend` (cross-wave consumer)
- OR `feat/1494/entity-relation-grondslagen-impl` → PR #1503 (Wave 1.3)
- OR `feat/1495/text-extraction-eml-impl` → PR #1504 (Wave 1.4)

---

## 0. Pre-flight

- [ ] All four feature branches checked out and apps enabled (`occ app:list | grep -E "openregister|filinq"`)
- [ ] Filinq register imported at version 7.0.0 (`occ config:app:get openregister imported_config_docudesk_version` → `7.0.0`)
- [ ] OR migrations applied (`occ migrations:execute openregister Version1Date20260512120000` ran cleanly on first enable)
- [ ] Nextcloud UI loads at `http://localhost:8080`; Filinq app icon visible in the top bar

---

## Wave 1.1 — Filinq Dossier Register

### Register + schemas installed

- [ ] `GET /apps/openregister/api/registers/dossier?_extend=schemas` returns slug `dossier` with schemas `dossier` + `base`
- [ ] `objectNameField` on the `dossier` schema is `"name"` (so list UIs render the stored name)

### Seeds present

- [ ] `GET /apps/openregister/api/objects/filinq/base` returns **6** records with slugs:
  `persoonsgegevens`, `bijzondere-persoonsgegevens`, `strafrechtelijk`,
  `bedrijfs-fabricagegegevens`, `onevenredige-benadeling`, `nationale-veiligheid`
- [ ] `GET /apps/openregister/api/objects/filinq/dossier` returns 3–5 seed dossiers (one with `bases: []`, one with `checkedOn: null`)

### Create flow

- [ ] In Files: create a folder, copy its node ID
- [ ] `POST /apps/openregister/api/objects/filinq/dossier` with `name`, `bases: ["persoonsgegevens"]`, and `@self.folder: "<node-id>"` → 201, returns a uuid
- [ ] `GET /apps/openregister/api/objects/filinq/dossier/<uuid>` returns the same data; stored `folder` matches the supplied node ID; **no new folder was created**
- [ ] Posting without `name` → validation error
- [ ] Posting with only `name` + `@self.folder` → 201, `bases` is `[]` (or absent), `checkedOn` is `null`

### Referential integrity (v1 slug-string model)

- [ ] Posting a dossier with `bases: ["does-not-exist"]` → write succeeds (v1 trade-off: no FK enforcement). Note in test log that this is **known and documented** in `design.md` §D1.
- [ ] Adding a custom base via `POST /apps/openregister/api/objects/filinq/base` works and the new slug is usable in `dossier.bases`

### Audit trail (`checkedOn`)

- [ ] PUT a dossier with a new `checkedOn` timestamp as user `admin`
- [ ] In OR's audit-trail UI (or `GET /apps/openregister/api/audit-trail?objectId=<dossier-uuid>`), the latest entry shows `actor = admin`, the new `checkedOn` in the diff, and a current timestamp

---

## Wave 1.2 — Filinq Entity Publication Policies

### Schemas + seeds

- [ ] `GET /apps/openregister/api/schemas/<id>` for the consent register lists schemas `publicationConsent` AND `publicationProhibition`
- [ ] `GET /apps/filinq/api/policy/prohibitions` returns **4** seed prohibitions
- [ ] `GET /apps/filinq/api/policy/standing-consents` returns **4** seed standing consents (all with `scope: "entity"`)

### Admin UI — three pages exist and filter correctly

- [ ] Side-nav shows three entries: **Consent Workflow**, **Standing Consents**, **Prohibitions**
- [ ] **Consent Workflow** page lists only `scope: "document"` records (no standing consents bleed in)
- [ ] **Standing Consents** page lists only `scope: "entity"` records (no workflow records bleed in)
- [ ] **Prohibitions** page lists only `publicationProhibition` records (no consents shown on either side)

### Prohibition CRUD

- [ ] On **Prohibitions** click *Add*; the create dialog requires `primaryName`, `entityType`, `reason`, and at least one match rule
- [ ] Submitting with only an `exact`/`normalized` rule shows the **"name-only" warning**
- [ ] Adding a BSN or KvK match rule clears the warning
- [ ] Submit creates the record; list refreshes with the new row
- [ ] *Edit* on a row loads existing values; submit updates the record
- [ ] *Delete* on a row removes it (confirm dialog appears first)

### Standing Consent CRUD

- [ ] On **Standing Consents** click *Add*; the create dialog requires `entityText`, `entityType`, `consentMethod`, and at least one match rule
- [ ] Leaving `validUntil` blank surfaces the **"no expiry" warning** (non-blocking)
- [ ] Submit without `consentMethod` is rejected with a 400 error
- [ ] Posting a standing-consent payload as a non-admin user who is NOT in `docudesk-standing-consent-admins` → 403 (use a second test user to confirm)

### Scope validation (also exercised by Newman)

- [ ] `POST /apps/filinq/api/policy/standing-consents` with `documentId` set → 400 (`scope=entity must not include documentId`)
- [ ] `POST /apps/filinq/api/policy/standing-consents` without `matchRules` → 400
- [ ] `POST /apps/filinq/api/policy/standing-consents` with `policyMatch` set → 400

### Detection-time outcomes (4 scenarios via the consent endpoint)

Pre-seed the policy layer first (create one prohibition matching "Jan Janssen" exact, one standing consent matching "Mark Mark" exact).

- [ ] **No match** — `POST /apps/filinq/api/consents` with entityText "Anna Anders" → record has `consentStatus: "pending"`, `notificationStatus: "pending"`, `policyMatch: null`
- [ ] **Prohibition match** — `POST .../api/consents` with entityText "Jan Janssen" → record has `consentStatus: "anonymized"`, `notificationStatus: "skipped"`, `publicationDecision: "anonymize"`, `objectionDeadline: null`, `policyMatch` references the prohibition UUID
- [ ] **Standing consent match** — `POST .../api/consents` with entityText "Mark Mark" → record has `consentStatus: "consent_given"`, `notificationStatus: "skipped"`, `publicationDecision: "publish_with_consent"`, `policyMatch` references the standing-consent UUID
- [ ] **Both match (prohibition wins)** — temporarily widen the standing consent to also match "Jan Janssen", repeat the prohibition test → `consentStatus: "anonymized"`, `policyMatch` references the **prohibition** (not the standing consent)

### Retroactive force-resolve (asymmetric)

- [ ] Create an in-flight consent record (no policy match) for entity "Pieter Test"
- [ ] Create a new prohibition matching "Pieter Test"
- [ ] Re-fetch the in-flight record → it now has `consentStatus: "anonymized"`, `policyMatch` referencing the new prohibition, original `notificationSentAt` preserved, `objectionDeadline: null`
- [ ] Create an in-flight consent record with `consentStatus: "objection_received"` for entity "Saskia Test"
- [ ] Create a new standing consent matching "Saskia Test"
- [ ] Re-fetch the in-flight record → **unchanged** (standing consent does NOT retroactively override existing decisions)

### ConsentDetail toggle behaviour

Open a consent record on the **Consent Workflow** page:

- [ ] Record with `policyMatch: null` → toggle reflects `publicationDecision` (existing UX), interactive
- [ ] Record with `policyMatch → prohibition` → toggle is **ON and locked**; tooltip/note explains "on the prohibition list"
- [ ] Record with `policyMatch → standing consent` → toggle is **OFF by default but interactive**; flipping ON updates `publicationDecision: "anonymize"` while `consentStatus` stays `"consent_given"` and `policyMatch` is preserved

### Policy-pre-empted indicator

- [ ] **Consent Workflow** list shows a "policy" badge next to the entity text on any row whose `policyMatch` is non-null

### Policy-pre-empted transition guard

- [ ] `PUT /apps/filinq/api/consents/<id>` attempting to change `consentStatus` from `"anonymized"` to `"pending"` on a record whose `policyMatch` is non-null → 400 with the "policy-pre-empted record" error message

---

## Wave 1.3 — OR Entity Relation Grondslagen (PR #1503)

### Migration + columns

- [ ] `SHOW COLUMNS FROM oc_openregister_entity_relations` on the dev DB includes `bases` (JSON) and `skip_anonymization` (BOOLEAN)

### PATCH endpoint

- [ ] `PATCH /apps/openregister/api/entity-relations/{id}` with `{"bases": ["persoonsgegevens"]}` → 200, response shows updated `bases`
- [ ] PATCH with `{"skipAnonymization": true}` → 200, response shows the flag set
- [ ] PATCH with both fields → 200, both persisted
- [ ] PATCH with a field NOT in the whitelist (e.g. `entityText`) → field is silently ignored / not persisted (whitelist enforced)

### Authorisation

- [ ] PATCH as a user who **cannot write** the parent file → 403
- [ ] PATCH as the file owner → 200

### Audit trail

- [ ] After a successful PATCH that actually changes `bases` or `skipAnonymization`, `AuditTrailMapper::findByObject(<relation-uuid>)` returns a new entry with the actor UID and the diff
- [ ] A PATCH that submits unchanged values → **no new audit entry**

### Skip-aware anonymisation

- [ ] Detect entities on a file, then PATCH one relation to `skipAnonymization: true`
- [ ] Trigger anonymisation on that file → the skipped entity is **not** anonymised in the output; other entities are
- [ ] `markAsAnonymized` does not flip the `anonymized` flag on a skipped relation (the new `AND skip_anonymization = 0` predicate holds)

---

## Wave 1.4 — OR Text Extraction EML (PR #1504)

### Dependency installed

- [ ] `composer show zbateson/mail-mime-parser` reports version `^3.0` (installed 3.0.5)

### Single-message EML

- [ ] Upload `tests/fixtures/sample.eml` (or any plain `.eml` file with subject + body + one PDF attachment) to a folder
- [ ] `POST /apps/openregister/api/text-extraction?fileId=<id>` → returns text containing the subject + body content
- [ ] The returned text is **valid UTF-8** (test with `iconv -f utf-8 -t utf-8` round-trip)

### Structured parse

- [ ] `parseEmlStructured(File)` (via the controller / a direct PHP call) returns an `EmlStructure` with non-empty `body.text` and one `EmlAttachment` whose `filename` matches the PDF
- [ ] `EmlAttachment.jsonSerialize()` returns the content as **base64-encoded** (not raw bytes)

### HTML body fallback

- [ ] An `.eml` file with only an HTML body returns plain-text-converted output (no raw `<p>` tags)

### Non-UTF-8 body

- [ ] An `.eml` file whose `Content-Transfer-Encoding` is `quoted-printable` with a non-UTF-8 charset (e.g. `iso-8859-1`) is transcoded; the returned text contains the accented characters correctly

### Nested EML (`message/rfc822` attachment)

- [ ] An `.eml` containing a forwarded `.eml` attachment yields text from BOTH the outer and inner message
- [ ] An `.eml` with a 5-level nested chain stops recursing at `MAX_DEPTH = 3` (verify by counting structures in `parseEmlStructured` output) — no infinite loop, no OOM

### Failure modes

- [ ] A non-EML file with `message/rfc822` mime mislabel → `EmlParser::parse` throws `EmlParseException`
- [ ] PII (email addresses, names) does **not** appear in the structured logs (use `tail -f data/nextcloud.log` while parsing)

---

## Cross-wave consumer (DD prohibition-gate amend)

- [ ] On a file with detected entities matching a prohibition, calling `OR EntityRelationMapper::findEntitiesForAnonymization($fileId)` returns the relation set EXCLUDING any that were PATCHed with `skipAnonymization: true`
- [ ] Calling the anonymise endpoint on that file produces an audit-log entry citing the prohibition match for any forced-skip overrides

---

## Wrap-up

- [ ] No new entries in `data/nextcloud.log` at ERROR or WARNING level that originate from any of the new services (`PolicyMatchService`, `PolicyRetroactiveService`, `PolicyCrudService`, `EmlParser`, `EntityRelationsController`)
- [ ] Browser console is clean (no unhandled promise rejections) while clicking through all three new admin pages
- [ ] Run `composer check:strict` in Filinq and OR — both green
- [ ] Run `npm run build` in Filinq — green

### If everything passes

- [ ] Open PRs for the two Filinq impl branches against `development`
- [ ] Move to Wave 2 (openanonymiser install + warning flow)

### If something fails

- File the issue against the relevant change (`/opsx:apply <change-id> --fixup`) and re-run the affected section only.
