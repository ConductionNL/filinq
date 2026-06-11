<?php

/**
 * EML Conversion Backend
 *
 * Bridges the PDF-conversion cascade to OpenRegister's structured EML
 * extractor + DocuDesk's `EmlPdfAssemblyService`. For EML inputs, the
 * backend:
 *
 *   1. Confirms OR's `TextExtractionService::parseEmlStructured` is
 *      available (the change's hard prerequisite).
 *   2. Delegates parsing to OR — gets back an `EmlStructure` value
 *      object (headers + body + attachments).
 *   3. Hands the structure to `EmlPdfAssemblyService::assemble` which
 *      emits the assembled PDF/A-3b bytes.
 *   4. Writes the bytes beside the source file, returning the new node.
 *
 * Tenant configuration:
 *   - `docudesk.conversion.backends.eml_enabled` (default `true`).
 *     When `false`, the backend stays unavailable even if OR is
 *     installed; lets operators temporarily disable EML conversion
 *     (e.g. to fall through to `outputFormat: "preserve"` during
 *     incident response).
 *
 * Cross-app dependency:
 *   - OR-side `text-extraction-eml` provides `parseEmlStructured()`
 *     returning an `\OCA\OpenRegister\Service\TextExtraction\EmlStructure`.
 *     The dependency is checked dynamically via `class_exists` +
 *     `method_exists` so DocuDesk still loads in installs without OR.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/eml-pdf-assembly/specs/eml-pdf-assembly/spec.md
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
 * EML conversion backend.
 *
 * `isAvailable()` reflects three signals: the tenant flag, the
 * presence of OR's structured-parse API, and the presence of the
 * `EmlPdfAssemblyService` (the latter is constructor-injected so it's
 * always present in this build). `canHandle()` claims `message/rfc822`
 * + `.eml`. `convert()` walks the parse→assemble→write path and
 * surfaces any OR-side parse failure as a `ConversionFailedException`
 * with the structured `attempts` payload the 422 contract expects.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 */
class EmlBackend implements ConversionBackendInterface
{


    /**
     * App config key for the tenant on/off flag.
     */
    private const ENABLED_KEY = 'docudesk.conversion.backends.eml_enabled';


    /**
     * App identifier used for IAppConfig reads/writes.
     */
    private const APP_ID = 'docudesk';


    /**
     * Fully-qualified class name of OR's TextExtractionService —
     * deliberately string-literal so DocuDesk doesn't `use` the
     * symbol and stays loadable without OR on the classpath.
     */
    private const OR_TEXT_EXTRACTION_FQCN = '\\OCA\\OpenRegister\\Service\\TextExtractionService';


    /**
     * Constructor.
     *
     * @param EmlPdfAssemblyService $assemblyService The EML→PDF assembler.
     * @param IAppConfig            $appConfig       Tenant configuration provider.
     * @param LoggerInterface       $logger          Logger for diagnostics.
     */
    public function __construct(
        private readonly EmlPdfAssemblyService $assemblyService,
        private readonly IAppConfig $appConfig,
        private readonly LoggerInterface $logger,
    ) {

    }//end __construct()


    /**
     * Backend identifier surfaced in the 422 body's `conversionAttempts[].name`.
     *
     * @return string
     */
    public function name(): string
    {
        return 'eml';

    }//end name()


    /**
     * Whether the backend is usable: the tenant flag must be on AND
     * OR's structured-parse method must be present on the classpath.
     *
     * Note: when OR is present but `EmlPdfAssemblyService` cannot
     * resolve the TextExtractionService via DI at runtime, we fall
     * through inside `convert()`; `isAvailable()` only validates the
     * statically observable signals.
     *
     * @return bool
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-8
     */
    public function isAvailable(): bool
    {
        $enabled = $this->appConfig->getValueString(self::APP_ID, self::ENABLED_KEY, 'true');
        if ($enabled === 'false' || $enabled === '0') {
            return false;
        }

        $orClass = self::OR_TEXT_EXTRACTION_FQCN;
        if (class_exists($orClass) === false) {
            return false;
        }

        if (method_exists($orClass, 'parseEmlStructured') === false) {
            return false;
        }

        return true;

    }//end isAvailable()


