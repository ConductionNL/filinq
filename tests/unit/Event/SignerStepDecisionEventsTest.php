<?php

/**
 * Unit tests for the signer-step decision events
 *
 * Pins the payload of the approved and rejected signer-step events: every
 * constructed scalar comes back through its getter, nullable fields included,
 * and both stay Nextcloud events.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Event
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Event;

use OCA\Filinq\Event\SignerStepApprovedEvent;
use OCA\Filinq\Event\SignerStepRejectedEvent;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the signer-step decision events.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class SignerStepDecisionEventsTest extends TestCase {

	/**
	 * The approved event returns every constructed value.
	 *
	 * @return void
	 */
	public function testApprovedEventGetters(): void {
		$event = new SignerStepApprovedEvent('seq-1', 'task-1', 2, 'alice', 'fine', 'obj-1');

		$this->assertInstanceOf(Event::class, $event);
		$this->assertSame('seq-1', $event->getSequenceUuid());
		$this->assertSame('task-1', $event->getTaskUuid());
		$this->assertSame(2, $event->getPosition());
		$this->assertSame('alice', $event->getUserId());
		$this->assertSame('fine', $event->getComment());
		$this->assertSame('obj-1', $event->getObjectUuid());

	}//end testApprovedEventGetters()

	/**
	 * The rejected event returns every constructed value.
	 *
	 * @return void
	 */
	public function testRejectedEventGetters(): void {
		$event = new SignerStepRejectedEvent('seq-2', 'task-2', 3, 'bob', 'no', 'obj-2');

		$this->assertInstanceOf(Event::class, $event);
		$this->assertSame('seq-2', $event->getSequenceUuid());
		$this->assertSame('task-2', $event->getTaskUuid());
		$this->assertSame(3, $event->getPosition());
		$this->assertSame('bob', $event->getUserId());
		$this->assertSame('no', $event->getComment());
		$this->assertSame('obj-2', $event->getObjectUuid());

	}//end testRejectedEventGetters()

	/**
	 * The nullable identity and comment stay null when absent.
	 *
	 * @return void
	 */
	public function testNullableFieldsStayNull(): void {
		$approved = new SignerStepApprovedEvent('s', 't', 1, null, null, 'o');
		$rejected = new SignerStepRejectedEvent('s', 't', 1, null, null, 'o');

		$this->assertNull($approved->getUserId());
		$this->assertNull($approved->getComment());
		$this->assertNull($rejected->getUserId());
		$this->assertNull($rejected->getComment());

	}//end testNullableFieldsStayNull()
}//end class
