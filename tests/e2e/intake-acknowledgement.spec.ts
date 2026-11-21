/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * intake-acknowledge-and-stamp (REQ-IAS-001 to REQ-IAS-004): the intake
 * inbox shows per waiting document whether the sender was told it arrived
 * (sent, queued as letter, refused, failed or nothing), and offers to open
 * the stamped copy when one was made.
 *
 * WHAT IS DRIVEN THROUGH THE UI AND WHAT IS NOT
 * --------------------------------------------
 * The Acknowledged column and the "Open stamped copy" row action on /intake
 * are what a registrar uses, so they are read and clicked. A document reaches
 * the inbox through a background feeder (a mailbox, a scanner), and the
 * acknowledgement and the stamp are written by the arrival listener after
 * that save; no browser journey makes a document arrive. So the inbox answer,
 * GET /api/intake/documents, comes from a route handler with the shape
 * IntakeController::index() returns, each row carrying the `acknowledgement`
 * and `stampedRendition` the listener writes.
 *
 * The stamped copy is a real PDF uploaded through WebDAV; the test checks
 * that the row action opens Nextcloud's /f/{fileId} for it. What is ON the
 * stamp (the word "Ontvangen", the date, the number) and that the arrived
 * file keeps its checksum are read by PHPUnit (ReceivedStampServiceTest),
 * because the stamp is made by the arrival, not by a screen.
 *
 * Whether the mail really reaches j.devries@example.nl needs a mail server;
 * that is task 4.2's live check, not this file.
 */

