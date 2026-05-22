# Design: TMLO / MDTO Metadata

## Architecture Overview

The metadata system is centered on a `document_metadata` object attached 1:1 to every Docudesk document. A discriminator field `profile` selects either `tmlo-1.2.1` or `mdto-1.0`. Both profiles share a common base schema for identification, classification, dates, and actors; profile-specific fields are layered on top.

### Core Entities

#### document_metadata (shared base)
Mandatory fields for both profiles:
- `id` (UUID, primary key)
- `documentId` (UUID, foreign key to docudesk document)
- `profile` (enum: `tmlo-1.2.1` | `mdto-1.0`)
- `profileVersion` (string, e.g. "1.2.1")
- `identification` (object)
  - `kenmerk` (string: human-readable ID)
  - `uri` (string: globally unique per NEN-2082)
  - `bron` (string: system that minted the ID)
- `naam` (string, required: title of the document)
- `omschrijving` (string, optional: description)
- `taal` (ISO 639-2 code, required: e.g. "nld")
- `aggregatieniveau` (enum: `archief` | `serie` | `dossier` | `record`)
- `classificatie` (object)
  - `code` (string: typically from Documentair Structuurplan)
  - `omschrijving` (string)
  - `bron` (string: source reference)
- `dekking` (object)
  - `geografisch` (string: geographic scope)
  - `temporeel` (string: temporal scope)
- `event` (array of objects)
  - `type` (enum: `creation` | `modification` | `migration` | `transfer` | etc.)
  - `tijdstip` (ISO 8601 timestamp)
  - `actor` (string: who performed the event)
  - `instrument` (string: by what system/law)
- `actoren` (array of objects)
  - `rol` (string: role type)
  - `naam` (string)
  - `identifier` (string)
  - `type` (enum: `natuurlijkPersoon` | `nietNatuurlijkPersoon` | `organisatie`)
- `bewaartermijn` (object: see selectielijst_koppeling below)
- `openbaarheid` (object)
  - `status` (enum per Woo: `openbaar` | `beperkt_openbaar` | `niet_openbaar`)
  - `grondslag` (string: legal grounds)
  - `vervaltermijn` (date: expiry of restriction)
- `vertrouwelijkheidsaanduiding` (enum: Wob/Woo classification)
- `relatie` (array of objects)
  - `type` (enum: `isPartOf` | `references` | `replaces` | `replacedBy` | `hasSignature` | etc.)
  - `target` (string: URI of related object)
  - `omschrijving` (string)

#### TMLO 1.2.1 profile extensions
Additional fields:
- `dctermsType` (string: TMLO-specific type list)
- `mediumOpReceptie` (string: medium upon receipt)
- `verschijningsvorm` (object)
  - `formaat` (string)
  - `bestandsformaat` (string)
  - `omvang` (integer: size in bytes)
- `integriteit` (object)
  - `algoritme` (string: hash algorithm)
  - `waarde` (string: hash value)
  - `tijdstip` (ISO 8601 timestamp)
- `dekkingInTijd` (string: temporal coverage)
- `vorm` (object)
  - `structuur` (string)
  - `redactie` (string)

#### MDTO 1.0 profile extensions
Additional fields:
- `dekkingInTijdBegin` (date: temporal start)
- `dekkingInTijdEind` (date: temporal end)
- `bestand` (array of objects, replaces `verschijningsvorm`)
  - `omvang` (integer: file size)
  - `bestandsformaat` (string: via FDD reference)
  - `checksum` (object)
    - `checksumAlgoritme` (string: e.g. "SHA-256")
    - `checksumWaarde` (string: hex value)
    - `checksumDatum` (ISO 8601 timestamp)
  - `URLBestand` (string: location reference)
- `betrokkene` (array: stricter typed actor replacement)
- `archiefvormer` (string, required for rijks-e-Depot transfers)

