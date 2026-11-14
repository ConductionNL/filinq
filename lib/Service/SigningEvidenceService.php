<?php

/**
 * Signing Evidence Service
 *
 * One sealed evidence document for a finished signing transaction.
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

use OCA\Filinq\Exception\WaarmerkRefusedException;
use OCA\Filinq\Service\Waarmerk\SealedArtifact;
use OCA\Filinq\Service\Waarmerk\SealedFileWriter;
use OCP\Files\File;
use OCP\Files\Folder;
use Throwable;

/**
 * The evidence PDF/A of a signing request in a terminal state, sealed with
 * `sealType: signing-evidence` (REQ-DDWMK-006).
 *
 * It lists the document title and checksums, every signer (name, method,
 * assurance level, outcome) and every audited step with its moment. It
 * carries no document content beyond the title and the checksums. A request
 * that is still open is refused with 409, naming the open signers.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-completed-signing-transaction-yields-one-sealed-evidence-document-req-ddwmk-006
 */
class SigningEvidenceService {
	use ReadsStoredObjectRows;

	public const TERMINAL = ['COMPLETED', 'DECLINED', 'CANCELLED', 'EXPIRED'];

	private const TEMPLATE = __DIR__ . '/../Resources/templates/waarmerk/signing_evidence.twig';

	/**
	 * Constructor.
	 *
	 * @param SigningService $signing Reads the request, scoped to the caller.
	 * @param SigningAuditService $audit The request's audit trail.
	 * @param SettingsService $settings OpenRegister's ObjectService.
	 * @param OpenRegisterResolver $bindings The signerRecord binding.
	 * @param ReadableFileResolver $files The caller's access to the document.
	 * @param PdfService $pdfService Renders the evidence document.
	 * @param WaarmerkService $waarmerk Seals it.
	 * @param SealedFileWriter $writer Writes a copy next to the document.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SigningService $signing,
		private readonly SigningAuditService $audit,
		private readonly SettingsService $settings,
		private readonly OpenRegisterResolver $bindings,
		private readonly ReadableFileResolver $files,
		private readonly PdfService $pdfService,
		private readonly WaarmerkService $waarmerk,
		private readonly SealedFileWriter $writer,
	) {

	}//end __construct()

	/**
	 * The sealed evidence PDF, for download.
	 *
	 * @param string $requestId The signing request id.
	 * @param string $callerScope The caller's uid, or '' for an admin (unscoped).
	 * @param string $userId The caller.
	 *
	 * @return array{pdf: string, fileName: string, waarmerk: array<string, mixed>}
	 *
	 * @throws WaarmerkRefusedException 404 unknown or not the caller's, 409 still open.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-completed-signing-transaction-yields-one-sealed-evidence-document-req-ddwmk-006
	 */
	public function download(string $requestId, string $callerScope, string $userId): array {
		$sealed = $this->sealed(requestId: $requestId, callerScope: $callerScope, userId: $userId);
		$artifact = $sealed['artifact'];
		$name = $sealed['name'];

		return [
			'pdf' => $artifact->pdf,
			'fileName' => $name,
			'waarmerk' => $this->waarmerk->record(artifact: $artifact, documentFileId: 0),
		];

	}//end download()

	/**
	 * Write the sealed evidence PDF next to the signed document ("Kopie naar dossier").
	 *
	 * @param string $requestId The signing request id.
	 * @param string $callerScope The caller's uid, or '' for an admin (unscoped).
	 * @param string $userId The caller.
	 *
	 * @return array{waarmerk: array<string, mixed>, fileId: int, fileName: string}
	 *
	 * @throws WaarmerkRefusedException 404 unknown or the document is not readable, 409 still open.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-completed-signing-transaction-yields-one-sealed-evidence-document-req-ddwmk-006
	 */
	public function copyToDossier(string $requestId, string $callerScope, string $userId): array {
		$sealed = $this->sealed(requestId: $requestId, callerScope: $callerScope, userId: $userId);
		$artifact = $sealed['artifact'];
		$name = $sealed['name'];
		$document = $sealed['document'];

		$folder = null;
		if ($document !== null) {
			$folder = $document->getParent();
		}

		if (($folder instanceof Folder) === false) {
			throw new WaarmerkRefusedException(message: 'The signed document is not in a folder you can write to', status: 404);
		}

		return $this->writer->write(folder: $folder, name: $name, artifact: $artifact);

	}//end copyToDossier()

	/**
	 * What the evidence document states.
	 *
	 * @param array<string, mixed> $request The signing request.
	 * @param array<int, array<string, mixed>> $signers Its signer records.
	 * @param array<int, array<string, mixed>> $trail Its audit trail.
	 * @param File|null $document The signed document, when readable.
	 *
	 * @return array<string, mixed>
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-completed-signing-transaction-yields-one-sealed-evidence-document-req-ddwmk-006
	 */
	public function evidenceData(array $request, array $signers, array $trail, ?File $document): array {
		$after = null;
		if ($document !== null) {
			try {
				$after = hash('sha256', $document->getContent());
			} catch (Throwable) {
				$after = null;
			}
		}

		return [
			'requestId' => (string) ($request['id'] ?? $request['uuid'] ?? ''),
			'documentName' => (string) ($request['documentName'] ?? ''),
			'status' => (string) ($request['status'] ?? ''),
			'signatureLevel' => (string) ($request['signatureLevel'] ?? ''),
			'provider' => (string) ($request['provider'] ?? ''),
			'signingMode' => (string) ($request['signingMode'] ?? ''),
			'hashBefore' => $this->recordedHashBefore(trail: $trail),
			'hashAfter' => $after,
			'signedVersionTag' => $this->versionTag(ref: (string) ($request['signedDocumentRef'] ?? '')),
			'signers' => array_map(fn (array $signer): array => $this->signerRow(signer: $signer), $signers),
			'steps' => array_map(fn (array $entry): array => $this->stepRow(entry: $entry), $trail),
		];

	}//end evidenceData()

