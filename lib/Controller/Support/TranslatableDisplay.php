<?php

/**
 * TranslatableDisplay: a translatable property as the text a person reads.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Controller
 * @package  OCA\Filinq\Controller\Support
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/archive/2026-11-25-document-intake-inbox/specs/document-intake-inbox/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller\Support;

use OCA\Filinq\Service\Support\LanguageMapText;

/**
 * Resolves language-keyed values in rows an answer is about to show.
 *
 * OpenRegister stores a `translatable` property as a map of language to text
 * (`{"nl": "Bezwaarschrift"}`). Its HTTP read path resolves the map, but a
 * service call such as a slug search returns it raw, and a worklist then shows
 * the JSON (seen live on the intake worklist, 25 Nov 2026). This resolves the
 * named fields for display only: the reader's language, then nl, then en,
 * then the first text there is. Nothing is written back.
 *
 * @spec openspec/changes/archive/2026-11-25-document-intake-inbox/specs/document-intake-inbox/spec.md
 */
final class TranslatableDisplay {

	/**
	 * The reader's primary language, such as `en`; empty when the request names none.
	 *
	 * @var string
	 */
	private readonly string $language;

	/**
	 * Constructor.
	 *
	 * @param string $acceptLanguage The request's Accept-Language header, such as `en-US,en;q=0.9`.
	 *
	 * @spec openspec/changes/archive/2026-11-25-document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function __construct(string $acceptLanguage) {
		$first          = trim(explode(';', trim(explode(',', $acceptLanguage)[0]))[0]);
		$this->language = strtolower(explode('-', $first)[0]);
	}//end __construct()

	/**
	 * The rows with each named field resolved to one text.
	 *
	 * @param array<int, mixed>  $rows   The rows to show.
	 * @param array<int, string> $fields The translatable fields.
	 *
	 * @return array<int, mixed> The same rows; a field that is a language map becomes its text.
	 *
	 * @spec openspec/changes/archive/2026-11-25-document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function rows(array $rows, array $fields): array {
		foreach ($rows as $index => $row) {
			if (is_array($row) === false) {
				continue;
			}

			foreach ($fields as $field) {
				if (isset($row[$field]) === true && is_array($row[$field]) === true) {
					$rows[$index][$field] = $this->text(map: $row[$field]);
				}
			}
		}

		return $rows;
	}//end rows()

	/**
	 * The text of one language map.
	 *
	 * @param array<mixed> $map Language code to text.
	 *
	 * @return string The text in the reader's language, else nl, else en, else the first; '' when there is none.
	 *
	 * @spec openspec/changes/archive/2026-11-25-document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function text(array $map): string {
		foreach (array_unique([$this->language, 'nl', 'en']) as $language) {
			if ($language !== '' && isset($map[$language]) === true && is_scalar($map[$language]) === true) {
				return (string) $map[$language];
			}
		}

		return (new LanguageMapText())->text(value: $map);
	}//end text()
}//end class
