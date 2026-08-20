#!/usr/bin/env bash
#
# DocuDesk API-contract test runner (Newman / Postman).
#
# Runs tests/integration/docudesk.postman_collection.json against a live
# Nextcloud instance serving the docudesk app. The collection is self-contained
# and idempotent: setup seeds a template and a signing request and captures their
# ids; teardown deletes everything it created.
#
# Usage:
#   ./run-newman.sh                                  # defaults to localhost:8080, admin:admin
#   BASE_URL=http://localhost:8080 ./run-newman.sh
#   ADMIN_USER=admin ADMIN_PASS=admin ./run-newman.sh
#
# Uses a globally-installed `newman` if present, otherwise falls back to
# `npx newman`. Runs are serialised via flock (when available) so concurrent
# CI agents do not trip the Nextcloud brute-force protection.
#
# SPDX-License-Identifier: EUPL-1.2
# SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>

set -euo pipefail

# Re-exec under an exclusive flock so parallel agents serialise.
LOCK_FILE="/tmp/uiaudit-docudesk.lock"
if [ "${DOCUDESK_NEWMAN_LOCKED:-}" != "1" ] && command -v flock >/dev/null 2>&1; then
  export DOCUDESK_NEWMAN_LOCKED=1
  exec flock "${LOCK_FILE}" "$0" "$@"
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Run EVERY collection in this directory, exactly as CI does. This named a
# single file, so a local "newman is green" reported on one of three
# collections while CI's flat `*.postman_collection.json` glob ran all of
# them — a local runner that covers less than CI turns a passing local run
# into evidence for a claim it never tested.
COLLECTIONS=()
while IFS= read -r _c; do
  COLLECTIONS+=("${_c}")
done < <(find "${SCRIPT_DIR}" -maxdepth 1 -name '*.postman_collection.json' | sort)

if [ "${#COLLECTIONS[@]}" -eq 0 ]; then
  echo "ERROR: no *.postman_collection.json found in ${SCRIPT_DIR}" >&2
  exit 1
fi

BASE_URL="${BASE_URL:-http://localhost:8080}"
ADMIN_USER="${ADMIN_USER:-admin}"
ADMIN_PASS="${ADMIN_PASS:-admin}"

# Authenticated requests use baseUrl; the authorization (no-auth) tests use a
# DIFFERENT host alias so the session cookie that authenticated requests
# establish (host-scoped) is never sent to them — keeping them genuinely
# unauthenticated. Defaults to the 127.0.0.1 form of baseUrl.
if [ -n "${NO_AUTH_BASE:-}" ]; then
  NOAUTH_BASE="${NO_AUTH_BASE}"
elif [[ "${BASE_URL}" == *"localhost"* ]]; then
  NOAUTH_BASE="${BASE_URL/localhost/127.0.0.1}"
else
  NOAUTH_BASE="${BASE_URL/127.0.0.1/localhost}"
fi

if command -v newman >/dev/null 2>&1; then
  NEWMAN=(newman)
else
  NEWMAN=(npx --yes newman)
fi

# --ignore-redirects: assert NC's 401-on-unauthenticated directly instead of
# following a 303 to the login page (so the authz tests are honest).
FAILED=0
for COLLECTION in "${COLLECTIONS[@]}"; do
  echo ""
  echo "=== Running: $(basename "${COLLECTION}") ==="
  # `baseUrl` differs per collection: docudesk.postman_collection.json builds
  # full /index.php/apps/docudesk/... paths from a bare origin, while the two
  # collections moved here from tests/newman carry their own baseUrl defaults.
  # An --env-var always overrides a collection variable, so only pass the ones
  # a collection actually declares.
  if [ "$(basename "${COLLECTION}")" = "docudesk.postman_collection.json" ]; then
    "${NEWMAN[@]}" run "${COLLECTION}" \
      --env-var "baseUrl=${BASE_URL}" \
      --env-var "noAuthBase=${NOAUTH_BASE}" \
      --env-var "adminUser=${ADMIN_USER}" \
      --env-var "adminPass=${ADMIN_PASS}" \
      --ignore-redirects \
      --reporters cli \
      --color on \
      "$@" || FAILED=1
  else
    "${NEWMAN[@]}" run "${COLLECTION}" \
      --env-var "username=${ADMIN_USER}" \
      --env-var "password=${ADMIN_PASS}" \
      --ignore-redirects \
      --reporters cli \
      --color on \
      "$@" || FAILED=1
  fi
done

if [ "${FAILED}" -ne 0 ]; then
  echo "ERROR: one or more Newman collections failed." >&2
  exit 1
fi
