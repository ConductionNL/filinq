#!/usr/bin/env bash
#
# SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
# SPDX-License-Identifier: EUPL-1.2
#
# Read an OpenRegister list endpoint to the end and write one
# `{"results": [...]}` file.
#
#     fetch-all-pages.sh <url> <out-file> <user:pass> [page-size]
#
# OpenRegister caps a page and returns no total, so one request with a large
# `_limit` silently drops everything past the cap. On the shared dev instance
# (well over a thousand schemas) that made ci-seed.sh report filinq schemas as
# missing and bind nothing. This pages with `_offset` until a page comes back
# shorter than the page size. Any request or JSON failure exits non-zero.
#
# Tested by tests/vitest/ciSeedPaging.spec.js.

set -euo pipefail

URL="$1"
OUT="$2"
CREDENTIALS="$3"
PAGE_SIZE="${4:-500}"

SEPARATOR='?'
case "$URL" in *\?*) SEPARATOR='&' ;; esac

PAGES_DIR="$(mktemp -d)"
trap 'rm -rf "$PAGES_DIR"' EXIT

OFFSET=0
PAGE=0
# A hard stop so a server that ignores `_offset` cannot loop for ever.
MAX_PAGES=200
while [ "$PAGE" -lt "$MAX_PAGES" ]; do
	PAGE_FILE="${PAGES_DIR}/$(printf '%04d' "$PAGE").json"
	curl -sSf -u "$CREDENTIALS" -H 'OCS-APIRequest: true' \
		"${URL}${SEPARATOR}_limit=${PAGE_SIZE}&_offset=${OFFSET}" -o "$PAGE_FILE"
	COUNT="$(python3 -c '
import json, sys
body = json.load(open(sys.argv[1]))
items = body if isinstance(body, list) else (body.get("results") or [])
print(len(items))
' "$PAGE_FILE")"
	PAGE=$((PAGE + 1))
	if [ "$COUNT" -lt "$PAGE_SIZE" ]; then
		break
	fi
	OFFSET=$((OFFSET + PAGE_SIZE))
done

if [ "$PAGE" -ge "$MAX_PAGES" ]; then
	echo "::error::fetch-all-pages: ${URL} still returned full pages after ${MAX_PAGES} requests." >&2
	exit 1
fi

python3 - "$OUT" "$PAGES_DIR" <<'PY'
import glob, json, os, sys

out, pages_dir = sys.argv[1:3]
merged = []
for page in sorted(glob.glob(os.path.join(pages_dir, '*.json'))):
    body = json.load(open(page))
    merged.extend(body if isinstance(body, list) else (body.get('results') or []))
json.dump({'results': merged}, open(out, 'w'))
PY
