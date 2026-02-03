<?php
/**
 * Anonymization Controller
 *
 * Controller for document anonymization operations.
 * Provides endpoints for anonymizing documents and managing anonymization rules.
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
use OCA\DocuDesk\Service\AnonymizationService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Controller for anonymization operations
 *
 * This controller provides endpoints for anonymizing documents,
 * previewing anonymization, and managing anonymization rules.
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class AnonymizationController extends Controller
{
    /**
     * Logger instance for error reporting
     *
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;

    /**
     * Anonymization service
     *
     * @var AnonymizationService
     */
    private readonly AnonymizationService $anonymizationService;

    /**
     * Constructor for AnonymizationController
     *
     * @param string                $appName              The application name
     * @param IRequest              $request              The request object
     * @param LoggerInterface       $logger               Logger for error reporting
     * @param AnonymizationService  $anonymizationService Service for anonymization operations
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        LoggerInterface $logger,
        AnonymizationService $anonymizationService
    ) {
        parent::__construct($appName, $request);
        $this->logger              = $logger;
        $this->anonymizationService = $anonymizationService;

    }//end __construct()

    /**
     * Anonymize a document
     *
     * @return JSONResponse JSON response with anonymization results
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function anonymize(): JSONResponse
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

            // Anonymize document.
            $result = $this->anonymizationService->anonymizeDocument($data['documentId']);

            return new JSONResponse($result);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to anonymize document: '.$e->getMessage(),
                [
                    'exception' => $e,
                ]
            );
            return new JSONResponse(
                ['error' => 'Failed to anonymize document: '.$e->getMessage()],
                500
            );
        }

    }//end anonymize()

    /**
     * Preview anonymization without creating an anonymized file
     *
     * @return JSONResponse JSON response with preview data
     *
     * @NoAdminRequired
     */
    public function preview(): JSONResponse
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

            // Preview anonymization.
            $result = $this->anonymizationService->previewAnonymization($data['documentId']);

            return new JSONResponse($result);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to preview anonymization: '.$e->getMessage(),
                [
                    'exception' => $e,
                ]
            );
            return new JSONResponse(
                ['error' => 'Failed to preview anonymization: '.$e->getMessage()],
                500
            );
        }

    }//end preview()

    /**
     * Get anonymization rules
     *
     * @return JSONResponse JSON response with anonymization rules
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getRules(): JSONResponse
    {
        try {
            $rules = $this->anonymizationService->getAnonymizationRules();

            return new JSONResponse($rules);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to get anonymization rules: '.$e->getMessage(),
                [
                    'exception' => $e,
                ]
            );
            return new JSONResponse(
                ['error' => 'Failed to get anonymization rules: '.$e->getMessage()],
                500
            );
        }

    }//end getRules()

    /**
     * Update anonymization rules
     *
     * @return JSONResponse JSON response indicating success or failure
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function updateRules(): JSONResponse
    {
        try {
            $rules = $this->request->getParams();

            // Update anonymization rules.
            $this->anonymizationService->updateAnonymizationRules($rules);

            return new JSONResponse(['success' => true]);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to update anonymization rules: '.$e->getMessage(),
                [
                    'exception' => $e,
                ]
            );
            return new JSONResponse(
                ['error' => 'Failed to update anonymization rules: '.$e->getMessage()],
                500
            );
        }

    }//end updateRules()

}//end class


