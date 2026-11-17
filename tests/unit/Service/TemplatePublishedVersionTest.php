<?php

/**
 * Unit tests for TemplateService's draft and published versions
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/specify-capability-rows/specs/template-management/spec.md#requirement-a-template-is-tried-with-sample-data-before-users-get-it-req-scr-015
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Service\OpenRegisterResolver;
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\Support\OpenRegisterAccess;
use OCA\Filinq\Service\TemplateService;
use OCA\Filinq\Service\TemplateVersionService;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * A new or changed template is a draft; users get the last published version,
 * and publishing is the author's explicit action.
 *
 * @covers \OCA\Filinq\Service\TemplateService
 * @uses   \OCA\Filinq\Service\OpenRegisterResolver
 * @uses   \OCA\Filinq\Service\Support\OpenRegisterAccess
 */
class TemplatePublishedVersionTest extends TestCase {

	/**
	 * The fake OpenRegister object service.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objectService;

	/**
	 * The version chain.
	 *
	 * @var TemplateVersionService&MockObject
	 */
	private TemplateVersionService&MockObject $versions;

	/**
	 * The objects saved, in order.
	 *
	 * @var list<array<string, mixed>>
	 */
	private array $saved = [];

	/**
	 * Set up the object service and version chain.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->objectService = $this->createMock(ObjectService::class);
		$this->objectService->method('saveObject')->willReturnCallback(
			function (array $object, ObjectSaveOptions $options): array {
				$this->saved[] = $object;

				return $object;
			}
		);

		$this->versions = $this->createMock(TemplateVersionService::class);

	}//end setUp()

	/**
	 * The service over one stored template whose chain is on the given head.
	 *
	 * @param array<string, mixed> $template The stored template.
	 * @param int                  $head     The version number the head is on.
	 *
	 * @return TemplateService
	 */
	private function service(array $template, int $head=3): TemplateService {
		$this->objectService->method('find')->willReturn($template);
		$this->versions->method('getNextVersionNumber')->willReturn($head);

		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($this->objectService);

		$settings = $this->createMock(SettingsService::class);
		$settings->method('getAllSettings')->willReturn(
			['configuration' => ['template_register' => 'reg-t', 'template_schema' => 'sch-t']]
		);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('author');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturn('15');

		return new TemplateService(
			new OpenRegisterAccess($apps, $container),
			new OpenRegisterResolver($settings),
			$this->versions,
			$session,
			$config
		);

	}//end service()

	/**
	 * A new template is saved as a draft, whatever status the caller sends.
	 *
	 * @return void
	 */
	public function testANewTemplateStartsAsADraft(): void {
		$this->service([])->createTemplate(
			['name' => 'Brief', 'content' => '<p>x</p>', 'namespace' => 'filinq', 'status' => 'published', 'publishedVersion' => 9]
		);

		$this->assertSame('draft', $this->saved[0]['status']);
		$this->assertArrayNotHasKey('publishedVersion', $this->saved[0]);

	}//end testANewTemplateStartsAsADraft()

	/**
	 * A changed template becomes a draft and keeps the version users get.
	 *
	 * @return void
	 */
	public function testAChangedTemplateBecomesADraftAndKeepsItsPublishedVersion(): void {
		$this->versions->expects($this->once())->method('createVersion');
		$service = $this->service(['id' => 't1', 'name' => 'Brief', 'content' => '<p>v2</p>', 'status' => 'published', 'publishedVersion' => 2], 2);

		$service->updateTemplate('t1', ['content' => '<p>v3</p>', 'status' => 'published', 'publishedVersion' => 3]);

		$this->assertSame('draft', $this->saved[0]['status']);
		$this->assertSame(2, $this->saved[0]['publishedVersion']);
		$this->assertSame('<p>v3</p>', $this->saved[0]['content']);

	}//end testAChangedTemplateBecomesADraftAndKeepsItsPublishedVersion()

