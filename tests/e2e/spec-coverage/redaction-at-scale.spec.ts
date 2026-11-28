/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * redaction-at-scale, task 5.2: a batch above the synchronous cap goes to the
 * background instead of being refused, a recursive dossier folder becomes one
 * batch, the operator follows a batch on the "Batch operations" screen and
 * cancels it there, an errored batch resumes without redoing what succeeded,
 * and a completed background batch carries its QA sample.
 *
 * WHAT IT SEEDS ITSELF. The folder scenarios are self-contained: the spec
 * uploads a folder over WebDAV holding one file more than the synchronous cap
 * directly, plus three subfolders of two files each, and creates a flat and a
 * recursive folder batch from it. Both are above the synchronous cap, so both
 * go to the background path. The flat one is then followed and cancelled on
 * the Batch operations screen. The spec cancels whatever it started and
 * deletes the folder when it ends. The synchronous cap is not settable over
 * HTTP (`batch.max_files_per_run`, default 100); point `FILINQ_E2E_SYNC_CAP`
 * at the instance's value when it is not the default.
 *
 * WHAT IT READS FROM THE INSTANCE. Three scenarios need a batch that has been
 * through detection and anonymisation, which a test cannot make without a
 * real pipeline and the cron coordinator running:
 *   - `FILINQ_E2E_REVIEW_BATCH_ID`: a batch in status `review` with more files
 *     than the synchronous cap, every file checked (or the checked gate on
 *     `advisory`). The test anonymises it, so it is used up.
 *   - `FILINQ_E2E_ERRORED_BATCH_ID`: a batch in status `error` with some files
 *     anonymised and some not. The test resumes it, so it is used up.
 *   - `FILINQ_E2E_QA_BATCH_ID`: a completed background batch whose QA sample
 *     nobody has reviewed yet; `FILINQ_E2E_QA_RATE` is the instance's
 *     `batch.qa_sample_rate` when it is not the default 5.
 * Without them those tests skip and say why. The steps that wait for the
 * coordinator to finish work (a cancel landing, a resumed or enqueued batch
 * completing) run only with `FILINQ_E2E_CRON_RUNS=1`, on an instance whose
 * cron ticks while the test waits.
 *
 * EXCLUDED, as the spec marks them `@e2e exclude`: the small synchronous batch,
 * the multi-unit extraction, the flat-scan default, the recursive and plain
 * refusals above the background cap, the cache flush and cache coherence, the
 * cron ticks and time budget, the throttle computation, cancelling a completed
 * batch, the out-of-band success on resume, the report's failure groups and
 * throughput, the reproducible sample, rate zero and the soffice lock retry.
 * PHPUnit and Newman cover them. The batch-anonymization and
 * folder-batch-analysis scenarios that carry no `@e2e` line at all belong to
 * the main specs and are not named by task 5.2.
 *
 * Not run in the change that wrote it (decision 139): the live pass runs it.
 */

// @e2e openspec/changes/redaction-at-scale/specs/batch-anonymization/spec.md#large-batch-is-enqueued-instead-of-processed-in-request-req-ddras-009
// @e2e openspec/changes/redaction-at-scale/specs/folder-batch-analysis/spec.md#recursive-dossier-folder-becomes-one-batch
// @e2e openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#operator-watches-batch-progress
// @e2e openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#cancel-takes-effect-between-files
// @e2e openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#resume-after-a-mid-batch-failure
// @e2e openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#five-percent-of-a-batch-is-sampled
// @e2e openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#oversized-batch-routes-to-background-instead-of-400

import type { APIRequestContext } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { go } from './_helpers.ts'

/** The batch endpoints. */
const BATCH_API = '/index.php/apps/filinq/api/anonymization/batch'

/** The anonymizationBatch record in OpenRegister; its uuid is the batch id. */
const batchObject = (batchId: string) => `/index.php/apps/openregister/api/objects/filinq/anonymizationBatch/${batchId}`

/** The review state of one document. */
const reviewApi = (fileId: number | string) => `/index.php/apps/filinq/api/review/${fileId}`

/** The OCS header every call carries. */
const OCS = { 'OCS-APIRequest': 'true' }

/** The signed-in user the WebDAV seed writes for. */
const USER = process.env.NC_ADMIN_USER ?? 'admin'

/** The synchronous cap (`batch.max_files_per_run`). */
const SYNC_CAP = Number(process.env.FILINQ_E2E_SYNC_CAP ?? '100')

