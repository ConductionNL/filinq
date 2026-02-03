<?php
/**
 * Document Controller
 *
 * Controller for managing documents stored in OpenRegister.
 * Provides CRUD operations for documents via OpenRegister API.
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
use OCA\DocuDesk\Service\OpenRegisterService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Controller for document CRUD operations via OpenRegister
 *
 * This controller provides endpoints for creating, reading, updating,
 * and deleting documents stored in OpenRegister.
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class DocumentController extends Controller
{
    /**
     * Logger instance for error reporting
     *
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;

    /**
     * OpenRegister service for document operations
     *
     * @var OpenRegisterService
     */
    private readonly OpenRegisterService $openRegisterService;

    /**
     * Constructor for DocumentController
     *
     * @param string              $appName            The application name
     * @param IRequest            $request            The request object
     * @param LoggerInterface    $logger             Logger for error reporting
     * @param OpenRegisterService $openRegisterService Service for OpenRegister operations
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        LoggerInterface $logger,
        OpenRegisterService $openRegisterService
    ) {
        parent::__construct($appName, $request);
        $this->logger             = $logger;
        $this->openRegisterService = $openRegisterService;

    }//end __construct()

    /**
     * Create a new document in OpenRegister
     *
     * @return JSONResponse JSON response with created document
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function create(): JSONResponse
    {
        try {
            $data = $this->request->getParams();

            // Validate required fields.
            if (isset($data['filePath']) === false || empty($data['filePath']) === true) {
                return new JSONResponse(
                    ['error' => 'filePath is required'],
                    400
                );
            }

            // Create document in OpenRegister.
            $document = $this->openRegisterService->createDocument($data);

            return new JSONResponse($document, 201);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to create document: '.$e->getMessage(),
                [
                    'exception' => $e,
                ]
            );
            return new JSONResponse(
                ['error' => 'Failed to create document: '.$e->getMessage()],
                500
            );
        }

    }//end create()

    /**
     * Get a document by ID from OpenRegister
     *
     * @param string $id The document ID
     *
     * @return JSONResponse JSON response with document data
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function show(string $id): JSONResponse
    {
        try {
            $document = $this->openRegisterService->getDocument($id);

            if ($document === null) {
                return new JSONResponse(
                    ['error' => 'Document not found'],
                    404
                );
            }

            return new JSONResponse($document);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to get document: '.$e->getMessage(),
                [
                    'documentId' => $id,
                    'exception' => $e,
                ]
            );
            return new JSONResponse(
                ['error' => 'Failed to get document: '.$e->getMessage()],
                500
            );
        }

    }//end show()

    /**
     * Update a document in OpenRegister
     *
     * @param string $id The document ID
     *
     * @return JSONResponse JSON response with updated document
     *
     * @NoAdminRequired
     */
    public function update(string $id): JSONResponse
    {
        try {
            $data = $this->request->getParams();

            // Update document in OpenRegister.
            $document = $this->openRegisterService->updateDocument($id, $data);

            return new JSONResponse($document);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to update document: '.$e->getMessage(),
                [
                    'documentId' => $id,
                    'exception' => $e,
                ]
            );
            return new JSONResponse(
                ['error' => 'Failed to update document: '.$e->getMessage()],
                500
            );
        }

    }//end update()

    /**
     * Delete a document from OpenRegister
     *
     * @param string $id The document ID
     *
     * @return JSONResponse JSON response indicating success or failure
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function destroy(string $id): JSONResponse
    {
        try {
            $result = $this->openRegisterService->deleteDocument($id);

            if ($result === true) {
                return new JSONResponse(['success' => true]);
            } else {
                return new JSONResponse(
                    ['error' => 'Failed to delete document'],
                    500
                );
            }
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to delete document: '.$e->getMessage(),
                [
                    'documentId' => $id,
                    'exception' => $e,
                ]
            );
            return new JSONResponse(
                ['error' => 'Failed to delete document: '.$e->getMessage()],
                500
            );
        }

    }//end destroy()

    /**
     * List documents with optional filters
     *
     * @return JSONResponse JSON response with list of documents
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): JSONResponse
    {
        try {
            $filters = $this->request->getParams();
            $limit   = (int) ($filters['limit'] ?? 50);
            $offset  = (int) ($filters['offset'] ?? 0);

            // Remove pagination parameters from filters.
            unset($filters['limit'], $filters['offset']);

            $documents = $this->openRegisterService->findDocuments($filters, $limit, $offset);

            return new JSONResponse($documents);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to list documents: '.$e->getMessage(),
                [
                    'exception' => $e,
                ]
            );
            return new JSONResponse(
                ['error' => 'Failed to list documents: '.$e->getMessage()],
                500
            );
        }

    }//end index()

}//end class


