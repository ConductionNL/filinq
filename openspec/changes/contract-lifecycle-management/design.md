## Context

DocuDesk's document model treats contracts as inert records with tags. But contracts are actively managed: they cycle through approval gates, collect signatures from multiple parties, approach expiry dates that require action (renew, terminate, let lapse), and often need to be amended mid-life with redline tracking. Teams today run CLM in separate tools (Ironclad, Juro, ContractWorks) or on spreadsheets and email, creating visibility gaps and process debt. This change adds contract-specific state management, versioning, approval workflows, e-signature integration, automated reminders, and renewal lifecycle directly into docudesk so teams can manage contracts end-to-end on the platform.

## Goals / Non-Goals

**Goals:**
- A structured Contract entity with explicit lifecycle states (draft → in_review → awaiting_signature → active → expiring_soon → expired or renewed)
- Immutable version history with redline diffs so legal teams can review changes between drafts
- Configurable approval routes per contract type (e.g. value > €100k requires CFO approval; employment contracts require HR sign-off)
- E-signature integration (DocuSign, SignHey) with envelope tracking, certificate capture, and automatic active-status transition on completion
- Automated reminders triggered by contract dates (expiry, notice period, renewal trigger) with configurable recipients
- A renewal workflow that clones an active contract, prefills counterparty/terms, and links parent → child relationships
- A reusable clause library (standardised legal text by category, jurisdiction, language) with usage tracking and deprecation warnings
- A portfolio surface (search by counterparty, status, value, date, owner, clause content; export CSV/Excel; respect ACL per row)
- Seed data demonstrating realworld personas: a gemeente managing Woo/AVG contracts, an MKB supplier managing customer contracts, a Conduction reference project

**Non-Goals:**
- Enforcing immutability on seeded contract templates (possible future OpenRegister enhancement)
- Multi-party electronic signature with legal non-repudiation beyond DocuSign/SignHey certificate capture (e-signature provider responsibility)
- Contract negotiation / red-line editing UI (version + redline model is in place; UI can be added later)
- Automated compliance checking against contract templates (future analytics layer)
- Contract analytics / spend analytics (deferred to mydash integration)

## Decisions

### D1. Contract is a new register, not an extension of Document

Contract schemas live in a new `contract` register, not appended to the existing `document` register.

**Rationale:** Contracts have a distinct lifecycle (approval → signature → active → expiring/renewal), distinct access patterns (portfolio search, expiry alerts, renewal workflows), and distinct retention rules (longer hold post-expiry than documents). Bundling them into `document` muddies the register's contract and makes CLM-specific access-control harder to reason about.

**Alternative considered:** Extend the `document` register with optional contract fields. Rejected: feedback from legal users indicated contract-specific metadata should be first-class, not bolted-on.

### D2. Approval chain is sequential, not parallel

ContractApproval tracks one approver per sequenceOrder; approval moves forward only after the current approver decides.

**Rationale:** Legal and financial approval workflows are hierarchical (CFO approves after Legal, not in parallel). Sequential enforcement simplifies the state machine and matches real-world practice.

**Alternative considered:** Parallel approval (all approvers decide at once, contract advances if threshold met). Rejected: too permissive for high-value contracts where sign-off order matters.

### D3. E-signature integration tracks envelope state, not individual signature state

ContractSignature records the envelope lifecycle (sent, opened, signed, declined) via provider callbacks; individual signer status lives in the provider's system.

**Rationale:** Docudesk is the envelope coordinator, not the signature ledger. OpenConnector handles provider details; we track envelopes, not signer-by-signer progress. This keeps the schema simple and the provider integration stateless.

**Alternative considered:** Mirror every signer + status onto a separate ContractSigner table. Rejected: creates sync burden and schema bloat; provider webhooks already provide detailed state.

### D4. Reminders are trigger-based, fired by nightly cron, not scheduled per contract

ContractReminder defines trigger rules (expiry date − N days, notice period, custom); a nightly background job evaluates all contracts and fires notifications for contracts crossing trigger thresholds.

**Rationale:** Scales better than N scheduled jobs per contract. Simpler to adjust reminder windows (e.g. move expiry reminder from 60 to 90 days) — just recalculate the next fire time on every run.

**Trade-off:** Reminders fire once per nightly run, not instantaneously. Acceptable for contract timelines (days/weeks advance notice).

**Alternative considered:** ScheduledJobEntity for each reminder, fired by cron. Rejected: N contracts × M reminder rules = potential scaling bottleneck; nightly batches are simpler.

### D5. Clause library uses stable codes, not UUIDs, as the primary key

ContractClause defines `clauseCode` (unique string like `NDA-2025-CONF-01`) as the stable identifier; ContractClauseUsage links via code + version.

**Rationale:** Legal teams need human-readable codes to refer to clauses ("we're using NDA-2025-CONF-01 in this contract"); UUIDs are opaque. The code is stable across versions so old contracts can still reference the clause they used.

**Alternative considered:** Use UUID as primary key, track code as an alias. Rejected: adds indirection; feedback indicated codes are the mental model.

