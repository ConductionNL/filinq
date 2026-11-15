<?php

/**
 * LibreSign signing provider
 *
 * Certificate-based signing through the LibreSign app on the same Nextcloud.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Signing
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

namespace OCA\Filinq\Service\Signing;

use InvalidArgumentException;
use OCP\IAppConfig;
use RuntimeException;

/**
 * Signs through LibreSign, and claims no level its certificate cannot back.
 *
 * LibreSign signs with an X.509 certificate, so SES and AdES are always
 * offered. QES is offered only when the admin marked the certificate as
 * qualified (`libresign_qualified`, default off). Every method that could
 * produce or start a signature checks the level again and refuses one it
 * does not support: a QES request is never served by an AdES signature.
 *
 * LibreSign runs the signer flow itself (its own notifications and signing
 * page). Filinq keeps the LibreSign file uuid as the request's `externalId`,
 * reads the state back with `checkStatus()` and takes the signed PDF only
 * once LibreSign reports it signed.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/libresign-signing-provider/spec.md
 */
class LibreSignProvider implements SigningProviderInterface {

	/**
	 * The provider identifier, and the LibreSign app id.
	 */
	public const IDENTIFIER = 'libresign';

	/**
	 * LibreSign's FileStatus::SIGNED (lib/Enum/FileStatus.php).
	 */
	public const STATUS_SIGNED = 3;

	/**
	 * LibreSign's FileStatus values mapped onto signing-request states:
	 * DRAFT and ABLE_TO_SIGN wait for signers, PARTIAL_SIGNED and
	 * SIGNING_IN_PROGRESS are under way, SIGNED is done, DELETED and CANCELED
	 * are withdrawn.
	 */
	private const STATUS_MAP = [
		0 => 'PENDING',
		1 => 'PENDING',
		2 => 'IN_PROGRESS',
		3 => 'COMPLETED',
		4 => 'CANCELLED',
		5 => 'IN_PROGRESS',
		6 => 'CANCELLED',
	];

	/**
	 * Constructor
	 *
	 * @param IAppConfig      $config The app config (libresign_qualified)
	 * @param LibreSignClient $client The calls to LibreSign
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IAppConfig $config,
		private readonly LibreSignClient $client,
	) {

	}//end __construct()

	/**
	 * The provider identifier.
	 *
	 * @return string Always `libresign`.
	 *
	 * @spec openspec/changes/archive/2026-09-29-libresign-signing-provider/tasks.md#task-1.1
	 */
	public function getIdentifier(): string {
		return self::IDENTIFIER;

	}//end getIdentifier()

	/**
	 * Create the LibreSign signature request for a document.
	 *
	 * @param string               $documentPath The file path, used when no fileId is given
	 * @param string               $documentName The name LibreSign shows
	 * @param array<string, mixed> $signers      Signers with userId or email, and displayName
	 * @param string               $level        The requested level
	 * @param array<string, mixed> $options      `content`: the PDF bytes, or `fileId`: a file LibreSign's service account can read
	 *
	 * @return array<string, mixed> success, externalId (the LibreSign file uuid), message
	 *
	 * @throws RuntimeException         When the level is not supported or LibreSign answers without a uuid
	 * @throws InvalidArgumentException When a signer has neither an account nor an email address
	 *
	 * @spec openspec/changes/archive/2026-09-29-libresign-signing-provider/tasks.md#task-1.1
	 */
	public function initiateSigning(
		string $documentPath,
		string $documentName,
		array $signers,
		string $level,
		array $options = [],
	): array {
		$this->assertLevel(level: $level);

		$file = $this->toLibreSignFile(documentPath: $documentPath, documentName: $documentName, options: $options);

		$entries = [];
		foreach (array_values($signers) as $signer) {
			$entries[] = $this->toLibreSignSigner(signer: (array) $signer);
		}

		$answer = $this->client->requestSignature(file: $file, name: $documentName, signers: $entries);
		$uuid = (string) ($answer['uuid'] ?? '');
		if ($uuid === '') {
			throw new RuntimeException('LibreSign accepted the signature request but returned no uuid');
		}

		return [
			'success' => true,
			'externalId' => $uuid,
			'message' => 'LibreSign signature request created; LibreSign notifies the signers',
		];

	}//end initiateSigning()

	/**
	 * The request's state as LibreSign reports it. Reads only.
	 *
	 * @param string $externalId The LibreSign file uuid
	 *
	 * @return array<string, mixed> status (a signing-request state), libresignStatus, nodeId, signers
	 *
	 * @spec openspec/changes/archive/2026-09-29-libresign-signing-provider/tasks.md#task-1.1
	 */
	public function checkStatus(string $externalId): array {
		$data = $this->client->validate(uuid: $externalId);
		$libreSignStatus = (int) ($data['status'] ?? -1);

		$signers = [];
		foreach ((array) ($data['signers'] ?? []) as $signer) {
			$signers[] = [
				'displayName' => (string) ($signer['displayName'] ?? ''),
				'signedAt' => ($signer['sign_date'] ?? null),
			];
		}

		return [
			'status' => (self::STATUS_MAP[$libreSignStatus] ?? 'PENDING'),
			'libresignStatus' => $libreSignStatus,
			'nodeId' => (int) ($data['nodeId'] ?? 0),
			'signers' => $signers,
		];

	}//end checkStatus()

