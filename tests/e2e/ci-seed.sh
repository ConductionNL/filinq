#!/usr/bin/env bash
#
# SPDX-FileCopyrightText: 2026 DocuDesk Contributors
# SPDX-License-Identifier: EUPL-1.2
#
# Provision DocuDesk's OpenRegister registers + schemas, and the app-config
# object-type bindings that depend on them, on a freshly installed Nextcloud
# for the shared `E2E Tests (Playwright)` CI job.
#
# Wired up as the workflow's `playwright-seed-command`. That step runs AFTER
# `php -S` is up and with cwd set to the Nextcloud server root, so this is
# invoked as:
#
#     playwright-seed-command: 'bash apps/docudesk/tests/e2e/ci-seed.sh'
#
# WHY THIS IS NEEDED — TWO SEPARATE GAPS
# --------------------------------------
# 1. THE REGISTER IMPORT IS NOT A RELIABLE FRESH-INSTALL PATH.
#    DocuDesk imports `lib/Settings/docudesk_register.json` from
#    `Application::boot()` → `SettingsService::initialize()` →
#    `SettingsInitializer::initialize()`. That path fails silently two ways:
#
#      a. `boot()` wraps the whole call in `catch (\Exception) {}` with an
#         explicit "Silently fail" comment, and `initialize()` itself catches
#         `Exception` and only appends to `$results['errors']`. Nothing that
#         calls it ever inspects those errors, so a denied or broken import is
#         indistinguishable from a successful one. During `occ app:enable`
#         there is NO user session, so OpenRegister's RBAC can deny it outright.
#      b. It calls `importFromApp()` on the version-guarded path: if the
#         recorded `docudesk/configuration_version` is already >= the file's
#         version, it returns "up to date" and applies nothing.
#
#    Either way the app enables cleanly, the SPA boots, and the registers
#    simply are not there. The e2e suite's failure mode in that state is a wall
#    of 500s from the template/signing endpoints — messages that point at the
#    fixtures, not at the missing import.
#
#    So the import is done EXPLICITLY here through OpenRegister's admin HTTP
#    importer (which has a real session and passes RBAC), with `force=true` to
#    defeat the version guard, and then VERIFIED.
#
# 2. THE OBJECT-TYPE BINDINGS ARE ADMIN-CONFIGURED, NOT AUTO-PROVISIONED.
#    Importing the registers is NOT enough. DocuDesk resolves every write
#    through app-config keys `<schemaSlug>_register` / `<schemaSlug>_schema`
#    (see `OpenRegisterResolver::getRegisterAndSchema()`,
#    `SigningService::createRequest()`, `SettingsService::WRITABLE_KEYS`).
#    Those keys are populated by an administrator in DocuDesk's admin settings
#    UI; the only one the app self-provisions is `templateVersion_*`
#    (`SettingsInitializer::provisionTemplateVersionConfig()`). On a fresh
#    install every other binding is empty, and e.g. `POST /api/templates`
#    answers 500 `Template register/schema not configured`.
#
#    This script therefore performs the same binding an admin would, deriving
#    the IDs from OpenRegister itself rather than hardcoding them (IDs are
#    autoincrement and differ per install).
#
# A failed provision becomes ONE loud step failure here instead of a dozen
# misleading spec failures later.
#
# The script is idempotent: the import is idempotent server-side, the config
# writes are last-write-wins with the same values, and re-running only
# re-verifies.

set -euo pipefail

# ── Locations ────────────────────────────────────────────────────────────────
# Resolve from the script's own path so the script does not depend on the
# caller's cwd (the workflow runs it from the Nextcloud server root).
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
REGISTER_JSON="${APP_ROOT}/lib/Settings/docudesk_register.json"

# Nextcloud server root: two levels above the app dir (server/apps/docudesk or
# server/custom_apps/docudesk). Fall back to the cwd if that is not it.
NC_ROOT="$(cd "${APP_ROOT}/../.." && pwd)"
if [ ! -f "${NC_ROOT}/occ" ] && [ -f "${PWD}/occ" ]; then
	NC_ROOT="${PWD}"
fi

if [ ! -f "$REGISTER_JSON" ]; then
	echo "::error::DocuDesk register definition not found at ${REGISTER_JSON}."
	exit 1
fi

