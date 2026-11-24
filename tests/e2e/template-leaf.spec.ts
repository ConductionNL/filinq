/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * template-library-leaf, task 3.2: the `filinq-generate-document` widget
 * lists the templates scoped to its host object and generates one on it. The
 * result is a filinq document whose `sourceObject` names the host, stored in
 * the host's folder when the host has one.
 *
 * WHERE THE WIDGET IS MOUNTED. The leaf is placed by a sibling app (dossiq's
 * case page), which this instance need not have. The test therefore takes the
 * leaf from the integration registry the filinq page installs
 * (`window.OCA.OpenRegister.integrations`) and mounts it the way a host does,
 * through the descriptor's own `mount(el, { register, schema, objectId })`.
 * Everything after that is the shipped widget talking to filinq's own API.
 *
 * WHAT IT SEEDS ITSELF. A host dossier through `POST /api/dossiers`, one
 * published template scoped to `filinq/dossier` and one scoped to
 * `filinq/contract`, each with a run stamp. The generated file, both templates
 * and the dossier are deleted when the test ends. It reads no env-var fixture.
 *
 * EXCLUDED IN THE SPEC (`@e2e exclude`): the scope filter in
 * `listForObject()`, the data-provider read, and the gate-24 parity of the two
 * halves; PHPUnit and gate 24 cover them. The API half of the scope filter is
 * asserted here on the way, through `GET /api/templates/for-object`.
 *
 * Not run in the change that wrote it (decision 139): the live pass runs it.
 */

// @e2e openspec/changes/template-library-leaf/specs/template-management/spec.md#a-clerk-generates-a-letter-on-a-case

import type { APIRequestContext } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { go } from './spec-coverage/_helpers.ts'

/** The OCS header every call carries; it also satisfies the CSRF check. */
const OCS = { 'OCS-APIRequest': 'true' }

/** Filinq's own API. */
const API = '/index.php/apps/filinq/api'

/** OpenRegister's objects API for the filinq register. */
const OR = '/index.php/apps/openregister/api/objects/filinq'

/** The signed-in user. */
const USER = process.env.NC_ADMIN_USER ?? 'admin'

/** The leaf under test. */
const LEAF_ID = 'filinq-generate-document'

/** The host's register and schema, as slugs. */
const HOST = { register: 'filinq', schema: 'dossier' }

/** One run stamp for every name this file makes. */
const STAMP = `e2eleaf-${Date.now()}`

/**
 * Create a template, scoped to one register and schema, and publish it.
 *
 * @param request The authenticated request context.
 * @param name The template name.
 * @param schema The schema slug in the filinq register it is scoped to.
 * @return The template id.
 */
async function scopedTemplate(request: APIRequestContext, name: string, schema: string): Promise<string> {
	const created = await request.post(`${API}/templates`, {
		headers: OCS,
		data: {
			name,
			namespace: 'filinq',
			description: `Brief op een ${schema}`,
			content: '<h1>Brief</h1><p>Betreft: {{ dossier.name|default(\'uw dossier\') }}</p>',
			scope: [{ register: HOST.register, schema }],
		},
	})
	expect(created.ok(), `POST /api/templates -> ${created.status()} ${await created.text()}`).toBeTruthy()
	const id = String((await created.json()).id ?? '')
	expect(id).not.toBe('')

	const published = await request.post(`${API}/templates/${id}/publish`, { headers: OCS })
	expect(published.ok(), `publish ${id} -> ${published.status()}`).toBeTruthy()

	return id
}

/**
 * The file id of a folder in the signed-in user's files.
 *
 * @param request The authenticated request context.
 * @param path The folder path, relative to the user's files.
 * @return The file id.
 */
async function folderId(request: APIRequestContext, path: string): Promise<number> {
	const res = await request.fetch(`/remote.php/dav/files/${USER}/${encodeURI(path)}`, {
		method: 'PROPFIND',
		headers: { Depth: '0', 'Content-Type': 'application/xml' },
		data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns"><d:prop><oc:fileid/></d:prop></d:propfind>',
	})
	expect(res.status(), `PROPFIND ${path}`).toBe(207)

	return Number(/<oc:fileid>(\d+)<\/oc:fileid>/.exec(await res.text())?.[1] ?? 0)
}

/**
 * A stored file's path relative to the user's files, from its absolute path.
 *
 * @param absolute The path the generation answers, such as /admin/files/A/b.pdf.
 * @return The relative path.
 */
function relativePath(absolute: string): string {
	return absolute.replace(new RegExp(`^/?${USER}/files/`), '')
}

