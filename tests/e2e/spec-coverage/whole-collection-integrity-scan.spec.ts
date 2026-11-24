/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * whole-collection-integrity-scan, task 4: the integrity scan finds missing,
 * changed and orphaned files across the collection, and the administrator
 * reads and starts it from the "Integrity scan" section of the admin page.
 *
 * WHAT IT SEEDS ITSELF. The missing-member scenario is self-contained: the
 * spec uploads a file over WebDAV, creates a dossier, links the file, then
 * deletes the file (and empties it from the trash bin) so the dossier lists a
 * file id that no longer resolves.
 *
 * WHAT IT READS FROM THE INSTANCE. A final document whose bytes changed on
 * storage, and an anonymised copy whose source is gone, cannot be made from
 * the browser without going around filinq's own write guards. Point
 * `FILINQ_E2E_CHANGED_FILE_ID` at the file id of such a final document and
 * `FILINQ_E2E_ORPHANED_FILE_ID` at such a copy (the live pass prepares both
 * with occ); without them those tests skip and say why. The clean-collection
 * test runs only when `FILINQ_E2E_CLEAN_INSTANCE=1`, on an instance nothing
 * else writes to, because any finding on a shared instance would fail it.
 *
 * THE 403 TEST CREATES A USER. It makes a throwaway non-administrator through
 * the provisioning API as the signed-in admin, posts as that user, and deletes
 * the user afterwards.
 *
 * Not run in the change that wrote it (decision 139): the live pass runs it.
 */

// @e2e document-versions::a-file-of-a-final-document-was-changed-on-storage
// @e2e document-versions::a-dossier-member-was-deleted
// @e2e document-versions::an-anonymised-copy-lost-its-source
// @e2e document-versions::a-clean-collection
// @e2e document-versions::the-administrator-reads-the-last-result
// @e2e document-versions::only-administrators-start-a-scan
// @e2e document-versions::a-run-with-findings-notifies-once

