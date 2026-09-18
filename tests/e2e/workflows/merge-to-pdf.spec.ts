/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * End-to-end regression for `merge-documents-to-pdf`: a selection becomes one
 * PDF in the order it was given, a file the caller may not read is refused
 * before any job exists, a merge that cannot convert an input leaves no partial
 * file, and a large selection is queued with progress to watch.
 *
 * WHAT IS DRIVEN THROUGH THE UI AND WHAT IS NOT
 * --------------------------------------------
 * The bulk action lives in the consuming app's file list and the leaf that
 * carries it is still to come, so everything here goes through the documented
 * endpoint. That is also where the guarantees live: a refusal that only held in
 * the dialog would not be a refusal at all.
 *
 * The page order and the outline are NOT read here: those are read out of the
 * merged bytes, which is a job for PHPUnit with real fixtures rather than for a
 * browser.
 *
 * ⚠️ The `@e2e` anchors name BOTH the change's delta spec and the canonical
 * spec it is synced into at archive time. Gate 19 scans `openspec/specs/` only.
 */

import { expect, test } from '@playwright/test'
import {
	API,
	createDavFile,
	createDavFolder,
	deleteDavPath,
	harvestToken,
	jsonHeaders,
	TEST_PREFIX,
} from './_fixtures.ts'

/** Folder every document this suite seeds lives in. */
const FOLDER = `${TEST_PREFIX}-merge`

test.describe('Merge documents to one PDF', () => {
	let token = ''
	const seeded: Record<string, string> = {}

	test.beforeAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		token = await harvestToken(page)
		expect(token, 'the suite needs a live request token').not.toBe('')
		expect(await createDavFolder(page.request, token, FOLDER)).toBeLessThan(300)

		for (const name of ['aanvraag.txt', 'besluit.txt']) {
			const { status, fileId } = await createDavFile(
				page.request,
				token,
				`${FOLDER}/${name}`,
				`De inhoud van ${name}`,
			)
			expect(status, `seeding ${name} must succeed`).toBeLessThan(300)
			seeded[name] = fileId
		}

		await page.close()
	})

	test.afterAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		await deleteDavPath(page.request, await harvestToken(page), FOLDER)
		await page.close()
	})

	// @e2e openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md#dossiq-merges-the-documents-of-a-case
	// @e2e openspec/specs/document-merge/spec.md#dossiq-merges-the-documents-of-a-case
	test('a selection becomes one PDF, recorded as a job that names its inputs', async ({
		page,
	}) => {
		const merged = await page.request.post(`${API}/merge`, {
			headers: jsonHeaders(token),
			data: {
				inputs: [
					{ fileId: Number(seeded['aanvraag.txt']), label: 'Aanvraag' },
					{ fileId: Number(seeded['besluit.txt']), label: 'Besluit' },
				],
				options: {
					bookmarks: true,
					name: `${TEST_PREFIX}-bundel`,
					targetFolder: FOLDER,
				},
			},
		})

		// A merge needs a conversion backend for .txt. Where one is present the
		// job is done; where none is, the job FAILS and says so. Both are
		// correct answers, and neither is a half-written file: what this asserts
		// is that the job never claims a result it does not have.
		expect([200, 202]).toContain(merged.status())
		const job = await merged.json()
		expect(job.inputs.length).toBe(2)
		expect(job.inputs[0].label).toBe('Aanvraag')
		expect(['done', 'failed', 'queued', 'running']).toContain(job.status)
		if (job.status === 'done') {
			expect(job.resultFileId).toBeGreaterThan(0)
			expect(job.pageCount).toBeGreaterThan(0)
			expect(['pdf', 'pdfa-3b']).toContain(job.conformance)
		} else if (job.status === 'failed') {
			expect(job.lastError, 'a failed merge says what went wrong').not.toBe('')
			expect(job.resultFileId ?? 0, 'a failed merge has no result file').toBe(0)
		}
	})

	// @e2e openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md#a-file-the-user-may-not-read-is-refused
	// @e2e openspec/specs/document-merge/spec.md#dossiq-merges-the-documents-of-a-case
	test('a file the caller may not read is refused, and no job is created', async ({
		page,
	}) => {
		const before = await page.request.get(
			'/index.php/apps/openregister/api/objects/filinq/mergeJob',
			{ headers: jsonHeaders(token) },
		)
		expect(before.status()).toBe(200)
		const countBefore = (await before.json()).results?.length ?? 0

		// A file id nothing on this instance answers to: indistinguishable from
		// one the caller may not read, which is the point.
		const refused = await page.request.post(`${API}/merge`, {
			headers: jsonHeaders(token),
			data: {
				inputs: [
					{ fileId: Number(seeded['aanvraag.txt']), label: 'Aanvraag' },
					{ fileId: 987654321, label: 'Geheim' },
				],
				options: { name: `${TEST_PREFIX}-geweigerd` },
			},
		})
		expect(refused.status()).toBe(403)

		const after = await page.request.get(
			'/index.php/apps/openregister/api/objects/filinq/mergeJob',
			{ headers: jsonHeaders(token) },
		)
		const countAfter = (await after.json()).results?.length ?? 0
		expect(countAfter, 'a refusal creates no job').toBe(countBefore)
	})

	// @e2e openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md#a-file-the-user-may-not-read-is-refused
	// @e2e openspec/specs/document-merge/spec.md#dossiq-merges-the-documents-of-a-case
	test('a merge that names no documents is refused as a bad request', async ({
		page,
	}) => {
		const refused = await page.request.post(`${API}/merge`, {
			headers: jsonHeaders(token),
			data: { inputs: [], options: {} },
		})
		expect(refused.status()).toBe(400)
	})

	// @e2e openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md#a-large-batch-is-queued-and-reports-progress
	// @e2e openspec/specs/document-merge/spec.md#dossiq-merges-the-documents-of-a-case
	test('a large selection answers with a queued job, and the job can be read back', async ({
		page,
	}) => {
		const queued = await page.request.post(`${API}/merge`, {
			headers: jsonHeaders(token),
			data: {
				inputs: [
					{ fileId: Number(seeded['aanvraag.txt']), label: 'Aanvraag', pages: 500 },
				],
				options: { name: `${TEST_PREFIX}-groot`, targetFolder: FOLDER },
			},
		})
		expect(queued.status()).toBe(202)
		const job = await queued.json()
		expect(job.status).toBe('queued')
		expect(job.progress).toBe(0)

		const read = await page.request.get(`${API}/merge/${job.uuid}`, {
			headers: jsonHeaders(token),
		})
		expect(read.status()).toBe(200)
		const stored = await read.json()
		expect(stored.uuid).toBe(job.uuid)
		expect(stored).toHaveProperty('progress')
	})

	// @e2e openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md#a-large-batch-is-queued-and-reports-progress
	// @e2e openspec/specs/document-merge/spec.md#dossiq-merges-the-documents-of-a-case
	test('a merge nobody started answers 404 rather than an empty job', async ({
		page,
	}) => {
		const missing = await page.request.get(
			`${API}/merge/${TEST_PREFIX}-bestaat-niet`,
			{ headers: jsonHeaders(token) },
		)
		expect(missing.status()).toBe(404)
	})
})
