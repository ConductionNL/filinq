#!/usr/bin/env bash
# SPDX-License-Identifier: EUPL-1.2
# SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
#
# Verify ONE office suite, independently of every other.
#
# Each suite gets the same eight checks and its own answer. Nothing here reads
# another suite's result, and no check is skipped because a sibling passed it —
# that is exactly how "Euro-Office / ONLYOFFICE" came to be treated as one
# product in this repository on 2026-08-16, with an ONLYOFFICE measurement
# printed under a Euro-Office heading.
#
# Usage:  verify-suite.sh <suite> <container> <internal-base-url>
#   e.g.  verify-suite.sh onlyoffice filinq-onlyoffice http://filinq-onlyoffice
#
# Exit code is NOT the verdict — read the table. Several checks are expected to
# fail for some suites (Collabora serves no /healthcheck; LibreOffice headless
# has no HTTP surface at all), and a suite is not worse for being a different
# shape. What matters is that its row says what IS true of it.

set -uo pipefail

SUITE="${1:?suite name}"
CONTAINER="${2:?container name}"
BASE="${3:?internal base url}"
NC="${NC_CONTAINER:-nextcloud}"

pass() { printf '  %-42s %s\n' "$1" "PASS  $2"; }
fail() { printf '  %-42s %s\n' "$1" "FAIL  $2"; }
info() { printf '  %-42s %s\n' "$1" "----  $2"; }

echo "=============================================================="
echo " suite=$SUITE  container=$CONTAINER  base=$BASE"
echo " $(date -u '+%Y-%m-%dT%H:%M:%SZ')"
echo "=============================================================="

# 1. Is the container running at all?
state=$(docker inspect "$CONTAINER" --format '{{.State.Status}}' 2>/dev/null || echo "absent")
if [ "$state" = "running" ]; then pass "container running" "$state"; else fail "container running" "$state"; fi

# 2. Health, when the image declares a healthcheck. "no healthcheck" is a fact
#    about the image, not a failure of the suite.
health=$(docker inspect "$CONTAINER" --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}no-healthcheck{{end}}' 2>/dev/null || echo "absent")
case "$health" in
  healthy) pass "container health" "$health" ;;
  no-healthcheck) info "container health" "image declares none" ;;
  *) fail "container health" "$health" ;;
esac

# 3. Reachable FROM NEXTCLOUD. Not from the host: the host answering proves
#    nothing about the connection Nextcloud actually makes.
code=$(docker exec "$NC" curl -s -o /dev/null -w '%{http_code}' --max-time 10 "$BASE/" 2>/dev/null || echo 000)
if [ "$code" != "000" ]; then pass "reachable from nextcloud" "HTTP $code"; else fail "reachable from nextcloud" "no answer"; fi

# 4. WOPI discovery. THE probe that separates "installed" from "usable"
#    (ADR-087 §3). Tried at every path a suite might use; the first 200 wins and
#    the path is reported, because which path answered is itself the finding.
wopi_path=""
for p in /hosting/discovery /hosting/wopi/discovery /hosting/capabilities /wopi/discovery; do
  c=$(docker exec "$NC" curl -s -o /dev/null -w '%{http_code}' --max-time 10 "$BASE$p" 2>/dev/null || echo 000)
  if [ "$c" = "200" ]; then wopi_path="$p"; break; fi
done
if [ -n "$wopi_path" ]; then pass "WOPI discovery" "200 at $wopi_path"; else fail "WOPI discovery" "no path answered 200"; fi

# 5. Conversion service, and whether it PARSES rather than passes through.
#    docx -> odt is used deliberately: a same-format conversion can be a
#    passthrough, which on 2026-08-16 returned a byte-identical package that
#    looked like a rewrite.
#    Each family has its OWN conversion API and they are not interchangeable.
#    ONLYOFFICE/Euro-Office take a JSON body naming a URL to fetch;
#    Collabora takes a multipart file upload at a different path. Probing only
#    the first would report Collabora as "conversion unsupported", which is a
#    statement about the prober, not the suite — the same mistake as reporting
#    an ONLYOFFICE measurement under a Euro-Office heading.
conv="unsupported"

