<?php

/**
 * Woo Decision Context Builder
 *
 * Checks a Woo decision against the `woo-decision` data contract and gives
 * the templates its grounds in words.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Woo
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Woo;

use DateTimeImmutable;
use OCA\Filinq\Exception\WooDecisionContractException;
use OCA\Filinq\Service\Grounds\RefusalGroundsResolver;

/**
 * Builds the render context of a `woo-decision` template, or refuses it.
 *
 * Nothing is rendered from a decision that fails the contract: a letter that
 * withholds a document without a ground, or that numbers two documents the
 * same, is a decision the organisation cannot defend. Every problem is named
 * in one refusal, so the caller fixes them in one pass.
 *
 * @spec openspec/changes/woo-request-workflow/specs/woo-request-workflow/spec.md#requirement-the-woo-decision-data-contract-is-checked-before-anything-is-rendered-req-ddwrw-010
 */
class WooDecisionContextBuilder {

	/**
	 * The data contract this builder serves.
	 *
	 * @var string
	 */
	public const CONTRACT = 'woo-decision';

	/**
	 * The decision kinds.
	 *
	 * @var array<int, string>
	 */
	public const DECISION_KINDS = ['disclose', 'partially-disclose', 'withhold', 'not-held'];

	/**
	 * The assessments of one document.
	 *
	 * @var array<int, string>
	 */
	public const ASSESSMENTS = ['disclose', 'partially-disclose', 'withhold'];

	/**
	 * The text keys a decision carries.
	 *
	 * @var array<int, string>
	 */
	private const TEXT_KEYS = ['reference', 'subject', 'organisation'];

	/**
	 * The date keys a decision carries.
	 *
	 * @var array<int, string>
	 */
	private const DATE_KEYS = ['receivedAt', 'decisionDate'];

	/**
	 * Constructor.
	 *
	 * @param RefusalGroundsResolver $grounds The refusal grounds.
	 */
	public function __construct(
		private readonly RefusalGroundsResolver $grounds,
	) {
	}//end __construct()

	/**
	 * The checked decision, each document carrying its grounds as {code, article, label}.
	 *
	 * @param mixed $wooDecision         The caller's `data.wooDecision`.
	 * @param bool  $allowRetiredGrounds Whether a retired ground may be cited, to redraft an old decision.
	 *
	 * @return array<string, mixed> The decision for the templates.
	 *
	 * @throws WooDecisionContractException Naming every problem found.
	 *
	 * @spec openspec/changes/woo-request-workflow/specs/woo-request-workflow/spec.md#requirement-the-woo-decision-data-contract-is-checked-before-anything-is-rendered-req-ddwrw-010
	 */
	public function build(mixed $wooDecision, bool $allowRetiredGrounds = false): array {
		if (is_array($wooDecision) === false) {
			throw new WooDecisionContractException(problems: ['data.wooDecision is missing.']);
		}

		$problems = self::topLevelProblems(decision: $wooDecision);

		$documents = ($wooDecision['documents'] ?? null);
		if (is_array($documents) === false || array_is_list($documents) === false) {
			$problems[] = 'documents is missing or is not a list.';
			$documents = [];
		}

		$seen = [];
		$built = [];
		foreach ($documents as $position => $document) {
			$label = 'Document ' . ($position + 1);
			if (is_array($document) === false) {
				$problems[] = $label . ' is not an object.';
				continue;
			}

			$number = trim((string)($document['inventoryNumber'] ?? ''));
			if ($number !== '') {
				$label = 'Document ' . $number;
				if (isset($seen[$number]) === true) {
					$problems[] = 'Inventory number ' . $number . ' is used by more than one document.';
				}

				$seen[$number] = true;
			}

			[$document, $documentProblems] = $this->document(label: $label, document: $document, allowRetiredGrounds: $allowRetiredGrounds);
			$problems = array_merge($problems, $documentProblems);
			$built[] = $document;
		}//end foreach

		if ($problems !== []) {
			throw new WooDecisionContractException(problems: array_values(array_unique($problems)));
		}

		$wooDecision['documents'] = $built;

		return $wooDecision;
	}//end build()

