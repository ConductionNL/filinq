<?php
/**
 * Anonymize Request Service
 *
 * Owns the single-file anonymize endpoint's workflow behind the controller:
 * authentication and per-user file-access verification, request-body
 * validation, the publication-prohibition guards, and the call into
 * AnonymizationService. Extracted from AnonymizationController so the
 * controller stays a thin HTTP boundary.
 *
 * `ConversionFailedException` is deliberately NOT handled here: AnonymizationService
 * re-throws it unchanged so the controller can build the documented 422 body, and
 * that contract is preserved.
 *
 * Every method returns the `{status, body}` pair used elsewhere in this app
 * (see AnonymizationService::applyRelationSkipDecision) rather than an HTTP
 * response object, so the HTTP layer stays in the controller.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/anonymization/spec.md
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-11
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use OCA\DocuDesk\Exception\ProhibitionGateException;
use OCP\AppFramework\Http;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Drives the anonymize endpoint's authentication, validation and guards.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/anonymization/spec.md
 */
class AnonymizeRequestService
{

    /**
     * Validator for the anonymize request body.
     *
     * @var AnonymizeRequestValidator
     */
    private readonly AnonymizeRequestValidator $validator;

    /**
     * Constructor for AnonymizeRequestService
     *
     * @param LoggerInterface      $logger               Logger for gate failure reporting.
     * @param AnonymizationService $anonymizationService Service for anonymization operations.
     * @param IL10N                $l10n                 The localization service.
     * @param IRootFolder          $rootFolder           Root folder for per-user file access checks.
     * @param IUserSession         $userSession          User session for authentication.
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AnonymizationService $anonymizationService,
        private readonly IL10N $l10n,
        private readonly IRootFolder $rootFolder,
        private readonly IUserSession $userSession
    ) {
        $this->validator = new AnonymizeRequestValidator($l10n);

    }//end __construct()

    /**
     * Require an authenticated session.
     *
     * @return array{status: int, body: array<string, mixed>}|null The 401 payload, or null when
     *                                                             a user is signed in.
     *
     * @spec openspec/specs/anonymization/spec.md
     */
    public function requireAuthenticated(): ?array
    {
        if ($this->userSession->getUser() !== null) {
            return null;
        }

        return [
            'status' => Http::STATUS_UNAUTHORIZED,
            'body'   => ['error' => $this->l10n->t('Not authenticated')],
        ];

    }//end requireAuthenticated()

