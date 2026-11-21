/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * letter-email-sending, task 5: a composed letter is sent by email from the
 * Correspondence page (REQ-LCS-01), and a send that cannot happen says why
 * and leaves the dialog open (REQ-LCS-02).
 *
 * WHAT IS STUBBED. The two server answers the page reads, the preview
 * (`POST api/correspondence/send/preview`) and the send
 * (`POST api/correspondence/send`), are answered with `page.route()`, so the
 * screen half runs on any instance and the test can read exactly what the
 * page sent: one send request, with `attachPdf` as the officer chose it. The
 * server half (address lookup, composing, IMailer, the log entry) is pinned
 * by PHPUnit (`CorrespondenceServiceSendTest`, `CorrespondenceControllerSendTest`).
 *
 * THE REAL SEND. With `E2E_MAIL_CATCHER_URL` (a Mailpit-compatible API, e.g.
 * `http://localhost:8025`), `E2E_LETTER_TEMPLATE_ID` and
 * `E2E_LETTER_RECIPIENT` (`register/schema/uuid` of a recipient with an email
 * address) set, the last test sends through the real route and reads the
 * message from the catcher. Without them it skips and says why.
 *
 * Locale: the assertions accept the English and the Dutch strings.
 *
 * Not run in the change that wrote it (decision 139): the live pass runs it.
 */

// @e2e openspec/changes/letter-email-sending/specs/letter-correspondence-generation/spec.md#an-officer-sends-a-letter-to-a-citizen
// @e2e openspec/changes/letter-email-sending/specs/letter-correspondence-generation/spec.md#the-pdf-goes-along
// @e2e openspec/changes/letter-email-sending/specs/letter-correspondence-generation/spec.md#the-recipient-has-no-email-address

import type { Page, Route } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { go } from './_helpers.ts'

/** The recipient the spec names. */
const ADDRESS = 'j.jansen@example.nl'

/** The subject the composed letter carries. */
const SUBJECT = 'Uitnodiging gesprek'

/** A template and a recipient reference; the stubbed server never reads them. */
const TEMPLATE_ID = 'e2e-template-uitnodiging-gesprek'
const RECIPIENT = { register: 'filinq', schema: 'contact', id: 'e2e-contact-jansen' }

/** The strings the spec names, in English and Dutch. */
const SEND_BY_EMAIL = /^(Send by email|Versturen per e-mail)$/
const SEND = /^(Send|Versturen)$/
const ATTACH_PDF = /Attach the letter as PDF|De brief als pdf bijvoegen/
const NO_ADDRESS = 'Deze ontvanger heeft geen e-mailadres'
const SENT_TO = new RegExp(
	`(Sent to|Verstuurd naar) ${ADDRESS.replace(/\./g, '\\.')}`,
)

/**
 * Answer the preview and the send, and record every send request.
 *
 * @param page The page.
 * @param preview What the preview answers: the address and subject, or a refusal.
 * @return The bodies of the send requests the page made.
 */
async function stubSend(
	page: Page,
	preview: { status: number; body: Record<string, unknown> },
): Promise<Array<Record<string, any>>> {
	const sent: Array<Record<string, any>> = []
	await page.route(
		'**/apps/filinq/api/correspondence/send/preview',
		(route: Route) =>
			route.fulfill({
				status: preview.status,
				contentType: 'application/json',
				body: JSON.stringify(preview.body),
			}),
	)
	await page.route(
		/\/apps\/filinq\/api\/correspondence\/send$/,
		async (route: Route) => {
			sent.push(route.request().postDataJSON() ?? {})
			await route.fulfill({
				status: 200,
				contentType: 'application/json',
				body: JSON.stringify({
					status: 'sent',
					to: ADDRESS,
					subject: SUBJECT,
					sentAt: '2026-11-20T10:00:00+00:00',
					warnings: [],
				}),
			})
		},
	)

	return sent
}

/**
 * Open the Correspondence page and fill one letter: a template and one recipient.
 *
 * @param page The page.
 * @param templateId The template UUID.
 * @param recipient The recipient reference.
 * @param recipient.register The register.
 * @param recipient.schema The schema.
 * @param recipient.id The object id.
 * @return Resolves once the form can send.
 */
async function fillLetter(
	page: Page,
	templateId: string,
	recipient: { register: string; schema: string; id: string },
): Promise<void> {
	await go(page, '/correspondence')
	await page.getByLabel(/Template UUID|Sjabloon-UUID/).fill(templateId)
	await page
		.getByRole('button', {
			name: /Add data reference|Gegevensreferentie toevoegen/,
		})
		.click()
	await page
		.getByLabel(/^(Register)$/)
		.last()
		.fill(recipient.register)
	await page
		.getByLabel(/^(Schema)$/)
		.last()
		.fill(recipient.schema)
	await page
		.getByLabel(/^(UUID)$/)
		.last()
		.fill(recipient.id)
	await expect(page.getByTestId('correspondence-send-email')).toBeEnabled()
}

