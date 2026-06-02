<?php

/**
 * EML Conversion Backend
 *
 * Handles EML (email) inputs by delegating to EmlPdfAssemblyService
 * to produce a rich PDF/A-3b with headers, body, and embedded/rendered
 * attachments. Requires OR's text-extraction-eml change to be applied
 * (parseEmlStructured method must exist).
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Conversion
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-8
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Conversion;

use OCA\DocuDesk\Exception\ConversionFailedException;
use OCA\DocuDesk\Service\EmlPdfAssemblyService;
use OCP\Files\File;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * EML conversion backend. isAvailable() returns true iff both:
 * 1. OR's TextExtractionService::parseEmlStructured exists
 * 2. EmlPdfAssemblyService is registered (injected via constructor)
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://conduction.nl
 *
 * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-8
 */
class EmlBackend implements ConversionBackendInterface
{


    /**
     * App config key for tenant override.
     */
    private const ENABLED_KEY = 'docudesk.conversion.backends.eml_enabled';


    /**
     * App identifier used for IAppConfig reads/writes.
     */
    private const APP_ID = 'docudesk';

    /**
     * Constructor.
     *
     * @param IAppConfig            $appConfig       Tenant configuration provider.
     * @param LoggerInterface       $logger          Logger for diagnostics.
     * @param EmlPdfAssemblyService $assemblyService The EML PDF assembly service.
     */
    public function __construct(
        private readonly IAppConfig $appConfig,
        private readonly LoggerInterface $logger,
        private readonly EmlPdfAssemblyService $assemblyService,
    ) {

    }//end __construct()

    /**
     * Backend identifier surfaced in diagnostics.
     *
     * @return string
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-8
     */
    public function name(): string
    {
        return 'eml';

    }//end name()

    /**
     * Returns true iff:
     * 1. The tenant flag is not set to false.
     * 2. OR's TextExtractionService has a parseEmlStructured method.
     * 3. EmlPdfAssemblyService is available (always true if injected).
     *
     * @return bool
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-8
     */
    public function isAvailable(): bool
    {
        $value = $this->appConfig->getValueString(
            app: self::APP_ID,
            key: self::ENABLED_KEY,
            default: 'true'
        );
        if ($value === 'false') {
            return false;
        }

        // Check that OR's parseEmlStructured method exists.
        if (class_exists('\OCA\OpenRegister\Service\TextExtractionService') === false) {
            return false;
        }

        if (method_exists('\OCA\OpenRegister\Service\TextExtractionService', 'parseEmlStructured') === false) {
            return false;
        }

        return true;

    }//end isAvailable()

    /**
     * Declare the input formats this backend handles.
     *
     * @param string $mimeType  Source MIME.
     * @param string $extension Source extension (lowercased, no dot).
     *
     * @return bool True for message/rfc822 (.eml) inputs.
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-8
     */
    public function canHandle(string $mimeType, string $extension): bool
    {
        return $mimeType === 'message/rfc822' || $extension === 'eml';

    }//end canHandle()

    /**
     * Convert an EML file to a rich PDF/A-3b via EmlPdfAssemblyService.
     *
     * @param File $source Source EML file node.
     *
     * @return File Newly written PDF file node.
     *
     * @throws ConversionFailedException When conversion fails.
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-8
     */
    public function convert(File $source): File
    {
        try {
            return $this->assemblyService->assemble(
                sourceFile: $source,
                sourceFilename: $source->getName()
            );
        } catch (ConversionFailedException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->logger->error(
                '[EmlBackend] Conversion failed',
                [
                    'source'    => $source->getPath(),
                    'exception' => get_class($e),
                    'message'   => $e->getMessage(),
                ]
            );
            throw new ConversionFailedException(
                message: 'EML conversion failed: '.$e->getMessage(),
                attempts: [
                    [
                        'name'      => $this->name(),
                        'available' => true,
                        'supports'  => true,
                        'reason'    => $e->getMessage(),
                    ],
                ],
                previous: $e
            );
        }//end try

    }//end convert()
}//end class
