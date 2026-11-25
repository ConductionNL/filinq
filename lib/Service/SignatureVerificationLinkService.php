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
use OCA\Filinq\Kernel\Contracts\SignatureVerificationLinksInterface;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindTarget;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use OCA\OpenRegister\Kernel\Contracts\Value\SaveActor;
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
class SignatureVerificationLinkService implements SignatureVerificationLinksInterface {
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
	 * Thirty-two zero hex digits: the nil value, not a credential.
	 *
	 * @var string
	 */
	private const ZERO_HEX_32 = '00000000000000000000000000000000';

	/**
	 * The demo record's token, which is never a public handle.
	 *
	 * @var string
	 */
	public const NIL_TOKEN = self::ZERO_HEX_32;

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService.
	 * @param SigningVerificationService    $verifier       Checks the signatures in the signed bytes.
	 * @param SystemContextSeam|null        $seam           Reads the record across organisations for a visitor with no account.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly SigningVerificationService $verifier,
		private readonly ?SystemContextSeam $seam=null,
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
	 * A new random token: 128 bits, as 32 lowercase hex characters.
	 *
	 * Signing takes one before the document is signed, so the QR code that
	 * links to the record can be part of the signed bytes.
	 *
	 * @return string The token.
	 *
	 * @spec openspec/changes/signature-verification-portal/tasks.md#task-2.4
	 */
	public function newToken(): string {
		do {
			$token = bin2hex(random_bytes(16));
		} while (self::isWellFormed(token: $token) === false);

		return $token;
	}//end newToken()

	/**
	 * Store the outcome of a signed document's signatures under a token.
	 *
	 * @param string      $signedBytes The signed document as it was stored.
	 * @param string      $fileRef     The stored signed-artifact reference.
	 * @param string      $fileName    The file's name, the only thing about it the public page shows.
	 * @param string|null $waarmerkRef A seal on the same document, when there is one.
	 * @param string|null $token       The token taken with newToken() before signing; a new one when null.
	 *
	 * @return array<string, mixed> The stored record, with its token.
	 *
	 * @throws RuntimeException When the token is malformed or the record cannot be stored.
	 *
	 * @spec openspec/changes/signature-verification-portal/tasks.md#task-2.1
	 * @spec openspec/changes/signature-verification-portal/tasks.md#task-2.4
	 */
	public function mint(string $signedBytes, string $fileRef, string $fileName, ?string $waarmerkRef = null, ?string $token = null): array {
		$record = [
			'token' => $this->tokenFor(token: $token),
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

		return array_merge($record, $this->storedFields(row: $this->store(record: $record)), ['token' => $record['token']]);
	}//end mint()

	/**
	 * The token a record is minted under: the one given, checked, or a new one.
	 *
	 * @param string|null $token The token taken before signing, or null.
	 *
	 * @return string The token.
	 *
	 * @throws RuntimeException When the given token is malformed.
	 */
	private function tokenFor(?string $token): string {
		if ($token === null) {
			return $this->newToken();
		}

		if (self::isWellFormed(token: $token) === false) {
			throw new RuntimeException('A verification record needs a token of 32 lowercase hex characters');
		}

		return $token;
	}//end tokenFor()

	/**
	 * Save a record as a system-owned object.
	 *
	 * @param array<string, mixed> $record The record.
	 *
	 * @return mixed The stored row.
	 *
	 * @throws RuntimeException When the record cannot be stored.
	 */
	private function store(array $record): mixed {
		try {
			return $this->objectResolver->resolve()->saveObject(
				object: $record,
				options: new ObjectSaveOptions(
					target: new ObjectFindTarget(register: self::REGISTER, schema: self::SCHEMA),
					actor: new SaveActor(unowned: true)
				),
				_rbac: false
			);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'Could not store the signature verification record: ' . $e->getMessage(), code: 0, previous: $e);
		}
	}//end store()

	/**
	 * The verdict for a token that resolves to nothing, in the shape of every other verdict.
	 *
	 * @return array<string, mixed> The neutral `unknown` verdict.
	 *
	 * @spec openspec/changes/signature-verification-portal/tasks.md#task-2.3
	 */
	public static function unknownVerdict(): array {
		return SigningVerificationService::unknownVerdict();
	}//end unknownVerdict()

	/**
	 * The public verdict for a token: the verdict of the record it names, or
	 * the `unknown` verdict when it names none.
	 *
	 * @param string $token The token from the verification link.
	 *
	 * @return array<string, mixed> The verdict.
	 *
	 * @throws RuntimeException When the records cannot be read.
	 *
	 * @spec openspec/changes/signature-verification-portal/tasks.md#task-2.3
	 */
	public function verdictFor(string $token): array {
		$record = $this->findByToken(token: $token);
		if ($record === null) {
			return self::unknownVerdict();
		}

		return $this->verifier->verifyByRecord(record: $record);
	}//end verdictFor()

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

		foreach ($this->rowsFor(token: $token) as $row) {
			$fields = $this->storedFields(row: $row);
			if (hash_equals($token, (string)($fields['token'] ?? '')) === true) {
				return $fields;
			}
		}

		return null;
	}//end findByToken()

	/**
	 * The rows a token search returns; none when the search answers with something other than a list.
	 *
	 * @param string $token A well-formed token.
	 *
	 * @return array<int|string, mixed> The rows.
	 *
	 * @throws RuntimeException When the records cannot be read.
	 */
	private function rowsFor(string $token): array {
		$search = fn (): mixed => $this->objectResolver->slugSearch(_rbac: false)->search(
			register: self::REGISTER,
			schema: self::SCHEMA,
			filters: ['token' => $token]
		);

		try {
			// The visitor has no account and so no organisation, and OpenRegister's
			// organisation filter would hide every record from them. The token is
			// the capability (REQ-DDSVP-001), so this one exact-token lookup runs as
			// the system, through the logged seam.
			if ($this->seam !== null) {
				$rows = $this->seam->run(
					objectService: $this->objectResolver->resolve(),
					reason: 'a public verification link names its record by token, whatever the visitor\'s organisation',
					operation: $search
				);
			} else {
				$rows = $search();
			}
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'Could not read the signature verification records: ' . $e->getMessage(), code: 0, previous: $e);
		}

		if (is_array($rows) === false) {
			return [];
		}

		return $rows;
	}//end rowsFor()
}//end class
