/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * flow-operations, task 4.2: Filinq's four operations in Nextcloud Flow. The
 * administrator finds them in Settings > Flow, a matching file only queues a
 * run, the background job does the work as the file's owner, every run is
 * logged as a `flowOperationRun` object, a failure notifies the owner, and a
 * file a Filinq operation produced never starts another run.
 *
 * WHAT IT SEEDS ITSELF. Every test makes its own Flow rule through the
 * workflowengine OCS API (global scope, the signed-in admin), its own files
 * over WebDAV (from `tests/sample-documents/`), and, where a rule fires on a
 * tag, its own system tag. All of it carries a run stamp and is deleted when
 * the test ends. Runs are read back through OpenRegister's objects API; only
 * the server writes them, so the runs themselves stay behind as log rows.
 *
 * WHERE THE SPEC'S FOLDER CONDITION GOES. Nextcloud Flow has no "folder is"
 * check. A rule "File created in /Inkomend" is written as a File name check on
 * the run-stamped name of the file the test drops in a run-stamped folder,
 * which also matches when the event fires on the command line, as it does for
 * the PDF/A output the job writes.
 *
 * WHAT IT NEEDS FROM THE INSTANCE. The job runs on cron, so every test past
 * "queued" runs the queued `FlowOperationJob`s itself through occ, reached
 * through `FILINQ_E2E_OCC`, `FILINQ_E2E_CONTAINER` or `NEXTCLOUD_CONTAINER`
 * (see `../shared-instance.ts`), or `php occ` from the server root on CI.
 * Without a working occ those tests skip and say why. The failure
 * notification is delivered by OpenRegister's `ScheduledNotificationJob`,
 * which the notification tests run the same way. The validation test sets a
 * `besluit` profile in `filinq.validation.profiles` through occ and puts the
 * previous value back. The OCR test skips when OCR is switched off or
 * Tesseract is missing (`GET /api/ocr` says which); the OCR failure tests
 * switch OCR off through `POST /api/settings` and switch it back on.
 *
 * EXCLUDED IN THE SPEC (`@e2e exclude`, covered by PHPUnit): a malformed
 * operation config refused at save time, the duplicate-trigger dedupe, the
 * prohibition-gate failure, the counts-only run summary, OCR disabled failing
 * flagged, the encrypted PDF, warning-level validation findings, the app-store
 * category, and the two retention apply-blocker scenarios.
 *
 * Not run in the change that wrote it (decision 139): the live pass runs it.
 */

// @e2e openspec/changes/flow-operations/specs/flow-operations/spec.md#admin-builds-a-rule-from-a-filinq-operation
// @e2e openspec/changes/flow-operations/specs/flow-operations/spec.md#upload-returns-before-processing-runs
// @e2e openspec/changes/flow-operations/specs/flow-operations/spec.md#pdfa-output-does-not-loop
// @e2e openspec/changes/flow-operations/specs/flow-operations/spec.md#tagged-document-enters-review-not-export
// @e2e openspec/changes/flow-operations/specs/flow-operations/spec.md#scan-dropped-in-a-watched-folder-is-ocrd
// @e2e openspec/changes/flow-operations/specs/flow-operations/spec.md#decision-letter-is-archived-as-pdfa
// @e2e openspec/changes/flow-operations/specs/flow-operations/spec.md#upload-with-a-disallowed-format-is-flagged
// @e2e openspec/changes/flow-operations/specs/flow-operations/spec.md#failed-run-notifies-the-owner
// @e2e openspec/changes/flow-operations/specs/flow-operations/spec.md#runs-listing-shows-the-processing-log

import type { APIRequestContext } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { execFileSync } from 'node:child_process'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { occPrefix } from '../shared-instance.ts'
import { go, waitForNcContentReady } from './_helpers.ts'

/** The OCS header every call carries; it also satisfies the CSRF check. */
const OCS = { 'OCS-APIRequest': 'true' }

/** The signed-in user, who owns every file the tests drop. */
const USER = process.env.NC_ADMIN_USER ?? 'admin'

/** Filinq's own API. */
const API = '/index.php/apps/filinq/api'

/** Where the server logs the runs. */
const RUNS_API = '/index.php/apps/openregister/api/objects/filinq/flowOperationRun'

