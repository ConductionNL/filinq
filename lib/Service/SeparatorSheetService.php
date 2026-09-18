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
class SeparatorSheetService {

	/**
	 * The payload every separator sheet carries.
	 *
	 * Versioned on purpose: a sheet printed today is still in a drawer next
	 * year, and a splitter that changed its mind about the format would cut
	 * batches in the wrong place rather than refuse to.
	 *
	 * @var string
	 */
	public const PAYLOAD_PREFIX = 'filinq:sep:v1:';

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

	}//end render()

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

		// The barcode tag is mPDF's own, rendered locally. The payload is
		// repeated as text below it because that text is what the splitter
		// reads: see the class comment.
		return '<div style="text-align:center;font-family:sans-serif;">'
			. '<h1 style="font-size:28pt;margin-top:60pt;">' . $heading . '</h1>'
			. '<p style="font-size:12pt;">Leg dit vel tussen twee documenten. Wat erna komt, hoort bij het volgende document.</p>'
			. '<barcode code="' . $safePayload . '" type="QR" class="barcode" size="1.6" error="M" disableborder="1" />'
			. '<p style="font-size:16pt;letter-spacing:1pt;margin-top:24pt;font-family:monospace;">' . $safePayload . '</p>'
			. '</div>';

	}//end sheetHtml()
}//end class
