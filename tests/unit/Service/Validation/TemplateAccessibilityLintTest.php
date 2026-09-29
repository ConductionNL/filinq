<?php

/**
 * The accessibility lint of a template preview: images without alt text,
 * heading jumps, tables without header cells and no language, each with
 * where it is, and nothing for a clean template.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Validation
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/pdfua-accessible-output/tasks.md#task-3.3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Validation;

use OCA\Filinq\Service\TemplatePreviewService;
use OCA\Filinq\Service\TemplateRenderer;
use OCA\Filinq\Service\TemplateService;
use OCA\Filinq\Service\Validation\TemplateAccessibilityLint;
use OCP\IConfig;
use PHPUnit\Framework\TestCase;

class TemplateAccessibilityLintTest extends TestCase {

	private const TRAP = '<h1>Besluit parkeervergunning Demostad</h1>'
		. '<img src="/apps/filinq/img/wapen.png">'
		. '<h3>Overwegingen</h3>'
		. '<img src="handtekening.png" alt="">'
		. '<table><tr><td>Kenteken</td></tr><tr><td>AB-123-C</td></tr></table>';

	public function testTheTrapTemplateReportsEachProblemWhereItIs(): void {
		$lint = (new TemplateAccessibilityLint())->lint(html: self::TRAP, instanceLanguage: 'nl');

		$this->assertSame(
			[
				['rule' => 'image-missing-alt', 'position' => 1, 'text' => 'wapen.png'],
				['rule' => 'image-missing-alt', 'position' => 2, 'text' => 'handtekening.png'],
				['rule' => 'heading-order-jump', 'position' => 2, 'text' => 'Overwegingen', 'from' => 'h1', 'to' => 'h3'],
				['rule' => 'table-without-headers', 'position' => 1, 'text' => 'Kenteken'],
			],
			$lint
		);
	}//end testTheTrapTemplateReportsEachProblemWhereItIs()

	public function testACleanTemplateLintsEmpty(): void {
		$clean = '<h1>Besluit</h1><h2>Overwegingen</h2><img src="wapen.png" alt="Wapen van Demostad"><table><tr><th>Kenteken</th></tr></table>';

		$this->assertSame([], (new TemplateAccessibilityLint())->lint(html: $clean, instanceLanguage: 'nl'));
	}//end testACleanTemplateLintsEmpty()

	public function testNoLanguageAnywhereIsReported(): void {
		$lint = (new TemplateAccessibilityLint())->lint(html: '<p>x</p>', instanceLanguage: '');
		$this->assertSame([['rule' => 'language-unresolved', 'position' => 0, 'text' => '']], $lint);

		$this->assertSame([], (new TemplateAccessibilityLint())->lint(html: '<div lang="nl"><p>x</p></div>', instanceLanguage: ''));
	}//end testNoLanguageAnywhereIsReported()

	public function testGoingBackUpAndStartingLowerAreNotJumps(): void {
		$this->assertSame([], (new TemplateAccessibilityLint())->lint(html: '<h2>A</h2><h3>B</h3><h2>C</h2><h1>D</h1><h2>E</h2>', instanceLanguage: 'nl'));
	}//end testGoingBackUpAndStartingLowerAreNotJumps()

	public function testThePreviewReturnsTheLintBesideTheHtmlAndStillRenders(): void {
		$renderer = $this->createMock(TemplateRenderer::class);
		$renderer->method('convertConditionalSections')->willReturnArgument(0);
		$renderer->method('renderTemplate')->willReturn(self::TRAP);
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueString')->willReturn('nl');
		$service = new TemplatePreviewService($renderer, $this->createMock(TemplateService::class), $config);

		$result = $service->previewWithLint(content: '{{ x }}', data: []);

		$this->assertSame(self::TRAP, $result['html']);
		$this->assertCount(4, $result['lint']);
	}//end testThePreviewReturnsTheLintBesideTheHtmlAndStillRenders()
}//end class
