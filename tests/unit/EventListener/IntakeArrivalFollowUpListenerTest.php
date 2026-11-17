<?php

/**
 * Unit tests for IntakeArrivalFollowUpListener (design D2).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
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

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\EventListener\IntakeArrivalFollowUpListener;
use OCA\Filinq\Service\IntakeAcknowledgementService;
use OCA\Filinq\Service\IntakeDefaultRuleService;
use OCA\Filinq\Service\IntakeRepository;
use OCA\Filinq\Service\ReceivedStampService;
use OCA\OpenRegister\Event\ObjectCreatedEvent;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use OCA\OpenRegister\Db\ObjectEntity;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The acknowledgement runs after the save, writes its outcome, and never
 * throws into the arrival.
 *
 * @covers \OCA\Filinq\EventListener\IntakeArrivalFollowUpListener
 */
class IntakeArrivalFollowUpListenerTest extends TestCase {

	/**
	 * A saved intake document.
	 *
	 * @param string $schema The schema the object carries.
	 *
	 * @return ObjectEntity
	 */
	private static function saved(string $schema = 'intakeDocument'): ObjectEntity {
		$entity = new ObjectEntity();
		$entity->setUuid('doc-1');
		$entity->setSchema($schema);
		$entity->setObject(['channel' => 'mail', 'sender' => 'j.devries@example.nl', 'status' => 'received', 'defaultRule' => 'rule-mail']);
		return $entity;
	}//end saved()

	/**
	 * The listener over its collaborators.
	 *
	 * @param IntakeDefaultRuleService     $rules      The rules.
	 * @param IntakeAcknowledgementService $ack        The acknowledgement.
	 * @param IntakeRepository             $repository The repository.
	 * @param LoggerInterface|null         $logger     The logger.
	 * @param ContainerInterface|null      $container  The container.
	 *
	 * @return IntakeArrivalFollowUpListener
	 */
	private function listener(
		IntakeDefaultRuleService $rules,
		IntakeAcknowledgementService $ack,
		IntakeRepository $repository,
		?LoggerInterface $logger = null,
		?ContainerInterface $container = null,
		?ReceivedStampService $stamp = null
	): IntakeArrivalFollowUpListener {
		return new IntakeArrivalFollowUpListener(
			($container ?? $this->createMock(ContainerInterface::class)),
			$rules,
			$ack,
			$repository,
			($logger ?? $this->createMock(LoggerInterface::class)),
			$stamp
		);
	}//end listener()

	/**
	 * The outcome is written onto the saved document, keeping its fields.
	 *
	 * @return void
	 */
	public function testTheOutcomeIsWrittenOntoTheDocument(): void {
		$rule = ['uuid' => 'rule-mail', 'acknowledgeWith' => 'tpl'];
		$outcome = ['status' => 'sent', 'address' => 'j.devries@example.nl', 'at' => '2026-10-14T09:30:00+00:00', 'reason' => null];
		$rules = $this->createMock(IntakeDefaultRuleService::class);
		$rules->expects($this->once())->method('findRule')->with('rule-mail')->willReturn($rule);
		$ack = $this->createMock(IntakeAcknowledgementService::class);
		$ack->expects($this->once())->method('acknowledge')->with(
			['uuid' => 'doc-1', 'channel' => 'mail', 'sender' => 'j.devries@example.nl', 'status' => 'received', 'defaultRule' => 'rule-mail'],
			$rule
		)->willReturn($outcome);
		$repository = $this->createMock(IntakeRepository::class);
		$repository->expects($this->once())->method('save')->with(
			['channel' => 'mail', 'sender' => 'j.devries@example.nl', 'status' => 'received', 'defaultRule' => 'rule-mail', 'acknowledgement' => $outcome],
			'doc-1'
		)->willReturn([]);

		$this->listener($rules, $ack, $repository)->handle(new ObjectCreatedEvent(self::saved()));
	}//end testTheOutcomeIsWrittenOntoTheDocument()

	/**
	 * A rule with the stamp on writes the stamped copy's id beside the
	 * acknowledgement, stamped with the received date (REQ-IAS-003).
	 *
	 * @return void
	 *
	 * @spec openspec/changes/intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-a-received-stamp-goes-on-a-copy-never-on-what-arrived-req-ias-003
	 */
	public function testAStampingRuleStoresTheStampedCopy(): void {
		$entity = self::saved();
		$entity->setObject(['channel' => 'mail', 'status' => 'received', 'defaultRule' => 'rule-mail', 'file' => 41, 'receivedAt' => '2026-10-02T08:15:00+02:00']);
		$rules = $this->createMock(IntakeDefaultRuleService::class);
		$rules->method('findRule')->willReturn(['uuid' => 'rule-mail', 'stampReceived' => true]);
		$ack = $this->createMock(IntakeAcknowledgementService::class);
		$ack->method('acknowledge')->willThrowException(new RuntimeException('mail down'));
		$stamp = $this->createMock(ReceivedStampService::class);
		$stamp->expects($this->once())->method('stampFile')->with(41, '02-10-2026')->willReturn(77);
		$repository = $this->createMock(IntakeRepository::class);
		$repository->expects($this->once())->method('save')->with(
			['channel' => 'mail', 'status' => 'received', 'defaultRule' => 'rule-mail', 'file' => 41, 'receivedAt' => '2026-10-02T08:15:00+02:00', 'stampedRendition' => 77],
			'doc-1'
		)->willReturn([]);

		$this->listener($rules, $ack, $repository, stamp: $stamp)->handle(new ObjectCreatedEvent($entity));
	}//end testAStampingRuleStoresTheStampedCopy()

