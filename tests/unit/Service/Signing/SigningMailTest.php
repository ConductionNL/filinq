<?php

/**
 * Unit tests for SigningMail and the turn rule it mails by
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Signing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Signing;

use OCA\Filinq\Service\ObjectBoundMail;
use OCA\Filinq\Service\ObjectBoundMailTarget;
use OCA\Filinq\Service\ObjectBoundMessage;
use OCA\Filinq\Service\Signing\SigningMail;
use OCA\Filinq\Service\Signing\SigningTurn;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The signer whose turn it is and the initiator of a declined request are mailed, bound to the request.
 *
 * @covers \OCA\Filinq\Service\Signing\SigningMail
 * @covers \OCA\Filinq\Service\Signing\SigningTurn
 *
 * @spec openspec/changes/signer-consent-notifications-to-email-leaf/tasks.md#d-1-signerinitiator-notifications-via-the-email-leaf-m
 */
class SigningMailTest extends TestCase {

	/**
	 * Sends the mail bound to the request.
	 *
	 * @var ObjectBoundMail&MockObject
	 */
	private ObjectBoundMail $mail;

	/**
	 * The service under test.
	 *
	 * @var SigningMail
	 */
	private SigningMail $signingMail;

	/**
	 * The signing request as a mail target.
	 *
	 * @var ObjectBoundMailTarget
	 */
	private ObjectBoundMailTarget $target;

	/**
	 * Build the service.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->mail = $this->createMock(originalClassName: ObjectBoundMail::class);
		$l10n       = $this->createMock(originalClassName: IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $text, array $p=[]): string => vsprintf($text, $p));
		$this->signingMail = new SigningMail(
			mail: $this->mail,
			l10n: $l10n,
			logger: $this->createMock(originalClassName: LoggerInterface::class)
		);
		$this->target      = new ObjectBoundMailTarget(uuid: 'req-1', register: 'filinq', schema: 'signingRequest');

	}//end setUp()

	/**
	 * Three signers in order: A by address, B by account, C by address.
	 *
	 * @param string $a A's status.
	 * @param string $b B's status.
	 * @param string $c C's status.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function signers(string $a='PENDING', string $b='PENDING', string $c='PENDING'): array {
		return [
			's-a' => ['status' => $a, 'order' => 0, 'email' => 'a@x.nl', 'displayName' => 'A'],
			's-b' => ['status' => $b, 'order' => 1, 'userId' => 'bert'],
			's-c' => ['status' => $c, 'order' => 2, 'email' => 'c@x.nl'],
		];

	}//end signers()

	/**
	 * A signing request over those signers.
	 *
	 * @param string $mode     sequential or parallel.
	 * @param string $provider The signing provider.
	 *
	 * @return array<string, mixed>
	 */
	private static function request(string $mode='sequential', string $provider='native'): array {
		return [
			'id' => 'req-1',
			'signerIds' => ['s-a', 's-b', 's-c'],
			'signingMode' => $mode,
			'provider' => $provider,
			'documentName' => 'Besluit.pdf',
			'initiatorUserId' => 'ini',
		];

	}//end request()

	/**
	 * Only the first signer of a sequential request is mailed at creation, bound to the request.
	 *
	 * @return void
	 */
	public function testOnlyTheFirstSignerOfASequentialRequestIsMailedAtCreation(): void {
		$this->mail->expects($this->once())->method('toAddress')
			->with(
				'a@x.nl',
				'A',
				$this->callback(
					callback: fn (ObjectBoundMessage $m): bool => str_contains($m->subject, 'Besluit.pdf') === true
						&& str_contains($m->body, 'your turn') === true
						&& $m->object === $this->target
				)
			)
			->willReturn('dispatched');
		$this->mail->expects($this->never())->method('toUser');

		$this->assertSame(
			expected: ['s-a:dispatched'],
			actual: $this->signingMail->turnStarted(request: self::request(), signers: self::signers(), target: $this->target)
		);

	}//end testOnlyTheFirstSignerOfASequentialRequestIsMailedAtCreation()

	/**
	 * After the first signs, the next is mailed; a signer without an address is mailed at their account.
	 *
	 * @return void
	 */
	public function testTheNextSignerIsMailedAtTheirAccountAfterTheFirstSigns(): void {
		$this->mail->expects($this->never())->method('toAddress');
		$this->mail->expects($this->once())->method('toUser')->with('bert', $this->anything())->willReturn('dispatched');

		$this->assertSame(
			expected: ['s-b:dispatched'],
			actual: $this->signingMail->turnStarted(request: self::request(), signers: self::signers(a: 'SIGNED'), target: $this->target, after: 's-a')
		);

	}//end testTheNextSignerIsMailedAtTheirAccountAfterTheFirstSigns()

