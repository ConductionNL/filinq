// SPDX-License-Identifier: EUPL-1.2
// Copyright (C) 2026 Conduction B.V.
//
// The match mode and active badges a custom dictionary shows, on the index
// and on the detail page.

import { translate as t } from '@nextcloud/l10n'

export const customDictionaryBadges = {
	data() {
		return {
			matchModeColorMap: {
				exact: 'error',
				caseInsensitive: 'primary',
				wordBoundary: 'success',
			},
			activeColorMap: {
				[t('filinq', 'Active')]: 'success',
				[t('filinq', 'Inactive')]: 'default',
			},
		}
	},

	methods: {
		/**
		 * Translated label for a dictionary match mode, falling back to the raw
		 * value and finally to the schema default.
		 *
		 * @param {string} mode Stored match mode.
		 * @return {string} Displayable label.
		 * @spec openspec/specs/custom-dictionary-recognition/spec.md#requirement-custom-dictionary-admin-ui-req-ddcdr-006
		 */
		matchModeLabel(mode) {
			const labels = {
				exact: t('filinq', 'Exact'),
				caseInsensitive: t('filinq', 'Case-insensitive'),
				wordBoundary: t('filinq', 'Word boundary'),
			}
			return labels[mode] || mode || t('filinq', 'Case-insensitive')
		},

		/**
		 * Translated active state of a dictionary: only an explicit `false`
		 * reads as inactive.
		 *
		 * @param {object} dictionary The dictionary.
		 * @return {string} Active or Inactive.
		 * @spec openspec/specs/custom-dictionary-recognition/spec.md#requirement-custom-dictionary-admin-ui-req-ddcdr-006
		 */
		activeLabel(dictionary) {
			return dictionary.active === false
				? t('filinq', 'Inactive')
				: t('filinq', 'Active')
		},
	},
}