#### selectielijst_koppeling
Links document to retention policy:
- `id` (UUID, primary key)
- `documentMetadataId` (UUID, foreign key)
- `selectielijstId` (UUID: reference to Selectielijst entry register)
- `procestype` (string: process type from Selectielijst)
- `resultaat` (string: result category)
- `bewaartermijnJaren` (integer: retention period in years)
- `bewaartermijnTrigger` (enum: `definitief` | `eindeZaak` | `eindeRelatie` | `vernietigingDatum`)
- `vernietigingDatum` (date, nullable: computed or set based on trigger)
- `vernietigingsStatus` (enum: `actief` | `gepland` | `uitgesteld` | `vernietigd` | `overgebracht`)

#### identificatie_register
Enforces NEN-2082 globally unique identifiers:
- `id` (UUID, primary key)
- `uri` (string, unique constraint: the minted identifier)
- `documentMetadataId` (UUID, foreign key)
- `mintedAt` (ISO 8601 timestamp)
- `mintedBy` (string: service/user who minted it)
- `scheme` (enum: `URN` | `URI` | `Handle` | `DOI`)

Minting strategy: Instance-configured base URI (e.g. `https://docs.gemeente-zeist.nl/doc/`) + UUIDv7 (sortable by mint time, no coordination required).

#### zgw_mapping
Tracks ZGW DocumentenAPI inbound mappings:
- `id` (UUID, primary key)
- `documentMetadataId` (UUID, foreign key)
- `zgwInformatieobjecttype` (URI: source type)
- `zgwIdentificatie` (string: source identifier)
- `mappingApplied` (object: field-by-field mapping result with source→target paths)
- `unmappedFields` (array of strings: fields in ZGW payload that didn't map)

### Persistence Strategy

All metadata objects are stored as OpenRegister entries in a `docudesk_metadata` register, inheriting:
- Audit trail
- ACL enforcement
- Search indexing
- Computed fields (used for `vernietigingDatum` calculation)
- Lifecycle management

Selectielijst entries, identifier register entries, and ZGW mappings are also OpenRegister objects, making them queryable and auditable.

### Validation & Enforcement

1. **Save-time validation**: Every `document_metadata` is validated against its declared profile's required-element list before persistence; field-level errors are returned.
2. **Schema-version pinning**: `profileVersion` field stamps the exact version applied at save, making profile upgrades explicit.
3. **Identifier uniqueness**: `identificatie_register` has a unique constraint on `uri` to prevent duplicate minting.
4. **Bewaartermijn binding**: At save, the system validates the link to Selectielijst and pre-computes `vernietigingDatum` if the trigger is `definitief`.

### Export Strategy

Two parallel export pipelines:

**TMLO 1.2.1 export**:
- Validate all required elements per TMLO schema
- Render to XML conforming to TMLO 1.2.1 XSD
- Produce package at aggregation level (archief/serie/dossier/record) with manifest and checksums

**MDTO 1.0 export**:
- Validate all required elements per MDTO schema (including profile-specific such as `archiefvormer` for rijks-e-Depot)
- Render to XML conforming to MDTO 1.0 XSD
- Produce Submission Information Package (SIP) bundle per e-Depot intake spec
- Validate each XML against official XSD before packing

### ZGW Integration

When a zaak-systeem POSTs an EnkelvoudigInformatieObject via OpenConnector:
1. The documented crosswalk is applied (titel → naam, creatiedatum → event.creation.tijdstip, etc.)
2. A `document_metadata` object is created with the mapped fields
3. The mapping result is recorded in `zgw_mapping` for audit
4. Any unmapped ZGW fields are surfaced for manual handling

### Profile Migration

Migrating a TMLO 1.2.1 record to MDTO 1.0:
1. A new MDTO 1.0 `document_metadata` is created by applying a profile-specific mapper
2. Original TMLO record is marked as migrated (or archived, per config)
3. A migration `event` is appended to the document with both profile versions and timestamp
4. System ensures continuity: identification URI is preserved, bewaartermijn is transferred

---

## Seed Data

### Example 1: TMLO 1.2.1 Besluit (Decision Document)

**document_metadata (TMLO)**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440001",
  "documentId": "550e8400-e29b-41d4-a716-446655440010",
  "profile": "tmlo-1.2.1",
  "profileVersion": "1.2.1",
  "identification": {
    "kenmerk": "ZEIST-2024-003427",
    "uri": "https://docs.gemeente-zeist.nl/doc/018f3b8a-0000-7000-8000-000000000001",
    "bron": "docudesk-identifier-minter"
  },
  "naam": "Besluit omgevingsvergunning Bergsingelbuurt 45",
  "omschrijving": "Omgevingsvergunning verleend voor verbouwing woning Bergsingelbuurt 45, Zeist",
  "taal": "nld",
  "aggregatieniveau": "record",
  "classificatie": {
    "code": "2.5.1.3",
    "omschrijving": "Besluiten omgevingsrecht",
    "bron": "Documentair Structuurplan gemeente Zeist"
  },
  "dekking": {
    "geografisch": "Gemeente Zeist",
    "temporeel": "2024"
  },
  "event": [
    {
      "type": "creation",
      "tijdstip": "2024-03-15T14:32:00+01:00",
      "actor": "Afdeling Omgeving",
      "instrument": "Omgevingswet"
    }
  ],
  "actoren": [
    {
      "rol": "opsteller",
      "naam": "Mevr. J.P.M. Jansen",
      "identifier": "123456789",
      "type": "natuurlijkPersoon"
    },
    {
      "rol": "organisatie",
      "naam": "Gemeente Zeist, Afdeling Omgeving",
      "identifier": "0321010",
      "type": "organisatie"
    }
  ],
  "bewaartermijn": {
    "procestype": "Verlenen omgevingsvergunning",
    "resultaat": "Verleend",
    "bewaartermijnJaren": 10,
    "bewaartermijnTrigger": "definitief",
    "vernietigingsDatum": "2034-03-15",
    "vernietigingsStatus": "actief"
  },
  "openbaarheid": {
    "status": "openbaar",
    "grondslag": null,
    "vervaltermijn": null
  },
  "vertrouwelijkheidsaanduiding": "openbaar",
  "relatie": [],
  "dctermsType": "besluit",
  "mediumOpReceptie": "digitaal",
  "verschijningsvorm": {
    "formaat": "PDF/A-2b",
    "bestandsformaat": "application/pdf",
    "omvang": 2457600
  },
  "integriteit": {
    "algoritme": "SHA-256",
    "waarde": "a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3",
    "tijdstip": "2024-03-15T14:32:15+01:00"
  },
  "dekkingInTijd": "2024",
  "vorm": {
    "structuur": "ongestructureerd",
    "redactie": "origineel"
  }
}
```

**selectielijst_koppeling**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440002",
  "documentMetadataId": "550e8400-e29b-41d4-a716-446655440001",
  "selectielijstId": "550e8400-e29b-41d4-a716-446655440020",
  "procestype": "Verlenen omgevingsvergunning",
  "resultaat": "Verleend",
  "bewaartermijnJaren": 10,
  "bewaartermijnTrigger": "definitief",
  "vernietigingsDatum": "2034-03-15",
  "vernietigingsStatus": "actief"
}
```

