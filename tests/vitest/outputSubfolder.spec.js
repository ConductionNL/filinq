/**
 * The output subfolder name rule: lowercase letters, digits, `_` and `-`
 * only, each offending character reported once in order of appearance.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

import { describe, expect, it } from 'vitest'
import {
	disallowedSubfolderCharacters,
	isValidSubfolderName,
} from '../../src/services/outputSubfolder.js'

describe('disallowedSubfolderCharacters', () => {
	it('reports nothing for an allowed name', () => {
		expect(disallowedSubfolderCharacters('output_2026-a')).toEqual([])
	})

	it('reports each offending character once, in order', () => {
		expect(disallowedSubfolderCharacters('Out put/ put')).toEqual(['O', ' ', '/'])
	})

	it('treats a missing name as empty', () => {
		expect(disallowedSubfolderCharacters(null)).toEqual([])
	})
})

describe('isValidSubfolderName', () => {
	it('accepts an allowed name', () => {
		expect(isValidSubfolderName('geanonimiseerd')).toBe(true)
	})

	it('refuses an empty or missing name', () => {
		expect(isValidSubfolderName('')).toBe(false)
		expect(isValidSubfolderName(undefined)).toBe(false)
	})

	it('refuses a name with a disallowed character', () => {
		expect(isValidSubfolderName('../etc')).toBe(false)
	})
})
