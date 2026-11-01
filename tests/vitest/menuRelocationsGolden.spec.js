/**
 * Golden: how the relocation pass re-homes menu entries, recorded on the
 * implementation where each pass walked the menu inline.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of existing menu relocation
 */

import { describe, it } from 'vitest'
import manifest from '../../src/manifest.json'
import {
	applyMenuRelocations,
	mergeMenuItems,
} from '../../src/services/menuRelocations.js'
import { golden } from './golden/golden.js'

/**
 * A fresh test menu.
 *
 * @return {Array<object>}
 */
function menu() {
	return [
		{ id: 'cap', type: 'caption', label: 'Caption' },
		{ id: 'self', route: 'self' },
		{ id: 'home', label: 'Home', route: 'home' },
		{
			id: 'settings',
			label: 'Settings',
			children: [
				{ id: 'general', route: 'general' },
				{
					id: 'nested',
					label: 'Nested',
					children: [{ id: 'deep', route: 'd' }],
				},
				{ id: 'stay', route: 'stay' },
			],
		},
		{
			id: 'tools',
			label: 'Tools',
			children: [
				{
					id: 'general',
					label: 'General',
					icon: 'cog',
					children: [{ id: 'g2', href: '/g' }],
				},
				{ id: 'export', action: 'export' },
			],
		},
		{ id: 'admin', label: 'Admin', icon: 'shield', children: [] },
		{ id: 'loose', label: 'Loose', href: '/loose' },
		{ id: 'empty', label: 'Empty group' },
		{ id: 'chain', label: 'Chain', route: 'chain' },
	]
}

const RELOCATIONS = [
	['none', undefined],
	['null', null],
	['string', 'tools'],
	['empty', {}],
	['group dissolves into a group', { tools: 'settings' }],
	['leaf to a group', { home: 'admin', loose: 'settings' }],
	['child leaf to another group', { stay: 'tools', export: 'admin' }],
	['child leaf onto its own parent', { stay: 'settings' }],
	['child group flattens into its parent', { nested: 'settings' }],
	['missing target', { home: 'nowhere', stay: 'nowhere' }],
	['self target', { self: 'self' }],
	['two children to one group', { general: 'admin', stay: 'admin' }],
	['chained over passes', { tools: 'settings', export: 'admin', admin: 'loose' }],
	[
		'cycle',
		{
			home: 'admin',
			admin: 'tools',
			tools: 'settings',
			settings: 'chain',
			chain: 'home',
		},
	],
	[
		'everything into admin',
		{
			cap: 'admin',
			settings: 'admin',
			tools: 'admin',
			empty: 'admin',
			deep: 'admin',
		},
	],
]

describe('menu relocations golden', () => {
	it('re-homes menu entries', () => {
		const out = RELOCATIONS.map(([name, relocations]) => {
			const input = menu()
			const result = applyMenuRelocations(input, relocations)
			return { name, result, input, same: result === input }
		})
		const real = structuredClone(manifest.menu)
		const relocated = applyMenuRelocations(real, {
			[real[1]?.id]: real[0]?.id,
		})
		out.push({
			name: 'bundled manifest, own group dissolved',
			result: relocated.map((m) => [
				m.id,
				(m.children || []).map((c) => c.id),
			]),
		})
		golden('menuRelocations', out)
	})

	it('merges menu items by id', () => {
		const target = [
			{ id: 'a', label: 'A', children: [{ id: 'x' }] },
			{ id: 'b', children: 'none' },
			{ id: 'f', label: 'F' },
		]
		mergeMenuItems(target, [
			{
				id: 'a',
				label: 'other',
				icon: 'i',
				order: 0,
				href: null,
				children: [{ id: 'x', route: 'r' }, { id: 'y' }],
			},
			{ id: 'b', permission: 'p', children: [{ id: 'z' }] },
			{ id: 'c', children: [{ id: 'k' }] },
			{ id: 'd', children: 'keep' },
			{ id: 'a', visibleIf: 'v', children: [] },
			{ id: 'f', children: [] },
		])
		const incoming = [{ id: 'e', children: [{ id: 'e1' }] }]
		mergeMenuItems(target, incoming)
		incoming[0].children.push({ id: 'e2' })
		golden('mergeMenuItems', {
			target,
			keys: target.map((entry) => Object.keys(entry)),
		})
	})
})
