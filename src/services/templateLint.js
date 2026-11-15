/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The accessibility lint of a template preview, as sentences an author can
 * act on: what is wrong and where.
 *
 * @spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-3.3
 */

import { translate as t } from '@nextcloud/l10n'

/**
 * The sentence per lint rule; a rule without one is shown by its name.
 */
const LINT_MESSAGES = new Map([
	['image-missing-alt', imageMissingAlt],
	['heading-order-jump', headingOrderJump],
	['table-without-headers', tableWithoutHeaders],
	['language-unresolved', languageUnresolved],
])

/**
 * An image without alternative text.
 *
 * @param {object} item The finding: position and the image's name.
 * @return {string} The sentence.
 */
function imageMissingAlt(item) {
	return t(
		'filinq',
		'Image {position} ({name}) has no alternative text.',
		{
			position: item.position,
			name: item.text || '?',
		},
	)
}

/**
 * A heading that skips a level.
 *
 * @param {object} item The finding: the heading's text and the from/to levels.
 * @return {string} The sentence.
 */
function headingOrderJump(item) {
	return t(
		'filinq',
		'Heading "{text}" is {to} straight after {from}: a level is skipped.',
		{
			text: item.text,
			from: item.from,
			to: item.to,
		},
	)
}

/**
 * A table without header cells.
 *
 * @param {object} item The finding: position and the table's first text.
 * @return {string} The sentence.
 */
function tableWithoutHeaders(item) {
	return t(
		'filinq',
		'Table {position} (starting with "{text}") has no header cells.',
		{
			position: item.position,
			text: item.text,
		},
	)
}

/**
 * No language for the template or the instance.
 *
 * @return {string} The sentence.
 */
function languageUnresolved() {
	return t(
		'filinq',
		'No language is set for this template or for this Nextcloud, so an accessible PDF cannot say what language it is in.',
	)
}

/**
 * One lint finding as a sentence.
 *
 * @param {object} item A lint finding: rule, position, text, and from/to for a jump.
 * @return {string} The sentence.
 * @spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-3.3
 */
export function lintMessage(item) {
	const message = LINT_MESSAGES.get(item.rule)
	return message ? message(item) : item.rule
}