	/**
	 * The signed PDF, only once LibreSign reports the request signed.
	 *
	 * @param string $externalId The LibreSign file uuid
	 *
	 * @return string The signed PDF bytes.
	 *
	 * @throws RuntimeException When the request is not signed, or what came back is not a PDF.
	 *
	 * @spec openspec/changes/archive/2026-09-29-libresign-signing-provider/tasks.md#task-1.1
	 */
	public function downloadSignedDocument(string $externalId): string {
		$status = $this->checkStatus(externalId: $externalId);
		if ($status['libresignStatus'] !== self::STATUS_SIGNED) {
			throw new RuntimeException(
				'LibreSign has not signed this document yet (LibreSign status ' . $status['libresignStatus'] . ')'
			);
		}

		$bytes = $this->client->downloadSigned(uuid: $externalId);
		if (str_starts_with($bytes, '%PDF') === false) {
			throw new RuntimeException('LibreSign reported the document signed but did not return a PDF');
		}

		return $bytes;

	}//end downloadSignedDocument()

	/**
	 * Withdraw the request in LibreSign.
	 *
	 * @param string $externalId The LibreSign file uuid
	 *
	 * @return void
	 *
	 * @throws RuntimeException When LibreSign does not know the file or refuses.
	 *
	 * @spec openspec/changes/archive/2026-09-29-libresign-signing-provider/tasks.md#task-1.1
	 */
	public function cancelSigning(string $externalId): void {
		$nodeId = $this->checkStatus(externalId: $externalId)['nodeId'];
		if ($nodeId <= 0) {
			throw new RuntimeException('LibreSign returned no file for request ' . $externalId);
		}

		$this->client->deleteRequest(nodeId: $nodeId);

	}//end cancelSigning()

	/**
	 * Whether the configured certificate backs a level.
	 *
	 * @param string $level SES, AdES or QES
	 *
	 * @return bool True for SES and AdES; QES only with a qualified certificate.
	 *
	 * @spec openspec/changes/archive/2026-09-29-libresign-signing-provider/tasks.md#task-1.2
	 */
	public function supportsLevel(string $level): bool {
		if ($level === 'SES' || $level === 'AdES') {
			return true;
		}

		// Stored as '1' by the admin settings, like every other switch there.
		return $level === 'QES' && $this->config->getValueString('filinq', 'libresign_qualified', '0') === '1';

	}//end supportsLevel()

	/**
	 * The signed PDF for a completing request, never the original.
	 *
	 * @param string               $documentContent The original bytes (not used: LibreSign signed its own copy)
	 * @param array<string, mixed> $context         level, and externalId (the LibreSign file uuid)
	 *
	 * @return string The signed PDF bytes.
	 *
	 * @throws RuntimeException When the level is not supported, there is no LibreSign request, or it is not signed.
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter) The interface hands every provider the original.
	 *
	 * @spec openspec/changes/archive/2026-09-29-libresign-signing-provider/tasks.md#task-1.2
	 */
	public function produceSignedArtifact(string $documentContent, array $context): string {
		$this->assertLevel(level: (string) ($context['level'] ?? 'SES'));

		$externalId = (string) ($context['externalId'] ?? '');
		if ($externalId === '') {
			throw new RuntimeException('This signing request has no LibreSign request to take the signed file from');
		}

		return $this->downloadSignedDocument(externalId: $externalId);

	}//end produceSignedArtifact()

	/**
	 * Refuse a level the certificate cannot back.
	 *
	 * @param string $level The requested level
	 *
	 * @return void
	 *
	 * @throws RuntimeException When the level is not supported.
	 */
	private function assertLevel(string $level): void {
		if ($this->supportsLevel(level: $level) === false) {
			throw new RuntimeException(
				'LibreSign is not configured to sign at level ' . $level . '; a lower level is never substituted'
			);
		}

	}//end assertLevel()

	/**
	 * The document as LibreSign's NewFile.
	 *
	 * The bytes are the default: LibreSign acts as its service account, which
	 * cannot read a file in the initiator's folder by id. A file id or a path
	 * is for a file the service account can read itself.
	 *
	 * @param string               $documentPath The path, the last resort
	 * @param string               $documentName The name LibreSign stores the copy under
	 * @param array<string, mixed> $options      content or fileId
	 *
	 * @return array<string, mixed> The NewFile.
	 */
	private function toLibreSignFile(string $documentPath, string $documentName, array $options): array {
		if (is_string($options['content'] ?? null) === true && $options['content'] !== '') {
			return ['base64' => base64_encode($options['content']), 'name' => $documentName];
		}

		if ((int) ($options['fileId'] ?? 0) > 0) {
			return ['nodeId' => (int) $options['fileId']];
		}

		return ['path' => $documentPath];

	}//end toLibreSignFile()

	/**
	 * One signer as LibreSign's NewSigner.
	 *
	 * @param array<string, mixed> $signer userId or email, and displayName
	 *
	 * @return array<string, mixed> The NewSigner entry.
	 *
	 * @throws InvalidArgumentException When the signer has neither an account nor an email address.
	 */
	private function toLibreSignSigner(array $signer): array {
		$method = 'email';
		$value = (string) ($signer['email'] ?? '');
		if ((string) ($signer['userId'] ?? '') !== '') {
			$method = 'account';
			$value = (string) $signer['userId'];
		}

		if ($value === '') {
			throw new InvalidArgumentException('LibreSign needs an account or an email address for every signer');
		}

		return [
			'identifyMethods' => [['method' => $method, 'value' => $value, 'requirement' => 'required']],
			'displayName' => (string) ($signer['displayName'] ?? ''),
		];

	}//end toLibreSignSigner()
}//end class