/** Nextcloud Flow's rule API, admin scope. */
const RULES_API = '/ocs/v2.php/apps/workflowengine/api/v1/workflows/global'

/** The signed-in user's notifications. */
const NOTIFICATIONS_API = '/ocs/v2.php/apps/notifications/api/v2/notifications'

/** The four operations, by the class name Flow stores. */
const OPERATION = {
	anonymize: 'OCA\\Filinq\\Flow\\Operation\\AnonymizeDocumentOperation',
	ocr: 'OCA\\Filinq\\Flow\\Operation\\RunOcrOperation',
	pdfa: 'OCA\\Filinq\\Flow\\Operation\\ConvertToPdfaOperation',
	validate: 'OCA\\Filinq\\Flow\\Operation\\RunValidationOperation',
}

/** The names and descriptions `getDisplayName()` / `getDescription()` answer, English or Dutch. */
const OPERATION_NAMES = [
	/Anonymise document|Document anonimiseren/,
	/Run OCR|OCR uitvoeren|Tekstherkenning/,
	/Convert to PDF\/A|Omzetten naar PDF\/A/,
	/Run validation checks|Validatiecontroles uitvoeren/,
]

/** Flow's file entity and the two events the rules listen to. */
const FILE_ENTITY = 'OCA\\WorkflowEngine\\Entity\\File'
const FILE_CREATED = '\\OCP\\Files::postCreate'
const TAG_ASSIGNED = 'OCP\\SystemTag\\ISystemTagObjectMapper::assignTags'

/** The two background jobs the tests run instead of waiting for cron. */
const FLOW_JOB = 'OCA\\Filinq\\Flow\\FlowOperationJob'
const NOTIFY_JOB = 'OCA\\OpenRegister\\Notifications\\BackgroundJob\\ScheduledNotificationJob'

/** Sample files, read from the repository. */
const SAMPLES = resolve(process.cwd(), 'tests/sample-documents')

/** One run stamp for every name this file makes. */
const STAMP = `e2eflow-${Date.now()}`

/** A Flow check, as the rule API takes it. */
interface Check {
	class: string
	operator: string
	value: string
}

/** A `flowOperationRun` as OpenRegister answers it. */
interface Run {
	operation: string
	fileId: number
	fileName?: string
	ownerUserId: string
	status: string
	statusReason?: string | null
	startedAt?: string | null
	finishedAt?: string | null
	resultSummary?: Record<string, any> | null
	producedFileId?: number | null
	errorMessage?: string | null
}

/**
 * Run occ on the instance under test.
 *
 * @param args The occ arguments.
 * @return What occ printed.
 */
function occ(args: string[]): string {
	const [bin, ...prefix] = occPrefix()
	// A bare `php occ` runs from the server root, two levels above this app.
	const cwd = bin === 'php' ? resolve(process.cwd(), '../..') : process.cwd()

	return execFileSync(bin, [...prefix, ...args], { cwd, encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] })
}

let occChecked: boolean | null = null

/**
 * Whether occ answers on this machine for the instance under test.
 *
 * @return True when `occ status` runs.
 */
function occWorks(): boolean {
	if (occChecked === null) {
		try {
			occ(['status', '--output=json'])
			occChecked = true
		} catch {
			occChecked = false
		}
	}

	return occChecked
}

/** The reason a test that needs occ gives when it skips. */
const NO_OCC = 'occ is not reachable (set FILINQ_E2E_OCC or FILINQ_E2E_CONTAINER): the queued job cannot be run without waiting for cron'

/**
 * Run every queued job of one class now, instead of waiting for cron.
 *
 * @param jobClass The job class.
 * @return How many jobs were run.
 */
function runJobs(jobClass: string): number {
	const jobs = JSON.parse(occ(['background-job:list', `--class=${jobClass}`, '--output=json'])) as Array<{ id: number | string }>
	for (const job of jobs) {
		occ(['background-job:execute', String(job.id), '--force-execute'])
	}

	return jobs.length
}

/**
 * Read one filinq app config value through occ.
 *
 * @param key The config key.
 * @return The value, or null when it is not set.
 */
function readAppConfig(key: string): string | null {
	try {
		return occ(['config:app:get', 'filinq', key]).replace(/\n$/, '')
	} catch {
		return null
	}
}

