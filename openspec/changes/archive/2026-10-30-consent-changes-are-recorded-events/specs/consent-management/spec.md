# consent-management (delta)

## ADDED Requirements

### Requirement: A consent status change is a recorded event

A change of `consentStatus` or `notificationStatus` MUST be one of the declared
moves, MUST be made by a consent officer, a policy admin or an admin, and MUST
be appended to the record's `statusEvents` with who made it, when, the old and
new value and the evidence. Recording `consent_given` or `objection_received`
MUST carry evidence.

#### Scenario: an officer records a consent with evidence
- GIVEN a consent record with `consentStatus` pending
- AND a user in `filinq-woo-officers`
- WHEN they set `consentStatus` to consent_given with evidence "Signed form received by post on 20 October"
- THEN the record's status is consent_given
- AND `statusEvents` ends with that change, the officer, the time and the evidence

#### Scenario: consent without evidence
- GIVEN the same record
- WHEN an officer sets `consentStatus` to consent_given without evidence
- THEN the change is refused with 400 and the record is unchanged

#### Scenario: the person who opened the request
- GIVEN a consent record opened by a user who is not an officer, policy admin or admin
- WHEN that user tries to set `consentStatus` to consent_given
- THEN the change is refused
- AND they can still read the record

#### Scenario: an undeclared move
- GIVEN a consent record with `consentStatus` objection_received
- WHEN an officer sets it to pending
- THEN the change is refused with 400
