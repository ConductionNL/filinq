/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * signing-trust-rebuild, task 4.2: the browser- and API-drivable half of the
 * signing trust rebuild. A genuine native signature verifies as `verified`
 * with the signer it was made by; a QES request naming the native provider
 * is refused before anything is stored; a completed request cannot be
 * declined, while a request in progress still can, with an audit entry; and
 * a PDF carrying a signature Filinq did not make reads `unverifiable`, never
 * tampered.
 *
 * WHAT IT SEEDS ITSELF. Each test uploads its own one-page PDF over WebDAV
 * (a hand-built PDF 1.4) and drives signing requests through
 * `POST /api/signing/requests`, `/sign` and `/decline`, with the signed-in
 * admin as every signer (two signer records for the decline-in-progress test,
 * so the first signature moves the request to IN_PROGRESS). The external
 * signature is a PDF with a `/Type /Sig` signature dictionary and no Filinq
 * marker, which is what the verifier classifies; to run that test against a
 * genuinely CMS-signed PDF instead, set `FILINQ_E2E_EXTERNAL_SIGNED_FILE_ID`
 * to its file id. Requests and files are removed afterwards.
 *
 * WHAT IT READS FROM THE INSTANCE. A native signature only verifies with the
 * signing secret configured. When the verify answer says
 * `signing-secret-not-configured`, the symmetric-round-trip test skips and
 * says so; it does not pass.
 *
 * KNOWN DISAGREEMENTS WITH THE CODE (found while writing this):
 *  - "rejects it with HTTP 400 naming the unsupported level/provider pair":
 *    the 400 is real, but `SigningController::errorResponse()` returns only the
 *    generic "Failed to create signing request" (the oracle-free pattern of
 *    REQ-DDSTR-009), so the body names nothing. The test asserts the 400 and
 *    that nothing was stored, not the wording.
 *  - "Declining a completed request is rejected with an error": `decline()`
 *    throws a RuntimeException without an HTTP code, so the answer is a 500,
 *    not a 4xx. The test asserts a refusal (>= 400) and the unchanged state.
 *
 * Excluded in the spec (`@e2e exclude`), covered by PHPUnit or Newman: the
 * consent error-body and non-owner probes, payload-rewritten artifact, legacy
 * v1 assertion, unknown provider at completion, provider refusing a level at
 * production time, marker-less session download, failing MAC after a byte
 * flip, the decidesk seam shape and completion payload, and all four
 * signing-audit-via-or scenarios.
 *
 * Not run in the change that wrote it (decision 139): the live pass runs it.
 */

// @e2e openspec/changes/signing-trust-rebuild/specs/document-signing/spec.md#writer-and-verifier-are-symmetric-on-a-genuine-artifact
// @e2e openspec/changes/signing-trust-rebuild/specs/document-signing/spec.md#qes-request-cannot-complete-with-a-native-ses-artifact
// @e2e openspec/changes/signing-trust-rebuild/specs/document-signing/spec.md#declining-a-completed-request-is-rejected
// @e2e openspec/changes/signing-trust-rebuild/specs/document-signing/spec.md#decline-from-a-signable-state-still-works
// @e2e openspec/changes/signing-trust-rebuild/specs/document-signing/spec.md#externally-signed-pdf-is-unverifiable-not-tampered

import type { APIRequestContext } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { go } from './_helpers.ts'

/** filinq's API root. */
const API = '/index.php/apps/filinq/api'

/** OpenRegister, for the signer records. */
const OR_API = '/index.php/apps/openregister/api'

/** The signed-in user every signer record names. */
const USER = process.env.NC_ADMIN_USER ?? 'admin'

/** A genuinely CMS-signed PDF on the instance, optional (see the header). */
const EXTERNAL_FILE_ID = process.env.FILINQ_E2E_EXTERNAL_SIGNED_FILE_ID ?? ''

/** The OCS header every API call carries. */
const OCS = { 'OCS-APIRequest': 'true' }

/** A run-unique prefix for every file and request this spec writes. */
const RUN = `trust-e2e-${Date.now()}`

/** WebDAV files and signing requests created here, removed in afterAll. */
const written: string[] = []
const createdRequests: string[] = []