test.describe('letter-email-sending: a letter goes out by email', () => {
	test('an officer sends a letter to a citizen', async ({ page }) => {
		const sent = await stubSend(page, {
			status: 200,
			body: { to: ADDRESS, subject: SUBJECT },
		})
		await fillLetter(page, TEMPLATE_ID, RECIPIENT)

		await page
			.getByTestId('correspondence-send-email')
			.getByText(SEND_BY_EMAIL)
			.click()
		const dialog = page.getByTestId('correspondence-send-dialog')
		await expect(dialog).toBeVisible()
		await expect(dialog.getByTestId('correspondence-send-to')).toHaveText(
			ADDRESS,
		)
		await expect(dialog.getByTestId('correspondence-send-subject')).toHaveText(
			SUBJECT,
		)

		await dialog.getByRole('button', { name: SEND }).click()

		await expect(page.getByTestId('correspondence-sent')).toContainText(SENT_TO)
		expect(sent, 'exactly one send request').toHaveLength(1)
		expect(sent[0]?.templateId).toBe(TEMPLATE_ID)
		expect(sent[0]?.options?.attachPdf).toBe(false)
	})

	test('the PDF goes along when the officer asks for it', async ({ page }) => {
		const sent = await stubSend(page, {
			status: 200,
			body: { to: ADDRESS, subject: SUBJECT },
		})
		await fillLetter(page, TEMPLATE_ID, RECIPIENT)

		await page.getByTestId('correspondence-send-email').click()
		const dialog = page.getByTestId('correspondence-send-dialog')
		await dialog
			.getByTestId('correspondence-send-pdf')
			.getByText(ATTACH_PDF)
			.click()
		await dialog.getByRole('button', { name: SEND }).click()

		await expect(page.getByTestId('correspondence-sent')).toBeVisible()
		expect(sent).toHaveLength(1)
		expect(sent[0]?.options?.attachPdf).toBe(true)
	})

	test('a recipient without an email address is refused and nothing is sent', async ({
		page,
	}) => {
		const sent = await stubSend(page, {
			status: 422,
			body: { error: NO_ADDRESS },
		})
		await fillLetter(page, TEMPLATE_ID, RECIPIENT)

		await page.getByTestId('correspondence-send-email').click()
		const dialog = page.getByTestId('correspondence-send-dialog')
		await expect(dialog.getByTestId('correspondence-send-error')).toContainText(
			NO_ADDRESS,
		)
		await expect(dialog, 'the dialog stays open').toBeVisible()
		await expect(dialog.getByRole('button', { name: SEND })).toBeDisabled()
		expect(sent, 'no send request').toHaveLength(0)
		await expect(page.getByTestId('correspondence-sent')).toHaveCount(0)
	})

	test('the message arrives at the dev mail catcher', async ({
		page,
		request,
	}) => {
		const catcher = process.env.E2E_MAIL_CATCHER_URL ?? ''
		const templateId = process.env.E2E_LETTER_TEMPLATE_ID ?? ''
		const [register, schema, id] = (
			process.env.E2E_LETTER_RECIPIENT ?? ''
		).split('/')
		test.skip(
			catcher === '' || templateId === '' || !register || !schema || !id,
			'needs E2E_MAIL_CATCHER_URL, E2E_LETTER_TEMPLATE_ID and E2E_LETTER_RECIPIENT (register/schema/uuid)',
		)

		const before = Date.now()
		await fillLetter(page, templateId, { register, schema, id })
		await page.getByTestId('correspondence-send-email').click()
		const dialog = page.getByTestId('correspondence-send-dialog')
		const to = (
			await dialog.getByTestId('correspondence-send-to').innerText()
		).trim()
		expect(to, 'the recipient has an address').toContain('@')
		await dialog.getByRole('button', { name: SEND }).click()
		await expect(page.getByTestId('correspondence-sent')).toBeVisible()

		await expect
			.poll(
				async () => {
					const res = await request.get(
						`${catcher}/api/v1/search?query=${encodeURIComponent(`to:${to}`)}`,
					)
					if (!res.ok()) {
						return 0
					}
					const body = await res.json()
					const messages: Array<{ Created?: string }> =
						body?.messages ?? []
					return messages.filter(
						(m) => new Date(m.Created ?? 0).getTime() >= before - 1000,
					).length
				},
				{ timeout: 30_000, message: 'one message reaches the catcher' },
			)
			.toBe(1)
	})
})
