/**
 * The SFC options harness evaluates a component's script blocks the way the
 * Vue compiler merges them, and fakes enough of an instance to drive them.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude test of the characterization test harness
 */

import { join } from 'node:path'
import { describe, expect, it, vi } from 'vitest'
import { createInstance, loadSfcOptions } from './sfc/sfcOptions.js'

vi.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars) =>
		text.replace(/\{(\w+)\}/g, (_, k) => String(vars?.[k] ?? '')),
}))

const FILE = join(import.meta.dirname, 'sfc', 'fixtures', 'CounterFixture.vue')

describe('sfcOptions', () => {
	it('binds setup imports, real modules and UI stubs', async () => {
		const options = await loadSfcOptions(FILE)
		expect(options.name).toBe('CounterFixture')
		expect(options.components.NcButton).toEqual({
			stub: '@nextcloud/vue#NcButton',
		})
		expect(options.components.Plus).toEqual({
			stub: 'vue-material-design-icons/Plus.vue#default',
		})
		expect(options.mixins).toHaveLength(1)
	})

	it('merges mixins, props, data, computed, watchers and hooks', async () => {
		const vm = createInstance(await loadSfcOptions(FILE), {
			props: { step: 5 },
		})
		expect(vm.tags).toEqual(['a'])
		expect(vm.source).toBe('component')
		expect(vm.label()).toBe('component')
		vm.increment()
		expect(vm.count).toBe(5)
		expect(vm.title).toBe('Count 5')
		await vm.$fireWatch('count', 5, 0)
		expect(vm.$emitted).toEqual([['changed', 5, 0]])
		await vm.$hook('mounted')
		expect(vm.log).toEqual(['mixin mounted', 'component mounted'])
		vm.open()
		expect(vm.$pushed).toEqual([{ name: 'Detail' }])
	})
})
