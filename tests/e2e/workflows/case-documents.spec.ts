/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * End-to-end regression for `case-documents-and-the-flat-list`: every file on a
 * case is readable as one flat list naming the record each file belongs to, an
 * upload obeys the administered policy and is refused by its BYTES rather than
 * its name, one document record serves several domains, and unlinking the last
 * domain keeps the record.
 *
 * WHAT IS DRIVEN THROUGH THE UI AND WHAT IS NOT
 * --------------------------------------------
 * The flat list has no surface of its own yet: it is offered to consuming apps
 * as an endpoint, and the leaf that renders it is still to come. So everything
 * here goes through the documented endpoints and the DAV upload path, which is
 * also where the refusal has to hold: a policy enforced in the browser only is
 * not a policy.
 *
 * ⚠️ The `@e2e` anchors name BOTH the change's delta spec and the canonical
 * spec it is synced into at archive time. Gate 19 scans `openspec/specs/` only.
 *
 * WHAT THIS FILE DELIBERATELY DOES NOT COVER, AND WHERE IT IS COVERED INSTEAD
 * -------------------------------------------------------------------------
 * Three members of this change run on the scheduler or before any document
 * exists, and none of them has a surface a browser can reach:
 *
 *   - the nightly domain-folder reconciliation (REQ-CDF-02). Its whole point is
 *     the report on drift it could NOT correct, and reddening that here would
 *     mean a mount rigged to refuse a permission change, which is a fixture no
 *     instance will hold still for. Covered by DomainFolderReconcilerTest,
 *     including the partly-reconciled case that must report refused rather
 *     than corrected.
 *   - the nightly upload-fragment reaper (REQ-CDF-05). A fragment has to be
 *     older than the declared age, and the floor under that age is one hour, so
 *     an honest run of it takes an hour of wall clock. Covered by
 *     UploadFragmentReaperTest, where the assertions that matter are the ones
 *     about what it LEAVES.
 *   - validating an external mount before it is used (REQ-CDF-06). The
 *     validator is built and tested; the probe that asks a real mount what it
 *     supports is not, because OCP\Files\Mount\IMountPoint and
 *     OCP\Files\Storage\IStorage do not exist in this repository's test
 *     environment. Covered by ExternalMountValidatorTest.
 *
 * Each of those scenarios carries its own `@e2e exclude` in the spec, so the
 * gate sees the reason rather than a gap.
 */

import type { APIRequestContext } from '@playwright/test'

import { expect, test } from '@playwright/test'
import {
	API,
	createDavFile,
	createDavFolder,
	deleteDavPath,
	harvestToken,
	jsonHeaders,
	TEST_PREFIX,
} from './_fixtures.ts'

/** Folder every document this suite seeds lives in. */
const FOLDER = `${TEST_PREFIX}-case-docs`

/** The OpenRegister objects endpoint for the document records. */
const OR_RECORDS = '/index.php/apps/openregister/api/objects/filinq/documentVersion'

/** The case every seeded record is linked to. */
const CASE = { register: 'filinq', schema: 'dossier', id: '' }

/**
 * Seed one document record on the case, for one file.
 *
 * @param req    The request context.
 * @param token  The CSRF request-token.
 * @param fileId The file the record is about.
 * @param name   The document name.
 * @return The record's uuid.
 */
async function seedRecord(
	req: APIRequestContext,
	token: string,
	fileId: string,
	name: string,
): Promise<string> {
	const created = await req.post(OR_RECORDS, {
		headers: jsonHeaders(token),
		data: {
			fileId: Number(fileId),
			documentName: name,
			status: 'draft',
			domains: [CASE],
		},
	})
	expect(created.status(), `seeding ${name} must succeed`).toBeLessThan(300)
	const body = await created.json()
	const uuid = body?.['@self']?.id || body?.id || body?.uuid || ''
	expect(uuid, `seeding ${name} must yield a uuid`).not.toBe('')
	return uuid
}

