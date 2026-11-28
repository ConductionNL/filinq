/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The case-system section of the admin settings: add a destination, retry a
 * failed delivery, and the two delivery counts on /reports/documents.
 *
 * The destination is added through the form, as an admin does it; the failed
 * delivery is seeded through OpenRegister because only a failing write-back
 * produces one. What the screen shows is cross-checked against the register,
 * and the retry route is also asked for a delivery that did not fail (409)
 * and without a session (refused), so the button cannot pass on its own.
 *
 * Base URL and instance come from the environment (tests/e2e/base-url.ts via
 * the config); everything this run writes is name-stamped and deleted after.
 *
 * Covers, from openspec/changes/generate-store-in-case-system:
 *   task 3.1 "adds a destination and retries a failed delivery"
 *   task 2.3 the retry answers 409 for a delivery in any other state
 *   task 3.2 the waiting and failed counts on /reports/documents
 *   REQ-GSC-002 a generation stored with a case-system destination is queued
 *   REQ-GSC-003 a written-back delivery shows its badge on /my-documents
 *
 * @spec openspec/changes/archive/2026-11-25-generate-store-in-case-system/specs
 */
import type { Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { randomUUID } from 'node:crypto'
import { APP, dismissOverlays, waitForNcContentReady } from './spec-coverage/_helpers.ts'
import { createDavFile, createDavFolder, deleteDavPath, harvestToken } from './workflows/_fixtures.ts'

/** Distinctive enough that finding it proves the read, not a coincidence. */
const STAMP = `e2e-case-system-${Date.now()}`

const OBJECTS = '/index.php/apps/openregister/api/objects/filinq'

/**
 * Call a route from inside the page, carrying the session and its token.
 *
 * @param page   The page to run in.
 * @param method The HTTP verb.
 * @param url    The url.
 * @param body   The request body, for a write.
 *
 * @return The status and the parsed body.
 */
async function api(
	page: Page,
	method: string,
	url: string,
	body?: Record<string, unknown>,
): Promise<{ status: number; body: Record<string, unknown> }> {
	return await page.evaluate(
		async ({ method, url, body }) => {
			const res = await fetch(url, {
				method,
				headers: {
					'Content-Type': 'application/json',
					requesttoken:
						document
							.querySelector('head[data-requesttoken]')
							?.getAttribute('data-requesttoken') ?? '',
				},
				body: body === undefined ? undefined : JSON.stringify(body),
			})
			const text = await res.text()
			let parsed: Record<string, unknown>
			try {
				parsed = JSON.parse(text)
			} catch {
				parsed = { raw: text.slice(0, 300) }
			}
			return { status: res.status, body: parsed }
		},
		{ method, url, body },
	)
}

/**
 * Seed one object in the filinq register.
 *
 * @param page   The page.
 * @param schema The schema slug.
 * @param object The object.
 *
 * @return The new id.
 */
async function seed(page: Page, schema: string, object: Record<string, unknown>): Promise<string> {
	// OpenRegister starts a delivery in ready_for_writeback and refuses any
	// other state on create; a later state is reached by its transition.
	const target = object.deliveryStatus
	const initial = schema === 'caseSystemDelivery' && target !== undefined && target !== 'ready_for_writeback'
		? { ...object, deliveryStatus: 'ready_for_writeback' }
		: object
	const res = await api(page, 'POST', `${OBJECTS}/${schema}`, initial)
	expect(res.status, `could not seed a ${schema} — ${JSON.stringify(res.body).slice(0, 300)}`).toBeLessThan(400)
	const created = res.body as Record<string, never>
	const id = (created?.id ?? created?.['@self']?.id) as string
	if (initial !== object) {
		const moved = await api(page, 'PUT', `${OBJECTS}/${schema}/${id}`, object)
		expect(moved.status, `could not move the ${schema} to ${String(target)} — ${JSON.stringify(moved.body).slice(0, 300)}`).toBeLessThan(400)
	}
	return id
}

/**
 * Open the admin settings and wait for the case-system section.
 *
 * @param page The page.
 */
async function openCaseSystem(page: Page): Promise<void> {
	await page.goto('/index.php/settings/admin/filinq', { waitUntil: 'domcontentloaded' })
	await waitForNcContentReady(page)
	await dismissOverlays(page)
	await expect(page.getByRole('heading', { name: 'Case system', exact: true })).toBeVisible({ timeout: 20_000 })
}

test.describe('case-system delivery in the admin settings', () => {
	test.describe.configure({ mode: 'serial' })
	test.setTimeout(180_000)

	const created: Array<{ schema: string; id: string }> = []

	test.afterAll(async ({ browser }) => {
		if (created.length === 0) return
		const page = await browser.newPage()
		await page.goto(`${APP}/`, { waitUntil: 'domcontentloaded' })
		for (const { schema, id } of created.reverse()) {
			await api(page, 'DELETE', `${OBJECTS}/${schema}/${id}`)
		}
		await page.close()
	})

	// @e2e case-system-delivery::an-admin-sends-hr-documents-to-the-dms
	test('an admin adds a destination through the form', async ({ page }) => {
		await openCaseSystem(page)

		const form = page.locator('fieldset.case-system__form')
		await form.getByLabel('Template namespace').fill(STAMP)
		await form.getByLabel('Bridge source').fill(`source-${STAMP}`)
		await form.getByLabel('Document type in the case system').fill(`https://zaken.example/catalogi/${STAMP}`)
		await form.getByLabel('Source organisation (RSIN)').fill('002220647')
		// Inactive, so this run never competes with a real active destination.
		const active = form.getByRole('switch', { name: 'Active' }).or(form.getByRole('checkbox', { name: 'Active' }))
		if (await active.isChecked()) {
			await active.uncheck({ force: true })
		}
		await form.getByRole('button', { name: 'Save destination' }).click()

		const row = page.getByRole('row').filter({ hasText: `Namespace ${STAMP}` })
		await expect(row).toBeVisible({ timeout: 20_000 })
		await expect(row).toContainText('Inactive')

		const listed = await api(page, 'GET', '/index.php/apps/filinq/api/bridge/case-system')
		expect(listed.status).toBe(200)
		const destination = ((listed.body.destinations ?? []) as Array<Record<string, unknown>>)
			.find((d) => d.templateNamespace === STAMP)
		expect(destination, 'the saved destination is served back').toBeTruthy()
		created.push({ schema: 'caseSystemDestination', id: String(destination?.id) })
	})

	// @e2e case-system-delivery::an-admin-retries-a-failed-delivery
	test('a failed delivery is retried and waits again; any other state answers 409', async ({ page, playwright, baseURL }) => {
		await openCaseSystem(page)
		const destinationId = created.find((c) => c.schema === 'caseSystemDestination')?.id ?? STAMP

		const failed = await seed(page, 'caseSystemDelivery', {
			generatedDocumentId: randomUUID(),
			destinationId,
			sourceId: `source-${STAMP}`,
			deliveryStatus: 'writeback_failed',
			errorMessage: `Case system unreachable ${STAMP}`,
			metadata: { bestandsnaam: `${STAMP}.pdf` },
		})
		created.push({ schema: 'caseSystemDelivery', id: failed })
		const waiting = await seed(page, 'caseSystemDelivery', {
			generatedDocumentId: randomUUID(),
			destinationId,
			sourceId: `source-${STAMP}`,
			deliveryStatus: 'ready_for_writeback',
			metadata: { bestandsnaam: `${STAMP}-waiting.pdf` },
		})
		created.push({ schema: 'caseSystemDelivery', id: waiting })

		// Without a session the route refuses, and the delivery stays failed.
		const anonymous = await playwright.request.newContext({
			baseURL,
			storageState: { cookies: [], origins: [] },
		})
		const refused = await anonymous.post(`/index.php/apps/filinq/api/bridge/deliveries/${failed}/retry`)
		expect(refused.status(), 'retry without a session').toBeGreaterThanOrEqual(400)
		expect(refused.status()).not.toBe(404)
		await anonymous.dispose()

		// A delivery that did not fail cannot be retried.
		const conflict = await api(page, 'POST', `/index.php/apps/filinq/api/bridge/deliveries/${waiting}/retry`)
		expect(conflict.status).toBe(409)

		await page.reload()
		await openCaseSystem(page)
		const item = page.locator('.case-system__delivery').filter({ hasText: `${STAMP}.pdf` })
		await expect(item).toContainText('Failed', { timeout: 20_000 })
		await expect(item).toContainText(`Case system unreachable ${STAMP}`)
		await item.getByRole('button', { name: 'Retry' }).click()

		await expect(item).toContainText('Waiting', { timeout: 20_000 })
		await expect(item.getByRole('button', { name: 'Retry' })).toHaveCount(0)
		await expect(item).not.toContainText(`Case system unreachable ${STAMP}`)

		const stored = await api(page, 'GET', `${OBJECTS}/caseSystemDelivery/${failed}`)
		expect(stored.body.deliveryStatus).toBe('ready_for_writeback')
		expect(stored.body.errorMessage ?? '').toBe('')
	})

	// @e2e case-system-delivery::an-hr-contract-is-queued-for-the-dms
	test('a document stored with a case-system destination is queued for the case system', async ({ page }) => {
		await page.goto(`${APP}/`, { waitUntil: 'domcontentloaded' })

		// The request names its destination, so no active destination of this
		// instance is touched (REQ-GSC-002 allows either).
		const destination = {
			sourceId: `source-${STAMP}`,
			informatieobjecttype: `https://zaken.example/catalogi/${STAMP}`,
			vertrouwelijkheidaanduiding: 'vertrouwelijk',
			bronorganisatie: '002220647',
		}
		const template = await api(page, 'POST', '/index.php/apps/filinq/api/templates', {
			name: `${STAMP}-contract`,
			content: 'Employment contract for {{name}}.',
			namespace: 'filinq',
		})
		expect(template.status, JSON.stringify(template.body).slice(0, 300)).toBe(200)
		const templateId = String(template.body.id)
		created.push({ schema: 'template', id: templateId })
		// A new template is a draft; users generate only from a published one.
		const published = await api(page, 'POST', `/index.php/apps/filinq/api/templates/${templateId}/publish`)
		expect(published.status, JSON.stringify(published.body).slice(0, 300)).toBe(200)

		const generated = await page.evaluate(
			async ({ templateId, destination, stamp }) => {
				const res = await fetch('/index.php/apps/filinq/api/documents/generate', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						requesttoken:
							document.querySelector('head[data-requesttoken]')?.getAttribute('data-requesttoken') ?? '',
					},
					body: JSON.stringify({
						templateId,
						filename: `${stamp}-contract`,
						// dataRefs lists OpenRegister references; literal values go as adHocData.
						dataRefs: [],
						options: { format: 'pdf', adHocData: { name: 'A. Employee' }, output: { mode: 'both', caseSystem: destination } },
					}),
				})
				return { status: res.status, fileId: res.headers.get('X-Docudesk-File-Id') ?? '' }
			},
			{ templateId, destination, stamp: STAMP },
		)
		expect(generated.status).toBe(200)
		expect(Number(generated.fileId), 'mode both stores the document in Files').toBeGreaterThan(0)

		const found = await api(page, 'GET', `${OBJECTS}/caseSystemDelivery?fileId=${generated.fileId}&_limit=5`)
		expect(found.status).toBeLessThan(400)
		const deliveries = (found.body.results ?? []) as Array<Record<string, unknown>>
		expect(deliveries, 'one delivery for the stored file').toHaveLength(1)
		const delivery = deliveries[0]
		created.push({ schema: 'caseSystemDelivery', id: String(delivery.id ?? (delivery['@self'] as Record<string, unknown>)?.id) })
		expect(delivery.deliveryStatus).toBe('ready_for_writeback')
		const metadata = (delivery.metadata ?? {}) as Record<string, unknown>
		expect(metadata.informatieobjecttype).toBe(destination.informatieobjecttype)
		expect(metadata.vertrouwelijkheidaanduiding).toBe('vertrouwelijk')
	})

	// @e2e case-system-delivery::a-delivered-document-shows-where-it-went
	test('a written-back document shows "In the case system" on My documents', async ({ page }) => {
		await page.goto(`${APP}/`, { waitUntil: 'domcontentloaded' })
		const name = `${STAMP}-delivered.txt`
		const token = await harvestToken(page)
		expect([201, 405]).toContain(await createDavFolder(page.request, token, 'DocuDesk'))
		const stored = await createDavFile(page.request, token, `DocuDesk/${name}`, 'Delivered to the case system.')
		expect(stored.status).toBeLessThan(300)
		const fileId = Number(stored.fileId)
		expect(fileId, 'the stored file has an id').toBeGreaterThan(0)

		// Only integriq's write-back sets written_back, so the delivery is seeded.
		const delivered = await seed(page, 'caseSystemDelivery', {
			generatedDocumentId: randomUUID(),
			destinationId: randomUUID(),
			sourceId: `source-${STAMP}`,
			fileId,
			deliveryStatus: 'written_back',
			externalUrl: `https://zaken.example/documenten/${STAMP}`,
			metadata: { bestandsnaam: name },
		})
		created.push({ schema: 'caseSystemDelivery', id: delivered })

		try {
			await page.goto(`${APP}/my-documents`, { waitUntil: 'domcontentloaded' })
			await waitForNcContentReady(page)
			await dismissOverlays(page)
			const row = page.locator('tr').filter({ hasText: `${STAMP}-delivered` }).first()
			await expect(row).toBeVisible({ timeout: 30_000 })
			await expect(row.getByTestId('my-documents-case-system-badge')).toHaveText('In the case system')
		} finally {
			await deleteDavPath(page.request, token, `DocuDesk/${name}`)
		}
	})

	test('the documents report counts the waiting and failed deliveries', async ({ page }) => {
		await page.goto(`${APP}/reports/documents`, { waitUntil: 'domcontentloaded' })
		await waitForNcContentReady(page)
		await dismissOverlays(page)

		for (const status of ['ready_for_writeback', 'writeback_failed']) {
			const res = await api(page, 'GET', `${OBJECTS}/caseSystemDelivery?deliveryStatus=${status}&_limit=1`)
			expect(res.status).toBeLessThan(400)
			const total = Number(res.body.total ?? (res.body.results as unknown[] | undefined)?.length ?? 0)
			const title = status === 'ready_for_writeback'
				? 'Waiting for the case system'
				: 'Failed to reach the case system'
			// Each report widget is a region named by its title.
			const widget = page.getByRole('region', { name: title })
			await expect(widget, `${title} widget`).toBeVisible({ timeout: 20_000 })
			await expect(widget).toContainText(String(total), { timeout: 20_000 })
		}
	})
})
