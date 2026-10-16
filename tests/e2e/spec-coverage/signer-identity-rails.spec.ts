/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage: signer identity rails (REQ-DDSIR-002, 003, 004).
 *
 * The identity broker is a mock OpenID Connect provider that THIS spec starts
 * in its own process (`startMockOidc` below): a small Node https server that
 * serves discovery, authorize, token, userinfo and jwks, and signs its ID
 * tokens with the fixed test key in `tests/e2e/fixtures/mock-oidc/`. No
 * container, no network dependency.
 *
 * Two channels reach it:
 *  - the FRONT channel (authorize) is the test browser, always on
 *    `https://localhost:<port>`; the browser context ignores the test
 *    certificate's issuer, nothing else does;
 *  - the BACK channel (token) is Nextcloud itself, server-side. Filinq refuses
 *    a broker that is not https, and Nextcloud's HTTP client refuses both an
 *    untrusted certificate and a local address. So the back channel only
 *    works on an instance whose administrator made two decisions for it:
 *    trust `tests/e2e/fixtures/mock-oidc/test-cert.pem`
 *    (`occ security:certificates:import`) and allow the back-channel host
 *    (`allow_local_remote_servers`). This spec never makes those changes. It
 *    runs the scenarios that need the back channel only when the operator
 *    says the instance is ready, with FILINQ_E2E_OIDC_BACKCHANNEL=1
 *    (and FILINQ_E2E_OIDC_BACKCHANNEL_HOST when Nextcloud reaches the test
 *    host under another name, such as `host.docker.internal`). Without it
 *    those two tests are reported as SKIPPED, with the reason, never as passed.
 *
 * The broker settings, the credential in OpenRegister and every signing
 * request this spec creates are restored or removed in afterAll.
 */

// @e2e openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md#request-cannot-undercut-its-level-floor
// @e2e openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md#substantial-request-refuses-a-session-only-signer
// @e2e openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md#step-up-unlocks-the-signature
// @e2e openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md#evidence-lands-on-record-audit-and-artifact-together

import type { APIRequestContext, Page } from '@playwright/test'
import type { Server } from 'https'
import type { AddressInfo } from 'net'

import { expect, request as playwrightRequest, test } from '@playwright/test'
import { createPrivateKey, createPublicKey, randomBytes, sign } from 'crypto'
import { readFileSync } from 'fs'
import { createServer } from 'https'
import * as path from 'path'
import { resolveBaseUrl } from '../base-url.ts'
import { go } from './_helpers.ts'

test.describe.configure({ mode: 'serial' })
// Only the test browser skips the check of the mock's self-signed issuer.
test.use({ ignoreHTTPSErrors: true })

const FIXTURES = path.resolve(__dirname, '..', 'fixtures', 'mock-oidc')
const API = '/index.php/apps/filinq/api'
const OR_API = '/index.php/apps/openregister/api'
const ADMIN_USER = process.env.NC_ADMIN_USER || 'admin'
const ADMIN_PASS = process.env.NC_ADMIN_PASS || 'admin'
const BACKCHANNEL = process.env.FILINQ_E2E_OIDC_BACKCHANNEL === '1'
const BACKCHANNEL_HOST = process.env.FILINQ_E2E_OIDC_BACKCHANNEL_HOST || 'localhost'
const BACKCHANNEL_REASON =
	'FILINQ_E2E_OIDC_BACKCHANNEL is not set: the instance under test was not '
	+ 'prepared to reach the mock broker server-side (trust tests/e2e/fixtures/mock-oidc/test-cert.pem '
	+ 'and allow the back-channel host), so the token exchange cannot succeed here.'

/** DigiD Midden in the default acr mapping: means `digid`, assurance `substantial`. */
const ACR_DIGID_SUBSTANTIAL =
	'urn:oasis:names:tc:SAML:2.0:ac:classes:MobileTwoFactorContract'
const CLIENT_ID = 'filinq-e2e'
const CLIENT_SECRET = `filinq-e2e-secret-${randomBytes(8).toString('hex')}`
const SUBJECT = 'mock-digid-subject-0001'
const RUN = `sir-e2e-${Date.now()}`

interface MockOidc {
	server: Server
	frontBase: string
	backBase: string
	authorizeRequests: URLSearchParams[]
	tokenRequests: URLSearchParams[]
}

/**
 * base64url of a buffer or string.
 *
 * @param value The bytes.
 * @return The encoding.
 */
function b64url(value: Buffer | string): string {
	return Buffer.from(value).toString('base64url')
}

