<?php

/**
 * Public Verification Controller
 *
 * Public, account-free verification portal (`verify/{token}` +
 * `api/verify/{token}`) — reached by scanning the QR stamped on a signed or
 * waarmerked DocuDesk document. No Nextcloud session, no file-read access.
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
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use OCA\DocuDesk\AppInfo\Application;
use OCA\DocuDesk\Service\SignatureVerificationLinkService;
use OCA\DocuDesk\Service\SigningAuditService;
use OCA\DocuDesk\Service\SigningVerificationService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Public verification portal + JSON verdict endpoint.
 *
 * Security posture (design.md D3 / REQ-DDSVP-004):
 * - `#[PublicPage] #[NoCSRFRequired]` on both the page and the API.
 * - `#[AnonRateLimit]` throttles many-varying-tokens-from-one-IP enumeration
 *   attempts; `#[BruteForceProtection]` + `Response::throttle()` on every
 *   unknown/malformed-token response additionally penalises repeated guesses
 *   keyed by (action, token, IP) — both layers active together.
 * - Unknown, malformed, and revoked tokens ALL render the identical
 *   `status: unknown` shape — never a 404, never a distinct error — so the
 *   endpoint is not an existence oracle.
 * - No document bytes are served, no download is offered; only the file
 *   NAME (never a path) is exposed.
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/signature-verification-portal/specs/signature-verification-portal/spec.md#requirement-public-verification-portal-page-req-ddsvp-001
 */
class PublicVerificationController extends Controller
{
    /**
     * Public throttle action name, shared by the page + API guards so
     * repeated failures against either surface accumulate against one
     * bruteforce bucket per token+IP.
     */
    private const THROTTLE_ACTION = 'docudeskVerifyToken';

    /**
     * A syntactically plausible token: hex, the length minted by
     * {@see SignatureVerificationLinkService::mint()} (32 hex chars =
     * 128-bit) or longer, so a clearly-malformed value short-circuits
     * before ever reaching a register lookup.
     */
    private const TOKEN_PATTERN = '/^[a-f0-9]{32,64}$/';

    /**
     * Constructor
     *
     * @param IRequest                         $request             Request object
     * @param SignatureVerificationLinkService $linkService          Verification-link mint/lookup
     * @param SigningVerificationService       $verificationService  Record-based verify formatting
     * @param SigningAuditService              $auditService         Audit trail (redacted rollup)
     * @param IInitialState                    $initialState         Provides the token to the guest SPA (ADR-004 — no DOM dataset reads)
     * @param LoggerInterface                  $logger               Logger
     *
     * @return void
     */
    public function __construct(
        IRequest $request,
        private readonly SignatureVerificationLinkService $linkService,
        private readonly SigningVerificationService $verificationService,
        private readonly SigningAuditService $auditService,
        private readonly IInitialState $initialState,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct(appName: Application::APP_ID, request: $request);

    }//end __construct()

    /**
     * Render the public portal SPA shell for a `verify/{token}` deep link.
     *
     * Standalone guest layout (RENDER_AS_BASE) — NOT the authenticated
     * manifest shell (per design.md, the internal operator verify page is a
     * separate, `orphaned-surface-restoration`-owned surface). The page's own
     * script reads the token from the URL and calls {@see show()}.
     *
     * @param string $token The verification token (unused server-side here;
     *                      the SPA reads it from `window.location`).
     *
     * @return TemplateResponse
     *
     * @spec openspec/changes/signature-verification-portal/specs/signature-verification-portal/spec.md#requirement-public-verification-portal-page-req-ddsvp-001
     */
    #[PublicPage]
    #[NoCSRFRequired]
    #[AnonRateLimit(limit: 30, period: 60)]
    public function page(string $token=''): TemplateResponse
    {
        // IInitialState (ADR-004), not a DOM data-attribute: the guest SPA
        // reads this via loadState('docudesk', 'verifyToken', '') instead of
        // parsing window.location itself.
        $this->initialState->provideInitialState(key: 'verifyToken', data: $token);

        $response = new TemplateResponse(
            appName: Application::APP_ID,
            templateName: 'verify',
            params: [],
            renderAs: TemplateResponse::RENDER_AS_BASE
        );

        $csp = new ContentSecurityPolicy();
        $csp->addAllowedConnectDomain("'self'");
        $response->setContentSecurityPolicy($csp);

        return $response;

    }//end page()

