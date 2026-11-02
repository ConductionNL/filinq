<?php

/**
 * Where GrondslagenPdfWriter saves the per-dossier summary.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-8
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\Conversion\OutputLayoutResolver;
use OCA\Filinq\Service\FinalDocumentService;
use OCA\Filinq\Service\GrondslagenPdfWriter;
use OCA\Filinq\Service\PdfService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The summary sits beside the redacted copies, in the configured subfolder.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class GrondslagenPdfWriterDossierSummaryTest extends TestCase {

	/**
	 * Build the writer over the real resolver, with the subfolder named $name.
	 *
	 * @param string $name Configured subfolder name.
	 *
	 * @return GrondslagenPdfWriter
	 */
	private function writer(string $name = 'anonymised'): GrondslagenPdfWriter {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturn($name);
		return new GrondslagenPdfWriter(
			pdfService: $this->createMock(PdfService::class),
			finalDocuments: $this->createMock(FinalDocumentService::class),
			layoutResolver: new OutputLayoutResolver($config, $this->createMock(LoggerInterface::class))
		);

	}//end writer()

	/**
	 * A first summary creates the subfolder and lands in it.
	 *
	 * @return void
	 */
	public function testSummaryLandsInANewSubfolder(): void {
		$written = $this->createMock(File::class);
		$sub = $this->createMock(Folder::class);
		$sub->method('nodeExists')->with('grondslagen.pdf')->willReturn(false);
		$sub->expects($this->once())->method('newFile')->with('grondslagen.pdf', '%PDF')->willReturn($written);

		$dossier = $this->createMock(Folder::class);
		$dossier->method('nodeExists')->with('redacted')->willReturn(false);
		$dossier->expects($this->once())->method('newFolder')->with('redacted')->willReturn($sub);
		$dossier->expects($this->never())->method('newFile');

		$this->assertSame($written, $this->writer('redacted')->saveDossierSummary($dossier, '%PDF'));

	}//end testSummaryLandsInANewSubfolder()

	/**
	 * A regenerated summary refreshes the one already in the subfolder.
	 *
	 * @return void
	 */
	public function testSummaryRefreshesTheOneInTheExistingSubfolder(): void {
		$existing = $this->createMock(File::class);
		$existing->expects($this->once())->method('putContent')->with('%PDF2');
		$sub = $this->createMock(Folder::class);
		$sub->method('nodeExists')->with('grondslagen.pdf')->willReturn(true);
		$sub->method('get')->with('grondslagen.pdf')->willReturn($existing);

		$dossier = $this->createMock(Folder::class);
		$dossier->method('nodeExists')->with('anonymised')->willReturn(true);
		$dossier->method('get')->with('anonymised')->willReturn($sub);
		$dossier->expects($this->never())->method('newFolder');

		$this->assertSame($existing, $this->writer()->saveDossierSummary($dossier, '%PDF2'));

	}//end testSummaryRefreshesTheOneInTheExistingSubfolder()
}//end class