/** Fixtures the live pass prepares (see the header). */
const REVIEW_BATCH_ID = process.env.FILINQ_E2E_REVIEW_BATCH_ID ?? ''
const ERRORED_BATCH_ID = process.env.FILINQ_E2E_ERRORED_BATCH_ID ?? ''
const QA_BATCH_ID = process.env.FILINQ_E2E_QA_BATCH_ID ?? ''
const QA_RATE = Number(process.env.FILINQ_E2E_QA_RATE ?? '5')
const CRON_RUNS = process.env.FILINQ_E2E_CRON_RUNS === '1'

/** The seeded folder: unique per run. */
const RUN = `scale-e2e-${Date.now()}`
const ROOT = `/${RUN}`
const SUBFOLDERS = ['besluiten', 'correspondentie', 'bijlagen']
const FILES_PER_SUBFOLDER = 2
const DIRECT_FILES = SYNC_CAP + 1

/** The file status a succeeded file carries. */
const SUCCEEDED = 'anonymized'

/** What the folder-batch endpoint answers. */
interface FolderBatch {
	batchId: string
	folderPath: string
	fileCount: number
	files: Array<{ fileId: number; fileName: string; status: string; relativePath?: string }>
	background: boolean
	batchStatus: string | null
}

/** One file of a batch as the status endpoint answers it. */
interface BatchFile {
	fileId: number
	fileName: string
	status: string
	anonymizedFileId?: number | null
	qaSampled?: boolean
	qaOutcome?: string
}

/** What the status endpoint answers. */
interface BatchStatus {
	batchId: string
	batchStatus: string
	files: BatchFile[]
	progress: number
	totalFiles: number
	background?: boolean
	throttled?: boolean
	progressPercent?: number
	documentsPerHour?: number | null
	filesSucceeded?: number
}

/** The batches this run started, cancelled when it ends. */
const started: { flat: string; recursive: string } = { flat: '', recursive: '' }

/**
 * Write one file over WebDAV.
 *
 * @param request The authenticated request context.
 * @param path The path below the user's root, with a leading slash.
 * @param body The file's content.
 * @return Resolves once the file is written.
 */
async function davPut(request: APIRequestContext, path: string, body: string): Promise<void> {
	const res = await request.put(`/remote.php/dav/files/${USER}${path}`, { headers: OCS, data: body })
	expect([201, 204], `PUT ${path} -> ${res.status()}`).toContain(res.status())
}

/**
 * Make one folder over WebDAV.
 *
 * @param request The authenticated request context.
 * @param path The path below the user's root, with a leading slash.
 * @return Resolves once the folder exists.
 */
async function davMkcol(request: APIRequestContext, path: string): Promise<void> {
	const res = await request.fetch(`/remote.php/dav/files/${USER}${path}`, { method: 'MKCOL', headers: OCS })
	expect([201, 405], `MKCOL ${path} -> ${res.status()}`).toContain(res.status())
}

/**
 * Seed the folder: one file more than the synchronous cap directly, plus
 * three subfolders of two files each.
 *
 * @param request The authenticated request context.
 * @return Resolves once every file is written.
 */
async function seedFolder(request: APIRequestContext): Promise<void> {
	await davMkcol(request, ROOT)
	for (let i = 1; i <= DIRECT_FILES; i++) {
		await davPut(request, `${ROOT}/brief-${i}.txt`, `Brief ${i} aan J. de Vries, jan.devries${i}@example.nl.\n`)
	}
	for (const sub of SUBFOLDERS) {
		await davMkcol(request, `${ROOT}/${sub}`)
		for (let i = 1; i <= FILES_PER_SUBFOLDER; i++) {
			await davPut(request, `${ROOT}/${sub}/stuk-${i}.txt`, `Stuk ${i} in ${sub}, kenmerk ${RUN}.\n`)
		}
	}
}

/**
 * Create a folder batch from the seeded folder.
 *
 * @param request The authenticated request context.
 * @param recursive Whether subfolders are included.
 * @return The created batch.
 */
async function createFolderBatch(request: APIRequestContext, recursive: boolean): Promise<FolderBatch> {
	const data: Record<string, unknown> = { folderPath: ROOT }
	if (recursive) {
		data.recursive = true
	}
	const res = await request.post(`${BATCH_API}/folder`, { headers: OCS, data })
	expect(res.status(), `folder batch -> ${await res.text().catch(() => '')}`).toBe(200)

	return res.json()
}