/**
 * Write, or delete, one filinq app config value through occ.
 *
 * @param key The config key.
 * @param value The value, or null to delete the key.
 */
function writeAppConfig(key: string, value: string | null): void {
	if (value === null) {
		occ(['config:app:delete', 'filinq', key])
		return
	}
	occ(['config:app:set', 'filinq', key, `--value=${value}`])
}

/**
 * Make a folder in the user's files, if it is not there.
 *
 * @param request The authenticated request context.
 * @param folder The folder path.
 */
async function makeFolder(request: APIRequestContext, folder: string): Promise<void> {
	const res = await request.fetch(`/remote.php/dav/files/${USER}/${encodeURI(folder)}`, { method: 'MKCOL' })
	expect([201, 405]).toContain(res.status())
}

/**
 * The Nextcloud file id of a path in the user's files.
 *
 * @param request The authenticated request context.
 * @param path The path.
 * @return The file id.
 */
async function fileIdOf(request: APIRequestContext, path: string): Promise<number> {
	const res = await request.fetch(`/remote.php/dav/files/${USER}/${encodeURI(path)}`, {
		method: 'PROPFIND',
		headers: { Depth: '0', 'Content-Type': 'application/xml' },
		data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns"><d:prop><oc:fileid/></d:prop></d:propfind>',
	})
	expect(res.status(), `PROPFIND ${path}`).toBe(207)
	const match = /<oc:fileid>(\d+)<\/oc:fileid>/.exec(await res.text())
	expect(match, `PROPFIND ${path} answers a file id`).not.toBeNull()

	return Number(match![1])
}

/**
 * Upload bytes over WebDAV and answer the new file's id.
 *
 * @param request The authenticated request context.
 * @param path The path in the user's files.
 * @param bytes The content.
 * @return The file id.
 */
async function upload(request: APIRequestContext, path: string, bytes: Buffer | string): Promise<number> {
	const put = await request.put(`/remote.php/dav/files/${USER}/${encodeURI(path)}`, { data: bytes })
	expect([201, 204], `PUT ${path}`).toContain(put.status())

	return fileIdOf(request, path)
}

/**
 * Delete a file or folder over WebDAV, ignoring one that is already gone.
 *
 * @param request The authenticated request context.
 * @param path The path in the user's files.
 */
async function remove(request: APIRequestContext, path: string): Promise<void> {
	await request.delete(`/remote.php/dav/files/${USER}/${encodeURI(path)}`).catch(() => undefined)
}

/**
 * Save a Flow rule in the admin scope.
 *
 * @param request The authenticated request context.
 * @param rule The operation class, its options, the events and the checks.
 * @param rule.operationClass The operation's class name.
 * @param rule.options The operation's options as stored JSON, or ''.
 * @param rule.events The events the rule listens to.
 * @param rule.checks The rule's checks.
 * @return The rule id.
 */
async function saveRule(
	request: APIRequestContext,
	rule: { operationClass: string; options: string; events: string[]; checks: Check[] },
): Promise<number> {
	const res = await request.post(`${RULES_API}?format=json`, {
		headers: OCS,
		data: {
			class: rule.operationClass,
			name: '',
			checks: rule.checks,
			operation: rule.options,
			entity: FILE_ENTITY,
			events: rule.events,
		},
	})
	expect(res.ok(), `save Flow rule -> ${res.status()} ${await res.text()}`).toBeTruthy()
	const id = Number((await res.json())?.ocs?.data?.id)
	expect(id).toBeGreaterThan(0)

	return id
}

/**
 * Delete a Flow rule.
 *
 * @param request The authenticated request context.
 * @param id The rule id.
 */
async function deleteRule(request: APIRequestContext, id: number): Promise<void> {
	await request.delete(`${RULES_API}/${id}?format=json`, { headers: OCS }).catch(() => undefined)
}

/**
 * A File name check matching every name that starts with a prefix.
 *
 * @param prefix The prefix.
 * @return The check.
 */
function nameStartsWith(prefix: string): Check {
	return { class: 'OCA\\WorkflowEngine\\Check\\FileName', operator: 'matches', value: `/^${prefix.replace(/[.*+?^${}()|[\]\\/-]/g, '\\$&')}/i` }
}

/**
 * Create a user-visible, assignable system tag.
 *
 * @param request The authenticated request context.
 * @param name The tag name.
 * @return The tag id.
 */
