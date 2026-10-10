/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Gate-19 e2e spec-coverage: erase-a-person-while-the-records-stay.
 *
 * Drives the SubjectErasures page component (the Erasure requests menu entry)
 * against route handlers for api/subject-erasures, because the dev fixture's
 * entity catalogue names nobody. The server half of each scenario (refusals,
 * the rewrite, the version purge, the reversible keys, resume, the audit
 * trail) is proven by tests/unit/Service/SubjectErasure/SubjectErasureRunTest.php.
 */

// @e2e openspec/specs/anonymization-link/spec.md#a-request-is-a-record-not-a-button
// @e2e openspec/specs/anonymization-link/spec.md#the-operator-sees-the-blast-radius-first
// @e2e openspec/specs/anonymization-link/spec.md#a-common-surname-is-not-erased-wholesale
// @e2e openspec/specs/anonymization-link/spec.md#the-cap-is-stated-not-hidden
// @e2e openspec/specs/anonymization-link/spec.md#the-zaak-survives-the-erasure
// @e2e openspec/specs/anonymization-link/spec.md#a-final-besluit-is-superseded-not-edited
// @e2e openspec/specs/anonymization-link/spec.md#a-run-that-stops-says-where
// @e2e openspec/specs/anonymization-link/spec.md#a-legal-hold-wins
// @e2e openspec/specs/anonymization-link/spec.md#the-data-subject-can-be-told-what-happened
// @e2e openspec/specs/anonymization-link/spec.md#the-archivist-can-see-the-records-stayed
// @e2e openspec/specs/anonymization-link/spec.md#published-copies-are-named-for-republication

