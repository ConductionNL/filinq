/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * scan-intake-with-separator-sheets, task 5.2 (the pre-fill case): a scanned
 * document carries the case number of the separator sheet that cut it as its
 * `sourceRef`, and the assign dialog of the intake page pre-selects the case
 * with that number. Confirming assigns the document to it.
 *
 * The rest of the intake inbox (list, assign, reject) is covered by
 * tests/e2e/workflows/intake-inbox.spec.ts; this file holds only the
 * separator pre-fill.
 *
 * WHAT IT SEEDS ITSELF. A dossier whose name carries a case number unique to
 * the run, and a waiting `scan` intake document whose `sourceRef` is that
 * number, both through OpenRegister's object API (a document reaches the inbox
 * through a background feeder, never through a screen). Both are deleted when
 * the test ends. No env-var fixture is needed.
 *
 * HOW THE PRE-SELECT IS REACHED. `IntakeAssignDialog` loads the record list
 * only once a register and a schema are picked, then pre-selects the record
 * whose name contains the `sourceRef` (`preselectFromSourceRef`). So the test
 * picks the `filinq` register and the `dossier` schema by the titles
 * OpenRegister answers for them, and then asserts the record picker shows the
 * seeded dossier without the clerk touching it. The list is read with
 * `_limit: 100`; on an instance with more than 100 dossiers the seeded one may
 * fall outside it, which is a limit of the dialog, not of this test.
 *
 * Locale: the assertions accept the English and the Dutch strings.
 *
 * Not run in the change that wrote it (decision 139): the live pass runs it.
 */

// @e2e openspec/changes/archive/2026-11-25-scan-intake-with-separator-sheets/specs/scan-intake/spec.md#the-clerk-sees-the-case-the-separator-named

import type { APIRequestContext, Locator } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { go } from './spec-coverage/_helpers.ts'

/** OpenRegister's object API for filinq's register. */
const OBJECTS = '/index.php/apps/openregister/api/objects/filinq'

/** OpenRegister's registers, with their schemas, for the picker titles. */
const REGISTERS_API = '/index.php/apps/openregister/api/registers?_extend[]=schemas'

/** The OCS header every call carries. */
const OCS = { 'OCS-APIRequest': 'true' }

/** Unique per run: the case number the separator sheet named. */
const CASE_NUMBER = `2026-${String(Date.now()).slice(-7)}`

/**
 * Create one object in filinq's register.
 *
 * @param request The authenticated request context.
 * @param schema The schema slug.
 * @param data The object.
 * @return The new object's id.
 */
async function createObject(request: APIRequestContext, schema: string, data: Record<string, unknown>): Promise<string> {
	const res = await request.post(`${OBJECTS}/${schema}`, { headers: OCS, data })
	expect(res.status(), `create ${schema} -> ${res.status()}`).toBeLessThan(300)
	const body = await res.json()
	const id = String(body?.['@self']?.id ?? body?.id ?? body?.uuid ?? '')
	expect(id, `create ${schema} answers an id`).not.toBe('')

	return id
}

/**
 * Read one object of filinq's register.
 *
 * @param request The authenticated request context.
 * @param schema The schema slug.
 * @param id The object id.
 * @return The object's fields.
 */
async function readObject(request: APIRequestContext, schema: string, id: string): Promise<Record<string, any>> {
	const res = await request.get(`${OBJECTS}/${schema}/${id}`, { headers: OCS })
	expect(res.status(), `read ${schema}/${id}`).toBe(200)
	const body = await res.json()

	return (body.object ?? body) as Record<string, any>
}

/**
 * The titles the register/schema picker shows for filinq's register and its
 * dossier schema (`CnRegisterSchemaSelect` labels options by title).
 *
 * @param request The authenticated request context.
 * @return The register title and the dossier schema title.
 */
