# Woo publication pipeline

Under the Woo, a municipality publishes many documents actively. Filinq
prepares a document for that: it checks that the document may be published,
collects the Woo metadata, hands the redacted copy to the publication
platform and keeps a record of every step. OpenCatalogi is the publication
platform; Filinq builds no public portal.

## Start a publication

Open a document in My documents and choose **Publish**. Filinq starts a
publication for it and opens it under **Publications**.

## May it be published?

Filinq runs three checks and shows each with Yes or No:

- **Detected entities checked by a person**: the document has a redacted
  copy, and someone marked its detected entities as checked.
- **Consent requests allow publication**: every consent request for the
  document is settled. Consent given, anonymised, no reaction after the
  objection period, or an objection answered by anonymising all count as
  settled. A request that is still waiting, an objection without that
  decision, or a rejected publication blocks.
- **No publication prohibition applies**: nobody named in the consent
  requests is on the publication prohibition list.

Under the checks, Filinq lists what still blocks publication. It names
records, never people. Each failed check links to the page where you
resolve it. **Check again** runs the checks again.

## Woo metadata

Fill in the official title, the information category (chosen from
OpenCatalogi's TOOI list), the document type, the publisher, the date the
document was made and the date it should go live. The title, the category
and the publication date are needed before a hand-off.

## Hand off

**Hand off for publication** is available when all three checks pass, the
metadata is complete and OpenCatalogi is installed. Filinq runs the checks
again at that moment: if something changed, for example a new objection,
the publication goes back to Not ready and nothing is handed off. Otherwise
Filinq creates the publication in OpenCatalogi with the title, the document
type as summary and the publication date, and attaches the redacted copy.
The original never leaves Filinq. On the publication date the status moves
to Published.

## Withdraw

A publication that was handed off can be withdrawn with a reason. Filinq
sets the depublication date in OpenCatalogi to now, which takes it off every
public page, and never deletes the publication there.

## Destruction date

When the source system will destroy the document, record the date and where
it comes from, for example a selection list. Filinq passes it to
OpenCatalogi as the retention date with a note naming the source, so the
publication goes offline when the document is destroyed. OpenCatalogi's
retention job does the rest.

## History

Every step is logged: started, checked, metadata saved, handed off,
published, withdrawal asked, withdrawn, destruction date passed on. A log
entry is written once and no route changes or removes it.

## What stays in Filinq

OpenCatalogi's publication has no fields for the information category, the
document type as such, or the publisher yet. Filinq keeps them on its own
record until OpenCatalogi can take them.
