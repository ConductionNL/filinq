<?php

/**
 * Tests for ValidationRunner: the validation verdict fallback on object events.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-validation-checks/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\EventListener\ValidationRunner;
use OCA\Filinq\Kernel\Contracts\DocumentValidationServiceInterface;
use OCA\Filinq\Kernel\Contracts\MetadataServiceInterface;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Event\ObjectCreatedEvent;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\AbstractLogger;
use RuntimeException;

/**
 * What the fallback validates, writes and logs, per outcome.
 *
 * @covers \OCA\Filinq\EventListener\ValidationRunner
 */
class ValidationRunnerTest extends TestCase {

	/**
	 * The system writes: [uuid, register, schema, fields].
	 *
	 * @var array<int, array<int, mixed>>
	 */
	private array $saves = [];

	/**
	 * The validate calls: [file, record, documentType].
	 *
	 * @var array<int, array<int, mixed>>
	 */
	private array $validations = [];

	/**
	 * Lines logged: [level, message, context].
	 *
	 * @var array<int, array<int, mixed>>
	 */
	private array $lines = [];

	/**
	 * Run the fallback for one event.
	 *
	 * @param Event                $event   The event.
	 * @param array<string, mixed> $options nodes (what getById answers), saveFails, containerFails.
	 *
	 * @return void
	 */
	private function fallback(Event $event, array $options = []): void {
		$validation = $this->createMock(DocumentValidationServiceInterface::class);
		$validation->method('validate')->willReturnCallback(function (...$arguments): array {
			$this->validations[] = $arguments;
			return ['validationStatus' => 'warning', 'validationFindings' => [['rule' => 'r1']], 'extra' => 'ignored'];
		});

		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->willReturnCallback(fn (int $id): array => ($options['nodes'] ?? [$this->file]));
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willReturnCallback(function (string $uid) use ($folder): Folder {
			$this->lines[] = ['folder', $uid, []];
			return $folder;
		});

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(function (string $id) use ($validation, $root, $options): object {
			if (($options['containerFails'] ?? false) === true) {
				throw new RuntimeException('no such service');
			}

			return $id === IRootFolder::class ? $root : $validation;
		});

		$metadata = $this->createMock(MetadataServiceInterface::class);
		$metadata->method('saveEnrichedMetadataAsSystem')->willReturnCallback(function (...$arguments) use ($options): array {
			if (($options['saveFails'] ?? false) === true) {
				throw new RuntimeException('register down');
			}

			$this->saves[] = $arguments;
			return [];
		});

		$logger = new class($this->lines) extends AbstractLogger {
			public function __construct(private array &$lines) {
			}

			public function log($level, string|\Stringable $message, array $context = []): void {
				$this->lines[] = [(string)$level, (string)$message, $context];
			}
		};

		(new ValidationRunner(container: $container))->runFallbackForEvent(event: $event, metadataService: $metadata, logger: $logger);

	}//end fallback()

	/**
	 * The file the object points at.
	 *
	 * @var File
	 */
	private File $file;

	/**
	 * An object pointing at file 42, owned by alice.
	 *
	 * @param array<string, mixed> $data  The object data.
	 * @param string|null          $owner The owner.
	 *
	 * @return ObjectEntity The object.
	 */
	private function object(array $data = ['fileId' => 42, 'documentType' => 'besluit'], ?string $owner = 'alice'): ObjectEntity {
		$this->file = $this->createMock(File::class);
		$object = new ObjectEntity();
		$object->setUuid('obj-1');
		$object->setRegister('7');
		$object->setSchema('9');
		$object->setOwner($owner);
		$object->setObject($data);

		return $object;

	}//end object()

	/**
	 * A created object's file is validated and the verdict written as system and logged.
	 *
	 * @return void
	 */
	public function testAVerdictIsStoredAndLogged(): void {
		$object = $this->object();

		$this->fallback(event: new ObjectCreatedEvent($object));

		$this->assertSame([[$this->file, ['fileId' => 42, 'documentType' => 'besluit'], 'besluit']], $this->validations);
		$this->assertSame([['obj-1', '7', '9', ['validationStatus' => 'warning', 'validationFindings' => [['rule' => 'r1']]]]], $this->saves);
		$this->assertSame(
			[
				['folder', 'alice', []],
				['info', 'Filinq: Validation verdict stored for validation fallback', ['objectId' => 'obj-1', 'status' => 'warning']],
			],
			$this->lines
		);

	}//end testAVerdictIsStoredAndLogged()

	/**
	 * An updated object is validated in its new state; no document type passes null.
	 *
	 * @return void
	 */
	public function testAnUpdatedObjectIsValidatedInItsNewState(): void {
		$old = $this->object(data: ['fileId' => 1]);
		$new = $this->object(data: ['fileId' => 42]);

		$this->fallback(event: new ObjectUpdatedEvent($new, $old));

		$this->assertSame([[$this->file, ['fileId' => 42], null]], $this->validations);
		$this->assertCount(1, $this->saves);

	}//end testAnUpdatedObjectIsValidatedInItsNewState()

	/**
	 * No file id, no owner, no node or a folder instead of a file: nothing is validated or written.
	 *
	 * @return void
	 */
	public function testNothingToValidateIsSilent(): void {
		$this->fallback(event: new ObjectCreatedEvent($this->object(data: ['fileId' => 0])));
		$this->fallback(event: new ObjectCreatedEvent($this->object(owner: '')));
		$this->fallback(event: new ObjectCreatedEvent($this->object()), options: ['nodes' => []]);
		$this->fallback(event: new ObjectCreatedEvent($this->object()), options: ['nodes' => [$this->createMock(Folder::class)]]);

		$this->assertSame([], $this->validations);
		$this->assertSame([], $this->saves);
		$this->assertSame([['folder', 'alice', []], ['folder', 'alice', []]], $this->lines);

	}//end testNothingToValidateIsSilent()

	/**
	 * A failed write is an error naming the context; a missing service a debug line.
	 *
	 * @return void
	 */
	public function testFailuresAreLoggedNotThrown(): void {
		$this->fallback(event: new ObjectCreatedEvent($this->object()), options: ['saveFails' => true]);
		$this->fallback(event: new ObjectCreatedEvent($this->object()), options: ['containerFails' => true]);

		$this->assertSame(
			[
				['folder', 'alice', []],
				['error', 'Filinq: Failed to validate object for validation fallback', ['exception' => 'register down']],
				['debug', 'Filinq: validation fallback skipped: no such service', []],
			],
			$this->lines
		);

	}//end testFailuresAreLoggedNotThrown()

	/**
	 * Another event, or an event without an object, does nothing.
	 *
	 * @return void
	 */
	public function testOtherEventsDoNothing(): void {
		$this->fallback(event: new Event());
		$this->fallback(event: new ObjectCreatedEvent(null));

		$this->assertSame([[], [], []], [$this->validations, $this->saves, $this->lines]);

	}//end testOtherEventsDoNothing()
}//end class
