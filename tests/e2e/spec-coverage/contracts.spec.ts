/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage: contract-lifecycle-management.
 *
 * Drives the ContractDetail and ContractPipeline pages. The object API and
 * the contract routes are answered by route handlers, so the pages are
 * exercised against fixed contracts and a fixed shape; the server half
 * (defaulting, lifecycle, suggestions, contact lookup) is proven by
 * tests/unit/Service/Contract/*Test.php and
 * tests/unit/Controller/ContractControllerTest.php.
 */

// @e2e openspec/specs/contract-lifecycle-management/spec.md#contract-created-with-parties-from-contacts
// @e2e openspec/specs/contract-lifecycle-management/spec.md#suggestions-propose-the-human-disposes
// @e2e openspec/specs/contract-lifecycle-management/spec.md#pipeline-buckets-the-seeded-contracts-correctly
// @e2e openspec/specs/contract-lifecycle-management/spec.md#notice-deadline-defaults-from-end-date-and-notice-period
// @e2e openspec/specs/contract-lifecycle-management/spec.md#action-routes-are-guarded
// @e2e openspec/specs/contract-lifecycle-management/spec.md#approaching-notice-deadline-notifies-the-contract-managers
// @e2e openspec/specs/contract-lifecycle-management/spec.md#signed-artifact-is-linked-back
// @e2e openspec/specs/contract-lifecycle-management/spec.md#toggle-disables-extraction-entirely

