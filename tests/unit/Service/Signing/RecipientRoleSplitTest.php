<?php

/**
 * Unit tests for RecipientRole's split of signers and accepters.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-25-signing-accept-only-recipient/tasks.md#2-backend
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Signing;

use OCA\Filinq\Service\Signing\RecipientRole;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Accept-only recipients are found by their role and kept apart from the signers.
 */
#[CoversClass(RecipientRole::class)]
class RecipientRoleSplitTest extends TestCase {

	/**
	 * The accepters are the records with role `accept`, in order; a record
	 * without a role signs.
	 *
	 * @return void
	 */
	public function testTheAcceptersAreTheAcceptOnlyRecords(): void {
		$records = [
			'tenant'   => ['role' => 'sign', 'status' => 'SIGNED'],
			'officer'  => ['role' => 'accept', 'status' => 'ACCEPTED'],
			'landlord' => ['status' => 'SIGNED'],
			'clerk'    => ['role' => 'accept', 'status' => 'ACCEPTED'],
		];

		$this->assertSame(['officer', 'clerk'], RecipientRole::accepterIds(signers: $records));
		$this->assertSame([], RecipientRole::accepterIds(signers: ['a' => ['status' => 'SIGNED']]));
	}//end testTheAcceptersAreTheAcceptOnlyRecords()

	/**
	 * Split keeps the signers' order without the accepters, and lists the
	 * accepters apart; without `accepterIds` everybody signs.
	 *
	 * @return void
	 */
	public function testSplitKeepsAcceptersOutOfTheSigners(): void {
		$this->assertSame(
			['signers' => ['tenant', 'landlord'], 'accepted' => ['officer']],
			RecipientRole::split(request: ['signerIds' => ['tenant', 'officer', 'landlord'], 'accepterIds' => ['officer']])
		);
		$this->assertSame(
			['signers' => ['s1', 's2'], 'accepted' => []],
			RecipientRole::split(request: ['signerIds' => ['s1', 's2']])
		);
	}//end testSplitKeepsAcceptersOutOfTheSigners()
}//end class
