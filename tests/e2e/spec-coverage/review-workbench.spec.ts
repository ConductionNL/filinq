/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * anonymization-review-workbench, tasks 4.2 and 5.4: the review workbench at
 * `/review/{fileId}` shows the original beside the anonymised result with the
 * findings table as the decision panel; org rules pre-apply with badges that
 * link to the rule lists; grondslagen are proposed and overridable; a text
 * selection becomes a manual entity; the reviewed mark is set, voided by a
 * later edit, and gates the commit; a Word file is reviewed as its PDF
 * rendition; and the threshold panel shows both confidence numbers.
 *
 * WHAT IT SEEDS ITSELF. The mark, the commit gate and the selection tests
 * share one text document the spec uploads into `/DocuDesk`, with a BSN, an
 * e-mail address and two names in it, so the default detection finds
 * something and a selection has text to match. The grondslag-on-a-rule test
 * creates its own prohibition (prohibitions are archival and cannot be
 * deleted, so it is deactivated afterwards) and the new-rule test creates and
 * deletes its own standing consent. Settings a test changes (the checked-gate
 * mode, `certain_from`, OpenRegister's detection floor) are put back.
 *
 * WHAT IT READS FROM THE INSTANCE. Pre-application, the filter, the BSN
 * proposal, the converted Word file and the thresholds need documents through
 * a real detection backend, which a test cannot make. The live pass prepares:
 *   - `FILINQ_E2E_REVIEW_FILE_ID` / `FILINQ_E2E_REVIEW_FILE_PATH`: an extracted
 *     PDF with no committed anonymisation, in which detection found "Jan
 *     Jansen" (PERSON), an ORGANIZATION containing "Utrecht", "Beschermde
 *     Getuige A" and "Gemeente Voorbeeldstad" (matched by the register seed's
 *     prohibition and standing consent), "Stichting Voorbeeld"
 *     (ORGANIZATION, matched by no rule) and a BSN. The BSN test also needs
 *     the admin mapping `filinq.grondslagen.entity_type_bases` to propose a
 *     ground for BSN.
 *   - `FILINQ_E2E_ANONYMIZED_FILE_ID` / `FILINQ_E2E_ANONYMIZED_FILE_PATH`: a
 *     document with a committed anonymisation (an anonymizationLink).
 *   - `FILINQ_E2E_DOCX_FILE_ID` / `FILINQ_E2E_DOCX_FILE_PATH` /
 *     `FILINQ_E2E_DOCX_NAME`: an extracted `.docx` and the name detected in it.
 *   - `FILINQ_E2E_THRESHOLD_FILE_ID` / `FILINQ_E2E_THRESHOLD_FILE_PATH`: an
 *     extracted document with findings both below and above 0.9 (and above
 *     0.6), such as 0.65 and 0.95.
 *   - `FILINQ_E2E_CHECKED_BATCH_ID`: a batch in review, at or below the
 *     synchronous cap, every file marked reviewed. The test anonymises it, so
 *     it is used up.
 * Without them those tests skip and say why.
 *
 * WHERE THE SPEC AND THE CODE DISAGREE, and the test follows the spec, so it
 * fails until the code catches up:
 *   - "Toggling from the preview": the workbench passes the viewers no
 *     findings, so there is no highlighted occurrence to click and no reject
 *     choice in the original pane. That test will fail until it is built.
 *   - "Search and type filter": the summary line counts selected findings out
 *     of all (`{selected} of {total} entities selected`), not the filtered
 *     count out of the total the spec names.
 * And two where the test follows the code, because the spec's wording is stale:
 *   - Grounds are stored as their code (`5.1.2.e`), since
 *     grondslagen-read-from-dossiq, not as the slug `woo-art-5-1-2-e`.
 *   - A reviewer's ground travels on the relation write
 *     (`PATCH /api/anonymization/relations/{id}` with `bases`), which the
 *     anonymise call then reads; the anonymise body itself carries no bases
 *     (a stray one is ignored and reported).
 *
 * EXCLUDED, as the spec marks them `@e2e exclude`: the zero-match selection,
 * the prohibition-over-consent conflict, the rule without bases, the batch
 * refused for one unchecked file, advisory mode, the scan's OCR text layer,
 * the changed rendition, no text layer, the undecided uncertain finding,
 * certain-from below the floor, the three mountCompare scenarios, and the
 * entity-review response-shape scenarios. PHPUnit, Vitest, Jest and Newman
 * cover them.
 *
 * Not run in the change that wrote it (decision 139): the live pass runs it.
 */

// @e2e openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#reviewer-opens-the-workbench-for-an-extracted-document
// @e2e openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#anonymized-result-appears-after-a-commit
// @e2e openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#toggling-from-the-preview-updates-the-table
// @e2e openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#search-and-type-filter-operate-in-the-workbench
// @e2e openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#reviewer-creates-a-manual-entity-from-a-selection
// @e2e openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#prohibited-entity-arrives-pre-included-with-a-badge
// @e2e openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#standing-consent-entity-arrives-pre-excluded-with-a-badge
// @e2e openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#admin-adds-a-grondslag-to-a-prohibition-rule
// @e2e openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#rule-bases-pre-fill-the-matched-entitys-grondslag
// @e2e openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#proposal-appears-pre-filled-for-a-detected-bsn
// @e2e openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#reviewer-override-wins
// @e2e openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#reviewer-marks-a-document-as-checked
// @e2e openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#editing-entities-invalidates-the-check
// @e2e openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#single-file-commit-blocked-while-unchecked
// @e2e openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#reviewer-navigates-from-a-badge-to-the-rule-list
// @e2e openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#new-rule-applies-to-the-next-review
// @e2e openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#a-word-document-is-reviewed-as-a-pdf-and-the-link-says-so
// @e2e openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#an-organisation-sets-its-thresholds
// @e2e openspec/changes/anonymization-review-workbench/specs/anonymization-entity-review/spec.md#fully-checked-batch-proceeds

