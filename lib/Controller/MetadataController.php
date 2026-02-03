<?php
/**
 * Metadata Controller
 *
 * Controller for document metadata operations.
 * Provides endpoints for extracting, enhancing, and managing document metadata.
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version  GIT: <git_id>
 * @link     https://www.DocuDesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use Exception;
use OCA\DocuDesk\Service\MetadataService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Controller for metadata operations
 *
 * This controller provides endpoints for extracting, enhancing,
 * and managing document metadata stored in OpenRegister.
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class MetadataController extends Controller
{
    /**
     * Logger instance for error reporting
     *
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;

    /**
     * Metadata service
     *
     * @var MetadataService
     */
    private readonly MetadataService $metadataService;

    /**
     * Constructor for MetadataController
     *
     * @param string            $appName         The application name
     * @param IRequest         $request         The request object
     * @param LoggerInterface   $logger          Logger for error reporting
     * @param MetadataService  $metadataService Service for metadata operations
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        LoggerInterface $logger,
        MetadataService $metadataService
    ) {
        parent::__construct($appName, $request);
        $this->logger          = $logger;
        $this->metadataService = $metadataService;

    }//end __construct()

    /**
     * Extract metadata from a document
     *
     * @return JSONResponse JSON response with extracted metadata
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function extract(): JSONResponse
    {
        try {
            $data = $this->request->getParams();

            // Validate required fields.
            if (isset($data['documentId']) === false || empty($data['documentId']) === true) {
                return new JSONResponse(
                    ['error' => 'documentId is required'],
                    400
                );
            }

            // Extract metadata.
            $metadata = $this->metadataService->extractMetadata($data['documentId']);

            return new JSONResponse($metadata);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to extract metadata: '.$e->getMessage(),
                [
                    'exception' => $e,
                ]
            );
            return new JSONResponse(
                ['error' => 'Failed to extract metadata: '.$e->getMessage()],
                500
            );
        }

    }//end extract()

    /**
     * Enhance metadata with additional information
     *
     * @return JSONResponse JSON response with enhanced metadata
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function enhance(): JSONResponse
    {
        try {
            $data = $this->request->getParams();

            // Validate required fields.
            if (isset($data['documentId']) === false || empty($data['documentId']) === true) {
                return new JSONResponse(
                    ['error' => 'documentId is required'],
                    400
                );
            }

            // Get existing metadata if provided, otherwise extract it.
            $metadata = $data['metadata'] ?? [];
            if (empty($metadata) === true) {
                $metadata = $this->metadataService->extractMetadata($data['documentId']);
            }

            // Enhance metadata.
            $enhancedMetadata = $this->metadataService->enhanceMetadata($data['documentId'], $metadata);

            return new JSONResponse($enhancedMetadata);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to enhance metadata: '.$e->getMessage(),
                [
                    'exception' => $e,
                ]
            );
            return new JSONResponse(
                ['error' => 'Failed to enhance metadata: '.$e->getMessage()],
                500
            );
        }

    }//end enhance()

    /**
     * Get metadata for a document
     *
     * @param string $documentId The document ID
     *
     * @return JSONResponse JSON response with document metadata
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getMetadata(string $documentId): JSONResponse
    {
        try {
            // Extract metadata from document.
            $metadata = $this->metadataService->extractMetadata($documentId);

            return new JSONResponse($metadata);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to get metadata: '.$e->getMessage(),
                [
                    'documentId' => $documentId,
                    'exception'  => $e,
                ]
            );
            return new JSONResponse(
                ['error' => 'Failed to get metadata: '.$e->getMessage()],
                500
            );
        }

    }//end getMetadata()

    /**
     * Update metadata for a document
     *
     * @param string $documentId The document ID
     *
     * @return JSONResponse JSON response with updated document
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function updateMetadata(string $documentId): JSONResponse
    {
        try {
            $data = $this->request->getParams();

            // Update metadata.
            $document = $this->metadataService->updateMetadata($documentId, $data);

            return new JSONResponse($document);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to update metadata: '.$e->getMessage(),
                [
                    'documentId' => $documentId,
                    'exception'  => $e,
                ]
            );
            return new JSONResponse(
                ['error' => 'Failed to update metadata: '.$e->getMessage()],
                500
            );
        }

    }//end updateMetadata()

}//end class


