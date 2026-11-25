/**
 * A document's activity beside the viewer: the trail endpoint the card reads,
 * the words for each action, and the report that the viewer opened a file.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
	DOCUMENT_ACTIVITY_ENDPOINT,
	documentActivityLabels,
	recordDocumentOpened,
} from '../../src/services/documentActivity.js'

const calls = []
let answer = () => Promise.resolve({ data: { recorded: true } })

vi.mock('@nextcloud/axios', () => ({
	default: {
		post: (url) => {
			calls.push(url)
			return answer()
		},
	},
}))

beforeEach(() => {
	calls.length = 0
	answer = () => Promise.resolve({ data: { recorded: true } })
})

describe('DOCUMENT_ACTIVITY_ENDPOINT', () => {
	it('names the trail route of one file', () => {
		expect(DOCUMENT_ACTIVITY_ENDPOINT.replace('{id}', '41')).toBe('/apps/filinq/api/documents/41/audit')
	})
})

describe('documentActivityLabels', () => {
	it('has words for every document action the server writes', () => {
		const labels = documentActivityLabels()
		for (const action of ['opened', 'downloaded', 'edited', 'versioned', 'shared', 'anonymised', 'signed', 'deleted']) {
			expect(labels[`filinq.document.${action}`]).toBeTruthy()
		}
		expect(labels.create).toBe('Registered')
	})
})

describe('recordDocumentOpened', () => {
	it('posts the opening of a file and answers whether it was written', async () => {
		await expect(recordDocumentOpened(41)).resolves.toBe(true)
		expect(calls).toEqual([expect.stringContaining('/apps/filinq/api/documents/41/opened')])
	})

	it('posts nothing without a file', async () => {
		await expect(recordDocumentOpened(0)).resolves.toBe(false)
		await expect(recordDocumentOpened('abc')).resolves.toBe(false)
		await expect(recordDocumentOpened(null)).resolves.toBe(false)
		expect(calls).toEqual([])
	})

	it('never throws when the report fails', async () => {
		answer = () => Promise.reject(new Error('404'))
		await expect(recordDocumentOpened(41)).resolves.toBe(false)
	})
})
