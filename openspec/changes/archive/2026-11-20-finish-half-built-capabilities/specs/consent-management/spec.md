# consent-management Specification (delta)

## ADDED Requirements

### Requirement: A consent request reaches the third party (REQ-FHB-02)

When a consent request is created for a third party with a known email or postal address, Filinq SHALL send that party the request: what will be published, the objection period and how to respond. It SHALL go by email when an email address is known and as a letter from the correspondence composer otherwise. The consent record SHALL record the channel, the moment it was sent and the end of the objection period counted from that moment. A request that cannot be sent SHALL keep the record open and say why on the consent screen.

#### Scenario: A third party with an email address is asked

- **GIVEN** a document to be published that names a third party with email address `info@bakkerij-jansen.nl`
- **WHEN** a consent request is created for that party
- **THEN** one message goes to `info@bakkerij-jansen.nl` naming the document and the objection period
- **AND** the consent record says "email" and the time it was sent

#### Scenario: A third party without any address

- **GIVEN** a third party with no email and no postal address
- **WHEN** a consent request is created
- **THEN** nothing is sent and the consent screen says the party has no address