/**
 * Start the mock OpenID Connect provider on a free port.
 *
 * Authorize answers at once with a code (the signer "logged in"), token
 * checks the client secret Filinq resolved from its credentialRef and issues
 * an RS256 ID token carrying the nonce, the DigiD-substantial acr and a
 * subject that must never reach Filinq's store in the clear.
 *
 * @return The running mock and what it recorded.
 */
async function startMockOidc(): Promise<MockOidc> {
	const keyPem = readFileSync(path.join(FIXTURES, 'test-key.pem'))
	const certPem = readFileSync(path.join(FIXTURES, 'test-cert.pem'))
	const privateKey = createPrivateKey(keyPem)
	const jwk = {
		...createPublicKey(keyPem).export({ format: 'jwk' }),
		kid: 'filinq-e2e',
		alg: 'RS256',
		use: 'sig',
	}
	const codes = new Map<string, { nonce: string; clientId: string }>()
	const mock = {
		authorizeRequests: [] as URLSearchParams[],
		tokenRequests: [] as URLSearchParams[],
	} as MockOidc

	const idToken = (nonce: string): string => {
		const now = Math.floor(Date.now() / 1000)
		const header = b64url(
			JSON.stringify({ alg: 'RS256', typ: 'JWT', kid: 'filinq-e2e' }),
		)
		const claims = b64url(
			JSON.stringify({
				iss: mock.frontBase,
				aud: CLIENT_ID,
				sub: SUBJECT,
				nonce,
				acr: ACR_DIGID_SUBSTANTIAL,
				iat: now,
				exp: now + 300,
				auth_time: now,
			}),
		)
		const signature = sign(
			'sha256',
			Buffer.from(`${header}.${claims}`),
			privateKey,
		)
		return `${header}.${claims}.${b64url(signature)}`
	}

	const server = createServer({ key: keyPem, cert: certPem }, (req, res) => {
		const url = new URL(req.url || '/', mock.frontBase)
		const json = (status: number, body: unknown) => {
			res.writeHead(status, { 'Content-Type': 'application/json' })
			res.end(JSON.stringify(body))
		}

		if (url.pathname === '/.well-known/openid-configuration') {
			json(200, {
				issuer: mock.frontBase,
				authorization_endpoint: `${mock.frontBase}/authorize`,
				token_endpoint: `${mock.backBase}/token`,
				userinfo_endpoint: `${mock.backBase}/userinfo`,
				jwks_uri: `${mock.backBase}/jwks`,
				response_types_supported: ['code'],
				subject_types_supported: ['pairwise'],
				id_token_signing_alg_values_supported: ['RS256'],
				acr_values_supported: [ACR_DIGID_SUBSTANTIAL],
			})
			return
		}
		if (url.pathname === '/jwks') {
			json(200, { keys: [jwk] })
			return
		}
		if (url.pathname === '/authorize') {
			mock.authorizeRequests.push(url.searchParams)
			const code = randomBytes(16).toString('hex')
			codes.set(code, {
				nonce: url.searchParams.get('nonce') || '',
				clientId: url.searchParams.get('client_id') || '',
			})
			const back = new URL(url.searchParams.get('redirect_uri') || '')
			back.searchParams.set('code', code)
			back.searchParams.set('state', url.searchParams.get('state') || '')
			res.writeHead(302, { Location: back.toString() })
			res.end()
			return
		}
		if (url.pathname === '/token' && req.method === 'POST') {
			let body = ''
			req.on('data', (chunk) => {
				body += chunk
			})
			req.on('end', () => {
				const form = new URLSearchParams(body)
				mock.tokenRequests.push(form)
				const issued = codes.get(form.get('code') || '')
				codes.delete(form.get('code') || '')
				if (
					!issued
					|| form.get('client_secret') !== CLIENT_SECRET
					|| form.get('client_id') !== issued.clientId
				) {
					json(400, { error: 'invalid_grant' })
					return
				}
				json(200, {
					access_token: randomBytes(16).toString('hex'),
					token_type: 'Bearer',
					expires_in: 300,
					id_token: idToken(issued.nonce),
				})
			})
			return
		}
		if (url.pathname === '/userinfo') {
			json(200, { sub: SUBJECT })
			return
		}
		json(404, { error: 'not_found' })
	})

	await new Promise<void>((resolve) => server.listen(0, '0.0.0.0', resolve))
	const port = (server.address() as AddressInfo).port
	mock.server = server
	mock.frontBase = `https://localhost:${port}`
	mock.backBase = `https://${BACKCHANNEL_HOST}:${port}`
	return mock
}

/**
 * Decode the assertion Filinq's native provider appended to a signed file.
 *
 * @param bytes The signed document.
 * @return The assertion object, `mac` included.
 */
