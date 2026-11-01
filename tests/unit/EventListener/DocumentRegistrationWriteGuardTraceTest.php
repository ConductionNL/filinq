<?php

/**
 * Characterization tests for DocumentRegistrationWriteGuard::handle()
 *
 * Pins the refusal payload, the log entry and which side of the update the
 * schema and the number are read from.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\EventListener\DocumentRegistrationWriteGuard;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Event\ObjectUpdatingEvent;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterization of the registration-number write guard.
 *
 * @covers \OCA\Filinq\EventListener\DocumentRegistrationWriteGuard
 */
class DocumentRegistrationWriteGuardTraceTest extends TestCase {

	private LoggerInterface&MockObject $logger;

	/**
	 * Fresh logger double.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * A moved number is refused with the full payload, logged with the new object's uuid.
	 *
	 * @return void
	 */
	public function testRefusalPayloadAndLogEntry(): void {
		$this->logger->expects($this->once())->method('warning')->with(
			'filinq.post-register.number-change-refused',
			['was' => '2026-00042', 'now' => '2026-00043', 'uuid' => 'new-uuid']
		);

		$event = $this->guard(
			new: $this->entity(uuid: 'new-uuid', schema: 'documentRegistration', data: ['registrationNumber' => '2026-00043']),
			old: $this->entity(uuid: 'old-uuid', schema: 'documentRegistration', data: ['registrationNumber' => '2026-00042'])
		);

		$this->assertTrue($event->isPropagationStopped());
		$this->assertSame(
			[
				'message' => 'Registratienummer 2026-00042 ligt vast en kan niet worden gewijzigd. '
					. 'Trek de registratie in met een reden (withdrawnReason) en maak een nieuwe aan.',
				'property' => 'registrationNumber',
				'was' => '2026-00042',
			],
			$event->getErrors()
		);

	}//end testRefusalPayloadAndLogEntry()

	/**
	 * Removing an issued number is a move too.
	 *
	 * @return void
	 */
	public function testRemovingAnIssuedNumberIsRefused(): void {
		$this->logger->expects($this->once())->method('warning')->with(
			'filinq.post-register.number-change-refused',
			['was' => '7', 'now' => '', 'uuid' => 'n']
		);

		$event = $this->guard(
			new: $this->entity(uuid: 'n', schema: 'documentRegistration', data: []),
			old: $this->entity(uuid: 'o', schema: 'documentRegistration', data: ['registrationNumber' => '7'])
		);

		$this->assertTrue($event->isPropagationStopped());

	}//end testRemovingAnIssuedNumberIsRefused()

	/**
	 * The schema matches on either side of the update, case-insensitively.
	 *
	 * @return void
	 */
	public function testSchemaMatchesOnEitherSide(): void {
		$cases = [
			['DocumentRegistration', 'other'],
			['other', 'DOCUMENTREGISTRATION'],
		];
		foreach ($cases as [$newSchema, $oldSchema]) {
			$event = $this->guard(
				new: $this->entity(uuid: 'n', schema: $newSchema, data: ['registrationNumber' => 'B']),
				old: $this->entity(uuid: 'o', schema: $oldSchema, data: ['registrationNumber' => 'A'])
			);

			$this->assertTrue($event->isPropagationStopped(), $newSchema . '/' . $oldSchema);
		}

	}//end testSchemaMatchesOnEitherSide()

	/**
	 * Neither side a registration: nothing is refused or logged.
	 *
	 * @return void
	 */
	public function testNeitherSideARegistrationPasses(): void {
		$this->logger->expects($this->never())->method('warning');

		$event = $this->guard(
			new: $this->entity(uuid: 'n', schema: 'other', data: ['registrationNumber' => 'B']),
			old: $this->entity(uuid: 'o', schema: 'documentRegistrationX', data: ['registrationNumber' => 'A'])
		);

		$this->assertFalse($event->isPropagationStopped());
		$this->assertSame([], $event->getErrors());

	}//end testNeitherSideARegistrationPasses()

