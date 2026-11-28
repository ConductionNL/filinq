/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * office-template-authoring, task 5.2: a Word file is a template of its own.
 * Uploaded through `POST /api/templates/office` it keeps its source file and
 * checksum, its merge tags are read and checked against the bound schema, it
 * renders through the conversion cascade, a text fragment fills in where it
 * is referenced, a ZIP of a whole estate imports with a report, and an unknown
 * tag is mapped to a field in the import wizard. The template-management
 * parity scenarios (the object's source fields, a new source version and its
 * restore, the cascade preview) are covered here too, not in templates.spec.ts.
 *
 * WHAT IT SEEDS ITSELF. Every DOCX is built in the test from
 * `tests/sample-documents/beschikking-parkeervergunning.docx` (its four tags:
 * `${aanvrager.naam}`, `${aanvrager.adres}`, `${besluit.datum}` and
 * `${fragment:ondertekening-burgemeester}`), with JSZip rewriting a tag where a
 * scenario needs another one. Templates, text fragments and the import ZIP are
 * made through filinq's API, carry a run stamp, and are deleted when the test
 * ends. The schema check binds to `dossier`/`dossier`, as the spec does.
 *
 * WHAT IT NEEDS FROM THE INSTANCE. The PDF scenarios (generation, preview)
 * convert the filled DOCX through the cascade, which needs LibreOffice; they
 * skip when `GET /api/documents/formats` reports it missing. The DOCX
 * scenarios (fragment, field mapping) need no converter. The bulk import runs
 * as a background job, so the import tests run the queued `TemplateImportJob`
 * through occ, reached through `FILINQ_E2E_OCC`, `FILINQ_E2E_CONTAINER` or
 * `NEXTCLOUD_CONTAINER` (see `../shared-instance.ts`), or `php occ` from the
 * server root on CI; without a working occ they skip and say why.
 *
 * EXCLUDED IN THE SPEC (`@e2e exclude`, covered by PHPUnit): the macro-enabled
 * upload, the ODT normalisation, the blocking severity, missing data as a
 * warning, the missing fragment, the corrupt file in an import, the untouched
 * Twig templates after the schema bump, and the duplicate's source copy. The
 * three `document-creatie-sjablonen` scenarios of this change name
 * `multi-format-output.spec.ts`, not this file.
 *
 * Not run in the change that wrote it (decision 139): the live pass runs it.
 */

// @e2e openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#communications-officer-uploads-a-docx-house-style-template
// @e2e openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#unknown-tag-is-reported-on-upload
// @e2e openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#office-template-generates-a-pdf-via-the-cascade
// @e2e openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#fragment-content-is-rendered-into-a-generated-document
// @e2e openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#zip-of-house-style-templates-imports-with-a-report
// @e2e openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#operator-maps-an-unknown-tag-interactively
// @e2e openspec/changes/archive/2026-11-25-office-template-authoring/specs/template-management/spec.md#office-template-object-carries-source-reference-and-hash
// @e2e openspec/changes/archive/2026-11-25-office-template-authoring/specs/template-management/spec.md#new-source-upload-creates-a-version-and-restore-brings-it-back
// @e2e openspec/changes/archive/2026-11-25-office-template-authoring/specs/template-management/spec.md#office-template-preview-renders-through-the-cascade

import type { APIRequestContext } from '@playwright/test'

import { expect, test } from '@playwright/test'
import JSZip from 'jszip'
import { execFileSync } from 'node:child_process'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { occPrefix } from '../shared-instance.ts'
import { go } from './_helpers.ts'

/** The OCS header every call carries; it also satisfies the CSRF check. */
const OCS = { 'OCS-APIRequest': 'true' }

/** Filinq's own API. */
const API = '/index.php/apps/filinq/api'

/** Where generations are logged. */
const GENERATED_API = '/index.php/apps/openregister/api/objects/filinq/generatedDocument'