# ── Target resolution ────────────────────────────────────────────────────────
# The shared workflow's "Seed test data" step exports BASE_URL / NEXTCLOUD_URL /
# NC_BASE_URL / ADMIN_USER / ADMIN_PASSWORD (ConductionNL/.github #124). Accept
# any of them, and fall back to the CI runner's own `php -S 0.0.0.0:8080` only
# when we are demonstrably on CI.
#
# That gate is load-bearing. On a developer box `localhost:8080` is the SHARED
# dev container, and this script performs ADMIN WRITES — it must never import a
# register or rewrite app config in somebody else's environment. Off CI, an
# unset target is a hard error.
BASE="${PLAYWRIGHT_BASE_URL:-${BASE_URL:-${NEXTCLOUD_URL:-${NC_BASE_URL:-}}}}"
if [ -z "$BASE" ]; then
	if [ "${GITHUB_ACTIONS:-}" = "true" ] || [ "${CI:-}" = "true" ]; then
		BASE="http://localhost:8080"
	else
		echo "ERROR: no base URL set. Export PLAYWRIGHT_BASE_URL or BASE_URL." >&2
		echo "       Refusing to default to http://localhost:8080 outside CI —" >&2
		echo "       that is the SHARED dev container and this script writes to it." >&2
		exit 1
	fi
fi
BASE="${BASE%/}"

USER_NAME="${ADMIN_USER:-${NC_ADMIN_USER:-admin}}"
USER_PASS="${ADMIN_PASSWORD:-${NC_ADMIN_PASS:-admin}}"

echo "[ci-seed] target:    ${BASE}"
echo "[ci-seed] app root:  ${APP_ROOT}"
echo "[ci-seed] nc root:   ${NC_ROOT}"

# ── 0. Let DocuDesk's own boot-time import run once, first ───────────────────
# `Application::boot()` calls `SettingsService::initialize()` on EVERY request
# until `docudesk/configuration_version` has been written, and that call
# re-imports the whole definition (5 registers, 20 schemas, 46 objects).
#
# Order matters: if we bound the object-type config keys BEFORE that ran, the
# boot import would fire afterwards against IDs we had already recorded. Firing
# it here — before we import and bind — means it either succeeds and closes its
# own version gate, or fails silently and our forced import below is what
# actually provisions the instance. Either way nothing runs after the binding.
#
# Its outcome is deliberately not checked: it is unobservable by design (boot()
# swallows every \Exception), which is exactly why the rest of this script
# exists. No timeout either — this is the request that pays the whole import.
echo "[ci-seed] priming DocuDesk boot-time initialization…"
PRIME_CODE="$(curl -sS -o /dev/null -w '%{http_code}' -u "${USER_NAME}:${USER_PASS}" \
	-H 'OCS-APIRequest: true' "${BASE}/index.php/apps/docudesk/" || echo 000)"
echo "[ci-seed] prime /index.php/apps/docudesk/ -> ${PRIME_CODE}"

# ── 1. Import the DocuDesk configuration into OpenRegister ───────────────────
# DocuDesk has NO import route of its own — `appinfo/routes.php` registers only
# `settings#index` (GET) and `settings#create` (POST) at `api/settings`. So the
# import goes through OpenRegister's generic importer.
#
# `ConfigurationsController::import()` is admin-only and `@NoCSRFRequired`, so
# basic auth suffices. It accepts EXACTLY three input shapes — a multipart file
# under the literal key `file`, a `url` key, or a `json` key — and rejects a raw
# JSON body with 400 "Missing required keys in POST body". We use the file
# upload. `force` is compared `=== 'true' || === true`, so the multipart string
# "true" is accepted here.
IMPORT_URL="${BASE}/index.php/apps/openregister/api/configurations/import"
echo "[ci-seed] POST ${IMPORT_URL} (force=true, appId=docudesk)"

IMPORT_BODY="$(mktemp)"
IMPORT_CODE="$(
	curl -sS -o "$IMPORT_BODY" -w '%{http_code}' \
		-u "${USER_NAME}:${USER_PASS}" \
		-X POST \
		-H 'OCS-APIRequest: true' \
		-F "file=@${REGISTER_JSON};type=application/json" \
		-F 'force=true' \
		-F 'appId=docudesk' \
		"$IMPORT_URL" || echo 000
)"

echo "[ci-seed] import HTTP ${IMPORT_CODE}"
head -c 2000 "$IMPORT_BODY"; echo

