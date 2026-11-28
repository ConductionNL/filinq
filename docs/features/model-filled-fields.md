---
id: model-filled-fields
title: Filling record fields from a document
sidebar_label: Model-filled fields
description: A text model suggests the fields of a record from one of its documents, and a person decides each field
keywords:
  - intake
  - text model
  - field suggestions
---

# Filling record fields from a document

A registrar who files an incoming document on a record can let a text model
suggest that record's fields from the document: the applicant, an amount, a
date. The model only suggests. Nothing is written to the record until a person
has decided every field.

## Who reads the text

The document text is read by the text model that Nextcloud task processing
offers for "free text to text". The admin settings name that provider before
the feature can be switched on. If the provider is not installed on this
server (for example a cloud service), the document text leaves the server.
Choose a local provider when that is not acceptable.

## Switching it on

Under **Administration settings → Filinq → Model filling**:

- The section names the provider that would read the documents, or says
  "No text model is available on this server." when there is none. Without a
  provider the switch cannot be turned on.
- The switch is off on a fresh install.
- **Field profiles** declare per register and schema which fields a model may
  suggest. A profile names the register, the schema, the field names and
  whether it is active. A BSN field is refused: "A BSN field cannot be filled
  by a model." Filinq ships one inactive profile for its own `dossier` schema
  (`name` and `description`) so the shape is visible.

## Using it on the intake worklist

1. Assign a document on `/intake` to a record. When model filling is on and
   the record's schema has an active profile, the suggestions open straight
   away. For a document that already sits on a record, choose **Fill fields
   from document** in its row actions.
2. The dialog shows, per field, the value the record holds now, the suggested
   value and the passage of the document it came from. A value that does not
   occur in the text literally says "Not found in the document text". When
   the document was too long, the dialog says the model read only its first
   part.
3. Choose **Accept**, **Change** (and type the value) or **Reject** for every
   field, then **Save decisions**.

The accepted and changed values are written to the record as you, with your
rights: when you may not change the record, nothing is written and the dialog
says so. A field the record already holds keeps its value unless you accept
the suggestion for it. Every decision is kept beside the suggestion (who
decided what, and when), so it stays visible which values a person checked.

## API

| Route | What it does |
|---|---|
| `GET api/extraction/fields/status` | Whether the feature is on and which provider would read the text |
| `PUT api/extraction/fields/status` | Switch it on or off (admin) |
| `POST api/extraction/fields` | Suggest the fields of a record (`fileId`, `register`, `schema`, `objectId`); 409 without a profile, 503 without a model |
| `POST api/extraction/fields/{id}/decide` | Write the decisions (`decisions`: per field `accept`, `change` with `value`, or `reject`) |
| `GET`/`POST`/`PUT api/extraction/field-profiles` | List, create and update the profiles (admin) |