test.describe('the generate-document leaf on a host object', () => {
	test.setTimeout(180_000)

	test('the widget lists the templates scoped to the host and generates one into a filinq document on it', async ({ page, request }) => {
		const madeDossier = await request.post(`${API}/dossiers`, { headers: OCS, data: { name: `${STAMP} zaak` } })
		expect(madeDossier.status(), `POST /api/dossiers -> ${await madeDossier.text()}`).toBe(201)
		const dossier = await madeDossier.json()
		const hostId = String(dossier.id ?? dossier.uuid ?? dossier['@self']?.id ?? '')
		expect(hostId).not.toBe('')

		const onDossier = `${STAMP} brief op de zaak`
		const onContract = `${STAMP} brief op een contract`
		const templateIds: string[] = []
		let storedPath = ''
		try {
			templateIds.push(await scopedTemplate(request, onDossier, HOST.schema))
			templateIds.push(await scopedTemplate(request, onContract, 'contract'))

			// The list the widget reads: the dossier template, not the contract one.
			const rows = await request.get(`${API}/templates/for-object?register=${HOST.register}&schema=${HOST.schema}&id=${hostId}`, { headers: OCS })
			expect(rows.status()).toBe(200)
			const results = ((await rows.json()).results ?? []) as Array<Record<string, unknown>>
			const row = results.find((entry) => entry.id === templateIds[0])
			expect(row, 'the scoped template applies to the dossier').toBeTruthy()
			expect(Object.keys(row!).sort()).toEqual(['description', 'format', 'id', 'name'])
			expect(results.some((entry) => entry.id === templateIds[1]), 'a contract template does not apply').toBe(false)

			// Mount the widget the way a host app does.
			await go(page, '')
			const mounted = await page.evaluate(
				({ leafId, props }) => {
					const registry = (window as any).OCA?.OpenRegister?.integrations
					const entry = registry?.get?.(leafId) ?? registry?._queue?.find((queued: any) => queued.id === leafId)
					if (!entry || typeof entry.mount !== 'function') {
						return false
					}
					const el = document.createElement('div')
					el.id = 'e2e-generate-leaf'
					el.style.cssText = 'position:fixed;top:60px;right:16px;width:360px;z-index:100000;background:var(--color-main-background);padding:8px;'
					document.body.appendChild(el)
					entry.mount(el, props)
					return true
				},
				{ leafId: LEAF_ID, props: { ...HOST, objectId: hostId } },
			)
			expect(mounted, `the ${LEAF_ID} leaf is registered with a mount hand-off`).toBe(true)

			const widget = page.locator('#e2e-generate-leaf .filinq-generate-leaf')
			const choice = widget.getByRole('radio', { name: new RegExp(onDossier) })
			await expect(choice).toBeVisible({ timeout: 30_000 })
			await expect(widget).not.toContainText(onContract)

			await choice.check()
			const generated = page.waitForResponse((res) => res.request().method() === 'POST' && res.url().includes('/api/documents/generate'))
			await widget.getByRole('button', { name: /^(Generate|Maken)$/ }).click()
			const response = await generated
			expect(response.status(), await response.text()).toBe(200)
			const sent = response.request().postDataJSON()
			expect(sent.options.sourceObject).toEqual({ ...HOST, id: hostId })
			const body = await response.json()
			expect(Number(body.fileId)).toBeGreaterThan(0)
			storedPath = relativePath(String(body.path ?? ''))

			await expect(widget.getByRole('status')).toHaveText(/Generated .+|.+ is gegenereerd/)

			// A filinq document whose sourceObject is the host.
			const logged = await request.get(`${OR}/generatedDocument?fileId=${body.fileId}&_limit=20`, { headers: OCS })
			expect(logged.ok()).toBeTruthy()
			const entry = (((await logged.json()).results ?? []) as any[]).find((doc) => Number(doc.fileId) === Number(body.fileId))
			expect(entry, 'the generation is logged').toBeTruthy()
			expect(entry.templateId).toBe(templateIds[0])
			expect(entry.sourceObject).toEqual({ ...HOST, id: hostId })

			// In the host's folder, when the host has one.
			const host = await request.get(`${OR}/${HOST.schema}/${hostId}`, { headers: OCS })
			expect(host.ok()).toBeTruthy()
			const hostFolder = Number((await host.json())['@self']?.folder)
			if (Number.isInteger(hostFolder) && hostFolder > 0) {
				const parent = storedPath.split('/').slice(0, -1).join('/')
				expect(await folderId(request, parent), 'the document is in the host\'s folder').toBe(hostFolder)
			} else {
				test.info().annotations.push({ type: 'note', description: 'the host has no folder, so the document stays in the default folder' })
			}
		} finally {
			if (storedPath !== '') {
				await request.delete(`/remote.php/dav/files/${USER}/${encodeURI(storedPath)}`).catch(() => undefined)
			}
			for (const id of templateIds) {
				await request.delete(`${API}/templates/${id}`, { headers: OCS }).catch(() => undefined)
			}
			await request.delete(`${OR}/${HOST.schema}/${hostId}`, { headers: OCS }).catch(() => undefined)
		}
	})
})