    /**
     * Declare the input formats this backend claims for cascade routing.
     *
     * @param string $mimeType  Source MIME.
     * @param string $extension Source extension (lowercased, no dot).
     *
     * @return bool True for `message/rfc822` (.eml).
     */
    public function canHandle(string $mimeType, string $extension): bool
    {
        return $mimeType === 'message/rfc822' || $extension === 'eml';

    }//end canHandle()


    /**
     * Convert an EML source into an assembled PDF/A-3b file beside it.
     *
     * Errors surface as `ConversionFailedException` with a structured
     * `attempts[]` payload so the cascade aggregator can append it
     * cleanly into the 422 response body.
     *
     * @param File $source Source file node.
     *
     * @return File Newly written PDF file node.
     *
     * @throws ConversionFailedException When parse or assembly fails.
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-8
     */
    public function convert(File $source): File
    {
        $textExtractionService = $this->assemblyService->resolveTextExtractionService();
        if ($textExtractionService === null) {
            throw new ConversionFailedException(
                message: 'OpenRegister TextExtractionService is unavailable for EML parsing.',
                attempts: [
                    [
                        'name'      => $this->name(),
                        'available' => false,
                        'supports'  => true,
                        'reason'    => 'OR TextExtractionService not resolvable from container',
                    ],
                ]
            );
        }

        try {
            // Structured parse returns the EmlStructure value object.
            $structure = $textExtractionService->parseEmlStructured($source);
        } catch (Throwable $e) {
            $this->logger->error(
                '[EmlBackend] OR parseEmlStructured failed.',
                [
                    'source'    => $source->getPath(),
                    'exception' => get_class($e),
                    'message'   => $e->getMessage(),
                ]
            );

            throw new ConversionFailedException(
                message: 'EML parse failed: '.$e->getMessage(),
                attempts: [
                    [
                        'name'      => $this->name(),
                        'available' => true,
                        'supports'  => true,
                        'reason'    => 'OR parseEmlStructured threw: '.$e->getMessage(),
                    ],
                ],
                previous: $e
            );
        }//end try

        try {
            $pdfBinary = $this->assemblyService->assemble(
                structure: $structure,
                sourceFilename: $source->getName()
            );
        } catch (ConversionFailedException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->logger->error(
                '[EmlBackend] Assembly threw.',
                [
                    'source'    => $source->getPath(),
                    'exception' => get_class($e),
                    'message'   => $e->getMessage(),
                ]
            );
            throw new ConversionFailedException(
                message: 'EML assembly failed: '.$e->getMessage(),
                attempts: [
                    [
                        'name'      => $this->name(),
                        'available' => true,
                        'supports'  => true,
                        'reason'    => 'EmlPdfAssemblyService threw: '.$e->getMessage(),
                    ],
                ],
                previous: $e
            );
        }//end try

        // Write the PDF beside the source. If a same-named file
        // already exists, replace it — this is a fresh conversion.
        $parent     = $source->getParent();
        $outputName = $this->stripExtension(name: $source->getName()).'.pdf';
        if ($parent->nodeExists($outputName) === true) {
            $parent->get($outputName)->delete();
        }

        return $parent->newFile($outputName, $pdfBinary);

    }//end convert()


    /**
     * Return `$name` without its trailing `.ext` suffix.
     *
     * @param string $name Filename.
     *
     * @return string Name without extension.
     */
    private function stripExtension(string $name): string
    {
        $dotPos = strrpos($name, '.');
        if ($dotPos === false) {
            return $name;
        }

        return substr($name, 0, $dotPos);

    }//end stripExtension()
}//end class
