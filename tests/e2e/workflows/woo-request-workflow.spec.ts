/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * End-to-end regression for `woo-request-workflow`, task 4.1: an organisation
 * edits the seeded `woo-besluit` template in the template editor, the decision
 * letter and the inventory are generated through the API from
 * `tests/fixtures/woo-decision.json`, both are stored, and the stored PDF
 * opens.
 *
 * WHAT IT SEEDS AND WHAT IT READS. It reads the two seeded templates
 * (`woo-besluit` and `woo-inventarislijst`, namespace `woo`) the register
 * import put on the instance, and fails when they are missing: their seed is
 * the change. It edits `woo-besluit` through the editor's raw HTML field,
 * publishes that version, and when the test ends writes the original body
 * back and publishes it again. The stored PDFs are deleted. It reads no
 * env-var fixture.
 *
 * ⚠️ WHERE THE GROUNDS COME FROM. The `woo-decision` contract builder that
 * turns ground codes into `{code, article, label}` runs only on the
 * in-process `DocumentGenerationRequestedEvent` (dossiq's call), not behind
 * `POST /api/documents/generate`. The test therefore resolves each document's
 * ground codes through `GET /api/grounds`, the same list the builder reads,
 * and sends them as `grounds` the way the builder would. The refusals of that
 * builder are `@e2e exclude` in the spec and covered by PHPUnit.
 *
 * EXCLUDED IN THE SPEC (`@e2e exclude`): the edit surviving an upgrade, the
 * withheld document without a ground, every problem named at once, the ground
 * printed in words through dossiq's list, the unknown ground, and filinq
 * absent.
 *
 * Not run in the change that wrote it (decision 139): the live pass runs it.
 */

// @e2e openspec/changes/woo-request-workflow/specs/woo-request-workflow/spec.md#dossiq-drafts-a-partial-disclosure-decision

import type { APIRequestContext, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { go } from '../spec-coverage/_helpers.ts'
import { API, TEST_PREFIX } from './_fixtures.ts'

/** The OCS header every call carries; it also satisfies the CSRF check. */
const OCS = { 'OCS-APIRequest': 'true' }

/** Where generations are logged. */
const OR_DOCUMENTS = '/index.php/apps/openregister/api/objects/filinq/generatedDocument'

/** The signed-in user, whose files the PDFs are stored in. */
const USER = process.env.NC_ADMIN_USER ?? 'admin'

/** The request the caller sends, as the task names it. */
const FIXTURE = JSON.parse(readFileSync(resolve(process.cwd(), 'tests/fixtures/woo-decision.json'), 'utf8'))

/** The paragraph the organisation adds to the letter. */
const EDITED = `Met vriendelijke groet, het Woo-team (${TEST_PREFIX})`

/** One ground as the templates read it. */
interface Ground {
	code: string
	article: string
	label: string
}

/**
 * A text as Twig's html autoescape writes it, so a label with an apostrophe
 * or an ampersand is compared as it appears in the rendered letter.
 *
 * @param text The text.
 * @return The escaped text.
 */
function escapeHtml(text: string): string {
	return text
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#039;')
}

/**
 * A seeded Woo template by its slug.
 *
 * @param request The authenticated request context.
 * @param slug The slug.
 * @return The template.
 */
async function wooTemplate(request: APIRequestContext, slug: string): Promise<any> {
	const res = await request.get(`${API}/templates?namespace=woo&_limit=100`, { headers: OCS })
	expect(res.status()).toBe(200)
	const found = (((await res.json()).results ?? []) as any[]).find((template) => template.slug === slug)
	expect(found, `the seeded ${slug} template exists`).toBeTruthy()
	expect(found.dataContract).toBe('woo-decision')

	return found
}

/**
 * The fixture with each document's ground codes resolved through the
 * grounds endpoint, as the woo-decision contract builder passes them.
 *
 * @param request The authenticated request context.
 * @return The wooDecision data.
 */
async function wooDecision(request: APIRequestContext): Promise<any> {
	const res = await request.get(`${API}/grounds`, { headers: OCS })
	expect(res.ok(), `GET /api/grounds -> ${res.status()}`).toBeTruthy()
	const grounds = ((await res.json()).grounds ?? []) as any[]

	return {
		...FIXTURE,
		documents: FIXTURE.documents.map((document: any) => ({
			...document,
			grounds: document.groundCodes.map((code: string): Ground => {
				const ground = grounds.find((row) => row.code === code)
				expect(ground, `ground ${code} is in the list`).toBeTruthy()
				return { code, article: String(ground.article ?? ''), label: String(ground.label ?? '') }
			}),
		})),
	}
}

/**
 * Generate from a template with the Woo decision as data.
 *
 * @param request The authenticated request context.
 * @param templateId The template id.
 * @param data The wooDecision data.
 * @param format html to read the text back, pdf to store a file.
 * @return The answer.
 */
async function generate(request: APIRequestContext, templateId: string, data: any, format: 'html' | 'pdf'): Promise<any> {
	const options: Record<string, unknown> = { format, adHocData: { wooDecision: data } }
	if (format === 'pdf') {
		options.output = { mode: 'files' }
	}
	const res = await request.post(`${API}/documents/generate`, {
		headers: OCS,
		data: { templateId, dataRefs: [], filename: `${TEST_PREFIX}-${format}`, options },
	})
	expect(res.status(), await res.text()).toBe(200)

	return res.json()
}

/**
 * Edit the letter's closing in the template editor and save it.
 *
 * @param page The page.
 * @param templateId The woo-besluit template id.
 */
async function editClosing(page: Page, templateId: string): Promise<void> {
	await go(page, `templates/${templateId}`)
	await page.getByRole('button', { name: /^(Edit HTML|HTML bewerken)$/ }).click()
	const raw = page.locator('.html-document-editor__raw-area')
	await expect(raw).toBeVisible({ timeout: 30_000 })
	const body = await raw.inputValue()
	expect(body).toContain('wooDecision.reference')
	await raw.fill(`${body}\n<p>${EDITED}</p>`)
	await page.getByLabel(/Change note \(optional\)|Wijzigingsnotitie \(optioneel\)/).fill('Afsluiting aangepast')

	const saved = page.waitForResponse((res) => res.request().method() === 'PUT' && res.url().includes(`/api/templates/${templateId}`))
	await page.getByRole('button', { name: /^(Save|Opslaan)$/ }).click()
	expect((await saved).status()).toBe(200)
}

test.describe('Woo decision letter and inventory', () => {
	test.setTimeout(240_000)

	test('the edited letter and the inventory are generated from the fixture, agree, and are stored as PDFs', async ({ page, request }) => {
		const letter = await wooTemplate(request, 'woo-besluit')
		const inventory = await wooTemplate(request, 'woo-inventarislijst')
		const original = String(letter.content)
		const stored: string[] = []

		try {
			await editClosing(page, letter.id)
			const published = await request.post(`${API}/templates/${letter.id}/publish`, { headers: OCS })
			expect(published.ok(), `publish -> ${published.status()}`).toBeTruthy()
			const edited = await request.get(`${API}/templates/${letter.id}`, { headers: OCS })
			const editedTemplate = await edited.json()
			expect(editedTemplate.content).toContain(EDITED)

			const data = await wooDecision(request)

			// The letter: the edit, the reference, and documents 2 and 3 with their grounds in words.
			const letterHtml = String((await generate(request, letter.id, data, 'html')).content)
			expect(letterHtml).toContain(EDITED)
			expect(letterHtml).toContain(escapeHtml(FIXTURE.reference))
			expect(letterHtml).toContain('Document 2: Advies verkeerskundige')
			expect(letterHtml).toContain('Document 3: Intern memo wethouder')
			for (const document of data.documents.slice(1)) {
				for (const ground of document.grounds as Ground[]) {
					expect(ground.label, `ground ${ground.code} has a label`).not.toBe('')
					expect(letterHtml).toContain(escapeHtml(ground.label))
					expect(letterHtml).toContain(`grond ${ground.code}`)
				}
			}
			// Document 2 is partly disclosed, never listed as disclosed.
			const disclosed = /<h2>Openbaar<\/h2>\s*<ul>([\s\S]*?)<\/ul>/.exec(letterHtml)?.[1] ?? ''
			expect(disclosed).toContain('Document 1')
			expect(disclosed).not.toContain('Document 2')

			// The inventory: 1, 2 and 3 with the same numbers and assessments.
			const inventoryHtml = String((await generate(request, inventory.id, data, 'html')).content)
			const rows = [...inventoryHtml.matchAll(/<tr><td>([^<]*)<\/td><td>([^<]*)<\/td><td>[^<]*<\/td><td>([^<]*)<\/td>/g)].map((match) => [
				match[1],
				match[2],
				match[3],
			])
			expect(rows).toEqual([
				['1', 'Raadsvoorstel herinrichting', 'Openbaar'],
				['2', 'Advies verkeerskundige', 'Gedeeltelijk openbaar'],
				['3', 'Intern memo wethouder', 'Niet openbaar'],
			])

			// Both are stored, and the stored letter opens as a PDF.
			for (const template of [letter, inventory]) {
				const file = await generate(request, template.id, data, 'pdf')
				expect(Number(file.fileId)).toBeGreaterThan(0)
				const path = String(file.path).replace(new RegExp(`^/?${USER}/files/`), '')
				stored.push(path)
				const pdf = await request.get(`/remote.php/dav/files/${USER}/${encodeURI(path)}`)
				expect(pdf.status(), `GET the stored ${template.slug}`).toBe(200)
				expect((await pdf.body()).subarray(0, 5).toString()).toBe('%PDF-')

				// The stored document names the template version it came from.
				const logged = await request.get(`${OR_DOCUMENTS}?fileId=${file.fileId}&_limit=20`, { headers: OCS })
				expect(logged.ok()).toBeTruthy()
				const entry = (((await logged.json()).results ?? []) as any[]).find((row) => Number(row.fileId) === Number(file.fileId))
				expect(entry, `the ${template.slug} generation is logged`).toBeTruthy()
				expect(entry.templateId).toBe(template.id)
				if (template.id === letter.id) {
					expect(entry.templateVersion ?? null).toBe(editedTemplate.version ?? null)
				}
			}
		} finally {
			for (const path of stored) {
				await request.delete(`/remote.php/dav/files/${USER}/${encodeURI(path)}`).catch(() => undefined)
			}
			// Put the organisation's letter back as it was, and publish it.
			const restored = await request.put(`${API}/templates/${letter.id}`, {
				headers: OCS,
				data: { content: original, _changelog: 'e2e: original closing restored' },
			})
			expect(restored.ok(), `restore woo-besluit -> ${restored.status()}`).toBeTruthy()
			await request.post(`${API}/templates/${letter.id}/publish`, { headers: OCS })
		}
	})
})
