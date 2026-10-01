/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage: guided-document-wizard.
 *
 * Drives the WizardRunner page (/templates/:id/wizard) and the Wizard tab of
 * TemplateDetail. The filinq routes (api/templates/{id}/wizard, api/wizards,
 * api/wizards/{id}/prefill, api/documents/generate) are answered by route
 * handlers with the controllers' shapes, so the tests prove what the browser
 * sends and shows. The server half is proven by
 * tests/unit/Service/WizardServiceTest.php (validation, conditions,
 * translation, prefill, twig/office parity),
 * tests/unit/Service/DocumentServiceWizardTest.php (422 before rendering,
 * wizardContext on the generatedDocument, validated with Opis against the
 * real register fragment) and tests/unit/Controller/WizardControllerTest.php.
 */

// @e2e openspec/specs/guided-document-wizard/spec.md#author-saves-a-valid-wizard-for-a-template
// @e2e openspec/specs/guided-document-wizard/spec.md#unknown-mapsto-path-warns-but-saves
// @e2e openspec/specs/guided-document-wizard/spec.md#conditional-question-appears-only-on-the-triggering-answer
// @e2e openspec/specs/guided-document-wizard/spec.md#clerk-runs-the-wizard-end-to-end
// @e2e openspec/specs/guided-document-wizard/spec.md#runner-is-keyboard-operable
// @e2e openspec/specs/guided-document-wizard/spec.md#missing-required-visible-answer-fails-with-422
// @e2e openspec/specs/guided-document-wizard/spec.md#wizard-started-from-a-dossier-asks-only-the-remaining-questions
// @e2e openspec/specs/guided-document-wizard/spec.md#same-wizard-payload-for-both-template-types
// @e2e openspec/specs/document-creatie-sjablonen/spec.md#generated-document-carries-the-wizard-context

import type { Page, Request } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { appUrl, dismissOverlays, waitForAppReady } from './_helpers.ts'

// The view under test, named after its component file (gate-26 matches on the stem).
const WizardRunner = 'templates/tmpl-1/wizard'

const DECISION = {
	key: 'besluit',
	label: 'What is the decision?',
	type: 'choice',
	required: true,
	choices: [
		{ value: 'toegewezen', label: 'Granted' },
		{ value: 'afgewezen', label: 'Rejected' },
	],
	mapsTo: 'besluit.uitkomst',
}

const REASON = {
	key: 'afwijzingsreden',
	label: 'Reason for rejection',
	type: 'text',
	required: true,
	mapsTo: 'besluit.afwijzingsreden',
	condition: { questionKey: 'besluit', operator: 'equals', value: 'afgewezen' },
}

const START = {
	key: 'ingangsdatum',
	label: 'Effective date',
	type: 'date',
	required: true,
	mapsTo: 'besluit.ingangsdatum',
	condition: { questionKey: 'besluit', operator: 'equals', value: 'toegewezen' },
}

const DOSSIER = {
	key: 'dossier',
	label: 'Which dossier is this decision for?',
	type: 'registerObject',
	required: true,
	register: 'filinq',
	schema: 'dossier',
}

const WIZARD = {
	uuid: 'wizard-1',
	version: '3',
	name: 'Parking permit decision',
	templateId: 'tmpl-1',
	active: true,
	questions: [DECISION, REASON, START],
}

/**
 * Answer the wizard route of tmpl-1 with a wizard.
 *
 * @param page The page
 * @param wizard The wizard to serve
 */
async function serveWizard(page: Page, wizard: object): Promise<void> {
	await page.route('**/apps/filinq/api/templates/tmpl-1/wizard', (route) =>
		route.fulfill({ json: { wizard } }),
	)
}

/**
 * Capture the generate requests and answer each with a small PDF.
 *
 * @param page The page
 * @return The captured requests
 */
async function captureGenerate(page: Page): Promise<Request[]> {
	const sent: Request[] = []
	await page.route('**/apps/filinq/api/documents/generate', (route) => {
		sent.push(route.request())
		return route.fulfill({
			body: '%PDF-1.4',
			contentType: 'application/pdf',
			headers: {
				'content-disposition': 'attachment; filename="beschikking.pdf"',
			},
		})
	})
	return sent
}

/**
 * Open a page of the app.
 *
 * @param page The page
 * @param route The app route
 */
async function open(page: Page, route: string): Promise<void> {
	await page.goto(await appUrl(page, route), { waitUntil: 'domcontentloaded' })
	await waitForAppReady(page)
	await dismissOverlays(page)
}

