<?php

/**
 * Separator Sheet Service
 *
 * Prints the sheets the splitter reads back. Filinq prints them and filinq
 * reads them, which is the whole reason the payload is fixed:
 * `filinq:sep:v1:<profileId>` and, when a case number is given,
 * `:<caseNumber>`.
 *
 * Every sheet carries the payload TWICE: as a QR code for scanners that split
 * in hardware, and as plain text underneath it, which is what this app's own
 * splitter reads. Reading the text rather than decoding the QR keeps the split
 * inside the process: no decoder binary, no external service, and a sheet a
 * human can read to see what it is for.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Com\Tecnick\Barcode\Barcode;
use OCA\Filinq\Kernel\Contracts\SeparatorSheetServiceInterface;
use RuntimeException;
use Throwable;

/**
 * Renders separator sheets, one page per case.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 */
class SeparatorSheetService implements SeparatorSheetServiceInterface {

	/**
	 * Constructor.
	 *
	 * @param PdfService $pdf The renderer.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly PdfService $pdf,
	) {

	}//end __construct()

	/**
	 * The payload one sheet carries.
	 *
	 * @param string $profileId The scan profile the sheet belongs to.
	 * @param string $caseNumber The case the documents after it belong to, or an empty string.
	 *
	 * @return string The payload.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function payload(string $profileId, string $caseNumber = ''): string {
		$payload = self::PAYLOAD_PREFIX . $profileId;
		if (trim($caseNumber) !== '') {
			$payload .= ':' . trim($caseNumber);
		}

		return $payload;

	}//end payload()

	/**
	 * The case number a payload names, if it names one.
	 *
	 * @param string $payload The payload read off a page.
	 *
	 * @return string|null The case number, an empty string when the sheet named none, or null when this is not a separator.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function readPayload(string $payload): ?string {
		$original = $this->readOriginal(payload: $payload);
		if ($original !== null) {
			return $original;
		}

		$position = stripos($payload, self::PAYLOAD_PREFIX);
		if ($position === false) {
			return null;
		}

		$rest = substr($payload, ($position + strlen(self::PAYLOAD_PREFIX)));
		$rest = trim((string)preg_replace('/\s.*$/s', '', $rest));
		if ($rest === '') {
			return '';
		}

		$parts = explode(':', $rest);
		if (count($parts) < 2) {
			return '';
		}

		return trim($parts[1]);

	}//end readPayload()

	/**
	 * The payload of a sheet naming one paper original.
	 *
	 * @param string $profileId  The scan profile the sheet belongs to.
	 * @param string $originalId The paperOriginal the pages after it are the scan of.
	 *
	 * @return string The payload.
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-a-separator-sheet-names-the-original-it-precedes-req-pad-002
	 */
	public function originalPayload(string $profileId, string $originalId): string {
		return self::PAYLOAD_PREFIX_V2 . trim($profileId) . ':' . self::ORIGINAL_MARKER . trim($originalId);

	}//end originalPayload()

	/**
	 * Render one separator sheet per paper original.
	 *
	 * @param string                           $profileId The scan profile the sheets belong to.
	 * @param array<int, array<string, mixed>> $originals The paperOriginal records, each with `id`, `shelfMark` and `description`.
	 *
	 * @return string The sheets as PDF bytes.
	 *
	 * @throws RuntimeException When there is no original to print or the sheets do not render.
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-a-separator-sheet-names-the-original-it-precedes-req-pad-002
	 */
	public function renderOriginals(string $profileId, array $originals): string {
		if (trim($profileId) === '') {
			throw new RuntimeException(message: 'A separator sheet names the scan profile it belongs to.');
		}

		$pages = [];
		foreach ($originals as $original) {
			$originalId = trim((string)($original['id'] ?? ($original['@self']['id'] ?? '')));
			if ($originalId !== '') {
				$pages[] = $this->originalSheetHtml(payload: $this->originalPayload(profileId: $profileId, originalId: $originalId), original: $original);
			}
		}

		if ($pages === []) {
			throw new RuntimeException(message: 'There is no paper original to print a separator sheet for.');
		}

		return $this->renderPages(pages: $pages);

	}//end renderOriginals()

	/**
	 * The `po:<originalId>` a v2 payload names, or null when it is no v2 separator.
	 *
	 * @param string $payload The payload read off a page.
	 *
	 * @return string|null
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-a-separator-sheet-names-the-original-it-precedes-req-pad-002
	 */
	private function readOriginal(string $payload): ?string {
		$position = stripos($payload, self::PAYLOAD_PREFIX_V2);
		if ($position === false) {
			return null;
		}

		$rest = substr($payload, ($position + strlen(self::PAYLOAD_PREFIX_V2)));
		$rest = trim((string)preg_replace('/\s.*$/s', '', $rest));
		$marker = strpos($rest, ':' . self::ORIGINAL_MARKER);
		if ($marker === false) {
			return null;
		}

		$originalId = trim(substr($rest, ($marker + 1 + strlen(self::ORIGINAL_MARKER))));
		if ($originalId === '') {
			return null;
		}

		return self::ORIGINAL_MARKER . $originalId;

	}//end readOriginal()