    /**
     * Resolve a verification token to its public verdict JSON.
     *
     * Fail-closed: any unverifiable state (unknown token, malformed token,
     * revoked record, lookup failure) returns the SAME `status: unknown`
     * shape — see class docblock.
     *
     * @param string $token The verification token.
     *
     * @return JSONResponse
     *
     * @spec openspec/changes/signature-verification-portal/specs/signature-verification-portal/spec.md#requirement-public-verification-portal-page-req-ddsvp-001
     * @spec openspec/changes/signature-verification-portal/specs/signature-verification-portal/spec.md#requirement-public-endpoints-are-fail-closed-and-rate-limited-req-ddsvp-004
     */
    #[PublicPage]
    #[NoCSRFRequired]
    #[AnonRateLimit(limit: 30, period: 60)]
    #[BruteForceProtection(action: self::THROTTLE_ACTION)]
    public function show(string $token=''): JSONResponse
    {
        if (preg_match(self::TOKEN_PATTERN, $token) !== 1) {
            return $this->unknownResponse(token: $token);
        }

        $record = $this->linkService->lookupByToken(token: $token);
        if ($record === null) {
            return $this->unknownResponse(token: $token);
        }

        try {
            $verdict         = $this->verificationService->verifyByRecord(record: $record);
            $verdict['audit'] = $this->buildAuditRollup(record: $record);

            return new JSONResponse($verdict);
        } catch (Throwable $e) {
            $this->logger->error(
                'PublicVerificationController: failed to render verdict for a known token: '.$e->getMessage(),
                ['exception' => $e]
            );
            // Fail closed — never leak the exception, never fall back to a
            // shape that differs from the unknown-token response's contract
            // (still status:unknown here, since we could not honestly serve
            // a verdict).
            return $this->unknownResponse(token: $token);
        }//end try

    }//end show()

    /**
     * The neutral "unknown" verdict — identical shape to a known verdict
     * (REQ-DDSVP-001 "Unknown token is not an existence oracle"), throttled
     * so repeated guesses against one token (or many tokens from one IP, via
     * `#[AnonRateLimit]`) are penalised.
     *
     * @param string $token The (possibly malformed) token that was requested.
     *
     * @return JSONResponse
     */
    private function unknownResponse(string $token): JSONResponse
    {
        $response = new JSONResponse(
            data: [
                'status'      => 'unknown',
                'fileName'    => '',
                'contentHash' => '',
                'signatures'  => [],
                'isValid'     => false,
                'waarmerkRef' => null,
                'createdAt'   => null,
                'audit'       => null,
            ]
        );

        $response->throttle(['action' => self::THROTTLE_ACTION, 'token' => $token]);

        return $response;

    }//end unknownResponse()

    /**
     * Build a redacted audit rollup for the public page — step count and
     * terminal outcome only, NEVER signer emails/UIDs/IPs (design.md Open
     * Questions: "provisional: redacted rollup only").
     *
     * Presence-gated: a record with no `signingRequestId` (e.g. a
     * waarmerk-only seal, or the audit trail being unavailable) yields null,
     * which the frontend renders as "no audit trail available" rather than
     * an error.
     *
     * @param array<string, mixed> $record The verification record.
     *
     * @return array<string, mixed>|null The redacted rollup, or null.
     */
    private function buildAuditRollup(array $record): ?array
    {
        $signingRequestId = $record['signingRequestId'] ?? null;
        if (is_string($signingRequestId) === false || $signingRequestId === '') {
            return null;
        }

        try {
            $entries = $this->auditService->getAuditTrail(signingRequestId: $signingRequestId);
        } catch (Throwable $e) {
            $this->logger->warning(
                'PublicVerificationController: audit rollup unavailable: '.$e->getMessage()
            );
            return null;
        }

        if (is_array($entries) === false || count($entries) === 0) {
            return null;
        }

        $lastAction = null;
        $lastAt     = null;
        foreach ($entries as $entry) {
            $when = $entry['created'] ?? null;
            if ($when !== null) {
                $lastAction = ($entry['action'] ?? null);
                $lastAt     = $when;
            }
        }

        return [
            'steps'      => count($entries),
            'lastAction' => $lastAction,
            'lastAt'     => $lastAt,
        ];

    }//end buildAuditRollup()
}//end class
