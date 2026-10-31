<?php

/**
 * What one SigningExpirationJob sweep reads, writes, emits, audits and logs.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\BackgroundJob
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-signing/tasks.md#task-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\BackgroundJob;

use Exception;
use OCA\Filinq\BackgroundJob\SigningExpirationJob;
use OCA\Filinq\Kernel\SigningAuditEvent;
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\SigningAuditService;
use OCA\Filinq\Service\SigningService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use ReflectionMethod;

/**
 * One sweep over both open statuses, recorded call by call.
 *
 * @covers \OCA\Filinq\BackgroundJob\SigningExpirationJob
 * @uses   \OCA\Filinq\Kernel\SigningAuditActor
 * @uses   \OCA\Filinq\Kernel\SigningAuditEvent
 * @uses   \OCA\Filinq\Kernel\SigningAuditSubject
 */
class SigningExpirationSweepTest extends TestCase {

	/**
	 * Calls made, in order: [what, ...arguments].
	 *
	 * @var array<int, array<int, mixed>>
	 */
	private array $calls = [];

	/**
	 * Run one sweep.
	 *
	 * @param array<string, mixed> $byStatus  What findAll answers per status; an Exception is thrown.
	 * @param bool                 $auditFails Whether logEvent throws.
	 *
	 * @return void
	 */
	private function sweep(array $byStatus, bool $auditFails = false): void {
		$objects = $this->createMock(ObjectService::class);
		$objects->method('findAll')->willReturnCallback(function (array $config, bool $_rbac) use ($byStatus): array {
			$this->calls[] = ['findAll', $config, $_rbac];
			$answer = $byStatus[$config['filters']['status']];
			if ($answer instanceof Exception) {
				throw $answer;
			}

			return $answer;
		});
		$objects->method('saveObject')->willReturnCallback(function (...$arguments): array {
			$this->calls[] = ['save', array_slice($arguments, 0, 5)];
			return [];
		});
		$settings = $this->createMock(SettingsService::class);
		$settings->method('getObjectService')->willReturn($objects);

		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnMap(
			[
				['filinq', 'signingRequest_register', '', 'signing'],
				['filinq', 'signingRequest_schema', '', 'signingRequest'],
			]
		);

		$audit = $this->createMock(SigningAuditService::class);
		$audit->method('logEvent')->willReturnCallback(function (SigningAuditEvent $event) use ($auditFails): array {
			$this->calls[] = ['audit', [$event->request->id, $event->action, $event->actor->userId, $event->actor->displayName, $event->actor->ipAddress, $event->request->signatureLevel, $event->request->provider, $event->metadata]];
			if ($auditFails === true) {
				throw new Exception('audit down');
			}

			return [];
		});
		$signing = $this->createMock(SigningService::class);
		$signing->method('emitExpiredConclusion')->willReturnCallback(function (array $request): void {
			$this->calls[] = ['emit', $request['id'] ?? $request['uuid']];
		});

		$logger = new class($this->calls) extends AbstractLogger {
			public function __construct(private array &$calls) {
			}

			public function log($level, string|\Stringable $message, array $context = []): void {
				$this->calls[] = [(string)$level, (string)$message];
			}
		};

		$job = new SigningExpirationJob(
			time: $this->createMock(ITimeFactory::class),
			settingsService: $settings,
			auditService: $audit,
			config: $config,
			logger: $logger,
			signingService: $signing
		);
		(new ReflectionMethod($job, 'run'))->invoke($job, null);

	}//end sweep()

