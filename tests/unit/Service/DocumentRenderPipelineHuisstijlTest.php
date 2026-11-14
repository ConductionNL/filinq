<?php

/**
 * Unit tests for the organisation's default huisstijl in DocumentRenderPipeline.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/multi-tenant-hardening/tasks.md#task-3.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\Charts\ChartSvgRenderer;
use OCA\Filinq\Service\Charts\SvgRasterizer;
use OCA\Filinq\Service\Charts\TableHtmlRenderer;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentRenderPipeline;
use OCA\Filinq\Service\EffectiveSettings;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\Support\PdfRendering;
use OCA\Filinq\Service\TemplateRenderer;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

/**
 * REQ-DDMTH-004: a document generated without a named huisstijl uses its organisation's default.
 *
 * @covers \OCA\Filinq\Service\DocumentRenderPipeline
 * @uses   \OCA\Filinq\Service\DocumentObjectServiceResolver
 * @uses   \OCA\Filinq\Service\TemplateRenderer
 * @uses   \OCA\Filinq\Service\Support\PdfRendering
 */
class DocumentRenderPipelineHuisstijlTest extends TestCase {
	/**
	 * The pipeline over a store holding Demostad's huisstijl, with the given default.
	 *
	 * @param string|null $default The organisation's default huisstijl.
	 * @param array<int, string> $asked Receives the huisstijl ids looked up.
	 *
	 * @return DocumentRenderPipeline
	 */
	private function pipeline(?string $default, array &$asked): DocumentRenderPipeline {
		$objects = $this->createMock(ObjectService::class);
		$objects->method('find')->willReturnCallback(
			function (...$args) use (&$asked): ?array {
				$asked[] = (string)($args['id'] ?? $args[0]);
				return ['id' => 'hs-demostad', 'primaryColor' => '#004b87'];
			}
		);
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($objects);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);
		$effective = $this->createMock(EffectiveSettings::class);
		$effective->method('defaultHuisstijl')->willReturn($default);

		return new DocumentRenderPipeline(
			new PdfRendering(renderer: new TemplateRenderer(new NullLogger(), new ChartSvgRenderer(), new TableHtmlRenderer()), pdf: $this->createMock(PdfService::class)),
			new DocumentObjectServiceResolver($container, $apps),
			new NullLogger(),
			$this->createMock(SvgRasterizer::class),
			null,
			null,
			$effective
		);
	}//end pipeline()

	/**
	 * Without a named huisstijl the organisation's default is loaded; a named one wins.
	 *
	 * @return void
	 */
	public function testTheOrganisationsDefaultIsUsedWhenNoneIsNamed(): void {
		$asked = [];
		$this->assertSame(['id' => 'hs-demostad', 'primaryColor' => '#004b87'], $this->pipeline(default: 'hs-demostad', asked: $asked)->loadHuisstijl(null));
		$this->pipeline(default: 'hs-demostad', asked: $asked)->loadHuisstijl('hs-named');

		$this->assertSame(['hs-demostad', 'hs-named'], $asked);
	}//end testTheOrganisationsDefaultIsUsedWhenNoneIsNamed()

	/**
	 * Without a default, no huisstijl is loaded.
	 *
	 * @return void
	 */
	public function testWithoutADefaultNoHuisstijlIsLoaded(): void {
		$asked = [];

		$this->assertNull($this->pipeline(default: null, asked: $asked)->loadHuisstijl(''));
		$this->assertSame([], $asked);
	}//end testWithoutADefaultNoHuisstijlIsLoaded()
}//end class
