<?php

/**
 * Policy Rule Text: a policy rule's translatable fields as text.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/entity-publication-policies/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * Reads a rule's translatable fields as text (used by PolicyCrudService).
 *
 * @spec openspec/specs/entity-publication-policies/spec.md
 */
trait PolicyRuleText
{

	/**
	 * The translatable fields of the rule schemas (publicationProhibition,
	 * publicationConsent), read back as text.
	 *
	 * @var string[]
	 */
	private const TRANSLATABLE_FIELDS = ['primaryName', 'reason', 'notes', 'objectionReason', 'publicationDecision'];

	/**
	 * A rule with its translatable fields as text.
	 *
	 * The rules are read through OpenRegister inside the seam, which hands a
	 * translatable field back as its language map (`{"nl": "..."}`) where its
	 * own API gives a string. The rule screens took that map for text: the
	 * edit form failed on `primaryName.trim` and the list showed the map. The
	 * text is the Dutch value, else the English, else the first there is; a
	 * save writes it back under the default language.
	 *
	 * @param array<string, mixed> $row The rule as OpenRegister returned it.
	 *
	 * @return array<string, mixed> The rule with text in its translatable fields.
	 *
	 * @spec openspec/specs/entity-publication-policies/spec.md
	 */
	private static function withText(array $row): array {
		foreach (self::TRANSLATABLE_FIELDS as $field) {
			if (is_array($row[$field] ?? null) === false) {
				continue;
			}

			$row[$field] = self::textOf(map: $row[$field]);
		}

		return $row;
	}//end withText()

	/**
	 * The text of a language map: nl, then en, then the first language.
	 *
	 * @param array<mixed> $map The language map.
	 *
	 * @return string
	 */
	private static function textOf(array $map): string {
		foreach (['nl', 'en'] as $language) {
			if (is_scalar($map[$language] ?? null) === true) {
				return (string)$map[$language];
			}
		}

		foreach ($map as $text) {
			if (is_scalar($text) === true) {
				return (string)$text;
			}
		}

		return '';
	}//end textOf()
}//end trait
