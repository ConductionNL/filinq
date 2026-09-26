# Design: the document at the boundary

Kind: code. One registration act, one second rendition, one listener.

## Context

filinq has no `document` schema of its own. A document is a Nextcloud
file plus a record: `correspondence` for what was sent,
`generatedDocument` for what was made. Neither carries a direction, a
registration number or a discharge, and neither is notified on.

`lib/EventListener/` holds nine listeners and none of them watches a
download. There is no `lib/Notification/` in filinq at all, which is the
filinq-side reading of the same absence the ledger note records on the
dossiq side.

`x-openregister-lifecycle` and `x-openregister-notifications` are already
used on filinq schemas, so both mechanisms this change needs are house
mechanisms rather than new ones. The register is `filinq`, one register
since descriptor v8.0.0.

## D1. The number is openregister's, the registration is filinq's

openregister's open change `generated-identifier` puts a sequence and a
format on a schema property: `{year}`, `{seq:5}`, a prefix, `resetOn:
year`, taken under a lock, frozen after creation, and shareable between
schemas. That is the whole of the generator, and writing a second one in
filinq is the duplication ADR-070 and `openspec/config.yaml` both forbid.

What filinq adds is the act around it: the direction, the unit, the
registration date, the scope key that picks the sequence, and the
refusal to renumber.

The unit scope is a sequence name, not a format placeholder. One sequence
per unit means two units never share a counter and neither waits on the
other's lock.

## D2. Gap tolerant is not gapless, and the spec says which one this is

The row asks for a gapless number. openregister's proposal says the
generator is "unique per sequence, gap-tolerant, and never reused after a
rollback". Those are different guarantees and pretending otherwise is how
a spec ships a claim the code cannot make.

So filinq does not claim a series without gaps. It claims a series that
can be read: an allocation that did not become a registration is recorded
as withdrawn, with its reason and its moment. An archivist reading the
register end to end finds every number, and the ones that carry no
document say why. That is what the audit question behind the row actually
needs, and it is checkable by a test.

## D3. The discharge is a link, not a status

An outbound registration names the inbound one it answers. The inbound
entry is not edited into a `discharged` status by the outbound write;
the discharge is read from the link, so the open post list is a query and
never a second copy of the truth that can drift from the first.

A second outbound may answer the same inbound: a herstelverzoek and then
the decision. The link is therefore many to one, and the inbound entry
shows the first discharge date and every number that answered it.

## D4. The plain rendition is a second output of one generation

It is not a second document with its own registration number. Both
renditions come out of one generation, from the same data, bound to the
same `generatedDocument` record and the same registration entry. That is
what keeps them from drifting: a corrected formal letter that leaves its
plain twin stale is worse than no twin at all, so the twin is regenerated
with it or it is not there.

Required statements are declared on the template as merge fields the
plain rendition must resolve. An unresolved one refuses the generation.
A plain letter that leaves out the bezwaartermijn is the failure this
requirement exists to prevent, and a template author cannot be relied on
to notice at three in the afternoon.

## D5. The rewrite is a suggestion

A model may draft the plain text. It may not file it. The draft is
offered, a named person accepts it, and the acceptance is recorded with
the person and the moment. This is the posture
`inbound-auto-classification` and `anonymization-review-workbench`
already take for classification and redaction, and a decision letter is a
worse thing to guess at than either.

## D6. The download handler records and enqueues

ADR-078 measures what a post-event handler costs on the write path. A
download is a read path and the same rule applies harder: the handler
writes the download record and enqueues the notification, and the
notification is rendered and delivered by the job, not by the person
waiting for their file.

The record names the route: the web UI, the API, a public link, or the
portal. A public link download has no session user. The record names the
link and the person who created it, and leaves the downloader
unidentified rather than attributing the download to the link's owner.

## D7. Who is told is declared, and it is never the data subject

`filinq-notifications` already requires staff-only routing and forbids
data-subject external email recipients. The download notification obeys
it. Recipients are the document's steward and whoever declared a watch,
resolved through the notification engine, and a schema that declares a
data-subject recipient on this path fails the same rule.

Repeated downloads of the same document by the same person inside a
declared window collapse to one notification. A notification per click
trains people to ignore the notification, which is the same as not
sending it.

## Risks

- **The sequence lands later than this change.** openregister's
  `generated-identifier` is an open change, not a released mechanism. The
  registration entry is specified against the annotation, and until it
  exists the registration refuses rather than numbering by hand, so
  nothing writes a number that the sequence would later disagree with.
- **A download filinq cannot see.** A file reached through WebDAV or a
  sync client is still a download, and a listener that only watches the
  web route would report a quiet register while copies leave. The
  requirement names the routes and the tests assert each one; a route
  filinq genuinely cannot observe is named in the spec rather than
  implied away.
- **A plain rendition read as the decision.** It is not the legal text.
  Every plain rendition names the formal document it explains, and says
  so on the document itself, not only in the record.
