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

import type { Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import {
	appUrl,
	dismissOverlays,
	waitForAppReady,
} from './_helpers.ts'

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
		{ field: 'endDate', value: '2028-12-31', confidence: 0.9, source: 'file:412', status: 'proposed' },
		{ field: 'value', value: '240000', confidence: 0.6, source: 'file:412', status: 'proposed' },
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
				{ role: 'opdrachtnemer', displayName: 'Groenbedrijf Demostad B.V.', contactRef: GROEN.parties[0].contactRef, linked: true, email: 'info@groen.example' },
				{ role: 'opdrachtgever', displayName: 'Gemeente Demostad', contactRef: '', linked: false, email: '' },
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
	test('both parties render, the linked one from its contact', async ({ page }) => {
		// @e2e openspec/specs/contract-lifecycle-management/spec.md#contract-created-with-parties-from-contacts
		await serve(page, { 'c-groen': GROEN })
		await open(page, 'contracts/c-groen')

		const parties = page.getByTestId('contract-parties')
		await expect(parties).toContainText('Groenbedrijf Demostad B.V.')
		await expect(parties).toContainText('from contacts')
		await expect(parties).toContainText('Gemeente Demostad')
		await expect(parties).not.toContainText('Oude naam')
		await expect(page.getByTestId('contract-term-noticePeriodDays')).toHaveText('90')
	})

	test('a suggestion changes nothing until it is accepted', async ({ page }) => {
		// @e2e openspec/specs/contract-lifecycle-management/spec.md#suggestions-propose-the-human-disposes
		const contract = JSON.parse(JSON.stringify(GROEN))
		await serve(page, { 'c-groen': contract })
		const decisions: string[] = []
		await page.route('**/apps/filinq/api/contracts/c-groen/suggestions/*', (route) => {
			const index = Number(new URL(route.request().url()).pathname.split('/').pop())
			const decision = route.request().postDataJSON().decision
			decisions.push(`${index}:${decision}`)
			contract.keyTermSuggestions[index].status = decision
			if (decision === 'accepted') {
				contract.endDate = contract.keyTermSuggestions[index].value
			}
			return route.fulfill({ json: contract })
		})
		await open(page, 'contracts/c-groen')

		await expect(page.getByTestId('contract-suggestions')).toBeVisible()
		await expect(page.getByTestId('contract-term-value')).toHaveText('-')
		await page.getByRole('button', { name: 'Accept End date' }).click()
		await expect(page.getByTestId('contract-term-endDate')).toHaveText('2028-12-31')
		await page.getByRole('button', { name: 'Reject Value' }).click()
		await expect(page.getByTestId('contract-suggestion-value')).toContainText('Rejected')
		await expect(page.getByTestId('contract-term-value')).toHaveText('-')
		expect(decisions).toEqual(['0:accepted', '1:rejected'])
	})

	test('the pipeline buckets the seeded contracts and renewing moves one out', async ({ page }) => {
		// @e2e openspec/specs/contract-lifecycle-management/spec.md#pipeline-buckets-the-seeded-contracts-correctly
		const contracts: Record<string, any> = {
			'c-groen': { ...GROEN },
			'c-schoon': { ...SCHOON },
			'c-concept': { ...CONCEPT },
		}
		await serve(page, contracts)
		await page.route('**/apps/filinq/api/contracts/c-schoon/renew', (route) => {
			contracts['c-schoon'].status = 'renewed'
			contracts['c-next'] = { ...SCHOON, id: 'c-next', status: 'draft', renews: 'c-schoon' }
			return route.fulfill({ json: { contract: contracts['c-schoon'], successor: contracts['c-next'] } })
		})
		await open(page, 'contracts/pipeline')

		await expect(page.getByTestId('contract-bucket-noticeDue')).toContainText('Schoonmaakdienstverlening stadskantoor')
		await expect(page.getByTestId('contract-bucket-later')).toContainText('Raamovereenkomst groenonderhoud')
		for (const bucket of ['expired', 'noticeDue', 'expiring', 'later']) {
			await expect(page.getByTestId(`contract-bucket-${bucket}`)).not.toContainText('Subsidieovereenkomst')
		}

		await page.getByRole('button', { name: 'Renew Schoonmaakdienstverlening stadskantoor' }).click()
		await expect(page.getByTestId('contract-bucket-noticeDue')).not.toContainText('Schoonmaakdienstverlening stadskantoor')
	})
})
