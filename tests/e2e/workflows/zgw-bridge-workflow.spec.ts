/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * zgw-document-bridge, task 4.2 (the end-to-end path): a case-system source
 * appears in the admin panel, two documents of one zaak are staged from it,
 * an operator attaches both to a Woo dossier, the dossier copy is anonymised
 * while the staged original stays byte-for-byte the same, and the processed
 * document is released for write-back. The per-scenario checks (panel health,
 * empty state, badge, failed write-back) are
 * tests/e2e/spec-coverage/zgw-bridge.spec.ts.
 *
 * SERIAL. Each step builds on the one before; a failure stops the path there.
 *
 * WHAT IT SEEDS ITSELF. The source and the staged documents are OpenRegister
 * objects in filinq's register, written through OpenRegister's object API the
 * way an OpenConnector synchronisation writes them; the staged files are
 * uploaded over WebDAV with their sha256 as `contentHash`. The dossier is made
 * through `POST api/dossiers`. Attach and release go through filinq's own
 * bridge routes. Everything is name-stamped and deleted in `afterAll` (the
 * dossier's home folder stays: filinq has no dossier delete route; the copies
 * in it are deleted).
 *
 * WHAT IT READS FROM THE INSTANCE.
 *   - `FILINQ_E2E_BRIDGE_DOCUMENT`: a local file with personal data the
 *     anonymiser detects (the live pass has one). The staged files are made
 *     from it; without it they are small text files and the anonymisation
 *     step skips, saying why.
 *   - `FILINQ_E2E_ZGW_PUSH=1`: OpenConnector's push synchronisation is
 *     configured against a (mock) DRC, so a released document reaches
 *     `written_back`. Without it the push-leg check skips.
 *
 * EXPECTED TO FAIL UNTIL THE CODE LANDS. Nothing in filinq moves an external
 * document from `in_processing` to `processed` or records the redacted output
 * in `resultFileRef` (`BridgeService` knows only pickUp, release and retry).
 * The anonymisation step asserts the spec (`resultFileRef` names a new file)
 * and will fail at that assertion until it is built. So the release step does
 * not depend on it: when the document is still `in_processing` it uploads a
 * derivative and records `processed` + `resultFileRef` through OpenRegister,
 * standing in for the missing step, and then releases through filinq.
 *
 * The clause "the existing batch-anonymisation flow can process the dossier
 * folder unchanged" is not asserted separately: it needs the anonymiser
 * backend on a whole folder, and the anonymisation step already runs the
 * existing single-document flow on the copy in the dossier folder.
 *
 * Not run in the change that wrote it (decision 139): the live pass runs it.
 */

// @e2e openspec/changes/zgw-document-bridge/specs/zgw-document-bridge/spec.md#attach-staged-documents-to-a-dossier
// @e2e openspec/changes/zgw-document-bridge/specs/zgw-document-bridge/spec.md#anonymisation-of-a-bridge-document-leaves-the-staged-original-intact
// @e2e openspec/changes/zgw-document-bridge/specs/zgw-document-bridge/spec.md#release-for-write-back

import type { APIRequestContext } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { createHash } from 'node:crypto'
import { readFileSync } from 'node:fs'
import { basename, extname } from 'node:path'
import { waitForNcContentReady } from '../spec-coverage/_helpers.ts'
import { API, TEST_PREFIX } from './_fixtures.ts'

test.describe.configure({ mode: 'serial' })

/** OpenRegister's object API for filinq's register. */
const OBJECTS = '/index.php/apps/openregister/api/objects/filinq'

/** The signed-in user the WebDAV seed writes for. */
const USER = process.env.NC_ADMIN_USER ?? 'admin'

/** Fixtures the live pass prepares (see the header). */
const DOCUMENT = process.env.FILINQ_E2E_BRIDGE_DOCUMENT ?? ''
const PUSH = process.env.FILINQ_E2E_ZGW_PUSH === '1'

/** The OCS header every call carries. */
const OCS = { 'OCS-APIRequest': 'true' }

/** The zaak both documents belong to, and the staging folder. */
const ZAAK = 'ZAAK-2026-0042'
const STAGING = `${TEST_PREFIX}-zgw-staging`

/** The state the steps hand on. */
const state = {
	sourceId: '',
	sourceName: `Demostad zaaksysteem ${TEST_PREFIX}`,
	documents: [] as Array<{ id: string; externalId: string; filename: string; stagedFileId: number; hash: string; copiedFileId: number }>,
	dossierId: '',
	createdObjects: [] as Array<{ schema: string; id: string }>,
	createdHrefs: [] as string[],
}

