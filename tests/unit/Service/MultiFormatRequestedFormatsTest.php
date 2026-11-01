<?php

/**
 * The formats a multi-format request asks for, or why it is refused.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-2.4
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Service\DocumentRenderPipeline;
use OCA\Filinq\Service\DocumentService;
use OCA\Filinq\Service\DocumentStorageService;
use OCA\Filinq\Service\GeneratedDocumentLogger;
use OCA\Filinq\Service\MultiFormatOutputProducer;
use OCA\Filinq\Service\TemplateService;
use PHPUnit\Framework\TestCase;

/**
 * requestedFormats() answers, compared with the outcomes of the implementation
 * before it was split.
 *
 * @covers \OCA\Filinq\Service\MultiFormatOutputProducer
 */
class MultiFormatRequestedFormatsTest extends TestCase {

	/**
	 * Options and the outcome: the formats, or the exception code and message.
	 *
	 * @return array<string, array{0: array<string, mixed>, 1: array<int, string>|string}>
	 */
	public static function requests(): array {
		$valid = 'Valid formats: pdf, odf, docx, html';
		return [
			'one format' => [['formats' => ['pdf']], ['pdf']],
			'every format' => [['formats' => ['html', 'docx', 'odf', 'pdf']], ['html', 'docx', 'odf', 'pdf']],
			'duplicates keep the first position' => [['formats' => ['docx', 'pdf', 'docx', 'pdf']], ['docx', 'pdf']],
			'a leading duplicate is renumbered' => [['formats' => ['pdf', 'pdf', 'docx']], ['pdf', 'docx']],
			'format and formats' => [['format' => 'pdf', 'formats' => ['pdf']], '400 Use options.format or options.formats, not both'],
			'a null format next to formats' => [['format' => null, 'formats' => ['pdf']], '400 Use options.format or options.formats, not both'],
			'format only' => [['format' => 'pdf'], '400 Use options.format or options.formats, not both'],
			'no formats' => [[], '400 options.formats must be a non-empty list of formats'],
			'null formats' => [['formats' => null], '400 options.formats must be a non-empty list of formats'],
			'a string' => [['formats' => 'pdf'], '400 options.formats must be a non-empty list of formats'],
			'an empty list' => [['formats' => []], '400 options.formats must be a non-empty list of formats'],
			'a map' => [['formats' => ['a' => 'pdf']], '400 options.formats must be a non-empty list of formats'],
			'a list with a gap' => [['formats' => [0 => 'pdf', 2 => 'docx']], '400 options.formats must be a non-empty list of formats'],
			'an unknown format' => [['formats' => ['pdf', 'xlsx']], '400 Unsupported format "xlsx" in options.formats. ' . $valid],
			'an upper case format' => [['formats' => ['PDF']], '400 Unsupported format "PDF" in options.formats. ' . $valid],
			'a number' => [['formats' => [1]], '400 Unsupported format 1 in options.formats. ' . $valid],
			'a boolean' => [['formats' => [true]], '400 Unsupported format true in options.formats. ' . $valid],
			'the first unknown wins' => [['formats' => ['odt', 'xlsx']], '400 Unsupported format "odt" in options.formats. ' . $valid],
		];
	}//end requests()

	/**
	 * The outcome matches the one before the split.
	 *
	 * @param array<string, mixed> $options The generation options.
	 * @param array<int, string>|string $expected The formats, or "<code> <message>".
	 *
	 * @dataProvider requests
	 *
	 * @return void
	 */
	public function testOutcome(array $options, array|string $expected): void {
		$producer = new MultiFormatOutputProducer(
			documents: $this->createMock(DocumentService::class),
			templates: $this->createMock(TemplateService::class),
			renderPipeline: $this->createMock(DocumentRenderPipeline::class),
			storage: $this->createMock(DocumentStorageService::class),
			auditLog: $this->createMock(GeneratedDocumentLogger::class),
		);

		try {
			$outcome = $producer->requestedFormats(options: $options);
		} catch (Exception $e) {
			$outcome = $e->getCode() . ' ' . $e->getMessage();
		}

		$this->assertSame($expected, $outcome);
	}//end testOutcome()
}//end class
