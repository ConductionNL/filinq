/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Contract lifecycle on the contract detail: renew, end with a reason, a
 * refused move, and generate, attach and send for signature. The object API
 * and the routes are answered by route handlers; OpenRegister's lifecycle
 * refusal is answered with the status it sends. The server half is proven by
 * tests/unit/Service/Contract/ContractServiceTest.php,
 * ContractSigningLinkTest.php and ContractRegisterDeclarationTest.php.
 */

// @e2e openspec/specs/contract-lifecycle-management/spec.md#renewal-creates-a-linked-successor
// @e2e openspec/specs/contract-lifecycle-management/spec.md#invalid-transition-is-rejected-declaratively
// @e2e openspec/specs/contract-lifecycle-management/spec.md#termination-requires-a-reason
// @e2e openspec/specs/contract-lifecycle-management/spec.md#generate-attach-and-send-for-signature-from-the-contract

import type { Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import {
	appUrl,
	dismissOverlays,
	waitForAppReady,
} from '../spec-coverage/_helpers.ts'

const OBJECTS = '**/apps/openregister/api/objects/filinq/documentContract'

const ACTIVE = {
	id: 'c-1',
	title: 'Raamovereenkomst groenonderhoud',
	contractType: 'inkoop',
	parties: [{ role: 'opdrachtgever', displayName: 'Gemeente Demostad' }],
	internalOwner: 'admin',
	endDate: '2028-12-31',
	noticePeriodDays: 90,
	noticeDeadline: '2028-10-02',
	value: 240000,
	currency: 'EUR',
	status: 'active',
	documents: [],
	keyTermSuggestions: [],
}

/**
 * Answer the object API for the given contracts; a PUT stores the body.
 *
 * @param page The page
 * @param contracts The contracts, by id
 * @param refuse Answer PUTs with OpenRegister's lifecycle refusal
 */
async function serve(
	page: Page,
	contracts: Record<string, any>,
	refuse = false,
): Promise<void> {
	await page.route(`${OBJECTS}/*`, (route) => {
		const id = new URL(route.request().url()).pathname.split('/').pop() as string
		if (route.request().method() === 'PUT') {
			if (refuse) {
				return route.fulfill({
					status: 400,
					json: { error: 'Transition not allowed' },
				})
			}
			contracts[id] = { ...route.request().postDataJSON(), id }
		}
		return route.fulfill({ json: contracts[id] })
	})
	await page.route('**/apps/filinq/api/contracts/*/parties', (route) =>
		route.fulfill({ json: [] }),
	)
}

/**
 * Open a contract.
 *
 * @param page The page
 * @param id The contract
 */
async function open(page: Page, id: string): Promise<void> {
	await page.goto(await appUrl(page, `contracts/${id}`), {
		waitUntil: 'domcontentloaded',
	})
	await waitForAppReady(page)
	await dismissOverlays(page)
}

test.describe('contract lifecycle', () => {
	test('renewing opens the linked draft successor', async ({ page }) => {
		// @e2e openspec/specs/contract-lifecycle-management/spec.md#renewal-creates-a-linked-successor
		const contracts: Record<string, any> = { 'c-1': { ...ACTIVE } }
		await serve(page, contracts)
		await page.route('**/apps/filinq/api/contracts/c-1/renew', (route) => {
			contracts['c-1'] = {
				...contracts['c-1'],
				status: 'renewed',
				renewedBy: 'c-2',
			}
			contracts['c-2'] = {
				...ACTIVE,
				id: 'c-2',
				status: 'draft',
				renews: 'c-1',
				uuid: 'c-2',
			}
			return route.fulfill({
				json: { contract: contracts['c-1'], successor: contracts['c-2'] },
			})
		})
		await open(page, 'c-1')

		await page.getByTestId('contract-renew').click()
		await expect(page).toHaveURL(/contracts\/c-2/)
		await expect(page.getByTestId('contract-status')).toHaveText('Draft')
		await expect(
			page.getByText('This contract renews an earlier one.'),
		).toBeVisible()
		await expect(page.getByTestId('contract-term-value')).toContainText('240')
	})

	test('a move the lifecycle does not declare is refused and nothing changes', async ({
		page,
	}) => {
		// @e2e openspec/specs/contract-lifecycle-management/spec.md#invalid-transition-is-rejected-declaratively
		const contracts: Record<string, any> = {
			'c-3': { ...ACTIVE, id: 'c-3', status: 'draft' },
		}
		await serve(page, contracts, true)
		await open(page, 'c-3')

		await expect(page.getByTestId('contract-terminate')).toHaveCount(0)
		await page.getByTestId('contract-activate').click()
		await expect(
			page.getByText('The contract could not be changed. Try again later.'),
		).toBeVisible()
		await expect(page.getByTestId('contract-status')).toHaveText('Draft')
	})

	test('ending needs a reason, and the reason is kept', async ({ page }) => {
		// @e2e openspec/specs/contract-lifecycle-management/spec.md#termination-requires-a-reason
		const contracts: Record<string, any> = { 'c-1': { ...ACTIVE } }
		await serve(page, contracts)
		await page.route('**/apps/filinq/api/contracts/c-1/terminate', (route) => {
			const reason = route.request().postDataJSON().reason
			contracts['c-1'] = {
				...contracts['c-1'],
				status: 'terminated',
				terminationReason: reason,
			}
			return route.fulfill({ json: contracts['c-1'] })
		})
		await open(page, 'c-1')

		await page.getByTestId('contract-terminate').click()
		const confirm = page.getByTestId('contract-terminate-confirm')
		await expect(confirm).toBeDisabled()
		await page
			.getByTestId('contract-terminate-reason')
			.locator('textarea')
			.fill('Opgezegd per brief')
		await confirm.click()
		await expect(page.getByTestId('contract-status')).toHaveText('Terminated')
		await expect(page.getByTestId('contract-term-terminationReason')).toHaveText(
			'Opgezegd per brief',
		)
	})

	test('generate, attach and send for signature keep references on the contract', async ({
		page,
	}) => {
		// @e2e openspec/specs/contract-lifecycle-management/spec.md#generate-attach-and-send-for-signature-from-the-contract
		const contracts: Record<string, any> = { 'c-1': { ...ACTIVE } }
		await serve(page, contracts)
		await page.route(
			'**/apps/openregister/api/objects/filinq/template**',
			(route) =>
				route.fulfill({
					json: { results: [{ id: 'tpl-1', name: 'Raamovereenkomst' }] },
				}),
		)
		await page.route('**/apps/filinq/api/documents/generate', (route) =>
			route.fulfill({
				json: { fileId: 88, name: 'Raamovereenkomst.pdf', format: 'pdf' },
			}),
		)
		await page.route('**/apps/filinq/api/contracts/c-1/suggestions', (route) =>
			route.fulfill({
				json: { contract: contracts['c-1'], added: 0, enabled: true },
			}),
		)
		await page.route('**/apps/filinq/api/signing/requests', (route) =>
			route.fulfill({ json: { id: 'req-1', status: 'DRAFT' } }),
		)
		await page.route('**/apps/filinq/api/contracts/c-1/signing', (route) => {
			contracts['c-1'] = { ...contracts['c-1'], signingRequestRef: 'req-1' }
			return route.fulfill({
				json: {
					contract: contracts['c-1'],
					signingRequest: { id: 'req-1', status: 'DRAFT', signed: false },
				},
			})
		})
		await open(page, 'c-1')

		await page.getByTestId('contract-generate').click()
		await page.getByTestId('contract-generate-template').click()
		await page.getByRole('option', { name: 'Raamovereenkomst' }).click()
		await page.getByTestId('contract-generate-confirm').click()
		await expect(page.getByTestId('contract-documents')).toContainText('File 88')
		expect(contracts['c-1'].documents).toEqual(['88'])

		await page.getByTestId('contract-send-for-signature').click()
		await page.getByTestId('contract-sign-user').locator('input').fill('admin')
		await page.getByTestId('contract-sign-confirm').click()
		await expect(page.getByTestId('contract-signing-status')).toContainText(
			'DRAFT',
		)
		expect(contracts['c-1'].signingRequestRef).toBe('req-1')
	})
})
