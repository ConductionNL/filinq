<?php

/**
 * Characterization tests for the register entries and batch paths of CorrespondenceService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\BackgroundJob\JobQueue;
use OCA\Filinq\Service\CorrespondenceService;
use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\TemplateRenderer;
use OCA\Filinq\Service\TemplateService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Pins the exact register entry, batch result and queued job of CorrespondenceService.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Service\CorrespondenceService
 * @uses   \OCA\Filinq\BackgroundJob\JobQueue
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class CorrespondenceServiceRegisterTest extends TestCase {

	/**
	 * The service under test
	 *
	 * @var CorrespondenceService
	 */
	private CorrespondenceService $service;

	/**
	 * Mock template service
	 *
	 * @var TemplateService&MockObject
	 */
	private TemplateService $templateSvc;

	/**
	 * Mock job list
	 *
	 * @var IJobList&MockObject
	 */
	private IJobList $jobList;

	/**
	 * Mock app config
	 *
	 * @var IAppConfig&MockObject
	 */
	private IAppConfig $appConfig;

	/**
	 * Every object handed to saveObject, in order
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $saved = [];

	/**
	 * Set up test fixtures
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->templateSvc = $this->createMock(TemplateService::class);
		$this->jobList     = $this->createMock(IJobList::class);
		$this->appConfig   = $this->createMock(IAppConfig::class);

		$dataResolver = $this->createMock(DataResolverService::class);
		$dataResolver->method('resolve')->willReturn(['data' => [], 'errors' => [], 'warnings' => []]);
		$renderer = $this->createMock(TemplateRenderer::class);
		$renderer->method('renderTemplate')->willReturn('<p>x</p>');
		$pdfService = $this->createMock(PdfService::class);
		$pdfService->method('renderPdf')->willReturn('%PDF%');

		$objectSvc = $this->createMock(ObjectService::class);
		$objectSvc->method('saveObject')->willReturnCallback(
			function (array $object) {
				$this->saved[] = $object;
				return ['id' => 'log-' . count($this->saved)];
			}
		);

		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn(['openregister']);

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			function (string $class) use ($objectSvc) {
				if ($class === 'OCA\OpenRegister\Service\ObjectService') {
					return $objectSvc;
				}

				if ($class === IAppConfig::class) {
					return $this->appConfig;
				}

				return null;
			}
		);

		$this->service = new CorrespondenceService(
			$this->templateSvc,
			$dataResolver,
			$renderer,
			$pdfService,
			$container,
			$appManager,
			new JobQueue(jobList: $this->jobList),
			$this->createMock(LoggerInterface::class),
			$this->appConfig
		);

	}//end setUp()

	/**
	 * A generated letter logs one entry with every request field it carries.
	 *
	 * @return void
	 */
	public function testGeneratedLetterLogsTheFullEntry(): void {
		$this->templateSvc->method('getTemplate')->willReturn(['id' => 'tmpl-1', 'name' => 'Brief', 'content' => 'x']);

		$result = $this->service->generate(
			templateId: 'tmpl-1',
			dataRefs: [['register' => 'brp', 'schema' => 'p', 'id' => 'abc']],
			options: ['format' => 'html', 'recipientType' => 'person', 'caseReference' => 'Z-1', 'userId' => 'alice']
		);

		$this->assertSame(['id' => 'log-1'], $result['registerEntry']);
		$this->assertCount(1, $this->saved);
		$entry = $this->saved[0];
		$this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T/', $entry['generatedAt']);
		unset($entry['generatedAt']);
		$this->assertSame(
			[
				'templateId'    => 'tmpl-1',
				'templateName'  => 'Brief',
				'recipientId'   => 'abc',
				'recipientType' => 'person',
				'caseReference' => 'Z-1',
				'format'        => 'html',
				'status'        => 'generated',
				'generatedBy'   => 'alice',
				'errorMessage'  => null,
			],
			$entry
		);

	}//end testGeneratedLetterLogsTheFullEntry()

	/**
	 * Without data references the entry has an empty recipient and the configured default format.
	 *
	 * @return void
	 */
	public function testEntryWithoutDataRefsUsesTheDefaultFormat(): void {
		$this->templateSvc->method('getTemplate')->willReturn(['id' => 'tmpl-1', 'content' => 'x']);
		$this->appConfig->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key): string => $key === 'correspondence.default_format' ? 'email' : ''
		);

		$this->service->generate(templateId: 'tmpl-1', dataRefs: [], options: []);

		$this->assertSame('', $this->saved[0]['recipientId']);
		$this->assertSame('', $this->saved[0]['templateName']);
		$this->assertSame('email', $this->saved[0]['format']);
		$this->assertSame('', $this->saved[0]['recipientType']);
		$this->assertSame('', $this->saved[0]['generatedBy']);

	}//end testEntryWithoutDataRefsUsesTheDefaultFormat()

	/**
	 * A synchronous batch reports per recipient and logs a failed entry for the recipient that fails.
	 *
	 * @return void
	 */
	public function testSyncBatchReportsPerRecipientAndLogsFailures(): void {
		$calls = 0;
		$this->templateSvc->method('getTemplate')->willReturnCallback(
			static function () use (&$calls): array {
				$calls++;
				if ($calls === 2) {
					throw new Exception(message: 'template gone');
				}

				return ['id' => 'tmpl-1', 'name' => 'Brief', 'content' => 'x'];
			}
		);

		$result = $this->service->generateBatch(
			templateId: 'tmpl-1',
			recipientIds: ['r1', 'r2'],
			options: ['register' => 'brp', 'schema' => 'persoon', 'format' => 'html', 'userId' => 'bob']
		);

		$this->assertSame(
			[
				'results'   => [
					['recipientId' => 'r1', 'status' => 'success', 'content' => '<p>x</p>', 'warnings' => []],
					['recipientId' => 'r2', 'status' => 'error', 'error' => 'template gone'],
				],
				'total'     => 2,
				'completed' => 1,
				'errors'    => 1,
			],
			$result
		);

		$this->assertCount(2, $this->saved);
		$this->assertSame('generated', $this->saved[0]['status']);
		$this->assertSame('r1', $this->saved[0]['recipientId']);
		$failed = $this->saved[1];
		unset($failed['generatedAt']);
		$this->assertSame(
			[
				'templateId'    => 'tmpl-1',
				'templateName'  => '',
				'recipientId'   => 'r2',
				'recipientType' => '',
				'caseReference' => '',
				'format'        => 'html',
				'status'        => 'failed',
				'generatedBy'   => 'bob',
				'errorMessage'  => 'template gone',
			],
			$failed
		);

	}//end testSyncBatchReportsPerRecipientAndLogsFailures()

	/**
	 * A large batch queues one job with the request and stores the queued status with its owner.
	 *
	 * @return void
	 */
	public function testLargeBatchQueuesTheJobAndStoresItsStatus(): void {
		$recipients = array_map(static fn (int $i): string => 'r' . $i, range(1, 11));
		$options    = ['register' => 'brp', 'userId' => 'carol'];

		$queued = null;
		$this->jobList->expects($this->once())->method('add')->willReturnCallback(
			static function ($job, $argument) use (&$queued): void {
				$queued = $argument;
			}
		);
		$stored = null;
		$this->appConfig->method('setValueString')->willReturnCallback(
			static function (string $app, string $key, string $value) use (&$stored): bool {
				$stored = [$app, $key, json_decode($value, true)];
				return true;
			}
		);

		$result = $this->service->generateBatch(templateId: 'tmpl-9', recipientIds: $recipients, options: $options);

		$this->assertSame('queued', $result['status']);
		$this->assertSame(11, $result['totalRecipients']);
		$this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $result['jobId']);
		$this->assertSame(
			['jobId' => $result['jobId'], 'templateId' => 'tmpl-9', 'recipientIds' => $recipients, 'options' => $options],
			$queued
		);
		$this->assertSame(
			[
				'filinq',
				'correspondence_job_' . $result['jobId'],
				['status' => 'queued', 'total' => 11, 'completed' => 0, 'errors' => 0, 'results' => [], 'ownerUserId' => 'carol'],
			],
			$stored
		);
		$this->assertSame([], $this->saved);

	}//end testLargeBatchQueuesTheJobAndStoresItsStatus()
}//end class
