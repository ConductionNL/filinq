# woo-metadata-worklist Specification (delta)

## Purpose

A Woo coordinator sees which queued publication records miss which DiWoo
fields and fills them for many records at once. Matrix row
`woo-metadata-gaps` (filinq), tender
https://www.tenderned.nl/aankondigingen/overzicht/407973 (VPB-02). Builds
on the publication records of open change `woo-publicatie-pipeline`.

## ADDED Requirements

### Requirement: Every publication record says which DiWoo fields it misses (REQ-WMW-001)

filinq MUST derive, for every publication record, the list of mandatory
DiWoo fields that are empty (`wooCategory`, `documentsoort`, `publisher`,
`officieleTitel`, `creatiedatum`, `publicatiedatum`). The publications index
MUST show that list in a column and MUST offer a filter for records whose
list is not empty.

Rows: `woo-metadata-gaps` (filinq matrix), tender https://www.tenderned.nl/aankondigingen/overzicht/407973

#### Scenario: A Woo coordinator finds the incomplete records

- GIVEN three queued publication records, one complete, one without a Woo category, one without publisher and documentsoort
- WHEN the Woo coordinator opens the publications index and chooses "Metadata incomplete"
- THEN two records are listed, with "Woo category" and "Publisher, documentsoort" in the missing column
- @e2e tests/e2e/woo-metadata-worklist.spec.ts

### Requirement: A coordinator fills one value on a selection (REQ-WMW-002)

The publications index MUST let a Woo coordinator select records and set
one value for `wooCategory` (from the TOOI list only), `documentsoort` or
`publisher` on all of them. filinq MUST write a `publicationLogEntry` with
action `metadata_assembled` per record, and MUST skip and report a record
that is already handed off.

#### Scenario: One publisher for forty records

- GIVEN forty queued records without a publisher
- WHEN the Woo coordinator selects them and sets the publisher to the municipality's TOOI URI
- THEN none of the forty is listed under "Metadata incomplete" for publisher, and each record's log shows the change with the coordinator's name
- @e2e tests/e2e/woo-metadata-worklist.spec.ts

### Requirement: New records start with the organisation's publisher (REQ-WMW-003)

An admin MUST be able to set a default publisher, and a new publication
record MUST start with it.

#### Scenario: An admin sets the default publisher

- GIVEN an admin who sets the default publisher to the municipality's TOOI URI
- WHEN a Woo coordinator starts a new publication from a document
- THEN the new record's publisher is already filled and "Publisher" is not in its missing list
- @e2e tests/e2e/woo-metadata-worklist.spec.ts
