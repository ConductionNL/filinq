# document-signing Specification (delta)

---
status: proposed
---

## Purpose

Surface the standard OR leaves on the signing records: NC Mail linkage and the calendar
deadline leaf on `signingRequest`, and the contacts leaf on `signerRecord`. Consumes the
`integration-email`, `integration-calendar`, and `integration-contacts` leaves via the
registry (ADR-019, ADR-022); changes no signing behaviour.

## ADDED Requirements

### Requirement: Signing Request Is A Mail Link Target

The `signingRequest` schema SHALL declare `configuration.linkedTypes` containing `"mail"`,
so that OR's `EmailService` offers signing requests as link targets in the NC Mail sidebar
and linked messages surface on the signing-request record. The linkage SHALL be link-only:
NC Mail owns compose/send, and linking a message SHALL NOT create, advance, or cancel any
signing state.

#### Scenario: A provider email is linked to its signing request

- GIVEN a signing request for "contract-2026-114.pdf" and a related message in NC Mail
- WHEN the user links the message to the signing request from the Mail sidebar
- THEN the message SHALL appear on the signing request's comms/leaf surface
- AND the signing request's `status`, `signerIds`, and `deadline` SHALL be unchanged

#### Scenario: Mail app absent

- GIVEN a host instance without NC Mail
- WHEN the signing-request detail surface renders
- THEN the mail leaf SHALL NOT be present and the page SHALL render without error

### Requirement: Signing Deadline Surfaces On The Calendar Leaf

The `signingRequest` schema SHALL declare `configuration.linkedTypes` containing
`"calendar"`, so the calendar leaf renders on the signing-request record and the request's
`deadline` is visible as its temporal anchor. The `signingRequest` object SHALL remain the
canonical store of the deadline; the leaf SHALL NOT introduce a second write path for it.

#### Scenario: An expiring request is visible as a deadline

- GIVEN a signing request with `deadline` 2026-09-01 and `status` "pending"
- WHEN the initiator opens the signing-request record
- THEN the calendar leaf SHALL be rendered with the request's deadline visible

#### Scenario: A request without a deadline renders no calendar entry

- GIVEN a signing request with no `deadline` set
- WHEN its record surface renders
- THEN the calendar leaf SHALL render without an entry for that object and SHALL NOT error

### Requirement: Signers Bridge To NC Contacts Via The Contacts Leaf

The `signerRecord` schema SHALL declare `configuration.linkedTypes` containing
`"contacts"`, so the contacts leaf renders on the signer surface and a signer can be
linked to (or looked up as) an NC Contacts entry. This is an authenticated UI surface
under DocuDesk's normal access control; it SHALL NOT expose `signatureData` or
`ipAddress` through the leaf, and it SHALL NOT alter the MCP exclusion of `signerRecord`
declared by `docudesk-mcp-adoption`.

#### Scenario: A signer is linked to a contact

- GIVEN a signer record with `displayName` "J. de Vries" and `email` set
- WHEN the caseworker uses the contacts leaf on the signer surface
- THEN the signer SHALL be linkable to the matching NC Contacts entry
- AND the leaf SHALL show contact identity fields only — never `signatureData` or `ipAddress`

#### Scenario: Contacts leaf does not widen the agent surface

- GIVEN the contacts leaf enabled on `signerRecord`
- WHEN an agent enumerates DocuDesk's MCP tool surface
- THEN no tool exposes `signerRecord` (the `docudesk-mcp-adoption` exclusion still holds)