	/**
	 * Overdue requests expire, in order, with their audit line; the rest are left alone.
	 *
	 * @return void
	 */
	public function testASweepInFull(): void {
		$entity = new class implements \JsonSerializable {
			public function jsonSerialize(): array {
				return ['uuid' => 'req-entity', 'deadline' => '2020-02-01T00:00:00+00:00'];
			}
		};

		$this->sweep(
			byStatus: [
				'PENDING' => [
					['id' => 'req-1', 'status' => 'PENDING', 'deadline' => '2020-01-01T00:00:00+00:00', 'signatureLevel' => 'AES', 'provider' => 'native'],
					['id' => 'req-future', 'deadline' => '2099-01-01T00:00:00+00:00'],
					['id' => 'req-no-deadline'],
					['deadline' => '2020-01-01T00:00:00+00:00'],
				],
				'IN_PROGRESS' => [$entity],
			]
		);

		$audits = array_values(array_filter($this->calls, fn (array $call): bool => $call[0] === 'audit'));
		$this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T/', $audits[0][1][7]['expiredAt']);
		$expiredAt = $audits[0][1][7]['expiredAt'];
		$auditOf = fn (string $id, string $deadline, string $level, string $provider): array => [
			'audit',
			[$id, 'EXPIRED', 'system', 'System', '127.0.0.1', $level, $provider, ['deadline' => $deadline, 'expiredAt' => $expiredAt]],
		];

		$this->assertSame(
			[
				['info', '[SigningExpirationJob] Starting deadline enforcement check'],
				['findAll', ['filters' => ['register' => 'signing', 'schema' => 'signingRequest', 'status' => 'PENDING']], false],
				['save', [['id' => 'req-1', 'status' => 'EXPIRED', 'deadline' => '2020-01-01T00:00:00+00:00', 'signatureLevel' => 'AES', 'provider' => 'native'], 'signing', 'signingRequest', null, false]],
				['emit', 'req-1'],
				$auditOf('req-1', '2020-01-01T00:00:00+00:00', 'AES', 'native'),
				['info', '[SigningExpirationJob] Expired signing request req-1'],
				['findAll', ['filters' => ['register' => 'signing', 'schema' => 'signingRequest', 'status' => 'IN_PROGRESS']], false],
				['save', [['uuid' => 'req-entity', 'deadline' => '2020-02-01T00:00:00+00:00', 'status' => 'EXPIRED'], 'signing', 'signingRequest', null, false]],
				['emit', 'req-entity'],
				$auditOf('req-entity', '2020-02-01T00:00:00+00:00', '', ''),
				['info', '[SigningExpirationJob] Expired signing request req-entity'],
				['info', '[SigningExpirationJob] Done. Expired 2 request(s).'],
			],
			$this->calls
		);

	}//end testASweepInFull()

	/**
	 * A failing audit write is logged and the request still counts; a failing status is logged and the next one runs.
	 *
	 * @return void
	 */
	public function testFailuresAreLoggedAndTheSweepGoesOn(): void {
		$this->sweep(
			byStatus: [
				'PENDING' => new Exception('register down'),
				'IN_PROGRESS' => [
					['id' => 'req-2', 'deadline' => '2020-01-01T00:00:00+00:00'],
					['id' => 'req-3', 'deadline' => 'not a date'],
					['id' => 'req-4', 'deadline' => '2020-01-01T00:00:00+00:00'],
				],
			],
			auditFails: true
		);

		$logged = array_values(array_filter($this->calls, fn (array $call): bool => in_array($call[0], ['error', 'info'], true)));
		$this->assertStringStartsWith('[SigningExpirationJob] Failed to process status IN_PROGRESS: ', $logged[4][1]);
		$this->assertStringContainsString('not a date', $logged[4][1]);
		$logged[4][1] = 'unparseable deadline';
		$this->assertSame(
			[
				['info', '[SigningExpirationJob] Starting deadline enforcement check'],
				['error', '[SigningExpirationJob] Failed to process status PENDING: register down'],
				['error', '[SigningExpirationJob] Failed to log audit event for req-2: audit down'],
				['info', '[SigningExpirationJob] Expired signing request req-2'],
				['error', 'unparseable deadline'],
				['info', '[SigningExpirationJob] Done. Expired 1 request(s).'],
			],
			$logged
		);

	}//end testFailuresAreLoggedAndTheSweepGoesOn()
}//end class