if [ "$IMPORT_CODE" != "200" ]; then
	echo "::error::DocuDesk configuration import failed (HTTP ${IMPORT_CODE}). The e2e suite cannot exercise templates or signing without it."
	exit 1
fi

# How many entities the importer itself claims to have written. This is the
# POSITIVE CONTROL for the verification queries below: if the importer reports
# N schemas and the schemas endpoint then reports zero, the fault is in the
# QUERY, not in the data — and a bare "missing slugs" message would send us
# hunting the wrong thing.
IMPORT_COUNTS="$(
	python3 - "$IMPORT_BODY" <<'PY'
import json, sys
try:
    body = json.load(open(sys.argv[1]))
except Exception:
    print('0 0')
    raise SystemExit(0)
imported = body.get('imported') or {}
print(len(imported.get('registers') or []), len(imported.get('schemas') or []))
PY
)"
IMPORTED_REGISTERS="${IMPORT_COUNTS% *}"
IMPORTED_SCHEMAS="${IMPORT_COUNTS#* }"
echo "[ci-seed] importer reports ${IMPORTED_REGISTERS} register(s), ${IMPORTED_SCHEMAS} schema(s) written."

# Surface per-entity import failures.
#
# OpenRegister's `ImportHandler` imports schemas one at a time and swallows a
# per-schema `Exception` with "Continue with other schemas instead of failing
# the entire import" — it logs the reason and moves on. So a partial import
# still answers **HTTP 200 "Import successful"**, and the only record of WHY a
# schema is missing lives in `data/nextcloud.log`. Without this, the verification
# below can only say WHICH slugs are absent, never why.
NC_LOG="${NC_ROOT}/data/nextcloud.log"
if [ -f "$NC_LOG" ]; then
	python3 - "$NC_LOG" <<'PY' || true
import json, sys

hits = []
with open(sys.argv[1], errors='replace') as fh:
    for line in fh:
        line = line.strip()
        if 'ImportHandler' not in line or 'Failed to' not in line:
            continue
        try:
            entry = json.loads(line)
        except json.JSONDecodeError:
            hits.append(line[:400])
            continue
        message = entry.get('message')
        if isinstance(message, dict):
            text = message.get('message', '')
            context = message
        else:
            text = str(message)
            context = entry.get('data') or {}
        key = context.get('schemaKey') or context.get('registerKey') or context.get('slug') or '?'
        error = context.get('error') or ''
        hits.append(f'{text} [{key}] {error}'[:400])

if hits:
    print(f'[ci-seed] OpenRegister reported {len(hits)} per-entity import failure(s):')
    for hit in hits:
        print(f'[ci-seed]   {hit}')
else:
    print('[ci-seed] no per-entity import failures logged by OpenRegister.')
PY
else
	echo "[ci-seed] (no ${NC_LOG} to inspect for per-entity import failures)"
fi

# ── 2. Verify the registers and schemas are actually there ───────────────────
# The importer reporting success is not the same as the registers existing.
# Verify against OpenRegister directly, and require exactly the slugs declared
# in `lib/Settings/docudesk_register.json` — so this check cannot drift away
# from what the app actually ships.
REG_BODY="$(mktemp)"
curl -sS -u "${USER_NAME}:${USER_PASS}" -H 'OCS-APIRequest: true' \
	"${BASE}/index.php/apps/openregister/api/registers?_limit=300" -o "$REG_BODY"

SCH_BODY="$(mktemp)"
curl -sS -u "${USER_NAME}:${USER_PASS}" -H 'OCS-APIRequest: true' \
	"${BASE}/index.php/apps/openregister/api/schemas?_limit=1000" -o "$SCH_BODY"

# Emits `KEY<TAB>VALUE` lines for the app-config bindings, on stdout, after
# verifying. Anything diagnostic goes to stderr so the two never mix.
BINDINGS="$(mktemp)"
python3 - "$REGISTER_JSON" "$REG_BODY" "$SCH_BODY" "$IMPORTED_REGISTERS" "$IMPORTED_SCHEMAS" > "$BINDINGS" <<'PY'
import json, sys

definition_path, reg_path, sch_path, n_reg_imported, n_sch_imported = sys.argv[1:6]