	/**
	 * Every signer of a parallel request is mailed once, at creation.
	 *
	 * @return void
	 */
	public function testEverySignerOfAParallelRequestIsMailedOnceAtCreation(): void {
		$this->mail->expects($this->exactly(count: 2))->method('toAddress')->willReturn('dispatched');
		$this->mail->expects($this->once())->method('toUser')->willReturn('dispatched');

		$created = $this->signingMail->turnStarted(request: self::request(mode: 'parallel'), signers: self::signers(), target: $this->target);
		$later   = $this->signingMail->turnStarted(
			request: self::request(mode: 'parallel'),
			signers: self::signers(a: 'SIGNED'),
			target: $this->target,
			after: 's-a'
		);

		$this->assertCount(expectedCount: 3, haystack: $created);
		$this->assertSame(expected: [], actual: $later);

	}//end testEverySignerOfAParallelRequestIsMailedOnceAtCreation()

	/**
	 * An external provider mails its own signers, so filinq mails nobody.
	 *
	 * @return void
	 */
	public function testAnExternalProviderMailsItsOwnSigners(): void {
		$this->mail->expects($this->never())->method('toAddress');
		$this->mail->expects($this->never())->method('toUser');
		$request = self::request(provider: 'validsign');

		$this->assertSame(expected: [], actual: $this->signingMail->turnStarted(request: $request, signers: self::signers(), target: $this->target));
		$this->assertSame(expected: '', actual: $this->signingMail->declined(request: $request, declinedBy: 'A', reason: 'nee', target: $this->target));

	}//end testAnExternalProviderMailsItsOwnSigners()

	/**
	 * A signer without any address is not mailed; a send that throws is a failed outcome, not an error.
	 *
	 * @return void
	 */
	public function testASignerWithoutAddressOrAFailingSendDoesNotStopTheTurn(): void {
		$signers = self::signers();
		unset($signers['s-a']['email']);
		$this->assertSame(
			expected: ['s-a:no-address'],
			actual: $this->signingMail->turnStarted(request: self::request(), signers: $signers, target: $this->target)
		);

		$this->mail->method('toAddress')->willThrowException(new RuntimeException('down'));
		$this->assertSame(
			expected: ['s-a:failed'],
			actual: $this->signingMail->turnStarted(request: self::request(), signers: self::signers(), target: $this->target)
		);

	}//end testASignerWithoutAddressOrAFailingSendDoesNotStopTheTurn()

	/**
	 * The initiator is mailed the decline and its reason, bound to the request.
	 *
	 * @return void
	 */
	public function testTheInitiatorIsMailedTheDeclineAndItsReason(): void {
		$this->mail->expects($this->once())->method('toUser')
			->with(
				'ini',
				$this->callback(
					callback: fn (ObjectBoundMessage $m): bool => $m->subject === 'A declined to sign Besluit.pdf'
						&& str_contains($m->body, 'The reason given: Onjuist bedrag') === true
						&& $m->object === $this->target
				)
			)
			->willReturn('dispatched');

		$this->assertSame(
			expected: 'dispatched',
			actual: $this->signingMail->declined(request: self::request(), declinedBy: 'A', reason: 'Onjuist bedrag', target: $this->target)
		);

	}//end testTheInitiatorIsMailedTheDeclineAndItsReason()

	/**
	 * A decline without a reason says so; a request without an initiator mails nobody.
	 *
	 * @return void
	 */
	public function testADeclineWithoutReasonSaysSoAndOneWithoutInitiatorMailsNobody(): void {
		$this->mail->expects($this->once())->method('toUser')
			->with('ini', $this->callback(callback: static fn (ObjectBoundMessage $m): bool => str_contains($m->body, 'No reason was given.') === true))
			->willThrowException(new RuntimeException('down'));

		$this->assertSame(
			expected: 'failed',
			actual: $this->signingMail->declined(request: self::request(), declinedBy: 'A', reason: ' ', target: $this->target)
		);
		$this->assertSame(
			expected: '',
			actual: $this->signingMail->declined(request: ['provider' => 'native'], declinedBy: 'A', reason: 'x', target: $this->target)
		);

	}//end testADeclineWithoutReasonSaysSoAndOneWithoutInitiatorMailsNobody()

	/**
	 * Envelope and done signers are not due, equal orders share a turn, and a name falls back.
	 *
	 * @return void
	 */
	public function testEnvelopeAndDoneSignersAreNotDueAndEqualOrdersShareATurn(): void {
		$signers = self::signers();
		$signers['s-a']['envelopeRef'] = 'env-1';
		$signers['s-b']['order']       = 0;

		$this->assertSame(expected: ['s-b'], actual: SigningTurn::due(request: self::request(), signers: $signers));
		$this->assertSame(
			expected: ['s-c'],
			actual: SigningTurn::due(request: self::request(), signers: self::signers(a: 'SIGNED', b: 'SIGNED'), after: 's-b')
		);
		$this->assertSame(
			expected: [],
			actual: SigningTurn::due(request: self::request(), signers: self::signers(a: 'SIGNED', b: 'SIGNED', c: 'SIGNED'), after: 's-c')
		);
		$this->assertSame(expected: 'A', actual: SigningTurn::nameOf(signerId: 'fallback', signer: ['displayName' => 'A']));
		$this->assertSame(expected: 'fallback', actual: SigningTurn::nameOf(signerId: 'fallback', signer: []));

	}//end testEnvelopeAndDoneSignersAreNotDueAndEqualOrdersShareATurn()
}//end class