import type { APIRequestContext, Locator, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { go } from './_helpers.ts'

/** filinq endpoints. */
const API = '/index.php/apps/filinq/api'
const SETTINGS_API = `${API}/settings`
const PROHIBITIONS_API = `${API}/policy/prohibitions`
const CONSENTS_API = `${API}/policy/standing-consents`
const BATCH_API = `${API}/anonymization/batch`
const reviewApi = (fileId: number | string) => `${API}/review/${fileId}`
const extractApi = (fileId: number | string) => `${API}/anonymization/extract/${fileId}`
const anonymizeApi = (fileId: number | string) => `${API}/anonymization/anonymize/${fileId}`
const relationApi = (id: number | string) => `${API}/anonymization/relations/${id}`

/** OpenRegister endpoints. */
const LINKS_API = '/index.php/apps/openregister/api/objects/filinq/anonymizationLink'
const OR_FILE_SETTINGS = '/index.php/apps/openregister/api/settings/files'

/** The OCS header every call carries. */
const OCS = { 'OCS-APIRequest': 'true' }

/** The signed-in user. */
const USER = process.env.NC_ADMIN_USER ?? 'admin'

/** The admin mapping from entity type to proposed grounds. */
const MAPPING_KEY = 'filinq.grondslagen.entity_type_bases'

/** The ground the rule form picks. */
const GROUND = '5.1.2.e'

/** Fixtures the live pass prepares (see the header). */
const REVIEW_FILE_ID = process.env.FILINQ_E2E_REVIEW_FILE_ID ?? ''
const REVIEW_FILE_PATH = process.env.FILINQ_E2E_REVIEW_FILE_PATH ?? ''
const ANON_FILE_ID = process.env.FILINQ_E2E_ANONYMIZED_FILE_ID ?? ''
const ANON_FILE_PATH = process.env.FILINQ_E2E_ANONYMIZED_FILE_PATH ?? ''
const DOCX_FILE_ID = process.env.FILINQ_E2E_DOCX_FILE_ID ?? ''
const DOCX_FILE_PATH = process.env.FILINQ_E2E_DOCX_FILE_PATH ?? ''
const DOCX_NAME = process.env.FILINQ_E2E_DOCX_NAME ?? ''
const THRESHOLD_FILE_ID = process.env.FILINQ_E2E_THRESHOLD_FILE_ID ?? ''
const THRESHOLD_FILE_PATH = process.env.FILINQ_E2E_THRESHOLD_FILE_PATH ?? ''
const CHECKED_BATCH_ID = process.env.FILINQ_E2E_CHECKED_BATCH_ID ?? ''

/** Why a rich-fixture test skips. */
const NO_REVIEW_FILE = 'FILINQ_E2E_REVIEW_FILE_ID / FILINQ_E2E_REVIEW_FILE_PATH are not set: no extracted PDF with the named findings on this instance'

/** The seeded text document (see the header). */
const RUN = `workbench-e2e-${Date.now()}`
const SEED_NAME = `${RUN}.txt`
const SEED_PATH = `/DocuDesk/${SEED_NAME}`
const SELECTED = 'Pieter de Vries'
const EDITED = 'Jansen-Holtrop'
const SEED_TEXT = [
	`Verslag ${RUN}.`,
	'',
	`Aanwezig: ${SELECTED} en mevrouw ${EDITED}.`,
	'Contact: pieter.devries@example.nl, BSN 111222333.',
	'',
].join('\n')

/** One finding of an extract answer. */
interface Finding {
	type: string
	value: string
	confidence?: number | string
	relationId?: number | null
	relationIds?: number[]
}

/** A policy rule as the policy endpoints answer it. */
interface Rule {
	id?: string
	uuid?: string
	primaryName?: string
	entityText?: string
	entityType?: string
	matchRules?: Array<{ type: string; value: string }>
	bases?: string[]
	[key: string]: unknown
}

/** The seeded document's id, set once uploaded. */
const seeded = { fileId: 0 }

/**
 * Upload the seeded text document into /DocuDesk and answer its file id.
 *
 * @param request The authenticated request context.
 * @return The file id.
 */
async function seedDocument(request: APIRequestContext): Promise<number> {
	const url = `/remote.php/dav/files/${USER}${SEED_PATH}`
	await request.fetch(`/remote.php/dav/files/${USER}/DocuDesk`, { method: 'MKCOL', headers: OCS }).catch(() => null)
	const put = await request.put(url, { headers: OCS, data: SEED_TEXT })
	expect([201, 204], `PUT ${SEED_PATH} -> ${put.status()}`).toContain(put.status())

	const find = await request.fetch(url, {
		method: 'PROPFIND',
		headers: { ...OCS, Depth: '0', 'Content-Type': 'application/xml' },
		data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns"><d:prop><oc:fileid/></d:prop></d:propfind>',
	})
	expect(find.status()).toBe(207)
	const match = /<oc:fileid>(\d+)<\/oc:fileid>/.exec(await find.text())
	expect(match, 'PROPFIND answers the file id').not.toBeNull()

	return Number(match![1])
}

/**
 * Run (or read back) a document's detection.
 *
 * @param request The authenticated request context.
 * @param fileId The document.
 * @param force Whether to re-run detection.
 * @return The findings.
 */
async function extract(request: APIRequestContext, fileId: number | string, force = false): Promise<Finding[]> {
	const res = await request.post(extractApi(fileId), { headers: OCS, data: force ? { force: true } : {} })
	expect(res.status(), `extract ${fileId} -> ${await res.text().catch(() => '')}`).toBe(200)

	return (await res.json()).entities ?? []
}

/**
 * Every relation id of a set of findings.
 *
 * @param findings The findings.
 * @return The relation ids.
 */
function relationIdsOf(findings: Finding[]): number[] {
	const ids: number[] = []
	for (const finding of findings) {
		const own = Array.isArray(finding.relationIds) ? finding.relationIds : [finding.relationId]
		ids.push(...own.filter((id): id is number => id !== null && id !== undefined).map(Number))
	}

	return ids
}

/**
 * Read a document's review state.
 *
 * @param request The authenticated request context.
 * @param fileId The document.
 * @return The status answer.
 */
async function reviewState(request: APIRequestContext, fileId: number | string): Promise<any> {
	const res = await request.get(reviewApi(fileId), { headers: OCS })
	expect(res.status(), `GET review ${fileId}`).toBe(200)

	return res.json()
}

/**
 * The anonymizationLink of a source file, or null.
 *
 * @param request The authenticated request context.
 * @param fileId The source file.
 * @return The link record.
 */
async function linkOf(request: APIRequestContext, fileId: number | string): Promise<any | null> {
	const res = await request.get(LINKS_API, { headers: OCS, params: { sourceFileId: String(fileId) } })
	expect(res.status()).toBe(200)
	const rows = (await res.json()).results ?? []

	return rows.find((row: { sourceFileId?: number | string }) => String(row.sourceFileId) === String(fileId)) ?? null
}

/**
 * filinq's settings, flat.
 *
 * @param request The authenticated request context.
 * @return The settings.
 */
async function readSettings(request: APIRequestContext): Promise<Record<string, any>> {
	const res = await request.get(SETTINGS_API, { headers: OCS })
	expect(res.status()).toBe(200)

	return res.json()
}

/**
 * Write filinq settings.
 *
 * @param request The authenticated request context.
 * @param data The keys to write.
 * @return Resolves once written.
 */
async function writeSettings(request: APIRequestContext, data: Record<string, unknown>): Promise<void> {
	const res = await request.post(SETTINGS_API, { headers: OCS, data })
	expect(res.status(), `POST settings ${JSON.stringify(data)} -> ${await res.text().catch(() => '')}`).toBe(200)
}

/**
 * The proposed grounds the admin mapping holds for one entity type.
 *
 * @param request The authenticated request context.
 * @param type The entity type.
 * @return The proposed ground codes, possibly none.
 */
async function proposalFor(request: APIRequestContext, type: string): Promise<string[]> {
	const raw = (await readSettings(request))[MAPPING_KEY]
	const mapping = typeof raw === 'string' ? JSON.parse(raw || '{}') : (raw ?? {})
	const value = mapping[type]
	if (Array.isArray(value)) {
		return value.filter((code: unknown) => typeof code === 'string' && code !== '')
	}

	return typeof value === 'string' && value !== '' ? [value] : []
}

/**
 * A list of policy rules.
 *
 * @param request The authenticated request context.
 * @param url The list endpoint.
 * @return The rules.
 */
async function listRules(request: APIRequestContext, url: string): Promise<Rule[]> {
	const res = await request.get(url, { headers: OCS })
	expect(res.status(), `GET ${url}`).toBe(200)
	const body = await res.json()

	return Array.isArray(body) ? body : (body.results ?? [])
}

/**
 * The active rule of a list that matches a text, or null.
 *
 * @param rules The rules.
 * @param text The text a match rule names.
 * @return The rule.
 */
function ruleMatching(rules: Rule[], text: string): Rule | null {
	const wanted = text.toLowerCase()

	return rules.find((rule) => rule.active !== false
		&& (rule.matchRules ?? []).some((m) => String(m.value).toLowerCase() === wanted)) ?? null
}

/**
 * The id of a rule.
 *
 * @param rule The rule.
 * @return Its uuid.
 */
function ruleId(rule: Rule): string {
	return String(rule.id ?? rule.uuid ?? '')
}

/**
 * Open the workbench for a document, as the file viewer's link does.
 *
 * @param page The page.
 * @param fileId The document.
 * @param path Its path in the user's files.
 * @param mime Its MIME type, when known.
 * @return Resolves once the findings table shows.
 */
async function openWorkbench(page: Page, fileId: number | string, path: string, mime = ''): Promise<void> {
	const name = path.split('/').pop() ?? ''
	const query = new URLSearchParams({ name, path })
	if (mime !== '') {
		query.set('mime', mime)
	}
	await go(page, `review/${fileId}?${query.toString()}`)
	await expect(page.getByTestId('review-workbench')).toBeVisible()
	await expect(page.locator('.entity-table')).toBeVisible({ timeout: 90_000 })
}

/**
 * The findings-table row of a value.
 *
 * @param page The page.
 * @param value The finding's value.
 * @return The row.
 */
function rowOf(page: Page, value: string): Locator {
	return page.locator('.entity-table tbody tr').filter({ hasText: value }).first()
}

/**
 * The include checkbox of a finding's row.
 *
 * @param page The page.
 * @param value The finding's value.
 * @return The checkbox.
 */
function includeBox(page: Page, value: string): Locator {
	return rowOf(page, value).getByRole('checkbox', { name: `Include ${value} in anonymisation` })
}

/**
 * The two numbers of the summary line: selected and total.
 *
 * @param page The page.
 * @return Selected and total.
 */
async function summary(page: Page): Promise<{ selected: number; total: number }> {
	const text = await page.locator('.summary-bar').innerText()
	const match = /(\d+) of (\d+) entities selected/.exec(text)
	expect(match, `summary line: ${text}`).not.toBeNull()

	return { selected: Number(match![1]), total: Number(match![2]) }
}

/**
 * Select a text inside the original pane, the way a reviewer drags over it,
 * and let the workbench capture it.
 *
 * @param page The page.
 * @param text The text to select.
 * @return Resolves once the selection is captured.
 */
async function selectInOriginal(page: Page, text: string): Promise<void> {
	const pane = page.getByTestId('document-compare-original')
	await expect(pane).toContainText(text, { timeout: 60_000 })
	const selected = await pane.evaluate((root, needle) => {
		const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT)
		for (let node = walker.nextNode(); node !== null; node = walker.nextNode()) {
			const at = (node.textContent ?? '').indexOf(needle)
			if (at >= 0) {
				const range = document.createRange()
				range.setStart(node, at)
				range.setEnd(node, at + needle.length)
				const selection = window.getSelection()
				selection?.removeAllRanges()
				selection?.addRange(range)
				return true
			}
		}
		return false
	}, text)
	expect(selected, `"${text}" is one text run in the original pane`).toBe(true)
	await pane.dispatchEvent('mouseup')
}