function readAssertion(bytes: string): Record<string, any> {
	const match = bytes.match(/\/DocuDesk-Signature\(([A-Za-z0-9+/=]+)\)/)
	expect(match, 'the signed document carries a signature marker').not.toBeNull()
	return JSON.parse(
		Buffer.from((match as RegExpMatchArray)[1], 'base64').toString('utf8'),
	)
}

let mock: MockOidc
let api: APIRequestContext
let savedSettings: Record<string, unknown> = {}
let credentialId = ''
const createdRequests: string[] = []
const uploadedFiles: string[] = []

/**
 * Upload a small document for a signing request, and return its file id.
 *
 * @param name The file name.
 * @return The Nextcloud file id.
 */
async function uploadDocument(name: string): Promise<string> {
	const dav = `/remote.php/dav/files/${ADMIN_USER}/${name}`
	const put = await api.put(dav, { data: `%PDF-1.4\n% ${name}\n%%EOF\n` })
	expect(put.status(), `upload ${name}`).toBeLessThan(300)
	uploadedFiles.push(dav)
	const find = await api.fetch(dav, {
		method: 'PROPFIND',
		headers: { Depth: '0', 'Content-Type': 'application/xml' },
		data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns"><d:prop><oc:fileid/></d:prop></d:propfind>',
	})
	const id = (await find.text()).match(/<oc:fileid>(\d+)<\/oc:fileid>/)?.[1] || ''
	expect(id, `file id of ${name}`).not.toEqual('')
	return id
}

/**
 * Create a signing request with the admin as its one signer.
 *
 * @param fields Level, provider and assurance fields.
 * @return The created request as the API answered it.
 */
async function createRequest(
	fields: Record<string, unknown>,
): Promise<Record<string, any>> {
	const name = `${RUN}-${createdRequests.length + 1}.pdf`
	const fileId = await uploadDocument(name)
	const res = await api.post(`${API}/signing/requests`, {
		data: {
			documentFileId: fileId,
			documentName: name,
			signingMode: 'sequential',
			deadline: new Date(Date.now() + 86_400_000).toISOString(),
			signers: [{ userId: ADMIN_USER, displayName: ADMIN_USER }],
			...fields,
		},
	})
	expect(res.status(), await res.text()).toBe(201)
	const created = await res.json()
	createdRequests.push(created.id)
	return created
}

/**
 * The stored signer record, straight from OpenRegister.
 *
 * @param signerId The signer record id.
 * @return The record.
 */
async function signerRecord(signerId: string): Promise<Record<string, any>> {
	const res = await api.get(`${OR_API}/objects/filinq/signerRecord/${signerId}`)
	expect(res.ok(), `signer record ${signerId}`).toBeTruthy()
	return res.json()
}

/**
 * Walk the UI from the signing folder to the broker and back.
 *
 * @param page The page, in the admin's browser session.
 * @param documentName The document to sign.
 * @return Resolves once the broker sent the browser back to Filinq.
 */
async function stepUpThroughTheFolder(
	page: Page,
	documentName: string,
): Promise<void> {
	await go(page, 'signing-folder')
	await page.getByRole('checkbox', { name: `Select ${documentName}` }).check()
	await page.getByRole('button', { name: /Sign selected/ }).click()
	const confirm = page.getByRole('button', { name: 'Confirm my identity' })
	await expect(confirm).toBeVisible()
	await confirm.click()
	const dialog = page.getByRole('dialog', { name: 'Confirm who you are' })
	await expect(dialog).toBeVisible()
	await expect(dialog).toContainText('substantial')
	await dialog.getByRole('button', { name: 'Confirm my identity' }).click()
	await page.waitForURL(/\/apps\/filinq\/(signing\/|signing-folder\?stepUp=)/, {
		timeout: 60_000,
	})
}

