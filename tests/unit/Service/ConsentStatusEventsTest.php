<?php

/**
 * Unit tests for ConsentStatusEvents.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/consent-management/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use DateTimeImmutable;
use InvalidArgumentException;
use OCA\Filinq\Service\ConsentStatusEvents;
use PHPUnit\Framework\TestCase;

/**
 * Consent status moves are declared, evidenced and recorded.
 */
class ConsentStatusEventsTest extends TestCase {

	/**
	 * Apply one change to a pending record.
	 *
	 * @param array<string, mixed> $changes  The change.
	 * @param string               $evidence The evidence.
	 * @param array<string, mixed> $existing The stored record.
	 *
	 * @return array<string, mixed>
	 */
	private function apply(
		array $changes,
		string $evidence = '',
		array $existing = ['consentStatus' => 'pending', 'notificationStatus' => 'sent'],
	): array {
		return (new ConsentStatusEvents())->apply(
			existing: $existing,
			changes: $changes,
			actor: 'ingrid',
			evidence: $evidence,
			now: new DateTimeImmutable('2026-10-21T12:00:00+00:00')
		);

	}//end apply()

	/**
	 * A consent with evidence is recorded as an event with who, when and why.
	 *
	 * @return void
	 */
	public function testAConsentWithEvidenceIsRecorded(): void {
		$result = $this->apply(changes: ['consentStatus' => 'consent_given'], evidence: ' Signed form received by post ');

		$this->assertSame(
			expected: [[
				'field' => 'consentStatus',
				'from' => 'pending',
				'to' => 'consent_given',
				'evidence' => 'Signed form received by post',
				'by' => 'ingrid',
				'at' => '2026-10-21T12:00:00+00:00',
			]],
			actual: $result['statusEvents']
		);

	}//end testAConsentWithEvidenceIsRecorded()

	/**
	 * Consent or an objection without evidence is refused.
	 *
	 * @return void
	 */
	public function testConsentWithoutEvidenceIsRefused(): void {
		$this->expectException(exception: InvalidArgumentException::class);
		$this->expectExceptionCode(code: 400);

		$this->apply(changes: ['consentStatus' => 'objection_received'], evidence: '  ');

	}//end testConsentWithoutEvidenceIsRefused()

	/**
	 * An undeclared move is refused.
	 *
	 * @return void
	 */
	public function testAnUndeclaredMoveIsRefused(): void {
		$this->expectException(exception: InvalidArgumentException::class);
		$this->expectExceptionCode(code: 400);

		$this->apply(changes: ['consentStatus' => 'pending'], existing: ['consentStatus' => 'objection_received']);

	}//end testAnUndeclaredMoveIsRefused()

	/**
	 * Earlier events are kept, an unchanged value adds none, and a notification move needs no evidence.
	 *
	 * @return void
	 */
	public function testEventsAccumulateAndUnchangedValuesAddNone(): void {
		$earlier = [
			'field' => 'notificationStatus',
			'from' => 'pending',
			'to' => 'sent',
			'evidence' => '',
			'by' => 'bob',
			'at' => '2026-10-20T09:00:00+00:00',
		];

		$result = $this->apply(
			changes: ['consentStatus' => 'pending', 'notificationStatus' => 'delivered'],
			existing: ['consentStatus' => 'pending', 'notificationStatus' => 'sent', 'statusEvents' => [$earlier]]
		);

		$this->assertCount(expectedCount: 2, haystack: $result['statusEvents']);
		$this->assertSame(expected: $earlier, actual: $result['statusEvents'][0]);
		$this->assertSame(expected: 'delivered', actual: $result['statusEvents'][1]['to']);
		$this->assertArrayNotHasKey(key: 'statusEvents', array: $this->apply(changes: ['consentStatus' => 'pending']));

	}//end testEventsAccumulateAndUnchangedValuesAddNone()
}//end class
