<?php

/**
 * Filinq ExtractionVerdictEvent
 *
 * Public cross-app event Filinq dispatches after
 * `nl.conduction.filinq.extraction.completed` when the caller asked for a
 * callback: `nl.conduction.filinq.extraction.verdict`, whether the extraction
 * can be trusted without a person and why not. A sibling event, so the
 * completed event keeps exactly the keys REQ-FIN-05 lists (design D4), the
 * same way `gl-account.suggested` is a sibling.
 *
 * @category  Event
 * @package   OCA\Filinq\Event
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/extraction-receipt-photo-and-invoice-agent/specs/financial-document-field-extraction/spec.md#requirement-the-verdict-is-sent-as-a-sibling-event-req-erp-004
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Event;

use OCP\EventDispatcher\Event;

/**
 * Cross-app event: the unattended verdict of one financial extraction.
 * Immutable; consumers only read.
 */
class ExtractionVerdictEvent extends Event {

	/**
	 * The event name consumers listen on.
	 */
	public const NAME = 'nl.conduction.filinq.extraction.verdict';

	/**
	 * Constructor.
	 *
	 * @param string             $extractionId The saved financialExtraction id.
	 * @param string             $documentUri  The document that was read.
	 * @param string             $sourceApp    The app that asked for the extraction.
	 * @param string             $docType      `receipt` or `supplier-invoice`.
	 * @param UnattendedOutcome  $outcome      The verdict, its reasons and the threshold used.
	 *
	 * @spec openspec/changes/extraction-receipt-photo-and-invoice-agent/specs/financial-document-field-extraction/spec.md#requirement-the-verdict-is-sent-as-a-sibling-event-req-erp-004
	 */
	public function __construct(
		private readonly string $extractionId,
		private readonly string $documentUri,
		private readonly string $sourceApp,
		private readonly string $docType,
		private readonly UnattendedOutcome $outcome,
	) {
		parent::__construct();
	}//end __construct()

	/**
	 * The payload consumers read.
	 *
	 * @return array<string, mixed> extractionId, documentUri, sourceApp, docType, verdict, reasons and threshold.
	 *
	 * @spec openspec/changes/extraction-receipt-photo-and-invoice-agent/specs/financial-document-field-extraction/spec.md#requirement-the-verdict-is-sent-as-a-sibling-event-req-erp-004
	 */
	public function toPayload(): array {
		return [
			'extractionId' => $this->extractionId,
			'documentUri' => $this->documentUri,
			'sourceApp' => $this->sourceApp,
			'docType' => $this->docType,
			'verdict' => $this->outcome->verdict,
			'reasons' => $this->outcome->reasons,
			'threshold' => $this->outcome->threshold,
		];
	}//end toPayload()
}//end class
