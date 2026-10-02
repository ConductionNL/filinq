<?php

/**
 * SigningEnvelopeRollUp tests
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\SigningEnvelope
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

namespace OCA\Filinq\Tests\Unit\Service\SigningEnvelope;

use OCA\Filinq\Service\SigningEnvelope\SigningEnvelopeRollUp;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The envelope status for a set of member statuses.
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
 */
class SigningEnvelopeRollUpTest extends TestCase {

	/**
	 * Member statuses, stored status, expected roll-up.
	 *
	 * @return array<string, array{0: list<string>, 1: string, 2: string}>
	 */
	public static function cases(): array {
		return [
			'nobody acted' => [['PENDING', 'PENDING', 'PENDING'], 'pending', 'pending'],
			'one signature in' => [['IN_PROGRESS', 'PENDING', 'PENDING'], 'pending', 'in_progress'],
			'two done, one open' => [['COMPLETED', 'COMPLETED', 'PENDING'], 'in_progress', 'in_progress'],
			'all completed' => [['COMPLETED', 'COMPLETED', 'COMPLETED'], 'in_progress', 'completed'],
			'one declined, others signed' => [['COMPLETED', 'DECLINED', 'COMPLETED'], 'in_progress', 'partially_declined'],
			'one declined, others open' => [['PENDING', 'DECLINED', 'IN_PROGRESS'], 'in_progress', 'partially_declined'],
			'cancelled by its sender' => [['CANCELLED', 'DECLINED', 'CANCELLED'], 'cancelled', 'cancelled'],
			'one expired, rest completed' => [['COMPLETED', 'EXPIRED', 'COMPLETED'], 'in_progress', 'incomplete'],
			'member cancelled on its own, one open' => [['CANCELLED', 'PENDING'], 'pending', 'in_progress'],
		];

	}//end cases()

	/**
	 * The roll-up rules.
	 *
	 * @param list<string> $members  The member statuses
	 * @param string       $current  The stored status
	 * @param string       $expected The roll-up
	 *
	 * @return void
	 */
	#[DataProvider('cases')]
	public function testStatusOf(array $members, string $current, string $expected): void {
		$this->assertSame($expected, (new SigningEnvelopeRollUp())->statusOf(memberStatuses: $members, current: $current));

	}//end testStatusOf()
}//end class
