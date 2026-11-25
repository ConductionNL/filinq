<?php

/**
 * Language map text
 *
 * The text of a translatable field as OpenRegister answers it.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Support;

/**
 * The text of a translatable field: OpenRegister answers a field the
 * register declares translatable as a language map, while a caller that
 * builds a sentence or shows a panel wants one text.
 */
final class LanguageMapText {

	/**
	 * The text of a value: a scalar as itself; a language map in Dutch,
	 * else English, else its first non-empty text; anything else as ''.
	 *
	 * @param mixed $value The stored value.
	 *
	 * @return string The text.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function text(mixed $value): string {
		if (is_scalar($value) === true) {
			return (string) $value;
		}

		if (is_array($value) === false) {
			return '';
		}

		foreach (['nl', 'en'] as $language) {
			if (is_scalar($value[$language] ?? null) === true && (string) $value[$language] !== '') {
				return (string) $value[$language];
			}
		}

		foreach ($value as $candidate) {
			if (is_scalar($candidate) === true && (string) $candidate !== '') {
				return (string) $candidate;
			}
		}

		return '';
	}//end text()
}//end class