async function pickerTitles(request: APIRequestContext): Promise<{ register: string; schema: string }> {
	const res = await request.get(REGISTERS_API, { headers: OCS })
	expect(res.ok(), `GET registers -> ${res.status()}`).toBeTruthy()
	const body = await res.json()
	const registers = (body?.results ?? body ?? []) as Array<Record<string, any>>
	const register = registers.find((row) => row.slug === 'filinq')
	expect(register, 'the filinq register is listed').toBeTruthy()
	const schema = (register!.schemas ?? []).find((row: any) => row && row.slug === 'dossier')
	expect(schema, 'the dossier schema is in the filinq register').toBeTruthy()

	return {
		register: String(register!.title || register!.slug),
		schema: String(schema.title || schema.slug),
	}
}

/**
 * Pick an option in an NcSelect by typing its label and clicking it.
 *
 * @param select The NcSelect root.
 * @param label The option label.
 * @return Resolves once the option is chosen.
 */
async function pick(select: Locator, label: string): Promise<void> {
	await select.locator('.vs__dropdown-toggle').click()
	// The opened select has focus on its search box: type into it. Its
	// options are matched by text, as NcSelect may split a long label over
	// two spans and so its accessible name (seen on a live run, 25 Nov 2026).
	await select.page().keyboard.type(label)
	await select.page().getByRole('option').filter({ hasText: label }).first().click()
	await expect(select).toContainText(label)
}

test.describe('the intake inbox pre-fills the case the separator named', () => {
	test.setTimeout(180_000)

	const created: Array<{ schema: string; id: string }> = []

	test.afterEach(async ({ request }) => {
		for (const { schema, id } of created.reverse()) {
			await request.delete(`${OBJECTS}/${schema}/${id}`, { headers: OCS })
		}
		created.length = 0
	})

	test('the assign dialog shows the case selected, and confirming assigns the document to it', async ({ page, request }) => {
		const dossierName = `Zaak ${CASE_NUMBER}`
		const dossierId = await createObject(request, 'dossier', { name: dossierName })
		created.push({ schema: 'dossier', id: dossierId })

		const subject = `Scan ${CASE_NUMBER}`
		const documentId = await createObject(request, 'intakeDocument', {
			channel: 'scan',
			subject,
			sender: 'scanner-2e-verdieping',
			receivedAt: new Date().toISOString(),
			sourceRef: CASE_NUMBER,
			status: 'received',
		})
		created.push({ schema: 'intakeDocument', id: documentId })

		const titles = await pickerTitles(request)

		await go(page, 'intake')
		const row = page.getByRole('row', { name: new RegExp(subject) })
		await expect(row).toBeVisible({ timeout: 15_000 })
		await row.getByRole('button').last().click()
		await page.getByRole('menuitem', { name: /^(Assign|Indelen)$/ })
			.or(page.getByRole('button', { name: /^(Assign|Indelen)$/ }))
			.first()
			.click()

		const dialog = page.getByTestId('intake-assign-dialog')
		await expect(dialog).toBeVisible()
		const pickers = dialog.locator('.cn-register-schema-select .v-select')
		await pick(pickers.nth(0), titles.register)
		await pick(pickers.nth(1), titles.schema)

		// Nothing typed into the record picker: the separator's number selected it.
		const record = dialog.getByTestId('intake-assign-record')
		await expect(record.locator('.vs__selected')).toContainText(dossierName, { timeout: 15_000 })

		const assigned = page.waitForResponse(
			(res) => res.url().includes(`/api/intake/documents/${documentId}/assign`) && res.request().method() === 'POST',
		)
		await page.getByTestId('cn-dialog-actions-submit').click()
		const answer = await assigned
		expect(answer.status()).toBe(200)
		expect(answer.request().postDataJSON()).toEqual({ register: 'filinq', schema: 'dossier', id: dossierId })

		// Stored on the document, and gone from the waiting list.
		const stored = await readObject(request, 'intakeDocument', documentId)
		expect(stored.status).toBe('assigned')
		expect(stored.assignedTo?.id ?? stored.assignedTo).toBe(dossierId)
		await expect(row).toHaveCount(0, { timeout: 15_000 })
	})
})