	/**
	 * A template that predates drafts (no status, so counted as published)
	 * stays generatable at the version it was on when it is edited.
	 *
	 * @return void
	 */
	public function testAnEditedTemplateWithoutStatusKeepsItsVersionPublished(): void {
		$service = $this->service(['id' => 't1', 'name' => 'Brief', 'content' => '<p>v4</p>'], 4);

		$service->updateTemplate('t1', ['content' => '<p>v5</p>']);

		$this->assertSame('draft', $this->saved[0]['status']);
		$this->assertSame(4, $this->saved[0]['publishedVersion']);

	}//end testAnEditedTemplateWithoutStatusKeepsItsVersionPublished()

	/**
	 * Editing a draft leaves its last published version where it was.
	 *
	 * @return void
	 */
	public function testAnEditedDraftKeepsItsLastPublishedVersion(): void {
		$service = $this->service(['id' => 't1', 'name' => 'Brief', 'content' => '<p>v4</p>', 'status' => 'draft', 'publishedVersion' => 2], 4);

		$service->updateTemplate('t1', ['content' => '<p>v5</p>']);

		$this->assertSame('draft', $this->saved[0]['status']);
		$this->assertSame(2, $this->saved[0]['publishedVersion']);

	}//end testAnEditedDraftKeepsItsLastPublishedVersion()

	/**
	 * Publishing releases the version the head is on, without a new version.
	 *
	 * @return void
	 */
	public function testPublishingReleasesTheHeadVersion(): void {
		$this->versions->expects($this->never())->method('createVersion');
		$service = $this->service(['id' => 't1', 'name' => 'Brief', 'content' => '<p>v3</p>', 'status' => 'draft', 'publishedVersion' => 2], 3);

		$result = $service->publishTemplate('t1');

		$this->assertCount(1, $this->saved);
		$this->assertSame('published', $this->saved[0]['status']);
		$this->assertSame(3, $this->saved[0]['publishedVersion']);
		$this->assertSame('published', $result['status']);

	}//end testPublishingReleasesTheHeadVersion()

	/**
	 * A draft answers users with the content of its last published version.
	 *
	 * @return void
	 */
	public function testADraftIsRenderedFromTheLastPublishedVersion(): void {
		$this->versions->method('findVersionByNumber')->with('t1', 2)
			->willReturn(['templateId' => 't1', 'version' => 2, 'content' => '<p>v2</p>', 'name' => 'Brief']);
		$service = $this->service(['id' => 't1', 'name' => 'Brief', 'content' => '<p>draft</p>', 'status' => 'draft', 'publishedVersion' => 2], 3);

		$template = $service->getPublishedTemplate('t1');

		$this->assertSame('<p>v2</p>', $template['content']);
		$this->assertSame(2, $template['version']);

	}//end testADraftIsRenderedFromTheLastPublishedVersion()

	/**
	 * A published template, and one that predates drafts, answer the head.
	 *
	 * @return void
	 */
	public function testAPublishedOrUnstatusedTemplateAnswersTheHead(): void {
		$this->versions->expects($this->never())->method('findVersionByNumber');
		$service = $this->service(['id' => 't1', 'name' => 'Brief', 'content' => '<p>head</p>'], 3);

		$template = $service->getPublishedTemplate('t1');

		$this->assertSame('<p>head</p>', $template['content']);
		$this->assertSame(3, $template['version']);

	}//end testAPublishedOrUnstatusedTemplateAnswersTheHead()

	/**
	 * A draft that was never published is not offered to users.
	 *
	 * @return void
	 */
	public function testANeverPublishedDraftIsRefused(): void {
		$service = $this->service(['id' => 't1', 'name' => 'Brief', 'content' => '<p>draft</p>', 'status' => 'draft'], 1);

		try {
			$service->getPublishedTemplate('t1');
			$this->fail('A draft without a published version was answered.');
		} catch (Exception $e) {
			$this->assertSame(409, $e->getCode());
			$this->assertSame('Template t1 has no published version', $e->getMessage());
		}

	}//end testANeverPublishedDraftIsRefused()
}//end class
