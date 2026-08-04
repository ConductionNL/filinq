<?php

/**
 * EML Preview Controller
 *
 * Streams a PDF/A-3b preview of the ORIGINAL (un-redacted) content of a
 * `message/rfc822` file, so the in-app file viewer can render an .eml the same
 * way it renders a PDF. The rendering is delegated to EmlPreviewService, which
 * reuses the anonymise-assembly pipeline with an empty entity set.
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use OCA\DocuDesk\Service\EmlPreviewService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Serves original-EML previews as PDF.
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 */
class EmlPreviewController extends Controller
{


    /**
     * Constructor.
     *
     * @param string            $appName           App identifier.
     * @param IRequest          $request           The current request.
     * @param EmlPreviewService $emlPreviewService Renders the original EML to PDF.
     * @param LoggerInterface   $logger            Logger for diagnostics.
     * @param IL10N             $l10n              Localisation. The frontend renders this
     *                                             controller's `error` verbatim, so an
     *                                             untranslated message reaches the user in
     *                                             English regardless of their language.
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly EmlPreviewService $emlPreviewService,
        private readonly LoggerInterface $logger,
        private readonly IL10N $l10n,
    ) {
        parent::__construct(appName: $appName, request: $request);

    }//end __construct()


    /**
     * Stream a PDF/A-3b preview of the original (un-redacted) EML.
     *
     * @param int $fileId Nextcloud file id of the source .eml.
     *
     * @return DataDownloadResponse|JSONResponse PDF bytes, or a JSON error (422).
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function preview(int $fileId): DataDownloadResponse | JSONResponse
    {
        try {
            $pdf = $this->emlPreviewService->renderOriginalPreview(fileId: $fileId);
        } catch (Throwable $e) {
            $this->logger->warning(
                message: 'EML preview failed for file '.$fileId.': '.$e->getMessage(),
                context: ['fileId' => $fileId, 'exception' => $e]
            );
            // Translated, and WITHOUT the exception message. The frontend renders
            // this `error` verbatim, so appending $e->getMessage() both showed the
            // user English regardless of their language and pushed parser
            // internals — file paths, MIME details, fragments of the message — to
            // the client. The detail is already in the warning above, which is
            // where it belongs (ADR-005).
            return new JSONResponse(
                data: ['error' => $this->l10n->t('Could not render EML preview.')],
                statusCode: 422
            );
        }

        return new DataDownloadResponse(
            data: $pdf,
            filename: 'eml-preview.pdf',
            contentType: 'application/pdf'
        );

    }//end preview()


}//end class
