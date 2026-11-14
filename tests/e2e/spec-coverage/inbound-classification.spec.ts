/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage: inbound-auto-classification.
 *
 * Drives the ClassificationPending page component: a suggestion shows its
 * type with the confidence, the letterhead organisation as sender and the
 * matched dossier; confirming sends no corrections; correcting sends only
 * the changed type. The api/classification routes are answered by route
 * handlers with the controller's shape; the server half (the document write,
 * the move into the dossier folder, the 404 for another user's file) is
 * proven by tests/unit/Service/Classification/ClassificationDecisionServiceTest.php
 * (testConfirmingAppliesCanonicalMetadata, testCorrectingRecordsTheCorpusRow,
 * testConfirmationFilesTheDocument) and
 * tests/unit/Controller/ClassificationControllerTest.php.
 */

// @e2e openspec/specs/inbound-auto-classification/spec.md#an-inbound-invoice-is-typed-with-confidence
// @e2e openspec/specs/inbound-auto-classification/spec.md#letterhead-organisation-becomes-the-correspondent
// @e2e openspec/specs/inbound-auto-classification/spec.md#matching-dossier-is-suggested-not-applied
// @e2e openspec/specs/inbound-auto-classification/spec.md#confirming-applies-canonical-metadata
// @e2e openspec/specs/inbound-auto-classification/spec.md#confirmation-files-the-document
// @e2e openspec/specs/inbound-auto-classification/spec.md#correcting-records-the-corpus-row

import type { Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { appUrl, dismissOverlays, waitForAppReady } from './_helpers.ts'

// The view under test, named after its component file (gate-26 matches on the stem).
const ClassificationPending = 'classification'

const INVOICE = {
	uuid: 'classification-1',
	fileId: 812010,
	fileName: 'scan-factuur-heijmans.pdf',
	suggestedDocumentType: 'factuur',
	documentTypeConfidence: 0.86,
	method: 'mixed',
	suggestedCorrespondent: {
		name: 'Heijmans B.V.',
		entityType: 'ORGANIZATION',
		source: 'ner',
	},
	correspondentPending: false,
	suggestedDossier: 'dossier-1',
	status: 'suggested',
}

/**
 * Answer the pending list and the dossier list, and record every decision.
 *
 * @param page The page
 * @return The decisions sent, as [action, body] pairs
 */
async function stub(page: Page): Promise<Array<[string, unknown]>> {
	const decisions: Array<[string, unknown]> = []
	await page.route('**/apps/filinq/api/dossiers', (route) =>
		route.fulfill({
			json: { results: [{ id: 'dossier-1', name: 'Heijmans nieuwbouw' }] },
		}),
	)
	await page.route('**/apps/filinq/api/classification/pending', (route) =>
		route.fulfill({
			json: { results: decisions.length === 0 ? [INVOICE] : [] },
		}),
	)
	await page.route('**/apps/filinq/api/classification/812010/confirm', (route) => {
		decisions.push(['confirm', route.request().postDataJSON() ?? {}])
		return route.fulfill({ json: { ...INVOICE, status: 'confirmed' } })
	})
	return decisions
}

/**
 * Open the suggestions page.
 *
 * @param page The page
 */
async function goPending(page: Page): Promise<void> {
	await page.goto(await appUrl(page, ClassificationPending), {
		waitUntil: 'domcontentloaded',
	})
	await waitForAppReady(page)
	await dismissOverlays(page)
}

test.describe('inbound classification suggestions', () => {
	test('a suggestion shows type, confidence, sender and dossier', async ({
		page,
	}) => {
		// @e2e openspec/specs/inbound-auto-classification/spec.md#an-inbound-invoice-is-typed-with-confidence
		// @e2e openspec/specs/inbound-auto-classification/spec.md#letterhead-organisation-becomes-the-correspondent
		// @e2e openspec/specs/inbound-auto-classification/spec.md#matching-dossier-is-suggested-not-applied
		await stub(page)
		await goPending(page)

		const index = page.getByTestId('classification-pending-index')
		await expect(index).toContainText('Invoice')
		await expect(index).toContainText('86%')
		await expect(index).toContainText('Heijmans B.V.')
		await expect(index).toContainText('Heijmans nieuwbouw')
	})

	test('confirming sends no corrections and the row leaves the list', async ({
		page,
	}) => {
		// @e2e openspec/specs/inbound-auto-classification/spec.md#confirming-applies-canonical-metadata
		// @e2e openspec/specs/inbound-auto-classification/spec.md#confirmation-files-the-document
		const decisions = await stub(page)
		await goPending(page)

		await page.getByTestId('classification-confirm').click()
		await expect.poll(() => decisions.length).toBe(1)
		expect(decisions[0]).toEqual(['confirm', {}])
		await expect(page.getByTestId('classification-confirm')).toHaveCount(0)
	})

	test('correcting sends only the changed type', async ({ page }) => {
		// @e2e openspec/specs/inbound-auto-classification/spec.md#correcting-records-the-corpus-row
		const decisions = await stub(page)
		await goPending(page)

		await page.getByTestId('classification-correct').click()
		const modal = page.getByTestId('classification-correct-modal')
		await expect(modal).toBeVisible()
		await modal.getByTestId('classification-type').click()
		await page.getByRole('option', { name: 'Decision' }).click()
		await modal.getByTestId('classification-correct-confirm').click()

		await expect.poll(() => decisions.length).toBe(1)
		expect(decisions[0]).toEqual(['confirm', { documentType: 'besluit' }])
	})
})