### D6. Clause library includes language and jurisdiction fields for portability

ContractClause has `language` (en, nl) and `jurisdiction` (NL, BE, DE) so French-speaking Belgium can selectively import clauses from NL library.

**Rationale:** MKB and gemeente customers often operate across regions. Clauses are valuable when tagged with their jurisdiction context.

**Trade-off:** Clause library is larger (multiple language versions); UI can filter by jurisdiction + language.

### D7. Portfolio search uses full-text indexing on clause content, not clause-to-contract join queries

The portfolio search surface executes full-text queries on Contract.body (rich text / PDF extracted text) to find contracts containing a clause phrase, not a join on ClauseUsage.

**Rationale:** Clause content evolves; contracts often include custom variations. Matching on the contract text (whether from a library clause or hand-edited) is more useful than relying on ClauseUsage cardinality.

**Alternative considered:** Index ClauseUsage, surface only contracts using specifically tagged clauses. Rejected: misses variations and custom-written-in clauses; less useful for legal review.

### D8. Renewal workflow clones Contract + latest Version; parent marked superseded, not deleted

When a contract renews, the system creates a new Contract (new uuid, status draft), clones the latest ContractVersion, zeros out approval/signature records, and sets parent Contract to status superseded (not deleted).

**Rationale:** Audit trail and legal discovery require the old contract to remain accessible. Renewal is a new contract, not an update; parent → child linking is explicit.

**Alternative considered:** Update the existing contract in-place (change status to renewal, append renewal as a sub-entity). Rejected: merges two lifecycles; makes per-contract audit trail harder to reason about.

## Risks / Trade-offs

**[Approval chain bottleneck]** — If an approver is unavailable, the contract is stuck until they decide or someone delegates.
→ Mitigation: ContractApproval.decision can be "delegated" to another user; the workflow continues to the next approver if the delegate approves. Documentation emphasizes delegation as an escape hatch.

**[Signature provider downtime]** — If DocuSign/SignHey is down, the contract cannot transition to active.
→ Mitigation: Manual signature option (provider: "manual") allows offline signing + attachment. Envelope state remains "pending" until a user manually transitions.

**[Clause library maintenance]** — Clauses can be edited after seeding; old contracts may reference outdated text.
→ Mitigation: ClauseUsage records the `versionId` of the contract using the clause; the specific clause revision is recoverable from ContractVersion body + redlines. The library is a reference, not the source of truth.

**[Renewal parent-child linkage]** — If a user renews twice (by accident), two children point at the same parent.
→ Mitigation: Acceptable for v1. The parent status is "superseded" regardless; the first renewal is the canonical child. Future policy can enforce one-child-per-parent if needed.

**[Portfolio search performance on large clause text]** — Full-text search on 50,000 contracts with long PDFs may be slow.
→ Mitigation: Requirement is ≤2s for 50k records. Elasticsearch or similar full-text backend can be added later. Initial implementation targets ≤500 contracts (gemeenten, small MKB); scale up once real-world demand validates the feature.

## Seed Data

Per ADR-016, seed contracts cover three personas with realistic lifecycles: a Dutch gemeente (Woo/public-records contracts), an MKB (customer & supplier contracts), and Conduction (reference project). Each seed contract exercises the full CLM workflow: draft → review → signature → active → expiring → renewal.

### Persona 1: Gemeente Demostad (Dutch Municipality)

**Seed 1 — SLA contract with internet provider**

- `contractNumber`: "GD-2024-SLA-001"
- `title`: "SLA Ondersteuning Netwerk — InternetBedrijf BV"
- `counterpartyOrg`: "InternetBedrijf B.V." (create seed organisation)
- `counterpartyContact`: "Peter Jansen" (contact.email: peter@internetbedrijf.nl)
- `contractType`: "SLA"
- `value`: 45000 (€)
- `startDate`: "2023-06-01"
- `endDate`: "2025-05-31"
- `noticePeriodDays`: 90
- `autoRenew`: true
- `renewalTermMonths`: 12
- `status`: "active"
- `owner`: "User: Greet Smit" (gemeente procurement officer)
- `department`: "Digitale Transformatie"
- `tags`: ["SLA", "Netwerk", "Dienstverlening"]
- `description`: "Jaarlijkse SLA voor 99.5% netwerk uptime en helpdesk support. Geld voor 12 maanden; geen opzegging > 90 dagen vóór einddatum."

**Seed 2 — Public Records Request (Woo contract) with consultancy**

- `contractNumber`: "GD-2024-WOO-CONSULT-001"
- `title`: "Consultancy Publieke Datalekken — Conduction BV"
- `counterpartyOrg`: "Conduction B.V."
- `counterpartyContact`: "Sarah van Dijk" (sarah@conduction.nl)
- `contractType`: "dienstverlening"
- `value`: 18500
- `startDate`: "2024-11-15"
- `endDate`: "2025-03-31"
- `noticePeriodDays`: 30
- `autoRenew`: false
- `status`: "expiring_soon" (manually set to trigger reminder scenario)
- `owner`: "User: Greet Smit"
- `department`: "Juridische Zaken"
- `description`: "6-week consultancy reviewing Woo publication process for compliance; delivery 2025-02-28"

