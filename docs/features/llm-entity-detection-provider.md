# LLM Entity Detection Provider

## Overview

Filinq finds personal data in documents with OpenRegister's own detection
(regex and, where it runs, Presidio). Those engines are good at identifiers
with a fixed shape — a BSN, an IBAN, an e-mail address — and weaker at
entities that only context reveals: a name written in lower case, a role that
identifies one person, a reference to "the neighbour at number 12".

The LLM entity detection provider lets an organisation add a **local language
model** to detection. The model runs in a Hermiq agentflow on the
organisation's own infrastructure; Filinq sends it the document text and adds
the entities it returns to OpenRegister's entity catalogue, next to the ones
the default detection found. Review, grondslagen and redaction then treat them
like any other detection.

## On-premises, no cloud

The provider is built for instances where document text must not leave the
building:

- Filinq calls **only** the Hermiq endpoint the administrator configured.
- With **"Only allow an endpoint on this server or the local network"** on
  (the default), an endpoint outside the server or the private network is
  refused and the provider counts as unavailable. Turning it off is an explicit
  administrator decision.
- No API key or other secret is stored by this feature.

## Honest fallback

When an organisation is on the language model and it cannot run — the endpoint
is not set up, not local, does not answer, times out, or returns something that
is not an entity list — Filinq runs the default detection instead and **says
so**:

- The detection result names the engine that actually ran
  (`detectionProvider`), whether it fell back (`detectionFellBack`) and the
  warning (`detectionWarning`).
- The review sidebar shows the warning above the entities: *"LLM detection
  unavailable — fell back to the default detection; results may miss
  context-dependent entities."*
- When the language model did run, the sidebar notes that the entities were
  found by the local language model.

A default run is never presented as a language-model run.

## Configuration

Administrators find the section **Entity detection provider** in Filinq's
admin settings:

| Setting | What it does |
| --- | --- |
| Organisation | The organisation whose provider you choose. Defaults to your active organisation. |
| Detection provider | *Default detection (OpenRegister)* or *Local language model (Hermiq agentflow)*. Organisations not opted in keep the default. |
| Hermiq endpoint | The address of the Hermiq instance that runs the agentflow. |
| Agentflow | The agentflow that detects entities. |
| Only allow an endpoint on this server or the local network | Refuses any endpoint outside the server or private network. On by default. |
| Test the connection | Says whether the language model can be used now, and if not, why. |

The settings are stored in Filinq's app configuration
(`detection.provider`, `detection.hermiq_endpoint`,
`detection.hermiq_agentflow`, `detection.require_local`). OpenRegister's own
global detection method is not changed.

## The Hermiq dependency

The provider needs a Hermiq agentflow that accepts the document text and
answers with a JSON object whose `state.entities` holds the entity list. Each
entity's type is mapped to OpenRegister's entity types where it is known and
kept as it is otherwise; its value and positions are stored verbatim and its
score is clamped between 0 and 1. Without Hermiq, or with the agentflow not
configured, every organisation simply keeps the default detection.

## Specification

`openspec/changes/llm-entity-detection-provider/`