def load(path, kind):
    raw = open(path).read()
    try:
        body = json.loads(raw)
    except json.JSONDecodeError:
        print(f'::error::the {kind} endpoint did not return JSON. First 500 bytes:', file=sys.stderr)
        print(raw[:500], file=sys.stderr)
        sys.exit(1)
    items = body if isinstance(body, list) else (body.get('results') or [])
    return [i for i in items if isinstance(i, dict)]


definition = json.load(open(definition_path))
components = definition.get('components', {})
want_registers = sorted({r.get('slug') for r in components.get('registers', {}).values() if r.get('slug')})
want_schemas = sorted({s.get('slug') for s in components.get('schemas', {}).values() if s.get('slug')})

registers = load(reg_path, 'registers')
schemas = load(sch_path, 'schemas')

# POSITIVE CONTROL. A query that cannot match returns the same empty list as a
# genuinely empty instance. The importer just told us how much it wrote; if the
# endpoint disagrees at the "nothing at all" level, the query is the suspect.
if not registers or not schemas:
    print(
        f'::error::OpenRegister returned {len(registers)} register(s) and {len(schemas)} schema(s), '
        f'but the importer reported writing {n_reg_imported} register(s) and {n_sch_imported} schema(s). '
        'An empty result from a query that CANNOT match looks identical to a true zero — '
        'check the endpoint/params before concluding the import failed.',
        file=sys.stderr,
    )
    sys.exit(1)

reg_by_slug = {r.get('slug'): r for r in registers if r.get('slug')}
sch_by_slug = {s.get('slug'): s for s in schemas if s.get('slug')}

missing_registers = [s for s in want_registers if s not in reg_by_slug]
missing_schemas = [s for s in want_schemas if s not in sch_by_slug]

print(f'[ci-seed] registers present: {sorted(reg_by_slug)}', file=sys.stderr)
print(f'[ci-seed] schemas present:   {sorted(sch_by_slug)}', file=sys.stderr)

if missing_registers or missing_schemas:
    if missing_registers:
        print(f'::error::DocuDesk registers missing after import: {missing_registers}', file=sys.stderr)
    if missing_schemas:
        print(f'::error::DocuDesk schemas missing after import: {missing_schemas}', file=sys.stderr)
    sys.exit(1)

print(
    f'[ci-seed] register/schema verification OK '
    f'({len(want_registers)} registers, {len(want_schemas)} schemas).',
    file=sys.stderr,
)

# ── Derive the object-type bindings an administrator would set by hand. ──
# For every schema DocuDesk ships, bind it to the register that actually holds
# it. Prefer the register the definition file declares it in, so a schema slug
# that also happens to exist in an unrelated register (another app's import)
# cannot capture the binding.
declared_home = {}
for reg in components.get('registers', {}).values():
    reg_slug = reg.get('slug')
    for schema_slug in reg.get('schemas', []) or []:
        declared_home.setdefault(schema_slug, reg_slug)

for schema_slug in want_schemas:
    schema_id = sch_by_slug[schema_slug].get('id')
    home_slug = declared_home.get(schema_slug)
    register = reg_by_slug.get(home_slug) if home_slug else None
    if register is None:
        # Fall back to whichever register lists this schema's id.
        for candidate in registers:
            if schema_id in (candidate.get('schemas') or []):
                register = candidate
                break
    if register is None or schema_id in (None, ''):
        print(
            f'::error::could not resolve a register for schema "{schema_slug}" '
            '— the app-config binding would be written empty, which surfaces later '
            'as a 500 from the endpoint that uses it.',
            file=sys.stderr,
        )
        sys.exit(1)
    print(f'{schema_slug}_register\t{register.get("id")}')
    print(f'{schema_slug}_schema\t{schema_id}')
    print(f'{schema_slug}_source\topenregister')
PY

# ── 3. Bind the object types in DocuDesk's app config ────────────────────────
# Written with `occ` rather than `POST /apps/docudesk/api/settings`: that route
# is `#[AuthorizedAdminSetting]` WITHOUT `@NoCSRFRequired`, so a basic-auth curl
# is rejected before the controller runs, and harvesting a request-token in bash
# would be a second, fragile session. `occ` is the documented administrative
# path and needs no session at all. It also writes strictly more keys than the
# settings endpoint allows (its `WRITABLE_KEYS` allowlist covers only
# publicationConsent/template), and signing needs `signingRequest_*` /
# `signerRecord_*`.
if [ ! -f "${NC_ROOT}/occ" ]; then
	echo "::error::occ not found at ${NC_ROOT}/occ — cannot bind DocuDesk's object types."
	exit 1