/**
 * Read a batch through the status endpoint.
 *
 * @param request The authenticated request context.
 * @param batchId The batch.
 * @return The status answer.
 */
async function batchStatus(request: APIRequestContext, batchId: string): Promise<BatchStatus> {
	const res = await request.get(`${BATCH_API}/${batchId}/status`, { headers: OCS })
	expect(res.status(), `status ${batchId} -> ${res.status()}`).toBe(200)

	return res.json()
}

/**
 * Poll a batch until its status is one of `wanted`, or the budget runs out.
 *
 * @param request The authenticated request context.
 * @param batchId The batch.
 * @param wanted The statuses to wait for.
 * @param budgetMs How long to wait, in ms.
 * @return The last status read.
 */
async function waitForStatus(
	request: APIRequestContext,
	batchId: string,
	wanted: string[],
	budgetMs: number,
): Promise<BatchStatus> {
	let last = await batchStatus(request, batchId)
	await expect
		.poll(
			async () => {
				last = await batchStatus(request, batchId)
				return last.batchStatus
			},
			{ timeout: budgetMs, intervals: [15_000] },
		)
		.toMatch(new RegExp(`^(${wanted.join('|')})$`))

	return last
}

/**
 * The reviewed entity list of a batch, as the review step reads it.
 *
 * @param request The authenticated request context.
 * @param batchId The batch.
 * @return The consolidated entities.
 */
async function batchEntities(request: APIRequestContext, batchId: string): Promise<unknown[]> {
	const res = await request.get(`${BATCH_API}/${batchId}/entities`, { headers: OCS })
	expect(res.status(), `entities ${batchId} -> ${res.status()}`).toBe(200)
	const entities = (await res.json()).entities

	expect(Array.isArray(entities) && entities.length > 0, 'the batch has reviewed entities').toBe(true)
	return entities
}

/**
 * Cancel a batch through the API, ignoring a batch that is already over.
 *
 * @param request The authenticated request context.
 * @param batchId The batch.
 * @return Resolves once the cancel was asked.
 */
async function cancelQuietly(request: APIRequestContext, batchId: string): Promise<void> {
	if (batchId === '') {
		return
	}
	await request.post(`${BATCH_API}/${batchId}/cancel`, { headers: OCS }).catch(() => null)
}

/**
 * Record on the report that the coordinator-dependent tail of a test did not run.
 *
 * @param why What would not happen without the cron coordinator.
 * @return Nothing.
 */
function noteCronSkipped(why: string): void {
	test.info().annotations.push({ type: 'skipped-tail', description: `FILINQ_E2E_CRON_RUNS is not 1: ${why}` })
}