/**
 * Add the selected text as a PERSON entity through the modal.
 *
 * @param page The page.
 * @param text The selected text.
 * @return The manual-entities answer.
 */
async function addSelectionAsPerson(page: Page, text: string): Promise<any> {
	await selectInOriginal(page, text)
	await expect(page.getByTestId('add-selection')).toBeEnabled()
	await page.getByTestId('add-selection').click()

	const dialog = page.getByRole('dialog', { name: 'Add as entity' })
	await expect(dialog).toBeVisible()
	await expect(dialog.getByLabel('Selected text'), 'the modal is pre-filled with the selection').toHaveValue(text)
	await dialog.getByLabel('Entity type').click()
	// The table's type filter keeps its own (hidden) option list in the page: take the open one.
	await page.getByRole('option', { name: /^PERSON$/ }).filter({ visible: true }).click()

	const saved = page.waitForResponse((r) => r.url().includes('/manual-entities') && r.request().method() === 'POST')
	await dialog.getByRole('button', { name: 'Add as entity' }).click()
	const answer = await saved
	expect(answer.ok(), `manual entity -> ${answer.status()}`).toBeTruthy()

	return answer.json()
}

/**
 * Click every "Confirm" on an uncertain finding, so the mark is not refused for one.
 *
 * @param page The page.
 * @return Resolves once every uncertain finding is decided.
 */
