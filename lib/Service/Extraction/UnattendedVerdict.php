<?php

/**
 * Unattended Verdict
 *
 * Whether a financial extraction can be trusted without a person (`pass`) or
 * must be looked at (`review`), with one reason code per failed check. Computed
 * once, at extraction time, and stored with the threshold it used (design D3).
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Extraction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/extraction-receipt-photo-and-invoice-agent/specs/financial-document-field-extraction/spec.md#requirement-each-extraction-carries-an-unattended-verdict-with-its-reasons-req-erp-003
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Extraction;

use OCA\Filinq\Event\UnattendedOutcome;
use OCP\IAppConfig;

/**
 * Judges one extraction against the admin's confidence threshold.
 */
class UnattendedVerdict {

	public const SETTING_KEY = 'extraction_unattended_threshold';

	public const DEFAULT_THRESHOLD = 0.9;

	/**
	 * The pipeline's own low-confidence line: a field the model may still
	 * overwrite can never pass (design D5).
	 */
	public const MIN_THRESHOLD = 0.6;

	public const MAX_THRESHOLD = 1.0;

	public const REASON_MISSING = 'missing-field';

	public const REASON_LOW_CONFIDENCE = 'low-confidence';

	public const REASON_NOT_RECONCILED = 'not-reconciled';

	public const REASON_MODEL_ONLY = 'model-only';

	public const REASON_DUPLICATE = 'possible-duplicate';

	/**
	 * Fields a pass needs besides one supplier identifier.
	 */
	private const REQUIRED = ['supplierName', 'issueDate', 'totalIncl'];

	/**
	 * Constructor.
	 *
	 * @param IAppConfig                $config     Holds the threshold setting.
	 * @param ExtractionDuplicateLookup $duplicates Finds an earlier extraction of the same invoice.
	 */
	public function __construct(
		private readonly IAppConfig $config,
		private readonly ExtractionDuplicateLookup $duplicates,
	) {
	}//end __construct()

	/**
	 * The threshold in force: the setting when it is a number from 0.6 to 1.0, else 0.9.
	 *
	 * @return float The threshold.
	 *
	 * @spec openspec/changes/extraction-receipt-photo-and-invoice-agent/specs/financial-document-field-extraction/spec.md#requirement-each-extraction-carries-an-unattended-verdict-with-its-reasons-req-erp-003
	 */
	public function threshold(): float {
		$raw = trim($this->config->getValueString('filinq', self::SETTING_KEY, ''));
		if (self::isValidThreshold(value: $raw) === false) {
			return self::DEFAULT_THRESHOLD;
		}

		return (float)$raw;
	}//end threshold()

	/**
	 * Whether a value may be stored as the threshold.
	 *
	 * @param mixed $value The value.
	 *
	 * @return bool True for a number from 0.6 to 1.0.
	 *
	 * @spec openspec/changes/extraction-receipt-photo-and-invoice-agent/specs/financial-document-field-extraction/spec.md#requirement-an-admin-sets-the-confidence-a-pass-needs-req-erp-005
	 */
	public static function isValidThreshold(mixed $value): bool {
		if (is_numeric($value) === false) {
			return false;
		}

		$number = (float)$value;

		return $number >= self::MIN_THRESHOLD && $number <= self::MAX_THRESHOLD;
	}//end isValidThreshold()

	/**
	 * Judge one extraction with the threshold in force and the duplicate check.
	 *
	 * @param array<string, mixed> $result      The pipeline result: fields, fieldConfidence and reconciled.
	 * @param list<string>         $modelFilled Fields the model filled that the heuristics left empty.
	 *
	 * @return UnattendedOutcome The verdict.
	 *
	 * @spec openspec/changes/extraction-receipt-photo-and-invoice-agent/specs/financial-document-field-extraction/spec.md#requirement-each-extraction-carries-an-unattended-verdict-with-its-reasons-req-erp-003
	 */
	public function judge(array $result, array $modelFilled): UnattendedOutcome {
		return self::decide(
			fields: $result['fields'],
			confidence: $result['fieldConfidence'],
			reconciled: $result['reconciled'],
			modelFilled: $modelFilled,
			duplicate: $this->duplicates->hasEarlier(fields: $result['fields']),
			threshold: $this->threshold(),
		);
	}//end judge()