test.describe('redaction at scale: the seeded folder', () => {
	test.describe.configure({ mode: 'serial' })
	test.setTimeout(900_000)

	test.beforeAll(async ({ request }) => {
		test.setTimeout(600_000)
		await seedFolder(request)
	})

	test.afterAll(async ({ request }) => {
		await cancelQuietly(request, started.flat)
		await cancelQuietly(request, started.recursive)
		await request.delete(`/remote.php/dav/files/${USER}${ROOT}`, { headers: OCS }).catch(() => null)
	})

	test('a folder above the synchronous cap is accepted and marked for background processing', async ({ request }) => {
		const batch = await createFolderBatch(request, false)
		started.flat = batch.batchId

		expect(batch.fileCount, 'a flat scan holds only the direct files').toBe(DIRECT_FILES)
		expect(batch.background, 'above the synchronous cap the batch goes to the background').toBe(true)
		expect(batch.batchStatus).toBe('extracting')

		const status = await batchStatus(request, batch.batchId)
		expect(status.totalFiles).toBe(DIRECT_FILES)
		expect(status.background).toBe(true)
	})

	test('a recursive dossier folder becomes one batch with relative paths, routed to the background', async ({ request }) => {
		const batch = await createFolderBatch(request, true)
		started.recursive = batch.batchId

		const nested = SUBFOLDERS.length * FILES_PER_SUBFOLDER
		expect(batch.fileCount).toBe(DIRECT_FILES + nested)
		expect(batch.background).toBe(true)

		const paths = batch.files.map((file) => file.relativePath ?? '')
		expect(paths.every((path) => path !== ''), 'every file records its path below the batch root').toBe(true)
		for (const sub of SUBFOLDERS) {
			for (let i = 1; i <= FILES_PER_SUBFOLDER; i++) {
				expect(paths).toContain(`${sub}/stuk-${i}.txt`)
			}
		}
		expect(paths).toContain('brief-1.txt')

		const stored = await request.get(batchObject(batch.batchId), { headers: OCS })
		expect(stored.status()).toBe(200)
		expect((await stored.json()).recursive, 'the batch record says it was recursive').toBe(true)
	})

	test('the operator follows the batch on the Batch operations screen while it polls', async ({ page }) => {
		test.skip(started.flat === '', 'the flat batch was not created')

		await go(page, 'batch-operations')
		await expect(page).toHaveURL(/\/batch-operations/)
		const row = page.locator('#content tr, .app-content tr').filter({ hasText: RUN }).first()
		await expect(row, 'the seeded batch is listed for its owner').toBeVisible({ timeout: 30_000 })

		const firstPoll = page.waitForRequest((r) => r.url().includes(`/anonymization/batch/${started.flat}/status`))
		await go(page, `batch-operations/${started.flat}`)
		await firstPoll

		await expect(page.getByTestId('batch-phase')).toHaveText(/extracting|cancelling|review|anonymizing/)
		await expect(page.getByTestId('batch-progress')).toHaveText(/^\s*\d+(\.\d+)?%\s*$/)
		await expect(page.getByTestId('batch-throughput')).toHaveText(/^\s*(—|\d+(\.\d+)?)\s*$/)

		// The view polls a running batch every ten seconds: a second read proves it keeps updating.
		await page.waitForRequest((r) => r.url().includes(`/anonymization/batch/${started.flat}/status`), {
			timeout: 30_000,
		})
	})

	test('cancel through the confirmation; the batch stops between files', async ({ page, request }) => {
		test.skip(started.flat === '', 'the flat batch was not created')

		await go(page, `batch-operations/${started.flat}`)
		await page.getByTestId('batch-cancel').click()
		const dialog = page.getByRole('dialog', { name: 'Cancel this batch?' })
		await expect(dialog).toBeVisible()
		const cancelled = page.waitForResponse((r) => r.url().endsWith(`/anonymization/batch/${started.flat}/cancel`))
		await dialog.getByRole('button', { name: 'Cancel batch' }).click()
		const answer = await cancelled
		expect(answer.status()).toBe(202)
		expect(['cancelling', 'cancelled']).toContain((await answer.json()).batchStatus)

		await expect(page.getByTestId('batch-phase')).toHaveText(/cancelling|cancelled/)

		if (!CRON_RUNS) {
			noteCronSkipped('no coordinator tick will land the cancel')
			return
		}
		const ended = await waitForStatus(request, started.flat, ['cancelled'], 600_000)
		const done = ended.files.filter((file) => file.status !== 'uploaded').length

		// No further file starts once the batch is cancelled.
		await page.waitForTimeout(30_000)
		const later = await batchStatus(request, started.flat)
		expect(later.batchStatus).toBe('cancelled')
		expect(later.files.filter((file) => file.status !== 'uploaded').length).toBe(done)
	})
})

