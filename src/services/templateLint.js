/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The accessibility lint of a template preview, as sentences an author can
 * act on: what is wrong and where.
 *
 * @spec openspec/changes/pdfua-accessible-output/tasks.md#task-3.3
 */

import { translate as t } from '@nextcloud/l10n'

/**
 * One lint finding as a sentence.
 *
 * @param {object} item A lint finding: rule, position, text, and from/to for a jump.
 * @return {string} The sentence.
 * @spec openspec/changes/pdfua-accessible-output/tasks.md#task-3.3
 */
export function lintMessage(item) {
	switch (item.rule) {
		case 'image-missing-alt':
			return t('filinq', 'Image {position} ({name}) has no alternative text.', {
				position: item.position,
				name: item.text || '?',
			})
		case 'heading-order-jump':
			return t('filinq', 'Heading "{text}" is {to} straight after {from}: a level is skipped.', {
				text: item.text,
				from: item.from,
				to: item.to,
			})
		case 'table-without-headers':
			return t('filinq', 'Table {position} (starting with "{text}") has no header cells.', {
				position: item.position,
				text: item.text,
			})
		case 'language-unresolved':
			return t('filinq', 'No language is set for this template or for this Nextcloud, so an accessible PDF cannot say what language it is in.')
		default:
			return item.rule
	}
}
