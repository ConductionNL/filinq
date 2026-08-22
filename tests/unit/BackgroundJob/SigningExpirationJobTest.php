<?php

/**
 * Unit tests for SigningExpirationJob
 *
 * Asserts that the expiration job correctly identifies and marks past-deadline
 * signing requests as EXPIRED, logs audit events, and skips requests still
 * within their deadline.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\BackgroundJob
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/document-signing/tasks.md#task-3
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\BackgroundJob;

use OCA\Filinq\BackgroundJob\SigningExpirationJob;
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\SigningAuditService;
use OCA\Filinq\Service\SigningService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * Tests for SigningExpirationJob deadline enforcement
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\BackgroundJob
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SigningExpirationJobTest extends TestCase {

	/**
	 * @var SigningExpirationJob
	 */
	private SigningExpirationJob $job;

	/**
	 * @var SettingsService|MockObject
	 */
	private SettingsService|MockObject $settingsService;

	/**
	 * @var ObjectService|MockObject
	 */
	private ObjectService|MockObject $objectService;

	/**
	 * @var SigningAuditService|MockObject
	 */
	private SigningAuditService|MockObject $auditService;

	/**
	 * @var IAppConfig|MockObject
	 */
	private IAppConfig|MockObject $config;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->objectService = $this->createMock(ObjectService::class);
		$this->settingsService = $this->createMock(SettingsService::class);
		$this->settingsService->method('getObjectService')->willReturn($this->objectService);

		$this->auditService = $this->createMock(SigningAuditService::class);

		$this->config = $this->createMock(IAppConfig::class);
		$this->config->method('getValueString')->willReturnMap(
			[
				['filinq', 'signingRequest_register', '', 'signing'],
				['filinq', 'signingRequest_schema', '', 'signingRequest'],
			]
		);

		$timeFactory = $this->createMock(ITimeFactory::class);
		$timeFactory->method('getTime')->willReturn(time());

		$this->job = new SigningExpirationJob(
			time: $timeFactory,
			settingsService: $this->settingsService,
			auditService: $this->auditService,
			config: $this->config,
			logger: $this->createMock(LoggerInterface::class),
			signingService: $this->createMock(SigningService::class)
		);

	}//end setUp()

	/**
	 * The job class extends TimedJob
	 *
	 * @return void
	 */
	public function testJobExtendsTimedJob(): void {
		$ref = new ReflectionClass(SigningExpirationJob::class);
		$this->assertTrue(
			$ref->isSubclassOf(\OCP\BackgroundJob\TimedJob::class),
			'SigningExpirationJob must extend TimedJob.'
		);

	}//end testJobExtendsTimedJob()

	/**
	 * run() marks a past-deadline request as EXPIRED and logs an audit event
	 *
	 * @return void
	 */
	public function testRunExpiresOverdueRequest(): void {
		$expiredRequest = [
			'id' => 'req-expired',
			'status' => 'PENDING',
			'deadline' => '2020-01-01T00:00:00+00:00',
			'signatureLevel' => 'SES',
			'provider' => 'native',
		];

		// findAll returns the expired request for both status checks.
		$this->objectService->method('findAll')
			->willReturnOnConsecutiveCalls([$expiredRequest], []);

		$this->objectService->expects($this->once())
			->method('saveObject')
			->with(
				$this->callback(
					function (array $obj): bool {
						return $obj['status'] === 'EXPIRED';
					}
				),
				$this->anything(),
				$this->anything()
			);

		$this->auditService->expects($this->once())
			->method('logEvent')
			->with(
				$this->equalTo('req-expired'),
				$this->equalTo('EXPIRED'),
				$this->anything(),
				$this->anything(),
				$this->anything()
			);

		// Invoke via reflection to call the protected run() method.
		$ref = new ReflectionClass($this->job);
		$method = $ref->getMethod('run');
		$method->setAccessible(true);
		$method->invoke($this->job, []);

	}//end testRunExpiresOverdueRequest()

	/**
	 * run() skips requests whose deadline is still in the future
	 *
	 * @return void
	 */
	public function testRunSkipsFutureDeadline(): void {
		$futureRequest = [
			'id' => 'req-future',
			'status' => 'PENDING',
			'deadline' => '2099-01-01T00:00:00+00:00',
		];

		$this->objectService->method('findAll')
			->willReturnOnConsecutiveCalls([$futureRequest], []);

		// No save should occur for non-expired requests.
		$this->objectService->expects($this->never())->method('saveObject');
		$this->auditService->expects($this->never())->method('logEvent');

		$ref = new ReflectionClass($this->job);
		$method = $ref->getMethod('run');
		$method->setAccessible(true);
		$method->invoke($this->job, []);

	}//end testRunSkipsFutureDeadline()

	/**
	 * run() skips gracefully when register/schema config is not set
	 *
	 * @return void
	 */
	public function testRunSkipsWhenNotConfigured(): void {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturn('');

		$timeFactory = $this->createMock(ITimeFactory::class);
		$timeFactory->method('getTime')->willReturn(time());

		$job = new SigningExpirationJob(
			time: $timeFactory,
			settingsService: $this->settingsService,
			auditService: $this->auditService,
			config: $config,
			logger: $this->createMock(LoggerInterface::class),
			signingService: $this->createMock(SigningService::class)
		);

		// findAll must not be called when register/schema is empty.
		$this->objectService->expects($this->never())->method('findAll');

		$ref = new ReflectionClass($job);
		$method = $ref->getMethod('run');
		$method->setAccessible(true);
		$method->invoke($job, []);

	}//end testRunSkipsWhenNotConfigured()

	/**
	 * run() skips gracefully when OpenRegister is not available
	 *
	 * @return void
	 */
	public function testRunSkipsWhenOpenRegisterUnavailable(): void {
		$settingsService = $this->createMock(SettingsService::class);
		$settingsService->method('getObjectService')->willReturn(null);

		$timeFactory = $this->createMock(ITimeFactory::class);
		$timeFactory->method('getTime')->willReturn(time());

		$job = new SigningExpirationJob(
			time: $timeFactory,
			settingsService: $settingsService,
			auditService: $this->auditService,
			config: $this->config,
			logger: $this->createMock(LoggerInterface::class),
			signingService: $this->createMock(SigningService::class)
		);

		$ref = new ReflectionClass($job);
		$method = $ref->getMethod('run');
		$method->setAccessible(true);

		// Should not throw.
		$method->invoke($job, []);

		$this->assertTrue(true);

	}//end testRunSkipsWhenOpenRegisterUnavailable()

}//end class
