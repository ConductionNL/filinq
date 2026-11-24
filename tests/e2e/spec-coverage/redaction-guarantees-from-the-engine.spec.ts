/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * redaction-guarantees-from-the-engine, task 6: with OpenRegister's
 * `anonymisation.requireReview` on, a batch with one undecided document shows
 * the engine's refusal for that document and writes no copy of it; the
 * reviewer decides it, marks it reviewed in the workbench, runs the batch
 * again, and the document's anonymizationLink reads `clean`.
 *
 * NOT BUILT YET. No task of this change is ticked, and OpenRegister has no
 * `anonymisation.requireReview`, no review-mark route and no `verification` on
 * its anonymise result at the time of writing. This test is written from the
 * spec and the proposal's cross-app contract and WILL FAIL until both land:
 * today filinq's own checked gate refuses the whole batch with
 * `documents_not_reviewed` before the engine is asked, and no link carries a
 * `verificationVerdict` from the engine.
 *
 * WHAT IT READS FROM THE INSTANCE. A batch in review with one document whose
 * detection nobody decided cannot be made from the browser: it needs real
 * files through detection. The live pass prepares it:
 *   - `FILINQ_E2E_ENGINE_BATCH_ID`: a batch in status `review` in which every
 *     document but one is decided and marked reviewed.
 *   - `FILINQ_E2E_ENGINE_FILE_ID` and `FILINQ_E2E_ENGINE_FILE_PATH`: the one
 *     undecided document, its file id and its path in the admin's files.
 *   - `FILINQ_E2E_ENGINE_REQUIRE_REVIEW=1`: confirms the live pass switched
 *     OpenRegister's `anonymisation.requireReview` on (OpenRegister has no
 *     route for that setting yet, so the test cannot switch it itself).
 * Without them the test skips and says why. It anonymises the batch, so the
 * fixture is used up.
 *
 * EXCLUDED, as the spec marks them `@e2e exclude`: the leaking verdict, the
 * older OpenRegister, the verifier source scan, the review mark reaching the
 * engine, the term moving to the engine and the inexpressible rule. PHPUnit
 * covers them.
 *
 * Not run in the change that wrote it (decision 139): the live pass runs it.
 */

// @e2e openspec/changes/redaction-guarantees-from-the-engine/specs/redaction-output-guarantee/spec.md#an-unchecked-document-is-refused-by-the-engine-and-filinq-says-why

import type { APIRequestContext } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { go } from './_helpers.ts'

/** The batch endpoints. */
const BATCH_API = '/index.php/apps/filinq/api/anonymization/batch'

/** One document's detection, and one relation's include or skip. */
const extractApi = (fileId: string) => `/index.php/apps/filinq/api/anonymization/extract/${fileId}`
const relationApi = (id: number) => `/index.php/apps/filinq/api/anonymization/relations/${id}`

/** The anonymizationLink records in OpenRegister. */
const LINKS_API = '/index.php/apps/openregister/api/objects/filinq/anonymizationLink'

/** The OCS header every call carries. */
const OCS = { 'OCS-APIRequest': 'true' }

/** Fixtures the live pass prepares (see the header). */
const BATCH_ID = process.env.FILINQ_E2E_ENGINE_BATCH_ID ?? ''
const FILE_ID = process.env.FILINQ_E2E_ENGINE_FILE_ID ?? ''
const FILE_PATH = process.env.FILINQ_E2E_ENGINE_FILE_PATH ?? ''
const REQUIRE_REVIEW = process.env.FILINQ_E2E_ENGINE_REQUIRE_REVIEW === '1'

/** One entry of a batch result's per-document list. */
interface DocumentOutcome {
	fileId: number | string
	reason?: string
	message?: string
	error?: string
}

/**
 * The reviewed entity list of the batch.
 *
 * @param request The authenticated request context.
 * @return The consolidated entities.
 */
async function batchEntities(request: APIRequestContext): Promise<unknown[]> {
	const res = await request.get(`${BATCH_API}/${BATCH_ID}/entities`, { headers: OCS })
	expect(res.status()).toBe(200)
	const entities = (await res.json()).entities
	expect(Array.isArray(entities) && entities.length > 0, 'the batch has reviewed entities').toBe(true)

	return entities
}

/**
 * Start the batch anonymisation.
 *
 * @param request The authenticated request context.
 * @param entities The reviewed entity list.
 * @return The status and body of the answer.
 */
async function runBatch(request: APIRequestContext, entities: unknown[]): Promise<{ status: number; body: any }> {
	const res = await request.post(`${BATCH_API}/${BATCH_ID}/anonymize`, { headers: OCS, data: { entities } })

	return { status: res.status(), body: await res.json().catch(() => ({})) }
}

/**
 * The anonymizationLink of a source file, or null.
 *
 * @param request The authenticated request context.
 * @param fileId The source file.
 * @return The link record.
 */
