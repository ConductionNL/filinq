# Design: erasing a person while the records stay

Kind: code. One request schema, one preview, one job, one certificate.

## Context

The per-document mechanism exists: `EntityDetectionService`, the shared
entity catalogue, `AnonymizationService`, `BatchAnonymizeService`,
`anonymizationLink` and `entity-search`. The register is `filinq`, one
register since descriptor v8.0.0.

What this change adds is the subject as the unit of work.

## D1. The preview is the feature

Zammad caps its preview (`MAX_PREVIEW_TICKETS`) because an erasure
preview over a large instance is itself a large operation. The preview
answers, before anything is written: which documents, how many
occurrences each, which are final versions, which are under an
obligation, which cannot be processed and why, and how much the cap hid.

An erasure without a preview is a button whose consequences are
discovered afterwards, on records that cannot be restored.

## D2. Matching a person is a declared identity, not a name

A name is not an identity. The request names the person by the
identifiers the instance holds, and the match runs over the entity
catalogue's occurrences plus any custom dictionary terms the operator
adds for this person. Every matched occurrence is reviewable in the
preview, and an occurrence can be excluded with a reason before the job
runs.

Erasing every occurrence of a common surname because one person asked is
the failure mode this guards against.

## D3. A final version is superseded, never edited

Where a document's current version is final, the erasure writes a new
version carrying the anonymised content and referencing the one it
supersedes, per `final-documents-frozen`. The Archiefwet chain stays
intact and the AVG duty is met, which is the whole point of the feature.

The superseded version's file is then itself subject to the same erasure:
the content is replaced, the version record stays. What is kept is the
fact that a version existed and what it was about, not the personal data
in it.

## D4. A refusal is a listed decision

A document under retention, a publication prohibition, or a legal hold is
not erased. It is listed with the obligation named and the person who
must decide. A job that silently skipped would produce a certificate that
lies, and the certificate is the artefact the data subject and the
archivist both read.

## D5. The certificate is the record of the act

What was erased, from which documents, how many occurrences, when, by
whom, on which legal ground, and what was refused with the reason. It is
an OpenRegister object with its own retention, because the record that
an erasure happened must outlive the erasure. It is also the input to
republishing anything opencatalogi already published.

## D6. The mapping goes too

`reversible-pseudonymization` holds an encrypted placeholder-to-value
mapping. For an erased person the mapping entries are destroyed and the
destruction is recorded. A reversible pseudonymisation and an erasure are
opposite operations, and leaving the key behind turns the second into the
first.

## Risks

- **A person who cannot be matched reliably.** The preview is where that
  is seen, and exclusion with a reason is the answer, not a better
  matcher.
- **An enormous preview.** Capped, with the cap stated and the full
  count reported separately, so an operator knows the scale before
  starting.
- **A partial run.** The job is resumable and the certificate records
  what completed. A half-finished erasure that reports success is worse
  than one that reports where it stopped.
- **Republication.** A document already published carries an anonymised
  copy in opencatalogi. The certificate lists them so republication is a
  named follow-up rather than an assumption.
- **The erasure itself processes personal data.** The request, the
  preview and the certificate are declared as their own
  `x-openregister-processing` activity, with purpose, ground and
  retention.
