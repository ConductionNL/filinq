# custom-dictionary-recognition (delta)

## ADDED Requirements

### Requirement: Only maintainers are offered dictionary changes

The dictionary pages MUST offer creating, deleting and importing dictionaries
and adding or removing terms only to a user the server lets make those
changes. Other organisation members MUST still see the dictionaries and their
terms.

#### Scenario: an organisation member who is not a maintainer
- GIVEN a user outside `filinq-policy-admins` who is not an admin
- WHEN they open the custom dictionaries page
- THEN they see the dictionaries
- AND no Add or Delete action is shown

#### Scenario: a maintainer
- GIVEN a user in `filinq-policy-admins`
- WHEN they open a dictionary
- THEN Import, Add term and term delete are shown
