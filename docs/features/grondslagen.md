# Woo refusal grounds (grondslagen)

When filinq redacts a document for a Woo publication, every redaction cites
the ground it rests on: an exception from article 5.1 or 5.2 of the Woo, such
as 5.1.2.e (respect for personal privacy). There is one list of these grounds
for the whole platform, and it lives in dossiq. Filinq reads it and keeps no
list of its own.

## Where the list comes from

- **dossiq installed**: filinq reads dossiq's list. A label an administrator
  changes in dossiq shows in filinq's pickers and in the grondslagen summary
  of a dossier straight away.
- **dossiq not installed** (or unable to read its own list): filinq reads a
  read-only copy of the list that ships with filinq, the snapshot. It is
  there so redaction keeps working; nobody can edit it in filinq. Install
  dossiq to maintain the list.

The admin settings show which of the two filinq reads under **Woo refusal
grounds**: with dossiq, a link to dossiq's grounds page; without it, the
snapshot's version and its grounds, read-only.

## What is stored

A redaction, a dossier, a publication consent or prohibition rule, and the
proposed basis per entity type store the ground's code, for example
`5.1.2.e`. The pickers offer the grounds that can be cited, labelled with the
code and the name.

Your organisation can still keep bases of its own on the `base` schema. A new
`base` object that names a Woo ground is refused: add or change a Woo ground
in dossiq.

## Upgrading from the old list

Filinq used to seed its own 19 Woo grounds as `base` objects with slugs such
as `art-5-1-2-e`. A new install no longer seeds them, and existing `base`
objects stay readable.

On upgrade, the repair step **Re-point stored Woo grounds to the dossiq
refusal ground codes** rewrites every stored reference to an old slug to its
code (`art-5-1-2-e` becomes `5.1.2.e`): on dossiers, on publication consent
and prohibition rules, and in the proposed basis per entity type. It uses a
fixed one-to-one mapping and never guesses: a slug without a one-to-one code
is left as it is, and the upgrade log names the object and the slug. Running
it again changes nothing, and it deletes nothing.

The bases on detected entities are not rewritten yet; they keep showing the
right label, because filinq reads an old slug through the same mapping.

## Related

- [Woo publication pipeline](./woo-publicatie-pipeline.md)
