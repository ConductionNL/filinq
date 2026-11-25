<?php

/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Signing;

use OCA\Filinq\Service\ObjectBoundMail;
use OCA\Filinq\Service\ObjectBoundMailTarget;
use OCA\Filinq\Service\Signing\SigningMail;
use OCA\Filinq\Service\Signing\SigningTurn;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\Signing\SigningMail
 * @covers \OCA\Filinq\Service\Signing\SigningTurn
 */
class SigningMailTest extends TestCase {

	/** @var ObjectBoundMail&MockObject */
	private ObjectBoundMail $mail;

	private SigningMail $signingMail;

	private ObjectBoundMailTarget $target;

	protected function setUp(): void {
		$this->mail = $this->createMock(ObjectBoundMail::class);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $text, array $p = []): string => vsprintf($text, $p));
		$this->signingMail = new SigningMail(mail: $this->mail, l10n: $l10n, logger: $this->createMock(LoggerInterface::class));
		$this->target = new ObjectBoundMailTarget('req-1', 'filinq', 'signingRequest');
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private static function signers(string $a = 'PENDING', string $b = 'PENDING', string $c = 'PENDING'): array {
		return [
			's-a' => ['status' => $a, 'order' => 0, 'email' => 'a@x.nl', 'displayName' => 'A'],
			's-b' => ['status' => $b, 'order' => 1, 'userId' => 'bert'],
			's-c' => ['status' => $c, 'order' => 2, 'email' => 'c@x.nl'],
		];
	}

	private static function request(string $mode = 'sequential', string $provider = 'native'): array {
		return ['id' => 'req-1', 'signerIds' => ['s-a', 's-b', 's-c'], 'signingMode' => $mode, 'provider' => $provider, 'documentName' => 'Besluit.pdf', 'initiatorUserId' => 'ini'];
	}

	public function testOnlyTheFirstSignerOfASequentialRequestIsMailedAtCreation(): void {
		$this->mail->expects($this->once())->method('toAddress')
			->with('a@x.nl', 'A', $this->stringContains('Besluit.pdf'), $this->stringContains('your turn'), $this->target)
			->willReturn('dispatched');
		$this->mail->expects($this->never())->method('toUser');

		$this->assertSame(['s-a:dispatched'], $this->signingMail->turnStarted(self::request(), self::signers(), $this->target));
	}

	public function testTheNextSignerIsMailedAfterTheFirstSignsAndAnAccountWithoutAddressIsMailedAtItsAccount(): void {
		$this->mail->expects($this->never())->method('toAddress');
		$this->mail->expects($this->once())->method('toUser')
			->with('bert', $this->anything(), $this->anything(), $this->target)
			->willReturn('dispatched');

		$this->assertSame(['s-b:dispatched'], $this->signingMail->turnStarted(self::request(), self::signers('SIGNED'), $this->target, 's-a'));
	}

	public function testEverySignerOfAParallelRequestIsMailedOnceAtCreationAndNotAgain(): void {
		$this->mail->expects($this->exactly(2))->method('toAddress')->willReturn('dispatched');
		$this->mail->expects($this->once())->method('toUser')->willReturn('dispatched');

		$this->assertCount(3, $this->signingMail->turnStarted(self::request('parallel'), self::signers(), $this->target));
		$this->assertSame([], $this->signingMail->turnStarted(self::request('parallel'), self::signers('SIGNED'), $this->target, 's-a'));
	}

	public function testAnExternalProviderMailsItsOwnSigners(): void {
		$this->mail->expects($this->never())->method('toAddress');
		$this->mail->expects($this->never())->method('toUser');

		$this->assertSame([], $this->signingMail->turnStarted(self::request('sequential', 'validsign'), self::signers(), $this->target));
		$this->assertSame('', $this->signingMail->declined(self::request('sequential', 'validsign'), 'A', 'nee', $this->target));
	}

	public function testASignerWithoutAnyAddressOrAFailingSendDoesNotStopTheTurn(): void {
		$signers = self::signers();
		unset($signers['s-a']['email']);
		$this->assertSame(['s-a:no-address'], $this->signingMail->turnStarted(self::request(), $signers, $this->target));

		$this->mail->method('toAddress')->willThrowException(new RuntimeException('down'));
		$this->assertSame(['s-a:failed'], $this->signingMail->turnStarted(self::request(), self::signers(), $this->target));
	}

	public function testTheInitiatorIsMailedTheDeclineAndItsReason(): void {
		$this->mail->expects($this->once())->method('toUser')
			->with('ini', 'A declined to sign Besluit.pdf', $this->stringContains('The reason given: Onjuist bedrag'), $this->target)
			->willReturn('dispatched');

		$this->assertSame('dispatched', $this->signingMail->declined(self::request(), 'A', 'Onjuist bedrag', $this->target));
	}

	public function testADeclineWithoutReasonSaysSoAndOneWithoutInitiatorMailsNobody(): void {
		$this->mail->expects($this->once())->method('toUser')
			->with('ini', $this->anything(), $this->stringContains('No reason was given.'), $this->target)
			->willThrowException(new RuntimeException('down'));

		$this->assertSame('failed', $this->signingMail->declined(self::request(), 'A', ' ', $this->target));
		$this->assertSame('', $this->signingMail->declined(['provider' => 'native'], 'A', 'x', $this->target));
	}

	public function testEnvelopeAndDoneSignersAreNotDueAndEqualOrdersShareATurn(): void {
		$request = self::request();
		$signers = self::signers();
		$signers['s-a']['envelopeRef'] = 'env-1';
		$signers['s-b']['order'] = 0;
		$this->assertSame(['s-b'], SigningTurn::due($request, $signers));

		$this->assertSame(['s-c'], SigningTurn::due($request, self::signers('SIGNED', 'SIGNED'), 's-b'));
		$this->assertSame([], SigningTurn::due($request, self::signers('SIGNED', 'SIGNED', 'SIGNED'), 's-c'));
		$this->assertSame('A', SigningTurn::nameOf('fallback', ['displayName' => 'A']));
		$this->assertSame('fallback', SigningTurn::nameOf('fallback', []));
	}
}