async function confirmUncertain(page: Page): Promise<void> {
	// A confirmed row loses its button, so the list shrinks under the loop:
	// `.all()` hands out nth(i) locators and the second one then points past
	// the end. Click the first remaining one until none is left.
	const buttons = page.getByTestId('confirm-uncertain')
	for (let left = await buttons.count(), guard = 0; left > 0 && guard < 50; guard++) {
		await buttons.first().click()
		await expect(buttons).toHaveCount(left - 1)
		left = await buttons.count()
	}
}

test.describe('the workbench on a seeded document', () => {
	test.describe.configure({ mode: 'serial' })
	test.setTimeout(240_000)

	let findings: Finding[] = []

	test.beforeAll(async ({ request }) => {
		seeded.fileId = await seedDocument(request)
		findings = await extract(request, seeded.fileId)
		expect(findings.length, 'the default detection found the e-mail address and the BSN').toBeGreaterThan(0)
	})

	// The seeded document stays when the run ends: its name is unique per run,
	// and deleting a document with a review record is a worse failure than a
	// stray file.

	test('the original beside the pending placeholder, with the findings listed', async ({ page, request }) => {
		expect(await linkOf(request, seeded.fileId), 'nothing was committed for the seeded document').toBeNull()

		await openWorkbench(page, seeded.fileId, SEED_PATH, 'text/plain')

		const original = page.getByTestId('document-compare-original')
		await expect(original).toBeVisible()
		await expect(original).not.toContainText('This file type cannot be previewed.')
		await expect(original).toContainText(SELECTED, { timeout: 60_000 })

		await expect(page.getByTestId('document-compare-pending')).toHaveText(
			'Anonymisation has not been committed for this document yet.',
		)

		await expect(page.locator('.entity-table tbody tr')).toHaveCount(findings.length)
		expect((await summary(page)).total).toBe(findings.length)
	})

	test('an unchecked document is refused at commit with document_not_reviewed, and nothing is written', async ({ request }) => {
		const before = await reviewState(request, seeded.fileId)
		expect(before.checked, 'the seeded document is not marked yet').toBe(false)
		const mode = String(before.gateMode ?? 'enforced')
		if (mode !== 'enforced') {
			await writeSettings(request, { 'filinq.review.checked_gate': 'enforced' })
		}

		try {
			const entities = findings.map((f) => ({ type: f.type, value: f.value, confidence: f.confidence }))
			const res = await request.post(anonymizeApi(seeded.fileId), {
				headers: OCS,
				data: { entities, scope: 'document' },
			})
			const body = await res.json()
			expect(res.status(), JSON.stringify(body)).toBe(409)
			expect(body.reason).toBe('document_not_reviewed')
			expect(await linkOf(request, seeded.fileId), 'no anonymised file was produced').toBeNull()
		} finally {
			if (mode !== 'enforced') {
				await writeSettings(request, { 'filinq.review.checked_gate': mode })
			}
		}
	})

	test('"Mark as reviewed" stores the mark with the reviewer and the time, and the workbench says so', async ({ page, request }) => {
		await openWorkbench(page, seeded.fileId, SEED_PATH, 'text/plain')
		const status = page.getByTestId('review-status')
		await expect(status).toContainText('This document has not been marked as reviewed yet.')

		await confirmUncertain(page)
		const startedAt = Date.now()
		const marked = page.waitForResponse((r) => r.url().endsWith(`/api/review/${seeded.fileId}/check`) && r.request().method() === 'POST')
		await page.getByTestId('mark-reviewed').click()
		expect((await marked).status()).toBe(201)

		await expect(status).toContainText('This document is marked as reviewed.')
		await expect(page.getByTestId('unmark-reviewed')).toBeVisible()

		const state = await reviewState(request, seeded.fileId)
		expect(state.checked).toBe(true)
		expect(state.review.checkedBy).toBe(USER)
		const checkedOn = Date.parse(state.review.checkedOn)
		expect(checkedOn).toBeGreaterThan(startedAt - 120_000)
		expect(checkedOn).toBeLessThan(Date.now() + 120_000)
	})

	test('a selection in the original becomes a manual entity, pre-filled, with the proposed grondslag', async ({ page, request }) => {
		const proposed = await proposalFor(request, 'PERSON')
		await openWorkbench(page, seeded.fileId, SEED_PATH, 'text/plain')

		const answer = await addSelectionAsPerson(page, SELECTED)
		const matched = Number(answer.matchCount ?? (Array.isArray(answer.relations) ? answer.relations.length : 0))
		expect(matched, 'OpenRegister matched the selection in the extracted text').toBeGreaterThan(0)

		await expect(page.getByRole('dialog', { name: 'Add as entity' })).toBeHidden()
		const rows = page.locator('.entity-table tbody tr').filter({ hasText: SELECTED })
		await expect(rows, 'the new finding appears without a reload').toHaveCount(1)
		await expect(rows.first()).toContainText('PERSON')
		if (proposed.length > 0) {
			await expect(rows.first().locator('.bases-cell'), 'the row carries the PERSON proposal').toContainText(proposed[0])
		} else {
			test.info().annotations.push({ type: 'note', description: `${MAPPING_KEY} proposes no ground for PERSON on this instance` })
		}
	})

	test('adding a manual entity after the mark voids it and asks for a new review', async ({ page, request }) => {
		const current = await extract(request, seeded.fileId)
		const check = await request.post(`${reviewApi(seeded.fileId)}/check`, {
			headers: OCS,
			data: { decided: relationIdsOf(current) },
		})
		expect(check.status(), await check.text()).toBe(201)

		await openWorkbench(page, seeded.fileId, SEED_PATH, 'text/plain')
		const status = page.getByTestId('review-status')
		await expect(status).toContainText('This document is marked as reviewed.')

		await addSelectionAsPerson(page, EDITED)

		await expect(status).toContainText(
			'The findings changed after the last review. Review the document again before it is anonymised.',
			{ timeout: 30_000 },
		)
		await expect(page.getByTestId('mark-reviewed')).toHaveText(/Review again and mark as reviewed/)

		const state = await reviewState(request, seeded.fileId)
		expect(state.checked).toBe(false)
		expect(state.stale).toBe(true)
	})
})

