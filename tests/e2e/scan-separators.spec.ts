/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * scan-intake-with-separator-sheets, task 5.2: a clerk prints separator
 * sheets from the intake page, and an administrator adds a scanner profile on
 * the intake channels page.
 *
 * WHAT IT SEEDS ITSELF. Scanner profiles are one admin setting
 * (`scanProfiles`) that `POST api/scan/profiles` REPLACES as a whole list. Each
 * test reads the list first, declares the list plus its own profile, and puts
 * the original list back when it ends, so a run leaves the instance's profiles
 * exactly as it found them. The print test declares its profile through the
 * API; the add test adds it through the page's form, as an administrator does.
 *
 * THE PRINTED PDF IS READ, NOT JUST RECEIVED. The sheets are parsed with
 * pdfjs-dist (a dev dependency already): one page per case number, each page
 * carrying the payload `filinq:sep:v1:<profileId>:<caseNumber>`. The QR image
 * itself is not decoded (no QR decoder in the tree); `SeparatorSheetService`
 * prints the same payload as text under the code, and that text is asserted.
 *
 * WHAT IT READS FROM THE INSTANCE. "The next batch in the folder is split at
 * separator sheets" needs a scanned multi-page PDF that contains filinq
 * separator sheets, which a browser cannot make. Point
 * `FILINQ_E2E_SCAN_BATCH_PDF` at such a file on the machine running the suite
 * (optionally `FILINQ_E2E_SCAN_BATCH_SEGMENTS` at the number of documents it
 * holds); without it that half of the add test skips and says why. The batch
 * is uploaded over WebDAV into the profile's folder and the folder is deleted
 * afterwards. The intake documents the split announces are deleted through
 * OpenRegister where they can be found by file id.
 *
 * KNOWN DISAGREEMENT, EXPECTED TO FAIL UNTIL FIXED. The split leaves the
 * profile to the folder (`POST api/scan/batches/{fileId}/split` without a
 * `profileId`). `ScanIntakeController::split()` passes `File::getPath()` to
 * `ScanProfileService::forPath()`; that path is the absolute node path
 * (`/<user>/files/Scans/...`) while a profile's `watchedFolder` is declared
 * user-relative (`Scans/Post`, as the spec and the form write it), so no
 * profile matches and the split answers 400. This test asserts the spec.
 *
 * Excluded in the spec (`@e2e exclude`) and not here: the folder watch
 * becoming a batch, the blank-page split, the two-separator and the
 * no-separator splits (PHPUnit fixture batches), and the profile round trip
 * (PHPUnit on `ScanProfileService`).
 *
 * Locale: the assertions accept the English and the Dutch strings.
 *
 * Not run in the change that wrote it (decision 139): the live pass runs it.
 */

// @e2e openspec/changes/archive/2026-11-25-scan-intake-with-separator-sheets/specs/scan-intake/spec.md#a-clerk-prints-separators-for-three-cases
// @e2e openspec/changes/archive/2026-11-25-scan-intake-with-separator-sheets/specs/scan-intake/spec.md#an-administrator-adds-a-scanner-profile

