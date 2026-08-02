<?php
/**
 * Custom Dictionary Controller
 *
 * Thin, organisation-gated CRUD + import controller for the
 * `custom-dictionary-recognition` change. All authorisation (organisation
 * membership, fail-closed) lives in {@see CustomDictionaryService}; this
 * controller only translates HTTP <-> service calls and maps exceptions to
 * status codes (403 for the organisation gate, 404 for a missing record,
 * 400 for bad input, 503 when OpenRegister is unavailable).
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use Exception;
use InvalidArgumentException;
use OCA\DocuDesk\Service\CustomDictionaryService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * `api/custom-dictionaries` CRUD + import endpoints.
 *
 * Exceeds PHPMD's class-complexity threshold (69 vs 50): the figure is the sum
 * of ~12 thin CRUD/import/export endpoints, each individually simple. Splitting
 * the controller would fragment one REST resource across several classes
 * without reducing any single method's complexity.
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */
class CustomDictionaryController extends Controller
{
    /**
     * Constructor.
     *
     * @param string                  $appName     App name.
     * @param IRequest                $request     Request abstraction.
     * @param LoggerInterface         $logger      Logger.
     * @param CustomDictionaryService $service     Organisation-gated CRUD + import service.
     * @param IL10N                   $l10n        Localisation.
     * @param IUserSession            $userSession User session for authentication.
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly LoggerInterface $logger,
        private readonly CustomDictionaryService $service,
        private readonly IL10N $l10n,
        private readonly IUserSession $userSession
    ) {
        parent::__construct(appName: $appName, request: $request);

    }//end __construct()

    /**
     * List dictionaries visible to the caller's accessible organisations.
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
     */
    public function index(): JSONResponse
    {
        $unauthenticated = $this->requireAuthenticated();
        if ($unauthenticated !== null) {
            return $unauthenticated;
        }

        if ($this->service->isAvailable() === false) {
            return $this->unavailable();
        }

        try {
            return new JSONResponse($this->service->listDictionaries());
        } catch (Exception $e) {
            return $this->error(message: 'Failed to list custom dictionaries: ', exception: $e);
        }

    }//end index()

