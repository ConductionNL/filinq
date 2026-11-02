/**
 * Tests for the output subfolder name check of the admin settings.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

import {
	disallowedSubfolderCharacters,
	isValidSubfolderName,
} from './outputSubfolder.js'

// @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-2
describe('disallowedSubfolderCharacters', () => {
	it('accepts one lowercase segment with digits, hyphens and underscores', () => {
		expect(disallowedSubfolderCharacters('anonymised')).toEqual([])
		expect(disallowedSubfolderCharacters('redacted_2026-q1')).toEqual([])
	})

	it('names each character a traversal attempt uses, once', () => {
		expect(disallowedSubfolderCharacters('../traversal')).toEqual(['.', '/'])
	})

	it('names spaces and capitals', () => {
		expect(disallowedSubfolderCharacters('Mijn map')).toEqual(['M', ' '])
	})

	it('refuses an empty name and accepts a clean one', () => {
		expect(isValidSubfolderName('')).toBe(false)
		expect(isValidSubfolderName('../x')).toBe(false)
		expect(isValidSubfolderName('anonymised')).toBe(true)
	})
})
