<?php

/**
 * Unit tests for DocumentEventTranslator.
 *
 * Constructs the real OpenRegister object-event shapes and asserts one
 * document event per qualifying write, none for a failed generation, none
 * for another move or another record, with the envelope built from the
 * record.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use DateTime;
use DateTimeZone;
use OCA\Filinq\Event\Webhook\DocumentAnonymisedEvent;
use OCA\Filinq\Event\Webhook\DocumentGeneratedEvent;
use OCA\Filinq\Event\Webhook\DocumentRegisteredEvent;
use OCA\Filinq\Event\Webhook\DocumentSignedEvent;
use OCA\Filinq\Event\Webhook\DocumentWebhookEvent;
use OCA\Filinq\Event\Webhook\DocumentWebhookSubject;
use OCA\Filinq\EventListener\DocumentEventTranslator;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Event\ObjectCreatedEvent;
use OCA\OpenRegister\Event\ObjectTransitionedEvent;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use OCA\OpenRegister\Kernel\Contracts\RegisterSchemaRef;
use OCA\OpenRegister\Service\Lifecycle\TransitionActor;
use OCA\OpenRegister\Service\Lifecycle\TransitionMove;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for DocumentEventTranslator.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
#[CoversClass(DocumentEventTranslator::class)]
#[UsesClass(DocumentWebhookEvent::class)]
#[UsesClass(DocumentWebhookSubject::class)]
#[UsesClass(DocumentSignedEvent::class)]
#[UsesClass(DocumentAnonymisedEvent::class)]
#[UsesClass(DocumentGeneratedEvent::class)]
#[UsesClass(DocumentRegisteredEvent::class)]
class DocumentEventTranslatorTest extends TestCase {

	/**
	 * Events the translator dispatched.
	 *
	 * @var array<int, Event>
	 */
	private array $dispatched = [];

	/**
	 * Build the translator over a recording dispatcher, a fixed clock and a
	 * session whose user is `clerk` (or nobody).
	 *
	 * @param bool $withUser Whether a user is logged in.
	 *
	 * @return DocumentEventTranslator The translator.
	 */
	private function translator(bool $withUser=true): DocumentEventTranslator {
		$this->dispatched = [];
		$dispatcher = $this->createMock(IEventDispatcher::class);
		$dispatcher->method('dispatchTyped')->willReturnCallback(function (Event $event): void {
			$this->dispatched[] = $event;
		});

		$session = $this->createMock(IUserSession::class);
		$user    = null;
		if ($withUser === true) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn('clerk');
		}

		$session->method('getUser')->willReturn($user);

		$time = $this->createMock(ITimeFactory::class);
		$time->method('getDateTime')->willReturn(new DateTime('2026-11-13T09:30:00', new DateTimeZone('UTC')));

		return new DocumentEventTranslator(dispatcher: $dispatcher, userSession: $session, time: $time);

	}//end translator()

	/**
	 * An object entity holding the given data.
	 *
	 * @param array<string, mixed> $data The record data.
	 *
	 * @return ObjectEntity The entity.
	 */
	private function entity(array $data): ObjectEntity {
		$entity = new ObjectEntity();
		$entity->setUuid('rec-1');
		$entity->setRegister('4');
		$entity->setSchema('9');
		$entity->setObject($data);
		return $entity;

	}//end entity()

	/**
	 * A transition event on a signing request.
	 *
	 * @param string $action The lifecycle action.
	 *
	 * @return ObjectTransitionedEvent The event.
	 */
	private function transition(string $action): ObjectTransitionedEvent {
		return new ObjectTransitionedEvent(
			object: $this->entity(
				[
					'documentFileId' => 501,
					'documentName' => 'contract.pdf',
					'signatureLevel' => 'QES',
					'status' => 'COMPLETED',
					'signedDocumentRef' => '777:3',
					'signerIds' => ['s1', 's2'],
				]
			),
			move: new TransitionMove(action: $action, from: 'IN_PROGRESS', to: 'COMPLETED'),
			scope: new RegisterSchemaRef(register: '4', schema: '9'),
			actor: new TransitionActor(userId: 'signer-2')
		);

	}//end transition()

	/**
	 * A completed signing request raises one signed event with its envelope.
	 *
	 * @return void
	 */
	public function testCompleteTransitionRaisesOneSignedEvent(): void {
		$this->translator()->handle($this->transition('complete'));

		self::assertCount(1, $this->dispatched);
		self::assertInstanceOf(DocumentSignedEvent::class, $this->dispatched[0]);
		self::assertSame(
			[
				'event' => 'document.signed',
				'occurredAt' => '2026-11-13T09:30:00+00:00',
				'document' => ['fileId' => 501, 'name' => 'contract.pdf'],
				'record' => ['register' => '4', 'schema' => '9', 'id' => 'rec-1'],
				'actor' => 'signer-2',
				'signingRequestId' => 'rec-1',
				'signedFileRef' => '777:3',
				'signatureLevel' => 'QES',
			],
			$this->dispatched[0]->getPayload()
		);

	}//end testCompleteTransitionRaisesOneSignedEvent()

	/**
	 * Any other transition of a signing request raises nothing.
	 *
	 * @return void
	 */
	public function testOtherTransitionRaisesNothing(): void {
		$this->translator()->handle($this->transition('decline'));

		self::assertSame([], $this->dispatched);

	}//end testOtherTransitionRaisesNothing()

	/**
	 * A `complete` transition of a record that is not a signing request raises nothing.
	 *
	 * @return void
	 */
	public function testCompleteTransitionOfAnotherRecordRaisesNothing(): void {
		$this->translator()->handle(
			new ObjectTransitionedEvent(
				object: $this->entity(['title' => 'A task', 'status' => 'done']),
				move: new TransitionMove(action: 'complete', from: 'open', to: 'done'),
				scope: new RegisterSchemaRef(register: '4', schema: '12')
			)
		);

		self::assertSame([], $this->dispatched);

	}//end testCompleteTransitionOfAnotherRecordRaisesNothing()

	/**
	 * A created anonymisation link raises one anonymised event.
	 *
	 * @return void
	 */
	public function testCreatedAnonymisationLinkRaisesOneAnonymisedEvent(): void {
		$this->translator()->handle(
			new ObjectCreatedEvent(
				$this->entity(
					[
						'sourceFileId' => 10,
						'sourceFileName' => 'besluit.pdf',
						'anonymizedFileId' => 11,
						'status' => 'anonymized',
						'verificationVerdict' => 'clean',
						'replacementCount' => 4,
					]
				)
			)
		);

		self::assertCount(1, $this->dispatched);
		self::assertInstanceOf(DocumentAnonymisedEvent::class, $this->dispatched[0]);
		self::assertSame(
			[
				'event' => 'document.anonymised',
				'occurredAt' => '2026-11-13T09:30:00+00:00',
				'document' => ['fileId' => 10, 'name' => 'besluit.pdf'],
				'record' => ['register' => '4', 'schema' => '9', 'id' => 'rec-1'],
				'actor' => 'clerk',
				'sourceFileId' => 10,
				'anonymisedFileId' => 11,
				'verificationVerdict' => 'clean',
			],
			$this->dispatched[0]->getPayload()
		);

	}//end testCreatedAnonymisationLinkRaisesOneAnonymisedEvent()

	/**
	 * A generated document with status `generated` raises one generated event.
	 *
	 * @return void
	 */
	public function testSuccessfulGenerationRaisesOneGeneratedEvent(): void {
		$this->translator(withUser: false)->handle(
			new ObjectCreatedEvent(
				$this->entity(
					[
						'templateId' => 'tpl-7',
						'templateVersion' => 2,
						'format' => 'pdf',
						'status' => 'generated',
						'generatedAt' => '2026-11-13T09:29:00+00:00',
						'generatedBy' => 'clerk',
						'fileId' => 88,
						'filePath' => '/clerk/files/Filinq/brief.pdf',
					]
				)
			)
		);

		self::assertCount(1, $this->dispatched);
		self::assertInstanceOf(DocumentGeneratedEvent::class, $this->dispatched[0]);
		self::assertSame(
			[
				'event' => 'document.generated',
				'occurredAt' => '2026-11-13T09:30:00+00:00',
				'document' => ['fileId' => 88, 'name' => 'brief.pdf'],
				'record' => ['register' => '4', 'schema' => '9', 'id' => 'rec-1'],
				'actor' => null,
				'templateId' => 'tpl-7',
				'templateVersion' => 2,
				'format' => 'pdf',
			],
			$this->dispatched[0]->getPayload()
		);

	}//end testSuccessfulGenerationRaisesOneGeneratedEvent()

	/**
	 * A failed generation stays quiet.
	 *
	 * @return void
	 */
	public function testFailedGenerationRaisesNothing(): void {
		$this->translator()->handle(
			new ObjectCreatedEvent(
				$this->entity(
					[
						'templateId' => 'tpl-7',
						'format' => 'pdf',
						'status' => 'failed',
						'errorMessage' => 'Template could not be rendered',
					]
				)
			)
		);

		self::assertSame([], $this->dispatched);

	}//end testFailedGenerationRaisesNothing()

	/**
	 * A created registration raises one registered event.
	 *
	 * @return void
	 */
	public function testCreatedRegistrationRaisesOneRegisteredEvent(): void {
		$this->translator()->handle(
			new ObjectCreatedEvent(
				$this->entity(
					[
						'direction' => 'outbound',
						'unit' => 'juridische-zaken',
						'document' => 'doc-uuid-5',
						'registeredAt' => '2026-11-13T09:00:00+00:00',
						'registrationNumber' => 'UIT-2026-0042',
					]
				)
			)
		);

		self::assertCount(1, $this->dispatched);
		self::assertInstanceOf(DocumentRegisteredEvent::class, $this->dispatched[0]);
		self::assertSame(
			[
				'event' => 'document.registered',
				'occurredAt' => '2026-11-13T09:30:00+00:00',
				'document' => ['fileId' => 'doc-uuid-5', 'name' => null],
				'record' => ['register' => '4', 'schema' => '9', 'id' => 'rec-1'],
				'actor' => 'clerk',
				'direction' => 'outbound',
				'unit' => 'juridische-zaken',
				'registrationNumber' => 'UIT-2026-0042',
			],
			$this->dispatched[0]->getPayload()
		);

	}//end testCreatedRegistrationRaisesOneRegisteredEvent()

	/**
	 * A created record of another schema raises nothing.
	 *
	 * @return void
	 */
	public function testCreatedOtherRecordRaisesNothing(): void {
		$this->translator()->handle(new ObjectCreatedEvent($this->entity(['title' => 'Dossier', 'status' => 'open'])));

		self::assertSame([], $this->dispatched);

	}//end testCreatedOtherRecordRaisesNothing()

	/**
	 * A creation without an object, and any other event, raise nothing.
	 *
	 * @return void
	 */
	public function testEmptyCreationAndOtherEventsRaiseNothing(): void {
		$translator = $this->translator();
		$translator->handle(new ObjectCreatedEvent(null));
		$translator->handle(new ObjectUpdatedEvent($this->entity(['direction' => 'inbound', 'unit' => 'post', 'document' => 'd'])));

		self::assertSame([], $this->dispatched);

	}//end testEmptyCreationAndOtherEventsRaiseNothing()
}//end class
