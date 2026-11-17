<?php

/**
 * Waarmerk Service
 *
 * Seals PDFs with the organisation certificate, and lists, shows and
 * revokes the seals.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeInterface;
use OCA\Filinq\Exception\WaarmerkRefusedException;
use OCA\Filinq\Kernel\Contracts\WaarmerkServiceInterface;
use OCA\Filinq\Kernel\SealedArtifact;
use OCA\Filinq\Service\Waarmerk\WaarmerkCms;
use OCA\Filinq\Service\Waarmerk\WaarmerkKeyResolver;
use OCA\Filinq\Service\Waarmerk\WaarmerkSettings;
use OCA\Filinq\Service\Waarmerk\WaarmerkStampComposer;
use OCA\Filinq\Service\Waarmerk\WaarmerkStore;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * The organisation seal (waarmerk): an advanced-level electronic seal at
 * most, never a qualified one.
 *
 * Sealing is two steps so a failure leaves nothing behind: prepare() resolves
 * the key, composes the stamp page and signs; record() stores the write-once
 * record once the caller has written the sealed file. Every refusal before
 * that point produces no artifact.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-processed-pdf-can-be-sealed-with-a-waarmerk-req-ddwmk-002
 */
class WaarmerkService implements WaarmerkServiceInterface {

	public const SEAL_TYPES = ['waarmerk', 'processing-certificate', 'signing-evidence'];

	/**
	 * Constructor.
	 *
	 * @param WaarmerkSettings $settings The organisation certificate.
	 * @param WaarmerkKeyResolver $keys Resolves the private key at seal time.
	 * @param WaarmerkStampComposer $composer Appends the stamp page.
	 * @param WaarmerkCms $cms Signs the hash.
	 * @param WaarmerkStore $store The waarmerk records.
	 * @param IURLGenerator $urls Builds the public verification URL.
	 * @param ITimeFactory $time The clock.
	 * @param LoggerInterface $logger Logs fingerprints, never key material.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly WaarmerkSettings $settings,
		private readonly WaarmerkKeyResolver $keys,
		private readonly WaarmerkStampComposer $composer,
		private readonly WaarmerkCms $cms,
		private readonly WaarmerkStore $store,
		private readonly IURLGenerator $urls,
		private readonly ITimeFactory $time,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Compose and sign a sealed PDF; nothing is stored yet.
	 *
	 * @param string $pdf The PDF bytes to seal.
	 * @param string $documentName The name the sealed file will have.
	 * @param string $userId The sealing user.
	 * @param string $sealType waarmerk, processing-certificate or signing-evidence.
	 * @param string|null $anonymizationLinkId The certified run, for a processing certificate.
	 *
	 * @return SealedArtifact
	 *
	 * @throws WaarmerkRefusedException 503 without a resolvable key, 422 with an expired certificate or a non-PDF.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-processed-pdf-can-be-sealed-with-a-waarmerk-req-ddwmk-002
	 */
	public function prepare(
		string $pdf,
		string $documentName,
		string $userId,
		string $sealType = 'waarmerk',
		?string $anonymizationLinkId = null,
	): SealedArtifact {
		if (in_array($sealType, self::SEAL_TYPES, true) === false) {
			throw new WaarmerkRefusedException(message: 'Unknown seal type', status: 400);
		}

		$certificate = $this->settings->certificate();
		if ($certificate === null) {
			throw new WaarmerkRefusedException(message: 'No organisation certificate is configured for the waarmerk', status: 503);
		}

		$now = $this->time->getDateTime();
		if ($certificate->isExpiredAt(at: $now) === true) {
			throw new WaarmerkRefusedException(message: 'The organisation certificate has expired; sealing is refused', status: 422);
		}

		// Before composing anything: an unresolvable key leaves no artifact at all.
		$key = $this->keys->resolve(certificate: $certificate);

		$code = $this->verificationCode();
		$sealedAt = $now->format(DateTimeInterface::ATOM);
		$sealed = $this->composer->compose(
			sourcePdf: $pdf,
			stamp: [
				'organisation' => $certificate->organisation,
				'documentName' => $documentName,
				'sealedAt' => $sealedAt,
				'verificationCode' => $code,
				'verificationUrl' => $this->urls->linkToRouteAbsolute('filinq.waarmerk.verifyPage', ['code' => $code]),
			]
		);

		$hash = hash('sha256', $sealed);
		$signature = $this->cms->sign(hash: $hash, certificatePem: $certificate->pem, keyPem: $key);
		unset($key);

		$record = [
			'documentName' => mb_substr($documentName, 0, 255),
			'documentHash' => $hash,
			'sealType' => $sealType,
			'cmsSignature' => $signature,
			'certificateFingerprint' => $certificate->fingerprint,
			'certificateSubject' => mb_substr($certificate->subject, 0, 512),
			'verificationCode' => $code,
			'sealedAt' => $sealedAt,
			'sealedBy' => mb_substr($userId, 0, 255),
			'status' => 'active',
		];
		if ($anonymizationLinkId !== null && $anonymizationLinkId !== '') {
			$record['anonymizationLinkId'] = $anonymizationLinkId;
		}

		return new SealedArtifact(pdf: $sealed, record: $record);

	}//end prepare()

