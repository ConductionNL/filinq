# MCP tools for assistants

## Overview

An assistant (for example a Hermiq agent) can work with Filinq documents through
OpenRegister's MCP server. Filinq does not run its own MCP endpoint and does not
decide who may use a tool: it declares its tools, OpenRegister lists them and
checks every call against the agent's tool grants.

The surface is small on purpose. It is the read tools OpenRegister derives from
the Filinq register, plus three hand-written ("curated") tools for the things a
schema cannot express.

## Which operation uses which tool

| What the assistant wants | Tool | Kind |
|---|---|---|
| Which letter templates are there? | `filinq.template.search`, `filinq.template.get` | derived, read |
| Write a letter from a template | `filinq.generateCorrespondence` | curated |
| Is this document anonymised yet? | `filinq.getDocumentStatus` | curated, read |
| Anonymise this document | `filinq.anonymizeDocument` | curated, intake only |
| Which letters were sent, which documents were made, signing state, batches, legal bases, dossiers, branding | `filinq.<schema>.search` and `.get` for `correspondence`, `generatedDocument`, `signingRequest`, `batchCorrespondenceJob`, `base`, `dossier`, `huisstijl` | derived, read |

There is no second tool for listing templates or generating a document, and
no derived tool creates, updates or deletes anything. A schema is only
reachable when the register opens it with `configuration.x-openregister-mcp`.
Signing records and sessions, publication consents and prohibitions,
anonymisation links, financial extractions, template versions and the ledger
schemas are not opened.

### `filinq.getDocumentStatus`

Takes a `fileId` and answers, for a file the caller can read:

- `status`, the processing state of the file
- `entityCount`, `anonymizedCount` and `entityCounts`, the number of detected
  entities per type
- `riskLevel`
- `reviewChecked` and `reviewCheckedAt`, whether a person checked the review
- `ocrAvailable`, `ocrProcessed` and `ocrConfidence`
- references to the generation and signing records of the file

A file the caller cannot read answers `status: not_found`, the same answer as a
file that does not exist. Hints: read scope, read-only, not destructive,
idempotent.

### `filinq.anonymizeDocument`

Takes a `fileId` and nothing else. It runs the intake: text extraction, entity
detection, the proposed legal grounds, the policy match, and it puts the
document in the review queue. It answers `status` (`review_required`,
`refused`, `failed` or `not_found`), `entityCounts`, `reviewRequired` and, for
a refusal, `refusedReason`.

The tool never marks a document as reviewed and never makes the anonymised
export. A person decides that in the review screen, as on every other route.
The prohibition gate applies as everywhere: a document with a prohibited entity
is refused with the gate's reason, and there is no parameter through which an
agent could acknowledge or override the gate. The source file is never changed.
Hints: create scope, not read-only, not destructive, not idempotent (a second
run detects again).

## No entity values leave Filinq

No tool answer contains detected entity values, the placeholder map, document
text or file contents. Entities are reported as counts per type. The values are
shown only in the review screen, to people who may see them, and that screen is
audited. The unit tests pin the answer shapes of both curated tools, so a change
that adds entity values to an answer fails the build.

## Who may use the tools

Grants are administered in OpenRegister, not in Filinq: an agent sees and calls
only the tools it was granted, and nothing by default. See OpenRegister's MCP
documentation for granting tools to an agent. Every call leaves a record: a
generated letter is a `correspondence` row with the person it was made for, and
an anonymisation intake run is audited in OpenRegister and attributed `mcp`,
apart from `manual` and `flow` runs.

## Checking the surface

`tests/integration/Mcp/McpSurfaceTest.php` asks a running OpenRegister for its
tool list and checks the Filinq part: the derived read tools the register
declares (16 today) plus the three curated tools, complete hints on the curated
tools, no write verbs, no tool on a schema the register does not open, and
`fileId` as the only input of `filinq.anonymizeDocument`. Set `NC_BASE_URL` (or
`MCP_URL`), `NC_ADMIN_USER` and `NC_ADMIN_PASSWORD`; without them the test is
skipped.
