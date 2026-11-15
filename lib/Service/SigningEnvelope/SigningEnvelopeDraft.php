<?php

/**
 * Signing Envelope Draft
 *
 * What the caller asked for, checked and shaped: the documents, the
 * envelope's uuid, the signer roster, the fields every member request
 * shares and the envelope record to store once the members exist.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\SigningEnvelope
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\SigningEnvelope;

use OCA\Filinq\Kernel\Contracts\SigningEnvelopeServiceInterface;
use RuntimeException;
use Symfony\Component\Uid\UuidV4;

/**
 * A new envelope before it is stored. Built by SigningEnvelopeService::create().
 *
 * @category Service
 * @package  OCA\Filinq\Service\SigningEnvelope
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
 */
final class SigningEnvelopeDraft {

	/**
	 * The optional fields every member request shares when the caller set them.
	 *
	 * @var list<string>
	 */
	private const SHARED_FIELDS = ['signatureLevel', 'signingMode', 'provider', 'requiredAssurance', 'deadline'];

	/**
	 * The checked documents, in the caller's order.
	 *
	 * @var list<array{documentFileId: string, documentName: string}>
	 */
	public readonly array $documents;

	/**
	 * The envelope's uuid, chosen first so every member carries it from its first save.
	 *
	 * @var string
	 */
	public readonly string $uuid;

	/**
	 * The signer roster, each entry carrying the envelope's uuid.
	 *
	 * @var list<array>
	 */
	public readonly array $signers;

	/**
	 * Constructor: check the documents, choose the uuid, build the roster.
	 *
	 * @param array $input title, documents [{documentFileId, documentName}], signers, signatureLevel, signingMode, provider, requiredAssurance
	 *
	 * @return void
	 *
	 * @throws RuntimeException 400 when it is not two to MAX_DOCUMENTS documents with a file id each
	 */
	public function __construct(
		private readonly array $input,
	) {
		$this->documents = $this->checkedDocuments(value: ($input['documents'] ?? null));
		$this->uuid = (new UuidV4())->toRfc4122();
		$this->signers = $this->roster();

	}//end __construct()

	/**
	 * What every member request shares: the roster, the envelope and the optional fields that are set.
	 *
	 * @return array<string, mixed>
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
	 */
	public function shared(): array {
		$common = ['signers' => $this->signers, 'envelopeRef' => $this->uuid];
		foreach (self::SHARED_FIELDS as $key) {
			if (isset($this->input[$key]) === true && $this->input[$key] !== '') {
				$common[$key] = $this->input[$key];
			}
		}

		return $common;

	}//end shared()

	/**
	 * The envelope record to store once every member exists.
	 *
	 * @param list<string> $requestRefs The member request ids, in document order
	 * @param string       $userId      The initiator
	 *
	 * @return array<string, mixed>
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
	 */
	public function record(array $requestRefs, string $userId): array {
		return [
			'title' => mb_substr($this->title(), 0, 255),
			'requestRefs' => $requestRefs,
			'initiatorUserId' => $userId,
			'signerUserIds' => $this->signerUserIds(),
			'documentCount' => count($requestRefs),
			'status' => 'pending',
			'createdAt' => gmdate(DATE_ATOM),
		];

	}//end record()

	/**
	 * The signers the caller sent, each carrying the envelope's uuid.
	 *
	 * @return list<array>
	 */
	private function roster(): array {
		$signers = [];
		foreach ((array) ($this->input['signers'] ?? []) as $signer) {
			$signers[] = ['envelopeRef' => $this->uuid] + (array) $signer;
		}

		return $signers;

	}//end roster()

	/**
	 * The caller's title, else the first document's name.
	 *
	 * @return string
	 */
	private function title(): string {
		$title = trim((string) ($this->input['title'] ?? ''));
		if ($title === '') {
			return $this->documents[0]['documentName'];
		}

		return $title;

	}//end title()

	/**
	 * The Nextcloud users among the signers, each once.
	 *
	 * @return list<string>
	 */
	private function signerUserIds(): array {
		$uids = [];
		foreach ($this->signers as $signer) {
			$uid = trim((string) ($signer['userId'] ?? ''));
			if ($uid !== '') {
				$uids[$uid] = true;
			}
		}

		return array_keys($uids);

	}//end signerUserIds()

	/**
	 * Validate the documents list.
	 *
	 * @param mixed $value The `documents` the caller sent
	 *
	 * @return list<array{documentFileId: string, documentName: string}>
	 *
	 * @throws RuntimeException 400 when it is not two to MAX_DOCUMENTS documents with a file id each
	 */
	private function checkedDocuments(mixed $value): array {
		$this->guardDocumentCount(value: $value);

		$documents = [];
		$seen = [];
		foreach (array_values($value) as $position => $document) {
			$fileId = trim((string) (((array) $document)['documentFileId'] ?? ''));
			if ($fileId === '' || isset($seen[$fileId]) === true) {
				throw new RuntimeException(message: 'Document ' . ($position + 1) . ' needs a file id of its own', code: 400);
			}

			$seen[$fileId] = true;
			$documents[] = ['documentFileId' => $fileId, 'documentName' => $this->nameOf(document: $document, fileId: $fileId)];
		}

		return $documents;

	}//end checkedDocuments()

	/**
	 * Refuse a documents value that is not a list of two to MAX_DOCUMENTS entries.
	 *
	 * @param mixed $value The `documents` the caller sent
	 *
	 * @return void
	 *
	 * @throws RuntimeException 400 when it is not two to MAX_DOCUMENTS documents
	 */
	private function guardDocumentCount(mixed $value): void {
		$max = SigningEnvelopeServiceInterface::MAX_DOCUMENTS;
		if (is_array($value) === false || count($value) < 2 || count($value) > $max) {
			throw new RuntimeException(message: 'An envelope holds 2 to ' . $max . ' documents', code: 400);
		}

	}//end guardDocumentCount()

	/**
	 * A document's name, else its file id.
	 *
	 * @param mixed  $document The document the caller sent
	 * @param string $fileId   Its checked file id
	 *
	 * @return string
	 */
	private function nameOf(mixed $document, string $fileId): string {
		$name = trim((string) (((array) $document)['documentName'] ?? ''));
		if ($name === '') {
			return $fileId;
		}

		return $name;

	}//end nameOf()
}//end class
