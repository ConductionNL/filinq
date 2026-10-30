# anonymisation-prohibition-gate (delta)

## ADDED Requirements

### Requirement: Releasing a prohibition match on the anonymise call needs the override role

An override sent with an anonymise call MUST release a publish-never match
below the high-confidence threshold only when the acting user holds the
override role (admin or a member of `prohibition_override_groups`). Without it
the override MUST be rejected and nothing MUST be recorded.

#### Scenario: an editor without the role
- GIVEN a detected person who matches a publish-never rule with confidence 0.5
- AND a user who can open the file but is not in an override group
- WHEN the user anonymises the file with an override for that match
- THEN the call is refused with the override listed as rejected
- AND no prohibitionOverrideAudit entry is written

#### Scenario: a policy admin
- GIVEN the same match
- AND a user in an override group
- WHEN the user anonymises the file with an override and a reason
- THEN the match is released
- AND a prohibitionOverrideAudit entry names the user, the rule and the reason
