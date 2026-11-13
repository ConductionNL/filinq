<?php

/**
 * Filinq UnattendedOutcome
 *
 * The unattended verdict of one extraction: `pass` or `review`, the reason
 * codes of every failed check, and the threshold it was judged against.
 *
 * @category  Event
 * @package   OCA\Filinq\Event
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

namespace OCA\Filinq\Event;

/**
 * One verdict, as stored and as sent.
 */
final class UnattendedOutcome {

	public const PASS = 'pass';

	public const REVIEW = 'review';

	/**
	 * Constructor.
	 *
	 * @param string       $verdict   `pass` or `review`.
	 * @param list<string> $reasons   The reason codes; empty for `pass`.
	 * @param float        $threshold The confidence threshold used.
	 *
	 * @spec openspec/changes/extraction-receipt-photo-and-invoice-agent/specs/financial-document-field-extraction/spec.md#requirement-each-extraction-carries-an-unattended-verdict-with-its-reasons-req-erp-003
	 */
	public function __construct(
		public readonly string $verdict,
		public readonly array $reasons,
		public readonly float $threshold,
	) {
	}//end __construct()

	/**
	 * The fields stored on the financialExtraction.
	 *
	 * @return array{unattendedVerdict: string, verdictReasons: list<string>, verdictThreshold: float}
	 *
	 * @spec openspec/changes/extraction-receipt-photo-and-invoice-agent/specs/financial-document-field-extraction/spec.md#requirement-each-extraction-carries-an-unattended-verdict-with-its-reasons-req-erp-003
	 */
	public function toStored(): array {
		return [
			'unattendedVerdict' => $this->verdict,
			'verdictReasons' => $this->reasons,
			'verdictThreshold' => $this->threshold,
		];
	}//end toStored()
}//end class