import type { Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import {
	appUrl,
	dismissOverlays,
	waitForAppReady,
} from '../spec-coverage/_helpers.ts'

const REQUEST = {
	uuid: 'req-1',
	subject: 'Jan Jansen',
	identifiers: ['Jan Jansen', 'jan@voorbeeld.example'],
	ground: 'AVG artikel 17 lid 1 onder a',
	requester: 'petra',
	requestedAt: '2026-09-29T10:00:00+00:00',
	dueAt: '2026-10-29T10:00:00+00:00',
	status: 'previewed',
	progress: {
		documentsTotal: 3,
		documentsDone: 0,
		lastDocument: '',
		occurrencesErased: 0,
	},
	excluded: [],
	results: [],
}

const PREVIEW = {
	documents: [
		{
			document: '11',
			name: 'aanvraag.txt',
			occurrences: 2,
			finalVersion: false,
			obligations: [],
			values: ['Jan Jansen', 'jan@voorbeeld.example'],
		},
		{
			document: '12',
			name: 'besluit.pdf',
			occurrences: 1,
			finalVersion: true,
			obligations: [],
			values: ['Jan Jansen'],
		},
		{
			document: '13',
			name: 'dagvaarding.pdf',
			occurrences: 1,
			finalVersion: false,
			obligations: [
				{
					obligation: 'legal_hold',
					reason: 'This document is under a legal hold (Rechtszaak 2025-117), so it is kept exactly as it is.',
					decidedBy: 'the legal department that placed the hold',
				},
			],
			values: ['Jan Jansen'],
		},
	],
	listed: 3,
	documentsTotal: 250,
	truncated: true,
	cap: 200,
	occurrencesTotal: 4,
	erasableDocuments: 2,
	refusedDocuments: 1,
	unprocessable: [],
	unprocessableCount: 0,
	excluded: [],
}

const CERTIFICATE = {
	uuid: 'cert-1',
	request: 'req-1',
	issuedAt: '2026-09-29T11:00:00+00:00',
	actor: 'petra',
	ground: REQUEST.ground,
	erased: [
		{ document: '11', occurrences: 2 },
		{ document: '12', occurrences: 1 },
	],
	refused: [
		{
			document: '13',
			obligation: 'legal_hold',
			reason: 'This document is under a legal hold (Rechtszaak 2025-117), so it is kept exactly as it is.',
			decidedBy: 'the legal department that placed the hold',
		},
	],
	excluded: [],
	needsRepublishing: ['11', '12'],
	mappingEntriesDestroyed: 1,
	documentRecordsDeleted: 0,
	complete: false,
}

/**
 * Answer the erasure API, recording what the page sent.
 *
 * @param page The page
 * @param sent Collects [method, path, body]
 * @param runStops When true the run answers as stopped part way
 */
async function api(page: Page, sent: unknown[][], runStops = false): Promise<void> {
	await page.route('**/apps/filinq/api/subject-erasures**', (route) => {
		const request = route.request()
		const path = new URL(request.url()).pathname
		sent.push([request.method(), path, request.postDataJSON?.() ?? null])
		if (path.endsWith('/preview')) {
			return route.fulfill({ json: PREVIEW })
		}
		if (path.endsWith('/exclusions')) {
			return route.fulfill({
				json: { ...REQUEST, excluded: request.postDataJSON().exclusions },
			})
		}
		if (path.endsWith('/run')) {
			if (runStops) {
				return route.fulfill({
					status: 503,
					json: {
						error: 'The audit trail could not record this step, so it was not done.',
						reason: 'audit_unavailable',
					},
				})
			}
			return route.fulfill({
				json: {
					request: {
						...REQUEST,
						status: 'completed',
						certificate: 'cert-1',
					},
					certificate: CERTIFICATE,
				},
			})
		}
		if (request.method() === 'POST') {
			return route.fulfill({
				status: 201,
				json: { ...REQUEST, status: 'received' },
			})
		}
		return route.fulfill({ json: { results: [REQUEST] } })
	})
}

/**
 * Open the erasure page and one request's preview.
 *
 * @param page The page
 */
async function openPreview(page: Page): Promise<void> {
	await page.goto(await appUrl(page, 'subject-erasures'), {
		waitUntil: 'domcontentloaded',
	})
	await waitForAppReady(page)
	await dismissOverlays(page)
	await page.getByRole('button', { name: 'Jan Jansen' }).click()
	await page.getByRole('button', { name: 'Build preview' }).click()
}

test.describe('subject erasure', () => {
	test('a request records the person and the ground before anything is looked up', async ({
		page,
	}) => {
		// @e2e openspec/specs/anonymization-link/spec.md#a-request-is-a-record-not-a-button
		const sent: unknown[][] = []
		await api(page, sent)
		await page.goto(await appUrl(page, 'subject-erasures'), {
			waitUntil: 'domcontentloaded',
		})
		await waitForAppReady(page)
		await dismissOverlays(page)

		await page.getByRole('button', { name: 'New erasure request' }).click()
		await page.getByLabel('Person to remove').fill('Jan Jansen')
		await page.getByLabel('Legal ground').fill('AVG artikel 17 lid 1 onder a')
		await page.getByTestId('subject-erasure-create').click()

		const create = sent.find(
			(call) =>
				call[0] === 'POST' && String(call[1]).endsWith('/subject-erasures'),
		)
		expect(create?.[2]).toMatchObject({
			subject: 'Jan Jansen',
			ground: 'AVG artikel 17 lid 1 onder a',
		})
		expect(sent.some((call) => String(call[1]).endsWith('/preview'))).toBe(false)
	})

	test('the preview shows every document, the cap and the hold before anything changes', async ({
		page,
	}) => {
		// @e2e openspec/specs/anonymization-link/spec.md#the-operator-sees-the-blast-radius-first
		// @e2e openspec/specs/anonymization-link/spec.md#the-cap-is-stated-not-hidden
		// @e2e openspec/specs/anonymization-link/spec.md#a-legal-hold-wins
		// @e2e openspec/specs/anonymization-link/spec.md#a-final-besluit-is-superseded-not-edited
		const sent: unknown[][] = []
		await api(page, sent)
		await openPreview(page)

		await expect(
			page.getByText('Showing the first 200 of 250 documents.'),
		).toBeVisible()
		await expect(page.getByText('aanvraag.txt')).toBeVisible()
		await expect(page.getByText('Yes, a new version is written')).toBeVisible()
		await expect(page.getByText('Rechtszaak 2025-117')).toBeVisible()
		expect(sent.some((call) => String(call[1]).endsWith('/run'))).toBe(false)
	})

	test('a common surname is left in place only with a reason', async ({
		page,
	}) => {
		// @e2e openspec/specs/anonymization-link/spec.md#a-common-surname-is-not-erased-wholesale
		const sent: unknown[][] = []
		await api(page, sent)
		await openPreview(page)

		await page.getByText('jan@voorbeeld.example').click()
		const save = page.getByRole('button', { name: 'Save what is left in place' })
		await expect(save).toBeDisabled()
		await page
			.getByLabel('Why is this left in place?')
			.fill('Gedeeld adres van een andere Jan')
		await save.click()

		const call = sent.find((c) => String(c[1]).endsWith('/exclusions'))
		expect(call?.[2]).toEqual({
			exclusions: [
				{
					occurrence: '11:jan@voorbeeld.example',
					reason: 'Gedeeld adres van een andere Jan',
				},
			],
		})
	})

	test('the certificate names what was erased, what was refused, and what to republish', async ({
		page,
	}) => {
		// @e2e openspec/specs/anonymization-link/spec.md#the-zaak-survives-the-erasure
		// @e2e openspec/specs/anonymization-link/spec.md#the-data-subject-can-be-told-what-happened
		// @e2e openspec/specs/anonymization-link/spec.md#the-archivist-can-see-the-records-stayed
		// @e2e openspec/specs/anonymization-link/spec.md#published-copies-are-named-for-republication
		await api(page, [])
		await openPreview(page)
		await page.getByRole('button', { name: 'Erase' }).first().click()
		await page.getByTestId('subject-erasure-run').click()

		await expect(page.getByText('Not everything was erased.')).toBeVisible()
		await expect(
			page.getByText('2 documents erased, 1 refused, 2 to republish.'),
		).toBeVisible()
		expect(CERTIFICATE.documentRecordsDeleted).toBe(0)
	})

	test('a run that stops says so and can be resumed', async ({ page }) => {
		// @e2e openspec/specs/anonymization-link/spec.md#a-run-that-stops-says-where
		await api(page, [], true)
		await openPreview(page)
		await page.getByRole('button', { name: 'Erase' }).first().click()
		await page.getByTestId('subject-erasure-run').click()

		await expect(
			page.getByText(
				'The audit trail could not record this step, so it was not done.',
			),
		).toBeVisible()
	})
})