import type { APIRequestContext, Locator, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { createHash } from 'node:crypto'
import { readFileSync } from 'node:fs'
import { go } from './spec-coverage/_helpers.ts'

/** The scan profile routes: GET lists, POST declares the whole list. */
const PROFILES_API = '/index.php/apps/filinq/api/scan/profiles'

/** The separator sheet route the print dialog posts to. */
const SEPARATORS_API = '/index.php/apps/filinq/api/scan/separators'

/** The batch split route. */
const splitApi = (fileId: number): string => `/index.php/apps/filinq/api/scan/batches/${fileId}/split`

/** OpenRegister's intake documents, for cleaning up what a split announced. */
const INTAKE_OBJECTS = '/index.php/apps/openregister/api/objects/filinq/intakeDocument'

/** The signed-in user the WebDAV seed writes for. */
const USER = process.env.NC_ADMIN_USER ?? 'admin'

/**
 * WebDAV with the stored browser session is refused without a request token
 * (MKCOL answered 401 on a live run, 25 Nov 2026); the OCS header marks the
 * call as an API call, and basic auth names the admin.
 */
const DAV = {
	'OCS-APIRequest': 'true',
	Authorization: `Basic ${Buffer.from(`${USER}:${process.env.NC_ADMIN_PASS ?? 'admin'}`).toString('base64')}`,
}

/** A scanned batch with filinq separator sheets (see the header). */
const BATCH_PDF = process.env.FILINQ_E2E_SCAN_BATCH_PDF ?? ''
const BATCH_SEGMENTS = Number(process.env.FILINQ_E2E_SCAN_BATCH_SEGMENTS ?? '0')

/** The OCS header every call carries. */
const OCS = { 'OCS-APIRequest': 'true' }

/** Unique per run, so nothing this run writes collides with another run. */
const STAMP = `e2e-${Date.now()}`

/** One declared scan profile, as `api/scan/profiles` answers it. */
interface ScanProfile {
	id: string
	label?: string
	watchedFolder: string
	separatorMode: string
	resolution?: number
	colourMode?: string
	[key: string]: unknown
}

/**
 * The declared scan profiles.
 *
 * @param request The authenticated request context.
 * @return The profiles as the server stores them.
 */
async function listProfiles(request: APIRequestContext): Promise<ScanProfile[]> {
	const res = await request.get(PROFILES_API, { headers: OCS })
	expect(res.ok(), `GET ${PROFILES_API} -> ${res.status()}`).toBeTruthy()
	const body = await res.json()
	expect(Array.isArray(body.results)).toBe(true)

	return body.results as ScanProfile[]
}

/**
 * Declare the whole list of scan profiles.
 *
 * @param request The authenticated request context.
 * @param profiles The complete list to store.
 * @return The list the server stored.
 */
async function declareProfiles(request: APIRequestContext, profiles: ScanProfile[]): Promise<ScanProfile[]> {
	const res = await request.post(PROFILES_API, { headers: OCS, data: { profiles } })
	expect(res.ok(), `POST ${PROFILES_API} -> ${res.status()} ${await res.text()}`).toBeTruthy()

	return (await res.json()).results as ScanProfile[]
}

/**
 * The text of every page of a PDF, with all whitespace removed so the
 * letter-spaced payload line reads as one string.
 *
 * @param bytes The PDF.
 * @return One entry per page.
 */
async function pageTexts(bytes: Buffer): Promise<string[]> {
	const pdfjs = await import('pdfjs-dist/legacy/build/pdf.mjs')
	const task = pdfjs.getDocument({ data: new Uint8Array(bytes) })
	const doc = await task.promise
	const texts: string[] = []
	for (let number = 1; number <= doc.numPages; number++) {
		const page = await doc.getPage(number)
		const content = await page.getTextContent()
		texts.push(
			content.items
				.map((item) => ('str' in item ? item.str : ''))
				.join('')
				.replace(/\s+/g, ''),
		)
	}
	await task.destroy()

	return texts
}

/**
 * Create a WebDAV folder and every missing parent of it.
 *
 * @param request The authenticated request context.
 * @param path The folder, relative to the user's root.
 */
async function makeFolders(request: APIRequestContext, path: string): Promise<void> {
	let current = ''
	for (const part of path.split('/')) {
		current = current === '' ? part : `${current}/${part}`
		const res = await request.fetch(`/remote.php/dav/files/${USER}/${current}`, { method: 'MKCOL', headers: DAV })
		expect([201, 405], `MKCOL ${current} (201 created / 405 already there)`).toContain(res.status())
	}
}

/**
 * Upload a file over WebDAV and answer its Nextcloud file id.
 *
 * @param request The authenticated request context.
 * @param path The file, relative to the user's root.
 * @param bytes The contents.
 * @return The file id.
 */
async function uploadFile(request: APIRequestContext, path: string, bytes: Buffer): Promise<number> {
	const url = `/remote.php/dav/files/${USER}/${path}`
	const put = await request.put(url, { data: bytes, headers: { ...DAV, 'Content-Type': 'application/pdf' } })
	expect([201, 204], `PUT ${path}`).toContain(put.status())

	const find = await request.fetch(url, {
		method: 'PROPFIND',
		headers: { ...DAV, Depth: '0', 'Content-Type': 'application/xml' },
		data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns"><d:prop><oc:fileid/></d:prop></d:propfind>',
	})
	expect(find.status()).toBe(207)
	const match = /<oc:fileid>(\d+)<\/oc:fileid>/.exec(await find.text())
	expect(match, 'PROPFIND answers the file id').not.toBeNull()

	return Number(match![1])
}

/**
 * Delete the intake documents a split announced for these segment files.
 * Best effort: a document that cannot be found is left, and says so in the log.
 *
 * @param request The authenticated request context.
 * @param fileIds The segment file ids.
 */
async function deleteIntakeDocuments(request: APIRequestContext, fileIds: number[]): Promise<void> {
	for (const fileId of fileIds) {
		const list = await request.get(`${INTAKE_OBJECTS}?file=${fileId}`, { headers: OCS })
		if (!list.ok()) {
			continue
		}
		const rows = ((await list.json())?.results ?? []) as Array<Record<string, any>>
		for (const row of rows.filter((candidate) => Number(candidate.file) === fileId)) {
			const id = row['@self']?.id ?? row.id ?? row.uuid
			if (id) {
				await request.delete(`${INTAKE_OBJECTS}/${id}`, { headers: OCS })
			}
		}
	}
}

/**
 * An open select's option by its text. NcSelect renders a long label as two
 * spans (NcEllipsisedOption), so the accessible name reads "Black an d white"
 * while the text is whole (seen on a live run, 25 Nov 2026).
 *
 * @param page The page.
 * @param text The option text.
 * @return The option locator.
 */
function option(page: Page, text: string | RegExp): Locator {
	return page.getByRole('option').filter({ hasText: text }).first()
}

test.describe('scan intake with separator sheets', () => {
	test.setTimeout(180_000)

	let original: ScanProfile[] = []

	test.beforeEach(async ({ request }) => {
		original = await listProfiles(request)
	})

	test.afterEach(async ({ request }) => {
		await declareProfiles(request, original)
	})

	test('a clerk prints separators for three cases: one PDF, three pages, one case number each', async ({ page, request }) => {
		const profile: ScanProfile = {
			id: `sep-${STAMP}`,
			label: `E2E separators ${STAMP}`,
			watchedFolder: `Scans/${STAMP}`,
			separatorMode: 'qr',
		}
		await declareProfiles(request, [...original, profile])
		const caseNumbers = ['2026-0041', '2026-0042', '2026-0043']

		await go(page, 'intake')
		await page.getByTestId('intake-print-separators').click()

		const picker = page.getByTestId('separator-profile')
		await expect(picker).toBeVisible({ timeout: 15_000 })
		await picker.locator('.vs__dropdown-toggle').click()
		await option(page, String(profile.label)).click()
		await expect(picker).toContainText(String(profile.label))

		// NcTextArea hands its data-testid to the textarea itself (Vue 3), so
		// the box is found by its label (seen on a live run, 25 Nov 2026).
		await page.getByRole('textbox', { name: /Case numbers, one per line|Zaaknummers, één per regel/ }).fill(caseNumbers.join('\n'))

		const submit = page.getByTestId('cn-dialog-actions-submit')
		await expect(submit).toHaveText(/Print 3 sheets|3 vellen afdrukken/)

		const [response, download] = await Promise.all([
			page.waitForResponse((res) => res.url().includes('/api/scan/separators') && res.request().method() === 'POST'),
			page.waitForEvent('download'),
			submit.click(),
		])
		expect(response.status()).toBe(200)
		expect(response.headers()['content-type']).toContain('application/pdf')
		expect(response.request().postDataJSON()).toEqual({ profileId: profile.id, caseNumbers })
		expect(download.suggestedFilename()).toBe('scheidingsvellen.pdf')

		const texts = await pageTexts(await response.body())
		expect(texts, 'one page per case number').toHaveLength(3)
		caseNumbers.forEach((number, index) => {
			expect(texts[index]).toContain(`filinq:sep:v1:${profile.id}:${number}`)
		})
	})

	test('the separator route refuses a print without a profile', async ({ request }) => {
		const res = await request.post(SEPARATORS_API, { headers: OCS, data: { profileId: '', caseNumbers: ['2026-0042'] } })
		expect(res.status()).toBe(400)
		expect(String((await res.json()).error)).not.toBe('')
	})

	test('an administrator adds a scanner profile and it is listed and stored as declared', async ({ page, request }) => {
		const label = `Scanner 2e verdieping ${STAMP}`
		// A subfolder of Scans/Post, so a profile the instance already has on
		// Scans/Post does not take a batch: the deepest folder wins.
		const folder = `Scans/Post/${STAMP}`

		await go(page, 'intake-channels')
		await expect(page.getByTestId('intake-channels-index')).toBeVisible({ timeout: 15_000 })
		await expect(page.getByText(/^(Scanner profiles|Scanprofielen)$/)).toBeVisible()

		const form = page.getByTestId('scan-profile-form')
		await form.getByLabel(/^(Name|Naam)$/).fill(label)
		await form.getByLabel(/^(Watched folder|Bewaakte map)$/).fill(folder)
		await form.getByLabel(/^(Resolution \(dpi\)|Resolutie \(dpi\))$/).fill('300')

		const selects = form.locator('.v-select')
		await selects.nth(0).locator('.vs__dropdown-toggle').click()
		await option(page, /Black and white|Zwart-wit/).click()
		await selects.nth(1).locator('.vs__dropdown-toggle').click()
		await option(page, /Separator sheet with QR code|Scheidingsvel met QR-code/).click()

		const declared = page.waitForResponse((res) => res.url().includes('/api/scan/profiles') && res.request().method() === 'POST')
		await page.getByTestId('scan-profile-add').click()
		expect((await declared).status()).toBe(200)
		await expect(page.getByText(/Scanner profile added|Scanprofiel toegevoegd/)).toBeVisible({ timeout: 15_000 })

		// Listed on the page with what was chosen.
		const row = page.getByRole('row', { name: new RegExp(label) })
		await expect(row).toBeVisible()
		await expect(row).toContainText(folder)
		await expect(row).toContainText(/300 dpi/)
		await expect(row).toContainText(/Black and white|Zwart-wit/)

		// And stored as declared, next to the profiles that were already there.
		const stored = await listProfiles(request)
		const added = stored.find((profile) => profile.label === label)
		expect(added, 'the new profile is stored').toBeTruthy()
		expect(added!.watchedFolder).toBe(folder)
		expect(added!.separatorMode).toBe('qr')
		expect(added!.resolution).toBe(300)
		expect(added!.colourMode).toBe('blackAndWhite')
		for (const before of original) {
			expect(stored.map((profile) => profile.id)).toContain(before.id)
		}
	})

	test('a batch landing in the folder of a qr profile is split at the separator sheets', async ({ request }) => {
		test.skip(BATCH_PDF === '', 'FILINQ_E2E_SCAN_BATCH_PDF is not set: no scanned batch with separator sheets to split')

		// The profile of the scenario, declared the way the form declares it
		// (`withNewProfile` in src/services/scanProfiles.js).
		const folder = `Scans/Post/${STAMP}`
		const profile: ScanProfile = {
			id: `scanner-2e-verdieping-${STAMP}`,
			label: `Scanner 2e verdieping ${STAMP}`,
			watchedFolder: folder,
			separatorMode: 'qr',
			resolution: 300,
			colourMode: 'blackAndWhite',
		}
		await declareProfiles(request, [...original, profile])

		const bytes = readFileSync(BATCH_PDF)
		await makeFolders(request, folder)
		const segmentIds: number[] = []
		try {
			const batchName = `batch-${createHash('sha256').update(bytes).digest('hex').slice(0, 8)}-${STAMP}.pdf`
			const fileId = await uploadFile(request, `${folder}/${batchName}`, bytes)

			// No profileId: the folder the batch landed in decides how it is cut.
			const split = await request.post(splitApi(fileId), { headers: OCS, data: {} })
			const batch = await split.json()
			expect(split.status(), JSON.stringify(batch)).toBe(200)
			expect(batch.status).toBe('split')
			expect(batch.scannerId).toBe(profile.id)
			expect(batch.separatorMode).toBe('qr')
			expect(Array.isArray(batch.segments)).toBe(true)
			for (const segment of batch.segments as Array<{ fileId: number }>) {
				segmentIds.push(Number(segment.fileId))
			}
			if (BATCH_SEGMENTS > 0) {
				expect(batch.segments).toHaveLength(BATCH_SEGMENTS)
			} else {
				expect(batch.segments.length, 'a batch with separator sheets is cut into more than one document').toBeGreaterThan(1)
			}
		} finally {
			await deleteIntakeDocuments(request, segmentIds)
			await request.fetch(`/remote.php/dav/files/${USER}/${folder}`, { method: 'DELETE', headers: DAV })
		}
	})
})