test.describe('guided document wizard: runner', () => {
	test('a conditional question appears only on the triggering answer', async ({
		page,
	}) => {
		// @e2e openspec/specs/guided-document-wizard/spec.md#conditional-question-appears-only-on-the-triggering-answer
		await serveWizard(page, WIZARD)
		await open(page, WizardRunner)

		await expect(page.getByTestId('wizard-progress')).toContainText('1 of 1')
		await page.getByTestId('wizard-choice-afgewezen').click()
		await expect(page.getByTestId('wizard-progress')).toContainText('1 of 2')
		await page.getByTestId('wizard-next').click()
		await expect(page.getByTestId('wizard-step')).toContainText(
			'Reason for rejection',
		)

		await page.getByRole('button', { name: 'Previous' }).click()
		await page.getByTestId('wizard-choice-toegewezen').click()
		await page.getByTestId('wizard-next').click()
		await expect(page.getByTestId('wizard-step')).toContainText('Effective date')
		await expect(page.getByTestId('wizard-step')).not.toContainText(
			'Reason for rejection',
		)
	})

	test('a clerk runs the wizard end to end and one generate request carries the wizard context', async ({
		page,
	}) => {
		// @e2e openspec/specs/guided-document-wizard/spec.md#clerk-runs-the-wizard-end-to-end
		// @e2e openspec/specs/document-creatie-sjablonen/spec.md#generated-document-carries-the-wizard-context
		await serveWizard(page, WIZARD)
		const sent = await captureGenerate(page)
		await open(page, WizardRunner)

		await page.getByTestId('wizard-choice-afgewezen').click()
		await page.getByTestId('wizard-next').click()
		await page
			.getByTestId('wizard-answer-text')
			.locator('input')
			.fill('Geen parkeerplaats beschikbaar')
		await page.getByTestId('wizard-next').click()

		await expect(page.getByTestId('wizard-review')).toBeVisible()
		await expect(page.getByTestId('wizard-review-besluit')).toContainText(
			'Rejected',
		)
		await expect(
			page.getByTestId('wizard-review-afwijzingsreden'),
		).toContainText('Geen parkeerplaats beschikbaar')

		const download = page.waitForEvent('download')
		await page.getByTestId('wizard-generate').click()
		expect((await download).suggestedFilename()).toBe('beschikking.pdf')

		expect(sent).toHaveLength(1)
		const body = sent[0].postDataJSON()
		expect(body.templateId).toBe('tmpl-1')
		expect(body.options.adHocData).toEqual({
			besluit: {
				uitkomst: 'afgewezen',
				afwijzingsreden: 'Geen parkeerplaats beschikbaar',
			},
		})
		expect(body.options.wizardContext).toEqual({
			wizardId: 'wizard-1',
			wizardVersion: '3',
			answers: {
				besluit: 'afgewezen',
				afwijzingsreden: 'Geen parkeerplaats beschikbaar',
			},
		})
	})

	test('the runner is operable with the keyboard alone', async ({ page }) => {
		// @e2e openspec/specs/guided-document-wizard/spec.md#runner-is-keyboard-operable
		await serveWizard(page, WIZARD)
		const sent = await captureGenerate(page)
		await open(page, WizardRunner)

		const rejected = page.getByTestId('wizard-choice-afgewezen').locator('input')
		await rejected.focus()
		await page.keyboard.press('Space')
		await expect(rejected).toBeChecked()
		await page.getByTestId('wizard-next').focus()
		await page.keyboard.press('Enter')

		const reason = page.getByTestId('wizard-answer-text').locator('input')
		await reason.focus()
		await page.keyboard.type('Geen vergunning nodig')
		await page.keyboard.press('Enter')

		const generate = page.getByTestId('wizard-generate')
		await generate.focus()
		await page.keyboard.press('Enter')
		await expect.poll(() => sent.length).toBe(1)
	})

	test('a 422 marks the question the server refused on the review step', async ({
		page,
	}) => {
		// @e2e openspec/specs/guided-document-wizard/spec.md#missing-required-visible-answer-fails-with-422
		await serveWizard(page, WIZARD)
		await page.route('**/apps/filinq/api/documents/generate', (route) =>
			route.fulfill({
				status: 422,
				json: {
					error: 'The wizard answers are not complete',
					errors: { afwijzingsreden: 'An answer is required' },
				},
			}),
		)
		await open(page, WizardRunner)

		await page.getByTestId('wizard-choice-afgewezen').click()
		await page.getByTestId('wizard-next').click()
		await page.getByTestId('wizard-answer-text').locator('input').fill('x')
		await page.getByTestId('wizard-next').click()
		await page.getByTestId('wizard-generate').click()

		await expect(
			page.getByTestId('wizard-review-afwijzingsreden'),
		).toContainText('An answer is required')
	})

	test('started from a dossier, the dossier is suggested and the decision is asked', async ({
		page,
	}) => {
		// @e2e openspec/specs/guided-document-wizard/spec.md#wizard-started-from-a-dossier-asks-only-the-remaining-questions
		await serveWizard(page, { ...WIZARD, questions: [DOSSIER, DECISION] })
		let entry: Record<string, string> | null = null
		await page.route('**/apps/filinq/api/wizards/wizard-1/prefill', (route) => {
			entry = route.request().postDataJSON()
			return route.fulfill({
				json: {
					answers: { dossier: 'dossier-17' },
					unresolved: ['besluit'],
				},
			})
		})
		await open(
			page,
			`${WizardRunner}?register=filinq&schema=dossier&objectId=dossier-17`,
		)

		await expect
			.poll(() => entry)
			.toEqual({
				register: 'filinq',
				schema: 'dossier',
				objectId: 'dossier-17',
			})
		await expect(
			page.getByText(
				'Suggested from the object you started from. Change it if it is wrong.',
			),
		).toBeVisible()
		await page.getByTestId('wizard-next').click()
		await expect(page.getByTestId('wizard-step')).toContainText(
			'What is the decision?',
		)
	})

	test('a twig and an office template get the same wizard payload', async ({
		page,
	}) => {
		// @e2e openspec/specs/guided-document-wizard/spec.md#same-wizard-payload-for-both-template-types
		const bodies: object[] = []
		for (const templateType of ['twig', 'office']) {
			await page.unrouteAll({ behavior: 'ignoreErrors' })
			await serveWizard(page, { ...WIZARD, templateType })
			const sent = await captureGenerate(page)
			await open(page, WizardRunner)
			await page.getByTestId('wizard-choice-toegewezen').click()
			await page.getByTestId('wizard-next').click()
			await page
				.getByTestId('wizard-answer-date')
				.locator('input')
				.fill('2026-11-01')
			await page.getByTestId('wizard-next').click()
			await page.getByTestId('wizard-generate').click()
			await expect.poll(() => sent.length).toBe(1)
			bodies.push(sent[0].postDataJSON())
		}

		expect(bodies[1]).toEqual(bodies[0])
	})
})

