/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The admin settings page rendered `sections[type].selectedRegister` for every
 * object type while `sections` was still `{}`, so every load threw
 * "Cannot read properties of undefined (reading 'selectedRegister')" until the
 * settings response had filled it in. `initialSections()` gives every object
 * type an empty section from the first render on.
 */

import { readFileSync } from 'fs'
import * as path from 'path'
import { describe, expect, it } from 'vitest'
import {
	emptySection,
	initialSections,
} from '../../src/services/settingsSections.js'

describe('admin settings sections', () => {
	it('gives every object type an empty section', () => {
		expect(initialSections(['publicationConsent', 'signingRequest'])).toEqual({
			publicationConsent: {
				selectedRegister: '',
				selectedSchema: '',
				loading: false,
			},
			signingRequest: {
				selectedRegister: '',
				selectedSchema: '',
				loading: false,
			},
		})
	})

	it('hands out a fresh object per section', () => {
		const sections = initialSections(['a', 'b'])
		sections.a.loading = true
		expect(sections.b.loading).toBe(false)
		expect(emptySection()).not.toBe(emptySection())
	})

	it('is what the settings page starts from, so the first render has a section per type', () => {
		const source = readFileSync(
			path.resolve(__dirname, '../../src/views/settings/Settings.vue'),
			'utf8',
		)
		expect(source).toMatch(/sections:\s*initialSections\(OBJECT_TYPES\)/)
		expect(source).toMatch(/objectTypes:\s*OBJECT_TYPES/)
	})
})
