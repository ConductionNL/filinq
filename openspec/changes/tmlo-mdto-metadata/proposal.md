# TMLO / MDTO Metadata

## Summary

This change implements comprehensive metadata support for TMLO 1.2.1 (Dutch local government metadata standard in production use) and MDTO 1.0 (successor standard rolling out from 2025 onwards) to enable durable accessibility, preservation, and transfer of documents to e-Depot archives per the Archiefwet 1995.

## Motivation

Dutch local government organisations (gemeenten, provincies, waterschappen) must maintain documents durably accessible and transferable to permanent archives (e-Depot). Today, Docudesk stores documents but does not enforce metadata standards, validate against schemas, link to retention policies (bewaartermijnen) via Selectielijst, or produce archival-grade export packages. 

The parallel support for both TMLO (legacy, in active use) and MDTO (new, becoming mandatory 2025+) avoids a forced migration flag-day, allowing gradual transition. Both standards require a shared metadata model with profile-specific extensions, unique identifier minting per NEN-2082, bewaartermijn binding, and ZGW DocumentenAPI inbound mapping so documents from zaak-systemen arrive pre-populated.

## Scope

- **Data model**: Single `document_metadata` object per document with `tmlo-1.2.1` and `mdto-1.0` profiles, shared base fields (identification, classification, dates, actors), and profile-specific extensions
- **Validation**: Required-element enforcement per profile on save; schema-version pinning
- **Selectielijst binding**: Link every document to exactly one retention policy entry; compute `vernietigingDatum` from bewaartermijn + trigger event
- **Identification**: Mint globally unique URIs per NEN-2082 using instance-configured base + UUIDv7
- **ZGW inbound mapping**: Accept EnkelvoudigInformatieObject from DocumentenAPI; apply documented crosswalk to TMLO/MDTO
- **XML export**: Produce TMLO 1.2.1 and MDTO 1.0 conformant XML per document and per aggregation level (archief/serie/dossier/record)
- **E-Depot packages**: Bundle metadata XML(s), bitstreams, and manifests with checksums for Submission Information Package ingest
- **Profile migration**: Explicit migration from TMLO to MDTO with audit trail
- **OpenRegister integration**: All metadata objects live as OpenRegister entries; Selectielijst, identifier register, ZGW mappings are configurable per instance

## Affected Systems

- **docudesk** (documents and metadata attachment)
- **openregister** (persistence, ACL, audit, computed fields for `vernietigingDatum`)
- **openconnector** (ZGW inbound listening, e-Depot outbound transfer)
- **opencatalogi** (Selectielijst syndication)
- **eidas-qes-signature** (signature metadata as relaties)

## Target Users

- **DIV-medewerkers / archief-medewerkers**: Verify metadata, link to Selectielijst, mark for transfer or destruction, trigger e-Depot exports
- **Zaaksysteem developers**: Integrate via ZGW DocumentenAPI inbound mapping; receive TMLO/MDTO-tagged documents with stable URIs
- **Nationaal Archief / e-Depot operators**: Receive and validate MDTO export packages on ingest
- **Auditors**: Review audit trail to verify retention was applied per Selectielijst and identifiers were not silently changed
- **Platform administrators**: Configure Selectielijst register, identifier base URI, profile defaults (TMLO vs MDTO)