/** A created request: its id and its signer record ids, in order. */
interface Created {
	id: string
	signerIds: string[]
}

/**
 * A one-page PDF 1.4 with a correct cross-reference table.
 *
 * @param text The line written on the page.
 * @param extra Extra object bodies appended after the page objects.
 * @return The PDF bytes.
 */
function minimalPdf(text: string, extra: string[] = []): Buffer {
	const content = `BT /F1 18 Tf 72 760 Td (${text.replace(/[()\\]/g, '')}) Tj ET`
	const objects = [
		'<< /Type /Catalog /Pages 2 0 R >>',
		'<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
		'<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
		`<< /Length ${Buffer.byteLength(content)} >>\nstream\n${content}\nendstream`,
		'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
		...extra,
	]
	let body = '%PDF-1.4\n'
	const offsets: number[] = []
	objects.forEach((object, index) => {
		offsets.push(Buffer.byteLength(body))
		body += `${index + 1} 0 obj\n${object}\nendobj\n`
	})
	const xref = Buffer.byteLength(body)
	body += `xref\n0 ${objects.length + 1}\n0000000000 65535 f \n`
	for (const offset of offsets) {
		body += `${String(offset).padStart(10, '0')} 00000 n \n`
	}
	body += `trailer\n<< /Size ${objects.length + 1} /Root 1 0 R >>\nstartxref\n${xref}\n%%EOF\n`

	return Buffer.from(body, 'latin1')
}

/**
 * Upload a PDF over WebDAV and answer its file id.
 *
 * @param request The authenticated request context.
 * @param name The file name in the user's root.
 * @param bytes The content.
 * @return The file id.
 */
async function upload(request: APIRequestContext, name: string, bytes: Buffer): Promise<number> {
	const url = `/remote.php/dav/files/${USER}/${encodeURIComponent(name)}`
	const put = await request.put(url, { data: bytes, headers: { 'Content-Type': 'application/pdf' } })
	expect([201, 204], `PUT ${name}`).toContain(put.status())
	written.push(url)

	const find = await request.fetch(url, {
		method: 'PROPFIND',
		headers: { Depth: '0', 'Content-Type': 'application/xml' },
		data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns"><d:prop><oc:fileid/></d:prop></d:propfind>',
	})
	const id = Number(/<oc:fileid>(\d+)<\/oc:fileid>/.exec(await find.text())?.[1] ?? 0)
	expect(id, `file id of ${name}`).toBeGreaterThan(0)

	return id
}

/**
 * Create a native SES signing request over a fresh PDF with the admin as every signer.
 *
 * @param request The authenticated request context.
 * @param label A word for the file name.
 * @param signerCount How many signer records to create.
 * @return The request id, its signer ids and the document's file id.
 */
async function createRequest(request: APIRequestContext, label: string, signerCount: number): Promise<Created & { fileId: number }> {
	const name = `${RUN}-${label}.pdf`
	const fileId = await upload(request, name, minimalPdf(`${RUN} ${label}`))
	const res = await request.post(`${API}/signing/requests`, {
		headers: OCS,
		data: {
			documentFileId: fileId,
			documentName: name,
			signatureLevel: 'SES',
			provider: 'native',
			signingMode: 'sequential',
			signers: Array.from({ length: signerCount }, (_, order) => ({ userId: USER, displayName: USER, order })),
		},
	})
	expect(res.status(), await res.text()).toBe(201)
	const body = await res.json()
	const id = String(body.id ?? body.uuid)
	createdRequests.push(id)
	const signerIds = (body.signerIds ?? []).map(String)
	expect(signerIds).toHaveLength(signerCount)

	return { id, signerIds, fileId }
}

/**
 * Sign as one signer record and expect success.
 *
 * @param request The authenticated request context.
 * @param id The request id.
 * @param signerId The signer record id.
 */
async function sign(request: APIRequestContext, id: string, signerId: string): Promise<void> {
	const res = await request.post(`${API}/signing/requests/${id}/sign`, { headers: OCS, data: { signerId } })
	expect(res.status(), `sign ${signerId}: ${await res.text()}`).toBeLessThan(300)
}