async function createTag(request: APIRequestContext, name: string): Promise<string> {
	const res = await request.post('/remote.php/dav/systemtags', {
		headers: { 'Content-Type': 'application/json' },
		data: { name, userVisible: true, userAssignable: true, canAssign: true },
	})
	expect(res.status(), `create tag ${name}`).toBe(201)
	const id = /\/(\d+)$/.exec(res.headers()['content-location'] ?? '')?.[1] ?? ''
	expect(id, 'the tag create answers its id').not.toBe('')

	return id
}

/**
 * Assign a system tag to a file, which fires Flow's "Tag assigned" event.
 *
 * @param request The authenticated request context.
 * @param fileId The file id.
 * @param tagId The tag id.
 */
async function assignTag(request: APIRequestContext, fileId: number, tagId: string): Promise<void> {
	const res = await request.put(`/remote.php/dav/systemtags-relations/files/${fileId}/${tagId}`)
	expect([201, 409], `assign tag ${tagId} to ${fileId}`).toContain(res.status())
}

/**
 * Delete a system tag.
 *
 * @param request The authenticated request context.
 * @param tagId The tag id.
 */
async function deleteTag(request: APIRequestContext, tagId: string): Promise<void> {
	await request.delete(`/remote.php/dav/systemtags/${tagId}`).catch(() => undefined)
}

/**
 * The runs logged for one file.
 *
 * @param request The authenticated request context.
 * @param fileId The file id.
 * @return The runs.
 */
async function runsFor(request: APIRequestContext, fileId: number): Promise<Run[]> {
	const res = await request.get(`${RUNS_API}?fileId=${fileId}&_limit=50`, { headers: OCS })
	expect(res.ok(), `GET runs for ${fileId} -> ${res.status()}`).toBeTruthy()
	const body = await res.json()
	const rows = (body.results ?? []) as Run[]

	return rows.filter((run) => Number(run.fileId) === fileId)
}

/**
 * Wait until a file has a run of an operation in one of some statuses.
 *
 * @param request The authenticated request context.
 * @param fileId The file id.
 * @param operation The operation key.
 * @param statuses The statuses waited for.
 * @return The run.
 */
async function waitForRun(request: APIRequestContext, fileId: number, operation: string, statuses: string[]): Promise<Run> {
	let found: Run | undefined
	await expect
		.poll(
			async () => {
				found = (await runsFor(request, fileId)).find((run) => run.operation === operation && statuses.includes(run.status))
				return found?.status ?? 'none'
			},
			{ message: `a ${operation} run on ${fileId} in ${statuses.join('/')}`, timeout: 60_000 },
		)
		.not.toBe('none')

	return found as Run
}

/**
 * The signed-in user's notifications that name a text.
 *
 * @param request The authenticated request context.
 * @param text The text the subject or message must hold.
 * @return The notifications.
 */
async function notificationsNaming(request: APIRequestContext, text: string): Promise<any[]> {
	const res = await request.get(`${NOTIFICATIONS_API}?format=json`, { headers: OCS })
	expect(res.ok(), `GET notifications -> ${res.status()}`).toBeTruthy()

	return ((await res.json())?.ocs?.data ?? []).filter((n: any) => `${n.subject ?? ''} ${n.message ?? ''}`.includes(text))
}

/**
 * Read, then set, filinq's OCR switch through the settings API.
 *
 * @param request The authenticated request context.
 * @param enabled Whether OCR should be on.
 * @return Whether it was on before.
 */
async function switchOcr(request: APIRequestContext, enabled: boolean): Promise<boolean> {
	const before = await request.get(`${API}/settings`, { headers: OCS })
	expect(before.ok()).toBeTruthy()
	const wasOn = (await before.json()).ocr_enabled !== false
	const res = await request.post(`${API}/settings`, { headers: OCS, data: { ocr_enabled: enabled ? '1' : '0' } })
	expect(res.ok(), `POST settings ocr_enabled -> ${res.status()}`).toBeTruthy()

	return wasOn
}

/**
 * Drop a file in a run-stamped folder under a rule that runs OCR on it, with
 * OCR switched off, and run the job: the run fails with `ocr_disabled`.
 *
 * @param request The authenticated request context.
 * @param label What the caller calls this seed, for the names.
 * @return The file's path, id and name, and the failed run.
 */
