/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Coverage for the template DETAIL surface — `src/views/templates/TemplateDetail.vue`
 * and the version-history endpoints it drives.
 *
 * WHY THIS FILE EXISTS SEPARATELY FROM `templates-crud.spec.ts`
 * ------------------------------------------------------------
 * That file covers the list surface and the create/read/update/delete legs.
 * `/templates/:id` had ZERO coverage of any kind, and the reason it was
 * skipped over is recorded — wrongly — in that file's own header, which
 * called `TemplateDetail.vue` "a STUB ('Template editor' heading +
 * descriptive paragraph) with no name/content fields and no save button".
 * That description is stale: the component is an 806-line registered editor
 * with metadata fields, a WYSIWYG surface, a preview tab, a versions tab, a
 * restore-confirmation dialog and a lock banner. The header has been
 * corrected; `mountsTheEditor` below is the executable form of that
 * correction, so the claim cannot silently go stale again.
 *
 * ⚠️ WHAT IS *NOT* COVERED HERE, AND WHY — READ BEFORE ADDING A TEST
 * ------------------------------------------------------------------
 * `TemplateDetail.vue` never hydrates from the route. Its `mounted()` reads
 * `templateStore.templateItem` and nothing else:
 *
 *     isNew() { return this.templateStore.templateItem === null }
 *     async mounted() { if (!this.isNew) { …hydrate, acquireLock… } }
 *
 * `templateItem` is set in exactly one place in the whole app —
 * `TemplateIndex.vue::openTemplate()` — and `TemplateIndex` is NOT registered
 * in `src/registry.js` (removed by openspec/changes/orphaned-surface-restoration
 * when the Templates page was decomposed to `type:"index"`; see the comment
 * at the top of registry.js). The live Templates page is CnIndexPage, whose
 * manifest entry declares no `actions` / `headerActions`, so nothing in the
 * shipped UI ever navigates to `TemplateDetail` with `templateItem` set.
 *
 * Consequence: on every reachable navigation the component mounts with
 * `isNew === true`. That means
 *
 *   - the Versions tab is `v-if="!isNew"` → it does not render at all,
 *   - `lockOwner` stays null → the "Locked by {user}" banner cannot appear
 *     and Save is never disabled by a lock.
 *
 * So the UI halves of the versions/restore and lock-banner journeys are
 * UNREACHABLE, not merely untested. They are left UNCOVERED and UNTAGGED
 * rather than covered by a test that drives an unreachable state, or by a
 * `test.skip()` that gate-19's dead-test parser would count as live. See the
 * report on ConductionNL/filinq#782.
 *
 * The restore SCENARIO itself is not lost to that gap: it is written as an
 * HTTP contract ("WHEN `POST /api/templates/{id}/versions/{versionId}/restore`
 * is called"), and every one of its THEN clauses is asserted below at exactly
 * that level.
 */

import { test, expect, type APIRequestContext } from '@playwright/test'
import { appUrl, dismissOverlays, waitForAppReady } from '../spec-coverage/_helpers'
import {
	harvestToken,
	jsonHeaders,
	API,
	createTemplate,
	getTemplate,
	deleteTemplate,
	TEST_PREFIX,
	TEST_FAMILY,
} from './_fixtures'

test.describe.configure({ mode: 'serial' })

test.afterAll(async ({ request }) => {
	const res = await request.get(`${API}/templates`)
	const body = await res.json().catch(() => ({ results: [] }))
	for (const t of body.results ?? []) {
		if (String(t.name ?? '').startsWith(TEST_FAMILY) && t.id) {
			await request.delete(`${API}/templates/${t.id}`).catch(() => {})
		}
	}
})

/** One entry of the paginated version-history payload. */
interface VersionRow {
	id: string
	version: number
	content?: string
	changelog?: string
	editor?: string
}

/**
 * Read the version history for a template.
 *
 * @param req   The Playwright request context.
 * @param token The CSRF request-token.
 * @param id    The template id.
 * @return The parsed `{results, total}` payload.
 */
async function versions(
	req: APIRequestContext,
	token: string,
	id: string,
): Promise<{ results: VersionRow[]; total: number }> {
	const url = `${API}/templates/${id}/versions`
	const res = await req.get(url, { headers: jsonHeaders(token) })
	expect(res.status(), `GET ${url} must answer 200`).toBe(200)
	const body = await res.json()
	return { results: (body.results ?? []) as VersionRow[], total: body.total ?? 0 }
}

// ---------------------------------------------------------------------------
// Version restore — REQ-TMPL-08
// ---------------------------------------------------------------------------

// @e2e openspec/specs/template-management/spec.md#restore-writes-auto-snapshot-then-overwrites-template
//
// Every THEN clause of the scenario has an assertion below:
//
//   "the current template state is saved as a new version with
//    changelog = 'Auto-saved before restore to version <targetVersion>'"
//        → the auto-snapshot is located BY that exact changelog string and its
//          `content` is asserted to be the pre-restore (V2) body. Matching on
//          the string alone would pass on an empty snapshot.
//
//   "…overwritten with the target version's values via
//    updateTemplateWithoutVersion() so no second snapshot is produced"
//        → the history count is asserted to grow by EXACTLY ONE. This is the
//          clause that fails if the restore path is ever re-pointed at
//          `updateTemplate()`: the restore would then snapshot a second time
//          and the count would grow by two while every other assertion here
//          still passed.
//
//   "the restored template object is returned"
//        → the POST response body's `content` is asserted, and a follow-up GET
//          proves the overwrite persisted rather than only being echoed back.
test('Restoring a version writes the auto-snapshot first and then overwrites the template', async ({
	page,
}) => {
	const token = await harvestToken(page)
	const req = page.request

	const V1 = 'V1 — original body for {{recipient}}.'
	const V2 = 'V2 — revised body for {{recipient}}.'

	const tmpl = await createTemplate(req, token, {
		name: `${TEST_PREFIX}-restore`,
		content: V1,
	})

	// One content change, so the history holds a snapshot of V1 to restore to.
	const upd = await req.put(`${API}/templates/${tmpl.id}`, {
		headers: jsonHeaders(token),
		data: { name: tmpl.name, content: V2 },
	})
	expect(
		upd.status(),
		`PUT ${API}/templates/${tmpl.id} must answer 200 (body: ${await upd
			.text()
			.catch(() => '')})`,
	).toBe(200)

	// POSITIVE CONTROL. Everything below counts versions and searches the
	// history; on an empty history the "+1" and "no second snapshot"
	// assertions are satisfiable for the wrong reason.
	const before = await versions(req, token, tmpl.id)
	expect(
		before.results.length,
		'one content change must have produced exactly one snapshot before anything is restored',
	).toBe(1)
	const target = before.results[0]
	expect(
		target.content,
		'the snapshot taken on update captures the PRE-update state, so it must hold V1',
	).toBe(V1)

	const restoreUrl = `${API}/templates/${tmpl.id}/versions/${target.id}/restore`
	const restored = await req.post(restoreUrl, { headers: jsonHeaders(token) })
	expect(
		restored.status(),
		`POST ${restoreUrl} must answer 200 (body: ${await restored
			.text()
			.catch(() => '')})`,
	).toBe(200)

	// "AND the restored template object is returned"
	const restoredBody = await restored.json()
	expect(
		restoredBody.content,
		'the restore response must carry the target version content',
	).toBe(V1)

	const after = await versions(req, token, tmpl.id)

	// "…so no second snapshot is produced" — exactly one new row, not two.
	expect(
		after.results.length,
		'restore must add exactly ONE history row (the auto-snapshot). Two rows here '
			+ 'means the overwrite went through updateTemplate() instead of '
			+ `updateTemplateWithoutVersion(). Changelogs seen: ${after.results
				.map((v) => JSON.stringify(v.changelog))
				.join(', ')}`,
	).toBe(before.results.length + 1)

	// "the current template state is saved as a new version with
	//  changelog = 'Auto-saved before restore to version <targetVersion>'"
	const expectedChangelog = `Auto-saved before restore to version ${target.version}`
	const auto = after.results.find((v) => v.changelog === expectedChangelog)
	expect(
		auto,
		`the auto-snapshot must carry the changelog "${expectedChangelog}". `
			+ `Changelogs seen: ${after.results
				.map((v) => JSON.stringify(v.changelog))
				.join(', ')}`,
	).toBeDefined()
	expect(
		auto!.content,
		'the auto-snapshot must capture the state as it was JUST BEFORE the restore (V2). '
			+ 'A snapshot carrying V1 would mean the overwrite happened first and the '
			+ 'pre-restore body is gone — the exact history loss this scenario exists to prevent.',
	).toBe(V2)

	// "the template's content … overwritten with the target version's values" —
	// asserted against a fresh read, not the echoed response.
	const reread = await getTemplate(req, token, tmpl.id)
	expect(reread.status, 'GET the template after restore').toBe(200)
	expect(
		reread.body.content,
		'the persisted template content must be the restored version',
	).toBe(V1)

	await deleteTemplate(req, token, tmpl.id)
})

// ---------------------------------------------------------------------------
// The detail route itself
// ---------------------------------------------------------------------------

// DELIBERATELY CARRIES NO `@e2e` ANCHOR.
//
// This asserts that `/templates/:id` is routed and mounts the real editor —
// which is what refutes the stale "STUB … no name/content fields and no save
// button" claim this file's header describes. It is NOT a scenario: no
// `template-management` scenario says anything about the detail route
// rendering, and anchoring it to one of the CRUD scenarios would credit a
// clause it never evaluates (the `.github#345` defect — a green gate-19 row
// over a UI that does not do what the scenario says).
test('The /templates/:id route mounts the real TemplateDetail editor, not a stub', async ({
	page,
}) => {
	const token = await harvestToken(page)
	const req = page.request

	const tmpl = await createTemplate(req, token, {
		name: `${TEST_PREFIX}-detail-route`,
	})

	await page.goto(await appUrl(page, `templates/${tmpl.id}`), {
		waitUntil: 'domcontentloaded',
	})
	await waitForAppReady(page)
	await dismissOverlays(page)

	const detail = page.locator('.template-detail')
	await expect(
		detail,
		'the detail route must mount TemplateDetail.vue — a redirect to the list, or an '
			+ 'empty <main>, means the manifest page or its registry entry has been lost',
	).toBeVisible({ timeout: 30_000 })

	// The three fields the stale header said did not exist.
	for (const label of ['Name', 'Namespace', 'Description']) {
		await expect(
			detail.getByLabel(label, { exact: false }).first(),
			`the editor must render a "${label}" field`,
		).toBeVisible()
	}

	// The save button the stale header said did not exist.
	await expect(
		detail.getByRole('button', { name: 'Save', exact: true }),
		'the editor must render a Save button',
	).toBeVisible()

	// The content surface. `contenteditable` is the WYSIWYG area; its
	// aria-label is the only stable handle on it.
	await expect(
		detail.locator('[contenteditable="true"]'),
		'the editor must render an editable content surface',
	).toBeVisible()

	// The Editor and Preview tabs are unconditional. The Versions tab is
	// `v-if="!isNew"` and is NOT asserted here — see this file's header for
	// why it cannot render on a reachable navigation.
	for (const tab of ['Editor', 'Preview']) {
		await expect(
			detail.getByRole('button', { name: tab, exact: true }),
			`the "${tab}" tab must render`,
		).toBeVisible()
	}

	await deleteTemplate(req, token, tmpl.id)
})