	/**
	 * Store the write-once record of a sealed artifact the caller has written.
	 *
	 * @param SealedArtifact $artifact The prepared artifact.
	 * @param int $documentFileId The Nextcloud id of the sealed file (0 when not stored as a file).
	 *
	 * @return array<string, mixed> The stored waarmerk.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-processed-pdf-can-be-sealed-with-a-waarmerk-req-ddwmk-002
	 */
	public function record(SealedArtifact $artifact, int $documentFileId): array {
		$stored = $this->store->create(row: array_merge($artifact->record, ['documentFileId' => $documentFileId]));
		$this->logger->info(
			'Filinq: waarmerk applied',
			['waarmerk' => $stored['uuid'], 'sealType' => $stored['sealType'], 'fingerprint' => $stored['certificateFingerprint']]
		);

		return $stored;

	}//end record()

	/**
	 * The seals a user may see: an admin sees all, anyone else their own.
	 *
	 * @param string $userId The caller.
	 * @param bool $isAdmin Whether the caller is an admin.
	 * @param int|null $documentFileId Only the seals of this sealed file.
	 *
	 * @return array<int, array<string, mixed>>
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-processed-pdf-can-be-sealed-with-a-waarmerk-req-ddwmk-002
	 */
	public function list(string $userId, bool $isAdmin, ?int $documentFileId = null): array {
		$filters = [];
		if ($isAdmin === false) {
			$filters['sealedBy'] = $userId;
		}

		if ($documentFileId !== null) {
			$filters['documentFileId'] = $documentFileId;
		}

		return $this->store->search(filters: $filters);

	}//end list()

	/**
	 * One seal, or null when it does not exist or is not the caller's (one answer for both).
	 *
	 * @param string $uuid The seal's uuid.
	 * @param string $userId The caller.
	 * @param bool $isAdmin Whether the caller is an admin.
	 *
	 * @return array<string, mixed>|null
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-processed-pdf-can-be-sealed-with-a-waarmerk-req-ddwmk-002
	 */
	public function show(string $uuid, string $userId, bool $isAdmin): ?array {
		$seal = $this->store->find(uuid: $uuid);
		if ($seal === null || ($isAdmin === false && (string) ($seal['sealedBy'] ?? '') !== $userId)) {
			return null;
		}

		return $seal;

	}//end show()

	/**
	 * Revoke a seal: admins or the sealer only, with a reason, irreversibly.
	 *
	 * @param string $uuid The seal's uuid.
	 * @param string $userId The caller.
	 * @param bool $isAdmin Whether the caller is an admin.
	 * @param string $reason Why it is revoked.
	 *
	 * @return array<string, mixed> The revoked seal.
	 *
	 * @throws WaarmerkRefusedException 404 unknown, 403 not admin or sealer, 400 no reason, 409 already revoked.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-waarmerk-can-be-revoked-and-revocation-dominates-verification-req-ddwmk-005
	 */
	public function revoke(string $uuid, string $userId, bool $isAdmin, string $reason): array {
		$seal = $this->store->find(uuid: $uuid);
		if ($seal === null) {
			throw new WaarmerkRefusedException(message: 'Waarmerk not found', status: 404);
		}

		if ($isAdmin === false && (string) ($seal['sealedBy'] ?? '') !== $userId) {
			throw new WaarmerkRefusedException(message: 'Only an admin or the sealer can revoke this waarmerk', status: 403);
		}

		$reason = trim($reason);
		if ($reason === '') {
			throw new WaarmerkRefusedException(message: 'A reason is required to revoke a waarmerk', status: 400);
		}

		$revoked = $this->store->revoke(
			seal: $seal,
			revocation: [
				'status' => 'revoked',
				'revokedAt' => $this->time->getDateTime()->format(DateTimeInterface::ATOM),
				'revokedBy' => mb_substr($userId, 0, 255),
				'revocationReason' => mb_substr($reason, 0, 2000),
			]
		);
		$this->logger->info('Filinq: waarmerk revoked', ['waarmerk' => $uuid]);

		return $revoked;

	}//end revoke()

	/**
	 * Whether a string has the shape of a verification code.
	 *
	 * @param string $code The candidate.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-waarmerk-is-publicly-verifiable-fail-closed-req-ddwmk-004
	 */
	public static function isWellFormedCode(string $code): bool {
		if (preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12})-([0-9a-f]{4})$/', $code, $parts) !== 1) {
			return false;
		}

		return hash_equals(substr(hash('sha256', $parts[1]), 0, 4), $parts[2]);

	}//end isWellFormedCode()

	/**
	 * A new high-entropy code: a UUIDv4 and a 4-hex check part.
	 *
	 * @return string
	 */
	private function verificationCode(): string {
		$uuid = Uuid::v4()->toRfc4122();
		return $uuid . '-' . substr(hash('sha256', $uuid), 0, 4);

	}//end verificationCode()
}//end class
