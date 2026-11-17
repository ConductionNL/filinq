<?php

/**
 * The `filinq-templates` data-provider leaf: app-local, list only, the scoped
 * templates as {id, name, format, description} (template-library-leaf REQ-TMPL-14).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Integration
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/template-library-leaf/tasks.md#task-3.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Integration;

use OCA\Filinq\Integration\TemplatesLeafProvider;
use OCA\Filinq\Service\TemplateService;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests TemplatesLeafProvider.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Integration
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Integration\TemplatesLeafProvider
 * @uses   \OCA\Filinq\Integration\TemplateLeafRow
 */
class TemplatesLeafProviderTest extends TestCase {

	/**
	 * @var TemplateService&MockObject
	 */
	private TemplateService $templates;

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->templates = $this->createMock(TemplateService::class);

	}//end setUp()

	/**
	 * The provider under test.
	 *
	 * @return TemplatesLeafProvider The provider.
	 */
	private function provider(): TemplatesLeafProvider {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $text): string => 'nl:' . $text);

		return new TemplatesLeafProvider(templates: $this->templates, l10n: $l10n);

	}//end provider()

	/**
	 * The scoped templates for the host's register and schema come back in the leaf shape, nothing more.
	 *
	 * @return void
	 */
	public function testListReturnsTheScopedTemplatesInTheLeafShape(): void {
		$this->templates->expects($this->once())->method('listForObject')->with('dossiq', 'case')->willReturn(
			[
				['id' => 'tpl-1', 'name' => 'Ontvangstbevestiging', 'format' => 'A4', 'description' => 'Brief', 'content' => '<p>{{ name }}</p>', 'scope' => [['register' => 'dossiq', 'schema' => 'case']]],
				['uuid' => 'tpl-2', 'name' => 'Besluit'],
			]
		);

		$rows = $this->provider()->list('dossiq', 'case', 'case-7');

		$this->assertSame(
			[
				['id' => 'tpl-1', 'name' => 'Ontvangstbevestiging', 'format' => 'A4', 'description' => 'Brief'],
				['id' => 'tpl-2', 'name' => 'Besluit', 'format' => '', 'description' => ''],
			],
			$rows
		);

	}//end testListReturnsTheScopedTemplatesInTheLeafShape()

	/**
	 * No applicable template is an empty list.
	 *
	 * @return void
	 */
	public function testNoApplicableTemplateIsAnEmptyList(): void {
		$this->templates->method('listForObject')->willReturn([]);

		$this->assertSame([], $this->provider()->list('dossiq', 'contact', 'c-1'));

	}//end testNoApplicableTemplateIsAnEmptyList()

	/**
	 * The provider is Filinq's, app-local, healthy, and offers list only.
	 *
	 * @return void
	 */
	public function testTheProviderIsAnAppLocalReadOnlyLeaf(): void {
		$provider = $this->provider();

		$this->assertSame('filinq-templates', $provider->getId());
		$this->assertSame('nl:Templates', $provider->getLabel());
		$this->assertSame('FileDocumentEditOutline', $provider->getIcon());
		$this->assertSame('documents', $provider->getGroup());
		$this->assertSame('filinq', $provider->getRequiredApp());
		$this->assertSame('app-local', $provider->getStorageStrategy());
		$this->assertNull($provider->getOpenConnectorSource());
		$this->assertTrue($provider->isEnabled());
		$this->assertNull($provider->requiresPermission());
		$this->assertSame(['type' => 'none'], $provider->authRequirements());
		$this->assertSame('ok', $provider->health()['status']);
		$this->assertFalse(method_exists($provider, 'create'));
		$this->assertFalse(method_exists($provider, 'get'));

	}//end testTheProviderIsAnAppLocalReadOnlyLeaf()
}//end class
