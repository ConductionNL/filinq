<?php

/**
 * Unit tests for ContractNoticeDeadlineListener
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#4-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\EventListener\ContractNoticeDeadlineListener;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Event\ObjectCreatingEvent;
use OCA\OpenRegister\Event\ObjectUpdatingEvent;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * A contract saved without a notice deadline gets one; nothing else does.
 */
class ContractNoticeDeadlineListenerTest extends TestCase {

	/**
	 * An object entity with data and a schema reference.
	 *
	 * @param array<string, mixed> $data   The object data.
	 * @param string               $schema The schema reference.
	 *
	 * @return ObjectEntity The entity.
	 */
	private function entity(array $data, string $schema): ObjectEntity {
		$entity = new ObjectEntity();
		$entity->setObject($data);
		$entity->setSchema($schema);

		return $entity;

	}//end entity()

	/**
	 * The listener with a container whose SchemaMapper says schema 17 is `contract`.
	 *
	 * @return ContractNoticeDeadlineListener The listener.
	 */
	private function listener(): ContractNoticeDeadlineListener {
		$schema = new class {
			/**
			 * The slug.
			 *
			 * @return string The slug.
			 */
			public function getSlug(): string {
				return 'contract';
			}
		};
		$mapper = new class ($schema) {
			/**
			 * Constructor.
			 *
			 * @param object $schema The schema to return for id 17.
			 */
			public function __construct(private object $schema) {
			}

			/**
			 * Find by id.
			 *
			 * @param string $id The id.
			 *
			 * @return object The schema.
			 */
			public function find(string $id): object {
				if ($id !== '17') {
					throw new \RuntimeException('not found');
				}

				return $this->schema;
			}
		};
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($mapper);

		return new ContractNoticeDeadlineListener(container: $container);

	}//end listener()

	/**
	 * Create and update both get the deadline, by schema id or slug.
	 *
	 * @return void
	 */
	public function testAContractGetsItsNoticeDeadline(): void {
		$data = ['title' => 'Schoonmaak', 'status' => 'active', 'endDate' => '2026-12-31', 'noticePeriodDays' => 72];

		$creating = new ObjectCreatingEvent($this->entity(data: $data, schema: '17'));
		$this->listener()->handle($creating);
		$this->assertSame(['noticeDeadline' => '2026-10-20'], $creating->getModifiedData());

		$updating = new ObjectUpdatingEvent($this->entity(data: $data, schema: 'contract'), $this->entity(data: [], schema: 'contract'));
		$this->listener()->handle($updating);
		$this->assertSame(['noticeDeadline' => '2026-10-20'], $updating->getModifiedData());

	}//end testAContractGetsItsNoticeDeadline()

	/**
	 * A deadline somebody entered stays, and another schema is left alone.
	 *
	 * @return void
	 */
	public function testAManualDeadlineAndOtherSchemasAreLeftAlone(): void {
		$manual = new ObjectCreatingEvent($this->entity(data: ['endDate' => '2026-12-31', 'noticePeriodDays' => 72, 'noticeDeadline' => '2026-09-01'], schema: '17'));
		$this->listener()->handle($manual);
		$this->assertSame([], $manual->getModifiedData());

		$other = new ObjectCreatingEvent($this->entity(data: ['endDate' => '2026-12-31', 'noticePeriodDays' => 72], schema: '5'));
		$this->listener()->handle($other);
		$this->assertSame([], $other->getModifiedData());

	}//end testAManualDeadlineAndOtherSchemasAreLeftAlone()
}//end class
