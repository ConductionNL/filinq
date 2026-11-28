/**
 * Scanner profiles on the intake channels page: the add form, the new
 * profile's id, and the list declared back to the server.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-25-scan-intake-with-separator-sheets/tasks.md#task-4.2
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'

const post = vi.fn()
vi.mock('@nextcloud/axios', () => ({ default: { post: (...args) => post(...args) } }))

const {
	COLOUR_MODES,
	declareScanProfiles,
	emptyProfileForm,
	profileFormErrors,
	profileId,
	SEPARATOR_MODES,
	withNewProfile,
} = await import('../../src/services/scanProfiles.js')

describe('scanner profiles', () => {
	beforeEach(() => post.mockReset())

	it('offers the modes the server accepts', () => {
		expect(SEPARATOR_MODES).toEqual(['qr', 'blankPage', 'none'])
		expect(COLOUR_MODES).toEqual(['colour', 'greyscale', 'blackAndWhite'])
	})

	it('starts the form at 300 dpi, colour, QR separator', () => {
		expect(emptyProfileForm()).toEqual({
			label: '',
			watchedFolder: '',
			resolution: 300,
			colourMode: 'colour',
			separatorMode: 'qr',
		})
	})

	it('needs a name and a folder, and a resolution from 75 to 1200 dpi', () => {
		expect(profileFormErrors(emptyProfileForm())).toEqual({ label: 'required', watchedFolder: 'required' })
		const form = { ...emptyProfileForm(), label: 'Balie', watchedFolder: 'Scans/Balie' }
		expect(profileFormErrors(form)).toEqual({})
		expect(profileFormErrors({ ...form, resolution: 74 })).toEqual({ resolution: 'range' })
		expect(profileFormErrors({ ...form, resolution: '1201' })).toEqual({ resolution: 'range' })
		expect(profileFormErrors({ ...form, resolution: '600' })).toEqual({})
		expect(profileFormErrors({ ...form, resolution: 'x' })).toEqual({ resolution: 'range' })
	})

	it('derives a unique id from the name', () => {
		expect(profileId('Scanner 2e verdieping', [])).toBe('scanner-2e-verdieping')
		expect(profileId('Café balie', [{ id: 'cafe-balie' }, { id: 'cafe-balie-2' }])).toBe('cafe-balie-3')
		expect(profileId('***', [])).toBe('scanner')
	})

	it('appends the form as a profile and keeps the existing ones', () => {
		const existing = [{ id: 'balie', label: 'Balie', watchedFolder: 'Scans/Balie', separatorMode: 'qr' }]
		const next = withNewProfile(existing, {
			label: ' Scanner 2e verdieping ',
			watchedFolder: ' Scans/Post ',
			resolution: '300',
			colourMode: 'blackAndWhite',
			separatorMode: 'none',
		})
		expect(next[0]).toBe(existing[0])
		expect(next[1]).toEqual({
			id: 'scanner-2e-verdieping',
			label: 'Scanner 2e verdieping',
			watchedFolder: 'Scans/Post',
			resolution: 300,
			colourMode: 'blackAndWhite',
			separatorMode: 'none',
		})
	})

	it('falls back to the defaults for a mode the server does not know', () => {
		const [profile] = withNewProfile([], { label: 'A', watchedFolder: 'B', resolution: 300, colourMode: 'sepia', separatorMode: 'barcode' })
		expect(profile.colourMode).toBe('colour')
		expect(profile.separatorMode).toBe('qr')
	})

	it('declares the whole list and returns what the server stored', async () => {
		post.mockResolvedValue({ data: { results: [{ id: 'balie' }] } })
		const stored = await declareScanProfiles([{ id: 'balie' }])
		expect(post).toHaveBeenCalledWith('/index.php/apps/filinq/api/scan/profiles', { profiles: [{ id: 'balie' }] })
		expect(stored).toEqual([{ id: 'balie' }])
	})

	it('returns no profiles when the answer carries none', async () => {
		post.mockResolvedValue({ data: {} })
		expect(await declareScanProfiles([])).toEqual([])
	})
})
