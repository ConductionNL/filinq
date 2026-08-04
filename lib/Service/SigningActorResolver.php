<?php

/**
 * Signing Actor Resolver
 *
 * Owns the "who is acting, and may they act as this signer" concern of the
 * signing lifecycle: resolving the acting identity (Nextcloud session user or
 * verified external portal actor), loading the signer record that identity is
 * authorised to act on, and the audit context (client IP, portal assertion
 * reference) that every act is stamped with.
 *
 * Extracted verbatim from SigningService, which had grown past the complexity
 * and coupling thresholds. Behaviour is unchanged — every guard, message and
 * comparison is carried over as-is; this is a pure move.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/portal-signing-actions/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use OCP\IAppConfig;
use OCP\IRequest;
use OCP\IUserSession;
use RuntimeException;

/**
 * Resolves the acting identity and authorises it against a signer record.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/portal-signing-actions/spec.md
 */
class SigningActorResolver
{
    /**
     * Constructor.
     *
     * @param SettingsService $settingsService Settings service (resolves the ObjectService)
     * @param IAppConfig      $config          App config (register/schema resolution)
     * @param IUserSession    $userSession     User session
     * @param IRequest        $request         HTTP request (client IP for the audit trail)
     *
     * @return void
     */
    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly IAppConfig $config,
        private readonly IUserSession $userSession,
        private readonly IRequest $request
    ) {

    }//end __construct()

    /**
     * Load the signer record for `sign()`/`decline()` and authorise the actor.
     *
     * C4 security fix: the signer record must belong to this signing request —
     * without that check, an attacker who knows any valid signerId can act
     * under an arbitrary requestId they do not own. Security finding #282 /
     * portal-signing-actions REQ-DDPSA-005: the acting identity must be the
     * signer it claims to be — the Nextcloud session user for an in-app
     * signer, or the verified assertion's resolved email for an external
     * portal signer. An actor can never act on behalf of another signer by
     * supplying their signer ID.
     *
     * @param string                    $requestId     The signing request ID.
     * @param string                    $signerId      The signer record ID.
     * @param array<string, mixed>|null $verifiedActor The verified external actor, or null.
     * @param string                    $actorUserId   The resolved acting identity.
     * @param string                    $action        Verb used in the authorisation failure
     *                                                 message (`sign` or `decline`).
     *
     * @return array<string, mixed> The loaded, authorised signer record.
     *
     * @throws RuntimeException When the record is missing, belongs to another
     *                          request, or the actor is not that signer.
     *
     * @spec openspec/specs/portal-signing-actions/spec.md
     */
    public function loadAuthorisedSigner(
        string $requestId,
        string $signerId,
        ?array $verifiedActor,
        string $actorUserId,
        string $action
    ): array {
        $objectService  = $this->settingsService->getObjectService();
        $signerRegister = $this->config->getValueString('docudesk', 'signerRecord_register', '');
        $signerSchema   = $this->config->getValueString('docudesk', 'signerRecord_schema', '');
        $signerObject   = $objectService->find(id: $signerId, register: $signerRegister, schema: $signerSchema);

        if ($signerObject === null) {
            throw new RuntimeException('Signer record not found: '.$signerId);
        }

        $signer = $this->toArray(object: $signerObject);

        if (($signer['signingRequestId'] ?? '') !== $requestId) {
            throw new RuntimeException('Signer record does not belong to this signing request');
        }

        if ($this->actorMatchesSigner(signer: $signer, verifiedActor: $verifiedActor, actorUserId: $actorUserId) === false) {
            throw new RuntimeException('Not authorized to '.$action.' as this signer');
        }

        return $signer;

    }//end loadAuthorisedSigner()

    /**
     * Resolve the acting identity for `sign()`/`decline()`.
     *
     * Default (no verified actor): the Nextcloud session user — behaviour is
     * byte-identical to before this seam was added. With a verified actor
     * (portal-signing-actions REQ-DDPSA-005): the resolved portal signer's
     * email, never a Nextcloud uid.
     *
     * @param array<string, mixed>|null $verifiedActor The verified external actor, or null.
     *
     * @return array{0: string, 1: string} [actorUserId, actorDisplayName]
     *
     * @throws RuntimeException When no verified actor is supplied and there is
     *                          no authenticated Nextcloud user.
     *
     * @spec openspec/specs/portal-signing-actions/spec.md
     */
    public function resolveActingIdentity(?array $verifiedActor=null): array
    {
        if ($verifiedActor !== null) {
            $email = (string) ($verifiedActor['email'] ?? '');

            $displayName = 'External signer';
            if ($email !== '') {
                $displayName = $email;
            }

            // Namespaced so a portal actor identity can never collide with (or
            // be mistaken for) a Nextcloud uid in the audit trail.
            return ['portal:'.$email, $displayName];
        }

        $user = $this->userSession->getUser();
        if ($user === null) {
            throw new RuntimeException('No authenticated user');
        }

        return [$user->getUID(), $user->getDisplayName()];

    }//end resolveActingIdentity()

    /**
     * Get the current Nextcloud session user's UID, or '' when unauthenticated.
     *
     * The tolerant counterpart to resolveActingIdentity(): `bulkSign()` needs
     * the uid to match signer records against but must not throw when there is
     * no session — it reports a per-request failure instead.
     *
     * @return string The session user's UID, or '' when there is no session user.
     *
     * @spec openspec/specs/document-signing/spec.md
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
     * Find the signer record ID for a given user
     *
     * @param array<string> $signerIds The signer record IDs
     * @param string        $userId    The user ID to find
     *
     * @return string|null The signer record ID, or null
     *
     * @spec openspec/specs/document-signing/spec.md
     */
    public function findSignerForUser(array $signerIds, string $userId): ?string
    {
        $objectService  = $this->settingsService->getObjectService();
        $signerRegister = $this->config->getValueString('docudesk', 'signerRecord_register', '');
        $signerSchema   = $this->config->getValueString('docudesk', 'signerRecord_schema', '');

        foreach ($signerIds as $signerId) {
            $signerObj = $objectService->find(id: $signerId, register: $signerRegister, schema: $signerSchema);
            $signer    = $this->toArray(object: $signerObj);

            if (($signer['userId'] ?? '') === $userId && ($signer['status'] ?? '') === 'PENDING') {
                return $signerId;
            }
        }//end foreach

        return null;

    }//end findSignerForUser()

    /**
     * Build the audit `metadata` for a portal-originated act.
     *
     * Records the assertion `jti` so the portal act is traceable to its
     * originating portaliq session (portal-signing-actions REQ-DDPSA-005).
     * Returns an empty array for an in-app (Nextcloud session) actor.
     *
     * @param array<string, mixed>|null $verifiedActor The verified external actor, or null.
     *
     * @return array<string, mixed>
     *
     * @spec openspec/specs/portal-signing-actions/spec.md
     */
    public function actorAuditMetadata(?array $verifiedActor): array
    {
        if ($verifiedActor === null) {
            return [];
        }

        return ['portalJti' => (string) ($verifiedActor['jti'] ?? '')];

    }//end actorAuditMetadata()

    /**
     * Get the client IP address
     *
     * @return string The client IP address
     *
     * @spec openspec/specs/document-signing/spec.md
     */
    public function getClientIp(): string
    {
        return $this->request->getRemoteAddress();

    }//end getClientIp()

    /**
     * Check whether the acting identity matches the target signer record.
     *
     * @param array<string, mixed>      $signer        The loaded signer record.
     * @param array<string, mixed>|null $verifiedActor The verified external actor, or null.
     * @param string                    $actorUserId   The resolved acting identity (unused for
     *                                                 the verified-actor branch; kept for the
     *                                                 in-app branch's exact pre-existing check).
     *
     * @return bool True when the actor is authorised to act as this signer.
     */
    private function actorMatchesSigner(array $signer, ?array $verifiedActor, string $actorUserId): bool
    {
        if ($verifiedActor !== null) {
            $signerEmail = (string) ($signer['email'] ?? '');
            $actorEmail  = (string) ($verifiedActor['email'] ?? '');

            return $signerEmail !== '' && $actorEmail !== '' && strcasecmp($signerEmail, $actorEmail) === 0;
        }

        return ($signer['userId'] ?? '') === $actorUserId;

    }//end actorMatchesSigner()

    /**
     * Normalise an ObjectService result to an array
     *
     * OpenRegister's ObjectService::saveObject()/find() return an ObjectEntity
     * instance, not a plain array. Callers that need array access must serialize
     * it first. This helper mirrors the pattern TemplateService already uses.
     *
     * @param mixed $object The ObjectEntity (or array) to normalise
     *
     * @return array<string, mixed> The serialized object
     */
    private function toArray(mixed $object): array
    {
        if (is_object($object) === true && method_exists($object, 'jsonSerialize') === true) {
            return $object->jsonSerialize();
        }

        return (array) $object;

    }//end toArray()
}//end class