async function linkOf(request: APIRequestContext, fileId: string): Promise<any | null> {
	const res = await request.get(LINKS_API, { headers: OCS, params: { sourceFileId: fileId } })
	expect(res.status()).toBe(200)
	const rows = (await res.json()).results ?? []

	return rows.find((row: { sourceFileId?: number | string }) => String(row.sourceFileId) === fileId) ?? null
}

/**
 * The relation ids of the document's detections.
 *
 * @param request The authenticated request context.
 * @return Every relation id the detection stored.
 */
async function relationIds(request: APIRequestContext): Promise<number[]> {
	const res = await request.post(extractApi(FILE_ID), { headers: OCS, data: {} })
	expect(res.status()).toBe(200)
	const ids: number[] = []
	for (const entity of (await res.json()).entities ?? []) {
		const own = Array.isArray(entity.relationIds) ? entity.relationIds : [entity.relationId]
		ids.push(...own.filter((id: unknown) => id !== null && id !== undefined).map(Number))
	}
	expect(ids.length, 'the undecided document has detections').toBeGreaterThan(0)

	return ids
}

/**
 * The per-document outcome a batch result reports for a file, or null.
 *
 * @param body The batch result.
 * @param fileId The file.
 * @return The outcome entry.
 */
function outcomeFor(body: any, fileId: string): DocumentOutcome | null {
	const lists = [body.skippedFiles, body.refusedFiles, body.files].filter(Array.isArray) as DocumentOutcome[][]
	for (const list of lists) {
		const hit = list.find((entry) => String(entry.fileId) === fileId)
		if (hit) {
			return hit
		}
	}

	return null
}

test.describe('the engine enforces the review, filinq shows why', () => {
	test.setTimeout(300_000)

	test('an undecided document is refused by the engine in a batch, then passes once decided and reviewed', async ({ page, request }) => {
		test.skip(
			BATCH_ID === '' || FILE_ID === '' || FILE_PATH === '',
			'FILINQ_E2E_ENGINE_BATCH_ID / FILINQ_E2E_ENGINE_FILE_ID / FILINQ_E2E_ENGINE_FILE_PATH are not set: no prepared batch on this instance',
		)
		test.skip(!REQUIRE_REVIEW, 'FILINQ_E2E_ENGINE_REQUIRE_REVIEW is not 1: OpenRegister\'s anonymisation.requireReview is not known to be on')

		const entities = await batchEntities(request)

		await test.step('the batch runs, and the engine refuses the undecided document only', async () => {
			const first = await runBatch(request, entities)
			expect(first.body.reason, 'filinq no longer refuses the whole batch itself').not.toBe('documents_not_reviewed')
			expect(first.status, JSON.stringify(first.body)).not.toBe(409)

			const outcome = outcomeFor(first.body, FILE_ID)
			expect(outcome, `the result names document ${FILE_ID}: ${JSON.stringify(first.body)}`).not.toBeNull()
			const message = String(outcome?.message ?? outcome?.error ?? outcome?.reason ?? '')
			expect(message.trim(), 'filinq passes on the engine\'s message for that document').not.toBe('')

			const link = await linkOf(request, FILE_ID)
			expect(link?.anonymizedFileId ?? null, 'no redacted copy of the refused document was written').toBeNull()
		})

		await test.step('the reviewer decides every detection and marks the document reviewed in the workbench', async () => {
			for (const id of await relationIds(request)) {
				const res = await request.patch(relationApi(id), { headers: OCS, data: { skipAnonymization: false } })
				expect(res.status(), `include relation ${id}`).toBe(200)
			}

			const name = FILE_PATH.split('/').pop() ?? ''
			await go(page, `review/${FILE_ID}?name=${encodeURIComponent(name)}&path=${encodeURIComponent(FILE_PATH)}`)
			const status = page.getByTestId('review-status')
			await expect(page.getByTestId('mark-reviewed')).toBeEnabled({ timeout: 60_000 })
			for (const confirm of await page.getByTestId('confirm-uncertain').all()) {
				await confirm.click()
			}
			await page.getByTestId('mark-reviewed').click()
			await expect(status).toContainText('This document is marked as reviewed.', { timeout: 30_000 })
		})

		await test.step('the batch runs again and the link reads the engine\'s clean verdict', async () => {
			const again = await runBatch(request, entities)
			expect([200, 202], JSON.stringify(again.body)).toContain(again.status)
			expect(outcomeFor(again.body, FILE_ID)?.reason ?? null, 'the document is no longer refused').toBeNull()

			await expect
				.poll(async () => (await linkOf(request, FILE_ID))?.verificationVerdict ?? null, { timeout: 180_000, intervals: [10_000] })
				.toBe('clean')
			const link = await linkOf(request, FILE_ID)
			expect(link.anonymizedFileId, 'the redacted copy now exists').toBeTruthy()
			expect(link.verifiedAt, 'the verdict carries when the engine verified it').toBeTruthy()
		})
	})
})