    /**
     * Show a single dictionary.
     *
     * @param string $id Dictionary UUID.
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
     */
    public function show(string $id): JSONResponse
    {
        $unauthenticated = $this->requireAuthenticated();
        if ($unauthenticated !== null) {
            return $unauthenticated;
        }

        if ($this->service->isAvailable() === false) {
            return $this->unavailable();
        }

        try {
            return new JSONResponse($this->service->getDictionary(uuid: $id));
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => $this->l10n->t('Custom dictionary not found')], Http::STATUS_NOT_FOUND);
        } catch (RuntimeException $e) {
            return $this->forbidden();
        } catch (Exception $e) {
            return $this->error(message: 'Failed to load custom dictionary: ', exception: $e);
        }

    }//end show()

    /**
     * Create a dictionary.
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
     */
    public function create(): JSONResponse
    {
        $unauthenticated = $this->requireAuthenticated();
        if ($unauthenticated !== null) {
            return $unauthenticated;
        }

        if ($this->service->isAvailable() === false) {
            return $this->unavailable();
        }

        try {
            $data = $this->request->getParams();
            return new JSONResponse($this->service->createDictionary(data: $data), 201);
        } catch (InvalidArgumentException $e) {
            return new JSONResponse(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            return $this->error(message: 'Failed to create custom dictionary: ', exception: $e);
        }

    }//end create()

    /**
     * Update a dictionary.
     *
     * @param string $id Dictionary UUID.
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
     */
    public function update(string $id): JSONResponse
    {
        $unauthenticated = $this->requireAuthenticated();
        if ($unauthenticated !== null) {
            return $unauthenticated;
        }

        if ($this->service->isAvailable() === false) {
            return $this->unavailable();
        }

        try {
            $data = $this->request->getParams();
            return new JSONResponse($this->service->updateDictionary(uuid: $id, data: $data));
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => $this->l10n->t('Custom dictionary not found')], Http::STATUS_NOT_FOUND);
        } catch (RuntimeException $e) {
            return $this->forbidden();
        } catch (InvalidArgumentException $e) {
            return new JSONResponse(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            return $this->error(message: 'Failed to update custom dictionary: ', exception: $e);
        }

    }//end update()

    /**
     * Delete a dictionary (cascade-deletes its terms).
     *
     * @param string $id Dictionary UUID.
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
     */
    public function destroy(string $id): JSONResponse
    {
        $unauthenticated = $this->requireAuthenticated();
        if ($unauthenticated !== null) {
            return $unauthenticated;
        }

        if ($this->service->isAvailable() === false) {
            return $this->unavailable();
        }

        try {
            $this->service->deleteDictionary(uuid: $id);
            return new JSONResponse(['deleted' => $id]);
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => $this->l10n->t('Custom dictionary not found')], Http::STATUS_NOT_FOUND);
        } catch (RuntimeException $e) {
            return $this->forbidden();
        } catch (Exception $e) {
            return $this->error(message: 'Failed to delete custom dictionary: ', exception: $e);
        }

    }//end destroy()

    /**
     * List a dictionary's terms.
     *
     * @param string $id Dictionary UUID.
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
     */
    public function indexTerms(string $id): JSONResponse
    {
        $unauthenticated = $this->requireAuthenticated();
        if ($unauthenticated !== null) {
            return $unauthenticated;
        }

        if ($this->service->isAvailable() === false) {
            return $this->unavailable();
        }

        try {
            return new JSONResponse($this->service->listTerms(dictionaryUuid: $id));
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => $this->l10n->t('Custom dictionary not found')], Http::STATUS_NOT_FOUND);
        } catch (RuntimeException $e) {
            return $this->forbidden();
        } catch (Exception $e) {
            return $this->error(message: 'Failed to list terms: ', exception: $e);
        }

    }//end indexTerms()

    /**
     * Add a single term to a dictionary.
     *
     * @param string $id Dictionary UUID.
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
     */
    public function createTerm(string $id): JSONResponse
    {
        $unauthenticated = $this->requireAuthenticated();
        if ($unauthenticated !== null) {
            return $unauthenticated;
        }

        if ($this->service->isAvailable() === false) {
            return $this->unavailable();
        }

        try {
            $data = $this->request->getParams();
            return new JSONResponse($this->service->createTerm(dictionaryUuid: $id, data: $data), 201);
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => $this->l10n->t('Custom dictionary not found')], Http::STATUS_NOT_FOUND);
        } catch (RuntimeException $e) {
            return $this->forbidden();
        } catch (InvalidArgumentException $e) {
            return new JSONResponse(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            return $this->error(message: 'Failed to create term: ', exception: $e);
        }

    }//end createTerm()

    /**
     * Delete a single term.
     *
     * @param string $id     Dictionary UUID.
     * @param string $termId Term UUID.
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
     */
    public function deleteTerm(string $id, string $termId): JSONResponse
    {
        $unauthenticated = $this->requireAuthenticated();
        if ($unauthenticated !== null) {
            return $unauthenticated;
        }

        if ($this->service->isAvailable() === false) {
            return $this->unavailable();
        }

        try {
            $this->service->deleteTerm(dictionaryUuid: $id, termUuid: $termId);
            return new JSONResponse(['deleted' => $termId]);
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => $this->l10n->t('Term not found')], Http::STATUS_NOT_FOUND);
        } catch (RuntimeException $e) {
            return $this->forbidden();
        } catch (Exception $e) {
            return $this->error(message: 'Failed to delete term: ', exception: $e);
        }

    }//end deleteTerm()

    /**
     * Import terms from a CSV upload (`file` multipart field) or a
     * newline-separated plain-text body (`content` param, optional
     * `format=csv` to parse it as CSV instead).
     *
     * @param string $id Dictionary UUID.
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
     */
    public function import(string $id): JSONResponse
    {
        $unauthenticated = $this->requireAuthenticated();
        if ($unauthenticated !== null) {
            return $unauthenticated;
        }

        if ($this->service->isAvailable() === false) {
            return $this->unavailable();
        }

        try {
            [$content, $isCsv] = $this->readImportPayload();
            if ($content === null) {
                return new JSONResponse(['error' => $this->l10n->t('No import content provided')], 400);
            }

            return new JSONResponse($this->service->importTerms(dictionaryUuid: $id, content: $content, isCsv: $isCsv));
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => $this->l10n->t('Custom dictionary not found')], Http::STATUS_NOT_FOUND);
        } catch (RuntimeException $e) {
            return $this->forbidden();
        } catch (InvalidArgumentException $e) {
            return new JSONResponse(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            return $this->error(message: 'Failed to import terms: ', exception: $e);
        }

    }//end import()

    /**
     * Read the import request body, preferring a multipart `file` upload
     * over a pasted `content` param. CSV-ness is derived from the uploaded
     * filename/mimetype, or from an explicit `format=csv` param for pasted
     * content (default newline-list).
     *
     * @return array{0: string|null, 1: bool} Tuple of (content, isCsv).
     */
    private function readImportPayload(): array
    {
        $uploaded = $this->request->getUploadedFile('file');
        if (empty($uploaded) === true || isset($uploaded['tmp_name']) === false) {
            $content = $this->request->getParam('content');
            if (is_string($content) === false || trim($content) === '') {
                return [null, false];
            }

            $format = strtolower((string) $this->request->getParam('format', 'newline'));
            return [$content, ($format === 'csv')];
        }

        if (($uploaded['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return [null, false];
        }

        $content = file_get_contents($uploaded['tmp_name']);
        if ($content === false) {
            return [null, false];
        }

        $name     = strtolower((string) ($uploaded['name'] ?? ''));
        $mimeType = strtolower((string) ($uploaded['type'] ?? ''));
        $isCsv    = (str_ends_with($name, '.csv') === true || str_contains($mimeType, 'csv') === true);

        return [$content, $isCsv];

    }//end readImportPayload()

    /**
     * Require an authenticated session.
     *
     * @return JSONResponse|null A 401 response when unauthenticated, null otherwise.
     */
    private function requireAuthenticated(): ?JSONResponse
    {
        if ($this->userSession->getUser() === null) {
            return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
        }

        return null;

    }//end requireAuthenticated()

    /**
     * Standard 403 response for the organisation gate.
     *
     * @return JSONResponse
     */
    private function forbidden(): JSONResponse
    {
        return new JSONResponse(
            ['error' => $this->l10n->t('You do not have access to this custom dictionary')],
            Http::STATUS_FORBIDDEN
        );

    }//end forbidden()

    /**
     * Explanatory unavailable-state response when OpenRegister is not installed.
     *
     * @return JSONResponse
     */
    private function unavailable(): JSONResponse
    {
        return new JSONResponse(
            ['error' => $this->l10n->t('Custom dictionaries require OpenRegister, which is not currently available.')],
            Http::STATUS_SERVICE_UNAVAILABLE
        );

    }//end unavailable()

    /**
     * Generic 500 error mapper.
     *
     * @param string    $message   Log/response message prefix.
     * @param Exception $exception The caught exception.
     *
     * @return JSONResponse
     */
    private function error(string $message, Exception $exception): JSONResponse
    {
        $this->logger->error(
            $message.$exception->getMessage(),
            ['exception' => $exception]
        );
        return new JSONResponse(
            ['error' => $this->l10n->t($message.'%s', [$exception->getMessage()])],
            500
        );

    }//end error()
}//end class
