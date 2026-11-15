<?php

/**
 * Unit tests for AnsweringRegistration (intake-acknowledge-and-stamp D4).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Intake
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/intake-acknowledge-and-stamp
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Intake;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\Intake\AnsweringRegistration;
use OCA\OpenRegister\Service\ObjectService;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Finding the inbound registration and writing the outbound answer.
 *
 * @covers \OCA\Filinq\Service\Intake\AnsweringRegistration
 * @uses   \OCA\Filinq\Service\StoredRecordFields
 */
class AnsweringRegistrationTest extends TestCase {

	private ObjectService&MockObject $objects;

	private LoggerInterface&MockObject $logger;

	/**
	 * The registration at 14 October 2026, 09:30 UTC.
	 *
	 * @return AnsweringRegistration
	 */
	private function registration(): AnsweringRegistration {
		$this->objects = $this->createMock(ObjectService::class);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objects);
		$time = $this->createMock(ITimeFactory::class);
		$time->method('getDateTime')->willReturn(new \DateTime('2026-10-14T09:30:00+00:00'));
		$this->logger = $this->createMock(LoggerInterface::class);

		return new AnsweringRegistration($resolver, $time, $this->logger);
	}//end registration()

	/**
	 * The inbound entry naming the document is found by bare-key slug search.
	 *
	 * @return void
	 */
	public function testTheInboundRegistrationOfADocumentIsFound(): void {
		$registration = $this->registration();
		$this->objects->expects($this->once())->method('searchObjectsBySlug')
			->with('filinq', 'documentRegistration', ['document' => 'doc-1', 'direction' => 'inbound'])
			->willReturn([['id' => 'reg-in', 'direction' => 'inbound', 'unit' => 'post', 'registrationNumber' => '2026-00042', 'document' => 'doc-1']]);

		$inbound = $registration->inboundFor(' doc-1 ');

		$this->assertSame(['reg-in', 'post', '2026-00042'], [$inbound['uuid'], $inbound['unit'], $inbound['registrationNumber']]);
	}//end testTheInboundRegistrationOfADocumentIsFound()

	/**
	 * An outbound row the search returned anyway is not taken for the inbound one.
	 *
	 * @return void
	 */
	public function testAnOutboundRowIsNotTakenForTheInboundOne(): void {
		$registration = $this->registration();
		$this->objects->method('searchObjectsBySlug')->willReturn([['id' => 'reg-out', 'direction' => 'outbound']]);

		$this->assertNull($registration->inboundFor('doc-1'));
	}//end testAnOutboundRowIsNotTakenForTheInboundOne()

	/**
	 * No document, or a register that cannot be read, is no registration, logged.
	 *
	 * @return void
	 */
	public function testAFailedReadIsNoRegistration(): void {
		$registration = $this->registration();
		$this->objects->expects($this->once())->method('searchObjectsBySlug')->willThrowException(new RuntimeException('down'));
		$this->logger->expects($this->once())->method('warning');

		$this->assertNull($registration->inboundFor(''));
		$this->assertNull($registration->inboundFor('doc-1'));
	}//end testAFailedReadIsNoRegistration()

	/**
	 * The answer is an outbound entry in the inbound one's unit naming it in `answers`.
	 *
	 * @return void
	 */
	public function testTheAnswerIsRegisteredAsOutboundPost(): void {
		$registration = $this->registration();
		$this->objects->expects($this->once())->method('saveObject')
			->with(
				[
					'direction' => 'outbound',
					'registeredAt' => '2026-10-14T09:30:00+00:00',
					'unit' => 'post',
					'document' => 'gen-1',
					'answers' => 'reg-in',
				],
				'filinq',
				'documentRegistration'
			)
			->willReturn(['id' => 'reg-out', 'direction' => 'outbound', 'answers' => 'reg-in', 'registrationNumber' => '2026-00043']);

		$saved = $registration->registerAnswer(['uuid' => 'reg-in', 'unit' => 'post'], 'gen-1');

		$this->assertSame(['reg-out', 'reg-in', '2026-00043'], [$saved['uuid'], $saved['answers'], $saved['registrationNumber']]);
	}//end testTheAnswerIsRegisteredAsOutboundPost()

	/**
	 * Without the inbound id, its unit or the letter, nothing is written.
	 *
	 * @return void
	 */
	public function testNothingIsRegisteredWithoutWhatTheEntryNeeds(): void {
		$registration = $this->registration();
		$this->objects->expects($this->never())->method('saveObject');

		$this->assertNull($registration->registerAnswer(['uuid' => 'reg-in'], 'gen-1'));
		$this->assertNull($registration->registerAnswer(['unit' => 'post'], 'gen-1'));
		$this->assertNull($registration->registerAnswer(['uuid' => 'reg-in', 'unit' => 'post'], ' '));
	}//end testNothingIsRegisteredWithoutWhatTheEntryNeeds()

	/**
	 * A refused write is no registration, logged, never thrown.
	 *
	 * @return void
	 */
	public function testAFailedWriteIsNoRegistration(): void {
		$registration = $this->registration();
		$this->objects->method('saveObject')->willThrowException(new RuntimeException('refused'));
		$this->logger->expects($this->once())->method('warning');

		$this->assertNull($registration->registerAnswer(['uuid' => 'reg-in', 'unit' => 'post'], 'gen-1'));
	}//end testAFailedWriteIsNoRegistration()
}//end class
