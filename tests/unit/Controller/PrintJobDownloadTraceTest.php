<?php

/**
 * Trace tests for the print download endpoint: the answer, the access check and the service call.
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
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Stringable;

/**
 * Every path through download(), compared with JSON recorded from the
 * implementation before it was split.
 *
 * @covers \OCA\Filinq\Controller\PrintJobController
 */
class PrintJobDownloadTraceTest extends TestCase {

	/**
	 * What the doubles were asked, in order.
	 *
	 * @var array<int, array<int, mixed>>
	 */
	private array $calls = [];

	/**
	 * The scenarios: caller, admin or not, request parameters, and how the service answers.
	 *
	 * @return array<string, array{0: string|null, 1: bool, 2: array<string, mixed>, 3: array<string, mixed>}>
	 */
	public static function scenarios(): array {
		return [
			'nobody logged in' => [null, false, [], []],
			'no such job' => ['anna', false, [], ['job' => null]],
			'somebody else' => ['bob', false, [], []],
			'somebody else naming the owner' => ['bob', false, ['userId' => 'anna', 'requestedBy' => 'anna'], []],
			'an admin' => ['root', true, [], []],
			'still rendering' => ['anna', false, [], ['status' => 'rendering']],
			'the whole job' => ['anna', false, [], []],
			'one item' => ['anna', false, ['item' => '2'], []],
			'a negative item' => ['anna', false, ['item' => '-1'], []],
			'an item that is no number' => ['anna', false, ['item' => 'twee'], []],
			'no PDF' => ['anna', false, [], ['pdf' => null]],
			'the download fails' => ['anna', false, [], ['pdf' => 'fail']],
			'the lookup fails' => ['anna', false, [], ['job' => 'fail']],
		];
	}//end scenarios()

	/**
	 * The trace matches the recorded one.
	 *
	 * @param string|null          $uid     The caller, or null for nobody.
	 * @param bool                 $isAdmin Whether the caller is an admin.
	 * @param array<string, mixed> $params  The request parameters.
	 * @param array<string, mixed> $world   How the service answers.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testTrace(?string $uid, bool $isAdmin, array $params, array $world): void {
		$logger = new class extends AbstractLogger {
			/**
			 * @var array<int, array<int, mixed>>
			 */
			public array $lines = [];

			/**
			 * Record one line, without the exception object.
			 *
			 * @param mixed             $level   The level.
			 * @param string|Stringable $message The message.
			 * @param array<mixed>      $context The context.
			 *
			 * @return void
			 */
			public function log($level, string|Stringable $message, array $context = []): void {
				$context['exception'] = isset($context['exception']) === true ? $context['exception']::class : null;
				$this->lines[] = [$level, (string)$message, $context];
			}
		};

		$response = $this->controller($uid, $isAdmin, $params, $world, $logger)->download('job-1');
		$data = $response instanceof DataDownloadResponse ? [$response->getData(), $response->getHeaders()['Content-Type'] ?? null, $response->getHeaders()['Content-Disposition'] ?? null] : $response->getData();
		$trace = ['status' => $response->getStatus(), 'data' => $data, 'calls' => $this->calls, 'logs' => $logger->lines];

		$path = __DIR__ . '/golden/print-job-download-trace.json';
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
	 * @param string|null          $uid     The caller.
	 * @param bool                 $isAdmin Whether the caller is an admin.
	 * @param array<string, mixed> $params  The request parameters.
	 * @param array<string, mixed> $world   How the service answers.
	 * @param AbstractLogger       $logger  The logger.
	 *
	 * @return PrintJobController
	 */
	private function controller(?string $uid, bool $isAdmin, array $params, array $world, AbstractLogger $logger): PrintJobController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(
			static fn (string $key, $default = null) => $params[$key] ?? $default
		);

		$session = $this->createMock(IUserSession::class);
		$user = null;
		if ($uid !== null) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($uid);
		}

		$session->method('getUser')->willReturn($user);

		$groups = $this->createMock(IGroupManager::class);
		$groups->method('isAdmin')->willReturnCallback(
			function (string $userId) use ($isAdmin): bool {
				$this->calls[] = ['isAdmin', $userId];
				return $isAdmin;
			}
		);

		$jobs = $this->createMock(PrintJobServiceInterface::class);
		$jobs->method('getJob')->willReturnCallback(
			function (string $jobId) use ($world): ?array {
				$this->calls[] = ['getJob', $jobId];
				if (($world['job'] ?? '') === 'fail') {
					throw new Exception('repository offline');
				}

				return array_key_exists('job', $world) === true ? $world['job'] : ['id' => $jobId, 'requestedBy' => 'anna', 'status' => $world['status'] ?? 'ready'];
			}
		);
		$jobs->method('download')->willReturnCallback(
			function (array $job, ?int $item = null) use ($world): ?array {
				$this->calls[] = ['download', $job['id'], $item];
				if (($world['pdf'] ?? '') === 'fail') {
					throw new Exception('store offline');
				}

				if (array_key_exists('pdf', $world) === true) {
					return null;
				}

				return ['content' => '%PDF-' . ($item ?? 'all'), 'filename' => 'brief.pdf', 'contentType' => 'application/pdf'];
			}
		);

		return new PrintJobController('filinq', $request, $jobs, $session, $groups, $logger);
	}//end controller()
}//end class
