---
description: Run Filinq's unit tests (PHPUnit, Vitest, Jest)
---
Run the unit tests from the repository root. If `$ARGUMENTS` names a file or filter, pass it to the matching runner.

1. `composer test:unit` (PHPUnit, `tests/unit`, configured in `phpunit.xml`)
2. `npm run test:unit` (Vitest)
3. `npm test` (Jest)

Report failing tests with the assertion message. Fix the code, not the test, unless the test is wrong.
