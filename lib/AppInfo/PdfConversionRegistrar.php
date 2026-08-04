<?php

/**
 * DocuDesk PDF Conversion Registrar
 *
 * Wires the ordered PDF-conversion backend cascade into the container.
 * Extracted from `Application`.
 *
 * @category  AppInfo
 * @package   OCA\DocuDesk\AppInfo
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/pdf-conversion/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\AppInfo;

use OCA\DocuDesk\Service\Conversion\EmlBackend;
use OCA\DocuDesk\Service\Conversion\LibreOfficeHeadlessBackend;
use OCA\DocuDesk\Service\Conversion\MpdfBackend;
use OCA\DocuDesk\Service\Conversion\OfficeAppBackend;
use OCA\DocuDesk\Service\Conversion\PhpWordBackend;
use OCA\DocuDesk\Service\PdfConversionService;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use Psr\Log\LoggerInterface;

/**
 * Registers the PdfConversionService backend cascade.
 *
 * @category AppInfo
 * @package  OCA\DocuDesk\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class PdfConversionRegistrar
{
    /**
     * Wire the PDF-conversion cascade.
     *
     * PdfConversionService takes an ordered array of backends in its
     * constructor; Nextcloud's DI cannot autowire an `array` parameter, so
     * without this explicit registration every service that depends on
     * PdfConversionService (e.g. AnonymizationService → AnonymizationController)
     * fails to construct and the request 500s with a "Could not resolve
     * backends!" QueryException before the controller body ever runs. Order =
     * OfficeApp → LibreOffice → PhpWord → mPDF → EML (first success wins).
     * LibreOfficeHeadlessBackend (pdf-conversion-service) shells out to
     * `soffice --headless` with a lock + timeout as a high-fidelity fallback
     * when the NC IConversionManager providers are unavailable.
     *
     * @param IRegistrationContext $context The registration context.
     *
     * @return void
     *
     * @spec openspec/specs/pdf-conversion/spec.md
     */
    public function register(IRegistrationContext $context): void
    {
        $context->registerService(
            PdfConversionService::class,
            static function ($c): PdfConversionService {
                return new PdfConversionService(
                    backends: [
                        // OURS DI style ($c->get) preserved — autowires each
                        // backend, keeps development's LibreOfficeHeadlessBackend
                        // fallback, and pulls in Robert's EmlBackend (which now
                        // autowires EmlPdfAssemblyService via its constructor).
                        // Robert's explicit `new` variant referenced an undefined
                        // $conversionManager and dropped the LibreOffice backend.
                        $c->get(OfficeAppBackend::class),
                        $c->get(LibreOfficeHeadlessBackend::class),
                        $c->get(PhpWordBackend::class),
                        $c->get(MpdfBackend::class),
                        $c->get(EmlBackend::class),
                    ],
                    logger: $c->get(LoggerInterface::class),
                );
            }
        );

    }//end register()
}//end class
