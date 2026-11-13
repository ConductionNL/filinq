<?php

/**
 * Signature Verification Link Service
 *
 * Mints and finds the `signatureVerification` records the public
 * verification page reads: the outcome of a signed document's signatures,
 * kept under a random token so a verifier needs no account and no file.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/signature-verification-portal/tasks.md#task-2.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use DateTimeInterface;
use RuntimeException;
use Throwable;

/**
 * Mints and finds signature verification records by token.
 *
 * Both calls pass `_rbac: false`: the schema grants the API to signing admins
 * only, the record is written by the server at signing completion with the
 * system as owner, and it is read for an anonymous verifier who holds only the
 * token. A record holds the outcome, never the document and never the key.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signature-verification-portal/tasks.md#task-2.1
 */
class SignatureVerificationLinkService {
	use ReadsStoredObjectRows;

	/**
	 * The register the records live in.
	 *
	 * @var string
	 */
	public const REGISTER = 'filinq';

	/**
	 * The schema of a record.
	 *
	 * @var string
	 */
	public const SCHEMA = 'signatureVerification';

	/**
	 * The demo record's token, which is never a public handle.
	 *
	 * @var string
	 */
	public const NIL_TOKEN = '00000000000000000000000000000000';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService.
	 * @param SigningVerificationService    $verifier       Checks the signatures in the signed bytes.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly SigningVerificationService $verifier,
	) {

	}//end __construct()

	/**
	 * Whether a string can be a token at all: 32 lowercase hex characters, and not the nil token.
	 *
	 * @param string $token The candidate.
	 *
	 * @return bool True for a well-formed, non-nil token.
	 *
	 * @spec openspec/changes/signature-verification-portal/tasks.md#task-2.1
	 */
	public static function isWellFormed(string $token): bool {
		return preg_match('/^[0-9a-f]{32}$/', $token) === 1 && $token !== self::NIL_TOKEN;
	}//end isWellFormed()

	/**
	 * Store the outcome of a signed document's signatures under a new random token.
	 *
	 * @param string      $signedBytes The signed document as it was stored.
	 * @param string      $fileRef     The stored signed-artifact reference.
	 * @param string      $fileName    The file's name, the only thing about it the public page shows.
	 * @param string|null $waarmerkRef A seal on the same document, when there is one.
	 *
	 * @return array<string, mixed> The stored record, with its token.
	 *
	 * @throws RuntimeException When the record cannot be stored.
	 *
	 * @spec openspec/changes/signature-verification-portal/tasks.md#task-2.1
	 */
	public function mint(string $signedBytes, string $fileRef, string $fileName, ?string $waarmerkRef = null): array {
		$record = [
			'token' => bin2hex(random_bytes(16)),
			'fileRef' => $fileRef,
			'fileName' => $fileName,
			'contentHash' => hash('sha256', $signedBytes),
			'signatures' => $this->verifier->summarise(pdfContent: $signedBytes),
			'revoked' => false,
			'createdAt' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
		];
		if ($waarmerkRef !== null && $waarmerkRef !== '') {
			$record['waarmerkRef'] = $waarmerkRef;
		}

		try {
			$stored = $this->objectResolver->resolve()->saveObject(
				object: $record,
				register: self::REGISTER,
				schema: self::SCHEMA,
				_rbac: false,
				_unowned: true
			);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'Could not store the signature verification record: ' . $e->getMessage(), code: 0, previous: $e);
		}

		return array_merge($record, $this->storedFields(row: $stored), ['token' => $record['token']]);
	}//end mint()

	/**
	 * The record a token names.
	 *
	 * A malformed or nil token finds nothing without a read, and a row is
	 * returned only when its token matches exactly, whatever the search did
	 * with the filter.
	 *
	 * @param string $token The token from the verification link.
	 *
	 * @return array<string, mixed>|null The record, or null when there is none.
	 *
	 * @throws RuntimeException When the records cannot be read, as opposed to finding nothing.
	 *
	 * @spec openspec/changes/signature-verification-portal/tasks.md#task-2.1
	 */
	public function findByToken(string $token): ?array {
		if (self::isWellFormed(token: $token) === false) {
			return null;
		}

		try {
			$rows = $this->objectResolver->resolve()->searchObjectsBySlug(
				registerSlug: self::REGISTER,
				schemaSlug: self::SCHEMA,
				filters: ['token' => $token],
				_rbac: false
			);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'Could not read the signature verification records: ' . $e->getMessage(), code: 0, previous: $e);
		}

		if (is_array($rows) === false) {
			return null;
		}

		foreach ($rows as $row) {
			$fields = $this->storedFields(row: $row);
			if (hash_equals($token, (string)($fields['token'] ?? '')) === true) {
				return $fields;
			}
		}

		return null;
	}//end findByToken()
}//end class
