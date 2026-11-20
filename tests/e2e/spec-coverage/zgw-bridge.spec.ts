/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * zgw-document-bridge, task 4.2 (per-scenario checks): the bridge status
 * panel in the admin settings with one fresh and one failing source, its
 * empty state, the "Zaaksysteem: {vendor}" badge in My Documents, and a
 * failed write-back that is visible and retried. The one end-to-end path
 * (source, staged document, dossier, release) is
 * tests/e2e/workflows/zgw-bridge-workflow.spec.ts.
 *
 * WHAT IT SEEDS ITSELF. No OpenConnector and no case system are needed: the
 * bridge's data are ordinary OpenRegister objects in filinq's register, so
 * the spec writes `bridgeSource` and `externalDocument` objects through
 * OpenRegister's object API, the way OpenConnector's synchronisation writes
 * them, and uploads the staged files over WebDAV. Everything is name-stamped
 * and deleted afterwards. No env-var fixture is read.
 *
 * THE FAILED WRITE-BACK IS WALKED, NOT FORGED. The document goes through the
 * declared lifecycle one transition at a time: `in_processing` and
 * `processed` (with a derivative in `resultFileRef`) through OpenRegister,
 * `ready_for_writeback` through filinq's own release route, and
 * `writeback_failed` through OpenRegister again, standing in for
 * OpenConnector's push leg, which owns that transition.
 *
 * THERE IS NO SCREEN FOR AN EXTERNAL DOCUMENT'S STATUS. The spec says the
 * operator "views" a failed write-back and uses "a retry action"; filinq has
 * the retry route (`POST api/bridge/external-documents/{id}/retry`) but no
 * page lists external documents or offers the action. The test therefore
 * reads the status through OpenRegister and retries through the route.
 *
 * THE EMPTY STATE. A fresh install seeds one demo source, so an instance with
 * no source is rare. When `GET api/bridge/sources` answers none the panel is
 * asserted as it is; otherwise the browser's sources call is answered with an
 * empty list, which proves the panel's rendering of "no sources", not the
 * server's. The test says which in its annotation.
 *
 * Excluded in the spec (`@e2e exclude`) and not here: register import, the
 * OpenConnector sync, the no-client architecture rule, the lifecycle guard and
 * PUT semantics (PHPUnit), and the 25-hour stale clock (clock-injected
 * PHPUnit).
 *
 * Locale: the assertions accept the English and the Dutch strings.
 *
 * Not run in the change that wrote it (decision 139): the live pass runs it.
 */

// @e2e openspec/changes/zgw-document-bridge/specs/zgw-document-bridge/spec.md#failed-write-back-is-visible-and-retryable
// @e2e openspec/changes/zgw-document-bridge/specs/zgw-document-bridge/spec.md#admin-sees-per-source-health
// @e2e openspec/changes/zgw-document-bridge/specs/zgw-document-bridge/spec.md#empty-state-without-openconnector
// @e2e openspec/changes/zgw-document-bridge/specs/zgw-document-bridge/spec.md#badge-on-a-bridge-document

