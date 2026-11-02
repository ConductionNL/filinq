<?php

/**
 * tc-lib-pdf Conversion Backend
 *
 * Same inputs and output as MpdfBackend (HTML and plain text to PDF/A-3b),
 * rendered with tecnickcom/tc-lib-pdf (LGPL-3.0-or-later) instead of
 * mPDF (GPL-2.0). Sits in front of MpdfBackend in the cascade and is off
 * by default; when it is off or fails, MpdfBackend still converts.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Conversion;

use OCA\Filinq\Service\PdfService;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;

/**
 * MpdfBackend with the rendering step swapped for tc-lib-pdf.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 */
class TcLibPdfBackend extends MpdfBackend {

	/**
	 * App config key that enables this backend. Default false.
	 */
	public const ENABLED_KEY = 'filinq.conversion.backends.tclibpdf_enabled';

	/**
	 * Constructor.
	 *
	 * @param PdfService      $pdfService PDF service (used by the parent only).
	 * @param IAppConfig      $appConfig  Tenant configuration provider.
	 * @param LoggerInterface $logger     Logger for diagnostics.
	 */
	public function __construct(
		PdfService $pdfService,
		private readonly IAppConfig $appConfig,
		LoggerInterface $logger,
	) {
		parent::__construct(pdfService: $pdfService, appConfig: $appConfig, logger: $logger);
	}//end __construct()

	/**
	 * Identifier surfaced in the 422 body's `conversionAttempts[].backend`.
	 *
	 * @return string
	 *
	 * @spec openspec/specs/pdf-conversion/spec.md
	 */
	public function name(): string {
		return 'tc-lib-pdf';
	}//end name()

	/**
	 * Enabled only when the tenant sets the app config key to `true`.
	 *
	 * @return bool
	 *
	 * @spec openspec/specs/pdf-conversion/spec.md
	 */
	public function isAvailable(): bool {
		return $this->appConfig->getValueString('filinq', self::ENABLED_KEY, 'false') === 'true';
	}//end isAvailable()

	/**
	 * Render with tc-lib-pdf instead of mPDF.
	 *
	 * @param string $html  HTML document.
	 * @param string $title Document title.
	 *
	 * @return string PDF bytes.
	 *
	 * @spec openspec/specs/pdf-conversion/spec.md
	 */
	protected function renderPdf(string $html, string $title): string {
		return (new TcLibPdfaRenderer())->render(html: $html, title: $title);
	}//end renderPdf()
}//end class