test.describe('signer identity rails: assurance gate and broker step-up', () => {
	test.beforeAll(async () => {
		mock = await startMockOidc()
		const baseURL = resolveBaseUrl()
		api = await playwrightRequest.newContext({
			baseURL,
			httpCredentials: { username: ADMIN_USER, password: ADMIN_PASS },
			extraHTTPHeaders: { 'OCS-APIRequest': 'true' },
		})

		const current = await api.get(`${API}/settings/signer-identity`)
		expect(current.ok(), 'read the signer identity settings').toBeTruthy()
		savedSettings = await current.json()

		const credential = await api.post(`${OR_API}/credentials`, {
			data: {
				name: `${RUN} broker secret`,
				provider: 'generic-oauth2',
				secret: CLIENT_SECRET,
				allowedApps: ['filinq'],
			},
		})
		expect(credential.status(), await credential.text()).toBeLessThan(300)
		credentialId = (await credential.json()).id

		const update = await api.put(`${API}/settings/signer-identity`, {
			data: {
				provider: 'oidc-broker',
				issuer: mock.frontBase,
				clientId: CLIENT_ID,
				authorizationEndpoint: `${mock.frontBase}/authorize`,
				tokenEndpoint: `${mock.backBase}/token`,
				redirectUri: `${baseURL}${API}/signing/identity/callback`,
				scopes: 'openid',
				acrMapping: '',
				credentialRef: credentialId,
				evidenceMaxAgeMinutes: '15',
			},
		})
		expect(update.status(), await update.text()).toBeLessThan(300)
		const configured = await (
			await api.get(`${API}/settings/signer-identity`)
		).json()
		expect(
			configured.brokerConfigured,
			'the mock broker counts as configured',
		).toBe(true)
	})

	test.afterAll(async () => {
		for (const id of createdRequests) {
			await api.delete(`${API}/signing/requests/${id}`).catch(() => {})
		}
		for (const dav of uploadedFiles) {
			await api.delete(dav).catch(() => {})
		}
		const restore: Record<string, unknown> = {}
		for (const field of [
			'provider',
			'issuer',
			'clientId',
			'authorizationEndpoint',
			'tokenEndpoint',
			'redirectUri',
			'scopes',
			'acrMapping',
			'credentialRef',
		]) {
			restore[field] = savedSettings[field] ?? ''
		}
		restore.evidenceMaxAgeMinutes = String(
			savedSettings.evidenceMaxAgeMinutes ?? 15,
		)
		await api
			.put(`${API}/settings/signer-identity`, { data: restore })
			.catch(() => {})
		if (credentialId) {
			await api.delete(`${OR_API}/credentials/${credentialId}`).catch(() => {})
		}
		await api.dispose()
		await new Promise<void>((resolve) => mock.server.close(() => resolve()))
	})

	test('a QES request cannot undercut its level floor', async () => {
		// @e2e openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md#request-cannot-undercut-its-level-floor
		const created = await createRequest({
			signatureLevel: 'QES',
			provider: 'validsign',
			requiredAssurance: 'low',
		})
		expect(created.requiredAssurance, 'normalised upward to the QES floor').toBe(
			'high',
		)
		expect(
			created.assuranceFloor,
			'the response names the floor it applied',
		).toBe('high')

		const stored = await (
			await api.get(`${API}/signing/requests/${created.id}`)
		).json()
		expect(stored.requiredAssurance, 'persisted at the floor').toBe('high')
	})

	test('a substantial request refuses a signer who only has a Nextcloud session', async () => {
		// @e2e openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md#substantial-request-refuses-a-session-only-signer
		const created = await createRequest({
			signatureLevel: 'SES',
			provider: 'native',
			requiredAssurance: 'substantial',
		})
		const signerId = created.signerIds[0]

		const res = await api.post(`${API}/signing/requests/${created.id}/sign`, {
			data: { signerId },
		})
		expect(res.status()).toBe(403)
		const body = await res.json()
		expect(body.stepUp).toMatchObject({
			required: true,
			requiredAssurance: 'substantial',
			// What the session holds, and the provider that can raise it.
			heldAssurance: 'low',
			provider: 'oidc-broker',
		})

		expect(
			(await signerRecord(signerId)).status,
			'signer record unchanged',
		).toBe('PENDING')
		const request = await (
			await api.get(`${API}/signing/requests/${created.id}`)
		).json()
		expect(request.status, 'request status unchanged').toBe('PENDING')
	})

	test('the step-up sends the signer to the broker for the assurance the request needs', async ({
		page,
	}) => {
		// @e2e openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md#step-up-unlocks-the-signature
		const created = await createRequest({
			signatureLevel: 'SES',
			provider: 'native',
			requiredAssurance: 'substantial',
		})
		const before = mock.authorizeRequests.length

		await stepUpThroughTheFolder(page, created.documentName)

		expect(
			mock.authorizeRequests.length,
			'the browser reached the broker once',
		).toBe(before + 1)
		const authorize = mock.authorizeRequests[before]
		expect(authorize.get('client_id')).toBe(CLIENT_ID)
		expect(authorize.get('response_type')).toBe('code')
		expect(authorize.get('prompt')).toBe('login')
		expect(
			authorize.get('state')?.length ?? 0,
			'a state is sent',
		).toBeGreaterThan(15)
		expect(
			authorize.get('nonce')?.length ?? 0,
			'a nonce is sent',
		).toBeGreaterThan(15)
		const acrValues = (authorize.get('acr_values') || '').split(' ')
		expect(acrValues, 'DigiD substantial meets the request').toContain(
			ACR_DIGID_SUBSTANTIAL,
		)
		expect(acrValues, 'DigiD basis does not').not.toContain(
			'urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport',
		)

		if (!BACKCHANNEL) {
			// The broker answered; Nextcloud could not redeem the code, so the
			// signer lands back on the folder with the failure note.
			await expect(page).toHaveURL(/signing-folder\?stepUp=failed/)
			await expect(
				page.getByText('Your identity could not be confirmed.'),
			).toBeVisible()
		}
	})

	let signed: Record<string, any> = {}

	test('the step-up at DigiD substantial unlocks the signature', async ({
		page,
	}) => {
		// @e2e openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md#step-up-unlocks-the-signature
		test.skip(!BACKCHANNEL, BACKCHANNEL_REASON)
		const created = await createRequest({
			signatureLevel: 'SES',
			provider: 'native',
			requiredAssurance: 'substantial',
		})
		const tokenCallsBefore = mock.tokenRequests.length

		await stepUpThroughTheFolder(page, created.documentName)

		expect(
			mock.tokenRequests.length,
			'Nextcloud redeemed the code server-side',
		).toBe(tokenCallsBefore + 1)
		expect(
			mock.tokenRequests[tokenCallsBefore].get('client_secret'),
			'with the secret behind the credentialRef',
		).toBe(CLIENT_SECRET)
		await expect(page).toHaveURL(
			new RegExp(`/signing/${created.id}\\?stepUp=done`),
		)
		await expect(page.getByText('Your identity is confirmed.')).toBeVisible()
		await page.getByRole('button', { name: 'Sign now' }).click()
		await expect(page.getByText('You signed this document.')).toBeVisible()

		expect((await signerRecord(created.signerIds[0])).status).toBe('SIGNED')
		signed = created
	})

	test('the evidence lands on the record, the audit entry and the artifact together', async () => {
		// @e2e openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md#evidence-lands-on-record-audit-and-artifact-together
		test.skip(!BACKCHANNEL, BACKCHANNEL_REASON)
		expect(signed.id, 'the previous test signed a request').toBeTruthy()
		const expected = {
			provider: 'oidc-broker',
			means: 'digid',
			assurance: 'substantial',
		}

		// Record: the completed request carries the resolved assurance. The
		// tuple on the signer record is `visible: false`, so OpenRegister's
		// object API does not return it; the audit entry and the artifact
		// below are written from that same stored tuple.
		const request = await (
			await api.get(`${API}/signing/requests/${signed.id}`)
		).json()
		expect(request.status).toBe('COMPLETED')
		expect(request.resolvedAssurance).toBe('substantial')
		expect((await signerRecord(signed.signerIds[0])).status).toBe('SIGNED')

		// Audit: the SIGNED entry carries the same tuple.
		const audit = await (
			await api.get(`${API}/signing/requests/${signed.id}/audit`)
		).json()
		const signedEntry = audit.find((entry: any) =>
			String(entry.action).endsWith('.SIGNED'),
		)
		expect(signedEntry, 'a SIGNED audit entry').toBeTruthy()
		const auditEvidence =
			signedEntry.changed?.extra?.identityEvidence
			?? signedEntry.changed?.identityEvidence
		expect(auditEvidence).toMatchObject(expected)
		expect(
			auditEvidence.subjectPseudonym,
			'a pseudonym, not the subject',
		).not.toContain(SUBJECT)
		expect(auditEvidence.authenticatedAt).toBeTruthy()
		expect(auditEvidence.evidenceHash).toMatch(/^[0-9a-f]{64}$/)

		// Artifact: the tuple is inside the assertion, and the MAC verifies.
		const bytes = await (
			await api.get(
				`/remote.php/dav/files/${ADMIN_USER}/${signed.documentName}`,
			)
		).text()
		const assertion = readAssertion(bytes)
		expect(
			assertion.signerEvidence,
			'the assertion lists the signer evidence',
		).toEqual(
			expect.arrayContaining([
				expect.objectContaining({
					...expected,
					subjectPseudonym: auditEvidence.subjectPseudonym,
				}),
			]),
		)
		expect(JSON.stringify(assertion)).not.toContain(SUBJECT)
		const verify = await (
			await api.get(`${API}/signing/verify/${signed.documentFileId}`)
		).json()
		expect(
			JSON.stringify(verify),
			'the v2 MAC over the assertion verifies',
		).toMatch(/"(valid|verified)"\s*:\s*true|"status"\s*:\s*"valid"/)
	})
})