test.describe('guided document wizard: authoring', () => {
	test('a template editor saves a wizard and sees the warning for an unknown data path', async ({
		page,
	}) => {
		// @e2e openspec/specs/guided-document-wizard/spec.md#author-saves-a-valid-wizard-for-a-template
		// @e2e openspec/specs/guided-document-wizard/spec.md#unknown-mapsto-path-warns-but-saves
		await page.route('**/apps/filinq/api/templates/tmpl-1', (route) =>
			route.fulfill({
				json: {
					id: 'tmpl-1',
					name: 'Parking permit decision',
					content: '<p>{{ besluit.uitkomst }}</p>',
				},
			}),
		)
		await page.route('**/apps/filinq/api/templates/tmpl-1/lock', (route) =>
			route.fulfill({ json: {} }),
		)
		await serveWizard(page, null)
		let saved: Record<string, unknown> | null = null
		await page.route('**/apps/filinq/api/wizards', (route) => {
			saved = route.request().postDataJSON()
			return route.fulfill({
				json: {
					wizard: { ...saved, uuid: 'wizard-9', active: true },
					warnings: [
						'Question "naam" maps to aanvrager.naam, which the template\'s schema does not have.',
					],
				},
			})
		})
		await open(page, 'templates/tmpl-1')

		await page.getByTestId('template-tab-wizard').click()
		await page
			.getByTestId('wizard-name')
			.locator('input')
			.fill('Parking permit decision')
		await page.getByTestId('wizard-add-question').click()
		await page
			.getByTestId('wizard-question-label')
			.locator('input')
			.fill('Name of the applicant')
		await page.getByTestId('wizard-question-key').locator('input').fill('naam')
		await page
			.getByTestId('wizard-question-maps-to')
			.locator('input')
			.fill('aanvrager.naam')
		await page.getByTestId('wizard-question-save').click()
		await page.getByTestId('wizard-save').click()

		await expect.poll(() => saved?.templateId).toBe('tmpl-1')
		expect((saved?.questions as Array<{ key: string }>)[0].key).toBe('naam')
		await expect(page.getByText('maps to aanvrager.naam')).toBeVisible()
		await expect(page.getByTestId('template-generate-with-wizard')).toBeVisible()
	})
})
