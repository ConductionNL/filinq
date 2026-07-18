<?php

/**
 * EML Preview Service
 *
 * Renders the ORIGINAL (un-redacted) content of a `message/rfc822` file to a
 * previewable PDF/A-3b so the in-app file viewer can show an .eml the same way
 * it shows a PDF or Word document. The render is produced server-side — any
 * client (the DocuDesk viewer, a direct link, another app) consumes one
 * endpoint rather than re-implementing EML parsing in the browser.
 *
 * Reuses the anonymise pipeline with an EMPTY entity set: OpenRegister parses
 * the message and, with nothing to replace, returns its structure unchanged;
 * `EmlPdfAssemblyService` then renders that structure — headers, body and
 * renderable attachments — exactly as it renders the redacted result. There is
 * therefore no separate "original" render path to maintain.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use OCP\App\IAppManager;
use OCP\Files\File;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Produces a PDF/A-3b preview of an original EML message.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 */
class EmlPreviewService
{


    /**
     * OpenRegister FileService FQCN — exposes anonymizeEmlStructured().
     */
    private const OR_FILE_SERVICE = 'OCA\\OpenRegister\\Service\\FileService';


    /**
     * Constructor.
     *
     * @param IAppManager           $appManager  App manager (OpenRegister installed check).
     * @param ContainerInterface    $container   DI container for OR service resolution.
     * @param EmlPdfAssemblyService $emlAssembly Assembles the parsed structure into a PDF.
     * @param LoggerInterface       $logger      Logger for diagnostics.
     */
    public function __construct(
        private readonly IAppManager $appManager,
        private readonly ContainerInterface $container,
        private readonly EmlPdfAssemblyService $emlAssembly,
        private readonly LoggerInterface $logger,
    ) {

    }//end __construct()


    /**
     * Render the original EML identified by $fileId to a preview PDF/A-3b.
     *
     * An EMPTY entity set is passed so nothing is redacted: the assembled PDF
     * is a faithful preview of the source message (original headers, body and
     * renderable attachments). No file is written — the bytes are streamed by
     * the caller. `EmlPdfAssemblyService::assemble()` throws
     * `ConversionFailedException` on an unrecoverable render failure; that is
     * left to propagate so the controller can surface a 422.
     *
     * @param int $fileId Nextcloud file id of the source .eml.
     *
     * @return string PDF/A-3b bytes of the original-message preview.
     *
     * @throws RuntimeException When OpenRegister is unavailable, the file
     *                          cannot be resolved, or the anonymise-EML API
     *                          is absent.
     */
    public function renderOriginalPreview(int $fileId): string
    {
        $fileService = $this->resolveFileService();

        $node = $fileService->getFileById($fileId);
        if (($node instanceof File) === false) {
            throw new RuntimeException('EML preview: file '.$fileId.' is not a readable file node.');
        }

        if (method_exists($fileService, 'anonymizeEmlStructured') === false) {
            throw new RuntimeException('EML preview requires the OpenRegister anonymise-EML API.');
        }

        // Empty entity set → no redaction: the structure carries the original
        // headers, body and attachment bytes, so the assembled PDF is a
        // faithful preview of the source message.
        $structure = $fileService->anonymizeEmlStructured($node, [], 'document', null);

        $this->logger->debug('EML preview assembled', ['fileId' => $fileId]);

        return $this->emlAssembly->assemble(result: $structure, sourceFilename: (string) $node->getName());

    }//end renderOriginalPreview()


    /**
     * Resolve OpenRegister's FileService or fail with a clear message.
     *
     * @return mixed The OpenRegister FileService.
     *
     * @throws RuntimeException When OpenRegister is not installed or the
     *                          service cannot be resolved.
     */
    private function resolveFileService(): mixed
    {
        if (in_array('openregister', $this->appManager->getInstalledApps(), true) === false) {
            throw new RuntimeException('EML preview requires the OpenRegister app.');
        }

        try {
            return $this->container->get(self::OR_FILE_SERVICE);
        } catch (Throwable $e) {
            throw new RuntimeException(
                'EML preview could not resolve OpenRegister FileService: '.$e->getMessage(),
                0,
                $e
            );
        }

    }//end resolveFileService()


}//end class
