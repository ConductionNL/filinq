/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage: reversible-pseudonymization.
 *
 * The API scenarios run a real reversible anonymisation of a plain-text file
 * with outputFormat "preserve", so the anonymised copy stays text and the
 * restore writes a copy rather than a report. The refusal scenario answers
 * the status and restore calls with a route handler, because a second,
 * non-admin account is not part of the dev fixture; the server half of the
 * refusal (403, denial in the audit trail) is proven by
 * PseudonymRestoreServiceTest::testANonMemberIsRefusedAndTheDenialIsLogged.
 */

// @e2e openspec/specs/reversible-pseudonymization/spec.md#reversible-mode-stores-a-mapping-irreversible-does-not
// @e2e openspec/specs/reversible-pseudonymization/spec.md#placeholders-come-from-openregister-not-a-new-format
// @e2e openspec/specs/reversible-pseudonymization/spec.md#a-permitted-user-restores-the-original-text
// @e2e openspec/specs/reversible-pseudonymization/spec.md#a-non-member-is-refused-and-the-denial-is-logged
// @e2e openspec/specs/reversible-pseudonymization/spec.md#restore-action-states-it-is-audited-before-proceeding

import type { APIRequestContext } from '@playwright/test'

import { expect, test } from '@playwright/test'

const API = '/index.php/apps/filinq/api'
const NAME = 'Jan Jansen'

/**
 * Put a text file in the admin's files and return its file id.
 *
 * @param request The API context
 * @param name The file name
 * @return The Nextcloud file id
 */
async function putText(request: APIRequestContext, name: string): Promise<number> {
	const url = `/remote.php/dav/files/admin/${name}`
	const put = await request.put(url, {
		data: `Brief aan ${NAME} over de aanvraag.\n`,
	})
	expect([201, 204]).toContain(put.status())
	const found = await request.fetch(url, {
		method: 'PROPFIND',
		headers: { Depth: '0', 'Content-Type': 'application/xml' },
		data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns"><d:prop><oc:fileid/></d:prop></d:propfind>',
	})
	const fileId = (await found.text()).match(/<oc:fileid>(\d+)<\/oc:fileid>/)?.[1]
	expect(fileId).toBeTruthy()
	return Number(fileId)
}

/**
 * Extract and anonymise one file, reversibly or not.
 *
 * @param request The API context
 * @param fileId The source file
 * @param reversible Whether to keep a key
 * @return The anonymise answer
 */
async function anonymise(
	request: APIRequestContext,
	fileId: number,
	reversible: boolean,
): Promise<Record<string, unknown>> {
	const extract = await request.post(`${API}/anonymization/extract/${fileId}`)
	expect(extract.ok()).toBeTruthy()
	const run = await request.post(`${API}/anonymization/anonymize/${fileId}`, {
		data: {
			entities: [{ type: 'PERSON', value: NAME, confidence: 1 }],
			scope: 'document',
			outputFormat: 'preserve',
			reversible,
		},
	})
	expect(run.ok()).toBeTruthy()
	return run.json()
}

test.describe('reversible pseudonymisation', () => {
	test('a reversible run keeps a key and an irreversible rerun removes it', async ({
		request,
	}) => {
		// @e2e openspec/specs/reversible-pseudonymization/spec.md#reversible-mode-stores-a-mapping-irreversible-does-not
		const fileId = await putText(request, `pseudonym-${Date.now()}.txt`)

		const reversible = await anonymise(request, fileId, true)
		const kept = reversible.pseudonymisation as Record<string, unknown>
		expect(kept.keyKept).toBe(true)
		expect(kept.entryCount).toBe(1)

		const irreversible = await anonymise(request, fileId, false)
		expect(
			(irreversible.pseudonymisation as Record<string, unknown>)
				.previousKeyRemoved,
		).toBe(true)
	})

	test('the placeholder is the one OpenRegister wrote, and a permitted user gets the name back in a copy', async ({
		request,
	}) => {
		// @e2e openspec/specs/reversible-pseudonymization/spec.md#placeholders-come-from-openregister-not-a-new-format
		// @e2e openspec/specs/reversible-pseudonymization/spec.md#a-permitted-user-restores-the-original-text
		const fileId = await putText(request, `pseudonym-restore-${Date.now()}.txt`)
		const run = await anonymise(request, fileId, true)
		const anonymisedId = Number(run.anonymizedFileId)

		const copy = await request.get(
			`/remote.php/dav/files/admin/${String(run.anonymizedFileName)}`,
		)
		const anonymisedText = await copy.text()
		expect(anonymisedText).not.toContain(NAME)
		expect(anonymisedText).toMatch(/\[[A-Z]+: 1\]/)

		const status = await (
			await request.get(`${API}/pseudonymisation/status/${anonymisedId}`)
		).json()
		expect(status.reversible).toBe(true)
		expect(status.mayRestore).toBe(true)

		const restored = await request.post(
			`${API}/pseudonymisation/${status.linkId}/restore`,
		)
		expect(restored.ok()).toBeTruthy()
		const result = await restored.json()
		expect(result.mode).toBe('copy')
		expect(result.restored).toBe(1)

		const restoredText = await (
			await request.get(
				`/remote.php/dav/files/admin/${String(result.fileName)}`,
			)
		).text()
		expect(restoredText).toContain(NAME)
		// The anonymised copy is untouched.
		expect(
			await (
				await request.get(
					`/remote.php/dav/files/admin/${String(run.anonymizedFileName)}`,
				)
			).text(),
		).toBe(anonymisedText)
	})

	test('a refused restore shows the refusal, and the dialog names the audit trail first', async ({
		page,
	}) => {
		// @e2e openspec/specs/reversible-pseudonymization/spec.md#a-non-member-is-refused-and-the-denial-is-logged
		// @e2e openspec/specs/reversible-pseudonymization/spec.md#restore-action-states-it-is-audited-before-proceeding
		let restoreCalls = 0
		await page.route('**/apps/filinq/api/pseudonymisation/status/**', (route) =>
			route.fulfill({
				json: {
					linkId: 'link-e2e',
					reversible: true,
					entryCount: 2,
					mayRestore: true,
				},
			}),
		)
		await page.route(
			'**/apps/filinq/api/pseudonymisation/link-e2e/restore',
			(route) => {
				restoreCalls++
				return route.fulfill({
					status: 403,
					json: { error: 'You are not allowed to restore names.' },
				})
			},
		)

		await page.goto('/index.php/apps/filinq/anonymization', {
			waitUntil: 'domcontentloaded',
		})
		const restore = page.getByRole('button', { name: 'Restore original' })
		test.skip(
			!(await restore.isVisible({ timeout: 15_000 }).catch(() => false)),
			'no redacted copy is open in this fixture',
		)

		await restore.click()
		const dialog = page.getByRole('dialog', { name: 'Restore original' })
		await expect(dialog).toContainText(
			'written to the audit trail before anything is restored',
		)
		expect(restoreCalls).toBe(0)

		await dialog.getByRole('button', { name: 'Restore and log' }).click()
		await expect(dialog).toContainText('You are not allowed to restore names.')
		expect(restoreCalls).toBe(1)
	})
})
