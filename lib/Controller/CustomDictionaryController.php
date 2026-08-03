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
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
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
        return $this->dispatch(
            operation: fn (): array => $this->service->listDictionaries(),
            failureMessage: 'Failed to list custom dictionaries: ',
            notFoundMessage: $this->l10n->t('Custom dictionary not found')
        );

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
        return $this->dispatch(
            operation: fn (): array => $this->service->getDictionary(uuid: $id),
            failureMessage: 'Failed to load custom dictionary: ',
            notFoundMessage: $this->l10n->t('Custom dictionary not found')
        );

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
        return $this->dispatch(
            operation: fn (): array => $this->service->createDictionary(data: $this->request->getParams()),
            failureMessage: 'Failed to create custom dictionary: ',
            notFoundMessage: $this->l10n->t('Custom dictionary not found'),
            status: Http::STATUS_CREATED
        );

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
        return $this->dispatch(
            operation: fn (): array => $this->service->updateDictionary(
                uuid: $id,
                data: $this->request->getParams()
            ),
            failureMessage: 'Failed to update custom dictionary: ',
            notFoundMessage: $this->l10n->t('Custom dictionary not found')
        );

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
        return $this->dispatch(
            operation: function () use ($id): array {
                $this->service->deleteDictionary(uuid: $id);
                return ['deleted' => $id];
            },
            failureMessage: 'Failed to delete custom dictionary: ',
            notFoundMessage: $this->l10n->t('Custom dictionary not found')
        );

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
        return $this->dispatch(
            operation: fn (): array => $this->service->listTerms(dictionaryUuid: $id),
            failureMessage: 'Failed to list terms: ',
            notFoundMessage: $this->l10n->t('Custom dictionary not found')
        );

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
        return $this->dispatch(
            operation: fn (): array => $this->service->createTerm(
                dictionaryUuid: $id,
                data: $this->request->getParams()
            ),
            failureMessage: 'Failed to create term: ',
            notFoundMessage: $this->l10n->t('Custom dictionary not found'),
            status: Http::STATUS_CREATED
        );

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
        return $this->dispatch(
            operation: function () use ($id, $termId): array {
                $this->service->deleteTerm(dictionaryUuid: $id, termUuid: $termId);
                return ['deleted' => $termId];
            },
            failureMessage: 'Failed to delete term: ',
            notFoundMessage: $this->l10n->t('Term not found')
        );

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
        $noContentMessage = $this->l10n->t('No import content provided');

        return $this->dispatch(
            operation: function () use ($id, $noContentMessage): array {
                [$content, $isCsv] = $this->readImportPayload();
                if ($content === null) {
                    // Mapped to HTTP 400 with this message by dispatch().
                    throw new InvalidArgumentException($noContentMessage);
                }

                return $this->service->importTerms(
                    dictionaryUuid: $id,
                    content: $content,
                    isCsv: $isCsv
                );
            },
            failureMessage: 'Failed to import terms: ',
            notFoundMessage: $this->l10n->t('Custom dictionary not found')
        );

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
     * Run one endpoint operation behind the shared guard, mapping the
     * exceptions every endpoint can raise to their standard responses.
     *
     * Centralising this keeps each endpoint a single expression, and keeps
     * the guard order identical everywhere: unauthenticated (401) is checked
     * before OpenRegister availability (503), and only then does the
     * operation run.
     *
     * @param callable():array<string, mixed> $operation       The gated operation.
     * @param string                          $failureMessage  Log/response prefix for an unexpected failure.
     * @param string                          $notFoundMessage Already-translated 404 message.
     * @param int                             $status          Success HTTP status.
     *
     * @return JSONResponse
     *
     * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
     */
    private function dispatch(
        callable $operation,
        string $failureMessage,
        string $notFoundMessage,
        int $status=Http::STATUS_OK
    ): JSONResponse {
        $blocked = $this->guard();
        if ($blocked !== null) {
            return $blocked;
        }

        try {
            return new JSONResponse($operation(), $status);
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => $notFoundMessage], Http::STATUS_NOT_FOUND);
        } catch (RuntimeException $e) {
            return $this->forbidden();
        } catch (InvalidArgumentException $e) {
            return new JSONResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->error(message: $failureMessage, exception: $e);
        }//end try

    }//end dispatch()

    /**
     * The guard every endpoint shares: an authenticated session, and an
     * installed OpenRegister.
     *
     * @return JSONResponse|null The blocking response, or null when the
     *                           caller may proceed.
     *
     * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
     */
    private function guard(): ?JSONResponse
    {
        $unauthenticated = $this->requireAuthenticated();
        if ($unauthenticated !== null) {
            return $unauthenticated;
        }

        if ($this->service->isAvailable() === false) {
            return $this->unavailable();
        }

        return null;

    }//end guard()

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