# Family A — ONLYOFFICE / Euro-Office: JSON, fetches the document itself.
for endpoint in /ConvertService.ashx /converter; do
  r=$(docker exec "$CONTAINER" curl -s --max-time 60 -X POST "http://localhost$endpoint" \
        -H 'Accept: application/json' -H 'Content-Type: application/json' \
        -d '{"async":false,"filetype":"docx","key":"verify-'"$SUITE"'","outputtype":"odt","title":"probe.docx","url":"http://nextcloud:8123/probe.docx"}' 2>/dev/null || true)
  if echo "$r" | grep -q '"fileUrl"'; then conv="ok at $endpoint (json/url)"; break; fi
  if echo "$r" | grep -q '"error"'; then conv="error at $endpoint: $(echo "$r" | head -c 60)"; fi
done

# Family B — Collabora: multipart upload, converted bytes returned inline.
#
# Driven from the NEXTCLOUD container, not from the suite's own. The Collabora
# image ships no shell and no curl, so `docker exec <collabora> sh` fails
# outright. Probing from inside would report "conversion unsupported" for a
# suite that converts perfectly well — a fact about the probe, not the suite.
if [ "${conv#ok}" = "$conv" ]; then
  for endpoint in /cool/convert-to/odt /lool/convert-to/odt; do
    size=$(docker exec "$NC" sh -c \
      "curl -s --max-time 60 -F 'data=@/tmp/officefx/probe.docx' '$BASE$endpoint' -o /tmp/conv-out.odt -w '%{size_download}'" 2>/dev/null || echo 0)
    if [ "${size:-0}" -gt 1000 ]; then conv="ok at $endpoint (multipart, ${size}B)"; break; fi
  done
fi

# Family C — LibreOffice unoserver: multipart, and `convert-to` is a FORM FIELD.
# Passing it as a query parameter returns 400 "Field validation for 'ConvertTo'
# failed on the 'required' tag". Third API, third shape; a harness that knew only
# the first two would report LibreOffice as "conversion unsupported", which would
# be the third time this script blamed a suite for its own limits.
if [ "${conv#ok}" = "$conv" ]; then
  size=$(docker exec "$NC" sh -c \
    "curl -s --max-time 90 -F 'file=@/tmp/officefx/probe.docx' -F 'convert-to=pdf' '$BASE/request' -o /tmp/conv-out.pdf -w '%{size_download}'" 2>/dev/null || echo 0)
  if [ "${size:-0}" -gt 1000 ]; then conv="ok at /request (multipart form-field, ${size}B pdf)"; fi
fi

if [ "${conv#ok}" != "$conv" ]; then pass "conversion" "$conv"; else fail "conversion" "$conv"; fi

# 6. Is a Nextcloud connector app installed for this suite?
app=$(docker exec "$NC" php occ app:list 2>/dev/null | grep -iE "^\s+- (onlyoffice|richdocuments|eurooffice|collabora)" | tr -d ' ' | tr '\n' ' ')
info "NC connector apps present" "${app:-none}"

# 7. Filinq's own capability probe, for THIS suite only. Asking for one suite
#    keeps the row about the suite the row is about -- the whole-fleet output
#    printed here would mix another suite's verdict into this one's report.
probe=$(docker exec "$NC" php occ filinq:office:probe --suite="$SUITE" 2>/dev/null | head -1 || true)
info "filinq probe" "${probe:-not covered by the probe (WOPI suites only)}"

# 8. What the suite reports itself as. A suite that names itself is one less
#    thing anyone has to assume.
ident=$(docker exec "$NC" curl -s --max-time 10 "$BASE/healthcheck" 2>/dev/null | head -c 40 || true)
info "self-report (/healthcheck)" "${ident:-<none>}"

echo
