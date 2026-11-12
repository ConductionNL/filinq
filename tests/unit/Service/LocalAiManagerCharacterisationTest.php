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

use OCA\Filinq\Service\LocalTextAi;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Pins the manager preference (TaskProcessing, then TextProcessing, then
 * none) and the code-fence stripping the AI-assisted services share.
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
	 * The TaskProcessing manager wins when the container has it.
	 *
	 * @return void
	 */
	public function testTaskProcessingIsPreferred(): void {
		$task = new \stdClass();
		$this->assertSame(
			['type' => 'task', 'manager' => $task],
			$this->aiWith(['OCP\\TaskProcessing\\IManager' => $task, 'OCP\\TextProcessing\\IManager' => new \stdClass()])->manager()
		);

	}//end testTaskProcessingIsPreferred()

	/**
	 * A failing TaskProcessing lookup falls back to TextProcessing.
	 *
	 * @return void
	 */
	public function testTextProcessingIsTheFallback(): void {
		$text = new \stdClass();
		$this->assertSame(
			['type' => 'text', 'manager' => $text],
			$this->aiWith(['OCP\\TextProcessing\\IManager' => $text])->manager()
		);

	}//end testTextProcessingIsTheFallback()

	/**
	 * With neither manager resolvable there is no AI.
	 *
	 * @return void
	 */
	public function testNoManagerIsNull(): void {
		$this->assertNull($this->aiWith([])->manager());

	}//end testNoManagerIsNull()

	/**
	 * A fenced answer is unwrapped, with or without the json tag; an
	 * unfenced one is only trimmed.
	 *
	 * @return void
	 */
	public function testCodeFencesAreStripped(): void {
		$ai = $this->aiWith([]);
		$this->assertSame('{"a":1}', $ai->stripCodeFences("  ```json\n{\"a\":1}\n```  "));
		$this->assertSame('x y', $ai->stripCodeFences("```\n x y \n```"));
		$this->assertSame('{"order":[]} tail', $ai->stripCodeFences(" {\"order\":[]} tail \n"));
		$this->assertSame('```json {"a":1}', $ai->stripCodeFences('```json {"a":1}'));

	}//end testCodeFencesAreStripped()

	/**
	 * Build the local AI access over a container that holds the given
	 * entries; every other lookup throws.
	 *
	 * @param array<string,object> $entries The container entries.
	 *
	 * @return LocalTextAi The local AI access.
	 */
	private function aiWith(array $entries): LocalTextAi {
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			static function (string $id) use ($entries): object {
				if (isset($entries[$id]) === true) {
					return $entries[$id];
				}

				throw new RuntimeException('no ' . $id);
			}
		);

		return new LocalTextAi(container: $container);

	}//end aiWith()

}//end class
