#!/usr/bin/env bash
# SPDX-License-Identifier: EUPL-1.2
# SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
#
# Bring up every office suite and verify each one SEPARATELY.
#
#   bash docs/office-suites/test-locally.sh            # all four
#   bash docs/office-suites/test-locally.sh eurooffice # just one
#
# Assumes the shared dev stack is running (nextcloud + conduction-network).
# Images total ~10.5 GB; first run downloads them.

set -uo pipefail
cd "$(dirname "$0")/../.."          # repo root

NC="${NC_CONTAINER:-nextcloud}"
WANT="${1:-all}"

# suite | container | internal base url | host port
SUITES=(
  "onlyoffice|docudesk-onlyoffice|http://docudesk-onlyoffice|8092"
  "eurooffice|docudesk-eurooffice|http://docudesk-eurooffice|8093"
  "collabora|docudesk-collabora|http://docudesk-collabora:9980|9980"
  "libreoffice|docudesk-libreoffice|http://docudesk-libreoffice:2004|8094"
)

echo "### 1. fixture the suites can fetch"
# Every suite pulls the probe document over HTTP from the Nextcloud container.
# Without this, conversion checks fail with a DOWNLOAD error that looks like a
# suite problem — it is not.
docker exec "$NC" sh -c 'mkdir -p /tmp/officefx' >/dev/null 2>&1
if ! docker exec "$NC" test -f /tmp/officefx/probe.docx 2>/dev/null; then
  docker exec "$NC" php -r '
    require "/var/www/html/custom_apps/docudesk/vendor/autoload.php";
    $w = new \PhpOffice\PhpWord\PhpWord(); $s = $w->addSection();
    $s->addTitle("Probe document", 1);
    $s->addText("The assessment period is eight weeks.");
    $s->addText("Kind regards,");
    \PhpOffice\PhpWord\IOFactory::createWriter($w, "Word2007")->save("/tmp/officefx/probe.docx");
  ' >/dev/null 2>&1 && echo "  built /tmp/officefx/probe.docx"
fi
# `pgrep` alone is unreliable here — it has matched a dead server before. Ask the
# port instead: what answers is the only thing that matters.
if ! docker exec "$NC" curl -sf -o /dev/null --max-time 3 http://localhost:8123/probe.docx 2>/dev/null; then
  docker exec -d "$NC" php -S 0.0.0.0:8123 -t /tmp/officefx
  sleep 3
fi
docker exec "$NC" curl -s -o /dev/null -w "  fixture server: HTTP %{http_code}\n" \
  --max-time 5 http://localhost:8123/probe.docx

echo
echo "### 2. start the suites"
for row in "${SUITES[@]}"; do
  IFS='|' read -r suite container base port <<<"$row"
  [ "$WANT" != "all" ] && [ "$WANT" != "$suite" ] && continue
  printf '  %-12s ' "$suite"
  if [ "$(docker inspect "$container" --format '{{.State.Status}}' 2>/dev/null)" = "running" ]; then
    echo "already running"
  else
    docker compose -f docker-compose.office.yml --profile "$suite" up -d >/dev/null 2>&1 \
      && echo "started (first boot 1-4 min)" || echo "FAILED to start"
  fi
done

echo
echo "### 3. wait for readiness"
# Readiness is asked of the CONNECTION THAT MATTERS: Nextcloud reaching the suite.
# Container health is not that connection — Collabora's image has no shell, so its
# health status once said `unhealthy` while it was serving perfectly.
for row in "${SUITES[@]}"; do
  IFS='|' read -r suite container base port <<<"$row"
  [ "$WANT" != "all" ] && [ "$WANT" != "$suite" ] && continue
  printf '  %-12s ' "$suite"
  for _ in $(seq 1 60); do
    code=$(docker exec "$NC" curl -s -o /dev/null -w '%{http_code}' --max-time 5 "$base/" 2>/dev/null || echo 000)
    [ "$code" != "000" ] && [ "$code" != "502" ] && break
    sleep 5
  done
  echo "HTTP $code"
done

echo
echo "### 4. verify each suite, separately"
for row in "${SUITES[@]}"; do
  IFS='|' read -r suite container base port <<<"$row"
  [ "$WANT" != "all" ] && [ "$WANT" != "$suite" ] && continue
  bash docs/office-suites/verify-suite.sh "$suite" "$container" "$base"
done

echo "### 5. Nextcloud's own view"
docker exec "$NC" php occ docudesk:office:probe 2>&1 || echo "  (docudesk not deployed here)"

cat <<'NOTE'

### 6. Open a document in a browser

  http://localhost:8080/index.php/apps/onlyoffice/<fileId>
  http://localhost:8080/index.php/apps/eurooffice/<fileId>
  http://localhost:8080/index.php/apps/richdocuments/index?fileId=<fileId>

Find a fileId with:
  docker exec nextcloud php occ files:scan --path=/admin/files >/dev/null
  # then look in Files, or PROPFIND for oc:fileid

REMEMBER: the connector apps live INSIDE the nextcloud container and are NOT
bind-mounted. `clean-env.sh` deletes them and nothing reports it — the editor
just stops working. Re-run the install steps on each suite's page.
NOTE