	/**
	 * Without the stamp on the rule, or without a file, no copy is asked for.
	 *
	 * @return void
	 */
	public function testNoStampWithoutTheSwitchOrAFile(): void {
		$stamp = $this->createMock(ReceivedStampService::class);
		$stamp->expects($this->never())->method('stampFile');
		$ack = $this->createMock(IntakeAcknowledgementService::class);
		$ack->method('acknowledge')->willReturn(null);
		$repository = $this->createMock(IntakeRepository::class);
		$repository->expects($this->never())->method('save');

		foreach ([[['uuid' => 'r', 'stampReceived' => false], 41], [['uuid' => 'r', 'stampReceived' => true], null]] as [$rule, $file]) {
			$entity = self::saved();
			$entity->setObject(['defaultRule' => 'r', 'file' => $file]);
			$rules = $this->createMock(IntakeDefaultRuleService::class);
			$rules->method('findRule')->willReturn($rule);
			$this->listener($rules, $ack, $repository, stamp: $stamp)->handle(new ObjectCreatedEvent($entity));
		}
	}//end testNoStampWithoutTheSwitchOrAFile()

	/**
	 * A schema given by id is resolved to its slug before anything runs.
	 *
	 * @return void
	 */
	public function testASchemaIdIsResolvedToItsSlug(): void {
		$schema = new class {
			/**
			 * The slug.
			 *
			 * @return string
			 */
			public function getSlug(): string {
				return 'intakeDocument';
			}
		};
		$mapper = new class($schema) {
			/**
			 * Constructor.
			 *
			 * @param object $schema The schema.
			 */
			public function __construct(private object $schema) {
			}

			/**
			 * Find a schema.
			 *
			 * @param string $id The id.
			 *
			 * @return object
			 */
			public function find(string $id): object {
				return $this->schema;
			}
		};
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->with('OCA\\OpenRegister\\Db\\SchemaMapper')->willReturn($mapper);
		$rules = $this->createMock(IntakeDefaultRuleService::class);
		$rules->expects($this->once())->method('findRule')->willReturn(null);

		$this->listener($rules, $this->createMock(IntakeAcknowledgementService::class), $this->createMock(IntakeRepository::class), null, $container)
			->handle(new ObjectCreatedEvent(self::saved('17')));
	}//end testASchemaIdIsResolvedToItsSlug()

	/**
	 * Another schema, an unknown schema id, an update and another event do nothing.
	 *
	 * @return void
	 */
	public function testOtherObjectsAndEventsAreIgnored(): void {
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willThrowException(new RuntimeException('no mapper'));
		$rules = $this->createMock(IntakeDefaultRuleService::class);
		$rules->expects($this->never())->method('findRule');
		$listener = $this->listener($rules, $this->createMock(IntakeAcknowledgementService::class), $this->createMock(IntakeRepository::class), null, $container);

		$listener->handle(new ObjectCreatedEvent(self::saved('contract')));
		$listener->handle(new ObjectCreatedEvent(self::saved('99')));
		$listener->handle(new ObjectCreatedEvent(self::saved('')));
		$listener->handle(new ObjectUpdatedEvent(self::saved(), self::saved()));
		$listener->handle(new Event());
	}//end testOtherObjectsAndEventsAreIgnored()

	/**
	 * Without a rule, or when the rule asks for nothing, nothing is written.
	 *
	 * @return void
	 */
	public function testNothingIsWrittenWithoutAnOutcome(): void {
		$rules = $this->createMock(IntakeDefaultRuleService::class);
		$rules->method('findRule')->willReturnOnConsecutiveCalls(null, ['uuid' => 'rule-mail']);
		$ack = $this->createMock(IntakeAcknowledgementService::class);
		$ack->expects($this->once())->method('acknowledge')->willReturn(null);
		$repository = $this->createMock(IntakeRepository::class);
		$repository->expects($this->never())->method('save');
		$listener = $this->listener($rules, $ack, $repository);

		$listener->handle(new ObjectCreatedEvent(self::saved()));
		$listener->handle(new ObjectCreatedEvent(self::saved()));
	}//end testNothingIsWrittenWithoutAnOutcome()

	/**
	 * A failing write is logged and never thrown into the arrival.
	 *
	 * @return void
	 */
	public function testAFailingWriteIsLoggedAndSwallowed(): void {
		$rules = $this->createMock(IntakeDefaultRuleService::class);
		$rules->method('findRule')->willReturn(['uuid' => 'rule-mail']);
		$ack = $this->createMock(IntakeAcknowledgementService::class);
		$ack->method('acknowledge')->willReturn(['status' => 'sent']);
		$repository = $this->createMock(IntakeRepository::class);
		$repository->method('save')->willThrowException(new RuntimeException('register read-only'));
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning')->with(
			'[IntakeArrivalFollowUpListener] the arrival follow-up failed; the document is kept as it arrived',
			$this->callback(static fn (array $context): bool => $context['error'] === 'register read-only')
		);

		$this->listener($rules, $ack, $repository, $logger)->handle(new ObjectCreatedEvent(self::saved()));
	}//end testAFailingWriteIsLoggedAndSwallowed()
}//end class
