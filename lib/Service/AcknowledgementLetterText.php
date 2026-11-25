<?php

/**
 * Acknowledgement Letter Text: the arriving document's fields as an acknowledgement letter names them.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/intake-acknowledge-and-stamp
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use Throwable;

/**
 * Text, a date and a mail body for the acknowledgement of an arriving document.
 *
 * @spec openspec/changes/intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-a-matching-rule-acknowledges-receipt-to-the-sender-req-ias-001
 */
final class AcknowledgementLetterText
{
	/**
	 * A field as text. `subject` is translatable, so the saved object holds a
	 * language map (`{"nl": "..."}`) where the API shows a string: read nl,
	 * then en, then the first language there is.
	 *
	 * @param mixed $value A string or a language map.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-a-matching-rule-acknowledges-receipt-to-the-sender-req-ias-001
	 */
	public static function text(mixed $value): string {
		if (is_array($value) === false) {
			if (is_scalar($value) === true) {
				return (string)$value;
			}

			return '';
		}

		foreach (['nl', 'en'] as $language) {
			if (is_scalar($value[$language] ?? null) === true) {
				return (string)$value[$language];
			}
		}

		foreach ($value as $text) {
			if (is_scalar($text) === true) {
				return (string)$text;
			}
		}

		return '';
	}//end text()

	/**
	 * The received date as the letter names it: day-month-year, as on the
	 * stamp. A value that is not a date is passed on as it is.
	 *
	 * @param string $receivedAt The document's receivedAt.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-a-matching-rule-acknowledges-receipt-to-the-sender-req-ias-001
	 */
	public static function letterDate(string $receivedAt): string {
		if (trim($receivedAt) === '') {
			return '';
		}

		try {
			return (new DateTimeImmutable($receivedAt))->format('d-m-Y');
		} catch (Throwable) {
			return $receivedAt;
		}
	}//end letterDate()

	/**
	 * The rendered letter as a plain-text mail body. OpenRegister's mail
	 * content is plain text, so HTML would arrive as visible tags: paragraphs
	 * and line breaks become line breaks, the tags go and entities are decoded.
	 *
	 * @param string $html The rendered template.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-a-matching-rule-acknowledges-receipt-to-the-sender-req-ias-001
	 */
	public static function plainText(string $html): string {
		$text = preg_replace('~<br\s*/?>~i', "\n", $html) ?? $html;
		$text = preg_replace('~</(p|div|h[1-6]|li|tr)>~i', "\n\n", $text) ?? $text;
		$text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$lines = array_map(static fn (string $line): string => trim($line), explode("\n", $text));
		$text = preg_replace("~\n{3,}~", "\n\n", implode("\n", $lines)) ?? '';

		return trim($text);
	}//end plainText()
}//end class
