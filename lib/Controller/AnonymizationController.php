<?php
/**
 * Anonymization Controller
 *
 * Controller for the document anonymization pipeline.
 * Provides endpoints for uploading files, extracting/detecting entities,
 * and anonymizing documents.
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use Exception;
use OCA\DocuDesk\Exception\ConversionFailedException;
use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\FileListingService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Controller for anonymization pipeline endpoints
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
     * Constructor for AnonymizationController
     *
     * @param string               $appName              The application name
     * @param IRequest             $request              The request object
     * @param LoggerInterface      $logger               Logger for error reporting
     * @param AnonymizationService $anonymizationService Service for anonymization operations
     * @param FileListingService   $fileListingService   Service for file listing operations
     * @param IL10N                $l10n                 The localization service
     * @param IAppConfig           $appConfig            Tenant configuration provider (reads
     *                                                   docudesk.anonymisation.default_output_format)
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly LoggerInterface $logger,
        private readonly AnonymizationService $anonymizationService,
        private readonly FileListingService $fileListingService,
        private readonly IL10N $l10n,
        private readonly IAppConfig $appConfig
    ) {
        parent::__construct(appName: $appName, request: $request);

    }//end __construct()


    /**
     * Default-output-format tenant config key. The per-call
     * `outputFormat` request param overrides this when supplied.
     */
    private const DEFAULT_OUTPUT_FORMAT_KEY = 'docudesk.anonymisation.default_output_format';


    /**
     * Supported values for the `outputFormat` request param + tenant
     * config. Anything else from the request results in HTTP 400.
     */
    private const VALID_OUTPUT_FORMATS = ['pdf', 'preserve'];


    /**
     * List all processed files with entity counts and status
     *
     * Returns files from the user's DocuDesk folder with their
     * entity detection counts and anonymization status.
     *
     * @return JSONResponse JSON response with array of file data
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function files(): JSONResponse
    {
        try {
            $result = $this->fileListingService->listProcessedFiles();

            return new JSONResponse($result);
        } catch (Exception $e) {
            $statusCode = 500;
            if ($e->getCode() >= 400 && $e->getCode() < 600) {
                $statusCode = $e->getCode();
            }

            $this->logger->error(
                'Failed to list processed files: '.$e->getMessage(),
                ['exception' => $e]
            );
            return new JSONResponse(
                ['error' => $this->l10n->t('Failed to list processed files: %s', [$e->getMessage()])],
                $statusCode
            );
        }

    }//end files()


    /**
     * Upload a file to the user's DocuDesk folder
     *
     * Reads the uploaded file from the request and saves it
     * to the user's DocuDesk folder.
     *
     * @return JSONResponse JSON response with upload result
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function upload(): JSONResponse
    {
        try {
            $file = $this->request->getUploadedFile('file');

            if (empty($file) === true || isset($file['tmp_name']) === false) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('No file uploaded')],
                    400
                );
            }

            if ($file['error'] !== UPLOAD_ERR_OK) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('File upload failed with error code: %s', [$file['error']])],
                    400
                );
            }

            $fileName    = $file['name'];
            $fileContent = file_get_contents($file['tmp_name']);

            if ($fileContent === false) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('Failed to read uploaded file')],
                    500
                );
            }

            $result = $this->fileListingService->uploadFile($fileName, $fileContent);

            return new JSONResponse($result);
        } catch (Exception $e) {
            $statusCode = 500;
            if ($e->getCode() >= 400 && $e->getCode() < 600) {
                $statusCode = $e->getCode();
            }

            $this->logger->error(
                'Failed to upload file: '.$e->getMessage(),
                ['exception' => $e]
            );
            return new JSONResponse(
                ['error' => $this->l10n->t('Failed to upload file: %s', [$e->getMessage()])],
                $statusCode
            );
        }//end try

    }//end upload()


    /**
     * Extract text and detect entities in a file
     *
     * Runs text extraction and entity recognition on the specified file.
     *
     * @param int $fileId The Nextcloud file ID
     *
     * @return JSONResponse JSON response with extraction and detection results
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function extract(int $fileId): JSONResponse
    {
        try {
            $result = $this->anonymizationService->extractAndDetectEntities($fileId);

            return new JSONResponse($result);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to extract and detect entities: '.$e->getMessage(),
                ['exception' => $e]
            );
            return new JSONResponse(
                ['error' => $this->l10n->t('Failed to extract and detect entities: %s', [$e->getMessage()])],
                500
            );
        }

    }//end extract()


    /**
     * Anonymize entities in a document
     *
     * Replaces detected entities in the document with anonymized placeholders.
     * Supports optional excludeTypes and minConfidence filtering.
     *
     * @param int $fileId The Nextcloud file ID
     *
     * @return JSONResponse JSON response with anonymization result
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function anonymize(int $fileId): JSONResponse
    {
        try {
            $params   = $this->request->getParams();
            $entities = $params['entities'] ?? [];

            if (is_array($entities) === false || empty($entities) === true) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('No entities provided for anonymization')],
                    400
                );
            }

            // Wave 4a: optional `appendBasisSummary` flag. Default false; type-strict.
            $appendBasisSummary = false;
            if (array_key_exists('appendBasisSummary', $params) === true) {
                if (is_bool($params['appendBasisSummary']) === false) {
                    return new JSONResponse(
                        ['error' => $this->l10n->t('Invalid appendBasisSummary: must be a boolean')],
                        400
                    );
                }

                $appendBasisSummary = $params['appendBasisSummary'];
            }

            // Anonymise-output-as-pdf-by-default: optional `outputFormat`
            // selects PDF conversion vs native-format preservation.
            // Per-call value overrides the tenant default; missing
            // value falls back to the tenant default which itself
            // defaults to 'pdf'.
            $outputFormat = $this->resolveOutputFormat(params: $params);
            if ($outputFormat === null) {
                return new JSONResponse(
                    [
                        'error' => $this->l10n->t(
                            'Invalid outputFormat: must be one of %s',
                            [implode(', ', self::VALID_OUTPUT_FORMATS)]
                        ),
                    ],
                    400
                );
            }

            $entities = $this->filterByExcludeTypes(entities: $entities, params: $params);
            $entities = $this->filterByConfidence(entities: $entities, params: $params);

            // Placeholder-numbering scope (anonymisation-placeholder-id-scope):
            // forwarded to OpenRegister. 'document' (default) numbers entities
            // locally to this file; 'dossier' makes the number consistent across
            // the dossier folder's files. `dossierKey` is the stable folder id;
            // when omitted under scope=dossier, OpenRegister falls back to the
            // file's parent folder. Any value other than 'dossier' normalises to
            // per-document.
            $scopeParam = (string) ($params['scope'] ?? 'document');
            $scope      = 'document';
            if ($scopeParam === 'dossier') {
                $scope = 'dossier';
            }

            $dossierKeyParam = $params['dossierKey'] ?? null;
            $dossierKey      = null;
            if ($dossierKeyParam !== null && $dossierKeyParam !== '') {
                $dossierKey = (string) $dossierKeyParam;
            }

            try {
                $result = $this->anonymizationService->anonymizeDocument(
                    $fileId,
                    $entities,
                    $appendBasisSummary,
                    $outputFormat,
                    $scope,
                    $dossierKey
                );
            } catch (ConversionFailedException $e) {
                $this->logger->warning(
                    'PDF conversion cascade exhausted; returning 422.',
                    ['attempts' => $e->getAttempts()]
                );
                return new JSONResponse(
                    [
                        'error'              => $this->l10n->t(
                            'Conversion to PDF failed; anonymisation rolled back.'
                        ),
                        'conversionAttempts' => $e->getAttempts(),
                        'outputFormat'       => $outputFormat,
                        'fallback'           => $this->l10n->t(
                            'Set outputFormat to "preserve" to bypass conversion if you must keep the native format.'
                        ),
                    ],
                    422
                );
            }//end try

            return new JSONResponse($result);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to anonymize document: '.$e->getMessage(),
                ['exception' => $e]
            );
            return new JSONResponse(
                ['error' => $this->l10n->t('Failed to anonymize document: %s', [$e->getMessage()])],
                500
            );
        }//end try

    }//end anonymize()


    /**
     * Resolve the effective `outputFormat` for this request.
     *
     * Order: per-call value (when supplied and valid) → tenant default
     * from IAppConfig → hard-coded `"pdf"` fallback.
     *
     * Returns `null` when the per-call value is supplied but invalid;
     * the caller maps that to HTTP 400.
     *
     * @param array<string,mixed> $params Request params.
     *
     * @return string|null Resolved outputFormat ('pdf'|'preserve'), or
     *                     null when an invalid value was supplied.
     */
    private function resolveOutputFormat(array $params): ?string
    {
        if (array_key_exists('outputFormat', $params) === true) {
            $value = $params['outputFormat'];
            if (is_string($value) === false
                || in_array($value, self::VALID_OUTPUT_FORMATS, true) === false
            ) {
                return null;
            }

            return $value;
        }

        $tenantDefault = $this->appConfig->getValueString(
            'docudesk',
            self::DEFAULT_OUTPUT_FORMAT_KEY,
            'pdf'
        );

        if (in_array($tenantDefault, self::VALID_OUTPUT_FORMATS, true) === false) {
            // Malformed tenant setting falls back to spec default
            // rather than rejecting the call.
            return 'pdf';
        }

        return $tenantDefault;

    }//end resolveOutputFormat()


    /**
     * Filter entities by excluded types
     *
     * @param array<int, array<string, mixed>> $entities The entities
     * @param array<string, mixed>             $params   Request parameters
     *
     * @return array<int, array<string, mixed>> Filtered entities
     */
    private function filterByExcludeTypes(array $entities, array $params): array
    {
        $excludeTypes = $params['excludeTypes'] ?? [];
        if (is_array($excludeTypes) === false || empty($excludeTypes) === true) {
            return $entities;
        }

        return array_values(
            array_filter(
                $entities,
                static function (array $entity) use ($excludeTypes): bool {
                    $type = $entity['type'] ?? $entity['entityType'] ?? '';
                    return in_array($type, $excludeTypes, true) === false;
                }
            )
        );

    }//end filterByExcludeTypes()


    /**
     * Filter entities by minimum confidence threshold
     *
     * @param array<int, array<string, mixed>> $entities The entities
     * @param array<string, mixed>             $params   Request parameters
     *
     * @return array<int, array<string, mixed>> Filtered entities
     */
    private function filterByConfidence(array $entities, array $params): array
    {
        if (isset($params['minConfidence']) === false) {
            return $entities;
        }

        $minConfidence = (float) $params['minConfidence'];

        return array_values(
            array_filter(
                $entities,
                static function (array $entity) use ($minConfidence): bool {
                    return (float) ($entity['confidence'] ?? 0.0) >= $minConfidence;
                }
            )
        );

    }//end filterByConfidence()


}//end class