/**
 * Read a signing request.
 *
 * @param request The authenticated request context.
 * @param id The request id.
 * @return The stored request.
 */
async function readRequest(request: APIRequestContext, id: string): Promise<Record<string, any>> {
	const res = await request.get(`${API}/signing/requests/${id}`, { headers: OCS })
	expect(res.ok(), `GET request ${id} -> ${res.status()}`).toBeTruthy()

	return res.json()
}

/**
 * Read a signer record straight from OpenRegister.
 *
 * @param request The authenticated request context.
 * @param signerId The signer record id.
 * @return The stored signer record.
 */
async function readSigner(request: APIRequestContext, signerId: string): Promise<Record<string, any>> {
	const res = await request.get(`${OR_API}/objects/filinq/signerRecord/${signerId}`, { headers: OCS })
	expect(res.ok(), `signer record ${signerId} -> ${res.status()}`).toBeTruthy()

	return res.json()
}

/**
 * Verify a file through the signing engine.
 *
 * @param request The authenticated request context.
 * @param fileId The file id.
 * @return The verification answer.
 */
async function verify(request: APIRequestContext, fileId: number | string): Promise<Record<string, any>> {
	const res = await request.get(`${API}/signing/verify/${fileId}`, { headers: OCS })
	expect(res.status(), `GET verify/${fileId}`).toBe(200)

	return res.json()
}

