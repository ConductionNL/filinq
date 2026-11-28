/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The detection-provider calls, the picker and test wording, and the notice
 * the review shows about which engine produced a document's entities.
 *
 * @spec openspec/changes/archive/2026-11-24-llm-entity-detection-provider/tasks.md#task-3.2
 * @spec openspec/changes/archive/2026-11-24-llm-entity-detection-provider/tasks.md#task-4.2
 */

import axios from '@nextcloud/axios'
import {
	applyDetectionNotice,
	detectionNotice,
	loadDetectionSettings,
	loadOrganisations,
	providerFor,
	providerLabel,
	saveDetectionSettings,
	testDetectionProvider,
	testResultText,
} from './detectionProvider.js'

jest.mock('@nextcloud/axios', () => ({
	__esModule: true,
	default: { get: jest.fn(), put: jest.fn(), post: jest.fn() },
}))
jest.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))
jest.mock('@nextcloud/router', () => ({ generateUrl: (url) => '/index.php' + url }))

beforeEach(() => {
	jest.clearAllMocks()
})

describe('the settings calls', () => {
	test('loading reads the settings route', async () => {
		axios.get.mockResolvedValue({ data: { providers: {} } })

		const result = await loadDetectionSettings()

		expect(axios.get).toHaveBeenCalledWith('/index.php/apps/filinq/api/detection-provider/settings')
		expect(result).toEqual({ ok: true, data: { providers: {} } })
	})

	test('saving puts exactly the given fields', async () => {
		axios.put.mockResolvedValue({ data: { providers: { org: 'llm' } } })

		const result = await saveDetectionSettings({ organisation: 'org', provider: 'llm' })

		expect(axios.put).toHaveBeenCalledWith(
			'/index.php/apps/filinq/api/detection-provider/settings',
			{ organisation: 'org', provider: 'llm' },
		)
		expect(result.ok).toBe(true)
	})

	test('a refused save carries the status', async () => {
		axios.put.mockRejectedValue({ response: { status: 400, data: { error: 'endpoint' } } })

		const result = await saveDetectionSettings({ endpoint: 'ftp://x' })

		expect(result).toEqual({ ok: false, status: 400, error: 'endpoint' })
	})

	test('the connectivity test posts to the test route', async () => {
		axios.post.mockResolvedValue({ data: { available: false, reason: 'unreachable' } })

		const result = await testDetectionProvider()

		expect(axios.post).toHaveBeenCalledWith('/index.php/apps/filinq/api/detection-provider/test')
		expect(result.data).toEqual({ available: false, reason: 'unreachable' })
	})
})

describe('loadOrganisations', () => {
	test('lists organisations with a uuid and names the active one', async () => {
		axios.get.mockResolvedValue({
			data: {
				active: { uuid: 'b' },
				results: [{ uuid: 'a', name: 'Alpha' }, { uuid: 'b' }, { name: 'no id' }, null],
			},
		})

		const result = await loadOrganisations()

		expect(axios.get).toHaveBeenCalledWith('/index.php/apps/openregister/api/organisations')
		expect(result).toEqual({
			ok: true,
			data: { organisations: [{ uuid: 'a', name: 'Alpha' }, { uuid: 'b', name: 'b' }], active: 'b' },
		})
	})

	test('an answer without results lists none and no active one', async () => {
		axios.get.mockResolvedValue({ data: {} })

		expect(await loadOrganisations()).toEqual({ ok: true, data: { organisations: [], active: null } })
	})

	test('a refusal is passed on', async () => {
		axios.get.mockRejectedValue({ response: { status: 403, data: {} } })

		expect(await loadOrganisations()).toEqual({ ok: false, status: 403, error: '' })
	})
})

describe('providerFor', () => {
	test('an opted-in organisation reads its provider', () => {
		expect(providerFor({ org: 'llm' }, 'org')).toBe('llm')
	})

	test('an organisation not in the map, or none, is on the default', () => {
		expect(providerFor({ org: 'llm' }, 'other')).toBe('default')
		expect(providerFor({ org: 'llm' }, '')).toBe('default')
		expect(providerFor(null, 'org')).toBe('default')
	})
})

describe('wording', () => {
	test('known providers have a label and an unknown one is shown as it is', () => {
		expect(providerLabel('default')).toBe('Default detection (OpenRegister)')
		expect(providerLabel('llm')).toBe('Local language model (Hermiq agentflow)')
		expect(providerLabel('other')).toBe('other')
	})

	test('each test result reads as its own sentence', () => {
		expect(testResultText({ available: true, reason: null })).toBe('The language model provider answers and can be used.')
		expect(testResultText({ available: false, reason: 'unconfigured' })).toContain('not set up')
		expect(testResultText({ available: false, reason: 'not-local' })).toContain('only local endpoints')
		expect(testResultText({ available: false, reason: 'unreachable' })).toContain('did not answer')
		expect(testResultText({ available: false, reason: 'new' })).toBe('The language model provider cannot be used.')
		expect(testResultText(null)).toBe('The language model provider cannot be used.')
	})
})

describe('detectionNotice', () => {
	test('a language-model run names its provider and carries no warning', () => {
		expect(detectionNotice({ detectionProvider: 'llm', detectionFellBack: false, detectionWarning: null }))
			.toEqual({ provider: 'llm', fellBack: false, warning: null })
	})

	test('a fallback carries the server warning', () => {
		expect(detectionNotice({ detectionProvider: 'default', detectionFellBack: true, detectionWarning: 'Fell back.' }))
			.toEqual({ provider: 'default', fellBack: true, warning: 'Fell back.' })
	})

	test('a fallback without a server warning still warns', () => {
		const notice = detectionNotice({ detectionProvider: 'default', detectionFellBack: true, detectionWarning: '  ' })

		expect(notice.fellBack).toBe(true)
		expect(notice.warning).toContain('default detection was used')
	})

	test('an answer without a provider has no notice', () => {
		const none = { provider: null, fellBack: false, warning: null }
		expect(detectionNotice({ entities: [] })).toEqual(none)
		expect(detectionNotice({ detectionProvider: '', detectionFellBack: true })).toEqual(none)
		expect(detectionNotice(null)).toEqual(none)
	})

	test('the notice is copied onto a queue entry', () => {
		const entry = { detectionWarning: 'stale' }

		applyDetectionNotice(entry, { detectionProvider: 'default', detectionFellBack: true, detectionWarning: 'Fell back.' })

		expect(entry).toEqual({ detectionProvider: 'default', detectionFellBack: true, detectionWarning: 'Fell back.' })
	})
})