import type { APIRequestContext, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { waitForNcContentReady } from './_helpers.ts'

/** The admin-only endpoint the section reads (GET) and starts a run with (POST). */
const SCAN_API = '/index.php/apps/filinq/api/settings/integrity-scan'

/** The dossier endpoints the seed uses. */
const DOSSIER_API = '/index.php/apps/filinq/api/dossiers'

/** The signed-in user the WebDAV seed writes for. */
const USER = process.env.NC_ADMIN_USER ?? 'admin'

/** Fixtures the live pass prepares (see the header). */
const CHANGED_FILE_ID = process.env.FILINQ_E2E_CHANGED_FILE_ID ?? ''
const ORPHANED_FILE_ID = process.env.FILINQ_E2E_ORPHANED_FILE_ID ?? ''
const CLEAN_INSTANCE = process.env.FILINQ_E2E_CLEAN_INSTANCE === '1'

/** The provisioning and notification endpoints. */
const USERS_API = '/ocs/v1.php/cloud/users'
const NOTIFICATIONS_API = '/ocs/v2.php/apps/notifications/api/v2/notifications?format=json'

/** What one stored run carries. */
interface ScanRun {
	startedAt: string
	checked: number
	missing: number
	changed: number
	orphaned: number
	findings: Array<{ kind: string; fileId: number | string; record?: string; schema?: string }>
}

/**
 * Start a scan through the endpoint and answer the stored run.
 *
 * @param request The authenticated request context.
 * @return The run the scan stored.
 */
async function startScan(request: APIRequestContext): Promise<ScanRun> {
	const res = await request.post(SCAN_API, {
		headers: { 'OCS-APIRequest': 'true' },
	})
	expect(res.ok(), `POST ${SCAN_API} -> ${res.status()}`).toBeTruthy()
	const body = await res.json()
	expect(body.run, 'the start endpoint answers the stored run').toBeTruthy()

	return body.run as ScanRun
}

/**
 * Upload a small file over WebDAV and answer its Nextcloud file id.
 *
 * @param request The authenticated request context.
 * @param name The file name in the user's root folder.
 * @return The file id.
 */
async function uploadFile(request: APIRequestContext, name: string): Promise<number> {
	const url = `/remote.php/dav/files/${USER}/${encodeURIComponent(name)}`
	const put = await request.put(url, { data: 'integrity scan seed\n' })
	expect([201, 204]).toContain(put.status())

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
 * Delete a file over WebDAV and empty it from the trash bin, so its id no
 * longer resolves anywhere.
 *
 * @param request The authenticated request context.
 * @param name The file name in the user's root folder.
 */
async function deleteFile(request: APIRequestContext, name: string): Promise<void> {
	const del = await request.delete(`/remote.php/dav/files/${USER}/${encodeURIComponent(name)}`)
	expect([204, 404]).toContain(del.status())
	// Emptying the whole trash bin is fine on the throwaway e2e instance.
	await request.delete(`/remote.php/dav/trashbin/${USER}/trash`)
}

/**
 * Seed a dossier that lists a deleted file, so the next run has a `missing`
 * finding. Answers the dossier id and the file id.
 *
 * @param request The authenticated request context.
 * @return The dossier id and the deleted file's id.
 */
async function seedMissingMember(request: APIRequestContext): Promise<{ dossierId: string; fileId: number }> {
	const name = `integrity-scan-${Date.now()}.txt`
	const fileId = await uploadFile(request, name)

	const created = await request.post(DOSSIER_API, {
		headers: { 'OCS-APIRequest': 'true' },
		data: { name: `Integrity scan ${Date.now()}` },
	})
	expect(created.status(), `POST ${DOSSIER_API}`).toBe(201)
	const dossier = await created.json()
	const dossierId = String(dossier.id ?? dossier.uuid ?? dossier['@self']?.id ?? '')
	expect(dossierId).not.toBe('')

	const linked = await request.post(`${DOSSIER_API}/${dossierId}/documents`, {
		headers: { 'OCS-APIRequest': 'true' },
		data: { fileId },
	})
	expect(linked.ok(), `link ${fileId} -> ${linked.status()}`).toBeTruthy()

	await deleteFile(request, name)

	return { dossierId, fileId }
}

/**
 * The signed-in user's filinq integrity-scan notifications.
 *
 * @param request The authenticated request context.
 * @return The notifications for integrity scan runs.
 */
async function scanNotifications(request: APIRequestContext): Promise<Array<{ object_id: string }>> {
	const res = await request.get(NOTIFICATIONS_API, { headers: { 'OCS-APIRequest': 'true' } })
	expect(res.ok(), `GET notifications -> ${res.status()}`).toBeTruthy()
	const body = await res.json()

	return (body?.ocs?.data ?? []).filter(
		(n: any) => n.app === 'filinq' && n.object_type === 'integrityScan',
	)
}

/**
 * Open filinq's admin settings and wait for the integrity scan section.
 *
 * @param page The page.
 */
async function openScanSection(page: Page): Promise<void> {
	await page.goto('/index.php/settings/admin/filinq', { waitUntil: 'domcontentloaded' })
	await waitForNcContentReady(page)
	await expect(page.getByRole('heading', { name: /Integrity scan|Integriteitscontrole/ })).toBeVisible({
		timeout: 30_000,
	})
}

test.describe('whole-collection integrity scan', () => {
	test.setTimeout(180_000)

	test('a dossier member deleted from storage is reported as missing, naming the dossier', async ({ request }) => {
		const { dossierId, fileId } = await seedMissingMember(request)

		const run = await startScan(request)
		const missing = run.findings.filter((f) => f.kind === 'missing' && String(f.fileId) === String(fileId))
		expect(missing, 'one missing finding for the deleted member').toHaveLength(1)
		expect(missing[0].record).toBe(dossierId)
		expect(run.missing).toBeGreaterThanOrEqual(1)
	})

	test('a final document whose file changed on storage is reported as changed and left as it is', async ({ request }) => {
		test.skip(CHANGED_FILE_ID === '', 'FILINQ_E2E_CHANGED_FILE_ID is not set: no final document with changed bytes on this instance')

		const run = await startScan(request)
		const changed = run.findings.filter((f) => f.kind === 'changed' && String(f.fileId) === CHANGED_FILE_ID)
		expect(changed, 'one changed finding for the edited final document').toHaveLength(1)

		// The scan only reads: a second run finds the same file changed again.
		const again = await startScan(request)
		expect(again.findings.some((f) => f.kind === 'changed' && String(f.fileId) === CHANGED_FILE_ID)).toBe(true)
	})

	test('an anonymised copy whose source is gone is reported as orphaned', async ({ request }) => {
		test.skip(ORPHANED_FILE_ID === '', 'FILINQ_E2E_ORPHANED_FILE_ID is not set: no orphaned anonymised copy on this instance')

		const run = await startScan(request)
		const orphaned = run.findings.filter((f) => f.kind === 'orphaned' && String(f.fileId) === ORPHANED_FILE_ID)
		expect(orphaned, 'one orphaned finding for the copy').toHaveLength(1)
	})

	test('a clean collection stores a run with all three counts at zero', async ({ request }) => {
		test.skip(!CLEAN_INSTANCE, 'FILINQ_E2E_CLEAN_INSTANCE is not 1: a shared instance may hold findings')

		const run = await startScan(request)
		expect(run.missing).toBe(0)
		expect(run.changed).toBe(0)
		expect(run.orphaned).toBe(0)
		expect(run.findings).toHaveLength(0)

		const last = await request.get(SCAN_API, { headers: { 'OCS-APIRequest': 'true' } })
		expect(last.ok()).toBeTruthy()
		expect((await last.json()).run.startedAt).toBe(run.startedAt)
	})

	test('the administrator reads the last run, its counts and a link per finding', async ({ page, request }) => {
		const run = await startScan(request)

		await openScanSection(page)
		await expect(page.locator('.integrity-scan__time')).toBeVisible()
		const counts = page.locator('.integrity-scan__count')
		await expect(counts).toHaveCount(3)
		await expect(counts.nth(0)).toContainText(String(run.missing))
		await expect(counts.nth(1)).toContainText(String(run.changed))
		await expect(counts.nth(2)).toContainText(String(run.orphaned))
		await expect(page.locator('.integrity-scan__finding a')).toHaveCount(run.findings.length)

		// "Check now" runs a scan from the page and shows its result.
		await page.getByRole('button', { name: /Check now|Nu controleren/ }).click()
		await expect(page.locator('.integrity-scan__time')).toBeVisible({ timeout: 60_000 })
	})

	test('a user who is not an administrator is refused and no scan runs', async ({ playwright, request, baseURL }) => {
		const before = await request.get(SCAN_API, { headers: { 'OCS-APIRequest': 'true' } })
		expect(before.ok()).toBeTruthy()
		const lastStarted = (await before.json()).run?.startedAt ?? null

		const userid = `scan-e2e-${Date.now()}`
		const password = `Pw-${Date.now()}-integrity!`
		const made = await request.post(USERS_API, {
			headers: { 'OCS-APIRequest': 'true' },
			form: { userid, password },
		})
		expect(made.ok(), `create user -> ${made.status()}`).toBeTruthy()

		const asUser = await playwright.request.newContext({
			baseURL,
			httpCredentials: { username: userid, password },
		})
		try {
			const res = await asUser.post(SCAN_API, { headers: { 'OCS-APIRequest': 'true' } })
			expect(res.status()).toBe(403)

			const after = await request.get(SCAN_API, { headers: { 'OCS-APIRequest': 'true' } })
			expect((await after.json()).run?.startedAt ?? null).toBe(lastStarted)
		} finally {
			await asUser.dispose()
			await request.delete(`${USERS_API}/${userid}`, { headers: { 'OCS-APIRequest': 'true' } })
		}
	})

	test('a run with a missing file notifies each administrator once for that run', async ({ request }) => {
		await seedMissingMember(request)

		const run = await startScan(request)
		expect(run.missing + run.changed).toBeGreaterThan(0)

		const forRun = (await scanNotifications(request)).filter((n) => n.object_id === String(run.startedAt))
		expect(forRun, 'one notification for this run').toHaveLength(1)
	})
})
