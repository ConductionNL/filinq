/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * archiefwet-retention-engine, task 4.2: the archivist's Archiefbeheer flow
 * over OpenRegister's retention engine. Wire OR's archival settings to the
 * `archief` register from filinq's admin page, review and partially approve
 * a vernietigingslijst, reject one with a mandatory reason, read the
 * verklaring van vernietiging, and list a bewaren record past its actiedatum
 * for overbrenging.
 *
 * ⚠️ NOT BUILT YET. No task of this change is ticked: there is no `archief`
 * register, no Archiefbeheer admin section and no Archiefbeheer pages in
 * `src/manifest.d/`. This file is written from the spec, design.md and
 * screens.md (boards filinq/FqBewaren, filinq/FqDocument), and it WILL FAIL
 * until the code lands. The OpenRegister half is real and read from OR's
 * code: `GET/PUT /api/settings/archival`, `GET /api/archival/destruction-lists`,
 * `GET /api/archival/destruction-lists/{id}`, `POST .../approve` with
 * `{action, excluded, exclusionReasons}`, `POST .../reject` with `{reason}`
 * and `GET /api/archival/certificates?destructionList=`. The filinq half is
 * not, and its names are this file's assumptions, kept in the constants below
 * so the build pass can align them in one place:
 *  - the admin section heading "Archiefbeheer" and its button
 *    "Koppel archiefregister" (spec copy; English UI copy accepted as well);
 *  - the manifest routes `archiefbeheer/vernietigingslijsten`,
 *    `archiefbeheer/vernietigingslijsten/{uuid}`, `archiefbeheer/verklaringen`
 *    and `archiefbeheer/overbrenging`;
 *  - button names "Exclude", "Approve the rest", "Reject", and a reason field
 *    labelled "Reason" in the exclusion and rejection dialogs.
 *
 * WHAT IT SEEDS ITSELF. Nothing it cannot put back: the wiring test runs only
 * on an instance whose OR archival settings are unset (the spec's GIVEN) and
 * writes the original settings back in a finally block.
 *
 * WHAT IT READS FROM THE INSTANCE. A destruction list is drawn up by OR's
 * daily destruction check from records past their actiedatum, which a test
 * cannot fast-forward. The live pass prepares them and names them:
 *  - `FILINQ_E2E_ARCHIEF_REVIEW_LIST_ID`: a list in `in_review` with at least
 *    two objects (partial approval);
 *  - `FILINQ_E2E_ARCHIEF_REJECT_LIST_ID`: another list in `in_review`
 *    (rejection);
 *  - `FILINQ_E2E_ARCHIEF_EXECUTED_LIST_ID`: an approved list OR has executed
 *    (certificate);
 *  - `FILINQ_E2E_ARCHIEF_BEWAREN_OBJECT`: `register/schema/uuid` of a record
 *    with nominatie `bewaren` and an archiefactiedatum in the past.
 * Without them those tests skip and say why. Each one checks its fixture
 * against OR before it drives the UI, so a wrong fixture fails as a fixture.
 *
 * KNOWN DISAGREEMENTS BETWEEN THE SPEC AND OPENREGISTER (found while writing):
 *  - the certificate lists approvers as `approvedBy` and the count as
 *    `totalDestroyed` (plus `groupedBySchema`), not "approvers" and "object
 *    counts"; the compliance statement reads "Vernietiging conform Archiefwet
 *    1995 en Archiefbesluit 1995", not design.md's "..., artikelen 6-8";
 *  - a list's own detail is OR's object serialisation, so its fields may sit
 *    under `object` rather than at the top; the helpers read both.
 *
 * Excluded in the spec (`@e2e exclude`), covered by PHPUnit or review: register
 * import and seeds, the selectielijst field contract, existing wiring never
 * silently overwritten, record stamping and recalculation, no retention
 * arithmetic, no pass-through controllers, workflow artifacts are bewaren, the
 * transferred record's 409, destruction-date propagation (three scenarios),
 * the no-auto-delete annotation rules, the TODO-category apply-blocker, and
 * every document-register scenario.
 *
 * Not run in the change that wrote it (decision 139): the live pass runs it.
 */

// @e2e openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#admin-wires-the-archief-register
// @e2e openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#archivist-reviews-and-partially-approves-a-vernietigingslijst
// @e2e openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#rejection-requires-a-reason
// @e2e openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#certificate-appears-after-execution
// @e2e openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#bewaren-record-past-its-actiedatum-awaits-overbrenging

import type { APIRequestContext, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { go, waitForNcContentReady } from '../spec-coverage/_helpers.ts'

/** OpenRegister's API root. */
const OR_API = '/index.php/apps/openregister/api'

/** OR's archival settings (read and write). */
const ARCHIVAL_SETTINGS = `${OR_API}/settings/archival`

/** OR's destruction workflow. */
const LISTS_API = `${OR_API}/archival/destruction-lists`
const CERTIFICATES_API = `${OR_API}/archival/certificates`

/** The five settings the wiring action writes. */
const WIRED_KEYS = ['selectielijstRegister', 'selectielijstSchema', 'destructionListRegister', 'destructionListSchema', 'archivalRegister'] as const

/** filinq's routes and copy for the surface this change builds (assumptions, see the header). */
const ROUTE_LISTS = 'archiefbeheer/vernietigingslijsten'
const routeList = (uuid: string) => `${ROUTE_LISTS}/${encodeURIComponent(uuid)}`
const ROUTE_CERTIFICATES = 'archiefbeheer/verklaringen'
const ROUTE_TRANSFER = 'archiefbeheer/overbrenging'
const SECTION_HEADING = /^(Archiefbeheer|Archive management)$/
const WIRE_BUTTON = /Koppel archiefregister|Link (the )?archi(ef|ve) register/i
const CONFIRM_BUTTON = /^(Confirm|Bevestigen|Koppel archiefregister|Link)/i
const EXCLUDE_BUTTON = /^(Exclude|Uitsluiten)/i
const APPROVE_REST_BUTTON = /Approve the rest|Rest goedkeuren|Approve remaining|Gedeeltelijk goedkeuren/i
const REJECT_BUTTON = /^(Reject|Afwijzen)/i
const REASON_FIELD = /^(Reason|Reden)/i

/** Fixtures the live pass prepares (see the header). */
const REVIEW_LIST_ID = process.env.FILINQ_E2E_ARCHIEF_REVIEW_LIST_ID ?? ''
const REJECT_LIST_ID = process.env.FILINQ_E2E_ARCHIEF_REJECT_LIST_ID ?? ''
const EXECUTED_LIST_ID = process.env.FILINQ_E2E_ARCHIEF_EXECUTED_LIST_ID ?? ''
const BEWAREN_OBJECT = process.env.FILINQ_E2E_ARCHIEF_BEWAREN_OBJECT ?? ''

/** The OCS header every API call carries. */
const OCS = { 'OCS-APIRequest': 'true' }

/** A run-unique marker for the reasons this file writes. */
const RUN = `archief-e2e-${Date.now()}`

/**
 * Read OR's archival settings.
 *
 * @param request The authenticated request context.
 * @return The settings.
 */
async function archivalSettings(request: APIRequestContext): Promise<Record<string, any>> {
	const res = await request.get(ARCHIVAL_SETTINGS, { headers: OCS })
	expect(res.ok(), `GET ${ARCHIVAL_SETTINGS} -> ${res.status()}`).toBeTruthy()

	return res.json()
}

/**
 * Read one destruction list's own data from OR.
 *
 * @param request The authenticated request context.
 * @param uuid The list's uuid.
 * @return The list data: status and objects at the top level.
 */
async function destructionList(request: APIRequestContext, uuid: string): Promise<Record<string, any>> {
	const res = await request.get(`${LISTS_API}/${encodeURIComponent(uuid)}`, { headers: OCS })
	expect(res.ok(), `GET destruction list ${uuid} -> ${res.status()}`).toBeTruthy()
	const body = await res.json()

	return { ...body, ...(body.object ?? {}) }
}

/**
 * The slug of an OR register or schema, by id.
 *
 * @param request The authenticated request context.
 * @param kind `registers` or `schemas`.
 * @param id The id the settings hold.
 * @return The slug.
 */
async function slugOf(request: APIRequestContext, kind: 'registers' | 'schemas', id: unknown): Promise<string> {
	const res = await request.get(`${OR_API}/${kind}/${encodeURIComponent(String(id))}`, { headers: OCS })
	expect(res.ok(), `GET ${kind}/${id} -> ${res.status()}`).toBeTruthy()

	return String((await res.json()).slug ?? '')
}

/**
 * Open a list's detail page and wait for its object rows.
 *
 * @param page The page.
 * @param uuid The list's uuid.
 * @return Resolves once the rows are visible.
 */
async function openList(page: Page, uuid: string): Promise<void> {
	await go(page, routeList(uuid))
	await expect(page.locator('table tbody tr').first()).toBeVisible({ timeout: 30_000 })
}

test.describe('archiefwet retention: archivist workflow', () => {
	test.setTimeout(180_000)

	test('the admin wires the archief register with "Koppel archiefregister" and the panel shows it', async ({ page, request }) => {
		const original = await archivalSettings(request)
		const wired = WIRED_KEYS.filter((key) => original[key] !== null && original[key] !== undefined && original[key] !== '')
		test.skip(wired.length > 0, `OR archival settings are already wired (${wired.join(', ')}); this test only runs where they are unset and never overwrites them`)

		try {
			await page.goto('/index.php/settings/admin/filinq', { waitUntil: 'domcontentloaded' })
			await waitForNcContentReady(page)
			const section = page.locator('.settings-section', { has: page.getByRole('heading', { name: SECTION_HEADING }) }).first()
			await expect(section).toBeVisible({ timeout: 30_000 })

			// Opening the panel writes nothing.
			expect(await archivalSettings(request)).toEqual(original)

			const put = page.waitForResponse((r) => r.url().includes('/api/settings/archival') && ['PUT', 'PATCH'].includes(r.request().method()))
			await section.getByRole('button', { name: WIRE_BUTTON }).click()
			const dialog = page.getByRole('dialog')
			if (await dialog.isVisible().catch(() => false)) {
				await dialog.getByRole('button', { name: CONFIRM_BUTTON }).click()
			}
			expect((await put).status()).toBe(200)

			const after = await archivalSettings(request)
			for (const key of WIRED_KEYS) {
				expect(after[key], `${key} is set`).toBeTruthy()
			}
			expect(await slugOf(request, 'registers', after.selectielijstRegister)).toBe('archief')
			expect(await slugOf(request, 'registers', after.destructionListRegister)).toBe('archief')
			expect(await slugOf(request, 'registers', after.archivalRegister)).toBe('archief')
			expect(await slugOf(request, 'schemas', after.selectielijstSchema)).toBe('selectielijstEntry')
			expect(await slugOf(request, 'schemas', after.destructionListSchema)).toBe('destructionList')

			// The panel reflects the wired state.
			await expect(section).toContainText(/archief/i)
			await expect(section).toContainText(/selectielijstEntry|selectielijst/i)
		} finally {
			const restore = Object.fromEntries(WIRED_KEYS.map((key) => [key, original[key] ?? null]))
			await request.put(ARCHIVAL_SETTINGS, { headers: OCS, data: restore }).catch(() => {})
		}
	})

	test('the archivist excludes one object with a reason and approves the rest', async ({ page, request }) => {
		test.skip(REVIEW_LIST_ID === '', 'FILINQ_E2E_ARCHIEF_REVIEW_LIST_ID is not set: no destruction list in review on this instance')

		const list = await destructionList(request, REVIEW_LIST_ID)
		expect(list.status, 'the fixture list is in review').toBe('in_review')
		const objects = (list.objects ?? []) as Array<Record<string, any>>
		expect(objects.length, 'the fixture list holds at least two objects').toBeGreaterThanOrEqual(2)
		const excluded = objects[0]
		const reason = `Nog in gebruik voor een lopend bezwaar (${RUN})`

		await go(page, ROUTE_LISTS)
		await expect(page.locator('table tbody tr', { hasText: REVIEW_LIST_ID }).or(page.locator(`a[href*="${REVIEW_LIST_ID}"]`)).first()).toBeVisible({ timeout: 30_000 })

		await openList(page, REVIEW_LIST_ID)
		await expect(page.locator('table tbody tr')).toHaveCount(objects.length)
		const row = page.locator('table tbody tr', { hasText: String(excluded.title ?? excluded.uuid) }).first()
		await row.getByRole('button', { name: EXCLUDE_BUTTON }).click()
		await page.getByRole('dialog').getByLabel(REASON_FIELD).fill(reason)
		await page.getByRole('dialog').getByRole('button', { name: EXCLUDE_BUTTON }).click()

		const approve = page.waitForRequest((r) => r.url().includes(`/archival/destruction-lists/${REVIEW_LIST_ID}/approve`) && r.method() === 'POST')
		const answer = page.waitForResponse((r) => r.url().includes(`/archival/destruction-lists/${REVIEW_LIST_ID}/approve`))
		await page.getByRole('button', { name: APPROVE_REST_BUTTON }).click()
		const dialog = page.getByRole('dialog')
		if (await dialog.isVisible().catch(() => false)) {
			await dialog.getByRole('button', { name: APPROVE_REST_BUTTON }).click()
		}

		// OR's approve endpoint gets approve_partial, the excluded uuid and its reason.
		const sent = (await approve).postDataJSON()
		expect(sent.action).toBe('approve_partial')
		expect(sent.excluded).toEqual([excluded.uuid])
		expect(sent.exclusionReasons).toEqual({ [excluded.uuid]: reason })

		// The status shown is OR's answer.
		const res = await answer
		expect(res.status()).toBe(200)
		const result = await res.json()
		await expect(page.locator('main, #app-content').first()).toContainText(String(result.status))
		expect((await destructionList(request, REVIEW_LIST_ID)).status).toBe(result.status)
	})

	test('rejecting a vernietigingslijst is blocked until a reason is given, and OR receives the reason', async ({ page, request }) => {
		test.skip(REJECT_LIST_ID === '', 'FILINQ_E2E_ARCHIEF_REJECT_LIST_ID is not set: no destruction list in review on this instance')

		const list = await destructionList(request, REJECT_LIST_ID)
		expect(list.status, 'the fixture list is in review').toBe('in_review')

		let rejectCalls = 0
		page.on('request', (r) => {
			if (r.url().includes(`/archival/destruction-lists/${REJECT_LIST_ID}/reject`)) rejectCalls++
		})

		await openList(page, REJECT_LIST_ID)
		await page.getByRole('button', { name: REJECT_BUTTON }).first().click()
		const dialog = page.getByRole('dialog')
		await expect(dialog).toBeVisible()
		const submit = dialog.getByRole('button', { name: REJECT_BUTTON })

		// Without a reason the submission is blocked: disabled, or refused on click with nothing sent.
		if (await submit.isEnabled()) {
			await submit.click()
			await expect(dialog).toBeVisible()
		} else {
			await expect(submit).toBeDisabled()
		}
		expect(rejectCalls, 'no reject call without a reason').toBe(0)

		const reason = `Selectielijstcategorie onjuist toegepast (${RUN})`
		await dialog.getByLabel(REASON_FIELD).fill(reason)
		const reject = page.waitForRequest((r) => r.url().includes(`/archival/destruction-lists/${REJECT_LIST_ID}/reject`) && r.method() === 'POST')
		await submit.click()
		expect((await reject).postDataJSON().reason).toBe(reason)

		const stored = await destructionList(request, REJECT_LIST_ID)
		expect(stored.status).toBe('rejected')
		const rejections = (stored.rejections ?? []) as Array<Record<string, any>>
		expect(rejections.some((r) => r.reason === reason)).toBe(true)
	})

	test('the verklaring van vernietiging of an executed list is listed with its date, approvers and count', async ({ page, request }) => {
		test.skip(EXECUTED_LIST_ID === '', 'FILINQ_E2E_ARCHIEF_EXECUTED_LIST_ID is not set: no executed destruction list on this instance')

		const res = await request.get(`${CERTIFICATES_API}?destructionList=${encodeURIComponent(EXECUTED_LIST_ID)}`, { headers: OCS })
		expect(res.ok(), `GET certificates -> ${res.status()}`).toBeTruthy()
		const results = ((await res.json()).results ?? []) as Array<Record<string, any>>
		expect(results.length, 'OR holds a certificate for the executed list').toBeGreaterThanOrEqual(1)
		const certificate = { ...results[0], ...(results[0].object ?? {}) }
		expect(certificate.type).toBe('verklaring_van_vernietiging')
		expect(certificate.immutable).toBe(true)

		await go(page, ROUTE_CERTIFICATES)
		const row = page.locator('table tbody tr', { hasText: EXECUTED_LIST_ID }).first()
		await expect(row).toBeVisible({ timeout: 30_000 })
		const date = String(certificate.destructionDate ?? '').slice(0, 10)
		const shown = [date, new Date(date).toLocaleDateString('nl-NL'), new Date(date).toLocaleDateString('en-GB')]
		const text = await row.innerText()
		expect(shown.some((form) => form !== '' && text.includes(form)), `the row shows the destruction date (${date})`).toBe(true)
		for (const approver of (certificate.approvedBy ?? []) as string[]) {
			await expect(row).toContainText(approver)
		}
		await expect(row).toContainText(String(certificate.totalDestroyed))
	})

	test('a bewaren record past its actiedatum is listed for overbrenging and on no vernietigingslijst', async ({ page, request }) => {
		test.skip(BEWAREN_OBJECT.split('/').length !== 3, 'FILINQ_E2E_ARCHIEF_BEWAREN_OBJECT is not set as register/schema/uuid: no bewaren record past its actiedatum on this instance')
		const [register, schema, uuid] = BEWAREN_OBJECT.split('/')

		const res = await request.get(`${OR_API}/objects/${register}/${schema}/${uuid}`, { headers: OCS })
		expect(res.ok(), `GET the bewaren record -> ${res.status()}`).toBeTruthy()
		const record = await res.json()
		const retention = record.retention ?? record['@self']?.retention ?? {}
		expect(retention.archiefnominatie, 'the fixture record is bewaren').toBe('bewaren')
		expect(Date.parse(retention.archiefactiedatum), 'the fixture record is past its actiedatum').toBeLessThan(Date.now())

		await go(page, ROUTE_TRANSFER)
		const row = page.locator('table tbody tr', { hasText: String(record.title ?? record.name ?? uuid) }).first()
		await expect(row).toBeVisible({ timeout: 30_000 })
		await expect(row).toContainText(/Awaiting transfer|Wacht op overbrenging|Te brengen|overbrenging/i)

		// Never on a vernietigingslijst.
		const lists = ((await (await request.get(LISTS_API, { headers: OCS })).json()).results ?? []) as Array<Record<string, any>>
		for (const summary of lists) {
			const list = await destructionList(request, String(summary.uuid))
			const uuids = ((list.objects ?? []) as Array<Record<string, any>>).map((o) => o.uuid)
			expect(uuids, `destruction list ${summary.uuid} does not hold the bewaren record`).not.toContain(uuid)
		}
	})
})