### Persona 2: MKB — ReisBureau Zonnestraal (Travel Agency)

**Seed 3 — Customer contract with hotel chain**

- `contractNumber`: "RZ-2025-HOTEL-ALLSTAYS"
- `title`: "Raamovereenkomst — AllStays Hotel Groep"
- `counterpartyOrg`: "AllStays Hotel Groep"
- `counterpartyContact`: "Hans Kuijpers" (h.kuijpers@allstays.com)
- `contractType`: "raamovereenkomst"
- `value`: 250000
- `startDate`: "2025-01-01"
- `endDate`: "2027-12-31"
- `noticePeriodDays`: 180
- `autoRenew`: true
- `renewalTermMonths`: 24
- `status`: "active"
- `owner`: "User: Tim de Wilde" (travel agency operations manager)
- `department`: "Partnerships"
- `description`: "Raamovereenkomst minimaal 12 verblijfsduizenden/jaar; volume-based korting schema. Jaarlijkse terugkeertarief onderhandelingen."

**Seed 4 — Supplier contract (food provision for tours)**

- `contractNumber`: "RZ-2025-CATERING-TRAVELMEAL"
- `title`: "Catering & Maaltijden — TravelMeal BV"
- `counterpartyOrg`: "TravelMeal B.V."
- `counterpartyContact`: "Maria Hernández" (maria@travelmeal.nl)
- `contractType`: "inkoop"
- `value`: 35000
- `startDate`: "2025-03-01"
- `endDate`: "2026-02-28"
- `noticePeriodDays`: 60
- `autoRenew`: false
- `status`: "draft" (demonstrates draft contracts with approval pending)
- `owner`: "User: Tim de Wilde"
- `department`: "Logistics"
- `description`: "Voedsellevering voor groepsreizen; prijsvolume schema; jaarlijkse indexering 2%."

### Persona 3: Conduction B.V. (Reference Project)

**Seed 5 — Internal service agreement (reference/demo contract)**

- `contractNumber`: "COND-REF-2025-INTERN-001"
- `title`: "Interne Serviceovereenkomst — Engineering & PM Teams"
- `counterpartyOrg`: "Conduction B.V. (internal reference)"
- `counterpartyContact`: "Not applicable (self-reference)"
- `contractType`: "dienstverlening"
- `value`: 0 (internal reference)
- `startDate`: "2025-01-15"
- `endDate`: "2025-12-31"
- `noticePeriodDays`: null
- `autoRenew`: false
- `status`: "in_review" (demonstrates approval-routing scenario)
- `owner`: "User: Alex Chen" (Conduction product owner)
- `department`: "Engineering"
- `description`: "Reference contract for CLM demonstration — shows approval routing, versioning, and renewal workflow end-to-end."

## Migration Plan

1. **Schema & register** (one-time):
   - Merge 7 new schemas into `docudesk_register.json` under `components.schemas`: `contract`, `contractVersion`, `contractApproval`, `contractSignature`, `contractReminder`, `contractClause`, `contractClauseUsage`
   - Add `contract` register entry under `components.registers` with slug, Dutch title/description, and schemas list
   - Seed the 6 canonical ContractClause library entries (service agreement clauses, NDA, employment, SLA categories)
   - Seed 5 Contract + ContractVersion objects per personas above

2. **Service layer** (one-time):
   - Create `ContractService`, `ContractSignatureService`, `ContractReminderService`, `ContractClauseService` in `lib/Services/`
   - Implement state-machine validation, version creation, approval-chain logic

3. **Controllers & endpoints**:
   - Add `ContractController` with REST routes for contract CRUD, approvals, renewal, signature envelope management
   - Add webhook endpoint for e-signature provider callbacks

4. **Scheduled task**:
   - Register `contract:reminders` background job to run nightly, evaluating all contracts against reminder rules

5. **On install/upgrade**:
   - `RegistersLoader` applies `docudesk_register.json`, creates register + schemas + seed data (idempotent by uuid/slug)
   - Config section `contract.approval_policies`, `contract.signature_providers` initialized from defaults

6. **Rollback**:
   - Revert `docudesk_register.json` to prior version, re-run importer
   - Drop register + all contract objects (same idempotent mechanism)

## Open Questions

- **Clause versioning** — Should ContractClause support multiple versions (e.g. NDA-2025-CONF-01 → NDA-2025-CONF-02)? Current design: stable code, inline deprecation warning. Alternative: version by date suffix in code. Decide based on library size + churn in real-world usage.
- **Approval delegation depth** — Can a delegated approver delegate further, or only one level? Current: not specified; implement as single-level (A → B; B cannot delegate to C unless A re-opens).
- **Signature provider election** — How does a user choose between DocuSign/SignHey/manual at send-for-signature time? Current: not specified; implement as dropdown on the "send for signature" UI, or read from config default + allow override.
- **Clause content storage format** — Rich text (HTML), Markdown, or plain-text + PDF reference? Current: not specified; align with docudesk's existing document format (likely rich text); decide with editorial team.
