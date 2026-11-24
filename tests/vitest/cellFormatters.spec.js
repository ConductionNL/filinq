/**
 * The app's cell formatters: a list on one line, and counts grouped by keys;
 * and the destruction certificates page that names them is served them.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-verklaring-van-vernietiging-is-listed-and-permanent-req-ddare-005
 */

import { readFileSync } from 'node:fs'
import { describe, expect, it } from 'vitest'
import {
	CELL_FORMATTERS,
	formatGroupCounts,
	formatJoinedList,
	formatRowField,
} from '../../src/services/cellFormatters.js'
import { effectiveManifest } from './support/effectiveManifest.js'

describe('cell formatters', () => {
	it('writes a list on one line and leaves out what is empty', () => {
		expect(formatJoinedList(['admin', ' archivaris ', '', null, 7])).toBe(
			'admin, archivaris, 7',
		)
		expect(formatJoinedList('VNG selectielijst 2020')).toBe(
			'VNG selectielijst 2020',
		)
		expect(formatJoinedList([])).toBe('')
		expect(formatJoinedList(null)).toBe('')
		expect(formatJoinedList([{ name: 'x' }])).toBe('')
	})

	it('writes counts grouped by the declared keys', () => {
		const groups = [
			{ schema: 12, classification: '1.1', count: 3 },
			{ schema: 14, classification: null, count: 2 },
		]

		expect(
			formatGroupCounts(
				groups,
				{},
				{},
				{ keys: ['schema', 'classification'] },
			),
		).toBe('12 / 1.1: 3; 14: 2')
	})

	it('names a group by every key but the count when no keys are declared', () => {
		expect(formatGroupCounts([{ schema: 'brief', count: 4 }])).toBe('brief: 4')
		expect(
			formatGroupCounts(
				[{ type: 'a', total: 9 }],
				{},
				{},
				{ countKey: 'total' },
			),
		).toBe('a: 9')
	})

	it('writes a group without a name or count as its count, zero when missing', () => {
		expect(formatGroupCounts([{ count: 5 }, {}], {}, {}, {})).toBe('5; 0')
	})

	it('writes nothing for what is not a list of groups', () => {
		expect(formatGroupCounts(null)).toBe('')
		expect(formatGroupCounts('x')).toBe('')
		expect(formatGroupCounts([null, ['a'], 'x'])).toBe('')
	})

	it('serves the formatters the certificates page names, through the app shell', () => {
		const page = effectiveManifest().pages.find(
			(entry) => entry.id === 'DestructionCertificates',
		)
		const named = page.config.columns
			.map((column) => column.formatter)
			.filter(Boolean)

		expect(page.type).toBe('index')
		expect(page.config.sourceConfig.url).toBe(
			'/apps/openregister/api/archival/certificates',
		)
		expect(named).toEqual(['date', 'groupCounts', 'joinedList', 'joinedList'])
		expect(Object.keys(CELL_FORMATTERS)).toEqual(['joinedList', 'groupCounts', 'rowField'])
		const app = readFileSync(
			new URL('../../src/App.vue', import.meta.url),
			'utf8',
		)
		expect(app).toContain(':formatters="$options.cellFormatters"')
		expect(app).toContain('cellFormatters: CELL_FORMATTERS')
	})

	it('reads a value further down the row by its dotted path', () => {
		const row = { retention: { archiefactiedatum: '2020-01-01', bewaartermijn: 20 } }

		expect(formatRowField(undefined, row, {}, { path: 'retention.archiefactiedatum' })).toBe('2020-01-01')
		expect(formatRowField(undefined, row, {}, { path: 'retention.bewaartermijn' })).toBe('20')
	})

	it('writes nothing when the path leads nowhere, to no scalar, or is not given', () => {
		const row = { retention: { classification: ['1.1'] }, name: 'x' }

		expect(formatRowField(undefined, row, {}, { path: 'retention.missing' })).toBe('')
		expect(formatRowField(undefined, row, {}, { path: 'name.deeper' })).toBe('')
		expect(formatRowField(undefined, row, {}, { path: 'retention.classification' })).toBe('')
		expect(formatRowField(undefined, row, {}, { path: 'retention' })).toBe('')
		expect(formatRowField(undefined, row, {}, {})).toBe('')
		expect(formatRowField(undefined, null, {}, { path: 'name' })).toBe('')
	})

	it('lists the records due for transfer from OpenRegister, the dates read by path', () => {
		const page = effectiveManifest().pages.find((entry) => entry.id === 'TransferDue')

		expect(page.type).toBe('index')
		expect(page.route).toBe('/transfer-due')
		expect(page.config.entitySource).toBe('endpoint')
		expect(page.config.sourceConfig.url).toBe('/apps/openregister/api/transfers/eligible')
		expect(page.config.sourceConfig.rowsPath).toBe('results')
		const byPath = page.config.columns
			.filter((column) => column.formatter === 'rowField')
			.map((column) => column.formatterOptions.path)
		expect(byPath).toEqual(['retention.archiefactiedatum', 'retention.classification'])
		const keys = page.config.columns.map((column) => column.key)
		expect(new Set(keys).size).toBe(keys.length)
	})
})
