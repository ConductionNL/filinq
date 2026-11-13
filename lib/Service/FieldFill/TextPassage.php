<?php

/**
 * Text passage
 *
 * Where a suggested value occurs in the document text, and the passage around it.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\FieldFill
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\FieldFill;

/**
 * Finds a value in the document text and cuts the passage around it.
 *
 * @category Service
 * @package  OCA\Filinq\Service\FieldFill
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 */
class TextPassage {

	/**
	 * The longest passage kept, in characters (design D3).
	 *
	 * @var int
	 */
	public const MAX_LENGTH = 200;

	/**
	 * The passage of the text the value occurs in, or null when it does not occur literally.
	 *
	 * The match ignores case and runs of white space, so a value split over a
	 * line break in the PDF text is still found. A value that is not a string
	 * or a number, or is shorter than two characters, is never "found": a
	 * single character occurs in almost any text.
	 *
	 * @param string $text  The document text.
	 * @param mixed  $value The suggested value.
	 *
	 * @return string|null The passage, at most {@see self::MAX_LENGTH} characters.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	public function around(string $text, mixed $value): ?string {
		if (is_string($value) === false && is_int($value) === false && is_float($value) === false) {
			return null;
		}

		$needle = trim((string)preg_replace('/\s+/u', ' ', (string)$value));
		if (mb_strlen($needle) < 2) {
			return null;
		}

		$haystack = (string)preg_replace('/\s+/u', ' ', $text);
		$offset   = mb_stripos($haystack, $needle);
		if ($offset === false) {
			return null;
		}

		$length = mb_strlen($needle);
		if ($length >= self::MAX_LENGTH) {
			return mb_substr($haystack, $offset, self::MAX_LENGTH);
		}

		$margin = intdiv(self::MAX_LENGTH - $length, 2);
		$start  = max(0, $offset - $margin);

		return trim(mb_substr($haystack, $start, self::MAX_LENGTH));

	}//end around()
}//end class