import type { Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { harvestToken } from '../workflows/_fixtures.ts'
import { appUrl, dismissOverlays, waitForAppReady } from './_helpers.ts'

const OBJECTS = '**/apps/openregister/api/objects/filinq/documentContract'

/**
 * A date a number of days from today, as YYYY-MM-DD.
 *
 * @param days Days from today
 * @return The date
 */
function inDays(days: number): string {
	const d = new Date()
	d.setDate(d.getDate() + days)
	return d.toISOString().slice(0, 10)
}

const GROEN = {
	id: 'c-groen',
	title: 'Raamovereenkomst groenonderhoud',
	contractType: 'inkoop',
	parties: [
		{
			contactRef: 'urn:nc:contact:00000000-0000-0000-0000-000000000010',
			role: 'opdrachtnemer',
			displayName: 'Oude naam',
		},
		{ role: 'opdrachtgever', displayName: 'Gemeente Demostad' },
	],
	startDate: '2026-01-01',
	endDate: inDays(800),
	noticePeriodDays: 90,
	noticeDeadline: inDays(710),
	value: null,
	currency: 'EUR',
	status: 'active',
	documents: ['412'],
	keyTermSuggestions: [
		{
			field: 'endDate',
			value: '2028-12-31',
			confidence: 0.9,
			source: 'file:412',
			status: 'proposed',
		},
		{
			field: 'value',
			value: '240000',
			confidence: 0.6,
			source: 'file:412',
			status: 'proposed',
		},
	],
}

const SCHOON = {
	...GROEN,
	id: 'c-schoon',
	title: 'Schoonmaakdienstverlening stadskantoor',
	endDate: inDays(80),
	noticeDeadline: inDays(20),
	keyTermSuggestions: [],
}

const CONCEPT = {
	id: 'c-concept',
	title: 'Subsidieovereenkomst cultuurfonds (concept)',
	status: 'draft',
	documents: [],
	keyTermSuggestions: [],
}

/**
 * Answer the object API and the parties route for the given contracts.
 *
 * @param page The page
 * @param contracts The contracts, by id
 */
async function serve(page: Page, contracts: Record<string, object>): Promise<void> {
	await page.route(`${OBJECTS}?**`, (route) =>
		route.fulfill({ json: { results: Object.values(contracts) } }),
	)
	await page.route(`${OBJECTS}/*`, (route) => {
		const id = new URL(route.request().url()).pathname.split('/').pop() as string
		return route.fulfill({ json: contracts[id] })
	})
	await page.route('**/apps/filinq/api/contracts/*/parties', (route) =>
		route.fulfill({
			json: [
				{
					role: 'opdrachtnemer',
					displayName: 'Groenbedrijf Demostad B.V.',
					contactRef: GROEN.parties[0].contactRef,
					linked: true,
					email: 'info@groen.example',
				},
				{
					role: 'opdrachtgever',
					displayName: 'Gemeente Demostad',
					contactRef: '',
					linked: false,
					email: '',
				},
			],
		}),
	)
}

/**
 * Open a route of the app.
 *
 * @param page The page
 * @param route The route
 */
async function open(page: Page, route: string): Promise<void> {
	await page.goto(await appUrl(page, route), { waitUntil: 'domcontentloaded' })
	await waitForAppReady(page)
	await dismissOverlays(page)
}

test.describe('contracts', () => {
	test('both parties render, the linked one from its contact', async ({
		page,
	}) => {
		// @e2e openspec/specs/contract-lifecycle-management/spec.md#contract-created-with-parties-from-contacts
		await serve(page, { 'c-groen': GROEN })
		await open(page, 'contracts/c-groen')

		const parties = page.getByTestId('contract-parties')
		await expect(parties).toContainText('Groenbedrijf Demostad B.V.')
		await expect(parties).toContainText('from contacts')
		await expect(parties).toContainText('Gemeente Demostad')
		await expect(parties).not.toContainText('Oude naam')
		await expect(page.getByTestId('contract-term-noticePeriodDays')).toHaveText(
			'90',
		)
	})

	test('a suggestion changes nothing until it is accepted', async ({ page }) => {
		// @e2e openspec/specs/contract-lifecycle-management/spec.md#suggestions-propose-the-human-disposes
		const contract = JSON.parse(JSON.stringify(GROEN))
		await serve(page, { 'c-groen': contract })
		const decisions: string[] = []
		await page.route(
			'**/apps/filinq/api/contracts/c-groen/suggestions/*',
			(route) => {
				const index = Number(
					new URL(route.request().url()).pathname.split('/').pop(),
				)
				const decision = route.request().postDataJSON().decision
				decisions.push(`${index}:${decision}`)
				contract.keyTermSuggestions[index].status = decision
				if (decision === 'accepted') {
					contract.endDate = contract.keyTermSuggestions[index].value
				}
				return route.fulfill({ json: contract })
			},
		)
		await open(page, 'contracts/c-groen')

		await expect(page.getByTestId('contract-suggestions')).toBeVisible()
		await expect(page.getByTestId('contract-term-value')).toHaveText('-')
		await page.getByRole('button', { name: 'Accept End date' }).click()
		await expect(page.getByTestId('contract-term-endDate')).toHaveText(
			'2028-12-31',
		)
		await page.getByRole('button', { name: 'Reject Value' }).click()
		await expect(page.getByTestId('contract-suggestion-value')).toContainText(
			'Rejected',
		)
		await expect(page.getByTestId('contract-term-value')).toHaveText('-')
		expect(decisions).toEqual(['0:accepted', '1:rejected'])
	})

	test('the pipeline buckets the seeded contracts and renewing moves one out', async ({
		page,
	}) => {
		// @e2e openspec/specs/contract-lifecycle-management/spec.md#pipeline-buckets-the-seeded-contracts-correctly
		const contracts: Record<string, any> = {
			'c-groen': { ...GROEN },
			'c-schoon': { ...SCHOON },
			'c-concept': { ...CONCEPT },
		}
		await serve(page, contracts)
		await page.route('**/apps/filinq/api/contracts/c-schoon/renew', (route) => {
			contracts['c-schoon'].status = 'renewed'
			contracts['c-next'] = {
				...SCHOON,
				id: 'c-next',
				status: 'draft',
				renews: 'c-schoon',
			}
			return route.fulfill({
				json: {
					contract: contracts['c-schoon'],
					successor: contracts['c-next'],
				},
			})
		})
		await open(page, 'contracts/pipeline')

		await expect(page.getByTestId('contract-bucket-noticeDue')).toContainText(
			'Schoonmaakdienstverlening stadskantoor',
		)
		await expect(page.getByTestId('contract-bucket-later')).toContainText(
			'Raamovereenkomst groenonderhoud',
		)
		for (const bucket of ['expired', 'noticeDue', 'expiring', 'later']) {
			await expect(
				page.getByTestId(`contract-bucket-${bucket}`),
			).not.toContainText('Subsidieovereenkomst')
		}

		await page
			.getByRole('button', {
				name: 'Renew Schoonmaakdienstverlening stadskantoor',
			})
			.click()
		await expect(
			page.getByTestId('contract-bucket-noticeDue'),
		).not.toContainText('Schoonmaakdienstverlening stadskantoor')
	})
})

test.describe('contracts against the instance', () => {
	test('the notice deadline is filled in from the end date and the notice period', async ({
		page,
	}) => {
		// @e2e openspec/specs/contract-lifecycle-management/spec.md#notice-deadline-defaults-from-end-date-and-notice-period
		const token = await harvestToken(page)
		const created = await page.request.post(
			'/index.php/apps/openregister/api/objects/filinq/documentContract',
			{
				headers: { requesttoken: token, 'Content-Type': 'application/json' },
				data: {
					title: `e2e ${Date.now()}`,
					status: 'draft',
					endDate: '2028-12-31',
					noticePeriodDays: 90,
				},
			},
		)
		expect(created.status(), 'create the contract').toBeLessThan(300)
		const body = await created.json()
		const id = body.id ?? body['@self']?.id ?? body.uuid
		const read = await (
			await page.request.get(
				`/index.php/apps/openregister/api/objects/filinq/documentContract/${id}`,
				{ headers: { requesttoken: token } },
			)
		).json()
		expect(read.noticeDeadline).toBe('2028-10-02')

		const kept = await page.request.put(
			`/index.php/apps/openregister/api/objects/filinq/documentContract/${id}`,
			{
				headers: { requesttoken: token, 'Content-Type': 'application/json' },
				data: { ...read, noticeDeadline: '2028-09-01' },
			},
		)
		expect((await kept.json()).noticeDeadline).toBe('2028-09-01')
	})

	test('the renew and end routes refuse a contract the caller cannot read', async ({
		page,
	}) => {
		// @e2e openspec/specs/contract-lifecycle-management/spec.md#action-routes-are-guarded
		const token = await harvestToken(page)
		for (const path of ['renew', 'terminate']) {
			const answer = await page.request.post(
				`/index.php/apps/filinq/api/contracts/00000000-0000-0000-0000-00000000dead/${path}`,
				{ headers: { requesttoken: token }, data: { reason: 'x' } },
			)
			expect([403, 404]).toContain(answer.status())
		}
	})

	test('the register declares both reminders for active contracts only', async ({
		page,
	}) => {
		// @e2e openspec/specs/contract-lifecycle-management/spec.md#approaching-notice-deadline-notifies-the-contract-managers
		const token = await harvestToken(page)
		const answer = await page.request.get(
			'/index.php/apps/openregister/api/schemas?_search=documentContract',
			{ headers: { requesttoken: token } },
		)
		const schemas = (await answer.json()).results ?? []
		const schema = schemas.find(
			(s: { slug?: string }) => s.slug === 'documentContract',
		)
		expect(schema, 'documentContract is imported').toBeTruthy()
		const notifications = JSON.stringify(schema)
		expect(notifications).toContain('noticeDeadline')
		expect(notifications).toContain('filinq-contract-managers')
		expect(notifications).toContain('"status":"active"')
	})
})

test.describe('contract detail states', () => {
	test('a completed signing request shows the contract as signed', async ({
		page,
	}) => {
		// @e2e openspec/specs/contract-lifecycle-management/spec.md#signed-artifact-is-linked-back
		const contract = {
			...GROEN,
			documents: ['412'],
			signingRequestRef: 'req-7',
			keyTermSuggestions: [],
		}
		await serve(page, { 'c-groen': contract })
		await page.route('**/apps/filinq/api/contracts/c-groen/signing', (route) =>
			route.fulfill({
				json: {
					contract: { ...contract, signedDocumentRef: '412' },
					signingRequest: {
						id: 'req-7',
						status: 'COMPLETED',
						signed: true,
					},
				},
			}),
		)
		await open(page, 'contracts/c-groen')

		await expect(page.getByTestId('contract-signing-status')).toContainText(
			'Signed.',
		)
		await expect(page.getByTestId('contract-documents')).toContainText('signed')
	})

	test('with term suggestions switched off there is no suggestions panel', async ({
		page,
	}) => {
		// @e2e openspec/specs/contract-lifecycle-management/spec.md#toggle-disables-extraction-entirely
		await page.route('**/apps/filinq/api/settings', async (route) => {
			const response = await route.fetch()
			const json = await response.json()
			return route.fulfill({
				json: { ...json, enable_contract_term_extraction: false },
			})
		})
		await serve(page, { 'c-groen': GROEN })
		await open(page, 'contracts/c-groen')

		await expect(page.getByTestId('contract-term-endDate')).toBeVisible()
		await expect(page.getByTestId('contract-suggestions')).toHaveCount(0)
	})
})