import type { Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { go } from './spec-coverage/_helpers.ts'
import {
	createDavFile,
	createDavFolder,
	deleteDavPath,
	harvestToken,
	TEST_PREFIX,
} from './workflows/_fixtures.ts'

/** Folder the stamped copy is uploaded into. */
const FOLDER = `${TEST_PREFIX}-ack-stamp`

/**
 * A waiting intake document as the inbox returns it.
 *
 * @param subject The subject line.
 * @param extra   Fields the arrival listener wrote.
 * @return The row.
 */
function waitingRow(
	subject: string,
	extra: Record<string, unknown> = {},
): Record<string, unknown> {
	return {
		uuid: `${TEST_PREFIX}-${subject}`,
		channel: 'mail',
		sender: 'j.devries@example.nl',
		subject: `${TEST_PREFIX} ${subject}`,
		receivedAt: '2026-10-14T09:12:00+02:00',
		status: 'received',
		...extra,
	}
}

/**
 * Answer the waiting inbox with fixed rows.
 *
 * @param page The page.
 * @param rows The waiting documents.
 * @return Nothing.
 */
async function answerInbox(
	page: Page,
	rows: Array<Record<string, unknown>>,
): Promise<void> {
	await page.route(
		(url) =>
			url.pathname.endsWith('/apps/filinq/api/intake/documents')
			&& !url.searchParams.has('mode'),
		async (route) => {
			if (route.request().method() !== 'GET') {
				await route.fallback()
				return
			}
			await route.fulfill({
				status: 200,
				contentType: 'application/json',
				body: JSON.stringify({
					results: rows,
					total: rows.length,
					notificationReach: null,
				}),
			})
		},
	)
}

/**
 * The inbox row of a subject.
 *
 * @param page    The page.
 * @param subject The subject as passed to waitingRow().
 * @return The row locator.
 */
function rowOf(page: Page, subject: string) {
	return page.getByRole('row', { name: new RegExp(`${TEST_PREFIX} ${subject}`) })
}

test.describe('The intake inbox shows who was told a document arrived', () => {
	// @e2e intake-acknowledgement::a-mailed-letter-is-acknowledged
	test('a mailed letter that was acknowledged reads Sent on its row', async ({
		page,
	}) => {
		await answerInbox(page, [
			waitingRow('mailed', {
				acknowledgement: {
					status: 'sent',
					address: 'j.devries@example.nl',
					at: '2026-10-14T09:12:30+02:00',
				},
			}),
		])
		await go(page, 'intake')

		const row = rowOf(page, 'mailed')
		await expect(row).toBeVisible({ timeout: 15000 })
		const badge = row.getByTestId('intake-acknowledgement')
		await expect(badge).toHaveText(/Sent/)
		await expect(badge).toHaveAttribute('title', 'j.devries@example.nl')
	})

	// @e2e intake-acknowledgement::a-paper-letter-gets-a-letter-back
	test('a scanned letter from a sender without an address reads Queued as letter', async ({
		page,
	}) => {
		await answerInbox(page, [
			waitingRow('paper', {
				channel: 'scan',
				sender: 'Fam. Jansen, Dorpsstraat 1',
				acknowledgement: { status: 'queued-as-letter', letter: 4242 },
			}),
		])
		await go(page, 'intake')

		const row = rowOf(page, 'paper')
		await expect(row).toBeVisible({ timeout: 15000 })
		await expect(row.getByTestId('intake-acknowledgement')).toHaveText(
			/Queued as letter/,
		)
	})

	// @e2e intake-acknowledgement::a-registrar-prints-a-stamped-scan
	test('Open stamped copy opens the stamped rendition, not the arrived scan', async ({
		page,
	}) => {
		await page.goto('/index.php/apps/filinq/')
		const token = await harvestToken(page)
		expect(await createDavFolder(page.request, token, FOLDER)).toBeLessThan(300)
		try {
			const stamped = await createDavFile(
				page.request,
				token,
				`${FOLDER}/scan-ontvangen.pdf`,
				'%PDF-1.4\n%stamped copy placeholder\n',
			)
			expect(stamped.status, 'uploading the stamped copy').toBeLessThan(300)
			const arrived = await createDavFile(
				page.request,
				token,
				`${FOLDER}/scan.pdf`,
				'%PDF-1.4\n%arrived scan\n',
			)
			expect(arrived.status, 'uploading the arrived scan').toBeLessThan(300)

			await answerInbox(page, [
				waitingRow('stamped', {
					channel: 'scan',
					file: Number(arrived.fileId),
					stampedRendition: Number(stamped.fileId),
				}),
			])
			await go(page, 'intake')

			const row = rowOf(page, 'stamped')
			await expect(row).toBeVisible({ timeout: 15000 })
			await row.getByRole('button').last().click()
			const opened = page.waitForEvent('popup')
			await page
				.getByRole('menuitem', { name: 'Open stamped copy' })
				.or(page.getByRole('button', { name: 'Open stamped copy' }))
				.first()
				.click()
			const popup = await opened
			expect(popup.url()).toContain(`/f/${stamped.fileId}`)
			expect(popup.url()).not.toContain(`/f/${arrived.fileId}`)
			await popup.close()
		} finally {
			await deleteDavPath(page.request, token, FOLDER)
		}
	})

	// @e2e intake-acknowledgement::a-registrar-sees-who-was-not-told
	test('the column reads Sent, Refused and nothing for three waiting documents', async ({
		page,
	}) => {
		await answerInbox(page, [
			waitingRow('told', {
				acknowledgement: { status: 'sent', address: 'j.devries@example.nl' },
			}),
			waitingRow('refused', {
				sender: 'k.bakker@example.nl',
				acknowledgement: {
					status: 'refused',
					reason: 'The sender asked not to be contacted.',
				},
			}),
			waitingRow('no-rule', { sender: 'l.visser@example.nl' }),
		])
		await go(page, 'intake')

		await expect(rowOf(page, 'told')).toBeVisible({ timeout: 15000 })
		await expect(
			rowOf(page, 'told').getByTestId('intake-acknowledgement'),
		).toHaveText(/Sent/)
		const refused = rowOf(page, 'refused').getByTestId('intake-acknowledgement')
		await expect(refused).toHaveText(/Refused/)
		await expect(refused).toHaveAttribute(
			'title',
			'The sender asked not to be contacted.',
		)
		await expect(
			rowOf(page, 'no-rule').getByTestId('intake-acknowledgement'),
		).toHaveCount(0)
	})
})