async function seedFailedOcrRun(
	request: APIRequestContext,
	label: string,
): Promise<{ folder: string; fileName: string; fileId: number; run: Run }> {
	const folder = `Inkomend-${STAMP}-${label}`
	const fileName = `${STAMP}-${label}-scan.png`
	await makeFolder(request, folder)
	const rule = await saveRule(request, {
		operationClass: OPERATION.ocr,
		options: '',
		events: [FILE_CREATED],
		checks: [nameStartsWith(`${STAMP}-${label}`)],
	})
	const wasOn = await switchOcr(request, false)
	try {
		const fileId = await upload(request, `${folder}/${fileName}`, readFileSync(`${SAMPLES}/parkeervergunning-demostad-scan.png`))
		await waitForRun(request, fileId, 'ocr', ['queued'])
		runJobs(FLOW_JOB)
		const run = await waitForRun(request, fileId, 'ocr', ['failed'])

		return { folder, fileName, fileId, run }
	} finally {
		await switchOcr(request, wasOn)
		await deleteRule(request, rule)
	}
}

test.describe('Filinq operations in Nextcloud Flow', () => {
	test.setTimeout(240_000)

	test('the four operations are offered in Settings > Flow and a Run OCR rule saves', async ({ page, request }) => {
		await page.goto('/index.php/settings/admin/workflow', { waitUntil: 'domcontentloaded' })
		await waitForNcContentReady(page)
		const engine = page.locator('#workflowengine')
		await expect(engine).toBeVisible({ timeout: 30_000 })

		// Flow shows the first few operations and folds the rest behind a button.
		const more = engine.getByRole('button', { name: /^(Show more|Meer weergeven|Toon meer)$/i })
		if (await more.isVisible().catch(() => false)) {
			await more.click()
		}

		for (const name of OPERATION_NAMES) {
			const card = engine.locator('.actions__item').filter({ has: page.locator('h3', { hasText: name }) }).first()
			await expect(card, `the ${name} operation is offered`).toBeVisible()
			await expect(card.locator('small')).not.toHaveText('')
			// The icon is Filinq's own, set by getIcon().
			await expect(card.locator('.icon')).toHaveAttribute('style', /app-dark\.svg/)
		}

		// The rule builder script registered the option element: picking PDF/A
		// opens a draft rule with the target-folder field. Nothing is saved.
		await engine
			.locator('.actions__item')
			.filter({ has: page.locator('h3', { hasText: OPERATION_NAMES[2] }) })
			.first()
			.click()
		await expect(page.getByText(/^(Target folder|Doelmap)$/).first()).toBeVisible({ timeout: 15_000 })

		// "when File created and folder is /Inkomend, then Run OCR" saves.
		const rule = await saveRule(request, {
			operationClass: OPERATION.ocr,
			options: '',
			events: [FILE_CREATED],
			checks: [nameStartsWith(`${STAMP}-inkomend`)],
		})
		try {
			const listed = await request.get(`${RULES_API}?format=json`, { headers: OCS })
			expect(listed.ok()).toBeTruthy()
			const rules = Object.values((await listed.json())?.ocs?.data ?? {}).flat() as any[]
			const saved = rules.find((row) => Number(row.id) === rule)
			expect(saved, 'the saved rule is listed').toBeTruthy()
			expect(saved.class).toBe(OPERATION.ocr)
			expect(saved.events).toContain(FILE_CREATED)
		} finally {
			await deleteRule(request, rule)
		}
	})

	test('an upload into a watched folder only queues the OCR run', async ({ request }) => {
		const folder = `Inkomend-${STAMP}-queued`
		const fileName = `${STAMP}-queued-scan.png`
		await makeFolder(request, folder)
		const rule = await saveRule(request, {
			operationClass: OPERATION.ocr,
			options: '',
			events: [FILE_CREATED],
			checks: [nameStartsWith(`${STAMP}-queued`)],
		})
		try {
			const fileId = await upload(request, `${folder}/${fileName}`, readFileSync(`${SAMPLES}/parkeervergunning-demostad-scan.png`))

			// The upload request has returned: the run exists and waits.
			const runs = (await runsFor(request, fileId)).filter((run) => run.operation === 'ocr')
			expect(runs, 'one OCR run for the uploaded file').toHaveLength(1)
			expect(runs[0].status).toBe('queued')
			expect(runs[0].ownerUserId).toBe(USER)
			expect(runs[0].fileName).toBe(fileName)

			// OCR has not run yet.
			const ocr = await request.get(`${API}/ocr/${fileId}`, { headers: OCS })
			expect(ocr.ok()).toBeTruthy()
			expect((await ocr.json()).ocrProcessed).toBe(false)
		} finally {
			await deleteRule(request, rule)
			await remove(request, folder)
			// Let the queued job find its file gone now rather than on a later cron.
			if (occWorks()) {
				try {
					runJobs(FLOW_JOB)
				} catch {
					// Best effort: a later cron run does the same.
				}
			}
		}
	})

	test('the PDF/A copy a conversion writes is skipped as own output and never converted again', async ({ request }) => {
		test.skip(!occWorks(), NO_OCC)

		const folder = `Archief-${STAMP}-loop`
		const fileName = `${STAMP}-loop-besluit.pdf`
		await makeFolder(request, folder)
		const rule = await saveRule(request, {
			operationClass: OPERATION.pdfa,
			options: '',
			events: [FILE_CREATED],
			// Matches the source and, as its name starts the same, the PDF/A copy.
			checks: [nameStartsWith(`${STAMP}-loop`)],
		})
		try {
			const fileId = await upload(request, `${folder}/${fileName}`, readFileSync(`${SAMPLES}/pdfa/pdfa3b-rendered.pdf`))
			await waitForRun(request, fileId, 'pdfa', ['queued'])
			runJobs(FLOW_JOB)

			const run = await waitForRun(request, fileId, 'pdfa', ['succeeded', 'failed'])
			expect(run.status, `the conversion ran: ${run.errorMessage ?? ''}`).toBe('succeeded')
			const produced = Number(run.producedFileId)
			expect(produced).toBeGreaterThan(0)

			// Whatever ran the job's create event, the copy's runs are all skipped.
			runJobs(FLOW_JOB)
			const copyRuns = (await runsFor(request, produced)).filter((row) => row.operation === 'pdfa')
			expect(copyRuns.length, 'the copy\'s create event was logged').toBeGreaterThanOrEqual(1)
			for (const copyRun of copyRuns) {
				expect(copyRun.status).toBe('skipped')
				expect(copyRun.statusReason).toBe('own_output')
				expect(copyRun.producedFileId ?? null).toBeNull()
			}
		} finally {
			await deleteRule(request, rule)
			await remove(request, folder)
		}
	})

	test('a document tagged for a Woo request enters the review queue and is not exported', async ({ request }) => {
		test.skip(!occWorks(), NO_OCC)

		const tagId = await createTag(request, `woo-verzoek-${STAMP}`)
		const rule = await saveRule(request, {
			operationClass: OPERATION.anonymize,
			options: '',
			events: [TAG_ASSIGNED],
			checks: [{ class: 'OCA\\WorkflowEngine\\Check\\FileSystemTags', operator: 'is', value: tagId }],
		})
		const fileName = `${STAMP}-woo-brief.txt`
		try {
			const fileId = await upload(
				request,
				fileName,
				'Gemeente Demostad\nAan: Jan de Vries\nBurgerservicenummer: 111222333\nUw verzoek is in behandeling.\n',
			)
			await assignTag(request, fileId, tagId)
			await waitForRun(request, fileId, 'anonymize', ['queued'])
			runJobs(FLOW_JOB)

			const run = await waitForRun(request, fileId, 'anonymize', ['succeeded', 'failed'])
			expect(run.status, `detection ran: ${run.errorMessage ?? ''}`).toBe('succeeded')
			expect(run.producedFileId ?? null, 'the intake writes no anonymised copy').toBeNull()

			const review = await request.get(`${API}/review/${fileId}`, { headers: OCS })
			expect(review.ok(), `GET review -> ${review.status()}`).toBeTruthy()
			const state = await review.json()
			expect(state.extracted, 'the document is in the review queue with its entities').toBe(true)
			expect(state.entityCount).toBeGreaterThanOrEqual(1)
			expect(state.checked, 'nobody marked it reviewed').toBe(false)
			expect(state.review ?? null).toBeNull()
		} finally {
			await deleteRule(request, rule)
			await deleteTag(request, tagId)
			await remove(request, fileName)
		}
	})

	test('a scan dropped in a watched folder is OCR\'d by the flow', async ({ request }) => {
		test.skip(!occWorks(), NO_OCC)
		const capability = await request.get(`${API}/ocr?fileIds=`, { headers: OCS })
		expect(capability.ok()).toBeTruthy()
		test.skip((await capability.json()).capability?.available !== true, 'OCR is switched off or Tesseract is not installed on this instance')

		const folder = `Inkomend-${STAMP}-ocr`
		const fileName = `${STAMP}-ocr-scan.png`
		await makeFolder(request, folder)
		const rule = await saveRule(request, {
			operationClass: OPERATION.ocr,
			options: '',
			events: [FILE_CREATED],
			checks: [nameStartsWith(`${STAMP}-ocr`)],
		})
		try {
			const fileId = await upload(request, `${folder}/${fileName}`, readFileSync(`${SAMPLES}/parkeervergunning-demostad-scan.png`))
			await waitForRun(request, fileId, 'ocr', ['queued'])
			runJobs(FLOW_JOB)

			const run = await waitForRun(request, fileId, 'ocr', ['succeeded', 'failed', 'skipped'])
			expect(run.status, `OCR ran: ${run.statusReason ?? ''} ${run.errorMessage ?? ''}`).toBe('succeeded')
			expect(typeof run.resultSummary?.confidence).toBe('number')

			const ocr = await request.get(`${API}/ocr/${fileId}`, { headers: OCS })
			expect(ocr.ok()).toBeTruthy()
			const status = await ocr.json()
			expect(status.ocrProcessed).toBe(true)
			expect(status.triggeredBy).toBe('flow')
			expect(typeof status.confidence).toBe('number')
		} finally {
			await deleteRule(request, rule)
			await remove(request, folder)
		}
	})

	test('a decision letter tagged for archiving gets a PDF/A copy in the target folder', async ({ request }) => {
		test.skip(!occWorks(), NO_OCC)

		const archive = `Archief-${STAMP}`
		const fileName = `${STAMP}-besluit.docx`
		const original = readFileSync(`${SAMPLES}/concept-besluit-demostad.docx`)
		const tagId = await createTag(request, `archiveren-${STAMP}`)
		const rule = await saveRule(request, {
			operationClass: OPERATION.pdfa,
			options: JSON.stringify({ targetFolder: archive }),
			events: [TAG_ASSIGNED],
			checks: [{ class: 'OCA\\WorkflowEngine\\Check\\FileSystemTags', operator: 'is', value: tagId }],
		})
		try {
			const fileId = await upload(request, fileName, original)
			await assignTag(request, fileId, tagId)
			await waitForRun(request, fileId, 'pdfa', ['queued'])
			runJobs(FLOW_JOB)

			const run = await waitForRun(request, fileId, 'pdfa', ['succeeded', 'failed'])
			expect(run.status, `the conversion ran: ${run.errorMessage ?? ''}`).toBe('succeeded')
			const produced = Number(run.producedFileId)
			expect(produced).toBeGreaterThan(0)

			// The copy is in the target folder.
			const listing = await request.fetch(`/remote.php/dav/files/${USER}/${encodeURI(archive)}`, {
				method: 'PROPFIND',
				headers: { Depth: '1', 'Content-Type': 'application/xml' },
				data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns"><d:prop><oc:fileid/></d:prop></d:propfind>',
			})
			expect(listing.status()).toBe(207)
			expect(await listing.text()).toContain(`<oc:fileid>${produced}</oc:fileid>`)

			// The original is byte for byte what was uploaded.
			const after = await request.get(`/remote.php/dav/files/${USER}/${encodeURI(fileName)}`)
			expect(after.ok()).toBeTruthy()
			expect(Buffer.compare(await after.body(), original)).toBe(0)
		} finally {
			await deleteRule(request, rule)
			await deleteTag(request, tagId)
			await remove(request, fileName)
			await remove(request, archive)
		}
	})

	test('an upload in a format the besluit profile disallows fails the run and notifies the owner', async ({ request }) => {
		test.skip(!occWorks(), NO_OCC)

		const previous = readAppConfig('validation.profiles')
		let profiles: Record<string, any>
		try {
			profiles = previous ? JSON.parse(previous) : {}
		} catch {
			profiles = {}
		}
		profiles.besluit = { allowedMimes: ['application/pdf'], severities: { 'format-not-allowed': 'blocking' } }
		writeAppConfig('validation.profiles', JSON.stringify(profiles))

		const folder = `Besluiten-${STAMP}`
		const fileName = `${STAMP}-besluit-scan.png`
		let rule = 0
		try {
			await makeFolder(request, folder)
			rule = await saveRule(request, {
				operationClass: OPERATION.validate,
				options: JSON.stringify({ documentType: 'besluit' }),
				events: [FILE_CREATED],
				checks: [nameStartsWith(`${STAMP}-besluit`)],
			})
			const fileId = await upload(request, `${folder}/${fileName}`, readFileSync(`${SAMPLES}/parkeervergunning-demostad-scan.png`))
			await waitForRun(request, fileId, 'validate', ['queued'])
			runJobs(FLOW_JOB)

			const run = await waitForRun(request, fileId, 'validate', ['failed', 'succeeded'])
			expect(run.status).toBe('failed')
			expect(run.statusReason).toBe('validation_failed')
			expect(run.resultSummary?.documentType).toBe('besluit')
			const checks = ((run.resultSummary?.findings ?? []) as any[]).map((finding) => finding.checkId)
			expect(checks).toContain('format-not-allowed')

			runJobs(NOTIFY_JOB)
			await expect
				.poll(async () => (await notificationsNaming(request, fileName)).length, { timeout: 60_000 })
				.toBeGreaterThan(0)
		} finally {
			if (rule > 0) {
				await deleteRule(request, rule)
			}
			await remove(request, folder)
			writeAppConfig('validation.profiles', previous)
		}
	})

	test('a failed OCR run notifies its owner in Nextcloud, naming the file, the operation and the reason', async ({ request }) => {
		test.skip(!occWorks(), NO_OCC)

		const seeded = await seedFailedOcrRun(request, 'notify')
		try {
			expect(seeded.run.statusReason).toBe('ocr_disabled')
			expect(seeded.run.ownerUserId).toBe(USER)
			expect(seeded.run.errorMessage ?? '').not.toBe('')

			runJobs(NOTIFY_JOB)
			let found: any[] = []
			await expect
				.poll(
					async () => {
						found = await notificationsNaming(request, seeded.fileName)
						return found.length
					},
					{ timeout: 60_000 },
				)
				.toBeGreaterThan(0)

			const notification = found[0]
			const text = `${notification.subject ?? ''} ${notification.message ?? ''}`
			expect(text).toContain(seeded.fileName)
			expect(text).toContain('ocr')
			expect(text).toContain(String(seeded.run.errorMessage))
			// A Nextcloud notification to the file owner's account, not an address.
			expect(notification.user).toBe(USER)
			expect(notification.app).toBe('openregister')

			await request
				.delete(`${NOTIFICATIONS_API}/${notification.notification_id}?format=json`, { headers: OCS })
				.catch(() => undefined)
		} finally {
			await remove(request, seeded.folder)
		}
	})

	test('the Flow runs listing shows operation, file name, status, timing and reason', async ({ page, request }) => {
		test.skip(!occWorks(), NO_OCC)

		const seeded = await seedFailedOcrRun(request, 'listing')
		try {
			expect(seeded.run.finishedAt ?? '').not.toBe('')

			await go(page, 'flow-runs')
			for (const column of [/Operation|Bewerking/, /File name|Bestandsnaam/, /Status/, /Reason|Reden/, /Started|Gestart/, /Finished|Afgerond/]) {
				await expect(page.getByRole('columnheader', { name: column }).first()).toBeVisible({ timeout: 30_000 })
			}

			const row = page.getByRole('row').filter({ hasText: seeded.fileName }).first()
			await expect(row).toBeVisible({ timeout: 30_000 })
			await expect(row).toContainText('ocr')
			await expect(row).toContainText(/failed|Mislukt/)
			await expect(row).toContainText('ocr_disabled')
			await expect(row).toContainText(String(new Date().getFullYear()))
		} finally {
			await remove(request, seeded.folder)
		}
	})
})
