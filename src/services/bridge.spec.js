/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The bridge client: sources with their health for the admin panel, and one
 * badge request per page of My Documents.
 *
 * @spec openspec/changes/zgw-document-bridge/tasks.md#3-2
 */

import axios from '@nextcloud/axios'
import { fetchBridgeSources, fetchSourceBadges, healthColorMap, healthLabel, sourceBadgeLabel, sourceTypeLabel } from './bridge.js'

jest.mock('@nextcloud/axios', () => ({ __esModule: true, default: { get: jest.fn() } }))
jest.mock('@nextcloud/router', () => ({ generateUrl: (url) => '/index.php' + url }))
jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars) => text.replace(/{(\w+)}/g, (match, key) => (vars && key in vars ? vars[key] : match)),
}))

beforeEach(() => axios.get.mockReset())

describe('fetchSourceBadges', () => {
	it('asks once for the whole page and keeps only named vendors', async () => {
		axios.get.mockResolvedValue({ data: { badges: { 10: 'zaaksysteem.nl', 11: '', 12: 5 } } })

		await expect(fetchSourceBadges([10, 11, 12])).resolves.toEqual({ 10: 'zaaksysteem.nl' })
		expect(axios.get).toHaveBeenCalledTimes(1)
		expect(axios.get).toHaveBeenCalledWith('/index.php/apps/filinq/api/bridge/badges', { params: { fileIds: '10,11,12' } })
	})

	it('asks nothing for an empty page and reads a malformed answer as no badges', async () => {
		await expect(fetchSourceBadges([])).resolves.toEqual({})
		expect(axios.get).not.toHaveBeenCalled()

		axios.get.mockResolvedValue({ data: { badges: null } })
		await expect(fetchSourceBadges([1])).resolves.toEqual({})
	})
})

describe('fetchBridgeSources', () => {
	it('reads the admin sources, an odd answer as none', async () => {
		axios.get.mockResolvedValueOnce({ data: { results: [{ name: 'A', health: 'fresh' }] } })
		await expect(fetchBridgeSources()).resolves.toEqual([{ name: 'A', health: 'fresh' }])
		expect(axios.get).toHaveBeenCalledWith('/index.php/apps/filinq/api/bridge/sources')

		axios.get.mockResolvedValueOnce({ data: {} })
		await expect(fetchBridgeSources()).resolves.toEqual([])
	})
})

describe('labels', () => {
	it('names the badge after the vendor', () => {
		expect(sourceBadgeLabel('zaaksysteem.nl')).toBe('Zaaksysteem: zaaksysteem.nl')
	})

	it('names each health with its colour, anything else unknown', () => {
		expect(healthLabel('fresh')).toBe('Up to date')
		expect(healthLabel('failing')).toBe('Failing')
		expect(healthLabel('nope')).toBe('Unknown')
		expect(healthColorMap()).toEqual({ 'Up to date': 'success', 'Not synced in the last day': 'warning', Failing: 'error', Inactive: 'default' })
	})

	it('names the two source types and passes another through', () => {
		expect(sourceTypeLabel('zgw-drc')).toBe('ZGW Documenten API')
		expect(sourceTypeLabel('stuf-zds')).toBe('StUF-ZDS')
		expect(sourceTypeLabel('other')).toBe('other')
		expect(sourceTypeLabel('')).toBe('-')
	})
})