### Example 2: MDTO 1.0 Zaak-document

**document_metadata (MDTO)**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440011",
  "documentId": "550e8400-e29b-41d4-a716-446655440030",
  "profile": "mdto-1.0",
  "profileVersion": "1.0",
  "identification": {
    "kenmerk": "ZEIST-2024-ZAK-001234-DOC-01",
    "uri": "https://docs.gemeente-zeist.nl/doc/018f3b8a-0001-7000-8000-000000000002",
    "bron": "zaak-systeem-integratie"
  },
  "naam": "Aanvraagformulier kapvergunning Groenestraat 12",
  "omschrijving": "Aanvraagformulier voor kapvergunning inzake twee zilverlindes, Groenestraat 12, Zeist",
  "taal": "nld",
  "aggregatieniveau": "record",
  "classificatie": {
    "code": "2.3.2.1",
    "omschrijving": "Aanvragen kapvergunning",
    "bron": "Documentair Structuurplan gemeente Zeist"
  },
  "dekking": {
    "geografisch": "Groenestraat 12, Zeist",
    "temporeel": "2024"
  },
  "dekkingInTijdBegin": "2024-04-01",
  "dekkingInTijdEind": "2024-04-01",
  "event": [
    {
      "type": "creation",
      "tijdstip": "2024-04-01T09:15:00+02:00",
      "actor": "Zaaksysteem ZAKEN2GO",
      "instrument": "API DocumentenAPI v1.5"
    }
  ],
  "actoren": [
    {
      "rol": "indiener",
      "naam": "Dhr. P.M. Groot",
      "identifier": "999888777",
      "type": "natuurlijkPersoon"
    }
  ],
  "betrokkene": [
    {
      "betrokkeneType": "persoon",
      "betrokkenIdentificatie": "999888777"
    }
  ],
  "archiefvormer": "Gemeente Zeist",
  "bewaartermijn": {
    "procestype": "Kapvergunning",
    "resultaat": "Verleend",
    "bewaartermijnJaren": 5,
    "bewaartermijnTrigger": "definitief",
    "vernietigingsDatum": "2029-04-01",
    "vernietigingsStatus": "actief"
  },
  "openbaarheid": {
    "status": "openbaar",
    "grondslag": null,
    "vervaltermijn": null
  },
  "vertrouwelijkheidsaanduiding": "openbaar",
  "relatie": [],
  "bestand": [
    {
      "omvang": 1234567,
      "bestandsformaat": "application/pdf",
      "checksum": {
        "checksumAlgoritme": "SHA-256",
        "checksumWaarde": "5e7b2d2f3a8c9b1f2e3d4c5b6a7f8e9d0c1b2a3f4e5d6c7b8a9f0e1d2c3b4a",
        "checksumDatum": "2024-04-01T09:15:30+02:00"
      },
      "URLBestand": "https://docs.gemeente-zeist.nl/file/018f3b8a-0001-7000-8000-000000000031"
    }
  ]
}
```

### Example 3: Selectielijst Entry

**Selectielijst Register Entry (VNG gemeenten 2020)**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440020",
  "procestype": "Verlenen omgevingsvergunning",
  "resultaat": "Verleend",
  "bewaartermijnJaren": 10,
  "bewaartermijnTrigger": "definitief",
  "toelichting": "Per Archiefwet, omgevingsvergunnningen bewaren tot 10 jaar na afgifte"
}
```

