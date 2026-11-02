<?php

/**
 * A registration number, once issued, is final.
 *
 * 🔴 THE VALUE OF A POST REGISTER IS THAT ITS NUMBERS DO NOT MOVE. A number is a
 * claim about an official act: a letter went out on this date under this number,
 * and somebody may have quoted it in a reply. Editing it afterwards means a
 * number in a filing cabinet no longer finds the record it names, and nothing
 * anywhere reports that the two came apart.
 *
 * 🔑 THE TASK ASSUMED A SERVICE THAT DOES NOT EXIST, and the platform was
 * checked rather than trusted. Registrations are written through OpenRegister's
 * objects API, so a guard in a filinq service would be bypassed by every
 * ordinary write. `MagicMapper` dispatches `ObjectUpdatingEvent` BEFORE an
 * update and throws `HookStoppedException` when a listener stops propagation —
 * so that, and only that, is where a refusal can land.
 *
 * The past-tense `ObjectUpdatedEvent` filinq already listens to could not have
 * done it: by the time it fires the number is already changed.
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
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\EventListener\DocumentRegistrationWriteGuard;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Event\ObjectUpdatingEvent;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * `DocumentRegistrationWriteGuard`.
 *
 * @covers \OCA\Filinq\EventListener\DocumentRegistrationWriteGuard
 */
class DocumentRegistrationWriteGuardTest extends TestCase {

	/**
	 * An object of the given schema carrying the given number.
	 *
	 * @param string $schema The schema slug.
	 * @param string $number The registration number, or ''.
	 *
	 * @return ObjectEntity The object.
	 */
	private function registration(string $schema, string $number): ObjectEntity {
		$object = new ObjectEntity();
		$object->setUuid('reg-1');
		$object->setSchema($schema);
		$object->setObject($number === '' ? [] : ['registrationNumber' => $number]);

		return $object;
	}//end registration()

	/**
	 * Run the guard over an update from one number to another.
	 *
	 * @param string $was    The stored number.
	 * @param string $now    The incoming number.
	 * @param string $schema The schema slug.
	 *
	 * @return ObjectUpdatingEvent The event afterwards.
	 */
	private function update(string $was, string $now, string $schema = 'documentRegistration'): ObjectUpdatingEvent {
		$event = new ObjectUpdatingEvent(
			$this->registration($schema, $now),
			$this->registration($schema, $was)
		);

		(new DocumentRegistrationWriteGuard(new NullLogger()))->handle($event);

		return $event;
	}//end update()

	/**
	 * 🔴 MOVING AN ISSUED NUMBER IS REFUSED, AND THE REFUSAL CARRIES THE NUMBER.
	 *
	 * @return void
	 */
	public function testMovingAnIssuedNumberIsRefused(): void {
		$event = $this->update(was: '2026-00042', now: '2026-00043');

		$this->assertTrue($event->isPropagationStopped());
		$this->assertStringContainsString('2026-00042', (string)$event->getErrors()['message']);
	}//end testMovingAnIssuedNumberIsRefused()

	/**
	 * The refusal says what to do instead, naming the withdrawal reason.
	 *
	 * "Refused" alone leaves somebody with a registration they cannot correct
	 * and no route forward, and the route is the whole point of
	 * `withdrawnReason` existing.
	 *
	 * @return void
	 */
	public function testTheRefusalNamesTheWayForward(): void {
		$message = (string)$this->update(was: '2026-00042', now: '2026-00043')->getErrors()['message'];

		$this->assertStringContainsString('withdrawnReason', $message);
	}//end testTheRefusalNamesTheWayForward()

	/**
	 * An update that leaves the number alone passes.
	 *
	 * The control. Without it, a guard that refused every update would pass the
	 * test above while making the schema unwritable.
	 *
	 * @return void
	 */
	public function testAnUpdateThatKeepsTheNumberPasses(): void {
		$this->assertFalse($this->update(was: '2026-00042', now: '2026-00042')->isPropagationStopped());
	}//end testAnUpdateThatKeepsTheNumberPasses()

	/**
	 * 🔑 A FIRST NUMBER MAY STILL LAND.
	 *
	 * The platform writes the number on create, and an entry saved before it had
	 * one may legitimately gain one. Refusing that would make the ordinary path
	 * impossible, which is how an over-eager guard takes down the feature it was
	 * written to protect.
	 *
	 * @return void
	 */
	public function testAFirstNumberMayStillBeIssued(): void {
		$this->assertFalse($this->update(was: '', now: '2026-00042')->isPropagationStopped());
	}//end testAFirstNumberMayStillBeIssued()

	/**
	 * Another schema's update is none of this guard's business.
	 *
	 * @return void
	 */
	public function testAnotherSchemaIsUntouched(): void {
		$this->assertFalse(
			$this->update(was: '2026-00042', now: '2026-00043', schema: 'template')->isPropagationStopped()
		);
	}//end testAnotherSchemaIsUntouched()

	/**
	 * A create carries no old object, so there is nothing to move.
	 *
	 * @return void
	 */
	public function testACreateIsNotAnUpdate(): void {
		$event = new ObjectUpdatingEvent($this->registration('documentRegistration', '2026-00042'), null);

		(new DocumentRegistrationWriteGuard(new NullLogger()))->handle($event);

		$this->assertFalse($event->isPropagationStopped());
	}//end testACreateIsNotAnUpdate()

	/**
	 * An unrelated event is ignored.
	 *
	 * @return void
	 */
	public function testAnUnrelatedEventIsIgnored(): void {
		(new DocumentRegistrationWriteGuard(new NullLogger()))->handle(new Event());

		$this->assertTrue(true);
	}//end testAnUnrelatedEventIsIgnored()
}//end class
