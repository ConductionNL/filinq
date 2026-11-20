<?php

/**
 * Unit tests for running a prompt through LocalTextAi and naming its provider
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\Extraction\AiPrompt;
use OCA\Filinq\Service\LocalTextAi;
use OCP\TaskProcessing\Task;
use OCP\TaskProcessing\TaskTypes\TextToText;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * The shared model call and the provider name.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class LocalTextAiRunTest extends TestCase {

	/**
	 * A container holding one TaskProcessing manager double.
	 *
	 * @param object $manager The manager.
	 *
	 * @return LocalTextAi
	 */
	private function ai(object $manager): LocalTextAi {
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(static function (string $id) use ($manager): object {
			if ($id === 'OCP\TaskProcessing\IManager') {
				return $manager;
			}

			throw new RuntimeException('no ' . $id);
		});

		return new LocalTextAi(container: $container);

	}//end ai()

	/**
	 * A TaskProcessing manager runs a TextToText task for the user and its output is returned.
	 *
	 * @return void
	 */
	public function testATaskProcessingRunReturnsTheOutput(): void {
		$manager = new class {
			/** @var Task|null The task run. */
			public ?Task $ran = null;

			/**
			 * Run.
			 *
			 * @param Task $task The task.
			 *
			 * @return Task
			 */
			public function runTask(Task $task): Task {
				$this->ran = $task;
				$task->setOutput(['output' => '{"a":1}']);
				return $task;
			}
		};

		$output = $this->ai($manager)->run(manager: ['type' => 'task', 'manager' => $manager], prompt: new AiPrompt(text: 'Read this', requestedBy: 'clerk'));

		$this->assertSame('{"a":1}', $output);
		$this->assertSame(['input' => 'Read this'], $manager->ran->getInput());

	}//end testATaskProcessingRunReturnsTheOutput()

	/**
	 * A TextProcessing manager's answer is returned as text.
	 *
	 * @return void
	 */
	public function testATextProcessingRunReturnsItsAnswer(): void {
		$manager = new class {
			/**
			 * Run.
			 *
			 * @param object $task The task.
			 *
			 * @return string
			 */
			public function runTask(object $task): string {
				return 'answer';
			}
		};

		$this->assertSame('answer', $this->ai($manager)->run(manager: ['type' => 'text', 'manager' => $manager], prompt: new AiPrompt(text: 'x', requestedBy: 'clerk')));

	}//end testATextProcessingRunReturnsItsAnswer()

	/**
	 * The provider name is the TextToText provider's; none, a blank name or a failure is null.
	 *
	 * @return void
	 */
	public function testTheProviderName(): void {
		$named = static fn (string $name): object => new class($name) {
			/**
			 * Constructor.
			 *
			 * @param string $name The name.
			 */
			public function __construct(private string $name) {
			}

			/**
			 * The provider.
			 *
			 * @param string $type The task type.
			 *
			 * @return object
			 */
			public function getPreferredProvider(string $type): object {
				if ($this->name === 'fail' || $type !== TextToText::ID) {
					throw new RuntimeException('no provider');
				}

				$name = $this->name;
				return new class($name) {
					/**
					 * Constructor.
					 *
					 * @param string $name The name.
					 */
					public function __construct(private string $name) {
					}

					/**
					 * Name.
					 *
					 * @return string
					 */
					public function getName(): string {
						return $this->name;
					}
				};
			}
		};

		$this->assertSame('Local LLM', $this->ai($named(' Local LLM '))->textProviderName());
		$this->assertNull($this->ai($named(''))->textProviderName());
		$this->assertNull($this->ai($named('fail'))->textProviderName());

	}//end testTheProviderName()
}//end class
