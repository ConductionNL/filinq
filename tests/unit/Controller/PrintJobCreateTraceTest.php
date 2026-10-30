<?php

/**
 * Trace tests for the create and batch print endpoints: the answer, the service call and the log.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/print-functionality/tasks.md#task-5
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use Exception;
use OCA\Filinq\Controller\PrintJobController;
use OCA\Filinq\Kernel\Contracts\PrintJobServiceInterface;
use OCA\Filinq\Kernel\PrintJobRequest;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Stringable;

/**
 * Every path through create() and batch(), compared with JSON recorded from
 * the implementation before they were split.
 *
 * @covers \OCA\Filinq\Controller\PrintJobController
 * @uses   \OCA\Filinq\Kernel\PrintJobRequest
 */
class PrintJobCreateTraceTest extends TestCase {

	/**
	 * What the service was asked.
	 *
	 * @var array<int, array<int, mixed>>
	 */
	private array $calls = [];

	/**
	 * The scenarios: action, logged in or not, request parameters, and the service's failure code or null.
	 *
	 * @return array<string, array{0: string, 1: bool, 2: array<string, mixed>, 3: int|null}>
	 */
	public static function scenarios(): array {
		$items = [['data' => ['naam' => 'Jansen'], 'filename' => 'a.pdf'], ['data' => []]];
		$batch = [];
		foreach (self::batchScenarios($items) as $name => $scenario) {
			$batch['batch: ' . $name] = ['batch', ...$scenario];
		}

		$create = [
			'nobody logged in' => [false, ['templateId' => 't-1'], null],
			'a forged owner' => [true, ['templateId' => 't-1', 'userId' => 'boss', 'requestedBy' => 'boss'], null],
			'no template' => [true, [], null],
			'template "0"' => [true, ['templateId' => '0'], null],
			'data and options not arrays' => [true, ['templateId' => 't-1', 'data' => 'x', 'options' => 'duplex'], null],
			'everything' => [true, ['templateId' => 't-1', 'data' => ['naam' => 'Jansen'], 'options' => ['duplex' => true], 'filename' => 'brief.pdf'], null],
			'a numeric file name' => [true, ['templateId' => 't-1', 'filename' => 7], null],
			'the service refuses with 422' => [true, ['templateId' => 't-1'], 422],
			'the service fails with 400' => [true, ['templateId' => 't-1'], 400],
			'the service fails with 599' => [true, ['templateId' => 't-1'], 599],
			'the service fails with 600' => [true, ['templateId' => 't-1'], 600],
			'the service fails with 399' => [true, ['templateId' => 't-1'], 399],
		];
		foreach ($create as $name => $scenario) {
			$batch['create: ' . $name] = ['create', ...$scenario];
		}

		return $batch;
	}//end scenarios()

	/**
	 * The batch scenarios.
	 *
	 * @param array<int, array<string, mixed>> $items A valid item list.
	 *
	 * @return array<string, array{0: bool, 1: array<string, mixed>, 2: int|null}>
	 */
	private static function batchScenarios(array $items): array {
		return [
			'nobody logged in' => [false, ['templateId' => 't-1', 'items' => $items], null],
			'a forged owner' => [true, ['templateId' => 't-1', 'items' => $items, 'userId' => 'boss', 'requestedBy' => 'boss'], null],
			'no template' => [true, ['items' => $items], null],
			'template "0"' => [true, ['templateId' => '0', 'items' => $items], null],
			'no items' => [true, ['templateId' => 't-1'], null],
			'items not a list' => [true, ['templateId' => 't-1', 'items' => 'a,b'], null],
			'empty items' => [true, ['templateId' => 't-1', 'items' => []], null],
			'options not an array' => [true, ['templateId' => 't-1', 'items' => $items, 'options' => 'duplex'], null],
			'everything' => [true, ['templateId' => 't-1', 'items' => $items, 'options' => ['duplex' => true], 'filename' => 'brieven.pdf'], null],
			'a numeric template id' => [true, ['templateId' => 12, 'items' => $items], null],
			'the service refuses with 422' => [true, ['templateId' => 't-1', 'items' => $items], 422],
			'the service fails with 400' => [true, ['templateId' => 't-1', 'items' => $items], 400],
			'the service fails with 599' => [true, ['templateId' => 't-1', 'items' => $items], 599],
			'the service fails with 600' => [true, ['templateId' => 't-1', 'items' => $items], 600],
			'the service fails with 399' => [true, ['templateId' => 't-1', 'items' => $items], 399],
			'the service fails with 0' => [true, ['templateId' => 't-1', 'items' => $items], 0],
		];
	}//end scenarios()

	/**
	 * The trace matches the recorded one.
	 *
	 * @param string               $action   create or batch.
	 * @param bool                 $loggedIn Whether somebody is logged in.
	 * @param array<string, mixed> $params   The request parameters.
	 * @param int|null             $failure  The code the service fails with, or null.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testTrace(string $action, bool $loggedIn, array $params, ?int $failure): void {
		$logger = new class extends AbstractLogger {
			/**
			 * @var array<int, array<int, mixed>>
			 */
			public array $lines = [];

			/**
			 * Record one line, with the exception's class and code.
			 *
			 * @param mixed             $level   The level.
			 * @param string|Stringable $message The message.
			 * @param array<mixed>      $context The context.
			 *
			 * @return void
			 */
			public function log($level, string|Stringable $message, array $context = []): void {
				$exception = $context['exception'] ?? null;
				$context['exception'] = $exception === null ? null : [$exception::class, $exception->getCode()];
				$this->lines[] = [$level, (string)$message, $context];
			}
		};

		$response = $this->controller($loggedIn, $params, $failure, $logger)->$action();
		$trace = ['status' => $response->getStatus(), 'data' => $response->getData(), 'calls' => $this->calls, 'logs' => $logger->lines];

		$path = __DIR__ . '/golden/print-job-create-trace.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $trace;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $trace);
	}//end testTrace()

	/**
	 * The controller over recording doubles.
	 *
	 * @param bool                 $loggedIn Whether somebody is logged in.
	 * @param array<string, mixed> $params   The request parameters.
	 * @param int|null             $failure  The code the service fails with, or null.
	 * @param AbstractLogger       $logger   The logger.
	 *
	 * @return PrintJobController
	 */
	private function controller(bool $loggedIn, array $params, ?int $failure, AbstractLogger $logger): PrintJobController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(
			static fn (string $key, $default = null) => $params[$key] ?? $default
		);

		$session = $this->createMock(IUserSession::class);
		$user = null;
		if ($loggedIn === true) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn('anna');
		}

		$session->method('getUser')->willReturn($user);

		$jobs = $this->createMock(PrintJobServiceInterface::class);
		$jobs->method('createBatchJob')->willReturnCallback(
			function (PrintJobRequest $request, array $items, string $userId) use ($failure): array {
				$this->calls[] = ['createBatchJob', [$request->templateId, $request->options, $request->filename], $items, $userId];
				if ($failure !== null) {
					throw new Exception('batch refused', $failure);
				}

				return ['batchId' => 'batch-1', 'count' => count($items)];
			}
		);
		$jobs->method('createJob')->willReturnCallback(
			function (PrintJobRequest $request, array $data, string $userId) use ($failure): array {
				$this->calls[] = ['createJob', [$request->templateId, $request->options, $request->filename], $data, $userId];
				if ($failure !== null) {
					throw new Exception('job refused', $failure);
				}

				return ['jobId' => 'job-1'];
			}
		);

		return new PrintJobController('filinq', $request, $jobs, $session, $this->createMock(IGroupManager::class), $logger);
	}//end controller()
}//end class
