/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * signature-verification-portal, task 4.2: the public portal a signed PDF's
 * QR points at, opened by a visitor with no account. Every page and public
 * API call in this file runs in a logged-out browser context
 * (`browser.newContext({ storageState: { cookies: [], origins: [] } })`).
 *
 * WHAT IT SEEDS ITSELF. One signed document, made once for the file as the
 * signed-in admin: a one-page PDF uploaded over WebDAV, a native SES signing
 * request with the admin as its one signer, and the signature. Completion
 * stamps the QR, signs and mints the verification record; the request then
 * carries `verificationToken`, which is the token the QR encodes. The signed
 * bytes are read back over WebDAV as "the downloaded copy". The request and
 * the file are removed afterwards; the verification record stays (it is the
 * evidence).
 *
 * WHAT IT READS FROM THE INSTANCE. Native signing needs the signing secret.
 * When the instance has none, the signature verifies as
 * `signing-secret-not-configured` and the tests that need a verified
 * signature skip and say so. The scenario "signer shown as asserted while the
 * MAC excludes it" needs a record minted with `identityBound: false`. Since
 * signing-trust-rebuild binds the signer into the MAC, a fresh signature is
 * minted with `identityBound: true`, so that state cannot be made here: point
 * `FILINQ_E2E_UNBOUND_VERIFY_TOKEN` at the token of such a record (one minted
 * before the rebuild); without it that test skips. Its sibling assertion, that
 * the fresh record's signer line matches its own flags, runs in the first test.
 *
 * WHAT IT DOES NOT READ. The QR is drawn as vector modules on the last page;
 * the tests do not decode it. They open `verify/{token}` with the token the
 * request recorded, and check the downloaded copy against the portal's own
 * "Compare your copy" (a local WebCrypto hash, no upload).
 *
 * KNOWN DISAGREEMENT WITH THE CODE (found while writing this): the portal page
 * `verify/{token}` (publicVerification#page) and the waarmerk page
 * `/verify/{code}` (waarmerkVerify#verifyPage, document-waarmerk-certification)
 * resolve to the same path in `appinfo/routes.php`, and the waarmerk route is
 * declared first. A visitor opening a signature QR therefore gets the waarmerk
 * page ("No waarmerk is known with this code"). The page tests below assert the
 * portal and fail until the routes are separated; the API tests are unaffected.
 *
 * Excluded in the spec (`@e2e exclude`), covered by PHPUnit: unknown token is
 * not an existence oracle (asserted at the API layer), a rewritten signer field
 * is not presented as verified identity, an external CMS signature reports
 * unverifiable, tokens are non-enumerable, repeated attempts are throttled,
 * one QR resolves both signature and seal status.
 *
 * Not run in the change that wrote it (decision 139): the live pass runs it.
 */

// @e2e openspec/changes/signature-verification-portal/specs/signature-verification-portal/spec.md#anonymous-visitor-verifies-a-signed-document-via-its-qr
// @e2e openspec/changes/signature-verification-portal/specs/signature-verification-portal/spec.md#signer-identity-shown-as-asserted-while-the-mac-excludes-it
// @e2e openspec/changes/signature-verification-portal/specs/signature-verification-portal/spec.md#signing-completion-mints-a-verification-record
// @e2e openspec/changes/signature-verification-portal/specs/signature-verification-portal/spec.md#public-page-is-reachable-logged-out-and-the-app-is-not-group-restricted
// @e2e openspec/changes/signature-verification-portal/specs/signature-verification-portal/spec.md#a-downloaded-signed-pdf-is-verifiable-from-its-stamped-qr

import type { APIRequestContext, Browser, BrowserContext, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { createHash, randomBytes } from 'crypto'
import { readFileSync } from 'fs'
import * as path from 'path'

/** filinq's API root. */
const API = '/index.php/apps/filinq/api'

/** OpenRegister, for the stored verification record. */
const OR_API = '/index.php/apps/openregister/api'

/** The public page and endpoint for a token. */
const portalPage = (token: string) => `/index.php/apps/filinq/verify/${encodeURIComponent(token)}`
const portalApi = (token: string) => `${API}/verify/${encodeURIComponent(token)}`

/** The signed-in user the seed writes and signs as. */
const USER = process.env.NC_ADMIN_USER ?? 'admin'

/** A record minted with `identityBound: false` (see the header). */
const UNBOUND_TOKEN = process.env.FILINQ_E2E_UNBOUND_VERIFY_TOKEN ?? ''

/** The OCS header every authenticated API call carries. */
const OCS = { 'OCS-APIRequest': 'true' }

/** A run-unique file name. */
const FILE_NAME = `portal-e2e-${Date.now()}.pdf`

/** The keys every verdict has, valid or not. */
const VERDICT_KEYS = ['audit', 'contentHash', 'fileName', 'recordedAt', 'seal', 'signatures', 'status']

/** What the one seeded signature left behind. */
interface Seeded {
	requestId: string
	fileId: number
	token: string
	signed: Buffer
	secretMissing: boolean
}

let seeded: Seeded | null = null

/**
 * A one-page PDF 1.4 with a correct cross-reference table, so the QR stamp can import it.
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
 * Hex sha256 of some bytes.
 *
 * @param bytes The bytes.
 * @return The lowercase hex digest.
 */
function sha256(bytes: Buffer): string {
	return createHash('sha256').update(bytes).digest('hex')
}

/**
 * The WebDAV URL of a file in the signed-in user's root.
 *
 * @param name The file name.
 * @return The DAV URL.
 */
function dav(name: string): string {
	return `/remote.php/dav/files/${USER}/${encodeURIComponent(name)}`
}

/**
 * Sign one document as the admin, once per file, and remember what it left.
 *
 * @param request The authenticated request context.
 * @return The request id, file id, verification token and signed bytes.
 */
async function signedDocument(request: APIRequestContext): Promise<Seeded> {
	if (seeded !== null) return seeded

	const put = await request.put(dav(FILE_NAME), { data: minimalPdf(FILE_NAME), headers: { 'Content-Type': 'application/pdf' } })
	expect([201, 204]).toContain(put.status())
	const find = await request.fetch(dav(FILE_NAME), {
		method: 'PROPFIND',
		headers: { Depth: '0', 'Content-Type': 'application/xml' },
		data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns"><d:prop><oc:fileid/></d:prop></d:propfind>',
	})
	const fileId = Number(/<oc:fileid>(\d+)<\/oc:fileid>/.exec(await find.text())?.[1] ?? 0)
	expect(fileId, 'PROPFIND answers the file id').toBeGreaterThan(0)

	const created = await request.post(`${API}/signing/requests`, {
		headers: OCS,
		data: {
			documentFileId: fileId,
			documentName: FILE_NAME,
			signatureLevel: 'SES',
			provider: 'native',
			signingMode: 'sequential',
			signers: [{ userId: USER, displayName: USER, order: 0 }],
		},
	})
	expect(created.status(), await created.text()).toBe(201)
	const body = await created.json()
	const requestId = String(body.id ?? body.uuid)
	const signerId = String((body.signerIds ?? [])[0] ?? '')
	expect(signerId).not.toBe('')

	const signed = await request.post(`${API}/signing/requests/${requestId}/sign`, { headers: OCS, data: { signerId } })
	expect(signed.status(), await signed.text()).toBeLessThan(300)

	const after = await (await request.get(`${API}/signing/requests/${requestId}`, { headers: OCS })).json()
	expect(after.status).toBe('COMPLETED')
	const token = String(after.verificationToken ?? '')

	const bytes = await request.get(dav(FILE_NAME))
	expect(bytes.status()).toBe(200)

	const verified = await (await request.get(`${API}/signing/verify/${fileId}`, { headers: OCS })).json()
	const secretMissing = (verified.signatures ?? []).some((s: any) => s.reason === 'signing-secret-not-configured')

	seeded = { requestId, fileId, token, signed: Buffer.from(await bytes.body()), secretMissing }

	return seeded
}

/**
 * A logged-out browser context on the instance.
 *
 * @param browser The browser.
 * @param baseURL The instance.
 * @return A context with no cookies and no storage.
 */
async function anonymous(browser: Browser, baseURL: string): Promise<BrowserContext> {
	return browser.newContext({ baseURL, storageState: { cookies: [], origins: [] } })
}

/**
 * Open the portal for a token and wait for its verdict to paint.
 *
 * @param context The logged-out context.
 * @param token The token.
 * @return The page.
 */
async function openPortal(context: BrowserContext, token: string): Promise<Page> {
	const page = await context.newPage()
	const res = await page.goto(portalPage(token), { waitUntil: 'domcontentloaded' })
	expect(res?.status(), 'the portal answers 200, never 404').toBe(200)
	await expect(page, 'a visitor without an account is not sent to the login').not.toHaveURL(/\/login/)
	await expect(page.getByRole('heading', { level: 1, name: /Check a signed document|Een ondertekend document controleren/ })).toBeVisible({
		timeout: 30_000,
	})
	await expect(page.getByTestId('verification-verdict')).toBeVisible({ timeout: 30_000 })

	return page
}

test.describe('signature verification portal (logged out)', () => {
	test.describe.configure({ mode: 'serial' })
	test.setTimeout(180_000)

	test.afterAll(async ({ request }) => {
		if (seeded !== null) {
			await request.delete(`${API}/signing/requests/${seeded.requestId}`, { headers: OCS }).catch(() => {})
		}
		await request.delete(dav(FILE_NAME)).catch(() => {})
	})

	test('signing completion mints a record with a token, the content hash and the signature summary, and no bytes', async ({ baseURL, browser, request }) => {
		const doc = await signedDocument(request)
		expect(doc.token, 'the completed request carries a 128-bit hex token').toMatch(/^[0-9a-f]{32}$/)

		const context = await anonymous(browser, baseURL!)
		try {
			const res = await context.request.get(portalApi(doc.token))
			expect(res.status()).toBe(200)
			const text = await res.text()
			const verdict = JSON.parse(text)
			expect(Object.keys(verdict).sort()).toEqual(VERDICT_KEYS)
			expect(verdict.fileName).toBe(FILE_NAME)
			expect(verdict.contentHash, 'the record hashes the stored signed artifact').toBe(sha256(doc.signed))
			expect(verdict.signatures).toHaveLength(1)
			expect(verdict.signatures[0].level).toBe('SES')
			expect(verdict.signatures[0].signerAsserted).not.toBe('')
			expect(verdict.audit.signatureCount).toBe(1)
			expect(text, 'the public answer carries no document bytes').not.toContain('%PDF')
		} finally {
			await context.close()
		}

		// The stored record itself: the token, the hash and the summary; no bytes, no secret.
		const stored = await request.get(`${OR_API}/objects/filinq/signatureVerification?token=${doc.token}`, { headers: OCS })
		expect(stored.ok(), `read the signatureVerification record -> ${stored.status()}`).toBeTruthy()
		const rows = ((await stored.json()).results ?? []) as Array<Record<string, any>>
		const record = rows.find((r) => (r.token ?? r.object?.token) === doc.token)
		expect(record, 'one signatureVerification object holds the token').toBeTruthy()
		const json = JSON.stringify(record)
		expect(json).toContain(sha256(doc.signed))
		expect(json).not.toContain('%PDF')
		expect(json).not.toMatch(/"(secret|signingSecret|hmacKey|content|bytes)"\s*:/)
	})

	test('an anonymous visitor opens verify/{token} and reads the verdict, the level and the audit summary, with no download', async ({ baseURL, browser, request }) => {
		const doc = await signedDocument(request)
		test.skip(doc.secretMissing, 'The instance has no signing secret, so the native signature cannot verify')

		const context = await anonymous(browser, baseURL!)
		const documents: string[] = []
		try {
			context.on('response', (r) => {
				if ((r.headers()['content-type'] ?? '').includes('application/pdf')) documents.push(r.url())
			})
			const page = await openPortal(context, doc.token)

			await expect(page.getByTestId('verification-verdict')).toContainText(
				/The document content is unchanged since it was signed\.|De inhoud van het document is niet veranderd sinds het is ondertekend\./,
			)
			await expect(page.getByTestId('verification-file-name')).toHaveText(FILE_NAME)
			await expect(page.getByTestId('verification-audit')).toContainText(/1 of 1 signature verified|1 van 1 handtekening gecontroleerd/)
			const signature = page.getByTestId('verification-signature')
			await expect(signature).toHaveCount(1)
			await expect(signature).toContainText(/Level: SES|Niveau: SES/)
			await expect(signature).toContainText(/^(Verified|Geverifieerd)/)

			// The signer line follows the record's own flags.
			const verdict = await (await context.request.get(portalApi(doc.token))).json()
			const sig = verdict.signatures[0]
			const signer = page.getByTestId('verification-signer')
			if (sig.integrityVerified === true && sig.identityBound === true) {
				await expect(signer).toContainText(/Signed by|Ondertekend door/)
			} else {
				await expect(signer).toContainText(/not cryptographically bound|niet cryptografisch aan de handtekening gebonden/)
			}

			// No bytes served, no download offered, no path shown.
			await expect(page.locator('a[download], a[href*="/remote.php/"], a[href$=".pdf"]')).toHaveCount(0)
			expect(documents, 'the portal fetched no PDF').toHaveLength(0)
			await expect(page.locator('body')).not.toContainText(`/${USER}/files/`)
		} finally {
			await context.close()
		}
	})

	test('a record with identityBound false shows the signer as stated, not cryptographically bound', async ({ baseURL, browser }) => {
		test.skip(UNBOUND_TOKEN === '', 'FILINQ_E2E_UNBOUND_VERIFY_TOKEN is not set: no record minted with identityBound false on this instance')

		const context = await anonymous(browser, baseURL!)
		try {
			const verdict = await (await context.request.get(portalApi(UNBOUND_TOKEN))).json()
			expect(verdict.signatures.length).toBeGreaterThan(0)
			for (const sig of verdict.signatures) {
				expect(sig.identityBound, 'the fixture is an unbound record').toBe(false)
			}

			const page = await openPortal(context, UNBOUND_TOKEN)
			const signers = page.getByTestId('verification-signer')
			await expect(signers.first()).toContainText(
				/Signer as stated: .+ \(not cryptographically bound to the signature\)|Ondertekenaar zoals opgegeven: .+ \(niet cryptografisch aan de handtekening gebonden\)/,
			)
			await expect(page.locator('body')).not.toContainText(/Signed by|Ondertekend door|signer identity verified/i)
			if (verdict.signatures.some((s: any) => s.integrityVerified === true)) {
				await expect(page.getByTestId('verification-verdict')).toContainText(/unchanged since it was signed|niet veranderd sinds het is ondertekend/)
			}
		} finally {
			await context.close()
		}
	})

	test('the portal loads logged out, an unknown token reads unknown and not 404, and info.xml has no group restriction', async ({ baseURL, browser }) => {
		const unknown = randomBytes(16).toString('hex')
		const context = await anonymous(browser, baseURL!)
		try {
			const page = await openPortal(context, unknown)
			await expect(page.getByTestId('verification-verdict')).toContainText(
				/No signed document matches this link\.|Bij deze link hoort geen ondertekend document\./,
			)
			await expect(page.getByTestId('verification-signature')).toHaveCount(0)

			const res = await context.request.get(portalApi(unknown))
			expect(res.status()).toBe(200)
			const verdict = await res.json()
			expect(verdict.status).toBe('unknown')
			expect(Object.keys(verdict).sort()).toEqual(VERDICT_KEYS)
		} finally {
			await context.close()
		}

		const infoXml = readFileSync(path.resolve(__dirname, '..', '..', '..', 'appinfo', 'info.xml'), 'utf8')
		expect(infoXml, 'appinfo/info.xml declares no group restriction').not.toMatch(/<groups?\b/)
	})

	test('the downloaded signed copy, opened through its QR link, compares as the signed document', async ({ baseURL, browser, request }) => {
		const doc = await signedDocument(request)
		expect(doc.signed.subarray(0, 5).toString('latin1')).toBe('%PDF-')

		const context = await anonymous(browser, baseURL!)
		try {
			const page = await openPortal(context, doc.token)
			await expect(page.getByTestId('verification-file-name')).toHaveText(FILE_NAME)

			const sent: string[] = []
			page.on('request', (r) => {
				if (r.method() !== 'GET') sent.push(r.url())
			})
			await page.getByTestId('verification-copy').setInputFiles({ name: FILE_NAME, mimeType: 'application/pdf', buffer: doc.signed })
			await expect(page.getByTestId('verification-copy-result')).toContainText(
				/Your copy is the document that was signed\.|Uw kopie is het document dat is ondertekend\./,
			)
			expect(sent, 'choosing the copy uploads nothing').toHaveLength(0)

			// A changed copy does not match.
			const changed = Buffer.concat([doc.signed, Buffer.from('\n% changed\n')])
			await page.getByTestId('verification-copy').setInputFiles({ name: 'changed.pdf', mimeType: 'application/pdf', buffer: changed })
			await expect(page.getByTestId('verification-copy-result')).toContainText(
				/Your copy is not the document that was signed\.|Uw kopie is niet het document dat is ondertekend\./,
			)
		} finally {
			await context.close()
		}
	})
})