/** The import job class the tests run instead of waiting for cron. */
const IMPORT_JOB = 'OCA\\Filinq\\BackgroundJob\\TemplateImportJob'

/** The namespace every template here is made in. */
const NAMESPACE = 'filinq'

/** The schema binding the spec names. */
const BOUND = { boundRegister: 'dossier', boundSchema: 'dossier' }

/** The house-style sample and its media type. */
const SAMPLE = readFileSync(resolve(process.cwd(), 'tests/sample-documents/beschikking-parkeervergunning.docx'))
const DOCX = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'

/** One run stamp for every name this file makes; lower case, so it fits a slug. */
const STAMP = `e2eota-${Date.now()}`

/** What the reason for a skip without occ says. */
const NO_OCC = 'occ is not reachable (set FILINQ_E2E_OCC or FILINQ_E2E_CONTAINER): the import job cannot be run without waiting for cron'

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

/**
 * Run every queued template import now, instead of waiting for cron.
 *
 * @return How many jobs were run.
 */
function runImportJobs(): number {
	const jobs = JSON.parse(occ(['background-job:list', `--class=${IMPORT_JOB}`, '--output=json'])) as Array<{ id: number | string }>
	for (const job of jobs) {
		occ(['background-job:execute', String(job.id), '--force-execute'])
	}

	return jobs.length
}

/**
 * The sample DOCX with some text replaced in its body.
 *
 * @param replacements Pairs of text to find and text to put in its place.
 * @return The new DOCX bytes.
 */
async function docxWith(replacements: Array<[string, string]>): Promise<Buffer> {
	const zip = await JSZip.loadAsync(SAMPLE)
	let xml = await zip.file('word/document.xml')!.async('string')
	for (const [from, to] of replacements) {
		expect(xml, `the sample holds ${from}`).toContain(from)
		xml = xml.split(from).join(to)
	}
	zip.file('word/document.xml', xml)

	return zip.generateAsync({ type: 'nodebuffer' })
}

/**
 * The text of a DOCX body.
 *
 * @param bytes The DOCX bytes.
 * @return word/document.xml.
 */
async function bodyOf(bytes: Buffer): Promise<string> {
	expect(bytes.subarray(0, 2).toString(), 'a DOCX is a ZIP').toBe('PK')
	const zip = await JSZip.loadAsync(bytes)

	return zip.file('word/document.xml')!.async('string')
}

/**
 * Upload a DOCX as a new office template.
 *
 * @param request The authenticated request context.
 * @param name The template name.
 * @param bytes The DOCX.
 * @param fields Further form fields, such as the schema binding.
 * @return The HTTP status and the answer.
 */
async function uploadOffice(
	request: APIRequestContext,
	name: string,
	bytes: Buffer,
	fields: Record<string, string> = {},
): Promise<{ status: number; body: any }> {
	const res = await request.post(`${API}/templates/office`, {
		headers: OCS,
		multipart: { file: { name: `${name}.docx`, mimeType: DOCX, buffer: bytes }, name, namespace: NAMESPACE, ...fields },
	})

	return { status: res.status(), body: await res.json().catch(() => ({})) }
}

/**
 * Upload a DOCX as a new office template and answer the template.
 *
 * @param request The authenticated request context.
 * @param name The template name.
 * @param bytes The DOCX.
 * @param fields Further form fields.
 * @return The created template.
 */
async function createOffice(request: APIRequestContext, name: string, bytes: Buffer, fields: Record<string, string> = {}): Promise<any> {
	const created = await uploadOffice(request, name, bytes, fields)
	expect(created.status, `POST /api/templates/office: ${JSON.stringify(created.body)}`).toBe(201)
	expect(created.body.template?.id).toBeTruthy()

	return created.body.template
}

/**
 * Read a template.
 *
 * @param request The authenticated request context.
 * @param id The template id.
 * @return The template.
 */
async function readTemplate(request: APIRequestContext, id: string): Promise<any> {
	const res = await request.get(`${API}/templates/${id}`, { headers: OCS })
	expect(res.status(), `GET /api/templates/${id}`).toBe(200)

	return res.json()
}

