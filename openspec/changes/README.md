# The open-change backlog, grouped

Compiled 2026-09-07. This file is an INDEX, not a plan: it moves nothing and
decides nothing. It exists because `openspec/changes/` holds 72 directories in
one flat list, and a flat list cannot answer the question the backlog actually
raises — *how many products is Filinq being asked to be?*

## What is in here

| Bucket | Count | Meaning |
|---|---:|---|
| Not started | 48 | No task ticked. A wish list, not a queue. |
| In progress | 12 | Some tasks ticked, some open. |
| Ticked complete | 12 | Every task ticked, not yet archived. **Not yet archivable — see below.** |

Counts are derived from checkbox state, which is **not always truthful**. Three
changes were found on 2026-09-07 carrying `DEFERRED` inside ticked boxes
(`anonymisation-batch-output-folder-layout`, `document-detail-leaf-widgets`,
`signer-consent-notifications-to-email-leaf`) and a fourth,
`publication-policy-labels-and-nav`, was ticked 6 of 6 while half of it had been
silently undone by the manifest migration. All four have been corrected. Treat
"ticked complete" as an upper bound and read the tasks file before trusting it.

## ⚠️ The twelve finished changes cannot be archived yet

Archiving is `git mv openspec/changes/<c> openspec/changes/archive/`, and doing
that to any of these twelve **buries requirements**. Checked 2026-09-07: every
one of them carries a spec delta whose requirements are NOT present in
`openspec/specs/`. `portal-contribution` is representative — its main spec holds
one requirement (`REQ-DDPORT-000`) while its delta adds six.

Nothing reports a buried requirement. The change disappears from
`openspec/changes/`, the capability spec never gains what the change specified,
and the only trace is a directory under `archive/` nobody reads.

**The precondition is `/opsx-sync` per change, not a move.** Roughly forty
requirements across eight capabilities (`anonymization`, `batch-anonymization`,
`document-creatie-sjablonen`, `document-editing`, `document-preview`,
`portal-contribution`, plus `beta-alignment`, `filinq-mcp-surface` and
`filinq-signing-events`, which have no `openspec/specs/` directory at all) need
to land in their specs first.

Verify before moving anything:

```sh
c=portal-contribution
for cap in openspec/changes/$c/specs/*/; do
  cap=$(basename "$cap")
  diff <(grep '^### Requirement:' "openspec/changes/$c/specs/$cap/spec.md") \
       <(grep '^### Requirement:' "openspec/specs/$cap/spec.md")
done
```

## The scope question

The 48 untouched changes are not one product's backlog. They are at least seven,
and each of these is something a company sells on its own:

### Accessibility and PDF conformance (4)
`accessible-redaction-output` · `pdfua-accessible-output` ·
`pdfua-verapdf-matterhorn` · `verapdf-validation`

### Archiving and records management (5)
`archiefwet-retention-engine` · `tmlo-mdto-metadata` · `e-discovery-legal-hold` ·
`document-waarmerk-certification` · `zgw-document-bridge`

### Signing (7)
`signature-verification-portal` · `signer-identity-rails` ·
`signing-cancellation` · `bulk-signing-field-builder` ·
`libresign-signing-provider` · `migrate-signing-to-or-tasks` ·
`signer-consent-notifications-to-email-leaf`

### Anonymisation and redaction (7)
`reversible-pseudonymization` · `image-redaction` · `redaction-at-scale` ·
`anonymization-review-workbench` · `llm-entity-detection-provider` ·
`entity-search` · `document-sanitization`

### Woo publication (2)
`woo-publicatie-pipeline` · `woo-request-workflow`

### Authoring, editing and output formats (6)
`document-rich-editing` · `office-suite-portability` ·
`office-template-authoring` · `multi-format-output` · `document-chart-embedding` ·
`guided-document-wizard`

### Ingestion (3)
`email-ingestion` · `inbound-auto-classification` · `ocr-trigger-surface`

### Contract lifecycle (1)
`contract-lifecycle-management`

### AI and agent tooling (2)
`hermiq-ai-tooling` · `mcp-generation-tools`

### The document register itself (1)
`document-register`

### App infrastructure and quality (10)
Not a product line — the cost of running the app.

`filinq-adopt-buildmanifest-pipeline` · `filinq-policy-controller-test-coverage` ·
`filinq-route-auth-explicit-attributes` · `filinq-nl-locale-parity` ·
`filinq-upload-dropzone-keyboard-access` · `multi-tenant-hardening` ·
`consumer-schema-authorization-audit` · `leaf-integrations` · `flow-operations` ·
`fix-dossier-grondslagen-route-mismatch`

## How to read this

A change sitting at zero for a long time is not necessarily wrong — a backlog is
allowed to hold intentions. What is worth noticing is the **shape**: signing,
redaction and records management each carry enough untouched work to be their own
roadmap, and nothing in the directory structure says which of them Filinq has
actually committed to.

That is a product decision, not a filing one, which is why this file groups
rather than prunes.

## Keeping it honest

Regenerate the counts with:

```sh
for d in openspec/changes/*/; do
  b=$(basename "$d"); [ "$b" = archive ] && continue
  t="$d/tasks.md"; [ -f "$t" ] || continue
  done=$(grep -c '^\s*- \[x\]' "$t"); todo=$(grep -c '^\s*- \[ \]' "$t")
  [ $((done+todo)) -eq 0 ] && continue
  if [ "$done" -eq 0 ]; then echo "NOTSTARTED $b"
  elif [ "$todo" -eq 0 ]; then echo "DONE $b"
  else echo "PARTIAL $b"; fi
done | sort | awk '{print $1}' | uniq -c
```

And find changes whose ticked boxes are not to be believed:

```sh
grep -rlE 'DEFERRED|cross-repo handoff|not yet shipped' openspec/changes/*/tasks.md
```
