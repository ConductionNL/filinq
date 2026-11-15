// @vitest-environment jsdom
/**
 * The signing folder leaf: what it reads, where it links, and that it reaches
 * OpenRegister's integration registry under its id.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/tasks.md#task-1.2
 */

import { describe, expect, it, vi } from 'vitest'
import {
	registerSigningFolderLeaf,
	SIGNING_FOLDER_INTEGRATION_ID,
	SIGNING_FOLDER_SURFACES,
} from '../../src/integrations/registerSigningFolderLeaf.js'
import {
	fetchSigningFolderPreview,
	formatDeadline,
	SIGNING_FOLDER_PREVIEW_SIZE,
	signingFolderPageUrl,
} from '../../src/services/signingFolderLeaf.js'

const asked = []

// The widget itself is a .vue file, which this vitest config does not compile;
// the registration is what is under test here.
vi.mock('../../src/integrations/CnFilinqSigningFolderWidget.vue', () => ({
	default: {},
}))

vi.mock('@nextcloud/axios', () => ({
	default: {
		get: (url, config) => {
			asked.push({ url, config })
			return Promise.resolve({
				data: {
					entries: [
						{
							requestId: 'r1',
							documentName: 'Besluit.pdf',
							deadline: '2026-10-20',
						},
					],
					total: 7,
				},
			})
		},
	},
}))

describe('fetchSigningFolderPreview', () => {
	it('reads the first entries and the total from the folder endpoint', async () => {
		const preview = await fetchSigningFolderPreview()
		expect(asked[0].url).toContain('/apps/filinq/api/signing/folder')
		expect(asked[0].config.params).toEqual({
			limit: SIGNING_FOLDER_PREVIEW_SIZE,
			offset: 0,
		})
		expect(preview.entries.map((entry) => entry.requestId)).toEqual(['r1'])
		expect(preview.total).toBe(7)
	})
})

describe('signingFolderPageUrl', () => {
	it('links to the folder page in filinq', () => {
		expect(signingFolderPageUrl()).toContain('/apps/filinq/signing-folder')
	})
})

describe('formatDeadline', () => {
	it('shows nothing without a deadline and the raw value when it does not parse', () => {
		expect(formatDeadline('')).toBe('')
		expect(formatDeadline(undefined)).toBe('')
		expect(formatDeadline('soon')).toBe('soon')
		expect(formatDeadline('2026-10-20')).not.toBe('')
	})
})

describe('registerSigningFolderLeaf', () => {
	it('registers a dashboard leaf under its id, mounted by the host', () => {
		const target = {}
		registerSigningFolderLeaf(target)
		const entry = target.OCA.OpenRegister.integrations._queue[0]
		expect(entry.id).toBe(SIGNING_FOLDER_INTEGRATION_ID)
		expect(entry.id).toBe('filinq-signing-folder')
		expect(SIGNING_FOLDER_SURFACES).toEqual(['user-dashboard', 'app-dashboard'])
		expect(entry.renderMode).toBe('mount')
		expect(typeof entry.mount).toBe('function')
		expect(typeof entry.unmount).toBe('function')
	})
})
