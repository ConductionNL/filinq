/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Regression test for ConductionNL/filinq#428.
 *
 * `ProhibitionOverrideCommitter` writes its AVG Art. 30 override-audit entry to
 * `register: consent, schema: prohibitionOverrideAudit` and is explicitly
 * fail-closed — "If the audit write fails we MUST NOT proceed to the OR PATCH"
 * — rethrowing as `RuntimeException(…, 500)`. That schema was declared NOWHERE:
 * `lib/Settings/filinq_register.json` listed 20 schemas and this was not one
 * of them, so the store answered
 *
 *     404 {"message":"Schema not found: 'prohibitionOverrideAudit'"}
 *
 * and every acknowledged prohibition override 500'd. The spec mandates the
 * schema by name:
 *
 *   "Implementations MUST use a `prohibitionOverrideAudit` schema in
 *    `filinq_register.json` for this entry, alongside the existing schemas."
 *   — openspec/specs/anonymisation-prohibition-gate/spec.md
 *
 * ⚠️ DELIBERATELY CARRIES NO `@e2e` ANCHOR, and that is not an oversight.
 * The nearest scenario is `#override-acknowledgement-writes-both-audit-and-skip-flag`,
 * which requires the CONTROLLER to write the entry while releasing a
 * LOW-confidence match. An override is only valid below the high-confidence
 * threshold (default 0.85, `filinq.prohibition.high_confidence_threshold`,
 * which is NOT in `SettingsService::WRITABLE_KEYS` so a test cannot move it),
 * and the regex-only backend this instance and CI run emits `CUSTOM_DICTIONARY`
 * at confidence 1.00 and nothing else — so no overridable match can be
 * produced here. This test proves the STORE half only. Anchoring it to that
 * scenario would credit a clause it never evaluates, which is the exact defect
 * `.github#345` describes.
 */

import { test, expect } from '@playwright/test'
import { harvestToken, jsonHeaders, TEST_PREFIX } from './_fixtures'

// `filinq`, not `consent`: the five registers were consolidated into one.
const OR = '/index.php/apps/openregister/api/objects/filinq'

test("the prohibition-override audit schema resolves and accepts the committer's payload", async ({
	page,
	request,
}) => {
	const token = await harvestToken(page)

	// POSITIVE CONTROL FIRST. If the filinq register itself were missing or
	// the seed had not run, the assertion below would fail for a reason that
	// has nothing to do with #428 — and a bare 404 cannot tell the two apart.
	const sibling = await request.get(`${OR}/publicationProhibition`, {
		headers: jsonHeaders(token),
	})
	expect(
		sibling.status(),
		`positive control — GET ${OR}/publicationProhibition must answer 200 before the `
			+ 'assertion below means anything',
	).toBe(200)

	const res = await request.get(`${OR}/prohibitionOverrideAudit`, {
		headers: jsonHeaders(token),
	})
	expect(
		res.status(),
		`GET ${OR}/prohibitionOverrideAudit must answer 200. A 404 here means the schema `
			+ 'is undeclared again and ProhibitionOverrideCommitter::writeAudit() is fail-closed '
			+ `on it, so EVERY acknowledged override 500s. Body: ${(await res.text()).slice(0, 200)}`,
	).toBe(200)

	// The store resolving is necessary but not sufficient: the committer writes
	// a specific six-field shape, and a schema that rejects it is the same
	// outage with a different status code.
	const write = await request.post(`${OR}/prohibitionOverrideAudit`, {
		headers: jsonHeaders(token),
		data: {
			ruleId: `${TEST_PREFIX}-rule`,
			entityRelationId: 7,
			fileId: 123,
			reason: `${TEST_PREFIX} — regression fixture for #428`,
			acknowledgedBy: 'admin',
			acknowledgedAt: new Date().toISOString(),
		},
	})
	expect(
		write.status(),
		`POST ${OR}/prohibitionOverrideAudit with the exact payload writeAudit() sends must be `
			+ `accepted. Body: ${(await write.text()).slice(0, 300)}`,
	).toBe(201)

	const created = await write.json()
	expect(created.ruleId, 'the audit entry must round-trip its ruleId').toBe(
		`${TEST_PREFIX}-rule`,
	)
	expect(
		created.acknowledgedBy,
		'the audit entry must round-trip the acting user',
	).toBe('admin')
})
