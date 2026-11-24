#!/usr/bin/env bash
# SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
# SPDX-License-Identifier: EUPL-1.2
#
# template-engine-renders-through-a-vendor task 3.1: a vendor render against
# integriq's mock-mode SmartDocuments source reaches `generated`.
#
# Folder "1. request" asks for the render and keeps integriq's job id; then
# integriq's DocumentGenerationStatusJob (a 300 s TimedJob) is force-executed so
# the mock job settles; folder "2. settled" reads the generatedDocument entry.
#
# Needs a live Nextcloud with filinq, OpenRegister and integriq installed, and
# integriq's shipped SmartDocuments source left in mock mode.
#
#   BASE_URL=http://localhost:8080 ADMIN_USER=admin ADMIN_PASS=admin \
#   OCC='docker exec -u www-data nextcloud php occ' \
#       tests/integration/live/vendor-render.sh

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
COLLECTION="${SCRIPT_DIR}/vendor-render.postman_collection.json"
BASE_URL="${BASE_URL:-http://localhost:8080}"
ADMIN_USER="${ADMIN_USER:-admin}"
ADMIN_PASS="${ADMIN_PASS:-admin}"
OCC="${OCC:-php occ}"
STATUS_JOB='OCA\Integriq\BackgroundJob\DocumentGenerationStatusJob'

if command -v newman >/dev/null 2>&1; then
	NEWMAN=(newman)
else
	NEWMAN=(npx --yes newman)
fi

ENV_FILE="$(mktemp)"
trap 'rm -f -- "${ENV_FILE}"' EXIT
printf '{"values": []}\n' > "${ENV_FILE}"

run_folder() {
	"${NEWMAN[@]}" run "${COLLECTION}" --folder "$1" \
		--environment "${ENV_FILE}" --export-environment "${ENV_FILE}" \
		--env-var "baseUrl=${BASE_URL}" --env-var "adminUser=${ADMIN_USER}" --env-var "adminPass=${ADMIN_PASS}" \
		--ignore-redirects
}

run_folder '1. request'

# shellcheck disable=SC2086 # OCC is a command line, split on purpose.
JOB_IDS="$(${OCC} background-job:list --class="${STATUS_JOB}" --output=json | php -r 'foreach (json_decode(stream_get_contents(STDIN), true) ?: [] as $j) { echo $j["id"], "\n"; }')"
if [ -z "${JOB_IDS}" ]; then
	echo "ERROR: integriq's DocumentGenerationStatusJob is not registered; is integriq enabled?" >&2
	exit 1
fi

for JOB_ID in ${JOB_IDS}; do
	# shellcheck disable=SC2086
	${OCC} background-job:execute "${JOB_ID}" --force-execute
done

run_folder '2. settled'
