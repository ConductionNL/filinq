<?php

/**
 * Unit tests for SigningTurn.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\Signing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Signing;

use OCA\Filinq\Service\Signing\SigningTurn;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Whose turn it is on a sequential signing request.
 *
 * @covers \OCA\Filinq\Service\Signing\SigningTurn
 */
final class SigningTurnTest extends TestCase {

	private const REQUEST = ['signerIds' => ['a', 'b', 'c']];

	/**
	 * The refusal for signing as the given signer, or null when allowed.
	 *
	 * @param array<string, mixed>                $request The request.
	 * @param string                              $signerId The signer.
	 * @param array<string, array<string, mixed>> $signers The records.
	 *
	 * @return string|null The refusal.
	 */
	private static function refusal(array $request, string $signerId, array $signers): ?string {
		try {
			SigningTurn::assertTurn(request: $request, signerId: $signerId, signers: $signers);
			return null;
		} catch (RuntimeException $refusal) {
			return $refusal->getMessage();
		}
	}//end refusal()

	public function testEveryEarlierUnsignedSignerIsNamed(): void {
		self::assertSame(
			"It is not this signer's turn yet: this request is signed in order, and Ann, b@example.org must sign first.",
			self::refusal(self::REQUEST, 'c', [
				'a' => ['status' => 'PENDING', 'displayName' => 'Ann'],
				'b' => ['status' => 'DECLINED', 'displayName' => ' ', 'email' => 'b@example.org'],
				'c' => ['status' => 'PENDING'],
			])
		);
	}//end testEveryEarlierUnsignedSignerIsNamed()

	public function testAnUnreadableEarlierSignerHoldsTheTurnByItsId(): void {
		self::assertStringEndsWith('and a must sign first.', (string)self::refusal(self::REQUEST, 'b', ['a' => ['status' => 'UNREADABLE'], 'b' => []]));
	}//end testAnUnreadableEarlierSignerHoldsTheTurnByItsId()

	public function testTheFirstSignerAndASignerAfterSignedOnesMaySign(): void {
		self::assertNull(self::refusal(self::REQUEST, 'a', ['a' => [], 'b' => ['status' => 'PENDING']]));
		self::assertNull(self::refusal(self::REQUEST, 'c', ['a' => ['status' => 'SIGNED'], 'b' => ['status' => 'SIGNED'], 'c' => []]));
	}//end testTheFirstSignerAndASignerAfterSignedOnesMaySign()

	public function testTheOrderFieldOutranksTheListPosition(): void {
		$signers = ['a' => ['order' => '3', 'status' => 'PENDING'], 'b' => ['order' => 1], 'c' => ['order' => 'later', 'status' => 'PENDING']];

		self::assertNull(self::refusal(self::REQUEST, 'b', $signers));
		self::assertStringEndsWith('and b, c must sign first.', (string)self::refusal(self::REQUEST, 'a', $signers));
	}//end testTheOrderFieldOutranksTheListPosition()

	public function testParallelModeAndAnUnlistedSignerAreNotJudged(): void {
		self::assertNull(self::refusal(['signingMode' => 'parallel'] + self::REQUEST, 'c', ['a' => ['status' => 'PENDING'], 'c' => []]));
		self::assertNull(self::refusal(self::REQUEST, 'z', ['a' => ['status' => 'PENDING']]));
		self::assertNull(self::refusal(self::REQUEST, 'c', ['c' => []]));
	}//end testParallelModeAndAnUnlistedSignerAreNotJudged()
}//end class
