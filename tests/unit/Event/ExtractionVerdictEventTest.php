<?php

/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Event;

use OCA\Filinq\Event\ExtractionVerdictEvent;
use OCA\Filinq\Event\UnattendedOutcome;
use PHPUnit\Framework\TestCase;

/**
 * REQ-ERP-004: the verdict event's payload.
 *
 * @spec openspec/changes/extraction-receipt-photo-and-invoice-agent/tasks.md#3.2
 */
class ExtractionVerdictEventTest extends TestCase {

	public function testThePayloadCarriesExactlyTheListedKeys(): void {
		$event = new ExtractionVerdictEvent(
			extractionId: 'extraction-2',
			documentUri: 'nc://file/7',
			sourceApp: 'shillinq',
			docType: 'supplier-invoice',
			outcome: new UnattendedOutcome(verdict: 'review', reasons: ['possible-duplicate'], threshold: 0.9),
		);

		$this->assertSame(
			[
				'extractionId' => 'extraction-2',
				'documentUri' => 'nc://file/7',
				'sourceApp' => 'shillinq',
				'docType' => 'supplier-invoice',
				'verdict' => 'review',
				'reasons' => ['possible-duplicate'],
				'threshold' => 0.9,
			],
			$event->toPayload()
		);
		$this->assertSame('nl.conduction.filinq.extraction.verdict', ExtractionVerdictEvent::NAME);
	}
}
