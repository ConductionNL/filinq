/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * extraction-model-filled-fields, tasks 4.1 and 4.2: the suggestion dialog on
 * the intake worklist and the model filling section of the admin settings.
 *
 * WITH A STUBBED PROVIDER. A text model's answer is not reproducible in a
 * browser run, and a test instance has no TextToText provider, so the calls
 * that only a provider can answer are stubbed in the browser, in the routes'
 * own shapes: the status (`enabled`, `provider`, `available`), the suggestion
 * (`POST api/extraction/fields`) and the decision. The worklist row and the
 * profile list are stubbed with them, so nothing is written to the instance.
 * What the server does with a decision (writes only accepted and changed
 * values, as the session user) is pinned by FieldSuggestionDecisionsTest and
 * the Newman requests; this file proves the screens send exactly the clerk's
 * decisions and show what the spec says.
 *
 * ONE TEST IS NOT STUBBED: "a server without a model" reads the instance's real
 * status, and skips when the instance does have a provider.
 *
 * Locale: the assertions accept the English and the Dutch strings.
 *
 * Not run in the change that wrote it (decision 139): the live pass runs it.
 */

import type { Page, Route } from '@playwright/test'

import { expect, test } from '@playwright/test'

const APP = '/index.php/apps/filinq'
const SETTINGS = '/index.php/settings/admin/filinq'

/** The record the intake document is on. */
const RECORD = { register: 'filinq', schema: 'subsidieaanvraag', id: 'e2e-subsidie-0001' }

/** The stubbed provider's name. */
const PROVIDER = 'E2E stub model'

/** One waiting document assigned to the subsidy record. */
const DOCUMENT = {
	uuid: 'e2e-intake-0001',
	channel: 'scan',
	sender: 'e2e@voorbeeld.nl',
	subject: 'E2E subsidieaanvraag buurtfeest',
	receivedAt: '2026-11-02T09:00:00+00:00',
	readingState: 'read',
	status: 'assigned',
	file: 4711,
	assignedTo: RECORD,
}

/** The model's suggestion; the amount is not in the text. */
const SUGGESTION = {
	uuid: 'e2e-suggestion-0001',
	status: 'suggested',
	provider: PROVIDER,
	droppedFields: 0,
	currentValues: { startDate: '2026-12-01' },
	suggestions: {
		applicant: { value: 'Stichting Buurtfeest Oost', foundInText: true, passage: 'Aanvrager: Stichting Buurtfeest Oost' },
		amount: { value: 2500, foundInText: false },
		startDate: { value: '2027-01-15', foundInText: true, passage: 'Startdatum: 15 januari 2027' },
	},
}

/** The strings the spec names, in English and Dutch. */
const NOT_FOUND = /Not found in the document text|Niet gevonden in de tekst van het document/
const FILL = /Fill fields from document|Velden vullen uit document/
const NO_MODEL = /No text model is available on this server\.|Er is geen tekstmodel beschikbaar op deze server\./

/**
 * Stub what only a provider can answer, and record what the page sends.
 *
 * @param page The page.
 * @return The suggestion requests, the decisions and the saved profiles.
 */
async function stubProvider(page: Page): Promise<{ asked: any[]; decided: any[]; saved: any[] }> {
	const asked: any[] = []
	const decided: any[] = []
	const saved: any[] = []
	const profiles: any[] = []

	await page.route('**/api/extraction/fields/status', async (route: Route) => {
		await route.fulfill({ json: { enabled: true, provider: PROVIDER, available: true } })
	})
	await page.route('**/api/extraction/field-profiles**', async (route: Route) => {
		if (route.request().method() === 'GET') {
			await route.fulfill({ json: { results: profiles } })
			return
		}
		const body = route.request().postDataJSON()
		saved.push(body)
		const stored = { uuid: `e2e-profile-${saved.length}`, ...body }
		profiles.push(stored)
		await route.fulfill({ status: 201, json: stored })
	})
	await page.route('**/api/extraction/fields', async (route: Route) => {
		asked.push(route.request().postDataJSON())
		await route.fulfill({ status: 201, json: SUGGESTION })
	})
	await page.route('**/api/extraction/fields/*/decide', async (route: Route) => {
		decided.push(route.request().postDataJSON())
		await route.fulfill({ json: { ...SUGGESTION, status: 'decided' } })
	})
	await page.route('**/api/intake/documents', async (route: Route) => {
		if (route.request().method() !== 'GET') {
			await route.continue()
			return
		}
		await route.fulfill({ json: { results: [DOCUMENT], notificationReach: null } })
	})

	return { asked, decided, saved }
}

/**
 * Open the intake worklist and the row of the stubbed document.
 *
 * @param page The page.
 * @return The row.
 */
async function openIntakeRow(page: Page) {
	await page.goto(`${APP}/#/intake`)
	await expect(page.getByTestId('intake-index')).toBeVisible({ timeout: 30_000 })
	const row = page.getByRole('row', { name: new RegExp(DOCUMENT.subject) })
	await expect(row).toBeVisible({ timeout: 15_000 })

	return row
}