test.describe('signing trust rebuild', () => {
	test.setTimeout(180_000)

	test.afterAll(async ({ request }) => {
		for (const id of createdRequests) {
			await request.delete(`${API}/signing/requests/${id}`, { headers: OCS }).catch(() => {})
		}
		for (const url of written) {
			await request.delete(url).catch(() => {})
		}
	})

	test('a genuine native signature verifies as verified with its signer, timestamp and level', async ({ page, request }) => {
		const created = await createRequest(request, 'genuine', 1)
		await sign(request, created.id, created.signerIds[0])
		expect((await readRequest(request, created.id)).status).toBe('COMPLETED')

		const result = await verify(request, created.fileId)
		const reasons = (result.signatures ?? []).map((s: any) => s.reason)
		test.skip(reasons.includes('signing-secret-not-configured'), 'The instance has no signing secret, so no native signature can verify')

		expect(result.signatures).toHaveLength(1)
		const signature = result.signatures[0]
		expect(signature.status).toBe('verified')
		expect(signature.valid, 'the derived boolean is kept').toBe(true)
		expect(signature.level).toBe('SES')
		expect(String(signature.signer)).not.toBe('')
		expect(signature.signer).not.toBe('Unknown')
		expect(Number.isNaN(Date.parse(signature.timestamp))).toBe(false)
		expect(result.verdict).toBe('verified')
		expect(result.isValid).toBe(true)

		// The verify page renders the engine's verdict.
		await go(page, `signing/verify/${created.fileId}`)
		await expect(page.locator('.verdict-badge')).toHaveText(/^(Verified|Geverifieerd)$/, { timeout: 30_000 })
		await expect(page.locator('.signature-list .status-badge')).toHaveText([/^(Verified|Geverifieerd)$/])
		await expect(page.locator('.signature-list .signer')).toHaveText([String(signature.signer)])
	})

	test('a QES request naming the native provider is refused with 400 and nothing is stored', async ({ request }) => {
		const name = `${RUN}-qes-native.pdf`
		const fileId = await upload(request, name, minimalPdf(`${RUN} qes`))

		const res = await request.post(`${API}/signing/requests`, {
			headers: OCS,
			data: {
				documentFileId: fileId,
				documentName: name,
				signatureLevel: 'QES',
				provider: 'native',
				signingMode: 'sequential',
				signers: [{ userId: USER, displayName: USER, order: 0 }],
			},
		})
		expect(res.status()).toBe(400)
		const body = await res.json()
		expect(body.id ?? body.uuid, 'a refusal carries no request id').toBeUndefined()

		const list = await (await request.get(`${API}/signing/requests`, { headers: OCS })).json()
		const rows: Array<Record<string, any>> = Array.isArray(list) ? list : (list.results ?? list.data ?? [])
		expect(rows.filter((r) => r.documentName === name), 'no signing request was persisted').toHaveLength(0)
	})

	test('declining a completed request is refused and leaves the request and the signer as they were', async ({ request }) => {
		const created = await createRequest(request, 'decline-completed', 1)
		await sign(request, created.id, created.signerIds[0])
		expect((await readRequest(request, created.id)).status).toBe('COMPLETED')
		const signerBefore = await readSigner(request, created.signerIds[0])
		expect(signerBefore.status).toBe('SIGNED')

		const res = await request.post(`${API}/signing/requests/${created.id}/decline`, {
			headers: OCS,
			data: { signerId: created.signerIds[0], reason: 'too late' },
		})
		expect(res.status(), 'the decline is refused').toBeGreaterThanOrEqual(400)
		expect(await res.text(), 'the refusal leaks no exception text').not.toMatch(/Cannot decline request in status/)

		expect((await readRequest(request, created.id)).status).toBe('COMPLETED')
		const signerAfter = await readSigner(request, created.signerIds[0])
		expect(signerAfter.status).toBe('SIGNED')
		expect(signerAfter.declineReason ?? null).toBe(signerBefore.declineReason ?? null)
	})

	test('a pending signer of a request in progress declines with a reason, and the decline is audited', async ({ request }) => {
		const created = await createRequest(request, 'decline-in-progress', 2)
		await sign(request, created.id, created.signerIds[0])
		expect((await readRequest(request, created.id)).status).toBe('IN_PROGRESS')

		const reason = `Not my document (${RUN})`
		const res = await request.post(`${API}/signing/requests/${created.id}/decline`, {
			headers: OCS,
			data: { signerId: created.signerIds[1], reason },
		})
		expect(res.status(), await res.text()).toBe(200)
		const answered = await res.json()
		expect(answered.status).toBe('DECLINED')
		expect(answered.declineReason).toBe(reason)

		expect((await readRequest(request, created.id)).status).toBe('DECLINED')
		const signer = await readSigner(request, created.signerIds[1])
		expect(signer.status).toBe('DECLINED')
		expect(signer.declineReason).toBe(reason)

		const audit = await request.get(`${API}/signing/requests/${created.id}/audit`, { headers: OCS })
		expect(audit.ok(), `GET audit -> ${audit.status()}`).toBeTruthy()
		const entries = (await audit.json()) as Array<Record<string, any>>
		expect(entries.filter((e) => e.action === 'docudesk.signing.DECLINED'), 'one DECLINED audit entry').toHaveLength(1)
	})

	test('a PDF with an external signature and no Filinq marker reads unverifiable, not tampered', async ({ page, request }) => {
		let fileId: number | string = EXTERNAL_FILE_ID
		if (fileId === '') {
			// A signature field whose value is a /Type /Sig dictionary, as a CMS signer writes it.
			fileId = await upload(request, `${RUN}-external.pdf`, minimalPdf(`${RUN} external`, [
				'<< /Type /Sig /Filter /Adobe.PPKLite /SubFilter /adbe.pkcs7.detached /ByteRange [0 0 0 0] /Contents <3082000000> /Name (External signer) >>',
			]))
		}

		const result = await verify(request, fileId)
		expect(result.signatures.length).toBeGreaterThan(0)
		for (const signature of result.signatures) {
			expect(signature.status).toBe('unverifiable')
			expect(signature.reason).toBe('external-signature-unsupported')
			expect(signature.valid).toBe(false)
		}
		expect(result.verdict).toBe('unverifiable')
		expect(result.isValid).toBe(false)

		await go(page, `signing/verify/${fileId}`)
		await expect(page.locator('.verdict-badge')).toHaveText(/^(Unverifiable|Niet verifieerbaar)$/, { timeout: 30_000 })
		await expect(page.locator('.signature-list .reason').first()).toContainText(
			/External signature, not yet supported|Externe handtekening, nog niet ondersteund/,
		)
		await expect(page.locator('.verdict-badge')).not.toHaveText(/Tampered|Gemanipuleerd/)
	})
})
