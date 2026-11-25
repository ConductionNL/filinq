<?php

/**
 * Unit tests for ObjectTypeBindingProvisioner
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use JsonSerializable;
use OCA\Filinq\Service\ObjectTypeBindingProvisioner;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * The provisioner writes the object type bindings nobody has set.
 *
 * @spec openspec/specs/admin-settings/spec.md#requirement-auto-initialization-on-boot-req-set-03
 */
class ObjectTypeBindingProvisionerTest extends TestCase {

	/**
	 * The app config.
	 *
	 * @var array<string, string>
	 */
	private array $values = [];

	/**
	 * A JSON-serialisable row, as OpenRegister's entities are.
	 *
	 * @param array<string, mixed> $data The serialised form.
	 *
	 * @return JsonSerializable
	 */
	private static function row(array $data): JsonSerializable {
		return new class($data) implements JsonSerializable {
			/**
			 * Constructor.
			 *
			 * @param array<string, mixed> $data The serialised form.
			 */
			public function __construct(private readonly array $data) {
			}

			/**
			 * The serialised form.
			 *
			 * @return array<string, mixed>
			 */
			public function jsonSerialize(): array {
				return $this->data;
			}
		};

	}//end row()

	/**
	 * A provisioner over the given registers and schemas.
	 *
	 * @param array<int, JsonSerializable>|null $registers The registers; null makes the register service fail.
	 * @param array<int, JsonSerializable>      $schemas   Schemas by id; an absent id throws.
	 *
	 * @return ObjectTypeBindingProvisioner
	 */
	private function provisioner(?array $registers, array $schemas): ObjectTypeBindingProvisioner {
		$config = $this->createMock(originalClassName: IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			fn (string $app, string $key, string $default=''): string => ($this->values[$key] ?? $default)
		);
		$config->method('setValueString')->willReturnCallback(
			function (string $app, string $key, string $value): bool {
				$this->values[$key] = $value;
				return true;
			}
		);

		$registerService = new class($registers) {
			/**
			 * Constructor.
			 *
			 * @param array<int, JsonSerializable>|null $registers The registers.
			 */
			public function __construct(private readonly ?array $registers) {
			}

			/**
			 * All registers.
			 *
			 * @param mixed ...$arguments The paging and filter arguments, ignored.
			 *
			 * @return array<int, JsonSerializable>
			 */
			public function findAll(mixed ...$arguments): array {
				if ($this->registers === null) {
					throw new Exception(message: 'registers unavailable');
				}

				return $this->registers;
			}
		};
		$schemaMapper = new class($schemas) {
			/**
			 * Constructor.
			 *
			 * @param array<int, JsonSerializable> $schemas Schemas by id.
			 */
			public function __construct(private readonly array $schemas) {
			}

			/**
			 * One schema.
			 *
			 * @param int|string $id The schema id.
			 *
			 * @return JsonSerializable
			 */
			public function find(int|string $id): JsonSerializable {
				if (isset($this->schemas[(int) $id]) === false) {
					throw new Exception(message: 'no schema ' . $id);
				}

				return $this->schemas[(int) $id];
			}
		};

		$container = $this->createMock(originalClassName: ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			static fn (string $id): object => match ($id) {
				'OCA\OpenRegister\Service\RegisterService' => $registerService,
				'OCA\OpenRegister\Db\SchemaMapper' => $schemaMapper,
			}
		);

		return new ObjectTypeBindingProvisioner(
			config: $config,
			container: $container,
			logger: $this->createMock(originalClassName: LoggerInterface::class)
		);

	}//end provisioner()

	/**
	 * Every schema of the app's register gets a binding; other registers are ignored.
	 *
	 * @return void
	 */
	public function testEverySchemaOfTheAppsRegisterIsBound(): void {
		$written = $this->provisioner(
			registers: [
				self::row(data: ['id' => 3, 'slug' => 'contacts', 'schemas' => [30]]),
				self::row(data: ['id' => 26, 'slug' => 'filinq', 'schemas' => [42, 43, 99]]),
			],
			schemas: [
				30 => self::row(data: ['id' => 30, 'slug' => 'contact']),
				42 => self::row(data: ['id' => 42, 'slug' => 'template']),
				43 => self::row(data: ['id' => 43, 'slug' => 'textFragment']),
			]
		)->provision();

		$this->assertSame(expected: 2, actual: $written, message: 'the unreadable schema 99 is skipped');
		$this->assertSame(
			expected: [
				'template_register' => '26',
				'template_schema' => '42',
				'template_source' => 'openregister',
				'textFragment_register' => '26',
				'textFragment_schema' => '43',
				'textFragment_source' => 'openregister',
			],
			actual: $this->values
		);

	}//end testEverySchemaOfTheAppsRegisterIsBound()

	/**
	 * A binding an administrator chose is never changed.
	 *
	 * @return void
	 */
	public function testAChosenBindingIsLeftAlone(): void {
		$this->values = ['template_register' => '7', 'template_schema' => '70'];

		$written = $this->provisioner(
			registers: [self::row(data: ['id' => 26, 'slug' => 'filinq', 'schemas' => [42]])],
			schemas: [42 => self::row(data: ['id' => 42, 'slug' => 'template'])]
		)->provision();

		$this->assertSame(expected: 0, actual: $written);
		$this->assertSame(expected: ['template_register' => '7', 'template_schema' => '70'], actual: $this->values);

	}//end testAChosenBindingIsLeftAlone()

	/**
	 * Without the app's register, or when OpenRegister cannot be read, the
	 * answer is null so the caller tries again, and nothing is written.
	 *
	 * @return void
	 */
	public function testWithoutTheRegisterTheAnswerIsNull(): void {
		$absent = $this->provisioner(registers: [self::row(data: ['id' => 3, 'slug' => 'contacts', 'schemas' => []])], schemas: [])->provision();
		$failing = $this->provisioner(registers: null, schemas: [])->provision();

		$this->assertNull(actual: $absent);
		$this->assertNull(actual: $failing);
		$this->assertSame(expected: [], actual: $this->values);

	}//end testWithoutTheRegisterTheAnswerIsNull()
}//end class
