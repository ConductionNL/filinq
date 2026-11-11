<?php

/**
 * Unit tests for how the financial extraction and the GL account suggestion
 * reach the local AI manager and read its answer
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\FinancialExtractionService;
use OCA\Filinq\Service\GlAccountSuggestionService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use RuntimeException;

/**
 * Pins the manager preference (TaskProcessing, then TextProcessing, then
 * none) and the code-fence stripping of both AI-assisted services.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class LocalAiManagerCharacterisationTest extends TestCase {

	/**
	 * Both services that use the local AI manager.
	 *
	 * @return array<string,array{0:class-string}>
	 */
	public static function services(): array {
		return [
			'financial extraction'  => [FinancialExtractionService::class],
			'gl account suggestion' => [GlAccountSuggestionService::class],
		];

	}//end services()

	/**
	 * The TaskProcessing manager wins when the container has it.
	 *
	 * @param class-string $class The service under test.
	 *
	 * @return void
	 */
	#[DataProvider('services')]
	public function testTaskProcessingIsPreferred(string $class): void {
		$task = new \stdClass();
		$this->assertSame(
			['type' => 'task', 'manager' => $task],
			$this->call($this->serviceWith($class, ['OCP\\TaskProcessing\\IManager' => $task, 'OCP\\TextProcessing\\IManager' => new \stdClass()]), ['resolveAiManager'])
		);

	}//end testTaskProcessingIsPreferred()

	/**
	 * A failing TaskProcessing lookup falls back to TextProcessing.
	 *
	 * @param class-string $class The service under test.
	 *
	 * @return void
	 */
	#[DataProvider('services')]
	public function testTextProcessingIsTheFallback(string $class): void {
		$text = new \stdClass();
		$this->assertSame(
			['type' => 'text', 'manager' => $text],
			$this->call($this->serviceWith($class, ['OCP\\TextProcessing\\IManager' => $text]), ['resolveAiManager'])
		);

	}//end testTextProcessingIsTheFallback()

	/**
	 * With neither manager resolvable there is no AI.
	 *
	 * @param class-string $class The service under test.
	 *
	 * @return void
	 */
	#[DataProvider('services')]
	public function testNoManagerIsNull(string $class): void {
		$this->assertNull($this->call($this->serviceWith($class, []), ['resolveAiManager']));

	}//end testNoManagerIsNull()

	/**
	 * A fenced answer is unwrapped, with or without the json tag; an
	 * unfenced one is only trimmed.
	 *
	 * @param class-string $class The service under test.
	 *
	 * @return void
	 */
	#[DataProvider('services')]
	public function testCodeFencesAreStripped(string $class): void {
		$service = $this->serviceWith($class, []);
		$this->assertSame('{"a":1}', $this->call($service, ['stripCodeFences', "  ```json\n{\"a\":1}\n```  "]));
		$this->assertSame('x y', $this->call($service, ['stripCodeFences', "```\n x y \n```"]));
		$this->assertSame('{"order":[]} tail', $this->call($service, ['stripCodeFences', " {\"order\":[]} tail \n"]));
		$this->assertSame('```json {"a":1}', $this->call($service, ['stripCodeFences', '```json {"a":1}']));

	}//end testCodeFencesAreStripped()

	/**
	 * Build the service with only a container that holds the given entries;
	 * every other lookup throws.
	 *
	 * @param class-string         $class   The service under test.
	 * @param array<string,object> $entries The container entries.
	 *
	 * @return object The service.
	 */
	private function serviceWith(string $class, array $entries): object {
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			static function (string $id) use ($entries): object {
				if (isset($entries[$id]) === true) {
					return $entries[$id];
				}

				throw new RuntimeException('no ' . $id);
			}
		);

		$service = (new ReflectionClass($class))->newInstanceWithoutConstructor();
		(function (ContainerInterface $container): void {
			$this->container = $container;
		})->call($service, $container);

		return $service;

	}//end serviceWith()

	/**
	 * Call a private method of the service.
	 *
	 * @param object           $service The service under test.
	 * @param array<int,mixed> $call    The method name followed by its arguments.
	 *
	 * @return mixed What the method returns.
	 */
	private function call(object $service, array $call): mixed {
		$method = array_shift($call);
		return (new ReflectionClass($service))->getMethod($method)->invoke($service, ...$call);

	}//end call()

}//end class