test.describe('redaction at scale: prepared batches', () => {
	test.setTimeout(900_000)

	test('a review batch above the synchronous cap is enqueued with 202 and its entity list persisted', async ({ request }) => {
		test.skip(REVIEW_BATCH_ID === '', 'FILINQ_E2E_REVIEW_BATCH_ID is not set: no reviewed batch above the cap on this instance')

		const before = await batchStatus(request, REVIEW_BATCH_ID)
		expect(before.batchStatus, 'the fixture is a batch in review').toBe('review')
		expect(before.files.length, 'the fixture is above the synchronous cap').toBeGreaterThan(SYNC_CAP)

		const entities = await batchEntities(request, REVIEW_BATCH_ID)
		const res = await request.post(`${BATCH_API}/${REVIEW_BATCH_ID}/anonymize`, { headers: OCS, data: { entities } })
		const body = await res.json()
		expect(res.status(), JSON.stringify(body)).toBe(202)
		expect(body.background).toBe(true)
		expect(body.batchStatus).toBe('anonymizing')
		expect(String(body.statusLocation)).toMatch(new RegExp(`/apps/filinq/api/anonymization/batch/${REVIEW_BATCH_ID}/status$`))

		const stored = await request.get(batchObject(REVIEW_BATCH_ID), { headers: OCS })
		expect(stored.status()).toBe(200)
		const record = await stored.json()
		expect(record.anonymizeRequest?.entities?.length, 'the reviewed list is on the batch record').toBe(entities.length)

		const after = await batchStatus(request, REVIEW_BATCH_ID)
		expect(['anonymizing', 'completed', 'error']).toContain(after.batchStatus)

		if (!CRON_RUNS) {
			noteCronSkipped('the background work units will not run while the test waits')
			return
		}
		const ended = await waitForStatus(request, REVIEW_BATCH_ID, ['completed', 'error'], 840_000)
		expect(ended.batchStatus).toBe('completed')
		expect(ended.files.filter((file) => file.status === SUCCEEDED).length).toBeGreaterThan(0)
	})

	test('resume re-queues only the files that did not succeed, and duplicates nothing', async ({ request }) => {
		test.skip(ERRORED_BATCH_ID === '', 'FILINQ_E2E_ERRORED_BATCH_ID is not set: no errored batch on this instance')

		const before = await batchStatus(request, ERRORED_BATCH_ID)
		expect(before.batchStatus, 'the fixture is an errored batch').toBe('error')
		const succeeded = new Map(
			before.files.filter((file) => file.status === SUCCEEDED).map((file) => [file.fileId, file.anonymizedFileId]),
		)
		const notSucceeded = before.files.length - succeeded.size
		expect(succeeded.size, 'the fixture has succeeded files').toBeGreaterThan(0)
		expect(notSucceeded, 'the fixture has files left to do').toBeGreaterThan(0)

		const res = await request.post(`${BATCH_API}/${ERRORED_BATCH_ID}/resume`, { headers: OCS })
		const body = await res.json()
		expect(res.status(), JSON.stringify(body)).toBe(202)
		expect(body.requeued).toBe(notSucceeded)
		expect(['review', 'extracting', 'anonymizing']).toContain(body.batchStatus)

		const after = await batchStatus(request, ERRORED_BATCH_ID)
		for (const file of after.files.filter((f) => succeeded.has(f.fileId))) {
			expect(file.status, `${file.fileName} stays succeeded`).toBe(SUCCEEDED)
			expect(file.anonymizedFileId).toBe(succeeded.get(file.fileId))
		}

		if (!CRON_RUNS) {
			noteCronSkipped('the resumed batch will not complete while the test waits')
			return
		}
		const ended = await waitForStatus(request, ERRORED_BATCH_ID, ['completed', 'error'], 840_000)
		expect(ended.batchStatus).toBe('completed')
		expect(ended.files.every((file) => file.status === SUCCEEDED)).toBe(true)
		const outputs = ended.files.map((file) => file.anonymizedFileId)
		expect(new Set(outputs).size, 'every file has its own single output').toBe(outputs.length)
		for (const [fileId, output] of succeeded) {
			expect(ended.files.find((file) => file.fileId === fileId)?.anonymizedFileId).toBe(output)
		}
	})

	test('a completed background batch carries its QA sample, pending on the report and on the screen', async ({ page, request }) => {
		test.skip(QA_BATCH_ID === '', 'FILINQ_E2E_QA_BATCH_ID is not set: no completed background batch on this instance')

		const status = await batchStatus(request, QA_BATCH_ID)
		expect(status.batchStatus).toBe('completed')
		const succeeded = status.files.filter((file) => file.status === SUCCEEDED)
		const expected = Math.max(1, Math.round((succeeded.length * QA_RATE) / 100))
		const sampled = status.files.filter((file) => file.qaSampled === true)
		expect(sampled.length, `${QA_RATE}% of ${succeeded.length} succeeded files`).toBe(expected)
		expect(sampled.every((file) => file.status === SUCCEEDED), 'only succeeded files are sampled').toBe(true)

		// Each sample is in the review queue: its review state reads, and nobody checked it yet.
		for (const file of sampled) {
			const review = await request.get(reviewApi(file.fileId), { headers: OCS })
			expect(review.status()).toBe(200)
			expect((await review.json()).checked, `${file.fileName} waits for review`).toBe(false)
		}

		const report = await request.get(`${BATCH_API}/${QA_BATCH_ID}/report`, { headers: OCS })
		expect(report.status()).toBe(200)
		const rows = (await report.text()).split('\n').filter((line) => line.startsWith('qaSample,'))
		expect(rows.length).toBe(expected)
		expect(rows.every((line) => line.trim().endsWith(',pending')), rows.join('\n')).toBe(true)

		await go(page, `batch-operations/${QA_BATCH_ID}`)
		await expect(page.getByTestId('batch-qa-sample')).toHaveCount(expected)
		await expect(page.getByTestId('batch-qa-sample').first()).toContainText('Awaiting review')
	})
})