fi

BOUND=0
while IFS=$'\t' read -r key value; do
	[ -z "$key" ] && continue
	php "${NC_ROOT}/occ" config:app:set docudesk "$key" --value="$value" --output=plain > /dev/null
	BOUND=$((BOUND + 1))
done < "$BINDINGS"
echo "[ci-seed] bound ${BOUND} DocuDesk object-type config keys."

# ── 4. Verify the bindings through the app's own settings endpoint ───────────
# `SettingsController::index` is the exact read path `OpenRegisterResolver`
# consumes, so verifying HERE proves the binding is visible to the code that
# will use it — not merely present in `oc_appconfig`.
SETTINGS_BODY="$(mktemp)"
SETTINGS_CODE="$(
	curl -sS -o "$SETTINGS_BODY" -w '%{http_code}' \
		-u "${USER_NAME}:${USER_PASS}" -H 'OCS-APIRequest: true' \
		"${BASE}/index.php/apps/docudesk/api/settings" || echo 000
)"
echo "[ci-seed] GET /api/settings -> ${SETTINGS_CODE}"

if [ "$SETTINGS_CODE" != "200" ]; then
	echo "::error::DocuDesk settings endpoint returned HTTP ${SETTINGS_CODE}; cannot verify the object-type bindings."
	head -c 1000 "$SETTINGS_BODY"; echo
	exit 1
fi

python3 - "$SETTINGS_BODY" <<'PY'
import json, sys

body = json.load(open(sys.argv[1]))
configuration = body.get('configuration') or {}
# These are the bindings the e2e workflow suite actually depends on:
# templates-crud drives create/update (template_* + templateVersion_*).
required = [
    'template_register', 'template_schema',
    'templateVersion_register', 'templateVersion_schema',
    'publicationConsent_register', 'publicationConsent_schema',
]
missing = [k for k in required if not configuration.get(k)]
print(f'[ci-seed] settings.configuration: {json.dumps(configuration, sort_keys=True)}')
if missing:
    print(f'::error::DocuDesk object-type bindings still empty after seeding: {missing}')
    print('::error::Template/consent writes would 500 with "register/schema not configured".')
    sys.exit(1)
print('[ci-seed] object-type bindings OK.')
PY

echo "[ci-seed] DocuDesk registers, schemas and object-type bindings provisioned."

# ── 4b. Probe WebDAV, which the file-backed workflow specs depend on ─────────
# `tests/e2e/workflows/_fixtures.ts` seeds real Nextcloud files through
# `/remote.php/dav/files/admin/...` for the signing and anonymisation journeys.
# On run 30801457803 the MKCOL came back 404 with nothing in nextcloud.log, and
# a 404 from DAV is ambiguous: it is what you get from a missing service
# mapping, from a user whose home has not been initialised, AND from a genuinely
# absent parent collection. Probing here — with the same admin credentials, at
# the point the environment is being prepared — turns that into a printed fact
# instead of a spec failure that accuses the fixture.
#
# Informational: it prints, it does not gate. The specs remain the authority on
# whether the journeys work.
DAV_ROOT="${BASE}/remote.php/dav/files/${USER_NAME}"
DAV_PROBE="ci-seed-probe-$$"
dav() {
	curl -sS -o /dev/null -w '%{http_code}' -u "${USER_NAME}:${USER_PASS}" \
		-X "$1" "${DAV_ROOT}/${2}" ${3:+--data-binary "$3"} || echo 000
}
echo "[ci-seed] dav PROPFIND root      -> $(curl -sS -o /dev/null -w '%{http_code}' \
	-u "${USER_NAME}:${USER_PASS}" -X PROPFIND -H 'Depth: 0' "${DAV_ROOT}/" || echo 000)"
echo "[ci-seed] dav MKCOL   ${DAV_PROBE} -> $(dav MKCOL "$DAV_PROBE")"
echo "[ci-seed] dav PUT     ${DAV_PROBE}/x.txt -> $(dav PUT "${DAV_PROBE}/x.txt" 'probe')"
echo "[ci-seed] dav DELETE  ${DAV_PROBE} -> $(dav DELETE "$DAV_PROBE")"

