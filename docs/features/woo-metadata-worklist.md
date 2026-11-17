# Woo metadata worklist

The Woo index needs six DiWoo fields on every document that is published
actively: the information category, the document type, the publisher, the
official title, the date the document was made and the publication date. The
hand-off refuses a publication that misses one of them. The worklist shows,
across all publications, which ones still miss what, and lets a Woo
coordinator fill the same value on many of them at once.

## Which publications miss metadata

Every publication says which of the six fields it still misses. Filinq works
this out from the publication itself every time it is read, so a field that
is emptied later shows up as missing again.

Under **Publications**, turn on **Metadata incomplete** to see only the
publications that miss at least one field. The **Missing metadata** column
names the fields each one misses.

## Fill one value on a selection

Select the publications and choose **Set metadata**. Pick the field and the
value:

- the information category, chosen from OpenCatalogi's TOOI list;
- the document type;
- the publisher (TOOI identifier).

Filinq writes the value on every selected publication and records it in the
log of each one. A publication that was already handed off is skipped and
listed as skipped, so a published record is never changed this way.
Afterwards the dialog shows what was updated and what was skipped.

## Default publisher

An administrator can set **Publisher (TOOI identifier)** in the admin
settings. A new publication starts with that publisher, so the field is
complete from the start for organisations that publish under one name.

## Related

- [Woo publication pipeline](./woo-publicatie-pipeline.md): the checks, the
  metadata form and the hand-off to OpenCatalogi.