	/**
	 * The problems with the decision's own keys.
	 *
	 * @param array<string, mixed> $decision The decision.
	 *
	 * @return array<int, string> The problems.
	 */
	private static function topLevelProblems(array $decision): array {
		$problems = [];
		foreach (self::TEXT_KEYS as $key) {
			if (is_string($decision[$key] ?? null) === false || trim($decision[$key]) === '') {
				$problems[] = $key . ' is missing.';
			}
		}

		foreach (self::DATE_KEYS as $key) {
			if (self::isDate(value: ($decision[$key] ?? null)) === false) {
				$problems[] = $key . ' is missing or is not a date.';
			}
		}

		if (in_array(($decision['decisionKind'] ?? null), self::DECISION_KINDS, true) === false) {
			$problems[] = 'decisionKind must be one of ' . implode(', ', self::DECISION_KINDS) . '.';
		}

		return $problems;
	}//end topLevelProblems()

	/**
	 * One document checked, its grounds resolved.
	 *
	 * @param string               $label               How the problems name it.
	 * @param array<string, mixed> $document            The document.
	 * @param bool                 $allowRetiredGrounds Whether a retired ground may be cited.
	 *
	 * @return array{0: array<string, mixed>, 1: array<int, string>} The document and its problems.
	 *
	 * @spec openspec/changes/woo-request-workflow/specs/woo-request-workflow/spec.md#requirement-grounds-are-printed-by-label-and-article-never-as-a-bare-code-req-ddwrw-011
	 */
	private function document(string $label, array $document, bool $allowRetiredGrounds): array {
		$problems = [];
		if (trim((string)($document['inventoryNumber'] ?? '')) === '') {
			$problems[] = $label . ' has no inventory number.';
		}

		if (trim((string)($document['title'] ?? '')) === '') {
			$problems[] = $label . ' has no title.';
		}

		$assessment = ($document['assessment'] ?? null);
		if (in_array($assessment, self::ASSESSMENTS, true) === false) {
			$problems[] = $label . ' has assessment ' . json_encode($assessment) . '; it must be one of ' . implode(', ', self::ASSESSMENTS) . '.';
		}

		$codes = array_map(static fn (mixed $code): string => trim((string)$code), (array)($document['groundCodes'] ?? []));
		$codes = array_values(array_filter($codes, static fn (string $code): bool => $code !== ''));
		if ($codes === [] && in_array($assessment, ['partially-disclose', 'withhold'], true) === true) {
			$problems[] = $label . ' is assessed ' . $assessment . ' but cites no ground.';
		}

		$document['grounds'] = [];
		foreach ($codes as $code) {
			$ground = $this->grounds->byCode(code: $code);
			if ($ground === null) {
				$problems[] = $label . ' cites ground ' . $code . ', which is not in the list of refusal grounds.';
				continue;
			}

			if (($ground['status'] ?? 'active') !== 'active' && $allowRetiredGrounds === false) {
				$problems[] = $label . ' cites ground ' . $code . ', which is retired; set allowRetiredGrounds to redraft an old decision.';
				continue;
			}

			$document['grounds'][] = [
				'code' => $code,
				'article' => (string)($ground['article'] ?? ''),
				'label' => (string)($ground['label'] ?? ''),
			];
		}

		return [$document, $problems];
	}//end document()

	/**
	 * Whether a value is a date (`Y-m-d`, optionally with a time).
	 *
	 * @param mixed $value The value.
	 *
	 * @return bool True when it is.
	 */
	private static function isDate(mixed $value): bool {
		if (is_string($value) === false || preg_match('/^\d{4}-\d{2}-\d{2}/', $value) !== 1) {
			return false;
		}

		return DateTimeImmutable::createFromFormat('!Y-m-d', substr($value, 0, 10)) !== false
			&& DateTimeImmutable::createFromFormat('!Y-m-d', substr($value, 0, 10))->format('Y-m-d') === substr($value, 0, 10);
	}//end isDate()
}//end class