test.describe('the workbench on prepared documents', () => {
	test.setTimeout(240_000)

	test('a committed run previews the anonymised file in the right pane', async ({ page, request }) => {
		test.skip(ANON_FILE_ID === '' || ANON_FILE_PATH === '', 'FILINQ_E2E_ANONYMIZED_FILE_ID / _PATH are not set: no committed anonymisation on this instance')

		const link = await linkOf(request, ANON_FILE_ID)
		expect(link?.anonymizedFileId, 'the fixture has an anonymizationLink').toBeTruthy()
		const anonymizedName = String(link.anonymizedFileName || String(link.anonymizedFilePath ?? '').split('/').pop())

		await openWorkbench(page, ANON_FILE_ID, ANON_FILE_PATH)
		const delivered = page.getByTestId('document-compare-delivered')
		await expect(delivered).toContainText(anonymizedName)
		await expect(page.getByTestId('document-compare-pending')).toHaveCount(0)
		await expect(delivered).not.toContainText('This file type cannot be previewed.')
	})

	test('rejecting a highlighted occurrence in the original pane unticks the row and lowers the counter', async ({ page }) => {
		test.skip(REVIEW_FILE_ID === '' || REVIEW_FILE_PATH === '', NO_REVIEW_FILE)

		await openWorkbench(page, REVIEW_FILE_ID, REVIEW_FILE_PATH)
		const box = includeBox(page, 'Jan Jansen')
		await expect(box).toBeChecked()
		const before = await summary(page)

		try {
			await page.getByTestId('document-compare-original').getByText('Jan Jansen').first().click()
			await page.getByRole('button', { name: /^Reject/ }).click()

			await expect(box).not.toBeChecked()
			await expect.poll(async () => (await summary(page)).selected).toBe(before.selected - 1)
		} finally {
			if (!(await box.isChecked())) {
				await box.click()
			}
		}
	})

	test('search and type filter narrow the table to ORGANIZATION findings containing "Utrecht"', async ({ page }) => {
		test.skip(REVIEW_FILE_ID === '' || REVIEW_FILE_PATH === '', NO_REVIEW_FILE)

		await openWorkbench(page, REVIEW_FILE_ID, REVIEW_FILE_PATH)
		const rows = page.locator('.entity-table tbody tr')
		const total = await rows.count()

		await page.getByLabel('Search entities').fill('Utrecht')
		await page.locator('.filter-bar select').selectOption('ORGANIZATION')

		const visible = await rows.count()
		expect(visible, 'an ORGANIZATION containing Utrecht was detected').toBeGreaterThan(0)
		expect(visible).toBeLessThan(total)
		for (const row of await rows.all()) {
			await expect(row).toContainText(/Utrecht/i)
			await expect(row.locator('.badge')).toHaveText('ORGANIZATION')
		}
		await expect(page.locator('.summary-bar'), 'the counter shows the filtered count out of the total').toContainText(`${visible} of ${total}`)
	})

	test('a prohibited entity arrives included with a badge naming the rule', async ({ page, request }) => {
		test.skip(REVIEW_FILE_ID === '' || REVIEW_FILE_PATH === '', NO_REVIEW_FILE)
		const rule = ruleMatching(await listRules(request, PROHIBITIONS_API), 'Beschermde Getuige A')
		test.skip(rule === null, 'no active prohibition matches "Beschermde Getuige A": the register seed is missing')

		await openWorkbench(page, REVIEW_FILE_ID, REVIEW_FILE_PATH)
		await expect(includeBox(page, 'Beschermde Getuige A')).toBeChecked()
		const badge = rowOf(page, 'Beschermde Getuige A').getByTestId('policy-badge-prohibition')
		await expect(badge).toHaveText(`Always anonymise: ${rule!.primaryName}`)
		await expect(badge).toHaveAttribute('href', new RegExp(`rule=${ruleId(rule!)}`))
	})

	test('a standing-consent entity arrives excluded with a badge, and can still be included', async ({ page, request }) => {
		test.skip(REVIEW_FILE_ID === '' || REVIEW_FILE_PATH === '', NO_REVIEW_FILE)
		const rule = ruleMatching(await listRules(request, CONSENTS_API), 'Gemeente Voorbeeldstad')
		test.skip(rule === null, 'no active standing consent matches "Gemeente Voorbeeldstad": the register seed is missing')

		await openWorkbench(page, REVIEW_FILE_ID, REVIEW_FILE_PATH)
		const box = includeBox(page, 'Gemeente Voorbeeldstad')
		await expect(box).not.toBeChecked()
		const badge = rowOf(page, 'Gemeente Voorbeeldstad').getByTestId('policy-badge-standingConsent')
		await expect(badge).toContainText('Never anonymise:')
		await expect(badge).toHaveAttribute('href', new RegExp(`rule=${ruleId(rule!)}`))

		try {
			await box.click()
			await expect(box, 'the reviewer may re-include it').toBeChecked()
		} finally {
			if (await box.isChecked()) {
				await box.click()
			}
		}
	})

	test('the admin adds a grondslag to a prohibition in its form, and it is stored and shown', async ({ page, request }) => {
		const name = `Workbench e2e ${Date.now()}`
		const created = await request.post(PROHIBITIONS_API, {
			headers: OCS,
			data: {
				primaryName: name,
				reason: 'review-workbench e2e fixture',
				entityType: 'PERSON',
				matchRules: [{ type: 'exact', value: name }],
				active: true,
			},
		})
		expect(created.status(), await created.text()).toBe(201)
		const rule: Rule = await created.json()
		expect(rule.bases ?? [], 'the new rule has no grondslag yet').toEqual([])

		try {
			await go(page, 'policy/prohibitions')
			const row = page.locator('#content tr, .app-content tr').filter({ hasText: name }).first()
			await expect(row).toBeVisible({ timeout: 30_000 })
			await row.getByRole('button', { name: /actions/i }).click()
			await page.getByRole('menuitem', { name: 'Edit' }).click()

			const dialog = page.getByRole('dialog', { name: 'Edit publish-never rule' })
			await expect(dialog).toBeVisible()
			await dialog.getByTestId('policy-rule-bases').click()
			await page.getByRole('option', { name: new RegExp(`^${GROUND.replace(/\./g, '\\.')}`) }).click()

			const saved = page.waitForResponse((r) => r.url().includes(`/api/policy/prohibitions/${ruleId(rule)}`) && r.request().method() === 'PUT')
			await dialog.getByRole('button', { name: 'Save' }).click()
			const answer = await saved
			expect(answer.status()).toBe(200)
			expect(answer.request().postDataJSON().bases).toEqual([GROUND])

			const shown = await request.get(`${PROHIBITIONS_API}/${ruleId(rule)}`, { headers: OCS })
			expect(shown.status()).toBe(200)
			expect((await shown.json()).bases).toEqual([GROUND])

			// The rule's form shows the stored grondslag when opened again.
			await row.getByRole('button', { name: /actions/i }).click()
			await page.getByRole('menuitem', { name: 'Edit' }).click()
			await expect(page.getByRole('dialog', { name: 'Edit publish-never rule' }).getByTestId('policy-rule-bases')).toContainText(GROUND)
		} finally {
			// Prohibitions are archival: deactivate instead of delete.
			await request.put(`${PROHIBITIONS_API}/${ruleId(rule)}`, {
				headers: OCS,
				data: {
					primaryName: name,
					reason: 'review-workbench e2e fixture',
					entityType: 'PERSON',
					matchRules: [{ type: 'exact', value: name }],
					bases: [GROUND],
					active: false,
				},
			})
		}
	})

	test('a rule\'s grondslag pre-fills the matched finding', async ({ page, request }) => {
		test.skip(REVIEW_FILE_ID === '' || REVIEW_FILE_PATH === '', NO_REVIEW_FILE)
		const rule = ruleMatching(await listRules(request, PROHIBITIONS_API), 'Beschermde Getuige A')
		test.skip(rule === null || (rule.bases ?? []).length === 0, 'the seeded prohibition for "Beschermde Getuige A" carries no bases')

		await openWorkbench(page, REVIEW_FILE_ID, REVIEW_FILE_PATH)
		const row = rowOf(page, 'Beschermde Getuige A')
		await expect(row.locator('.bases-cell')).toContainText(rule!.bases![0])
		await expect(row.getByTestId('bases-proposed')).toHaveText('Proposed')
	})

	test('the BSN finding shows the mapped grondslag, marked as proposed', async ({ page, request }) => {
		test.skip(REVIEW_FILE_ID === '' || REVIEW_FILE_PATH === '', NO_REVIEW_FILE)
		const proposed = await proposalFor(request, 'BSN')
		test.skip(proposed.length === 0, `${MAPPING_KEY} proposes no ground for BSN on this instance`)

		await openWorkbench(page, REVIEW_FILE_ID, REVIEW_FILE_PATH)
		await page.locator('.filter-bar select').selectOption('BSN')
		const row = page.locator('.entity-table tbody tr').first()
		await expect(row.locator('.bases-cell')).toContainText(proposed[0])
		await expect(row.getByTestId('bases-proposed')).toHaveText('Proposed')
	})

	test('the reviewer\'s own grondslag replaces the proposal and is what is written', async ({ page, request }) => {
		test.skip(REVIEW_FILE_ID === '' || REVIEW_FILE_PATH === '', NO_REVIEW_FILE)
		const proposed = await proposalFor(request, 'BSN')
		test.skip(proposed.length === 0, `${MAPPING_KEY} proposes no ground for BSN on this instance`)

		await openWorkbench(page, REVIEW_FILE_ID, REVIEW_FILE_PATH)
		await page.locator('.filter-bar select').selectOption('BSN')
		const row = page.locator('.entity-table tbody tr').first()
		await expect(row.getByTestId('bases-proposed')).toBeVisible()

		const written: Array<{ url: string; bases: string[] }> = []
		page.on('request', (r) => {
			if (r.method() === 'PATCH' && r.url().includes('/api/anonymization/relations/')) {
				written.push({ url: r.url(), bases: r.postDataJSON()?.bases ?? [] })
			}
		})

		try {
			await row.locator('.bases-cell .v-select').first().click()
			const option = page.getByRole('option').filter({ hasNotText: proposed[0] }).first()
			const chosen = ((await option.innerText()).split(' — ')[0] ?? '').trim()
			await option.click()

			await expect.poll(() => written.length, { timeout: 15_000 }).toBeGreaterThan(0)
			for (const write of written) {
				expect(write.bases, 'the reviewer\'s pick is written').toContain(chosen)
			}
			await expect(row.getByTestId('bases-proposed'), 'a confirmed pick is no longer a proposal').toHaveCount(0)
		} finally {
			for (const write of written) {
				const id = /\/relations\/(\d+)/.exec(write.url)?.[1] ?? ''
				await request.patch(relationApi(id), {
					headers: OCS,
					data: { skipAnonymization: false, bases: proposed },
				})
			}
		}
	})

	test('a prohibition badge opens the rule list showing the matched rule', async ({ page, request }) => {
		test.skip(REVIEW_FILE_ID === '' || REVIEW_FILE_PATH === '', NO_REVIEW_FILE)
		const rule = ruleMatching(await listRules(request, PROHIBITIONS_API), 'Beschermde Getuige A')
		test.skip(rule === null, 'no active prohibition matches "Beschermde Getuige A": the register seed is missing')

		await openWorkbench(page, REVIEW_FILE_ID, REVIEW_FILE_PATH)
		await rowOf(page, 'Beschermde Getuige A').getByTestId('policy-badge-prohibition').click()

		await expect(page).toHaveURL(new RegExp(`/policy/prohibitions\\?rule=${ruleId(rule!)}`))
		await expect(page.locator('#content, .app-content, main').first()).toContainText(String(rule!.primaryName))
	})

	test('a standing consent created now applies to the next review', async ({ page, request }) => {
		test.skip(REVIEW_FILE_ID === '' || REVIEW_FILE_PATH === '', NO_REVIEW_FILE)
		const text = 'Stichting Voorbeeld'
		const existing = ruleMatching(await listRules(request, CONSENTS_API), text)
		let createdId = ''

		try {
			let consentId = existing ? ruleId(existing) : ''
			if (consentId === '') {
				const created = await request.post(CONSENTS_API, {
					headers: OCS,
					data: { entityText: text, entityType: 'ORGANIZATION', consentMethod: 'paper', matchRules: [{ type: 'normalized', value: text }] },
				})
				expect(created.status(), await created.text()).toBe(201)
				createdId = String((await created.json()).id)
				consentId = createdId
			}

			// The next extraction picks the rule up.
			await extract(request, REVIEW_FILE_ID, true)

			await openWorkbench(page, REVIEW_FILE_ID, REVIEW_FILE_PATH)
			await expect(includeBox(page, text)).not.toBeChecked()
			const badge = rowOf(page, text).getByTestId('policy-badge-standingConsent')
			await expect(badge).toContainText('Never anonymise:')
			await expect(badge).toHaveAttribute('href', new RegExp(`rule=${consentId}`))
		} finally {
			if (createdId !== '') {
				await request.delete(`${CONSENTS_API}/${createdId}`, { headers: OCS })
				await extract(request, REVIEW_FILE_ID, true)
			}
		}
	})

	test('a Word document is reviewed as its converted PDF, and the link records the rendition', async ({ page, request }) => {
		test.skip(
			DOCX_FILE_ID === '' || DOCX_FILE_PATH === '' || DOCX_NAME === '',
			'FILINQ_E2E_DOCX_FILE_ID / _PATH / _NAME are not set: no extracted .docx on this instance',
		)

		const state = await reviewState(request, DOCX_FILE_ID)
		expect(state.rendition?.kind).toBe('converted')
		expect(String(state.rendition?.backend ?? '')).not.toBe('')
		expect(state.rendition?.textLayer).toBe('native')

		const link = await linkOf(request, DOCX_FILE_ID)
		expect(link?.reviewRendition?.kind, 'the link records the rendition').toBe('converted')
		expect(link.reviewRendition.backend).toBe(state.rendition.backend)
		expect(link.reviewRendition.textLayer).toBe('native')

		await openWorkbench(page, DOCX_FILE_ID, DOCX_FILE_PATH)
		await expect(page.getByTestId('review-rendition')).toContainText(`Reviewed as a PDF converted by ${state.rendition.backend}`)
		await expect(page.getByTestId('document-compare-original').locator('.document-compare__name')).toHaveText(/\.pdf$/)
		await expect(rowOf(page, DOCX_NAME), 'the name is detected on the rendition').toBeVisible()
	})

	test('with the floor at 0.6 and certain-from at 0.9, findings are split and both numbers shown', async ({ page, request }) => {
		test.skip(THRESHOLD_FILE_ID === '' || THRESHOLD_FILE_PATH === '', 'FILINQ_E2E_THRESHOLD_FILE_ID / _PATH are not set: no document with findings either side of 0.9')

		const orRead = await request.get(OR_FILE_SETTINGS, { headers: OCS })
		expect(orRead.status()).toBe(200)
		const floorBefore = (await orRead.json()).anonymisation?.confidenceThreshold
		const certainBefore = (await readSettings(request))['filinq.review.certain_from'] ?? '0.85'

		try {
			const floor = await request.patch(OR_FILE_SETTINGS, { headers: OCS, data: { anonymisation: { confidenceThreshold: 0.6 } } })
			expect(floor.ok(), `OpenRegister floor -> ${floor.status()}`).toBeTruthy()
			await writeSettings(request, { 'filinq.review.certain_from': 0.9 })

			await openWorkbench(page, THRESHOLD_FILE_ID, THRESHOLD_FILE_PATH)
			await expect(page.getByTestId('review-thresholds')).toContainText('Found from 0.6 (OpenRegister), certain from 0.9 (filinq)')

			let uncertain = 0
			let certain = 0
			for (const row of await page.locator('.entity-table tbody tr').all()) {
				const percent = Number(/(\d+(?:\.\d+)?)%/.exec(await row.innerText())?.[1] ?? 'NaN')
				if (percent < 90) {
					await expect(row.getByTestId('certainty-uncertain')).toHaveText('Uncertain')
					uncertain++
				} else {
					await expect(row.getByTestId('certainty-certain')).toHaveText('Certain')
					certain++
				}
			}
			expect(uncertain, 'a finding below 0.9').toBeGreaterThan(0)
			expect(certain, 'a finding at or above 0.9').toBeGreaterThan(0)
			await expect(page.getByTestId('uncertain-count')).toContainText(String(uncertain))
		} finally {
			await writeSettings(request, { 'filinq.review.certain_from': certainBefore })
			if (floorBefore !== undefined) {
				await request.patch(OR_FILE_SETTINGS, { headers: OCS, data: { anonymisation: { confidenceThreshold: floorBefore } } })
			}
		}
	})

	test('a batch whose files are all checked anonymises as before', async ({ request }) => {
		test.skip(CHECKED_BATCH_ID === '', 'FILINQ_E2E_CHECKED_BATCH_ID is not set: no fully checked batch on this instance')

		const status = await request.get(`${BATCH_API}/${CHECKED_BATCH_ID}/status`, { headers: OCS })
		expect(status.status()).toBe(200)
		const batch = await status.json()
		expect(batch.batchStatus, 'the fixture is a batch in review').toBe('review')
		const files = (batch.files as Array<{ fileId: number; status: string }>).filter((file) => file.status !== 'error')
		for (const file of files) {
			expect((await reviewState(request, file.fileId)).checked, `file ${file.fileId} is checked`).toBe(true)
		}

		const listed = await request.get(`${BATCH_API}/${CHECKED_BATCH_ID}/entities`, { headers: OCS })
		expect(listed.status()).toBe(200)
		const entities = (await listed.json()).entities
		expect(Array.isArray(entities) && entities.length > 0).toBe(true)

		const res = await request.post(`${BATCH_API}/${CHECKED_BATCH_ID}/anonymize`, { headers: OCS, data: { entities } })
		const body = await res.json()
		expect(body.reason, 'the checked gate lets a fully checked batch through').not.toBe('documents_not_reviewed')
		expect(res.status(), JSON.stringify(body)).toBe(200)
		expect(body.processedFiles).toBe(files.length)
	})
})