# ── 5. Warm the SPA so the first spec doesn't pay the cold start ─────────────
# The runner serves Nextcloud with `php -S`. Even with PHP_CLI_SERVER_WORKERS=8
# the first hit pays a cold opcache, the first parse of the webpack bundle, and
# — specific to DocuDesk — `Application::boot()`'s own register import, which
# runs on the FIRST request after enable and touches 5 registers, 20 schemas and
# 46 objects. Paying that here, in the environment-preparation step, is where it
# belongs; the alternative (raising the first spec's timeout) would hide a cold
# start inside an assertion and keep drifting upward.
#
# Failures are ignored on purpose: this is a warm-up, not a gate. The real
# checks are above and below.
for path in \
	"/index.php/apps/docudesk/" \
	"/index.php/apps/docudesk/api/settings" \
	"/index.php/apps/docudesk/api/templates" \
	"/index.php/apps/docudesk/api/signing/requests" \
	"/index.php/apps/openregister/api/registers?_limit=1"
do
	code="$(curl -sS -o /dev/null -w '%{http_code}' -u "${USER_NAME}:${USER_PASS}" \
		-H 'OCS-APIRequest: true' "${BASE}${path}" || echo 000)"
	echo "[ci-seed] warm ${path} -> ${code}"
done

# Pull the main webpack bundle once so it is in the page cache.
#
# Do NOT hardcode the URL. Nextcloud serves an app's assets from whichever apps
# directory it was installed into — `/apps/docudesk/js/...` on the CI runner,
# `/custom_apps/docudesk/js/...` in the docker dev images — and asking for the
# wrong one does not 404. It returns **HTTP 200 with `text/html`**: the NC error
# page, served through index.php. A status-code check therefore reports success
# while fetching a 40 KB HTML page instead of a multi-MB bundle, so the warm-up
# silently warms nothing.
#
# Read the real src out of the rendered app page instead, and verify the
# response is actually JavaScript.
APP_HTML="$(mktemp)"
curl -sS -u "${USER_NAME}:${USER_PASS}" -H 'OCS-APIRequest: true' \
	"${BASE}/index.php/apps/docudesk/" -o "$APP_HTML" || true

# `|| true` is load-bearing: grep exits 1 when it matches nothing, and under
# `set -euo pipefail` that aborts the script right here — so the case the gate
# below exists to explain (no bundle) would die with a bare non-zero exit and
# none of the diagnosis. Let it fall through to the gate instead.
BUNDLE_SRC="$(grep -oE 'src="[^"]*docudesk-main[^"]*"' "$APP_HTML" \
	| head -1 | sed 's/^src="//; s/"$//' || true)"

if [ -n "$BUNDLE_SRC" ]; then
	BUNDLE_INFO="$(curl -sS -o /dev/null \
		-w '%{http_code} %{content_type} %{size_download}' \
		-u "${USER_NAME}:${USER_PASS}" "${BASE}${BUNDLE_SRC}" || echo '000 - 0')"
	echo "[ci-seed] warm bundle ${BUNDLE_SRC} -> ${BUNDLE_INFO}"
else
	echo "[ci-seed] could not locate the bundle src in the rendered app page."
	BUNDLE_INFO=""
fi

# On CI this is a GATE, not a warm-up.
#
# The single most likely way this job "succeeds" dishonestly is by passing
# without ever loading the app — and the environment hides it well: when the
# bundle is absent, Nextcloud does not 404. It serves its HTML error page with
# **HTTP 200 and Content-Type text/html**, so `npm run build` producing nothing
# looks, to every status-code check in the pipeline, exactly like success.
#
# The specs are the honest signal; this check just makes the cause loud and
# immediate instead of arriving as a wall of selector timeouts.
if [ "${GITHUB_ACTIONS:-}" = "true" ] || [ "${CI:-}" = "true" ]; then
	case "$BUNDLE_INFO" in
		*javascript*)
			echo "[ci-seed] bundle verified as JavaScript."
			;;
		*)
			echo "::error::The DocuDesk frontend bundle did not serve as JavaScript (got: ${BUNDLE_INFO:-<not found>})."
			echo "::error::The SPA cannot mount, so every UI spec would fail on a selector timeout with a misleading cause."
			echo "::error::Check the 'Build app frontend' step — a missing bundle returns HTTP 200 text/html, not 404."
			exit 1
			;;
	esac
fi

echo "[ci-seed] done."
