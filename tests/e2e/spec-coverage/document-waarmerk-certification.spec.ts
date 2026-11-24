/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * document-waarmerk-certification, task 5.2: seal a PDF, open the public
 * verification page its QR points at, and verify the copy as valid, as
 * tampered and, after revocation, as revoked. Also the admin certificate
 * panel, the processing certificate of an anonymisation run and the signing
 * evidence document.
 *
 * WHAT IT SEEDS ITSELF. Every seal test writes its own one-page PDF over
 * WebDAV (a hand-built PDF 1.4 that FPDI can read), seals it through
 * `POST /api/waarmerk/seal` or the "Add waarmerk" action on My Documents,
 * downloads the sealed copy, and hashes it in the test the way the page does
 * with WebCrypto. The tampered copy is the sealed bytes with one byte changed.
 * The revoked seal is a fresh seal revoked through
 * `POST /api/waarmerk/{id}/revoke`. The evidence test creates a signing request
 * with three signer records for the admin, signs all three, and reads the
 * evidence document from the link on the signing request page. Every file it
 * writes is deleted afterwards; waarmerk records are write-once and stay.
 *
 * WHAT IT READS FROM THE INSTANCE. Sealing needs an organisation certificate
 * and a resolvable key, which a test must not make up. Each seal test reads
 * `GET /api/waarmerk/settings` first and skips, saying why, when no valid
 * certificate is configured. The admin-panel test re-submits the instance's
 * own certificate: set `FILINQ_E2E_WAARMERK_CERT_PEM` to that PEM (public
 * material) and `FILINQ_E2E_WAARMERK_CREDENTIAL_REF` to its credential
 * reference; it also skips on an instance in local custody, because saving the
 * broker reference there would delete the stored local key. The processing
 * certificate needs a recorded anonymisation run: set
 * `FILINQ_E2E_ANONYMIZATION_LINK_ID` to the uuid of an `anonymizationLink`
 * the admin can read. Without these, those tests skip and say why.
 *
 * WHAT IT DOES NOT READ. The stamp page's text and the QR live inside a
 * compressed stamp page rendered by mPDF, so the tests count pages (the sealed
 * copy has exactly one page more) instead of reading the text, open the
 * verification page by the route the spec names instead of decoding the QR,
 * and assert the evidence document through its seal, not its text. "No key
 * material in any log line" cannot be read from a browser; the admin test
 * checks the API answers and the stored seals instead.
 *
 * KNOWN DISAGREEMENTS WITH THE CODE (found while writing this):
 *  - `WaarmerkService::stamped()` builds the QR URL from the route name
 *    `filinq.waarmerk.verifyPage`, but `appinfo/routes.php` registers it as
 *    `waarmerkVerify#verifyPage` (route name `filinq.waarmerkVerify.verifyPage`),
 *    so the QR does not encode `/verify/{code}`. No test here can see that.
 *  - `GET /verify/{code}` (waarmerkVerify#verifyPage) and `GET verify/{token}`
 *    (publicVerification#page) resolve to the same path; the waarmerk route is
 *    declared first and wins. These tests expect the waarmerk page there.
 *
 * Excluded in the spec (`@e2e exclude`), covered by PHPUnit: unresolvable key
 * fails sealing closed, evidence fields are write-once, unknown code yields no
 * oracle, non-sealer non-admin cannot revoke, an open request has no evidence
 * document yet.
 *
 * Not run in the change that wrote it (decision 139): the live pass runs it.
 */

// @e2e openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#admin-configures-the-organisation-certificate
// @e2e openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#woo-officer-seals-an-anonymized-pdf
// @e2e openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#processing-certificate-for-an-anonymization-run
// @e2e openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#citizen-verifies-a-sealed-document-via-the-qr
// @e2e openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#tampered-document-is-detected
// @e2e openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#revoked-seal-never-verifies-as-valid
// @e2e openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#the-sender-downloads-the-evidence-document

import type { APIRequestContext, Browser, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { createHash, X509Certificate } from 'crypto'
import { go, waitForNcContentReady } from './_helpers.ts'

/** filinq's API root. */
const API = '/index.php/apps/filinq/api'

/** The waarmerk endpoints. */
const SETTINGS_API = `${API}/waarmerk/settings`
const SEAL_API = `${API}/waarmerk/seal`
const VERIFY_API = `${API}/waarmerk/verify`
const WAARMERK_API = `${API}/waarmerk`

/** The public verification page (the QR target). */
const verifyPage = (code: string) => `/index.php/apps/filinq/verify/${encodeURIComponent(code)}`

/** OpenRegister, for the signer records. */
const OR_API = '/index.php/apps/openregister/api'

/** The signed-in user the WebDAV seed writes for. */
const USER = process.env.NC_ADMIN_USER ?? 'admin'

/** The folder My Documents lists. */
const DOCS_FOLDER = 'DocuDesk'

/** Fixtures the live pass prepares (see the header). */
const CERT_PEM = process.env.FILINQ_E2E_WAARMERK_CERT_PEM ?? ''
const CREDENTIAL_REF = process.env.FILINQ_E2E_WAARMERK_CREDENTIAL_REF ?? ''
const LINK_ID = process.env.FILINQ_E2E_ANONYMIZATION_LINK_ID ?? ''

/** The OCS header every API call carries. */
const OCS = { 'OCS-APIRequest': 'true' }

/** A run-unique prefix for every file this spec writes. */
const RUN = `waarmerk-e2e-${Date.now()}`

/** WebDAV paths written by this spec, deleted in afterAll. */
const written: string[] = []

/** Signing requests created by this spec, cancelled in afterAll. */
const createdRequests: string[] = []

/** What the verify endpoint answers. */
interface Verdict {
	status: string
	organisation: string | null
	sealedAt: string | null
	documentName: string | null
	sealType: string | null
	certificateExpired: boolean | null
	revokedAt: string | null
	revocationReason: string | null
	checkedAt: string
}

/** What a seal answers: the stored record and the sealed file. */
interface Sealed {
	waarmerk: Record<string, any>
	fileId: number
	fileName: string
	sourceFileId: number
}

/**
 * A one-page PDF 1.4 with a correct cross-reference table, so FPDI can read it.
 *
 * @param text The line written on the page.
 * @return The PDF bytes.
 */
function minimalPdf(text: string): Buffer {
	const content = `BT /F1 18 Tf 72 760 Td (${text.replace(/[()\\]/g, '')}) Tj ET`
	const objects = [
		'<< /Type /Catalog /Pages 2 0 R >>',
		'<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
		'<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
		`<< /Length ${Buffer.byteLength(content)} >>\nstream\n${content}\nendstream`,
		'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
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
 * The number of page objects in a PDF (not the page-tree nodes).
 *
 * @param pdf The PDF bytes.
 * @return How many `/Type /Page` objects it holds.
 */
function pageCount(pdf: Buffer): number {
	return (pdf.toString('latin1').match(/\/Type\s*\/Page(?![s\w])/g) ?? []).length
}

/**
 * Hex sha256 of some bytes, as the verification page computes it.
 *
 * @param bytes The bytes.
 * @return The lowercase hex digest.
 */
function sha256(bytes: Buffer): string {
	return createHash('sha256').update(bytes).digest('hex')
}

/**
 * The WebDAV URL of a path in the signed-in user's files.
 *
 * @param path The path relative to the user's root.
 * @return The DAV URL.
 */
function dav(path: string): string {
	return `/remote.php/dav/files/${USER}/${path.split('/').map(encodeURIComponent).join('/')}`
}

/**
 * Upload bytes over WebDAV and answer the Nextcloud file id.
 *
 * @param request The authenticated request context.
 * @param path The path relative to the user's root.
 * @param bytes The file content.
 * @return The file id.
 */
async function upload(request: APIRequestContext, path: string, bytes: Buffer): Promise<number> {
	const put = await request.put(dav(path), { data: bytes, headers: { 'Content-Type': 'application/pdf' } })
	expect([201, 204], `PUT ${path}`).toContain(put.status())
	written.push(path)

	const find = await request.fetch(dav(path), {
		method: 'PROPFIND',
		headers: { Depth: '0', 'Content-Type': 'application/xml' },
		data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns"><d:prop><oc:fileid/></d:prop></d:propfind>',
	})
	expect(find.status()).toBe(207)
	const match = /<oc:fileid>(\d+)<\/oc:fileid>/.exec(await find.text())
	expect(match, `PROPFIND answers the file id of ${path}`).not.toBeNull()

	return Number(match![1])
}

/**
 * Download a file over WebDAV.
 *
 * @param request The authenticated request context.
 * @param path The path relative to the user's root.
 * @return The bytes.
 */
async function download(request: APIRequestContext, path: string): Promise<Buffer> {
	const res = await request.get(dav(path))
	expect(res.status(), `GET ${path}`).toBe(200)

	return Buffer.from(await res.body())
}

/**
 * Skip the calling test unless the instance has a valid organisation certificate.
 *
 * @param request The authenticated request context.
 * @return The settings summary.
 */
async function requireCertificate(request: APIRequestContext): Promise<Record<string, any>> {
	const res = await request.get(SETTINGS_API, { headers: OCS })
	expect(res.ok(), `GET ${SETTINGS_API} -> ${res.status()}`).toBeTruthy()
	const summary = await res.json()
	test.skip(summary.configured !== true, 'No organisation certificate is configured for the waarmerk on this instance')
	test.skip(summary.certificate?.expired === true, 'The organisation certificate on this instance has expired, so sealing is refused')

	return summary
}

/**
 * Write a fresh PDF and seal it through the API.
 *
 * @param request The authenticated request context.
 * @param label A word for the file name.
 * @return The seal answer, the source bytes and the sealed bytes.
 */
async function sealFresh(
	request: APIRequestContext,
	label: string,
): Promise<{ sealed: Sealed; source: Buffer; bytes: Buffer }> {
	const source = minimalPdf(`${RUN} ${label}`)
	const fileId = await upload(request, `${RUN}-${label}.pdf`, source)

	const res = await request.post(SEAL_API, { headers: OCS, data: { fileId } })
	expect(res.status(), `POST ${SEAL_API} -> ${await res.text()}`).toBe(201)
	const sealed = (await res.json()) as Sealed
	written.push(sealed.fileName)

	return { sealed, source, bytes: await download(request, sealed.fileName) }
}

/**
 * Verify a code against a sha256 as an anonymous visitor.
 *
 * @param baseURL The instance.
 * @param browser The browser, for an anonymous API context.
 * @param code The verification code.
 * @param hash The visitor's own sha256.
 * @return What the endpoint answers.
 */
async function verifyAnonymously(baseURL: string, browser: Browser, code: string, hash: string): Promise<Verdict> {
	const context = await browser.newContext({ baseURL, storageState: { cookies: [], origins: [] } })
	try {
		const res = await context.request.post(VERIFY_API, { data: { code, sha256: hash } })
		expect(res.status(), 'every verification outcome answers 200').toBe(200)

		return (await res.json()) as Verdict
	} finally {
		await context.close()
	}
}

/**
 * Open the verification page in a logged-out browser and check a local copy.
 *
 * @param baseURL The instance.
 * @param browser The browser.
 * @param code The verification code.
 * @param copy The bytes of the visitor's copy, or null to only open the page.
 * @param check What to assert on the open page.
 * @return The bodies of every request the page sent after the file was chosen.
 */
async function onVerifyPage(
	baseURL: string,
	browser: Browser,
	code: string,
	copy: Buffer | null,
	check: (page: Page) => Promise<void>,
): Promise<string[]> {
	const context = await browser.newContext({ baseURL, storageState: { cookies: [], origins: [] } })
	const sent: string[] = []
	try {
		const page = await context.newPage()
		await page.goto(verifyPage(code), { waitUntil: 'domcontentloaded' })
		await expect(page, 'a visitor without an account is not sent to the login').not.toHaveURL(/\/login/)
		await expect(page.getByRole('heading', { level: 1, name: /Check a waarmerk|Een waarmerk controleren/ })).toBeVisible({
			timeout: 30_000,
		})
		if (copy !== null) {
			page.on('request', (r) => {
				if (r.method() !== 'GET') {
					sent.push(r.postData() ?? '')
				}
			})
			await page.locator('#waarmerk-file').setInputFiles({ name: 'copy.pdf', mimeType: 'application/pdf', buffer: copy })
		}
		await check(page)

		return sent
	} finally {
		await context.close()
	}
}

test.describe('document waarmerk: seal, verify, revoke', () => {
	test.describe.configure({ mode: 'serial' })
	test.setTimeout(180_000)

	test.afterAll(async ({ request }) => {
		for (const id of createdRequests) {
			await request.delete(`${API}/signing/requests/${id}`, { headers: OCS }).catch(() => {})
		}
		for (const path of written) {
			await request.delete(dav(path)).catch(() => {})
		}
	})

	test('the admin configures the certificate and the panel shows subject, fingerprint and expiry, never a key', async ({ page, request }) => {
		test.skip(CERT_PEM === '' || CREDENTIAL_REF === '', 'FILINQ_E2E_WAARMERK_CERT_PEM and FILINQ_E2E_WAARMERK_CREDENTIAL_REF are not set')
		const before = await (await request.get(SETTINGS_API, { headers: OCS })).json()
		test.skip(before.custody === 'local', 'The instance holds its key in local custody; saving a broker reference would delete it')

		const expected = new X509Certificate(CERT_PEM)

		await page.goto('/index.php/settings/admin/filinq', { waitUntil: 'domcontentloaded' })
		await waitForNcContentReady(page)
		const section = page.locator('.settings-section', { hasText: /Waarmerk \(organisation seal\)|Waarmerk \(organisatiezegel\)/ }).first()
		await expect(section).toBeVisible({ timeout: 30_000 })
		await section.locator('#waarmerk-certificate').fill(CERT_PEM)
		await section.getByLabel(/^(Credential reference|Verwijzing naar de sleutel)$/).fill(CREDENTIAL_REF)
		const saved = page.waitForResponse((r) => r.url().includes('/api/waarmerk/settings') && r.request().method() === 'PUT')
		await section.getByRole('button', { name: /Save waarmerk settings|Waarmerkinstellingen opslaan/ }).click()
		expect((await saved).status()).toBe(200)

		// The panel shows what the server derived from the PEM.
		await expect(section).toContainText(expected.fingerprint256)
		const summary = await (await request.get(SETTINGS_API, { headers: OCS })).json()
		expect(summary.configured).toBe(true)
		expect(summary.credentialRef).toBe(CREDENTIAL_REF)
		expect(summary.certificate.fingerprint).toBe(expected.fingerprint256)
		expect(new Date(summary.certificate.validTo).getTime()).toBe(new Date(expected.validTo).getTime())
		await expect(section).toContainText(summary.certificate.subject)
		await expect(section).toContainText(summary.certificate.validTo)
		expect(summary.qualified, 'the panel never claims a qualified seal').toBe(false)

		// No key material in the settings answer, the app settings or any stored seal.
		for (const url of [SETTINGS_API, `${API}/settings`, WAARMERK_API]) {
			const text = await (await request.get(url, { headers: OCS })).text()
			expect(text, `${url} carries no private key`).not.toMatch(/PRIVATE KEY/)
		}
	})

	test('"Add waarmerk" on My Documents writes a sealed copy with one extra page and an active record', async ({ page, request }) => {
		await requireCertificate(request)

		const name = `${RUN}-ui.pdf`
		const source = minimalPdf(`${RUN} sealed from the UI`)
		await request.fetch(dav(DOCS_FOLDER), { method: 'MKCOL' })
		const sourceId = await upload(request, `${DOCS_FOLDER}/${name}`, source)

		await go(page, 'my-documents')
		const row = page.locator('tr').filter({ hasText: name.replace(/\.pdf$/, '') }).first()
		await expect(row).toBeVisible({ timeout: 30_000 })
		await row.getByRole('button', { name: /actions/i }).click()
		await page.getByRole('menuitem', { name: /^(Add waarmerk|Waarmerk toevoegen)$/ }).click()

		const dialog = page.getByTestId('waarmerk-seal')
		await expect(dialog).toContainText(/not a qualified electronic seal|geen gekwalificeerd/)
		const sealedResponse = page.waitForResponse((r) => r.url().includes('/api/waarmerk/seal'))
		await dialog.getByTestId('waarmerk-seal-confirm').click()
		const res = await sealedResponse
		expect(res.status()).toBe(201)
		const sealed = (await res.json()) as Sealed
		written.push(`${DOCS_FOLDER}/${sealed.fileName}`)
		expect(sealed.sourceFileId).toBe(sourceId)

		await expect(dialog).toContainText(sealed.fileName)
		await expect(dialog).toContainText(String(sealed.waarmerk.verificationCode))

		// The stamp is appended as a final page; the existing page is kept.
		const bytes = await download(request, `${DOCS_FOLDER}/${sealed.fileName}`)
		expect(pageCount(bytes)).toBe(pageCount(source) + 1)

		// The stored record: active, the hash of the sealed file, a CMS signature.
		const shown = await request.get(`${WAARMERK_API}/${sealed.waarmerk.uuid}`, { headers: OCS })
		expect(shown.ok()).toBeTruthy()
		const record = await shown.json()
		expect(record.status).toBe('active')
		expect(record.sealType).toBe('waarmerk')
		expect(record.documentHash).toBe(sha256(bytes))
		expect(String(record.cmsSignature).length).toBeGreaterThan(100)
		expect(record.verificationCode).toBe(sealed.waarmerk.verificationCode)

		// The original is unchanged.
		expect(sha256(await download(request, `${DOCS_FOLDER}/${name}`))).toBe(sha256(source))
	})

	test('a processing certificate is sealed for an anonymisation run with types and counts only', async ({ request }) => {
		test.skip(LINK_ID === '', 'FILINQ_E2E_ANONYMIZATION_LINK_ID is not set: no recorded anonymisation run on this instance')
		await requireCertificate(request)

		const res = await request.post(`${API}/waarmerk/anonymization/${encodeURIComponent(LINK_ID)}`, { headers: OCS })
		expect(res.status(), await res.text()).toBe(201)
		const body = await res.json()
		written.push(body.fileName)

		expect(body.waarmerk.sealType).toBe('processing-certificate')
		expect(body.waarmerk.anonymizationLinkId).toBe(LINK_ID)
		expect(body.waarmerk.status).toBe('active')

		// What the certificate states: recorded run fields, counts per type, the backend, the link.
		const allowed = new Set([
			'sourceFileName', 'sourceFilePath', 'anonymizedFileName', 'anonymizedFilePath', 'outputFormat',
			'replacementCount', 'runCount', 'anonymizedAt', 'anonymizedBy', 'verificationVerdict',
			'entityTypeCounts', 'entityTypeCountsRecorded', 'detectionBackend', 'anonymizationLinkId',
		])
		for (const key of Object.keys(body.certificate)) {
			expect(allowed.has(key), `the certificate carries no field "${key}" beyond the recorded run`).toBe(true)
		}
		for (const [type, count] of Object.entries(body.certificate.entityTypeCounts as Record<string, unknown>)) {
			expect(type, 'a count is keyed by an entity type, never a value').toMatch(/^[A-Z][A-Z0-9_]*$/)
			expect(typeof count).toBe('number')
		}
		expect(typeof body.certificate.detectionBackend).toBe('string')

		// The certificate PDF verifies as sealed.
		const bytes = await download(request, body.fileName)
		expect(body.waarmerk.documentHash).toBe(sha256(bytes))
	})

	test('a citizen opens the QR page, chooses the copy and reads valid, and the file is not uploaded', async ({ baseURL, browser, request }) => {
		const settings = await requireCertificate(request)
		const { sealed, bytes } = await sealFresh(request, 'valid')
		const code = String(sealed.waarmerk.verificationCode)

		const sent = await onVerifyPage(baseURL!, browser, code, bytes, async (page) => {
			await expect(page.getByRole('heading', { name: /This waarmerk exists|Dit waarmerk bestaat/ })).toBeVisible()
			await expect(page.locator('.waarmerk-verify__facts')).toContainText(sealed.fileName)
			if (settings.certificate?.organisation) {
				await expect(page.locator('.waarmerk-verify__facts')).toContainText(settings.certificate.organisation)
			}
			await expect(page.locator('.waarmerk-verify__facts')).toContainText(String(sealed.waarmerk.sealedAt))
			const result = page.getByRole('status')
			await expect(result).toContainText(/^(Valid|Geldig)/, { timeout: 30_000 })
			await expect(result).toContainText(/exactly the document that was sealed|precies het document dat is gewaarmerkt/)
			// The page offers no download of the document.
			await expect(page.locator('a[download], a[href$=".pdf"]')).toHaveCount(0)
		})

		// Only the code and the locally computed hash left the browser.
		expect(sent).toHaveLength(1)
		expect(JSON.parse(sent[0])).toEqual({ code, sha256: sha256(bytes) })
		expect(sent[0]).not.toContain('%PDF')

		const verdict = await verifyAnonymously(baseURL!, browser, code, sha256(bytes))
		expect(verdict.status).toBe('valid')
		expect(verdict.documentName).toBe(sealed.fileName)
		expect(verdict.sealedAt).toBe(sealed.waarmerk.sealedAt)
		expect(verdict.certificateExpired).toBe(false)
	})

	test('a copy changed after sealing reads hash-mismatch, never valid', async ({ baseURL, browser, request }) => {
		await requireCertificate(request)
		const { sealed, bytes } = await sealFresh(request, 'tampered')
		const code = String(sealed.waarmerk.verificationCode)

		// One byte changed inside the first page's text.
		const tampered = Buffer.from(bytes)
		const at = tampered.indexOf(Buffer.from(RUN, 'latin1'))
		const index = at > 0 ? at : Math.floor(tampered.length / 2)
		tampered[index] = tampered[index] === 0x41 ? 0x42 : 0x41
		expect(sha256(tampered)).not.toBe(sha256(bytes))

		const verdict = await verifyAnonymously(baseURL!, browser, code, sha256(tampered))
		expect(verdict.status).toBe('hash-mismatch')
		expect(verdict.organisation, 'a non-valid answer names nothing').toBeNull()

		await onVerifyPage(baseURL!, browser, code, tampered, async (page) => {
			const result = page.getByRole('status')
			await expect(result).toContainText(/Not the sealed document|Niet het gewaarmerkte document/, { timeout: 30_000 })
			await expect(result).not.toContainText(/^(Valid|Geldig)\b/)
		})
	})

	test('a revoked seal reads revoked for the unmodified copy, and the page shows date and reason', async ({ baseURL, browser, request }) => {
		await requireCertificate(request)
		const { sealed, bytes } = await sealFresh(request, 'revoked')
		const code = String(sealed.waarmerk.verificationCode)
		const reason = 'document withdrawn after objection'

		const revoked = await request.post(`${WAARMERK_API}/${sealed.waarmerk.uuid}/revoke`, { headers: OCS, data: { reason } })
		expect(revoked.status(), await revoked.text()).toBe(200)
		const record = await revoked.json()
		expect(record.status).toBe('revoked')
		expect(record.revocationReason).toBe(reason)
		expect(record.documentHash, 'revocation leaves the evidence fields alone').toBe(sealed.waarmerk.documentHash)

		const verdict = await verifyAnonymously(baseURL!, browser, code, sha256(bytes))
		expect(verdict.status).toBe('revoked')
		expect(verdict.revocationReason).toBe(reason)
		expect(verdict.revokedAt).toBe(record.revokedAt)

		await onVerifyPage(baseURL!, browser, code, null, async (page) => {
			await expect(page.getByRole('heading', { name: /This waarmerk has been revoked|Dit waarmerk is ingetrokken/ })).toBeVisible()
			const facts = page.locator('.waarmerk-verify__facts')
			await expect(facts).toContainText(/Revoked on|Ingetrokken op/)
			await expect(facts).toContainText(String(record.revokedAt))
			await expect(facts).toContainText(reason)
			// No file check is offered for a revoked seal, and nothing names who revoked it.
			await expect(page.locator('#waarmerk-file')).toHaveCount(0)
			await expect(facts).not.toContainText(String(record.revokedBy))
		})

		// Revocation is irreversible: a second revoke is refused.
		const again = await request.post(`${WAARMERK_API}/${sealed.waarmerk.uuid}/revoke`, { headers: OCS, data: { reason: 'again' } })
		expect(again.status()).toBe(409)
	})

	test('the sender downloads one sealed evidence document for a request three signers signed', async ({ baseURL, browser, page, request }) => {
		await requireCertificate(request)

		const name = `${RUN}-evidence.pdf`
		const fileId = await upload(request, name, minimalPdf(`${RUN} evidence`))
		const created = await request.post(`${API}/signing/requests`, {
			headers: OCS,
			data: {
				documentFileId: fileId,
				documentName: name,
				signatureLevel: 'SES',
				signingMode: 'sequential',
				signers: [1, 2, 3].map((order) => ({ userId: USER, displayName: `${USER} ${order}`, order })),
			},
		})
		expect(created.status(), await created.text()).toBe(201)
		const requestBody = await created.json()
		const id = String(requestBody.id ?? requestBody.uuid)
		createdRequests.push(id)
		const signerIds: string[] = requestBody.signerIds ?? []
		expect(signerIds).toHaveLength(3)

		for (const signerId of signerIds) {
			const signed = await request.post(`${API}/signing/requests/${id}/sign`, { headers: OCS, data: { signerId } })
			expect(signed.status(), `sign as ${signerId}: ${await signed.text()}`).toBeLessThan(300)
		}
		const after = await (await request.get(`${API}/signing/requests/${id}`, { headers: OCS })).json()
		expect(after.status).toBe('COMPLETED')

		// The signing request page offers the download on its evidence card.
		await go(page, `signing/${id}`)
		const link = page.getByRole('link', { name: /Download evidence document \(pdf\)|Bewijsdocument downloaden \(pdf\)/ })
		await expect(link).toBeVisible({ timeout: 30_000 })
		const href = String(await link.getAttribute('href'))
		expect(href).toContain(`/api/signing/${id}/evidence`)

		const res = await request.get(href)
		expect(res.status()).toBe(200)
		expect(res.headers()['content-type']).toContain('application/pdf')
		const pdf = Buffer.from(await res.body())
		expect(pdf.subarray(0, 5).toString('latin1')).toBe('%PDF-')

		// The evidence document carries a signing-evidence waarmerk that verifies as valid.
		const seals = (await (await request.get(WAARMERK_API, { headers: OCS })).json()).results as Array<Record<string, any>>
		const seal = seals.find((s) => s.documentHash === sha256(pdf))
		expect(seal, 'a waarmerk record holds the hash of the downloaded evidence document').toBeTruthy()
		expect(seal!.sealType).toBe('signing-evidence')

		const verdict = await verifyAnonymously(baseURL!, browser, String(seal!.verificationCode), sha256(pdf))
		expect(verdict.status).toBe('valid')
		expect(verdict.sealType).toBe('signing-evidence')

		// Each of the three signer records is signed, so the evidence covers three signers.
		for (const signerId of signerIds) {
			const signer = await request.get(`${OR_API}/objects/filinq/signerRecord/${signerId}`, { headers: OCS })
			expect(signer.ok(), `signer record ${signerId}`).toBeTruthy()
			expect((await signer.json()).status).toBe('SIGNED')
		}
	})
})
