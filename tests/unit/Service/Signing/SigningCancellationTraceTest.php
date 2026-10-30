<?php
/**
 * Trace tests for withdrawing a signing request: log lines, provider calls and the answer.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Signing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Signing;

use OCA\Filinq\Exception\SigningCancellationNotSupportedException;
use OCA\Filinq\Service\Signing\SigningCancellationService;
use OCA\Filinq\Service\Signing\SigningProviderFactory;
use OCA\Filinq\Service\Signing\SigningProviderInterface;
use OCA\Filinq\Service\SigningService;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use RuntimeException;
use Stringable;
use Throwable;

/**
 * Every path through cancel(), compared with JSON recorded from the
 * implementation before it was split.
 *
 * @covers \OCA\Filinq\Service\Signing\SigningCancellationService
 * @uses   \OCA\Filinq\Exception\SigningCancellationNotSupportedException
 *
 * @spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md
 */
class SigningCancellationTraceTest extends TestCase {

	/**
	 * The scenarios: acting user, what the lookup gives, and how the provider answers.
	 *
	 * @return array<string, array{0: string, 1: array<string, mixed>|string|null, 2: string}>
	 */
	public static function scenarios(): array {
		$request = ['id' => 'req-1', 'initiatorUserId' => 'alice', 'externalId' => 'ext-1', 'status' => 'pending'];
		return [
			'creator' => ['alice', $request, 'ok'],
			'already cancelled' => ['alice', ['status' => 'cancelled'] + $request, 'ok'],
			'other provider reference' => ['alice', ['externalId' => 'ext-9'] + $request, 'ok'],
			'named provider' => ['alice', ['provider' => 'libresign'] + $request, 'ok'],
			'not the creator' => ['bob', $request, 'ok'],
			'no such request' => ['alice', null, 'ok'],
			'lookup throws' => ['alice', 'throw', 'ok'],
			'no creator and no actor' => ['', ['initiatorUserId' => ''] + $request, 'ok'],
			'no creator field' => ['alice', ['externalId' => 'ext-1'], 'ok'],
			'empty provider reference' => ['alice', ['externalId' => ''] + $request, 'ok'],
			'no provider reference' => ['alice', ['initiatorUserId' => 'alice'], 'ok'],
			'unsupported' => ['alice', $request, 'unsupported'],
			'provider fails' => ['alice', $request, 'fails'],
		];
	}//end scenarios()

	/**
	 * The trace matches the recorded one.
	 *
	 * @param string                           $uid      The acting user.
	 * @param array<string, mixed>|string|null $request  What getRequest() gives, or 'throw'.
	 * @param string                           $provider How the provider answers: ok, unsupported or fails.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testTrace(string $uid, array|string|null $request, string $provider): void {
		$trace = ['lookups' => [], 'providers' => [], 'cancelled' => [], 'logs' => []];

		$requests = $this->createMock(SigningService::class);
		$requests->method('getRequest')->willReturnCallback(
			static function (string $requestId, string $callerUserId) use ($request, &$trace): ?array {
				$trace['lookups'][] = [$requestId, $callerUserId];
				if ($request === 'throw') {
					throw new RuntimeException('lookup failed');
				}

				return $request;
			}
		);

		$double = $this->createMock(SigningProviderInterface::class);
		$double->method('cancelSigning')->willReturnCallback(
			static function (string $externalId) use ($provider, &$trace): bool {
				$trace['cancelled'][] = $externalId;
				if ($provider === 'unsupported') {
					throw new SigningCancellationNotSupportedException('ValidSign');
				}

				if ($provider === 'fails') {
					throw new RuntimeException('provider down.');
				}

				return true;
			}
		);

		$factory = $this->createMock(SigningProviderFactory::class);
		$factory->method('getActiveProvider')->willReturnCallback(
			static function () use ($double, &$trace): SigningProviderInterface {
				$trace['providers'][] = 'active';
				return $double;
			}
		);
		$factory->method('getProvider')->willReturnCallback(
			static function (string $identifier) use ($double, &$trace): SigningProviderInterface {
				$trace['providers'][] = $identifier;
				return $double;
			}
		);

		$logger = new class extends AbstractLogger {
			/**
			 * @var array<int, array<int, mixed>>
			 */
			public array $lines = [];

			/**
			 * Record one line.
			 *
			 * @param mixed             $level   The level.
			 * @param string|Stringable $message The message.
			 * @param array<mixed>      $context The context.
			 *
			 * @return void
			 */
			public function log($level, string|Stringable $message, array $context = []): void {
				$this->lines[] = [$level, (string)$message, $context];
			}
		};

		$service = new SigningCancellationService(providers: $factory, requests: $requests, logger: $logger);
		try {
			$trace['result'] = $service->cancel(uid: $uid, requestId: 'req-1');
		} catch (Throwable $e) {
			$previous = $e->getPrevious();
			$trace['thrown'] = [$e::class, $e->getMessage(), $previous === null ? null : [$previous::class, $previous->getMessage()]];
		}

		$trace['logs'] = $logger->lines;

		$path = __DIR__ . '/golden/cancellation-trace.json';
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
}//end class