/**
 * The sha256 of some bytes, as hex.
 *
 * @param bytes The bytes.
 * @return The hex digest.
 */
function sha256(bytes: Buffer): string {
	return createHash('sha256').update(bytes).digest('hex')
}

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
	state.createdObjects.push({ schema, id })

	return id
}

/**
 * Read one external document's own fields.
 *
 * @param request The authenticated request context.
 * @param id The external document id.
 * @return The fields, without OpenRegister's metadata.
 */
async function readDocument(request: APIRequestContext, id: string): Promise<Record<string, any>> {
	const res = await request.get(`${OBJECTS}/externalDocument/${id}`, { headers: OCS })
	expect(res.status(), `read externalDocument/${id}`).toBe(200)
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
 * Upload a file over WebDAV and answer its file id.
 *
 * @param request The authenticated request context.
 * @param path The file, relative to the user's root.
 * @param bytes The contents.
 * @return The file id.
 */
async function uploadFile(request: APIRequestContext, path: string, bytes: Buffer): Promise<number> {
	const url = `/remote.php/dav/files/${USER}/${path}`
	const put = await request.put(url, { data: bytes })
	expect([201, 204], `PUT ${path}`).toContain(put.status())
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
 * Find a file anywhere in the user's files by its id (WebDAV SEARCH).
 *
 * @param request The authenticated request context.
 * @param fileId The file id.
 * @return The file's DAV href, or an empty string when it is not found.
 */
async function hrefOf(request: APIRequestContext, fileId: number): Promise<string> {
	const res = await request.fetch('/remote.php/dav/', {
		method: 'SEARCH',
		headers: { 'Content-Type': 'text/xml' },
		data: `<?xml version="1.0" encoding="UTF-8"?>
<d:searchrequest xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns">
	<d:basicsearch>
		<d:select><d:prop><oc:fileid/></d:prop></d:select>
		<d:from><d:scope><d:href>/files/${USER}</d:href><d:depth>infinity</d:depth></d:scope></d:from>
		<d:where><d:eq><d:prop><oc:fileid/></d:prop><d:literal>${fileId}</d:literal></d:eq></d:where>
		<d:orderby/>
	</d:basicsearch>
</d:searchrequest>`,
	})
	expect(res.status(), `SEARCH for file ${fileId}`).toBe(207)

	return /<d:href>([^<]+)<\/d:href>/.exec(await res.text())?.[1] ?? ''
}

/**
 * Download a file by its DAV href and answer its sha256.
 *
 * @param request The authenticated request context.
 * @param href The DAV href.
 * @return The hex digest.
 */
async function hashAt(request: APIRequestContext, href: string): Promise<string> {
	const res = await request.get(href)
	expect(res.status(), `GET ${href}`).toBe(200)

	return sha256(await res.body())
}

test.afterAll(async ({ request }) => {
	for (const { schema, id } of state.createdObjects.reverse()) {
		await request.delete(`${OBJECTS}/${schema}/${id}`, { headers: OCS })
	}
	for (const href of state.createdHrefs) {
		await request.fetch(href, { method: 'DELETE' })
	}
	await request.fetch(`/remote.php/dav/files/${USER}/${STAGING}`, { method: 'DELETE' })
})

test('the administrator sees the new case-system source in the bridge panel', async ({ page, request }) => {
	state.sourceId = await createObject(request, 'bridgeSource', {
		name: state.sourceName,
		vendor: 'zaaksysteem.nl',
		sourceType: 'zgw-drc',
		syncIntervalMinutes: 60,
		active: true,
		lastSyncAt: new Date().toISOString(),
		lastSyncStatus: 'success',
	})

	await page.goto('/index.php/settings/admin/filinq', { waitUntil: 'domcontentloaded' })
	await waitForNcContentReady(page)
	const row = page.getByTestId('bridge-source').filter({ hasText: state.sourceName })
	await expect(row).toBeVisible({ timeout: 30_000 })
	await expect(row).toContainText('zaaksysteem.nl')
	await expect(row).toContainText(/Up to date|Bijgewerkt/)
})

test('two documents of one zaak are staged from the source and listed as staged', async ({ request }) => {
	expect(state.sourceId, 'the source step ran').not.toBe('')
	const mkcol = await request.fetch(`/remote.php/dav/files/${USER}/${STAGING}`, { method: 'MKCOL' })
	expect([201, 405]).toContain(mkcol.status())

	const extension = DOCUMENT !== '' ? extname(DOCUMENT) : '.txt'
	const base = DOCUMENT !== '' ? readFileSync(DOCUMENT) : Buffer.from(`Besluit op het Woo-verzoek, ${ZAAK}.\n`)
	for (const [index, name] of ['besluit', 'bijlage'].entries()) {
		// The second file differs by one trailing byte, so the two hashes differ.
		const bytes = index === 0 ? base : Buffer.concat([base, Buffer.from('\n')])
		const filename = `${name}-${TEST_PREFIX}${extension}`
		const stagedFileId = await uploadFile(request, `${STAGING}/${filename}`, bytes)
		const externalId = `https://drc.example/documenten/api/v1/enkelvoudiginformatieobjecten/${TEST_PREFIX}-${index}`
		const id = await createObject(request, 'externalDocument', {
			sourceId: state.sourceId,
			externalId,
			zaakIdentificatie: ZAAK,
			title: `${name} ${ZAAK}`,
			filename,
			format: DOCUMENT !== '' ? 'application/octet-stream' : 'text/plain',
			creatiedatum: '2026-11-01',
			vertrouwelijkheidaanduiding: 'openbaar',
			versie: 1,
			stagedFileRef: stagedFileId,
			contentHash: sha256(bytes),
			syncedAt: new Date().toISOString(),
			processingStatus: 'staged',
		})
		state.documents.push({ id, externalId, filename, stagedFileId, hash: sha256(bytes), copiedFileId: 0 })
	}

	const listed = await request.get(`${API}/bridge/external-documents?sourceId=${state.sourceId}`, { headers: OCS })
	expect(listed.ok(), `GET external-documents -> ${listed.status()}`).toBeTruthy()
	const rows = (await listed.json()).results as Array<Record<string, any>>
	for (const document of state.documents) {
		const row = rows.find((candidate) => Number(candidate.stagedFileRef) === document.stagedFileId)
		expect(row, `${document.filename} is listed as staged`).toBeTruthy()
		expect(row!.processingStatus).toBe('staged')
		expect(row!.zaakIdentificatie).toBe(ZAAK)
	}
})

test('the operator attaches both staged documents to the dossier: copies in its folder, dossierRef and in_processing', async ({ request }) => {
	expect(state.documents, 'the staging step ran').toHaveLength(2)

	const made = await request.post(`${API}/dossiers`, { headers: OCS, data: { name: `Woo-verzoek 2026-021 ${TEST_PREFIX}` } })
	expect(made.status(), `POST ${API}/dossiers`).toBe(201)
	const dossier = await made.json()
	state.dossierId = String(dossier.id ?? dossier.uuid ?? dossier['@self']?.id ?? '')
	expect(state.dossierId).not.toBe('')
	state.createdObjects.push({ schema: 'dossier', id: state.dossierId })

	const attached = await request.post(`${API}/bridge/dossiers/${state.dossierId}/attach`, {
		headers: OCS,
		data: { documentIds: state.documents.map((document) => document.id) },
	})
	expect(attached.status(), await attached.text()).toBe(200)
	const results = (await attached.json()).results as Array<Record<string, any>>
	expect(results).toHaveLength(2)

	for (const document of state.documents) {
		const result = results.find((row) => Number(row.stagedFileRef) === document.stagedFileId)
		expect(result, `${document.filename} was attached`).toBeTruthy()
		expect(result!.processingStatus).toBe('in_processing')
		expect(result!.dossierRef).toBe(state.dossierId)
		document.copiedFileId = Number(result!.copiedFileId)
		expect(document.copiedFileId).toBeGreaterThan(0)
		expect(document.copiedFileId).not.toBe(document.stagedFileId)

		// Stored, not only answered.
		const stored = await readDocument(request, document.id)
		expect(stored.processingStatus).toBe('in_processing')
		expect(stored.dossierRef).toBe(state.dossierId)
		expect(stored.externalId).toBe(document.externalId)

		// The copy is a real file outside the staging folder, with the same bytes.
		const copyHref = await hrefOf(request, document.copiedFileId)
		expect(copyHref, 'the copy exists').not.toBe('')
		expect(decodeURIComponent(copyHref)).not.toContain(`/${STAGING}/`)
		state.createdHrefs.push(copyHref)
		expect(await hashAt(request, copyHref)).toBe(document.hash)

		// And copying left the staged original alone.
		expect(await hashAt(request, `/remote.php/dav/files/${USER}/${STAGING}/${encodeURIComponent(document.filename)}`)).toBe(document.hash)
	}

	// No longer waiting: the staged listing does not offer them again.
	const listed = await request.get(`${API}/bridge/external-documents?sourceId=${state.sourceId}`, { headers: OCS })
	const ids = ((await listed.json()).results as Array<Record<string, any>>).map((row) => Number(row.stagedFileRef))
	for (const document of state.documents) {
		expect(ids).not.toContain(document.stagedFileId)
	}
})

test('anonymising the dossier copy writes a new file into resultFileRef and leaves the staged original intact', async ({ request }) => {
	test.skip(DOCUMENT === '', 'FILINQ_E2E_BRIDGE_DOCUMENT is not set: no document with personal data to anonymise')
	const document = state.documents[0]
	expect(document?.copiedFileId, 'the attach step ran').toBeGreaterThan(0)

	const extract = await request.post(`${API}/anonymization/extract/${document.copiedFileId}`, { headers: OCS, data: {} })
	expect(extract.ok(), `extract -> ${extract.status()}`).toBeTruthy()
	const checked = await request.post(`${API}/redaction/documents/${document.copiedFileId}/checked`, { headers: OCS, data: {} })
	expect(checked.ok(), `mark checked -> ${checked.status()}`).toBeTruthy()
	const anonymised = await request.post(`${API}/anonymization/anonymize/${document.copiedFileId}`, {
		headers: OCS,
		data: { wooCase: false },
	})
	expect(anonymised.status(), await anonymised.text()).toBe(200)

	// The bridge records the redacted output on the external document.
	await expect
		.poll(async () => (await readDocument(request, document.id)).processingStatus, { timeout: 60_000 })
		.toBe('processed')
	const stored = await readDocument(request, document.id)
	const resultFileRef = Number(stored.resultFileRef)
	expect(resultFileRef, 'resultFileRef names the redacted output').toBeGreaterThan(0)
	expect(resultFileRef).not.toBe(document.stagedFileId)
	expect(resultFileRef).not.toBe(document.copiedFileId)
	const resultHref = await hrefOf(request, resultFileRef)
	expect(resultHref).not.toBe('')
	state.createdHrefs.push(resultHref)

	// The staged original is byte-for-byte what the case system delivered.
	expect(await hashAt(request, `/remote.php/dav/files/${USER}/${STAGING}/${encodeURIComponent(document.filename)}`)).toBe(document.hash)
	expect(stored.contentHash).toBe(document.hash)
})

test('the processed document is released for write-back and becomes ready_for_writeback', async ({ request }) => {
	const document = state.documents[0]
	expect(document?.copiedFileId, 'the attach step ran').toBeGreaterThan(0)

	let current = await readDocument(request, document.id)
	if (current.processingStatus === 'in_processing') {
		// Stand-in for the processing step filinq does not have yet (see header).
		const derivative = `${basename(document.filename, extname(document.filename))} (geanonimiseerd)${extname(document.filename)}`
		const resultFileRef = await uploadFile(request, `${STAGING}/${derivative}`, Buffer.from('[geanonimiseerd]\n'))
		const carried = Object.fromEntries(Object.entries(current).filter(([, value]) => value !== null))
		const saved = await request.put(`${OBJECTS}/externalDocument/${document.id}`, {
			headers: OCS,
			data: { ...carried, processingStatus: 'processed', resultFileRef },
		})
		expect(saved.ok(), `record processed -> ${saved.status()} ${await saved.text()}`).toBeTruthy()
		current = await readDocument(request, document.id)
	}
	expect(current.processingStatus).toBe('processed')
	const resultFileRef = Number(current.resultFileRef)
	expect(resultFileRef).toBeGreaterThan(0)

	const released = await request.post(`${API}/bridge/external-documents/${document.id}/release`, { headers: OCS })
	expect(released.status(), await released.text()).toBe(200)
	const answer = await released.json()
	expect(answer.processingStatus).toBe('ready_for_writeback')
	expect(Number(answer.resultFileRef)).toBe(resultFileRef)

	const stored = await readDocument(request, document.id)
	expect(stored.processingStatus).toBe('ready_for_writeback')
	expect(stored.dossierRef).toBe(state.dossierId)
	expect(stored.externalId).toBe(document.externalId)

	// The other document was never processed, so it cannot be released.
	const early = await request.post(`${API}/bridge/external-documents/${state.documents[1].id}/release`, { headers: OCS })
	expect(early.status()).toBe(400)
})

test('after OpenConnector\'s push leg the document is written_back as a new informatieobject', async ({ request }) => {
	test.skip(!PUSH, 'FILINQ_E2E_ZGW_PUSH is not 1: no OpenConnector push synchronisation against a DRC on this instance')
	const document = state.documents[0]

	await expect
		.poll(async () => (await readDocument(request, document.id)).processingStatus, { timeout: 180_000, intervals: [5_000] })
		.toBe('written_back')
	const stored = await readDocument(request, document.id)
	expect(String(stored.resultExternalId ?? '')).not.toBe('')
	expect(stored.resultExternalId, 'a new informatieobject, not the original').not.toBe(document.externalId)
})
