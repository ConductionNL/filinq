/**
 * The admin settings page starts from these defaults, fresh on every call.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/admin-settings/spec.md
 */

import { describe, expect, it } from 'vitest'
import {
	defaultOcrLanguages,
	defaultSettings,
	emptyOcrStatus,
	emptyVeraPdfStatus,
} from '../../src/services/settingsDefaults.js'

describe('defaultSettings', () => {
	it('lists every field with its default, in order', () => {
		const settings = defaultSettings()
		expect(Object.entries(settings)).toEqual([
			['publication_objection_period_days', 28],
			['enable_language_detection', true],
			['enable_keyword_extraction', true],
			['enable_topic_classification', true],
			['enable_contract_term_extraction', true],
			['enable_inbound_classification', true],
			['ocr_enabled', true],
			['ocr_on_arrival', true],
			['ocr_dpi', 300],
			['signing_enabled', false],
			['signing_provider', 'native'],
			['libresign_qualified', false],
			['signing_default_level', 'SES'],
			['signing_request_expiry_days', 30],
			['signing_guardian_consent_age', 16],
			['filinq.anonymisation.default_output_format', 'pdf-only'],
			['anonymisation.output_subfolder_name', 'anonymised'],
			['filinq.confidentiality.prioritise_analysis', false],
			[
				'filinq.confidentiality.label_vocabulary',
				{ Public: 0, Internal: 1, Confidential: 2, Secret: 3 },
			],
		])
	})

	it('returns a fresh object, vocabulary included', () => {
		const first = defaultSettings()
		const second = defaultSettings()
		expect(first).not.toBe(second)
		expect(first['filinq.confidentiality.label_vocabulary']).not.toBe(
			second['filinq.confidentiality.label_vocabulary'],
		)
	})
})

describe('the OCR and validator defaults', () => {
	it('select Dutch and English', () => {
		expect(defaultOcrLanguages()).toEqual({
			nld: true,
			eng: true,
			deu: false,
			fra: false,
		})
		expect(defaultOcrLanguages()).not.toBe(defaultOcrLanguages())
	})

	it('start with no OCR engine and an enabled, unavailable validator', () => {
		expect(emptyOcrStatus()).toEqual({
			tesseractAvailable: false,
			tesseractVersion: null,
		})
		expect(emptyVeraPdfStatus()).toEqual({
			enabled: true,
			available: false,
			version: '',
		})
	})
})
