<?php

/**
 * Unit tests for GlAccountSuggestedEvent
 *
 * Covers REQ-GLS-06: the sibling event's payload shape, distinct from and
 * unaffected by `nl.conduction.docudesk.extraction.completed`.
 *
 * @category  Tests
 * @package   OCA\DocuDesk\Tests\Unit\Event
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/ai-gl-account-suggestion/specs/ai-gl-account-suggestion/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Event;

use OCA\DocuDesk\Event\GlAccountSuggestedEvent;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the sibling GL-account suggestion event.
 */
class GlAccountSuggestedEventTest extends TestCase {

	/**
	 * @return void
	 */
	public function testGettersReturnConstructedValues(): void {
		$suggestions = [
			['code' => '4300', 'label' => 'Kantoorkosten', 'confidence' => 0.8, 'rationale' => 'Booked to 4300 in 8 of the last 10 invoices from this supplier'],
		];

		$event = new GlAccountSuggestedEvent(
			extractionId: 'extraction-1',
			supplierIdentity: '12345678',
			identityType: 'kvk',
			suggestedAccounts: $suggestions,
			source: 'history',
			sourceApp: 'shillinq',
			requestedBy: 'annemarie',
		);

		$this->assertSame('extraction-1', $event->getExtractionId());
		$this->assertSame('12345678', $event->getSupplierIdentity());
		$this->assertSame('kvk', $event->getIdentityType());
		$this->assertSame($suggestions, $event->getSuggestedAccounts());
		$this->assertSame('history', $event->getSource());
		$this->assertSame('shillinq', $event->getSourceApp());
		$this->assertSame('annemarie', $event->getRequestedBy());

	}//end testGettersReturnConstructedValues()

	/**
	 * @return void
	 */
	public function testToPayloadShapeIsCanonical(): void {
		$event = new GlAccountSuggestedEvent(
			extractionId: 'extraction-4',
			supplierIdentity: 'lunchroom de hoek',
			identityType: 'name',
			suggestedAccounts: [],
			source: 'none',
			sourceApp: 'shillinq',
			requestedBy: 'annemarie',
		);

		$payload = $event->toPayload();

		$this->assertSame(
			['extractionId', 'supplierIdentity', 'identityType', 'suggestedAccounts', 'source', 'sourceApp', 'requestedBy'],
			array_keys($payload)
		);
		$this->assertSame('extraction-4', $payload['extractionId']);
		$this->assertSame([], $payload['suggestedAccounts']);
		$this->assertSame('none', $payload['source']);

	}//end testToPayloadShapeIsCanonical()

	/**
	 * @return void
	 */
	public function testUnresolvableIdentityIsNullInPayload(): void {
		$event = new GlAccountSuggestedEvent(
			extractionId: 'extraction-5',
			supplierIdentity: null,
			identityType: null,
			suggestedAccounts: [],
			source: 'none',
			sourceApp: 'shillinq',
			requestedBy: 'annemarie',
		);

		$payload = $event->toPayload();

		$this->assertNull($payload['supplierIdentity']);
		$this->assertNull($payload['identityType']);

	}//end testUnresolvableIdentityIsNullInPayload()
}//end class
