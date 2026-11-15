/**
 * Golden fixtures for characterization tests.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * `golden(name, actual)` compares `actual` with `tests/vitest/golden/<name>.json`.
 * With GOLDEN_RECORD=1 it writes the fixture instead, so a record run compares
 * nothing: always run the suite again without the flag.
 */

import { existsSync, readFileSync, writeFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'
import { expect } from 'vitest'

const DIR = dirname(fileURLToPath(import.meta.url))

/**
 * Compare a value with its recorded fixture, or record it.
 *
 * @param {string} name The fixture name, without extension.
 * @param {*} actual The value to compare; it must survive a JSON round trip.
 */
export function golden(name, actual) {
	const file = join(DIR, name + '.json')
	const plain = JSON.parse(JSON.stringify(actual))
	if (process.env.GOLDEN_RECORD === '1') {
		writeFileSync(file, JSON.stringify(plain, null, '\t') + '\n')
		return
	}
	if (!existsSync(file)) {
		throw new Error(
			'No golden fixture ' + file + '; record it with GOLDEN_RECORD=1',
		)
	}
	expect(plain).toEqual(JSON.parse(readFileSync(file, 'utf8')))
}