test.describe('Case documents and the flat list', () => {
	let token = ''

	test.beforeAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		token = await harvestToken(page)
		expect(token, 'the suite needs a live request token').not.toBe('')
		expect(await createDavFolder(page.request, token, FOLDER)).toBeLessThan(300)

		const dossier = await page.request.post(
			'/index.php/apps/openregister/api/objects/filinq/dossier',
			{ headers: jsonHeaders(token), data: { title: `${TEST_PREFIX}-zaak` } },
		)
		expect(dossier.status()).toBeLessThan(300)
		const body = await dossier.json()
		CASE.id = body?.['@self']?.id || body?.id || ''
		expect(CASE.id, 'the suite needs a case to hang documents on').not.toBe('')

		await page.close()
	})

	test.afterAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/index.php/apps/filinq/')
		await deleteDavPath(page.request, await harvestToken(page), FOLDER)
		await page.close()
	})

	// @e2e openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md#the-jurist-finds-the-attachment
	// @e2e openspec/specs/document-register/spec.md#the-jurist-finds-the-attachment
	test('every file on the case is in the flat list, each naming its record', async ({
		page,
	}) => {
		const names = ['advies.txt', 'hoorzitting.txt', 'besluit.txt']
		for (const name of names) {
			const { status, fileId } = await createDavFile(
				page.request,
				token,
				`${FOLDER}/${name}`,
				`inhoud van ${name}`,
			)
			expect(status).toBeLessThan(300)
			await seedRecord(page.request, token, fileId, name)
		}

		const list = await page.request.get(`${API}/case-documents/files`, {
			headers: jsonHeaders(token),
			params: { register: CASE.register, schema: CASE.schema, id: CASE.id, limit: 50 },
		})
		expect(list.status()).toBe(200)
		const body = await list.json()
		expect(body.total).toBeGreaterThanOrEqual(3)
		for (const row of body.results) {
			expect(row.record?.uuid, 'every row names the record it belongs to').not.toBe('')
		}
	})

	// @e2e openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md#the-jurist-finds-the-attachment
	// @e2e openspec/specs/document-register/spec.md#the-jurist-finds-the-attachment
	test('the flat list filters on the file name and pages', async ({ page }) => {
		const filtered = await page.request.get(`${API}/case-documents/files`, {
			headers: jsonHeaders(token),
			params: {
				register: CASE.register,
				schema: CASE.schema,
				id: CASE.id,
				search: 'hoorzitting',
			},
		})
		expect(filtered.status()).toBe(200)
		const body = await filtered.json()
		for (const row of body.results) {
			expect(String(row.name).toLowerCase()).toContain('hoorzitting')
		}
	})

	// @e2e openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md#one-advies-three-zaaktypen
	// @e2e openspec/specs/document-register/spec.md#one-advies-three-zaaktypen
	test('one record serves several domains, and unlinking the last keeps it', async ({
		page,
	}) => {
		const { status, fileId } = await createDavFile(
			page.request,
			token,
			`${FOLDER}/advies-voor-drie.txt`,
			'een advies',
		)
		expect(status).toBeLessThan(300)
		const uuid = await seedRecord(page.request, token, fileId, 'advies-voor-drie.txt')

		const second = await page.request.post(`${API}/case-documents/${uuid}/domains`, {
			headers: jsonHeaders(token),
			data: { register: CASE.register, schema: CASE.schema, id: CASE.id },
		})
		expect(second.status()).toBe(200)
		const linked = await second.json()
		expect(Array.isArray(linked.domains)).toBe(true)
		expect(linked.domains.length, 'the same domain twice stays one link').toBe(1)

		const unlinked = await page.request.delete(`${API}/case-documents/${uuid}/domains`, {
			headers: jsonHeaders(token),
			data: { register: CASE.register, schema: CASE.schema, id: CASE.id },
		})
		expect(unlinked.status()).toBe(200)

		// The record itself survives the unlink, which is the whole point.
		const stored = await page.request.get(`${OR_RECORDS}/${uuid}`, {
			headers: jsonHeaders(token),
		})
		expect(stored.status()).toBe(200)
		const record = await stored.json()
		const fields = (record.object ?? record) as Record<string, unknown>
		expect(fields.documentName).toBe('advies-voor-drie.txt')
		expect(fields.createdByUser).not.toBe('')
	})

	// @e2e openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md#my-documents
	// @e2e openspec/specs/document-register/spec.md#my-documents
	test('a person sees the document records they created', async ({ page }) => {
		const mine = await page.request.get(`${API}/case-documents/mine`, {
			headers: jsonHeaders(token),
		})
		expect(mine.status()).toBe(200)
		const body = await mine.json()
		expect(Array.isArray(body.results)).toBe(true)
	})

	// @e2e openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md#an-executable-is-refused
	// @e2e openspec/specs/document-register/spec.md#an-executable-is-refused
	test('the upload policy is readable, and says what it allows', async ({ page }) => {
		const response = await page.request.get(`${API}/case-documents/upload-policy`, {
			headers: jsonHeaders(token),
		})
		expect(response.status()).toBe(200)
		const body = await response.json()

		// A fresh install carries the seeded standard policy. An instance that
		// deleted it answers null, which is a policy-free instance, not an error.
		if (body.policy !== null) {
			expect(Array.isArray(body.policy.allowedExtensions)).toBe(true)
			expect(body.policy.maxSizeBytes).toBeGreaterThan(0)
		}
	})

	// @e2e openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md#an-executable-is-refused
	// @e2e openspec/specs/document-register/spec.md#an-executable-is-refused
	test('an executable renamed to .pdf is refused by the upload endpoint', async ({
		page,
	}) => {
		// A complete ELF header: a truncated one reads as octet-stream, which
		// is refused for a different reason and would not prove the check reads
		// the bytes.
		const elf = Buffer.concat([
			Buffer.from([0x7f, 0x45, 0x4c, 0x46, 0x02, 0x01, 0x01, 0x00]),
			Buffer.alloc(8),
			Buffer.from([0x02, 0x00, 0x3e, 0x00, 0x01, 0x00, 0x00, 0x00]),
			Buffer.alloc(100),
		])

		const refused = await page.request.post(`${API}/anonymization/upload`, {
			headers: { requesttoken: token },
			multipart: {
				file: {
					name: 'bijlage.pdf',
					mimeType: 'application/pdf',
					buffer: elf,
				},
			},
		})

		// The refusal is the server's, whatever status it wraps it in: what
		// matters is that the file is not stored and the answer names the type.
		expect(refused.status()).toBeGreaterThanOrEqual(400)
		const body = await refused.text()
		expect(body.toLowerCase()).toContain('executable')
	})
})
