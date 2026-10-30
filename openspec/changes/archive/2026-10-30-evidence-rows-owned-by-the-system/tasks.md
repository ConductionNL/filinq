# Tasks: evidence-rows-owned-by-the-system

- [x] 1.1 Repair step `UnownEvidenceRecords` sets `_owner` to the system identity on the rows of the server-only schemas, in the schema's own table and in the shared objects table
- [x] 1.2 Register it in `<post-migration>` and `<install>`, after the register steps
- [x] 1.3 Unit test: only server-only schemas, only rows not yet owned by the system, nothing when OpenRegister is absent
