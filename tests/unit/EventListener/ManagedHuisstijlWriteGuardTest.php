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
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * @covers \OCA\Filinq\EventListener\ManagedHuisstijlWriteGuard
 * @uses   \OCA\Filinq\Service\HuisstijlWriteGate
 * @uses   \OCA\Filinq\Service\HuisstijlProfileMapper
 */
class ManagedHuisstijlWriteGuardTest extends TestCase {

	/**
	 * The guard, with a SchemaMapper that knows schema 41 as `huisstijl` and 42 as `template`.
	 *
	 * @param HuisstijlWriteGate|null $gate The gate, or a fresh one.
	 * @param bool $lookupFails Whether every schema lookup fails.
	 *
	 * @return ManagedHuisstijlWriteGuard The guard.
	 */
	private function guard(?HuisstijlWriteGate $gate = null, bool $lookupFails = false): ManagedHuisstijlWriteGuard {
		$mapper = new class ($lookupFails) {
			/**
			 * @param bool $fails Whether every lookup fails.
			 */
			public function __construct(private readonly bool $fails) {
			}

			/**
			 * @param int|string $id The schema id.
			 *
			 * @return object The schema.
			 */
			public function find(int|string $id): object {
				if ($this->fails === true) {
					throw new RuntimeException('database gone');
				}

				$slugs = ['41' => 'huisstijl', '42' => 'template'];
				if (isset($slugs[(string) $id]) === false) {
					throw new DoesNotExistException('Schema not found');
				}

				return new class ($slugs[(string) $id]) {
					/**
					 * @param string $slug The schema slug.
					 */
					public function __construct(private readonly string $slug) {
					}

					/**
					 * @return string
					 */
					public function getSlug(): string {
						return $this->slug;
					}
				};
			}
		};

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->with('OCA\OpenRegister\Db\SchemaMapper')->willReturn($mapper);

		return new ManagedHuisstijlWriteGuard(($gate ?? new HuisstijlWriteGate()), new HuisstijlProfileMapper(), new NullLogger(), $container);
	}//end guard()

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
		$this->guard()->handle($event);

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
		$guard = $this->guard($gate);

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

	public function testTheStoredSchemaIdIsResolvedToTheHouseStyle(): void {
		$event = $this->update(['managedBy' => 'thematiq', 'footerHtml' => 'a'], ['managedBy' => 'thematiq', 'footerHtml' => 'b'], '41');

		$this->assertTrue($event->isPropagationStopped());
		$this->assertSame(ManagedHuisstijlWriteGuard::MESSAGE, $event->getErrors()['message']);
	}//end testTheStoredSchemaIdIsResolvedToTheHouseStyle()

	public function testAnotherSchemaIdIsLeftAlone(): void {
		$event = $this->update(['managedBy' => 'thematiq'], ['managedBy' => 'thematiq'], '42');

		$this->assertFalse($event->isPropagationStopped());
	}//end testAnotherSchemaIdIsLeftAlone()

	public function testAnUnknownSchemaIdIsLeftAlone(): void {
		$event = $this->update(['managedBy' => 'thematiq'], ['managedBy' => 'thematiq'], '99');

		$this->assertFalse($event->isPropagationStopped());
	}//end testAnUnknownSchemaIdIsLeftAlone()

	public function testAFailedSchemaLookupRefusesAManagedWrite(): void {
		$event = new ObjectUpdatingEvent($this->object(['managedBy' => 'thematiq'], '41'), $this->object(['managedBy' => 'thematiq'], '41'));
		$this->guard(null, true)->handle($event);

		$this->assertTrue($event->isPropagationStopped());
	}//end testAFailedSchemaLookupRefusesAManagedWrite()

	public function testAHandMadeHouseStyleCostsNoSchemaLookup(): void {
		$event = new ObjectUpdatingEvent($this->object(['name' => 'b'], '41'), $this->object(['name' => 'a'], '41'));
		$this->guard(null, true)->handle($event);

		$this->assertFalse($event->isPropagationStopped());
	}//end testAHandMadeHouseStyleCostsNoSchemaLookup()

	public function testACreateHasNothingToGuard(): void {
		$event = new ObjectUpdatingEvent($this->object(['managedBy' => 'thematiq']), null);
		$this->guard()->handle($event);

		$this->assertFalse($event->isPropagationStopped());
	}//end testACreateHasNothingToGuard()

	public function testOtherEventsAreIgnored(): void {
		$event = new Event();
		$this->guard()->handle($event);

		$this->assertFalse($event->isPropagationStopped());
	}//end testOtherEventsAreIgnored()
}//end class
