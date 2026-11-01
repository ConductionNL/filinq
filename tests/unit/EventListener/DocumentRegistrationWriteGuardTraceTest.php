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
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Characterization of the registration-number write guard.
 *
 * @covers \OCA\Filinq\EventListener\DocumentRegistrationWriteGuard
 */
class DocumentRegistrationWriteGuardTraceTest extends TestCase {

	private LoggerInterface&MockObject $logger;

	/**
	 * The SchemaMapper double behind the last container built.
	 */
	private ?object $mapper = null;

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
	 * Numbers compare case-sensitively: "ZK-2026-1" to "zk-2026-1" is a move.
	 *
	 * @return void
	 */
	public function testChangingTheCaseOfANumberIsRefused(): void {
		$event = $this->guard(
			new: $this->entity(uuid: 'n', schema: 'documentRegistration', data: ['registrationNumber' => 'zk-2026-1']),
			old: $this->entity(uuid: 'o', schema: 'documentRegistration', data: ['registrationNumber' => 'ZK-2026-1'])
		);

		$this->assertTrue($event->isPropagationStopped());
		$this->assertSame('ZK-2026-1', $event->getErrors()['was']);

	}//end testChangingTheCaseOfANumberIsRefused()

	/**
	 * An event other than ObjectUpdatingEvent is ignored.
	 *
	 * @return void
	 */
	public function testOtherEventIsIgnored(): void {
		$this->logger->expects($this->never())->method('warning');
		$event = $this->createMock(Event::class);
		$event->expects($this->never())->method('stopPropagation');

		(new DocumentRegistrationWriteGuard($this->logger, $this->schemas()))->handle($event);

	}//end testOtherEventIsIgnored()

	/**
	 * A schema id that does not resolve is logged and the update passes.
	 *
	 * @return void
	 */
	public function testAnUnresolvedSchemaIdIsLogged(): void {
		$this->logger->expects($this->once())
			->method('warning')
			->with('filinq.post-register.schema-unresolved', ['schema' => '99', 'error' => 'Schema not found']);

		$event = $this->guard(
			new: $this->entity(uuid: 'n', schema: '99', data: ['registrationNumber' => 'B']),
			old: $this->entity(uuid: 'o', schema: '99', data: ['registrationNumber' => 'A'])
		);

		$this->assertFalse($event->isPropagationStopped());
		$this->assertCount(1, $this->mapper->calls);
	}//end testAnUnresolvedSchemaIdIsLogged()

	/**
	 * The stored side's schema id is resolved when the incoming side differs.
	 *
	 * @return void
	 */
	public function testTheStoredSchemaIdIsResolved(): void {
		$event = $this->guard(
			new: $this->entity(uuid: 'n', schema: '18', data: ['registrationNumber' => 'B']),
			old: $this->entity(uuid: 'o', schema: '17', data: ['registrationNumber' => 'A'])
		);

		$this->assertTrue($event->isPropagationStopped());
		$this->assertSame(['18', '17'], array_column($this->mapper->calls, 0));
	}//end testTheStoredSchemaIdIsResolved()

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
		(new DocumentRegistrationWriteGuard($this->logger, $this->schemas()))->handle($event);

		return $event;
	}//end guard()

	/**
	 * A container whose SchemaMapper knows schema 17 as `documentRegistration`
	 * and 18 as `template`, answers a slug with itself, and fails on any other id.
	 *
	 * The mapper double carries the real OpenRegister `find()` signature and
	 * records the arguments of each lookup in `$calls`.
	 *
	 * @return ContainerInterface The container.
	 */
	private function schemas(): ContainerInterface {
		$mapper = new class {
			/** @var list<array{string, bool, bool}> */
			public array $calls = [];

			/**
			 * Find a schema by id or slug.
			 *
			 * @param string|int $id            The id or slug.
			 * @param array|null $_extend       Unused.
			 * @param bool       $_rbac         Whether RBAC applies.
			 * @param bool       $_multitenancy Whether tenancy applies.
			 *
			 * @return object The schema.
			 */
			public function find(string|int $id, ?array $_extend = [], bool $_rbac = true, bool $_multitenancy = true): object {
				$this->calls[] = [(string)$id, $_rbac, $_multitenancy];
				$slug = ['17' => 'documentRegistration', '18' => 'template'][(string)$id] ?? null;
				if ($slug === null && is_numeric($id) === true) {
					throw new \RuntimeException('Schema not found');
				}

				return new class ($slug ?? (string)$id) {
					/**
					 * Constructor.
					 *
					 * @param string $slug The slug.
					 */
					public function __construct(private string $slug) {
					}

					/**
					 * The slug.
					 *
					 * @return string The slug.
					 */
					public function getSlug(): string {
						return $this->slug;
					}
				};
			}
		};
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->with('OCA\\OpenRegister\\Db\\SchemaMapper')->willReturn($mapper);
		$this->mapper = $mapper;

		return $container;
	}//end schemas()

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
