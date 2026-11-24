# Retention under the Archiefwet

Filinq keeps records for as long as the Archiefwet 1995 and the VNG
selectielijst say, and no longer. It does not count days itself: OpenRegister
stamps each record's retention block (its selectielijst category, appraisal,
retention period and archive action date), finds the records that are due,
and destroys or transfers them only through a list an archivist approved.
Filinq supplies the register, the category of each record type and the
screens an archivist works in.

## The selectielijst

The selectielijst rows live in filinq's register as `selectielijstEntry`
objects: a category, the appraisal (`bewaren` or `vernietigen`), the
retention period (ISO 8601, such as `P10Y`), the source and a description.
They are ordinary register objects. Information management keeps them
current in OpenRegister's object screens or through OpenRegister's
selectielijst import (`POST /api/archival/selectielijst/import`). Filinq has
no editor of its own for them.

The record types `correspondence`, `generatedDocument` and
`publicationRecord` each name their category in their schema's `archive`
block. OpenRegister derives each record's archive action date from it: from
`generatedAt` for generated documents and letters, and from the publication
date for publication records.

## Linking the archive register (administrator)

OpenRegister must know where the selectielijst and the destruction lists
are kept. On the filinq settings page, the **Archiefbeheer** section says
whether this is set up. **Link the archive register** (nl: *Koppel
archiefregister*) points OpenRegister's archival settings at filinq's
register, after you confirm. Nothing is linked without that click: no
update step and no background job writes these settings.

## Destruction lists (archivist)

**Destruction lists** shows the vernietigingslijsten OpenRegister proposed
for records whose term has passed. Open a list to see each record, its
schema, its archive action date and its category. A record under a legal
hold says so, and names the case to those allowed to see it.

- **Keep this record** takes the record off the list. A reason is
  required.
- **Approve list** approves every record. With records kept, **Approve the
  rest** approves the others in one step, each kept record with its reason.
- **Reject list** rejects the whole list. A reason is required.

Only an archivist can review a list. OpenRegister checks this and refuses
anyone else.

## Destruction certificates

**Destruction certificates** lists the verklaringen van vernietiging,
newest first. Each one gives the destruction date, the number of records
destroyed, the totals per schema and category, the approvers, the
selectielijst used and the compliance statement. A certificate is a
permanent record and cannot be changed.

## Records due for transfer

**Due for transfer** lists the records to keep permanently (`bewaren`)
whose archive action date has passed, that have not been transferred or
destroyed, carry no legal hold, and are not on a transfer list yet. These
are the records OpenRegister's daily transfer check puts on its next
transfer list for the e-depot. The page is read only: OpenRegister makes and
sends the transfer list. Only an administrator can see this list, and it
needs an OpenRegister release with `GET /api/transfers/eligible`.

A record that has been transferred (`archiefstatus` `overgebracht`) is
shown read only on its document or dossier, marked as transferred to the
e-depot. OpenRegister refuses any change to it.

## Production checklist

Before you switch destruction on:

1. Replace every `TODO-` category in filinq's register with the real VNG
   selectielijst category, its appraisal and its retention period, as
   approved by your selectielijst manager. The placeholders are there only
   so the register imports.
2. Import or check the selectielijst rows those categories point to.
3. Link the archive register on the settings page.
4. Give the archivists their role in OpenRegister, so they can review
   destruction lists.
5. Configure the e-depot in OpenRegister if records are to be transferred.

## Routes used

All of these routes belong to OpenRegister. Filinq adds none.

| Screen | Route |
|---|---|
| Archiefbeheer settings | `GET`/`PUT /apps/openregister/api/settings/archival` |
| Destruction lists | `GET /apps/openregister/api/archival/destruction-lists`, `…/{id}`, `…/{id}/approve`, `…/{id}/reject` |
| Destruction certificates | `GET /apps/openregister/api/archival/certificates` |
| Due for transfer | `GET /apps/openregister/api/transfers/eligible` |
