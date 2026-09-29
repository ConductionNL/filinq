/**
 * The banner reads the backend the server read, never a guessed one.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/anonymisation-fails-closed-without-a-detector/tasks.md#task-4
 */

import { readFileSync } from 'node:fs'
import { describe, expect, it } from 'vitest'
import {
	backendStateFromSettings,
	isRefusing,
} from '../../src/services/anonymiserBackendState.js'

describe('backendStateFromSettings', () => {
	it('names the backend OpenRegister reports', () => {
		const state = backendStateFromSettings({
			known: true,
			activeMethod: 'openanonymiser',
			effectiveMethod: 'openanonymiser',
			warning: null,
			showWarning: false,
		})
		expect(state.effectiveMethod).toBe('openanonymiser')
		expect(state.showWarning).toBe(false)
	})

	it('does not invent regex when the payload is missing', () => {
		const state = backendStateFromSettings(undefined)
		expect(state.effectiveMethod).toBe('')
		expect(state.warning).toBeNull()
	})

	it('ignores the retired method key', () => {
		expect(backendStateFromSettings({ method: 'regex' }).effectiveMethod).toBe('')
	})
})

describe('isRefusing', () => {
	it('marks the three states that stop anonymisation', () => {
		expect(['unknown', 'disabled', 'unavailable'].every(isRefusing)).toBe(true)
		expect(isRefusing('regex')).toBe(false)
		expect(isRefusing(null)).toBe(false)
	})
})

describe('the views', () => {
	it('read the state through backendStateFromSettings, not a method key', () => {
		for (const view of ['src/views/settings/Settings.vue', 'src/views/dashboard/DashboardIndex.vue']) {
			const source = readFileSync(view, 'utf8')
			expect(source).toContain('backendStateFromSettings(')
			expect(source).not.toMatch(/anonymiserBackend\.method/)
		}
	})
})
