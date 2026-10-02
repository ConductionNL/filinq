<?php

/**
 * Format matrix
 *
 * Which output formats this server can make right now, and why not when it
 * cannot. Computed per request from the conversion cascade's own report, so
 * installing or removing LibreOffice shows at once. The reason for an
 * unavailable format is the same sentence a forced generation of it fails
 * with.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-2.3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend;

/**
 * Reports the producible output formats per instance, template and flow.
 *
 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-2.3
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class FormatMatrixService {
	/**
	 * The formats document generation offers, in display order.
	 *
	 * @var string[]
	 */
	public const DOCUMENT_FORMATS = ['pdf', 'docx', 'odf', 'html'];

	/**
	 * The formats a letter can be made in, in display order.
	 *
	 * @var string[]
	 */
	public const CORRESPONDENCE_FORMATS = ['pdf', 'docx', 'html', 'email'];

	/**
	 * Formats made by LibreOffice from rendered HTML.
	 *
	 * @var string[]
	 */
	private const LIBREOFFICE_FORMATS = ['docx', 'odf'];

	/**
	 * Constructor.
	 *
	 * @param PdfConversionService $conversion The cascade, asked what its backends can do.
	 */
	public function __construct(
		private readonly PdfConversionService $conversion,
	) {

	}//end __construct()

	/**
	 * The matrix for a flow on this server.
	 *
	 * @param string $flow `documents` (generation) or `correspondence` (letters).
	 *
	 * @return array<string, array{available: bool, reason?: string}> Keyed by format, in display order.
	 *
	 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-2.3
	 */
	public function forInstance(string $flow = 'documents'): array {
		$formats = self::DOCUMENT_FORMATS;
		if ($flow === 'correspondence') {
			$formats = self::CORRESPONDENCE_FORMATS;
		}

		$libreOffice = $this->libreOfficeAvailable();
		$matrix = [];
		foreach ($formats as $format) {
			$matrix[$format] = ['available' => true];
			if ($libreOffice === false && in_array($format, self::LIBREOFFICE_FORMATS, true) === true) {
				$matrix[$format] = ['available' => false, 'reason' => LibreOfficeHeadlessBackend::UNAVAILABLE_REASON];
			}
		}

		return $matrix;

	}//end forInstance()

	/**
	 * The matrix for one template.
	 *
	 * A Twig template is rendered to HTML, from which all four formats are
	 * made, so its matrix is the instance's. An office template is a filled
	 * DOCX: its PDF always comes out (LibreOffice, else PhpWord and mPDF), while
	 * its DOCX passthrough, HTML (DOCX to HTML) and ODT are offered only with
	 * LibreOffice (REQ-DDMFO-007).
	 *
	 * @param array $template The template, as TemplateService returns it.
	 *
	 * @return array<string, array{available: bool, reason?: string}>
	 *
	 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-2.3
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-7
	 */
	public function forTemplate(array $template): array {
		if (($template['templateType'] ?? 'twig') !== 'office') {
			return $this->forInstance(flow: 'documents');
		}

		$libreOffice = $this->libreOfficeAvailable();
		$matrix = [];
		foreach (self::DOCUMENT_FORMATS as $format) {
			$matrix[$format] = ['available' => true];
			if ($libreOffice === false && $format !== 'pdf') {
				$matrix[$format] = ['available' => false, 'reason' => LibreOfficeHeadlessBackend::UNAVAILABLE_REASON];
			}
		}

		return $matrix;

	}//end forTemplate()

	/**
	 * Whether the cascade's LibreOffice backend is usable now.
	 *
	 * @return bool
	 */
	private function libreOfficeAvailable(): bool {
		foreach ($this->conversion->getCapabilities() as $backend) {
			if ($backend['name'] === 'libreoffice_headless') {
				return $backend['available'];
			}
		}

		return false;

	}//end libreOfficeAvailable()
}//end class