/**
 * Open filinq's admin settings at the model filling section.
 *
 * @param page The page.
 * @return The section.
 */
async function openModelFilling(page: Page) {
	await page.goto(SETTINGS, { waitUntil: 'domcontentloaded' })
	const section = page.getByTestId('settings-model-filling')
	await expect(section).toBeVisible({ timeout: 30_000 })

	return section
}

/**
 * Choose one decision for a field in the dialog.
 *
 * @param page     The page.
 * @param field    The field name.
 * @param decision The label of the decision.
 */
async function decide(page: Page, field: string, decision: RegExp): Promise<void> {
	await page
		.getByTestId(`field-suggestion-${field}`)
		.getByText(decision)
		.click()
}

test.describe('fields filled from a document by a model', () => {
	test.setTimeout(120_000)

	// @e2e openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md#a-server-without-a-model
	test('a server without a model: the switch cannot be turned on and the action is not offered', async ({
		page,
	}) => {
		const res = await page.request.get(`${APP}/api/extraction/fields/status`, {
			headers: { 'OCS-APIRequest': 'true' },
		})
		expect(res.ok(), `GET status -> ${res.status()}`).toBeTruthy()
		const status = await res.json()
		test.skip(Boolean(status.provider), 'this instance has a TextToText provider')

		const section = await openModelFilling(page)
		await expect(section.getByTestId('model-filling-no-model')).toContainText(NO_MODEL)
		await expect(section.getByTestId('model-filling-switch').locator('input')).toBeDisabled()

		// The worklist offers no fill action without a model, on any row.
		await page.goto(`${APP}/#/intake`)
		await expect(page.getByTestId('intake-index')).toBeVisible({ timeout: 30_000 })
		await expect(page.getByTestId('intake-fill-fields')).toHaveCount(0)
	})

	// @e2e openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md#an-admin-declares-the-fields-of-a-subsidy-application
	test('an admin declares the fields of a subsidy application', async ({ page }) => {
		const { saved } = await stubProvider(page)
		const section = await openModelFilling(page)
		await expect(section.getByTestId('model-filling-provider')).toContainText(PROVIDER)

		const form = section.getByTestId('model-filling-form')
		await form.getByLabel(/^(Register)$/).fill(RECORD.register)
		await form.getByLabel(/^(Schema)$/).fill(RECORD.schema)
		await form.getByLabel(/^(Fields|Velden)$/).fill('applicant, amount, startDate')
		const active = form.locator('input[type="checkbox"]').first()
		if (!(await active.isChecked())) {
			await form.getByText(/^(Active|Actief)$/).click()
		}
		await form.getByRole('button', { name: /Save profile|Profiel opslaan/ }).click()

		await expect.poll(() => saved.length).toBe(1)
		expect(saved[0].schema).toBe(RECORD.schema)
		expect(saved[0].active).toBe(true)
		expect(saved[0].fields).toEqual(['applicant', 'amount', 'startDate'])

		const listed = section.locator('tr').filter({ hasText: RECORD.schema })
		await expect(listed).toBeVisible()
		await expect(listed).toContainText('applicant, amount, startDate')
		await expect(listed).toContainText(/Yes|Ja/)
	})

	// @e2e openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md#a-registrar-fills-a-record-from-an-intake-document
	// @e2e openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md#a-value-not-in-the-text-is-flagged
	test('a registrar fills a record from an intake document, deciding each field', async ({ page }) => {
		const { asked, decided } = await stubProvider(page)
		const row = await openIntakeRow(page)

		await row.getByRole('button').last().click()
		await page.getByRole('button', { name: FILL }).first().click()

		// The model is asked for this file on this record.
		await expect.poll(() => asked.length).toBe(1)
		expect(asked[0]).toMatchObject({
			fileId: DOCUMENT.file,
			register: RECORD.register,
			schema: RECORD.schema,
			objectId: RECORD.id,
		})

		const amount = page.getByTestId('field-suggestion-amount')
		await expect(amount).toBeVisible({ timeout: 15_000 })
		await expect(amount).toContainText(NOT_FOUND)
		await expect(page.getByTestId('field-suggestion-applicant')).toContainText('Aanvrager: Stichting Buurtfeest Oost')

		await decide(page, 'applicant', /^(Accept|Accepteren)$/)
		await decide(page, 'amount', /^(Accept|Accepteren)$/)
		await decide(page, 'startDate', /^(Reject|Afwijzen)$/)
		await page.getByTestId('cn-dialog-actions-submit').click()

		await expect.poll(() => decided.length).toBe(1)
		const decisions = decided[0].decisions
		expect(Object.keys(decisions).sort()).toEqual(['amount', 'applicant', 'startDate'])
		expect(decisions.applicant).toMatchObject({ decision: 'accept' })
		expect(decisions.amount).toMatchObject({ decision: 'accept' })
		expect(decisions.startDate).toMatchObject({ decision: 'reject' })
	})
})
