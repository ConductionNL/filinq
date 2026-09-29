<?php

/**
 * Unit tests for ConsentClearance
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Publication
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/woo-publicatie-pipeline/specs/publication-consent/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Publication;

use DateTimeImmutable;
use OCA\Filinq\Service\Publication\ConsentClearance;
use PHPUnit\Framework\TestCase;

/**
 * Every consentStatus x publicationDecision x deadline combination.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Publication
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class ConsentClearanceTest extends TestCase {

	/**
	 * The rule, written out as the spec states it: `reject` always blocks;
	 * `consent_given` and `anonymized` clear; `no_response` clears once the
	 * objection window has closed; an objection clears only when it is
	 * resolved by anonymising; `pending` never clears.
	 *
	 * @param string      $status   consentStatus
	 * @param string      $decision publicationDecision
	 * @param string|null $deadline past, future or null
	 *
	 * @return bool Whether this one record permits publication.
	 */
	private static function expected(string $status, string $decision, ?string $deadline): bool {
		if ($decision === 'reject') {
			return false;
		}

		return match ($status) {
			'consent_given', 'anonymized' => true,
			'no_response' => $deadline === 'past',
			'objection_received' => in_array($decision, ['anonymize', 'publish_anonymized'], true),
			default => false,
		};

	}//end expected()

	/**
	 * All 75 combinations, one record each.
	 *
	 * @return void
	 */
	public function testEveryCombination(): void {
		$now = new DateTimeImmutable('2026-09-29T12:00:00+00:00');
		$deadlines = ['past' => '2026-09-01T00:00:00+00:00', 'future' => '2026-10-27T00:00:00+00:00', null => null];
		$checked = 0;
		foreach (['pending', 'consent_given', 'objection_received', 'no_response', 'anonymized'] as $status) {
			foreach (['pending', 'anonymize', 'publish_with_consent', 'publish_anonymized', 'reject'] as $decision) {
				foreach (['past', 'future', null] as $when) {
					$record = ['id' => 'c-1', 'scope' => 'document', 'consentStatus' => $status, 'publicationDecision' => $decision];
					if ($when !== null) {
						$record['objectionDeadline'] = $deadlines[$when];
					}

					$verdict = (new ConsentClearance())->evaluate(consents: [$record], now: $now);
					$label = $status . '/' . $decision . '/' . ($when ?? 'none');
					$this->assertSame(self::expected($status, $decision, $when), $verdict['clear'], $label);
					$this->assertSame($verdict['clear'], $verdict['reasons'] === [], $label);
					$checked++;
				}
			}
		}

		$this->assertSame(75, $checked);

	}//end testEveryCombination()

	/**
	 * No consent requests is clear; one blocking record blocks them all; a
	 * reason names the record, never the person.
	 *
	 * @return void
	 */
	public function testReasonsNameTheRecordNotThePerson(): void {
		$now = new DateTimeImmutable('2026-09-29T12:00:00+00:00');
		$clearance = new ConsentClearance();

		$this->assertSame(['clear' => true, 'reasons' => []], $clearance->evaluate(consents: [], now: $now));

		$verdict = $clearance->evaluate(
			consents: [
				['id' => 'c-1', 'consentStatus' => 'consent_given', 'publicationDecision' => 'publish_with_consent', 'entityText' => 'Jan Jansen'],
				['id' => 'c-2', 'consentStatus' => 'no_response', 'publicationDecision' => 'pending', 'objectionDeadline' => '2026-10-27T00:00:00+00:00', 'entityText' => 'Piet Pieters'],
				['id' => 'c-3', 'scope' => 'entity', 'consentStatus' => 'pending', 'publicationDecision' => 'reject'],
			],
			now: $now
		);

		$this->assertFalse($verdict['clear']);
		$this->assertCount(1, $verdict['reasons']);
		$this->assertStringContainsString('c-2', $verdict['reasons'][0]);
		$this->assertStringContainsString('2026-10-27', $verdict['reasons'][0]);
		$this->assertStringNotContainsString('Piet', $verdict['reasons'][0]);

	}//end testReasonsNameTheRecordNotThePerson()
}//end class