/**
 * Publish a template, so users may generate from it.
 *
 * @param request The authenticated request context.
 * @param id The template id.
 */
async function publish(request: APIRequestContext, id: string): Promise<void> {
	const res = await request.post(`${API}/templates/${id}/publish`, { headers: OCS })
	expect(res.ok(), `publish ${id} -> ${res.status()} ${await res.text()}`).toBeTruthy()
}

/**
 * Delete a template, ignoring one that is already gone.
 *
 * @param request The authenticated request context.
 * @param id The template id.
 */
async function deleteTemplate(request: APIRequestContext, id: string): Promise<void> {
	if (id) {
		await request.delete(`${API}/templates/${id}`, { headers: OCS }).catch(() => undefined)
	}
}

/**
 * Generate a document from a template with ad-hoc data.
 *
 * @param request The authenticated request context.
 * @param templateId The template id.
 * @param format The output format.
 * @param adHocData The data.
 * @return The HTTP status, the content type and the bytes.
 */
async function generate(
	request: APIRequestContext,
	templateId: string,
	format: string,
	adHocData: Record<string, unknown>,
): Promise<{ status: number; type: string; bytes: Buffer }> {
	const res = await request.post(`${API}/documents/generate`, {
		headers: OCS,
		data: { templateId, dataRefs: [], filename: `${STAMP}-document`, options: { format, adHocData } },
	})

	return { status: res.status(), type: res.headers()['content-type'] ?? '', bytes: await res.body() }
}

/**
 * Whether LibreOffice is there for the cascade, as filinq itself reports it.
 *
 * @param request The authenticated request context.
 * @return True when the format matrix offers DOCX.
 */
async function libreOfficeAvailable(request: APIRequestContext): Promise<boolean> {
	const res = await request.get(`${API}/documents/formats`, { headers: OCS })
	expect(res.status()).toBe(200)

	return (await res.json()).formats?.docx?.available === true
}

/** The data every generation here fills the sample's tags with. */
const DATA = {
	aanvrager: { naam: 'A. de Vries', adres: 'Marktplein 1, Demostad' },
	besluit: { datum: '13-10-2026' },
}

