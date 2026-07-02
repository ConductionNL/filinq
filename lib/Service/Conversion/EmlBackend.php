<?php

/**
 * EML Conversion Backend
 *
 * Cascade slot for EML (`message/rfc822`) inputs. EML is anonymised by
 * OpenRegister (which redacts headers, body, attachment bytes and inline
 * images) and assembled into a PDF/A-3b by DocuDesk's
 * `EmlPdfAssemblyService` — DocuDesk performs NO redaction itself and embeds
 * NO original or redacted bytes as PDF/A-3 file attachments.
 *
 * Wiring note (see DEFERRED_QUESTIONS in the change): the PRIMARY EML path is
 * the dedicated branch in `AnonymizationService::anonymizeDocument()`, which
 * calls OR's `anonymizeEmlStructured($node, $entities, ...)` with the
 * operator-selected entities and assembles the result. That is necessary
 * because the `ConversionBackendInterface::convert(File)` signature carries
 * no entity list, and entities are essential to redaction.
 *
 * This backend therefore exists for cascade completeness and observability:
 * `isAvailable()` reflects whether both dependencies are present, and
 * `convert()` provides a best-effort assembly path (calling OR's
 * anonymise-EML API with an empty entity set — OR still returns the
 * structured, body/header-redacted shape). It is NOT the operator-facing
 * redaction path; that is the AnonymizationService branch.
 *
 * See openspec/changes/eml-pdf-assembly/design.md (D-step-3, D9).
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Conversion;

use OCA\DocuDesk\Exception\ConversionFailedException;
use OCA\DocuDesk\Service\EmlPdfAssemblyService;
use OCP\App\IAppManager;
use OCP\Files\File;
use OCP\IAppConfig;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Available when OR's anonymise-EML API AND DocuDesk's EmlPdfAssemblyService
 * are both present. `convert()` calls OR's anonymise-EML API and delegates the
 * assembly to EmlPdfAssemblyService; OR exceptions surface as
 * ConversionFailedException with NO raw-parse fallback.
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
     * App config key for the tenant observability flag.
     */
    private const ENABLED_KEY = 'docudesk.conversion.backends.eml_enabled';


    /**
     * App identifier used for IAppConfig reads/writes.
     */
    private const APP_ID = 'docudesk';


    /**
     * OpenRegister FileService FQCN — exposes anonymizeEmlStructured().
     */
    private const OR_FILE_SERVICE = 'OCA\\OpenRegister\\Service\\FileService';


    /**
     * Constructor.
     *
     * @param IAppConfig            $appConfig  Tenant configuration provider.
     * @param IAppManager           $appManager App manager (OpenRegister installed check).
     * @param ContainerInterface    $container  DI container for OR service resolution.
     * @param EmlPdfAssemblyService $assembly   Redacted-component PDF assembler.
     * @param LoggerInterface       $logger     Logger for diagnostics.
     */
    public function __construct(
        private readonly IAppConfig $appConfig,
        private readonly IAppManager $appManager,
        private readonly ContainerInterface $container,
        private readonly EmlPdfAssemblyService $assembly,
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
     * Available iff the tenant flag is set AND OpenRegister exposes its
     * anonymise-EML API (`anonymizeEmlStructured`) AND the assembly service
     * is present. The tenant flag is read for observability and lets tenants
     * force fall-through.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        $flag = $this->appConfig->getValueString(self::APP_ID, self::ENABLED_KEY, 'true');
        if ($flag === 'false') {
            return false;
        }

        if (in_array('openregister', $this->appManager->getInstalledApps(), true) === false) {
            return false;
        }

        try {
            $fileService = $this->container->get(self::OR_FILE_SERVICE);
        } catch (Throwable $e) {
            return false;
        }

        return method_exists($fileService, 'anonymizeEmlStructured');

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
     * Convert an EML input: call OR's anonymise-EML API and assemble the
     * redacted result into a PDF/A-3b written beside the source.
     *
     * NO raw-parse fallback: if OR's API throws, this throws
     * ConversionFailedException so the cascade falls through (422 for EML) —
     * never emitting un-redacted content (design D9). Entities are not
     * threaded through the cascade signature, so this calls OR with an empty
     * entity set; the operator-facing redaction path is the
     * AnonymizationService branch.
     *
     * @param File $source Source EML file node.
     *
     * @return File Newly written PDF file node.
     *
     * @throws ConversionFailedException On OR API failure or assembly failure.
     */
    public function convert(File $source): File
    {
        try {
            $fileService = $this->container->get(self::OR_FILE_SERVICE);
        } catch (Throwable $e) {
            throw new ConversionFailedException(
                message: 'EML backend could not resolve OpenRegister FileService: '.$e->getMessage(),
                attempts: [
                    [
                        'name'      => $this->name(),
                        'available' => false,
                        'supports'  => true,
                        'reason'    => 'OpenRegister FileService unavailable',
                    ],
                ],
                previous: $e
            );
        }

        try {
            $structure = $fileService->anonymizeEmlStructured($source, [], 'document', null);
        } catch (Throwable $e) {
            // NO raw-parse fallback — re-throw as a typed failure.
            $this->logger->warning(
                '[EmlBackend] OR anonymizeEmlStructured failed; no raw-parse fallback',
                ['source' => $source->getPath(), 'exception' => get_class($e), 'message' => $e->getMessage()]
            );
            throw new ConversionFailedException(
                message: 'OpenRegister anonymise-EML API failed: '.$e->getMessage(),
                attempts: [
                    [
                        'name'      => $this->name(),
                        'available' => true,
                        'supports'  => true,
                        'reason'    => 'anonymizeEmlStructured threw: '.$e->getMessage(),
                    ],
                ],
                previous: $e
            );
        }//end try

        if (is_object($structure) === false) {
            throw new ConversionFailedException(
                message: 'OpenRegister anonymise-EML API returned no structure.',
                attempts: [
                    [
                        'name'      => $this->name(),
                        'available' => true,
                        'supports'  => true,
                        'reason'    => 'anonymizeEmlStructured returned non-object',
                    ],
                ]
            );
        }

        $pdfBytes = $this->assembly->assemble(result: $structure, sourceFilename: $source->getName());

        $parent     = $source->getParent();
        $outputName = $this->stripExtension(name: $source->getName()).'_anonymized.pdf';
        if ($parent->nodeExists($outputName) === true) {
            $parent->get($outputName)->delete();
        }

        return $parent->newFile($outputName, $pdfBytes);

    }//end convert()


    /**
     * Return $name without its trailing `.ext`.
     *
     * @param string $name File name with extension.
     *
     * @return string
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
