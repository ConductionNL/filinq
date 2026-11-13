<?php

/**
 * A house style managed by thematiq is changed only in thematiq.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
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
 * @spec openspec/changes/templates-house-style-from-thematiq/tasks.md#task-2.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\EventListener\ManagedHuisstijlWriteGuard;
use OCA\Filinq\Service\HuisstijlProfileMapper;
use OCA\Filinq\Service\HuisstijlWriteGate;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Event\ObjectUpdatingEvent;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @covers \OCA\Filinq\EventListener\ManagedHuisstijlWriteGuard
 * @uses   \OCA\Filinq\Service\HuisstijlWriteGate
 * @uses   \OCA\Filinq\Service\HuisstijlProfileMapper
 */
class ManagedHuisstijlWriteGuardTest extends TestCase {

	/**
	 * An object of a schema carrying some data.
	 *
	 * @param array<string, mixed> $data The data.
	 * @param string $schema The schema slug.
	 *
	 * @return ObjectEntity The object.
	 */
	private function object(array $data, string $schema = 'huisstijl'): ObjectEntity {
		$object = new ObjectEntity();
		$object->setUuid('hs-1');
		$object->setSchema($schema);
		$object->setObject($data);

		return $object;
	}//end object()

	/**
	 * Run the guard over an update.
	 *
	 * @param array<string, mixed> $old The stored data.
	 * @param array<string, mixed> $new The incoming data.
	 * @param string $schema The schema slug.
	 *
	 * @return ObjectUpdatingEvent The event afterwards.
	 */
	private function update(array $old, array $new, string $schema = 'huisstijl'): ObjectUpdatingEvent {
		$event = new ObjectUpdatingEvent($this->object($new, $schema), $this->object($old, $schema));
		(new ManagedHuisstijlWriteGuard(new HuisstijlWriteGate(), new HuisstijlProfileMapper(), new NullLogger()))->handle($event);

		return $event;
	}//end update()

	public function testAnEditorCannotChangeAManagedFooter(): void {
		$event = $this->update(
			['managedBy' => 'thematiq', 'footerHtml' => '<p>Bergen</p>'],
			['managedBy' => 'thematiq', 'footerHtml' => '<p>Anders</p>']
		);

		$this->assertTrue($event->isPropagationStopped());
		$this->assertSame(
			['message' => 'This house style follows thematiq; change it in Theming', 'property' => 'managedBy'],
			$event->getErrors()
		);
	}//end testAnEditorCannotChangeAManagedFooter()

	public function testDroppingTheManagedMarkIsRefusedToo(): void {
		$event = $this->update(['managedBy' => 'thematiq'], ['name' => 'Mijn eigen']);

		$this->assertTrue($event->isPropagationStopped());
	}//end testDroppingTheManagedMarkIsRefusedToo()

	public function testAHandMadeHouseStyleCannotBeMarkedManaged(): void {
		$event = $this->update(['name' => 'Eigen'], ['name' => 'Eigen', 'managedBy' => 'thematiq']);

		$this->assertTrue($event->isPropagationStopped());
	}//end testAHandMadeHouseStyleCannotBeMarkedManaged()

	public function testAHandMadeHouseStyleStaysEditable(): void {
		$event = $this->update(['name' => 'Eigen', 'footerHtml' => 'a'], ['name' => 'Eigen', 'footerHtml' => 'b']);

		$this->assertFalse($event->isPropagationStopped());
		$this->assertSame([], $event->getErrors());
	}//end testAHandMadeHouseStyleStaysEditable()

	public function testTheSyncsOwnWriteGoesThrough(): void {
		$gate = new HuisstijlWriteGate();
		$event = new ObjectUpdatingEvent($this->object(['managedBy' => 'thematiq', 'logo' => '/new']), $this->object(['managedBy' => 'thematiq', 'logo' => '/old']));
		$guard = new ManagedHuisstijlWriteGuard($gate, new HuisstijlProfileMapper(), new NullLogger());

		$gate->during(static fn () => $guard->handle($event));

		$this->assertFalse($event->isPropagationStopped());
	}//end testTheSyncsOwnWriteGoesThrough()

	public function testOtherSchemasAreLeftAlone(): void {
		$event = $this->update(['managedBy' => 'thematiq'], ['managedBy' => 'other'], 'template');

		$this->assertFalse($event->isPropagationStopped());
	}//end testOtherSchemasAreLeftAlone()

	public function testTheSchemaSlugIsMatchedWithoutCase(): void {
		$event = $this->update(['managedBy' => 'thematiq'], ['managedBy' => 'thematiq'], 'Huisstijl');

		$this->assertTrue($event->isPropagationStopped());
	}//end testTheSchemaSlugIsMatchedWithoutCase()

	public function testACreateHasNothingToGuard(): void {
		$event = new ObjectUpdatingEvent($this->object(['managedBy' => 'thematiq']), null);
		(new ManagedHuisstijlWriteGuard(new HuisstijlWriteGate(), new HuisstijlProfileMapper(), new NullLogger()))->handle($event);

		$this->assertFalse($event->isPropagationStopped());
	}//end testACreateHasNothingToGuard()

	public function testOtherEventsAreIgnored(): void {
		$event = new Event();
		(new ManagedHuisstijlWriteGuard(new HuisstijlWriteGate(), new HuisstijlProfileMapper(), new NullLogger()))->handle($event);

		$this->assertFalse($event->isPropagationStopped());
	}//end testOtherEventsAreIgnored()
}//end class
