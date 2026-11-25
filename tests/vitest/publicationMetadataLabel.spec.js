/**
 * The worklist names a missing DiWoo field by its label, not its key.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/woo-metadata-worklist/specs/woo-metadata-worklist/spec.md
 */

import { describe, expect, it } from 'vitest'
import { metadataLabel } from '../../src/services/publicationRecord.js'

const t = (app, text) => `[${text}]`

describe('metadataLabel', () => {
	it('names each of the six DiWoo fields by the label the Set metadata dialog uses', () => {
		expect(
			['officieleTitel', 'wooCategory', 'documentsoort', 'publisher', 'creatiedatum', 'publicatiedatum'].map(
				(field) => metadataLabel(field, t),
			),
		).toEqual([
			'[Official title]',
			'[Information category]',
			'[Document type]',
			'[Publisher]',
			'[Creation date]',
			'[Publication date]',
		])
	})

	it('keeps the key of a field it does not know', () => {
		expect(metadataLabel('somethingNew', t)).toBe('somethingNew')
	})
})
