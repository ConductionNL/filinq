<?php

/**
 * The PDF options of a generation carry what accessible output needs from
 * the template: its name as the fallback title, its language when it has
 * one. The caller's own options win.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/pdfua-accessible-output/tasks.md#task-1.3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\Charts\SvgRasterizer;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentRenderPipeline;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\TemplateRenderer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DocumentRenderPipelineAccessibleTest extends TestCase {

	private function pipeline(): DocumentRenderPipeline {
		return new DocumentRenderPipeline(
			$this->createMock(TemplateRenderer::class),
			$this->createMock(PdfService::class),
			$this->createMock(DocumentObjectServiceResolver::class),
			$this->createMock(LoggerInterface::class),
			$this->createMock(SvgRasterizer::class)
		);
	}//end pipeline()

	public function testTheTemplateGivesNameAndLanguage(): void {
		$options = $this->pipeline()->buildPdfOptions(['name' => 'Besluit parkeervergunning', 'language' => 'nl'], null, ['pdfOptions' => ['accessible' => true]]);

		$this->assertTrue($options['accessible']);
		$this->assertSame('Besluit parkeervergunning', $options['templateName']);
		$this->assertSame('nl', $options['templateLanguage']);
	}//end testTheTemplateGivesNameAndLanguage()

	public function testTheCallersTitleAndLanguageStay(): void {
		$options = $this->pipeline()->buildPdfOptions(['name' => 'Sjabloon'], null, ['pdfOptions' => ['title' => 'Eigen titel', 'lang' => 'en']]);

		$this->assertSame('Eigen titel', $options['title']);
		$this->assertSame('en', $options['lang']);
		$this->assertArrayNotHasKey('templateLanguage', $options);
	}//end testTheCallersTitleAndLanguageStay()
}//end class