	/**
	 * Render separator sheets, one page per case number.
	 *
	 * A print with no case numbers still produces ONE sheet: a separator that
	 * names no case is a perfectly ordinary thing to put between two documents
	 * that both still have to be sorted by hand.
	 *
	 * @param string $profileId The scan profile the sheets belong to.
	 * @param array<int, string> $caseNumbers The cases to print a sheet for.
	 *
	 * @return string The sheets as PDF bytes.
	 *
	 * @throws RuntimeException When the sheets do not render.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function render(string $profileId, array $caseNumbers = []): string {
		if (trim($profileId) === '') {
			throw new RuntimeException(message: 'A separator sheet names the scan profile it belongs to.');
		}

		$numbers = [];
		foreach ($caseNumbers as $number) {
			$number = trim((string)$number);
			if ($number !== '') {
				$numbers[] = $number;
			}
		}

		if ($numbers === []) {
			$numbers = [''];
		}

		$pages = [];
		foreach ($numbers as $number) {
			$pages[] = $this->sheetHtml(payload: $this->payload(profileId: $profileId, caseNumber: $number), caseNumber: $number);
		}

		return $this->renderPages(pages: $pages);

	}//end render()

	/**
	 * The sheets' pages as one PDF.
	 *
	 * @param array<int, string> $pages One sheet's HTML each.
	 *
	 * @return string The PDF bytes.
	 *
	 * @throws RuntimeException When the sheets do not render.
	 */
	private function renderPages(array $pages): string {
		try {
			return $this->pdf->generatePdfFromHtml(
				implode('<pagebreak />', $pages),
				['format' => 'A4', 'orientation' => 'P']
			);
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'The separator sheets did not render: ' . $e->getMessage(),
				code: 0,
				previous: $e
			);
		}

	}//end renderPages()

	/**
	 * One sheet's HTML.
	 *
	 * @param string $payload The payload it carries.
	 * @param string $caseNumber The case it names, or an empty string.
	 *
	 * @return string The HTML.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	private function sheetHtml(string $payload, string $caseNumber): string {
		$heading = 'Scheidingsvel';
		if ($caseNumber !== '') {
			$heading .= ' voor zaak ' . htmlspecialchars($caseNumber, ENT_QUOTES);
		}

		$safePayload = htmlspecialchars($payload, ENT_QUOTES);

		// The QR is drawn locally (qrImage()). The payload is repeated as
		// text below it because that text is what the splitter reads: see the
		// class comment.
		return '<div style="text-align:center;font-family:sans-serif;">'
			. '<h1 style="font-size:28pt;margin-top:60pt;">' . $heading . '</h1>'
			. '<p style="font-size:12pt;">Leg dit vel tussen twee documenten. Wat erna komt, hoort bij het volgende document.</p>'
			. $this->qrImage(payload: $payload)
			. '<p style="font-size:16pt;letter-spacing:1pt;margin-top:24pt;font-family:monospace;">' . $safePayload . '</p>'
			. '</div>';

	}//end sheetHtml()

	/**
	 * One original's sheet: the shelf mark and description in print above the code.
	 *
	 * @param string               $payload  The payload it carries.
	 * @param array<string, mixed> $original The paperOriginal.
	 *
	 * @return string The HTML.
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-a-separator-sheet-names-the-original-it-precedes-req-pad-002
	 */
	private function originalSheetHtml(string $payload, array $original): string {
		$shelfMark = htmlspecialchars(trim((string)($original['shelfMark'] ?? '')), ENT_QUOTES);
		$description = htmlspecialchars(trim((string)($original['description'] ?? '')), ENT_QUOTES);
		$safePayload = htmlspecialchars($payload, ENT_QUOTES);

		return '<div style="text-align:center;font-family:sans-serif;">'
			. '<h1 style="font-size:28pt;margin-top:60pt;">Scheidingsvel voor ' . $shelfMark . '</h1>'
			. '<p style="font-size:14pt;">' . $description . '</p>'
			. '<p style="font-size:12pt;">Leg dit vel voor het origineel met dit kenmerk. Wat erna komt, is de scan ervan.</p>'
			. $this->qrImage(payload: $payload)
			. '<p style="font-size:16pt;letter-spacing:1pt;margin-top:24pt;font-family:monospace;">' . $safePayload . '</p>'
			. '</div>';

	}//end originalSheetHtml()

	/**
	 * The QR code of a payload, as an image tag for the sheet.
	 *
	 * Drawn by tc-lib-barcode, which filinq already ships (tc-lib-pdf needs
	 * it; WaarmerkStampComposer draws its QR the same way). mPDF's own
	 * `<barcode type="QR">` needs mpdf/qrcode, which mPDF only lists as a
	 * development dependency: every print failed with 'Mpdf\QrCode package
	 * was not found' on a live run (25 Nov 2026).
	 *
	 * @param string $payload The payload the code carries.
	 *
	 * @return string The image tag, about 4 cm wide.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/tasks.md#task-5.2
	 */
	private function qrImage(string $payload): string {
		$svg = (new Barcode())->getBarcodeObj('QRCODE,M', $payload, -4, -4, 'black', [0, 0, 0, 0])->getSvgCode();

		return '<img src="data:image/svg+xml;base64,' . base64_encode($svg) . '" style="width:40mm;height:40mm;" alt="" />';

	}//end qrImage()
}//end class