test.describe('office templates', () => {
	test.setTimeout(240_000)

	const made: string[] = []
	const fragments: string[] = []

	test.afterAll(async ({ request }) => {
		for (const id of made) {
			await deleteTemplate(request, id)
		}
		for (const id of fragments) {
			await request.delete(`${API}/text-fragments/${id}`, { headers: OCS }).catch(() => undefined)
		}
	})

	test('a DOCX house-style template uploads with its source, checksum and merge tags, and is listed beside Twig templates', async ({ request }) => {
		const name = `${STAMP}-huisstijl`
		const created = await uploadOffice(request, name, SAMPLE)
		expect(created.status, JSON.stringify(created.body)).toBe(201)
		const template = created.body.template
		made.push(template.id)

		expect(template.templateType).toBe('office')
		expect(Number.isInteger(template.sourceFileId) && template.sourceFileId > 0).toBe(true)
		expect(template.contentHash).toMatch(/^[0-9a-f]{64}$/)
		const paths = (template.mergeFields ?? []).map((field: any) => field.path)
		expect(paths).toEqual(expect.arrayContaining(['aanvrager.naam', 'besluit.datum']))
		expect(created.body.converted).toBe(false)

		const twig = await request.post(`${API}/templates`, {
			headers: OCS,
			data: { name: `${STAMP}-twig`, content: '<p>{{ naam }}</p>', namespace: NAMESPACE },
		})
		expect(twig.ok(), await twig.text()).toBeTruthy()
		const twigId = String((await twig.json()).id)
		made.push(twigId)

		const listed = await request.get(`${API}/templates?_search=${encodeURIComponent(STAMP)}&_limit=100`, { headers: OCS })
		expect(listed.status()).toBe(200)
		const rows = ((await listed.json()).results ?? []) as any[]
		const office = rows.find((row) => row.id === template.id)
		const plain = rows.find((row) => row.id === twigId)
		expect(office?.templateType).toBe('office')
		expect(plain, 'the Twig template is in the same list').toBeTruthy()
		expect(plain.templateType ?? 'twig').toBe('twig')
	})

	test('the office template object carries its source reference, hash and merge fields', async ({ request }) => {
		const template = await createOffice(request, `${STAMP}-object`, SAMPLE)
		made.push(template.id)

		const fetched = await readTemplate(request, template.id)
		expect(fetched.templateType).toBe('office')
		expect(Number.isInteger(fetched.sourceFileId)).toBe(true)
		expect(fetched.sourceFileId).toBe(template.sourceFileId)
		expect(fetched.contentHash).toBe(template.contentHash)
		expect((fetched.mergeFields ?? []).map((field: any) => field.path)).toEqual(
			expect.arrayContaining(['aanvrager.naam', 'aanvrager.adres', 'besluit.datum', 'fragment:ondertekening-burgemeester']),
		)

		// The stored source is the uploaded file.
		const source = await request.get(`${API}/templates/${template.id}/office/source`, { headers: OCS })
		expect(source.status()).toBe(200)
		expect(Buffer.compare(await source.body(), SAMPLE)).toBe(0)
	})

	test('a misspelt tag on a bound template is reported unknown and shown on the Word file tab', async ({ page, request }) => {
		const bytes = await docxWith([['${aanvrager.naam}', '${aanvraagr.naam}']])
		const created = await uploadOffice(request, `${STAMP}-typo`, bytes, BOUND)
		expect(created.status, JSON.stringify(created.body)).toBe(201)
		const template = created.body.template
		made.push(template.id)

		const report = template.tagReport
		expect(report.validated, `the ${BOUND.boundSchema} schema was read: ${report.notice ?? ''}`).toBe(true)
		expect(report.severity).toBe('warning')
		expect(report.unknown).toContain('aanvraagr.naam')
		expect(report.tags).toContainEqual({ tag: 'aanvraagr.naam', status: 'unknown' })
		expect(report.tags).toContainEqual({ tag: 'fragment:ondertekening-burgemeester', status: 'fragment' })

		await go(page, `templates/${template.id}`)
		await page.getByTestId('template-tab-office').click()
		const panel = page.locator('.office-template-panel')
		await expect(panel).toBeVisible({ timeout: 30_000 })
		await expect(panel.getByText(/(merge tags? (is|are) not (a field|fields) of the schema|invulvelden? (is|zijn) geen velden? van het schema): .*aanvraagr\.naam/)).toBeVisible()
		const tagRow = panel.locator('.office-template-panel__tags li').filter({ hasText: '${aanvraagr.naam}' })
		await expect(tagRow.locator('.office-template-panel__status--unknown')).toHaveText(/Unknown|Onbekend/)
	})

	test('an office template generates a PDF through the cascade and logs the generation with its type', async ({ request }) => {
		test.skip(!(await libreOfficeAvailable(request)), 'LibreOffice is not available on this instance: the filled DOCX cannot be converted to PDF')

		const template = await createOffice(request, `${STAMP}-pdf`, SAMPLE)
		made.push(template.id)
		await publish(request, template.id)
		const published = await readTemplate(request, template.id)

		const out = await generate(request, template.id, 'pdf', DATA)
		expect(out.status, out.bytes.toString('utf8', 0, 300)).toBe(200)
		expect(out.type).toContain('application/pdf')
		expect(out.bytes.subarray(0, 5).toString()).toBe('%PDF-')

		const logged = await request.get(`${GENERATED_API}?templateId=${template.id}&_limit=20`, { headers: OCS })
		expect(logged.ok()).toBeTruthy()
		const entries = (((await logged.json()).results ?? []) as any[]).filter((row) => row.templateId === template.id)
		expect(entries.length, 'one generatedDocument entry for the run').toBeGreaterThanOrEqual(1)
		expect(entries[0].templateType).toBe('office')
		expect(entries[0].format).toBe('pdf')
		expect(entries[0].templateVersion ?? null).toBe(published.version ?? null)
	})

	test('a referenced text fragment is filled in, with the fields inside it, from the same data', async ({ request }) => {
		const slug = `${STAMP}-ondertekening`
		const fragment = await request.post(`${API}/text-fragments`, {
			headers: OCS,
			data: {
				name: `${STAMP} ondertekening`,
				slug,
				namespace: NAMESPACE,
				content: 'De burgemeester van Demostad, namens deze: ${ondertekenaar.naam}',
			},
		})
		expect(fragment.status(), await fragment.text()).toBe(201)
		fragments.push(String((await fragment.json()).id))

		const bytes = await docxWith([['${fragment:ondertekening-burgemeester}', `\${fragment:${slug}}`]])
		const template = await createOffice(request, `${STAMP}-fragment`, bytes)
		made.push(template.id)
		await publish(request, template.id)

		const out = await generate(request, template.id, 'docx', { ...DATA, ondertekenaar: { naam: 'M. Burgemeester' } })
		expect(out.status, out.bytes.toString('utf8', 0, 300)).toBe(200)
		const xml = await bodyOf(out.bytes)
		expect(xml).toContain('De burgemeester van Demostad, namens deze: M. Burgemeester')
		expect(xml).toContain('A. de Vries')
		expect(xml).not.toContain('${fragment:')
		expect(xml).not.toContain('ontbrekende bouwsteen')
	})

	test('a new source version is snapshotted and restoring it brings back its source and hash', async ({ request }) => {
		const template = await createOffice(request, `${STAMP}-versions`, SAMPLE)
		made.push(template.id)
		const revisionA = { sourceFileId: template.sourceFileId, contentHash: template.contentHash }

		const versionsOf = async (): Promise<any[]> => {
			const res = await request.get(`${API}/templates/${template.id}/versions?_limit=50`, { headers: OCS })
			expect(res.status()).toBe(200)
			return ((await res.json()).results ?? []) as any[]
		}

		const revisionB = await docxWith([['Uw aanvraag voor een parkeervergunning is toegekend.', 'Uw aanvraag is gewijzigd toegekend.']])
		const replaced = await request.post(`${API}/templates/${template.id}/office`, {
			headers: OCS,
			multipart: { file: { name: 'revisie-b.docx', mimeType: DOCX, buffer: revisionB }, changelog: 'Revisie B' },
		})
		expect(replaced.status(), await replaced.text()).toBe(200)
		const afterB = (await replaced.json()).template
		expect(afterB.contentHash).not.toBe(revisionA.contentHash)

		// The snapshot of A was written when B took effect.
		const beforeRestore = await versionsOf()
		const snapshotA = beforeRestore.find((version) => version.contentHash === revisionA.contentHash)
		expect(snapshotA, 'a version pins revision A').toBeTruthy()
		expect(Number(snapshotA.sourceFileId)).toBe(revisionA.sourceFileId)

		const versionId = String(snapshotA.id ?? snapshotA.uuid ?? snapshotA['@self']?.id)
		const restored = await request.post(`${API}/templates/${template.id}/versions/${versionId}/restore`, { headers: OCS })
		expect(restored.status(), await restored.text()).toBe(200)

		const now = await readTemplate(request, template.id)
		expect(now.contentHash).toBe(revisionA.contentHash)
		expect(now.sourceFileId).toBe(revisionA.sourceFileId)

		// The restore auto-snapshotted B first.
		const afterRestore = await versionsOf()
		expect(afterRestore.length).toBe(beforeRestore.length + 1)
		expect(afterRestore.some((version) => version.contentHash === afterB.contentHash)).toBe(true)
	})

	test('the office preview fills the tag with the preview data through the cascade', async ({ request }) => {
		test.skip(!(await libreOfficeAvailable(request)), 'LibreOffice is not available on this instance: the preview cannot be converted to PDF')

		const template = await createOffice(request, `${STAMP}-preview`, SAMPLE)
		made.push(template.id)

		const preview = await request.post(`${API}/templates/${template.id}/office/preview`, {
			headers: OCS,
			data: { adHocData: { aanvrager: { naam: 'A. de Vries' } } },
		})
		expect(preview.status(), await preview.text()).toBe(200)
		expect(preview.headers()['content-type']).toContain('application/pdf')
		expect((await preview.body()).subarray(0, 5).toString()).toBe('%PDF-')
		const warnings = JSON.parse(preview.headers()['x-filinq-warnings'] ?? '[]') as string[]
		expect(warnings.join(' '), 'the tag was filled').not.toContain('"aanvrager.naam"')

		// Control: without the data the same tag is reported empty, so the
		// assertion above is about the data and not about a silent preview.
		const bare = await request.post(`${API}/templates/${template.id}/office/preview`, { headers: OCS, data: { adHocData: { x: 1 } } })
		expect(bare.status()).toBe(200)
		expect(bare.headers()['x-filinq-warnings'] ?? '').toContain('aanvrager.naam')
	})

	test.describe('bulk import', () => {
		/**
		 * The estate the import tests send: three templates and two fragments.
		 *
		 * @return The ZIP bytes and the entry names.
		 */
		async function estate(): Promise<{ zip: Buffer; templates: string[]; fragments: string[] }> {
			const archive = new JSZip()
			const templates = [`${STAMP}-brief-a.docx`, `${STAMP}-brief-b.docx`, `${STAMP}-brief-c.docx`]
			archive.file(templates[0], SAMPLE)
			archive.file(templates[1], await docxWith([['${aanvrager.naam}', '${naam_aanvrager}']]))
			archive.file(templates[2], await docxWith([['Beschikking parkeervergunning', 'Beschikking ontheffing']]))
			const fragmentNames = [`fragments/${STAMP}-groet.txt`, `fragments/${STAMP}-slot.txt`]
			archive.file(fragmentNames[0], 'Geachte heer, mevrouw,')
			archive.file(fragmentNames[1], 'Met vriendelijke groet,\nGemeente Demostad')

			return { zip: await archive.generateAsync({ type: 'nodebuffer' }), templates, fragments: fragmentNames }
		}

		/**
		 * Wait until an import job is finished, running the job queue meanwhile.
		 *
		 * @param request The authenticated request context.
		 * @param jobId The job id.
		 * @return The finished job.
		 */
		async function finished(request: APIRequestContext, jobId: string): Promise<any> {
			let job: any = {}
			await expect
				.poll(
					async () => {
						runImportJobs()
						const res = await request.get(`${API}/templates/import/${jobId}`, { headers: OCS })
						expect(res.status()).toBe(200)
						job = await res.json()
						return job.status
					},
					{ timeout: 120_000 },
				)
				.toMatch(/^(completed|failed)$/)

			return job
		}

		/**
		 * Remember what an import made, so afterAll deletes it.
		 *
		 * @param job The finished job.
		 */
		function remember(job: any): void {
			for (const row of job.report ?? []) {
				if (row.status === 'imported' && row.kind === 'template') {
					made.push(String(row.id))
				}
				if (row.status === 'imported' && row.kind === 'fragment') {
					fragments.push(String(row.id))
				}
			}
		}

		test('a ZIP of three templates and two fragments imports as a job with a report per file', async ({ request }) => {
			test.skip(!occWorks(), NO_OCC)

			const { zip, templates, fragments: fragmentNames } = await estate()
			const started = await request.post(`${API}/templates/import`, {
				headers: OCS,
				multipart: { file: { name: `${STAMP}.zip`, mimeType: 'application/zip', buffer: zip }, namespace: NAMESPACE, ...BOUND },
			})
			expect(started.status(), await started.text()).toBe(202)
			const queued = await started.json()
			expect(queued.status).toBe('queued')
			expect(queued.totalFiles).toBe(5)

			const job = await finished(request, String(queued.id))
			remember(job)
			expect(job.status).toBe('completed')
			expect(job.imported).toBe(5)
			expect(job.failed).toBe(0)

			const rows = (job.report ?? []) as any[]
			for (const file of templates) {
				const row = rows.find((line) => line.file === file)
				expect(row, `a report row for ${file}`).toBeTruthy()
				expect(row.status).toBe('imported')
				expect(row.kind).toBe('template')
				expect(row.tags).toBe(4)
				expect(Array.isArray(row.unknown)).toBe(true)
				const stored = await readTemplate(request, row.id)
				expect(stored.templateType).toBe('office')
			}
			expect(rows.find((line) => line.file === templates[1]).unknown).toContain('naam_aanvrager')

			for (const file of fragmentNames) {
				const row = rows.find((line) => line.file === file)
				expect(row, `a report row for ${file}`).toBeTruthy()
				expect(row.status).toBe('imported')
				expect(row.kind).toBe('fragment')
				const stored = await request.get(`${API}/text-fragments/${row.id}`, { headers: OCS })
				expect(stored.status()).toBe(200)
			}
		})

		test('the operator maps an unknown tag in the import wizard and the next generation fills it', async ({ page, request }) => {
			test.skip(!occWorks(), NO_OCC)

			const { zip, templates } = await estate()
			await go(page, 'templates-import')
			await expect(page.getByRole('heading', { name: /Import Word templates|Word-sjablonen importeren/ })).toBeVisible({ timeout: 30_000 })

			await page.locator('#template-import-zip').setInputFiles({ name: `${STAMP}.zip`, mimeType: 'application/zip', buffer: zip })
			await page.getByLabel(/^Namespace/).fill(NAMESPACE)
			await page.getByLabel(/Register to check the tags against|Register om de invulvelden tegen te controleren/).fill(BOUND.boundRegister)
			await page.getByLabel(/Schema to check the tags against|Schema om de invulvelden tegen te controleren/).fill(BOUND.boundSchema)

			const startedResponse = page.waitForResponse((res) => res.request().method() === 'POST' && /\/api\/templates\/import$/.test(res.url()))
			await page.getByRole('button', { name: /Start import|Import starten/ }).click()
			const started = await startedResponse
			expect(started.status()).toBe(202)
			const job = await finished(request, String((await started.json()).id))
			remember(job)
			expect(job.status).toBe('completed')

			// The page polls the job and shows the report.
			const row = page.locator('.template-import__report tr').filter({ hasText: templates[1] })
			await expect(row).toBeVisible({ timeout: 30_000 })
			await expect(row).toContainText('naam_aanvrager')
			await row.getByRole('button', { name: /Map tags|Velden koppelen/ }).click()

			await page.getByLabel(/(Field for|Veld voor) naam_aanvrager/).fill('aanvrager.naam')
			const savedResponse = page.waitForResponse((res) => res.request().method() === 'PUT' && /\/field-map$/.test(res.url()))
			await page.getByRole('button', { name: /Save field mapping|Veldkoppeling opslaan/ }).click()
			expect((await savedResponse).status()).toBe(200)

			const templateId = String((job.report as any[]).find((line) => line.file === templates[1]).id)
			const stored = await readTemplate(request, templateId)
			expect(stored.fieldMap?.naam_aanvrager).toBe('aanvrager.naam')

			await publish(request, templateId)
			const out = await generate(request, templateId, 'docx', { aanvrager: { naam: 'B. Jansen' } })
			expect(out.status, out.bytes.toString('utf8', 0, 300)).toBe(200)
			const xml = await bodyOf(out.bytes)
			expect(xml).toContain('Aan: B. Jansen')
			expect(xml).not.toContain('${naam_aanvrager}')
		})
	})
})