### Example 4: Identifier Register Entry

**identificatie_register**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440002",
  "uri": "https://docs.gemeente-zeist.nl/doc/018f3b8a-0000-7000-8000-000000000001",
  "documentMetadataId": "550e8400-e29b-41d4-a716-446655440001",
  "mintedAt": "2024-03-15T14:32:00+01:00",
  "mintedBy": "docudesk-identifier-minter",
  "scheme": "URI"
}
```

### Example 5: ZGW Mapping Record

**zgw_mapping**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440005",
  "documentMetadataId": "550e8400-e29b-41d4-a716-446655440011",
  "zgwInformatieobjecttype": "https://zaak-systeem.example.nl/catalogi/api/v1/informatieobjecttypen/550e8400-aaaa-bbbb-cccc-dddddddddddd",
  "zgwIdentificatie": "ZAKEN2GO-2024-00123456",
  "mappingApplied": {
    "titel": {
      "zgwField": "titel",
      "targetField": "naam",
      "value": "Aanvraagformulier kapvergunning Groenestraat 12"
    },
    "creatiedatum": {
      "zgwField": "creatiedatum",
      "targetField": "event.creation.tijdstip",
      "value": "2024-04-01T09:15:00+02:00"
    },
    "identificatie": {
      "zgwField": "identificatie",
      "targetField": "identification.kenmerk",
      "value": "ZAKEN2GO-2024-00123456"
    }
  },
  "unmappedFields": []
}
```
