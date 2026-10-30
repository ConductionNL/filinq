# leaves-library-card

## Why

The documents leaf drew no card of its own on a case, and DqZaak lists a case's documents inside the "Gekoppeld" side
card. nextcloud-vue now hosts a leaf in the library card (integration-leaf-card, `chrome: "card"` + `onHeader`) and lists
leaves in a `linked` card (linked-integrations-card, `onSummary`).

## What changes

- The descriptor declares `chrome: "card"`.
- Given `onHeader`, the leaf reports its count and empty state and drops its own empty line.
- Given `onSummary`, it draws nothing and reports its count, the noun "documents" and a row per document linking to it.