	/**
	 * Read, check and seal the evidence document; nothing is stored yet.
	 *
	 * @param string $requestId The signing request id.
	 * @param string $callerScope The caller's uid, or '' for an admin.
	 * @param string $userId The caller.
	 *
	 * @return array{artifact: SealedArtifact, name: string, document: File|null}
	 *
	 * @throws WaarmerkRefusedException 404 or 409.
	 */
	private function sealed(string $requestId, string $callerScope, string $userId): array {
		$request = $this->signing->getRequest(requestId: $requestId, callerUserId: $callerScope);
		if ($request === null) {
			throw new WaarmerkRefusedException(message: 'Signing request not found', status: 404);
		}

		$signers = $this->signers(request: $request);
		if (in_array((string) ($request['status'] ?? ''), self::TERMINAL, true) === false) {
			$open = [];
			foreach ($signers as $signer) {
				if (in_array((string) ($signer['status'] ?? 'PENDING'), ['SIGNED', 'DECLINED'], true) === false) {
					$open[] = (string) ($signer['displayName'] ?? $signer['email'] ?? $signer['userId'] ?? '');
				}
			}

			throw new WaarmerkRefusedException(
				message: 'The signing request is still open; the evidence document exists once every signer has acted',
				status: 409,
				details: ['openSigners' => $open]
			);
		}

		$document = $this->files->node(fileId: (int) ($request['documentFileId'] ?? 0));
		if (($document instanceof File) === false) {
			$document = null;
		}

		$trail = $this->audit->getAuditTrail(signingRequestId: $requestId);
		$evidence = $this->evidenceData(request: $request, signers: $signers, trail: $trail, document: $document);
		$pdf = $this->pdfService->renderPdf(
			templateContent: (string) file_get_contents(self::TEMPLATE),
			data: ['evidence' => $evidence],
			options: ['pdfa' => true, 'title' => 'Bewijsdocument ondertekening']
		);

		$name = SealedFileWriter::siblingName(sourceName: (string) ($request['documentName'] ?? 'document'), suffix: 'bewijsdocument');
		$artifact = $this->waarmerk->prepare(pdf: $pdf, documentName: $name, userId: $userId, sealType: 'signing-evidence');

		return ['artifact' => $artifact, 'name' => $name, 'document' => $document];

	}//end sealed()

	/**
	 * The request's signer records.
	 *
	 * @param array<string, mixed> $request The signing request.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function signers(array $request): array {
		$objects = $this->settings->getObjectService();
		if ($objects === null) {
			throw new WaarmerkRefusedException(message: 'OpenRegister is not available', status: 503);
		}

		['register' => $register, 'schema' => $schema] = $this->bindings->getSignerRecordRegisterAndSchema();
		$signers = [];
		foreach ((array) ($request['signerIds'] ?? []) as $signerId) {
			$object = $objects->find(id: (string) $signerId, register: $register, schema: $schema, _rbac: false);
			if ($object !== null) {
				$signers[] = $this->storedFields(row: $object);
			}
		}

		return $signers;

	}//end signers()

	/**
	 * One signer line: name, method, assurance, outcome. No IP address, no signature data.
	 *
	 * @param array<string, mixed> $signer The signer record.
	 *
	 * @return array<string, string>
	 */
	private function signerRow(array $signer): array {
		$method = (string) ($signer['identityEvidence']['method'] ?? $signer['actingIdentity']['method'] ?? '');

		return [
			'name' => (string) ($signer['displayName'] ?? $signer['email'] ?? ''),
			'role' => (string) ($signer['role'] ?? 'signer'),
			'method' => $method,
			'assurance' => (string) ($signer['signatureAssurance'] ?? $signer['identityEvidence']['assurance'] ?? ''),
			'status' => (string) ($signer['status'] ?? ''),
			'signedAt' => (string) ($signer['signedAt'] ?? ''),
		];

	}//end signerRow()

	/**
	 * One audited step: what, who, when.
	 *
	 * @param array<string, mixed> $entry The audit trail entry.
	 *
	 * @return array<string, string>
	 */
	private function stepRow(array $entry): array {
		$action = (string) ($entry['action'] ?? '');
		$dot = strrpos($action, '.');
		if ($dot !== false) {
			$action = substr($action, ($dot + 1));
		}

		return [
			'action' => $action,
			'actor' => (string) ($entry['changed']['actorDisplayName'] ?? $entry['changed']['actorUserId'] ?? ''),
			'at' => (string) ($entry['created'] ?? ''),
		];

	}//end stepRow()

	/**
	 * The pre-signing checksum, when the CREATED step recorded one.
	 *
	 * @param array<int, array<string, mixed>> $trail The audit trail.
	 *
	 * @return string|null
	 */
	private function recordedHashBefore(array $trail): ?string {
		foreach ($trail as $entry) {
			$hash = (string) ($entry['changed']['extra']['documentHash'] ?? '');
			if (preg_match('/^[0-9a-f]{64}$/', $hash) === 1) {
				return $hash;
			}
		}

		return null;

	}//end recordedHashBefore()

	/**
	 * The content tag of the signed version, from `<fileId>:signed:<tag>`.
	 *
	 * @param string $ref The signed document reference.
	 *
	 * @return string|null
	 */
	private function versionTag(string $ref): ?string {
		if (preg_match('/^\d+:signed:([0-9a-f]{16})$/', $ref, $match) !== 1) {
			return null;
		}

		return $match[1];

	}//end versionTag()
}//end class
