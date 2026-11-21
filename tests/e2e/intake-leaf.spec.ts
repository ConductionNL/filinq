/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * document-intake-inbox REQ-DII-05: the `filinq-document-intake` leaf on
 * another app's record lists the documents waiting in the intake inbox and
 * files one on that record, so the document's `assignedTo` names the host.
 *
 * WHERE THE WIDGET IS MOUNTED. In the scenario dossiq's case page places the
 * leaf; this instance need not have dossiq. As template-leaf.spec.ts does, the
 * test takes the leaf from the integration registry the filinq page installs
 * (`window.OCA.OpenRegister.integrations`) and mounts it the way a host does,
 * through the descriptor's own `mount(el, { register, schema, objectId })`,
 * on a filinq dossier as the host record. Everything after that is the shipped
 * widget talking to filinq's own routes.
 *
 * WHAT IT SEEDS ITSELF. A host dossier through POST /api/dossiers and one
 * waiting intake document through OpenRegister's object API (a document
 * reaches the inbox through a background feeder, never through a screen),
 * whose `sourceRef` is the dossier id so the widget marks it "Names this
 * record". Both are deleted when the test ends.
 *
 * EXCLUDED IN THE SPEC: the descriptor and JS registration parity, which
 * gate-24 checks mechanically.
 */

import { expect, test } from '@playwright/test'
import { go } from './spec-coverage/_helpers.ts'
import {
	API,
	harvestToken,
	jsonHeaders,
	TEST_PREFIX,
} from './workflows/_fixtures.ts'

/** The leaf under test. */
const LEAF_ID = 'filinq-document-intake'

/** OpenRegister's objects API for the filinq register. */
const OR = '/index.php/apps/openregister/api/objects/filinq'

/** The host's register and schema, as slugs. */
const HOST = { register: 'filinq', schema: 'dossier' }

test.describe('The intake leaf on a host record', () => {
	test.setTimeout(120_000)

	// @e2e openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md#dossiq-places-the-leaf-on-a-case
	test('the widget lists waiting documents and files one on the host record', async ({
		page,
	}) => {
		await page.goto('/index.php/apps/filinq/')
		const token = await harvestToken(page)
		expect(token, 'the suite needs a live request token').not.toBe('')

		const madeDossier = await page.request.post(`${API}/dossiers`, {
			headers: jsonHeaders(token),
			data: { name: `${TEST_PREFIX} intake leaf zaak` },
		})
		expect(
			madeDossier.status(),
			`POST /api/dossiers -> ${await madeDossier.text()}`,
		).toBe(201)
		const dossier = await madeDossier.json()
		const hostId = String(
			dossier.id ?? dossier.uuid ?? dossier['@self']?.id ?? '',
		)
		expect(hostId).not.toBe('')

		const subject = `${TEST_PREFIX}-leaf-brief`
		let documentId = ''
		try {
			const created = await page.request.post(`${OR}/intakeDocument`, {
				headers: jsonHeaders(token),
				data: {
					channel: 'mail',
					subject,
					sender: `${TEST_PREFIX}@voorbeeld.nl`,
					receivedAt: new Date().toISOString(),
					sourceRef: hostId,
					status: 'received',
				},
			})
			expect(created.status(), 'seeding the waiting document').toBeLessThan(
				300,
			)
			const body = await created.json()
			documentId = String(body?.['@self']?.id || body?.id || body?.uuid || '')
			expect(documentId).not.toBe('')

			await go(page, '')
			const mounted = await page.evaluate(
				({ leafId, props }) => {
					const registry = (window as any).OCA?.OpenRegister?.integrations
					const entry =
						registry?.get?.(leafId)
						?? registry?._queue?.find(
							(queued: any) => queued.id === leafId,
						)
					if (!entry || typeof entry.mount !== 'function') {
						return false
					}
					const el = document.createElement('div')
					el.id = 'e2e-intake-leaf'
					el.style.cssText =
						'position:fixed;top:60px;right:16px;width:360px;z-index:100000;background:var(--color-main-background);padding:8px;'
					document.body.appendChild(el)
					entry.mount(el, props)
					return true
				},
				{ leafId: LEAF_ID, props: { ...HOST, objectId: hostId } },
			)
			expect(
				mounted,
				`the ${LEAF_ID} leaf is registered with a mount hand-off`,
			).toBe(true)

			const widget = page
				.locator('#e2e-intake-leaf')
				.getByTestId('filinq-document-intake-leaf')
			const item = widget.locator('li').filter({ hasText: subject })
			await expect(item).toBeVisible({ timeout: 30_000 })
			await expect(item).toContainText('Names this record')
			await expect(
				widget.locator('li').first(),
				'a document naming the record comes first',
			).toContainText(subject)

			const filed = page.waitForResponse(
				(res) =>
					res.request().method() === 'POST'
					&& res
						.url()
						.includes(`/api/intake/documents/${documentId}/assign`),
			)
			await item.getByRole('button', { name: 'File on this record' }).click()
			const response = await filed
			expect(response.status(), await response.text()).toBe(200)
			expect(response.request().postDataJSON()).toEqual({
				...HOST,
				id: hostId,
			})
			await expect(item).toHaveCount(0)

			const stored = await page.request.get(
				`${OR}/intakeDocument/${documentId}`,
				{ headers: jsonHeaders(token) },
			)
			expect(stored.status()).toBe(200)
			const record = await stored.json()
			const fields = (record.object ?? record) as Record<string, unknown>
			expect(fields.status).toBe('assigned')
			expect(fields.assignedTo).toEqual({ ...HOST, id: hostId })
		} finally {
			if (documentId !== '') {
				await page.request
					.delete(`${OR}/intakeDocument/${documentId}`, {
						headers: jsonHeaders(token),
					})
					.catch(() => undefined)
			}
			await page.request
				.delete(`${OR}/${HOST.schema}/${hostId}`, {
					headers: jsonHeaders(token),
				})
				.catch(() => undefined)
		}
	})
})