	/**
	 * The verdict for given facts. Pure.
	 *
	 * @param array<string, mixed> $fields      The extracted fields.
	 * @param array<string, float> $confidence  The confidence per field.
	 * @param bool                 $reconciled  Whether the totals reconciled.
	 * @param list<string>         $modelFilled Fields filled by the model alone.
	 * @param bool                 $duplicate   Whether an earlier extraction is the same invoice.
	 * @param float                $threshold   The confidence a field needs.
	 *
	 * @return UnattendedOutcome The verdict.
	 *
	 * @spec openspec/changes/extraction-receipt-photo-and-invoice-agent/specs/financial-document-field-extraction/spec.md#requirement-each-extraction-carries-an-unattended-verdict-with-its-reasons-req-erp-003
	 */
	public static function decide(
		array $fields,
		array $confidence,
		bool $reconciled,
		array $modelFilled,
		bool $duplicate,
		float $threshold,
	): UnattendedOutcome {
		$reasons = [];

		$checked = self::checkedFields(fields: $fields, confidence: $confidence);
		if ($checked === null) {
			$reasons[] = self::REASON_MISSING;
		}

		foreach (($checked ?? self::presentRequired(fields: $fields)) as $field) {
			if ((float)($confidence[$field] ?? 0.0) < $threshold) {
				$reasons[] = self::REASON_LOW_CONFIDENCE;
				break;
			}
		}

		if ($reconciled === false) {
			$reasons[] = self::REASON_NOT_RECONCILED;
		}

		if ($modelFilled !== []) {
			$reasons[] = self::REASON_MODEL_ONLY;
		}

		if ($duplicate === true) {
			$reasons[] = self::REASON_DUPLICATE;
		}

		$verdict = UnattendedOutcome::PASS;
		if ($reasons !== []) {
			$verdict = UnattendedOutcome::REVIEW;
		}

		return new UnattendedOutcome(verdict: $verdict, reasons: $reasons, threshold: $threshold);
	}//end decide()

	/**
	 * The fields a pass is judged on: the required ones and the best-read
	 * supplier identifier, or null when one of them is missing.
	 *
	 * @param array<string, mixed> $fields     The extracted fields.
	 * @param array<string, float> $confidence The confidence per field.
	 *
	 * @return list<string>|null The fields, or null when one is missing.
	 */
	private static function checkedFields(array $fields, array $confidence): ?array {
		$present = self::presentRequired(fields: $fields);
		if (count($present) !== count(self::REQUIRED)) {
			return null;
		}

		$best = null;
		foreach (ExtractionDuplicateLookup::IDENTIFIERS as $field) {
			if (self::isPresent(value: ($fields[$field] ?? null)) === false) {
				continue;
			}

			if ($best === null || (float)($confidence[$field] ?? 0.0) > (float)($confidence[$best] ?? 0.0)) {
				$best = $field;
			}
		}

		if ($best === null) {
			return null;
		}

		return [...$present, $best];
	}//end checkedFields()

	/**
	 * The required fields that are present.
	 *
	 * @param array<string, mixed> $fields The extracted fields.
	 *
	 * @return list<string>
	 */
	private static function presentRequired(array $fields): array {
		return array_values(
			array_filter(
				self::REQUIRED,
				static fn (string $field): bool => self::isPresent(value: ($fields[$field] ?? null))
			)
		);
	}//end presentRequired()

	/**
	 * Whether a field holds a value.
	 *
	 * @param mixed $value The value.
	 *
	 * @return bool
	 */
	private static function isPresent(mixed $value): bool {
		return $value !== null && $value !== '' && $value !== [];
	}//end isPresent()
}//end class