    /**
     * Return the acting user's UID, or an empty string when unauthenticated.
     *
     * @return string The UID of the signed-in user.
     *
     * @spec openspec/specs/anonymization/spec.md
     */
    public function currentUserId(): string
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            return '';
        }

        return $user->getUID();

    }//end currentUserId()

    /**
     * Verify the current user has access to the given file ID.
     *
     * Resolves the file via the user's own file tree so that an authenticated
     * user cannot operate on files they do not own (security finding C3 —
     * file IDOR). Returns 404 on failure so callers cannot probe for existence.
     *
     * @param int $fileId The Nextcloud file ID to check.
     *
     * @return array{status: int, body: array<string, mixed>}|null Null when access is granted,
     *                                                             the error payload otherwise.
     *
     * @spec openspec/specs/anonymization/spec.md
     */
    public function verifyFileAccess(int $fileId): ?array
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            return [
                'status' => Http::STATUS_UNAUTHORIZED,
                'body'   => ['error' => $this->l10n->t('Not authenticated')],
            ];
        }

        $nodes = $this->rootFolder->getUserFolder($user->getUID())->getById($fileId);
        if (empty($nodes) === true) {
            return $this->fileNotFound();
        }

        if (($nodes[0] instanceof File) === false) {
            return $this->fileNotFound();
        }

        return null;

    }//end verifyFileAccess()

    /**
     * Validate the anonymize request body.
     *
     * @param array<string, mixed> $params Request parameters.
     *
     * @return array{error: array{status: int, body: array<string, mixed>}|null,
     *               request: array<string, mixed>|null} The first validation failure, or the
     *                                                   normalised request under `request`.
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
     */
    public function validateBody(array $params): array
    {
        return $this->validator->validateBody(params: $params);

    }//end validateBody()

    /**
     * Reject unredacted entities that match a publication-prohibition rule.
     *
     * @param array<int, array<string, mixed>> $unredactedEntities Entities to publish unredacted.
     *
     * @return array{status: int, body: array<string, mixed>}|null The 422 payload, or null when clear.
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-2
     */
    public function checkUnredactedProhibitions(array $unredactedEntities): ?array
    {
        if (empty($unredactedEntities) === true) {
            return null;
        }

        $violations = $this->anonymizationService->checkUnredactedProhibitions(
            unredactedEntities: $unredactedEntities
        );
        if (empty($violations) === true) {
            return null;
        }

        return [
            'status' => 422,
            'body'   => [
                'error'             => $this->l10n->t(
                    'One or more unredacted entities match a publication-prohibition rule. '
                    .'Move those entities to entities[] to anonymize them instead.'
                ),
                'prohibitedEntries' => $violations,
            ],
        ];

    }//end checkUnredactedProhibitions()

    /**
     * Run the defence-in-depth backstop and perform the anonymisation.
     *
     * `ConversionFailedException` propagates to the caller by design — the
     * controller owns the documented 422 conversion body.
     *
     * @param int                  $fileId       The Nextcloud file ID.
     * @param string               $userId       UID of the acting user (for override audit entries).
     * @param array<string, mixed> $params       Raw request parameters (scope, dossierKey, filters).
     * @param array<string, mixed> $request      The normalised body from validateBody().
     * @param string               $outputFormat The resolved output format.
     *
     * @return array{status: int, body: array<string, mixed>} The response payload.
     *
     * @throws \OCA\DocuDesk\Exception\ConversionFailedException When the PDF cascade is exhausted.
     *
     * @spec openspec/specs/anonymization/spec.md
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-4
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-11
     */
    public function executeAnonymize(
        int $fileId,
        string $userId,
        array $params,
        array $request,
        string $outputFormat
    ): array {
        $entities = $this->filterEntities(entities: $request['entities'], params: $params);

        // Defence-in-depth backstop (Robert): refuse if an absolute-tier
        // prohibition entity would be left un-redacted (e.g. skipped by
        // bypassing the guarded skip endpoint and PATCHing OpenRegister
        // directly). Complements the request-payload prohibition gate that
        // runProhibitionGate() enforces inside anonymizeDocument().
        $violations = $this->anonymizationService->absoluteProhibitionViolations($fileId);
        if (empty($violations) === false) {
            return [
                'status' => 422,
                'body'   => [
                    'error'                     => $this->l10n->t(
                        'Anonymisation blocked: prohibition-listed entities would be left un-redacted.'
                    ),
                    'missingProhibitionMatches' => $violations,
                ],
            ];
        }

        try {
            $result = $this->callAnonymizeDocument(
                fileId: $fileId,
                entities: $entities,
                userId: $userId,
                params: $params,
                request: $request,
                outputFormat: $outputFormat
            );
        } catch (ProhibitionGateException $e) {
            return $this->prohibitionGateResponse(gateError: $e, fileId: $fileId);
        }

        if ($request['hasStrayBases'] === true) {
            $result['ignoredFields'] = ['bases'];
        }

        return ['status' => 200, 'body' => $result];

    }//end executeAnonymize()

    /**
     * Call the AnonymizationService entry point this request asked for.
     *
     * `appendBasisSummary` selects between the plain and the summary-producing
     * entry point; every other value is forwarded verbatim.
     *
     * @param int                              $fileId       The Nextcloud file ID.
     * @param array<int, array<string, mixed>> $entities     The filtered entities to anonymize.
     * @param string                           $userId       UID of the acting user.
     * @param array<string, mixed>             $params       Request parameters (scope / dossierKey).
     * @param array<string, mixed>             $request      The validated request body.
     * @param string                           $outputFormat Resolved per-call output format.
     *
     * @return array<string, mixed> The AnonymizationService result payload.
     *
     * @throws \OCA\DocuDesk\Exception\ProhibitionGateException When the prohibition gate fires.
     * @throws \OCA\DocuDesk\Exception\ConversionFailedException When the PDF cascade is exhausted.
     *
     * @spec openspec/specs/anonymization/spec.md
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-1
     */
    private function callAnonymizeDocument(
        int $fileId,
        array $entities,
        string $userId,
        array $params,
        array $request,
        string $outputFormat
    ): array {
        if ($request['appendBasisSummary'] === true) {
            return $this->anonymizationService->anonymizeDocumentWithBasisSummary(
                fileId: $fileId,
                entities: $entities,
                outputFormat: $outputFormat,
                unredactedEntities: $request['unredactedEntities'],
                overrides: $request['overrides'],
                userId: $userId,
                scope: $this->resolveScope(params: $params),
                dossierKey: $this->resolveDossierKey(params: $params)
            );
        }

        return $this->anonymizationService->anonymizeDocument(
            fileId: $fileId,
            entities: $entities,
            outputFormat: $outputFormat,
            unredactedEntities: $request['unredactedEntities'],
            overrides: $request['overrides'],
            userId: $userId,
            scope: $this->resolveScope(params: $params),
            dossierKey: $this->resolveDossierKey(params: $params)
        );

    }//end callAnonymizeDocument()

    /**
     * Record a per-entity skip/include decision, guarded by the prohibition policy.
     *
     * @param int                  $relationId The EntityRelation id.
     * @param array<string, mixed> $params     Request parameters.
     *
     * @return array{status: int, body: array<string, mixed>} The response payload.
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
     */
    public function applyRelationDecision(int $relationId, array $params): array
    {
        try {
            $skip = false;
            if (array_key_exists('skipAnonymization', $params) === true) {
                if (is_bool($params['skipAnonymization']) === false) {
                    return [
                        'status' => 400,
                        'body'   => [
                            'error' => $this->l10n->t('Invalid skipAnonymization: must be a boolean'),
                        ],
                    ];
                }

                $skip = $params['skipAnonymization'];
            }

            $bases = null;
            if (array_key_exists('bases', $params) === true && is_array($params['bases']) === true) {
                $bases = array_values($params['bases']);
            }

            return $this->anonymizationService->applyRelationSkipDecision(
                relationId: $relationId,
                skip: $skip,
                bases: $bases,
                force: filter_var(($params['force'] ?? false), FILTER_VALIDATE_BOOLEAN)
            );
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to update entity relation decision: '.$e->getMessage(),
                ['exception' => $e]
            );

            return [
                'status' => 500,
                'body'   => [
                    'error' => $this->l10n->t('Failed to update entity relation decision'),
                ],
            ];
        }//end try

    }//end applyRelationDecision()

    /**
     * Map a ProhibitionGateException onto its response payload.
     *
     * Fail-closed (backend outage) → 503 so clients can retry; rule-match block
     * → 422 with a structured missing/rejected body so the UI can prompt for
     * overrides.
     *
     * @param ProhibitionGateException $gateError The exception raised by the gate.
     * @param int                      $fileId    The Nextcloud file ID (for the log context).
     *
     * @return array{status: int, body: array<string, mixed>} The response payload.
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-11
     */
    private function prohibitionGateResponse(ProhibitionGateException $gateError, int $fileId): array
    {
        $backendReason = $gateError->getBackendUnavailable();
        if ($backendReason !== null) {
            $this->logger->warning(
                'ProhibitionGate failed closed: '.$backendReason,
                ['fileId' => $fileId]
            );

            return [
                'status' => 503,
                'body'   => [
                    'error'              => $this->l10n->t(
                        'Anonymisation temporarily unavailable: the prohibition gate could not '
                        .'verify the document. Please retry shortly.'
                    ),
                    'backendUnavailable' => $backendReason,
                ],
            ];
        }

        return [
            'status' => 422,
            'body'   => [
                'error'                     => $this->l10n->t(
                    'Anonymisation blocked: one or more prohibition-listed entities are missing '
                    .'from the to-be-anonymised set or an override was rejected.'
                ),
                'missingProhibitionMatches' => $gateError->getMissingProhibitionMatches(),
                'rejectedOverrides'         => $gateError->getRejectedOverrides(),
            ],
        ];

    }//end prohibitionGateResponse()

    /**
     * Build the HTTP 404 payload used for both "no node" and "not a file".
     *
     * @return array{status: int, body: array<string, mixed>} The 404 payload.
     *
     * @spec openspec/specs/anonymization/spec.md
     */
    private function fileNotFound(): array
    {
        return [
            'status' => Http::STATUS_NOT_FOUND,
            'body'   => ['error' => $this->l10n->t('File not found')],
        ];

    }//end fileNotFound()

    /**
     * Resolve the placeholder-numbering scope forwarded to OpenRegister.
     *
     * 'document' (default) numbers entities locally to this file; 'dossier'
     * makes the number consistent across the dossier folder's files. Any value
     * other than 'dossier' normalises to per-document.
     *
     * @param array<string, mixed> $params Request params.
     *
     * @return string Either 'document' or 'dossier'.
     *
     * @spec openspec/specs/anonymization/spec.md
     */
    private function resolveScope(array $params): string
    {
        if ((string) ($params['scope'] ?? 'document') === 'dossier') {
            return 'dossier';
        }

        return 'document';

    }//end resolveScope()

    /**
     * Resolve the stable dossier folder id, when supplied.
     *
     * When omitted under scope=dossier, OpenRegister falls back to the file's
     * parent folder.
     *
     * @param array<string, mixed> $params Request params.
     *
     * @return string|null The dossier key, or null when not supplied.
     *
     * @spec openspec/specs/anonymization/spec.md
     */
    private function resolveDossierKey(array $params): ?string
    {
        $dossierKeyParam = ($params['dossierKey'] ?? null);
        if ($dossierKeyParam !== null && $dossierKeyParam !== '') {
            return (string) $dossierKeyParam;
        }

        return null;

    }//end resolveDossierKey()

    /**
     * Apply the optional excludeTypes and minConfidence request filters.
     *
     * @param array<int, array<string, mixed>> $entities The submitted entities.
     * @param array<string, mixed>             $params   Request parameters.
     *
     * @return array<int, array<string, mixed>> Filtered entities.
     *
     * @spec openspec/specs/anonymization/spec.md
     */
    private function filterEntities(array $entities, array $params): array
    {
        return $this->filterByConfidence(
            entities: $this->filterByExcludeTypes(entities: $entities, params: $params),
            params: $params
        );

    }//end filterEntities()

    /**
     * Filter entities by excluded types
     *
     * @param array<int, array<string, mixed>> $entities The entities
     * @param array<string, mixed>             $params   Request parameters
     *
     * @return array<int, array<string, mixed>> Filtered entities
     *
     * @spec openspec/specs/anonymization/spec.md
     */
    private function filterByExcludeTypes(array $entities, array $params): array
    {
        $excludeTypes = ($params['excludeTypes'] ?? []);
        if (is_array($excludeTypes) === false || empty($excludeTypes) === true) {
            return $entities;
        }

        return array_values(
            array_filter(
                $entities,
                static function (array $entity) use ($excludeTypes): bool {
                    $type = ($entity['type'] ?? $entity['entityType'] ?? '');
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
     *
     * @spec openspec/specs/anonymization/spec.md
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