	/**
	 * Numbers are compared trimmed, so surrounding whitespace is not a move.
	 *
	 * @return void
	 */
	public function testWhitespaceIsNotAMove(): void {
		$this->logger->expects($this->never())->method('warning');

		$event = $this->guard(
			new: $this->entity(uuid: 'n', schema: 'documentRegistration', data: ['registrationNumber' => ' 2026-1 ']),
			old: $this->entity(uuid: 'o', schema: 'documentRegistration', data: ['registrationNumber' => '2026-1'])
		);

		$this->assertFalse($event->isPropagationStopped());

	}//end testWhitespaceIsNotAMove()

	/**
	 * A whitespace-only stored number counts as not issued.
	 *
	 * @return void
	 */
	public function testBlankStoredNumberIsNotIssued(): void {
		$event = $this->guard(
			new: $this->entity(uuid: 'n', schema: 'documentRegistration', data: ['registrationNumber' => 'X']),
			old: $this->entity(uuid: 'o', schema: 'documentRegistration', data: ['registrationNumber' => '   '])
		);

		$this->assertFalse($event->isPropagationStopped());

	}//end testBlankStoredNumberIsNotIssued()

	/**
	 * A numeric number is read as text and refused when it moves.
	 *
	 * @return void
	 */
	public function testNumericNumberIsReadAsText(): void {
		$this->logger->expects($this->once())->method('warning')->with(
			'filinq.post-register.number-change-refused',
			['was' => '42', 'now' => '43', 'uuid' => 'n']
		);

		$this->guard(
			new: $this->entity(uuid: 'n', schema: 'documentRegistration', data: ['registrationNumber' => 43]),
			old: $this->entity(uuid: 'o', schema: 'documentRegistration', data: ['registrationNumber' => 42])
		);

	}//end testNumericNumberIsReadAsText()

	/**
	 * Numbers compare as text: "1e3" to "1000" is a move, although PHP's == calls them equal.
	 *
	 * @return void
	 */
	public function testNumericLookingNumbersCompareAsText(): void {
		$event = $this->guard(
			new: $this->entity(uuid: 'n', schema: 'documentRegistration', data: ['registrationNumber' => '1000']),
			old: $this->entity(uuid: 'o', schema: 'documentRegistration', data: ['registrationNumber' => '1e3'])
		);

		$this->assertTrue($event->isPropagationStopped());

	}//end testNumericLookingNumbersCompareAsText()

	/**
	 * An event other than ObjectUpdatingEvent is ignored.
	 *
	 * @return void
	 */
	public function testOtherEventIsIgnored(): void {
		$this->logger->expects($this->never())->method('warning');
		$event = $this->createMock(Event::class);
		$event->expects($this->never())->method('stopPropagation');

		(new DocumentRegistrationWriteGuard($this->logger))->handle($event);

	}//end testOtherEventIsIgnored()

	/**
	 * Run the guard over one update.
	 *
	 * @param ObjectEntity $new The incoming object.
	 * @param ObjectEntity $old The stored object.
	 *
	 * @return ObjectUpdatingEvent The event afterwards.
	 */
	private function guard(ObjectEntity $new, ObjectEntity $old): ObjectUpdatingEvent {
		$event = new ObjectUpdatingEvent($new, $old);
		(new DocumentRegistrationWriteGuard($this->logger))->handle($event);

		return $event;
	}//end guard()

	/**
	 * An object entity.
	 *
	 * @param string $uuid The uuid.
	 * @param string $schema The schema slug.
	 * @param array<string, mixed> $data The object data.
	 *
	 * @return ObjectEntity The entity.
	 */
	private function entity(string $uuid, string $schema, array $data): ObjectEntity {
		$object = new ObjectEntity();
		$object->setUuid($uuid);
		$object->setSchema($schema);
		$object->setObject($data);

		return $object;
	}//end entity()
}//end class
