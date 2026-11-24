<?php

/**
 * Filinq Received Stamp Service
 *
 * A copy of an arrived PDF with a received stamp on its first page
 * (intake-acknowledge-and-stamp D5, REQ-IAS-003): "Ontvangen", the date and,
 * when there is one, the registration number, top right. Every page is
 * carried over at its own size. The copy is written beside the arrived file
 * as `<name> (ontvangen).pdf`; the arrived file keeps its bytes and its
 * checksum, because it is the evidence of what arrived.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/intake-acknowledge-and-stamp
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;
use RuntimeException;
use setasign\Fpdi\Fpdi;
use Throwable;

/**
 * Stamps a received copy of an arrived PDF.
 *
 * @spec openspec/changes/intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-a-received-stamp-goes-on-a-copy-never-on-what-arrived-req-ias-003
 */
class ReceivedStampService {

	/**
	 * The word on the stamp.
	 */
	public const WORD = 'Ontvangen';

	/**
	 * What the copy's name ends with, before the extension.
	 */
	public const SUFFIX = ' (ontvangen)';

	/**
	 * Constructor.
	 *
	 * @param IRootFolder     $rootFolder Finds the arrived file and its folder.
	 * @param LoggerInterface $logger     Records a stamp that could not be made.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * The PDF with the stamp on page 1; every other page as it was.
	 *
	 * @param string $pdf    The arrived PDF's bytes.
	 * @param string $date   The received date as the stamp shows it.
	 * @param string $number The registration number, or '' without one.
	 *
	 * @return string The stamped copy's bytes.
	 *
	 * @throws RuntimeException When the bytes are not a PDF this service can read.
	 *
	 * @spec openspec/changes/intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-a-received-stamp-goes-on-a-copy-never-on-what-arrived-req-ias-003
	 */
	public function stamp(string $pdf, string $date, string $number = ''): string {
		$document = new Fpdi('P', 'pt');
		$document->SetAutoPageBreak(false);
		try {
			$pages = $document->setSourceFile((new PdfStreamReaderFactory())->fromString(content: $pdf));
		} catch (Throwable $e) {
			throw new RuntimeException('A received stamp cannot be placed on this document: ' . $e->getMessage(), 0, $e);
		}

		for ($page = 1; $page <= $pages; $page++) {
			$template = $document->importPage($page);
			$size     = $document->getTemplateSize($template);
			$document->AddPage($size['orientation'], [$size['width'], $size['height']]);
			$document->useTemplate($template);
			if ($page === 1) {
				$this->drawStamp(document: $document, lines: $this->lines(date: $date, number: $number));
			}
		}

		return $document->Output('S');
	}//end stamp()

	/**
	 * Write a stamped copy beside the arrived file and answer its file id.
	 *
	 * Only a PDF is stamped. The arrived file is read, never written. Null
	 * when the file is gone, is not a PDF or cannot be stamped; the arrival
	 * is never failed for it.
	 *
	 * @param int    $fileId The arrived file's id.
	 * @param string $date   The received date as the stamp shows it.
	 * @param string $number The registration number, or ''.
	 *
	 * @return int|null The stamped copy's file id.
	 *
	 * @spec openspec/changes/intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-a-received-stamp-goes-on-a-copy-never-on-what-arrived-req-ias-003
	 */
	public function stampFile(int $fileId, string $date, string $number = ''): int|null {
		try {
			$arrived = $this->rootFolder->getFirstNodeById($fileId);
			if (($arrived instanceof File) === false || $arrived->getMimetype() !== 'application/pdf') {
				return null;
			}

			$folder = $arrived->getParent();
			if (($folder instanceof Folder) === false) {
				return null;
			}

			$stamped = $this->stamp(pdf: (string)$arrived->getContent(), date: $date, number: $number);
			$copy    = $folder->newFile($folder->getNonExistingName($this->copyName(name: $arrived->getName())), $stamped);

			return $copy->getId();
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[ReceivedStampService] no stamped copy was made; the arrived file is unchanged',
				context: ['fileId' => $fileId, 'error' => $e->getMessage()]
			);
			return null;
		}//end try
	}//end stampFile()

	/**
	 * The copy's name: `brief.pdf` becomes `brief (ontvangen).pdf`.
	 *
	 * @param string $name The arrived file's name.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-a-received-stamp-goes-on-a-copy-never-on-what-arrived-req-ias-003
	 */
	public function copyName(string $name): string {
		$base = preg_replace('/\.pdf$/i', '', $name);

		return (string)$base . self::SUFFIX . '.pdf';
	}//end copyName()

	/**
	 * The stamp's lines: the word, the date, and the number when there is one.
	 *
	 * @param string $date   The received date.
	 * @param string $number The registration number, or ''.
	 *
	 * @return list<string>
	 */
	private function lines(string $date, string $number): array {
		$lines = [self::WORD, $date];
		if (trim($number) !== '') {
			$lines[] = trim($number);
		}

		return $lines;
	}//end lines()

	/**
	 * Draw the bordered stamp in the top right corner of the current page.
	 *
	 * @param Fpdi         $document The document, on the page to stamp.
	 * @param list<string> $lines    The lines.
	 *
	 * @return void
	 */
	private function drawStamp(Fpdi $document, array $lines): void {
		$width     = (float)$document->GetPageWidth();
		$boxWidth  = 150.0;
		$lineHigh  = 14.0;
		$margin    = 24.0;
		$boxHeight = ((float)count($lines) * $lineHigh) + 8.0;
		$left      = ($width - $margin - $boxWidth);

		$document->SetDrawColor(180, 0, 0);
		$document->SetTextColor(180, 0, 0);
		$document->SetLineWidth(1.5);
		$document->Rect($left, $margin, $boxWidth, $boxHeight);
		foreach ($lines as $index => $line) {
			$style = '';
			if ($index === 0) {
				$style = 'B';
			}

			$document->SetFont('Helvetica', $style, 10);
			$document->SetXY($left, ($margin + 4.0 + ((float)$index * $lineHigh)));
			$document->Cell($boxWidth, $lineHigh, $this->latin1(text: $line), 0, 0, 'C');
		}
	}//end drawStamp()

	/**
	 * Text in the core fonts' encoding.
	 *
	 * @param string $text UTF-8 text.
	 *
	 * @return string
	 */
	private function latin1(string $text): string {
		if (function_exists('mb_convert_encoding') === false) {
			return $text;
		}

		return (string)mb_convert_encoding($text, 'Windows-1252', 'UTF-8');
	}//end latin1()
}//end class
