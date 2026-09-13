## 1. A template carries its version (REQ-DDTVP-001)

- [ ] 1.1 Lift the OpenRegister object version out of `@self` to the top level
      in `TemplateService::getTemplate()`, leaving `@self` untouched.
- [ ] 1.2 Omit the key when OpenRegister supplies no version, so a caller can
      tell unversioned from version 1.
- [ ] 1.3 Unit test both, and watch the test fail against today's code before
      the fix.

## 2. The record names the version that produced it (REQ-DDTVP-002)

- [ ] 2.1 Read the template version in `DocumentService::generateDocument()`
      from the value task 1.1 supplies, and drop the `?? 1` default.
- [ ] 2.2 Record an unknown version as unknown on the `generatedDocument`
      audit record rather than as a number.
- [ ] 2.3 Report the same version in the generation result.
- [ ] 2.4 Unit test that a template at version 4 records version 4, and that
      an unversioned template records no number.

## 3. A caller can pin a version (REQ-DDTVP-003)

- [ ] 3.1 Accept `options.templateVersion` in `generateDocument()` and resolve
      it through `TemplateVersionService::getVersion()`.
- [ ] 3.2 Render that version's stored content through the existing
      `TemplateRenderer`, without touching the template head.
- [ ] 3.3 Fail with a message naming the template and the version when the
      version does not exist, and produce no file.
- [ ] 3.4 Unit test the pinned render, the head render when the option is
      absent, and the refusal.

## 4. Which version was in force on a date (REQ-DDTVP-004)

- [ ] 4.1 Add a version-in-force query to `TemplateVersionService`, resolved
      from the version chain's creation timestamps.
- [ ] 4.2 Answer that no version was in force when the moment predates the
      first stored version.
- [ ] 4.3 Unit test a moment inside the chain, a moment before it, and a
      moment after the newest version.

## 5. The result reports the bytes (REQ-DDTVP-005)

- [ ] 5.1 Report a SHA-256 over the produced bytes in the generation result.
- [ ] 5.2 Report the page count for a paginated format, and no page count for
      a format with no page structure.
- [ ] 5.3 Unit test both against a real produced PDF and a non-PDF output.

## 6. Verification

- [ ] 6.1 Run PHPUnit in the container:
      `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`
      and read the exit code, not the summary line.
- [ ] 6.2 Run `openspec validate generated-document-names-its-template-version --strict`.
- [ ] 6.3 Run `composer check:strict`.
- [ ] 6.4 Generate a document on the dev instance and confirm the recorded
      version matches the template's OpenRegister version, and that the
      reported checksum matches the stored file's bytes.

## 7. Documentation and follow-on

- [ ] 7.1 Document `options.templateVersion` and the two new result fields in
      `docs/features/`.
- [ ] 7.2 Raise the counterpart change in the dossiq repo: point
      `FilinqTemplateEngineAdapter::resolveVersion()` at the new query and
      delete `countPages()`.