import type { APIRequestContext, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { go, waitForNcContentReady } from './_helpers.ts'

/** filinq's bridge routes. */
const SOURCES_API = '/index.php/apps/filinq/api/bridge/sources'
const releaseApi = (id: string): string => `/index.php/apps/filinq/api/bridge/external-documents/${id}/release`
const retryApi = (id: string): string => `/index.php/apps/filinq/api/bridge/external-documents/${id}/retry`

/** OpenRegister's object API for filinq's register. */
const OBJECTS = '/index.php/apps/openregister/api/objects/filinq'

/** The signed-in user the WebDAV seed writes for. */
const USER = process.env.NC_ADMIN_USER ?? 'admin'

/** The folder My Documents lists (`myDocumentsStore.currentPath`). */
const DOCS_FOLDER = 'DocuDesk'

/** The OCS header every call carries. */
const OCS = { 'OCS-APIRequest': 'true' }

/** Unique per run. */
const STAMP = `e2e-bridge-${Date.now()}`

/** What this run created, for the cleanup. */
const createdObjects: Array<{ schema: string; id: string }> = []
const createdPaths: string[] = []

/**
 * Create one object in filinq's register and remember it for the cleanup.
 *
 * @param request The authenticated request context.
 * @param schema The schema slug.
 * @param data The object.
 * @return The new object's id.
 */
async function createObject(request: APIRequestContext, schema: string, data: Record<string, unknown>): Promise<string> {
	const res = await request.post(`${OBJECTS}/${schema}`, { headers: OCS, data })
	expect(res.status(), `create ${schema} -> ${res.status()} ${await res.text()}`).toBeLessThan(300)
	const body = await res.json()
	const id = String(body?.['@self']?.id ?? body?.id ?? body?.uuid ?? '')
	expect(id, `create ${schema} answers an id`).not.toBe('')
	createdObjects.push({ schema, id })

	return id
}

/**
 * Read one object of filinq's register.
 *
 * @param request The authenticated request context.
 * @param schema The schema slug.
 * @param id The object id.
 * @return The object's own fields, without OpenRegister's metadata.
 */
async function readObject(request: APIRequestContext, schema: string, id: string): Promise<Record<string, any>> {
	const res = await request.get(`${OBJECTS}/${schema}/${id}`, { headers: OCS })
	expect(res.status(), `read ${schema}/${id}`).toBe(200)
	const body = await res.json()
	const fields: Record<string, any> = { ...(body.object ?? body) }
	for (const key of Object.keys(fields)) {
		if (key.startsWith('@') || key === 'id' || key === 'uuid') {
			delete fields[key]
		}
	}

	return fields
}

/**
 * Save an object with some fields changed, carrying every other field
 * forward (PUT semantics, as `BridgeService` saves).
 *
 * @param request The authenticated request context.
 * @param schema The schema slug.
 * @param id The object id.
 * @param changes The fields to change.
 * @return The saved object's own fields.
 */
async function updateObject(
	request: APIRequestContext,
	schema: string,
	id: string,
	changes: Record<string, unknown>,
): Promise<Record<string, any>> {
	// Unset fields come back as null; a hard-validated string property would
	// refuse a null, so they are left out rather than written back.
	const current = Object.fromEntries(
		Object.entries(await readObject(request, schema, id)).filter(([, value]) => value !== null),
	)
	const res = await request.put(`${OBJECTS}/${schema}/${id}`, { headers: OCS, data: { ...current, ...changes } })
	expect(res.ok(), `update ${schema}/${id} -> ${res.status()} ${await res.text()}`).toBeTruthy()

	return await readObject(request, schema, id)
}

/**
 * Upload a small file over WebDAV, remember it for the cleanup, and answer
 * its Nextcloud file id.
 *
 * @param request The authenticated request context.
 * @param path The file, relative to the user's root.
 * @param contents The contents.
 * @return The file id.
 */
async function uploadFile(request: APIRequestContext, path: string, contents: string): Promise<number> {
	const url = `/remote.php/dav/files/${USER}/${path}`
	const put = await request.put(url, { data: contents })
	expect([201, 204], `PUT ${path}`).toContain(put.status())
	createdPaths.push(path)

	const find = await request.fetch(url, {
		method: 'PROPFIND',
		headers: { Depth: '0', 'Content-Type': 'application/xml' },
		data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns"><d:prop><oc:fileid/></d:prop></d:propfind>',
	})
	expect(find.status()).toBe(207)
	const match = /<oc:fileid>(\d+)<\/oc:fileid>/.exec(await find.text())
	expect(match, 'PROPFIND answers the file id').not.toBeNull()

	return Number(match![1])
}

/**
 * Seed one case-system source the way an OpenConnector run leaves it.
 *
 * @param request The authenticated request context.
 * @param name The source name.
 * @param vendor The vendor.
 * @param telemetry The last sync fields.
 * @return The source id.
 */
async function seedSource(
	request: APIRequestContext,
	name: string,
	vendor: string,
	telemetry: { lastSyncAt?: string; lastSyncStatus: 'success' | 'error'; lastSyncError?: string },
): Promise<string> {
	return await createObject(request, 'bridgeSource', {
		name,
		vendor,
		sourceType: 'zgw-drc',
		syncIntervalMinutes: 60,
		active: true,
		lastSyncAt: telemetry.lastSyncAt ?? new Date().toISOString(),
		lastSyncStatus: telemetry.lastSyncStatus,
		...(telemetry.lastSyncError ? { lastSyncError: telemetry.lastSyncError } : {}),
	})
}

/**
 * Seed one staged external document for a staged file.
 *
 * @param request The authenticated request context.
 * @param sourceId The source it came from.
 * @param fileId The staged file.
 * @param filename The staged file's name.
 * @return The external document id.
 */
async function seedStaged(request: APIRequestContext, sourceId: string, fileId: number, filename: string): Promise<string> {
	return await createObject(request, 'externalDocument', {
		sourceId,
		externalId: `https://drc.example/documenten/api/v1/enkelvoudiginformatieobjecten/${STAMP}-${fileId}`,
		zaakIdentificatie: 'ZAAK-2026-0042',
		title: filename,
		filename,
		format: 'text/plain',
		creatiedatum: '2026-11-01',
		vertrouwelijkheidaanduiding: 'openbaar',
		versie: 1,
		stagedFileRef: fileId,
		syncedAt: new Date().toISOString(),
		processingStatus: 'staged',
	})
}

/**
 * Open filinq's admin settings and wait for the bridge status panel.
 *
 * @param page The page.
 */
async function openBridgePanel(page: Page): Promise<void> {
	await page.goto('/index.php/settings/admin/filinq', { waitUntil: 'domcontentloaded' })
	await waitForNcContentReady(page)
	await expect(page.getByRole('heading', { name: /^(Case system sources|Zaaksysteembronnen)$/ })).toBeVisible({
		timeout: 30_000,
	})
}

test.describe('zgw document bridge', () => {
	test.setTimeout(180_000)

	test.afterAll(async ({ request }) => {
		for (const { schema, id } of createdObjects.reverse()) {
			await request.delete(`${OBJECTS}/${schema}/${id}`, { headers: OCS })
		}
		for (const path of createdPaths) {
			await request.fetch(`/remote.php/dav/files/${USER}/${path}`, { method: 'DELETE' })
		}
	})

	test('the admin panel lists a fresh and a failing source with vendor, last sync and health', async ({ page, request }) => {
		const fresh = `Demostad DRC ${STAMP}`
		const failing = `Demostad StUF ${STAMP}`
		const error = `401 Unauthorized from the DRC (${STAMP})`
		await seedSource(request, fresh, 'Rx.Enterprise', { lastSyncStatus: 'success' })
		await seedSource(request, failing, 'Djuma', {
			lastSyncAt: new Date(Date.now() - 2 * 3600_000).toISOString(),
			lastSyncStatus: 'error',
			lastSyncError: error,
		})

		const res = await request.get(SOURCES_API, { headers: OCS })
		expect(res.ok(), `GET ${SOURCES_API} -> ${res.status()}`).toBeTruthy()
		const sources = (await res.json()).results as Array<Record<string, any>>
		expect(sources.find((source) => source.name === fresh)?.health).toBe('fresh')
		expect(sources.find((source) => source.name === failing)?.health).toBe('failing')

		await openBridgePanel(page)
		const freshRow = page.getByTestId('bridge-source').filter({ hasText: fresh })
		await expect(freshRow).toContainText('Rx.Enterprise')
		await expect(freshRow).toContainText(/ZGW Documenten API/)
		await expect(freshRow.locator('td').nth(3)).not.toHaveText(/^(Never|Nooit)$/)
		await expect(freshRow).toContainText(/Up to date|Bijgewerkt/)
		await expect(freshRow.getByTestId('bridge-source-error')).toHaveCount(0)

		const failingRow = page.getByTestId('bridge-source').filter({ hasText: failing })
		await expect(failingRow).toContainText('Djuma')
		await expect(failingRow).toContainText(/Failing|Mislukt/)
		await expect(failingRow.getByTestId('bridge-source-error')).toHaveText(error)
	})

	test('without sources the panel explains that sources come from OpenConnector', async ({ page, request }) => {
		const res = await request.get(SOURCES_API, { headers: OCS })
		expect(res.ok()).toBeTruthy()
		const real = ((await res.json()).results as unknown[]).length === 0
		if (!real) {
			test.info().annotations.push({
				type: 'stubbed',
				description: 'the instance has sources, so the browser call was answered with an empty list: this proves the rendering only',
			})
			await page.route('**/api/bridge/sources', (route) =>
				route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ results: [] }) }),
			)
		}

		await openBridgePanel(page)
		const empty = page.getByTestId('bridge-sources-empty')
		await expect(empty).toBeVisible()
		await expect(empty).toContainText('OpenConnector')
		await expect(page.getByTestId('bridge-sources')).toHaveCount(0)
		await expect(page.getByText(/Could not load the case system sources|De zaaksysteembronnen konden niet worden geladen/)).toHaveCount(0)
	})

	test('a bridge document shows "Zaaksysteem: zaaksysteem.nl" in My Documents and a native file shows no badge', async ({ page, request }) => {
		await request.fetch(`/remote.php/dav/files/${USER}/${DOCS_FOLDER}`, { method: 'MKCOL' })
		const bridged = `${STAMP}-bridged.txt`
		const native = `${STAMP}-native.txt`
		const bridgedId = await uploadFile(request, `${DOCS_FOLDER}/${bridged}`, 'Staged copy of a case-system document.\n')
		const nativeId = await uploadFile(request, `${DOCS_FOLDER}/${native}`, 'A file made in Nextcloud.\n')

		const sourceId = await seedSource(request, `zaaksysteem.nl ${STAMP}`, 'zaaksysteem.nl', { lastSyncStatus: 'success' })
		await seedStaged(request, sourceId, bridgedId, bridged)

		const lookups: string[] = []
		page.on('request', (sent) => {
			if (sent.url().includes('/api/bridge/badges')) {
				lookups.push(new URL(sent.url()).searchParams.get('fileIds') ?? '')
			}
		})

		await go(page, 'my-documents')
		const bridgedRow = page.locator('tr').filter({ hasText: bridged.replace(/\.txt$/, '') }).first()
		const nativeRow = page.locator('tr').filter({ hasText: native.replace(/\.txt$/, '') }).first()
		await expect(bridgedRow).toBeVisible({ timeout: 30_000 })
		await expect(nativeRow).toBeVisible()

		await expect(bridgedRow.getByTestId('my-documents-source-badge')).toHaveText('Zaaksysteem: zaaksysteem.nl', { timeout: 15_000 })
		await expect(nativeRow.getByTestId('my-documents-source-badge')).toHaveCount(0)

		// Batched: one lookup carried both rows' files, not one lookup per row.
		const both = lookups.filter((ids) => {
			const list = ids.split(',')
			return list.includes(String(bridgedId)) && list.includes(String(nativeId))
		})
		expect(both.length, `badge lookups: ${JSON.stringify(lookups)}`).toBeGreaterThan(0)
	})

	test('a failed write-back shows its error and the retry returns it to ready_for_writeback', async ({ request }) => {
		const folder = `${STAMP}-staging`
		await request.fetch(`/remote.php/dav/files/${USER}/${folder}`, { method: 'MKCOL' })
		createdPaths.push(folder)
		const stagedId = await uploadFile(request, `${folder}/besluit.txt`, 'Besluit op het Woo-verzoek.\n')
		const resultId = await uploadFile(request, `${folder}/besluit (geanonimiseerd).txt`, 'Besluit op het Woo-verzoek. [geanonimiseerd]\n')

		const sourceId = await seedSource(request, `Retry source ${STAMP}`, 'zaaksysteem.nl', { lastSyncStatus: 'success' })
		const id = await seedStaged(request, sourceId, stagedId, 'besluit.txt')

		await updateObject(request, 'externalDocument', id, { processingStatus: 'in_processing' })
		await updateObject(request, 'externalDocument', id, { processingStatus: 'processed', resultFileRef: resultId })

		const released = await request.post(releaseApi(id), { headers: OCS })
		expect(released.status(), await released.text()).toBe(200)
		expect((await released.json()).processingStatus).toBe('ready_for_writeback')

		// OpenConnector's push leg failed.
		const message = `DRC answered 400: informatieobjecttype unknown (${STAMP})`
		await updateObject(request, 'externalDocument', id, { processingStatus: 'writeback_failed', writeBackError: message })

		const failed = await readObject(request, 'externalDocument', id)
		expect(failed.processingStatus).toBe('writeback_failed')
		expect(failed.writeBackError).toBe(message)

		const retried = await request.post(retryApi(id), { headers: OCS })
		expect(retried.status(), await retried.text()).toBe(200)
		expect((await retried.json()).processingStatus).toBe('ready_for_writeback')

		const stored = await readObject(request, 'externalDocument', id)
		expect(stored.processingStatus).toBe('ready_for_writeback')
		expect(stored.writeBackError ?? '').toBe('')
		// Nothing else was lost on the way.
		expect(Number(stored.resultFileRef)).toBe(resultId)
		expect(Number(stored.stagedFileRef)).toBe(stagedId)
		expect(stored.zaakIdentificatie).toBe('ZAAK-2026-0042')

		// A document that did not fail cannot be retried.
		const again = await request.post(retryApi(id), { headers: OCS })
		expect(again.status()).toBe(400)
	})
})
