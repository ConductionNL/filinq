/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The values the admin settings page shows before the settings response
 * arrives. Each function returns a fresh object, so two pages never share
 * state.
 */

/**
 * The settings form with every field at its default.
 *
 * @return {object} The settings, keyed by app config key.
 *
 * @spec openspec/specs/admin-settings/spec.md
 */
export function defaultSettings() {
	return {
		publication_objection_period_days: 28,
		enable_language_detection: true,
		enable_keyword_extraction: true,
		enable_topic_classification: true,
		enable_contract_term_extraction: true,
		enable_inbound_classification: true,
		ocr_enabled: true,
		ocr_on_arrival: true,
		ocr_dpi: 300,
		signing_enabled: false,
		signing_provider: 'native',
		libresign_qualified: false,
		signing_default_level: 'SES',
		signing_request_expiry_days: 30,
		signing_guardian_consent_age: 16,
		'filinq.anonymisation.default_output_format': 'pdf-only',
		'anonymisation.output_subfolder_name': 'anonymised',
		// files-confidential-labels — off by default (design.md D3).
		'filinq.confidentiality.prioritise_analysis': false,
		'filinq.confidentiality.label_vocabulary': {
			Public: 0,
			Internal: 1,
			Confidential: 2,
			Secret: 3,
		},
	}
}

/**
 * The OCR languages, Dutch and English on.
 *
 * @return {Object<string, boolean>} Whether each language is selected.
 *
 * @spec openspec/specs/admin-settings/spec.md
 */
export function defaultOcrLanguages() {
	return {
		nld: true,
		eng: true,
		deu: false,
		fra: false,
	}
}

/**
 * The OCR engine status before it is read: unavailable.
 *
 * @return {{tesseractAvailable: boolean, tesseractVersion: null}} The status.
 *
 * @spec openspec/specs/admin-settings/spec.md
 */
export function emptyOcrStatus() {
	return {
		tesseractAvailable: false,
		tesseractVersion: null,
	}
}

/**
 * The PDF/A validator status before it is read: enabled, not available.
 *
 * @return {{enabled: boolean, available: boolean, version: string}} The status.
 *
 * @spec openspec/specs/admin-settings/spec.md
 */
export function emptyVeraPdfStatus() {
	return {
		enabled: true,
		available: false,
		version: '',
	}
}
