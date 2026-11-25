/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * intake-worklist-deadline-and-duplicates (REQ-IWS-001 to REQ-IWS-004): the
 * intake worklist shows each waiting document's handling deadline with a
 * working-day countdown, lets a registrar move it with a reason, lists what
 * is overdue, marks a possible duplicate and rejects it in one step.
 *
 * WHAT IS DRIVEN THROUGH THE UI AND WHAT IS NOT
 * --------------------------------------------
 * The countdown, "Change deadline", the Overdue mode, the "Possible
 * duplicate" badge and "Reject as duplicate" on /intake are what a registrar
 * and a team lead use, so they are read and clicked. The deadline and the
 * duplicate mark are written AT ARRIVAL (from the rule's handlingDays and the
 * file's SHA-256), and a document arrives through a background feeder, not a
 * screen; PHPUnit covers that computation. So:
 *  - the ten-working-days scenario answers GET /api/intake/documents from a
 *    route handler with a row due Monday 19 October, and fixes the browser
 *    clock on Monday 5 October, so the countdown is the page's own working-day
 *    count;
 *  - every other scenario seeds real intake documents through OpenRegister's
 *    object API with the `handlingDeadline` or `duplicateOf` the arrival would
 *    have written, and goes through filinq's real deadline, overdue and reject
 *    routes. Each seeded document is deleted at the end.
 *
 * The overdue scenario's assigned document names no default rule, so it
 * falls under the default `handledWhen: assigned` and counts as handled.
 */

import type { APIRequestContext, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { go } from './spec-coverage/_helpers.ts'
import { harvestToken, jsonHeaders, TEST_PREFIX } from './workflows/_fixtures.ts'

/** OpenRegister's objects endpoint for intake documents. */
const OR_INTAKE = '/index.php/apps/openregister/api/objects/filinq/intakeDocument'

/**
 * A local date as YYYY-MM-DD.
 *
 * @param date The date.
 * @return The ISO day.
 */
function isoDay(date: Date): string {
	const month = String(date.getMonth() + 1).padStart(2, '0')
	const day = String(date.getDate()).padStart(2, '0')
	return `${date.getFullYear()}-${month}-${day}`
}

/**
 * Move a date by a number of working days (Monday to Friday).
 *
 * @param from The start.
 * @param days Working days, negative for the past.
 * @return The moved date.
 */
function workingDaysFrom(from: Date, days: number): Date {
	const date = new Date(from.getFullYear(), from.getMonth(), from.getDate())
	const step = days < 0 ? -1 : 1
	let left = Math.abs(days)
	while (left > 0) {
		date.setDate(date.getDate() + step)
		if (date.getDay() !== 0 && date.getDay() !== 6) {
			left--
		}
	}
	return date
}

/**
 * Seed one intake document.
 *
 * @param req     The request context.
 * @param token   The CSRF request-token.
 * @param subject The subject line.
 * @param extra   Fields the arrival would have written.
 * @return The uuid.
 */
async function seed(
	req: APIRequestContext,
	token: string,
	subject: string,
	extra: Record<string, unknown> = {},
): Promise<string> {
	const created = await req.post(OR_INTAKE, {
		headers: jsonHeaders(token),
		data: {
			channel: 'mail',
			subject,
			sender: `${TEST_PREFIX}@voorbeeld.nl`,
			receivedAt: new Date().toISOString(),
			sourceRef: `${TEST_PREFIX}-${subject}`,
			status: 'received',
			...extra,
		},
	})
	expect(
		created.status(),
		`seeding ${subject}: ${await created.text()}`,
	).toBeLessThan(300)
	const body = await created.json()
	const uuid = String(body?.['@self']?.id || body?.id || body?.uuid || '')
	expect(uuid).not.toBe('')
	return uuid
}

/**
 * Read one intake document back.
 *
 * @param req   The request context.
 * @param token The CSRF request-token.
 * @param uuid  The document.
 * @return Its fields.
 */
async function readBack(
	req: APIRequestContext,
	token: string,
	uuid: string,
): Promise<Record<string, any>> {
	const response = await req.get(`${OR_INTAKE}/${uuid}`, {
		headers: jsonHeaders(token),
	})
	expect(response.status()).toBe(200)
	const body = await response.json()
	return (body.object ?? body) as Record<string, any>
}

/**
 * The worklist row of a subject.
 *
 * @param page    The page.
 * @param subject The subject.
 * @return The row.
 */
function rowOf(page: Page, subject: string) {
	return page.getByRole('row', { name: new RegExp(subject) })
}

/**
 * Click one row action.
 *
 * @param page    The page.
 * @param subject The row's subject.
 * @param action  The action's label.
 * @return Nothing.
 */
async function rowAction(
	page: Page,
	subject: string,
	action: string,
): Promise<void> {
	const row = rowOf(page, subject)
	await expect(row).toBeVisible({ timeout: 15000 })
	await row.getByRole('button').last().click()
	await page
		.getByRole('menuitem', { name: action })
		.or(page.getByRole('button', { name: action }))
		.first()
		.click()
}

test.describe('Deadlines and duplicates on the intake worklist', () => {
	test.describe.configure({ mode: 'serial' })

	let token = ''
	const seeded: string[] = []

	test.beforeAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		token = await harvestToken(page)
		expect(token, 'the suite needs a live request token').not.toBe('')
		await page.close()
	})

	test.afterAll(async ({ browser }) => {
		const page = await browser.newPage()
		for (const uuid of seeded) {
			await page.request
				.delete(`${OR_INTAKE}/${uuid}`, { headers: jsonHeaders(token) })
				.catch(() => undefined)
		}
		await page.close()
	})

	// @e2e intake-worklist-signals::a-letter-gets-ten-working-days
	test('a letter that arrived on Monday 5 October is due Monday 19 October, 10 days left', async ({
		page,
	}) => {
		await page.clock.setFixedTime(new Date(2026, 9, 5, 10, 0, 0))
		const subject = `${TEST_PREFIX}-tien-dagen`
		await page.route(
			(url) =>
				url.pathname.endsWith('/apps/filinq/api/intake/documents')
				&& !url.searchParams.has('mode'),
			async (route) => {
				if (route.request().method() !== 'GET') {
					await route.fallback()
					return
				}
				const row = {
					uuid: `${TEST_PREFIX}-tien-dagen`,
					channel: 'mail',
					sender: `${TEST_PREFIX}@voorbeeld.nl`,
					subject,
					receivedAt: '2026-10-05T09:00:00+02:00',
					status: 'received',
					handlingDeadline: '2026-10-19',
				}
				await route.fulfill({
					status: 200,
					contentType: 'application/json',
					body: JSON.stringify({
						results: [row],
						total: 1,
						notificationReach: null,
					}),
				})
			},
		)
		await go(page, 'intake')

		const row = rowOf(page, subject)
		await expect(row).toBeVisible({ timeout: 15000 })
		await expect(row).toContainText(new Date(2026, 9, 19).toLocaleDateString())
		await expect(row.getByTestId('intake-countdown')).toHaveText(/10 days left/)
	})

	// @e2e intake-worklist-signals::a-registrar-moves-a-deadline
	test('a registrar moves a deadline to next week and the reason stays on the document', async ({
		page,
	}) => {
		const subject = `${TEST_PREFIX}-verplaatsen`
		const today = new Date()
		const tomorrow = new Date(
			today.getFullYear(),
			today.getMonth(),
			today.getDate() + 1,
		)
		const nextWeek = new Date(
			today.getFullYear(),
			today.getMonth(),
			today.getDate() + 7,
		)
		const uuid = await seed(page.request, token, subject, {
			handlingDeadline: isoDay(tomorrow),
		})
		seeded.push(uuid)

		await go(page, 'intake')
		await rowAction(page, subject, 'Change deadline')
		await page
			.getByTestId('intake-deadline-input')
			.locator('xpath=descendant-or-self::input')
			.fill(isoDay(nextWeek))
		await page
			.getByTestId('intake-deadline-reason')
			.locator('xpath=descendant-or-self::input')
			.fill('waiting for the attachment')
		const saved = page.waitForResponse(
			(res) =>
				res.request().method() === 'POST'
				&& res.url().includes(`/api/intake/documents/${uuid}/deadline`),
		)
		await page.getByTestId('cn-dialog-actions-submit').click()
		const response = await saved
		expect(response.status(), await response.text()).toBe(200)

		await expect(rowOf(page, subject)).toContainText(
			nextWeek.toLocaleDateString(),
			{ timeout: 15000 },
		)
		const stored = await readBack(page.request, token, uuid)
		expect(String(stored.handlingDeadline).slice(0, 10)).toBe(isoDay(nextWeek))
		expect(stored.deadlineReason).toBe('waiting for the attachment')
	})

	// @e2e intake-worklist-signals::a-team-lead-sees-the-late-post
	test('Overdue lists the waiting late document and not the assigned one', async ({
		page,
	}) => {
		const late = `${TEST_PREFIX}-te-laat`
		const handled = `${TEST_PREFIX}-afgehandeld`
		const fiveAgo = workingDaysFrom(new Date(), -5)
		seeded.push(
			await seed(page.request, token, late, {
				handlingDeadline: isoDay(fiveAgo),
			}),
		)
		seeded.push(
			await seed(page.request, token, handled, {
				handlingDeadline: isoDay(fiveAgo),
				status: 'assigned',
				assignedTo: {
					register: 'filinq',
					schema: 'dossier',
					id: `${TEST_PREFIX}-zaak`,
				},
			}),
		)

		await go(page, 'intake')
		await page.getByTestId('intake-mode-overdue').click()
		const row = rowOf(page, late)
		await expect(row).toBeVisible({ timeout: 15000 })
		await expect(row.getByTestId('intake-countdown')).toHaveText(
			/5 days overdue/,
		)
		await expect(rowOf(page, handled)).toHaveCount(0)
	})

	// @e2e intake-worklist-signals::the-same-letter-by-scan-and-by-mail
	test('the second arrival of the same letter is waiting and marked as a possible duplicate of the first', async ({
		page,
	}) => {
		const first = `${TEST_PREFIX}-scan-eerst`
		const second = `${TEST_PREFIX}-mail-tweede`
		const firstUuid = await seed(page.request, token, first, { channel: 'scan' })
		seeded.push(firstUuid)
		seeded.push(
			await seed(page.request, token, second, { duplicateOf: firstUuid }),
		)

		await go(page, 'intake')
		await expect(rowOf(page, first)).toBeVisible({ timeout: 15000 })
		await expect(
			rowOf(page, first).getByTestId('intake-duplicate-badge'),
		).toHaveCount(0)
		const badge = rowOf(page, second).getByTestId('intake-duplicate-badge')
		await expect(badge).toHaveText(/Possible duplicate/)
		await expect(badge).toHaveAttribute(
			'title',
			`The same file arrived before as "${first}"`,
		)
	})

	// @e2e intake-worklist-signals::a-registrar-rejects-the-second-copy
	test('Reject as duplicate takes the copy off the list with a reason naming the first', async ({
		page,
	}) => {
		const first = `${TEST_PREFIX} Bezwaar parkeervergunning`
		const copy = `${TEST_PREFIX}-bezwaar-kopie`
		const firstUuid = await seed(page.request, token, first)
		seeded.push(firstUuid)
		const copyUuid = await seed(page.request, token, copy, {
			duplicateOf: firstUuid,
		})
		seeded.push(copyUuid)

		await go(page, 'intake')
		await rowAction(page, copy, 'Reject as duplicate')
		await expect(rowOf(page, copy)).toHaveCount(0, { timeout: 15000 })
		await expect(rowOf(page, first)).toBeVisible()

		const stored = await readBack(page.request, token, copyUuid)
		expect(stored.status).toBe('rejected')
		expect(stored.rejectReason).toBe(`Duplicate of ${first}`)
	})
})
