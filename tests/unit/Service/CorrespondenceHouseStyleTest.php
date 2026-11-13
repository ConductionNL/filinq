<?php

/**
 * A letter generated without a named house style carries the managed house
 * style of the requesting user's token set; a named one wins.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/templates-house-style-from-thematiq/tasks.md#task-2.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\BackgroundJob\JobQueue;
use OCA\Filinq\Service\CorrespondenceService;
use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Service\HuisstijlProfileSync;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\Support\OpenRegisterAccess;
use OCA\Filinq\Service\Support\PdfRendering;
use OCA\Filinq\Service\Support\TemplateSources;
use OCA\Filinq\Service\TemplateRenderer;
use OCA\Filinq\Service\TemplateService;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @covers \OCA\Filinq\Service\CorrespondenceService
 * @uses   \OCA\Filinq\Service\HuisstijlProfileMapper
 * @uses   \OCA\Filinq\BackgroundJob\JobQueue
 * @uses   \OCA\Filinq\Service\Support\OpenRegisterAccess
 * @uses   \OCA\Filinq\Service\Support\PdfRendering
 * @uses   \OCA\Filinq\Service\Support\TemplateSources
 * @uses   \OCA\Filinq\Service\DocumentObjectServiceResolver
 */
class CorrespondenceHouseStyleTest extends TestCase {

	private HuisstijlProfileSync&MockObject $sync;

	private ObjectService&MockObject $objectService;

	private bool $syncAvailable = true;

	/**
	 * The templates the renderer was given, in order.
	 *
	 * @var string[]
	 */
	private array $rendered = [];

	private function service(): CorrespondenceService {
		$this->sync = $this->createMock(HuisstijlProfileSync::class);
		$this->objectService = $this->createMock(ObjectService::class);
		$logEntry = $this->createMock(ObjectEntity::class);
		$logEntry->method('jsonSerialize')->willReturn(['id' => 'log-1']);
		$this->objectService->method('saveObject')->willReturn($logEntry);

		$templates = $this->createMock(TemplateService::class);
		$templates->method('getTemplate')->willReturn(['id' => 'tmpl-1', 'name' => 'Brief', 'content' => '<p>Brief</p>']);
		$data = $this->createMock(DataResolverService::class);
		$data->method('resolve')->willReturn(['data' => [], 'errors' => [], 'warnings' => []]);

		$renderer = $this->createMock(TemplateRenderer::class);
		$renderer->method('renderTemplate')->willReturnCallback(
			function (string $templateContent): string {
				$this->rendered[] = $templateContent;

				return $templateContent;
			}
		);

		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn(['openregister']);
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			function (string $id) {
				if ($id === 'OCA\OpenRegister\Service\ObjectService') {
					return $this->objectService;
				}

				if ($id === HuisstijlProfileSync::class && $this->syncAvailable === true) {
					return $this->sync;
				}

				return null;
			}
		);

		return new CorrespondenceService(
			new TemplateSources(templates: $templates, data: $data),
			new PdfRendering(renderer: $renderer, pdf: $this->createMock(PdfService::class)),
			new OpenRegisterAccess(apps: $appManager, container: $container),
			new JobQueue(jobList: $this->createMock(IJobList::class)),
			$this->createMock(LoggerInterface::class),
			$this->createMock(IAppConfig::class)
		);
	}//end service()

	public function testALetterWithoutANamedHouseStyleCarriesTheUsersFooter(): void {
		$service = $this->service();
		$this->sync->expects($this->once())->method('syncFor')->with('clerk-bergen')
			->willReturn(['managedBy' => 'thematiq', 'footerHtml' => '<p>Postbus 175, 1860 AD Bergen</p>']);

		$result = $service->generate('tmpl-1', [], ['format' => 'html', 'userId' => 'clerk-bergen']);

		$this->assertSame('<p>Brief</p><p>Postbus 175, 1860 AD Bergen</p>', $result['content']);
	}//end testALetterWithoutANamedHouseStyleCarriesTheUsersFooter()

	public function testTwoUsersGetTheirOwnHouseStyle(): void {
		$service = $this->service();
		$this->sync->method('syncFor')->willReturnMap(
			[
				['clerk-bergen', ['footerHtml' => '<p>Bergen</p>']],
				['clerk-uitgeest', ['footerHtml' => '<p>Uitgeest</p>']],
			]
		);

		$bergen = $service->generate('tmpl-1', [], ['format' => 'html', 'userId' => 'clerk-bergen']);
		$uitgeest = $service->generate('tmpl-1', [], ['format' => 'html', 'userId' => 'clerk-uitgeest']);

		$this->assertSame('<p>Brief</p><p>Bergen</p>', $bergen['content']);
		$this->assertSame('<p>Brief</p><p>Uitgeest</p>', $uitgeest['content']);
	}//end testTwoUsersGetTheirOwnHouseStyle()

	public function testANamedHouseStyleWins(): void {
		$service = $this->service();
		$this->sync->expects($this->never())->method('syncFor');
		$this->objectService->method('find')->willReturn(['footerHtml' => '<p>Eigen</p>']);

		$result = $service->generate('tmpl-1', [], ['format' => 'html', 'huisstijlId' => 'hand-made', 'userId' => 'clerk']);

		$this->assertSame('<p>Brief</p><p>Eigen</p>', $result['content']);
	}//end testANamedHouseStyleWins()

	public function testWithoutTheSyncTheLetterHasNoHouseStyle(): void {
		$this->syncAvailable = false;
		$service = $this->service();

		$result = $service->generate('tmpl-1', [], ['format' => 'html']);

		$this->assertSame('<p>Brief</p>', $result['content']);
		$this->assertSame(['<p>Brief</p>'], $this->rendered);
	}//end testWithoutTheSyncTheLetterHasNoHouseStyle()
}//end class
