<?php

/**
 * Document Domain Service
 *
 * A document has domains, not copies. Three case types reading one advies read
 * one record with one version history, because copying is how three versions of
 * one advies appear and how two of them go stale.
 *
 * Access is the union of what those domains allow, and OpenRegister evaluates
 * it. Nothing here recomputes permissions: a second implementation of an access
 * rule is a second answer to the same question, and the two drift.
 *
 * Removing the last domain does NOT delete the record. It leaves it with its
 * creator, on their own documents list. Silent deletion on unlink is how a
 * record disappears with nobody having decided to delete it.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Links and unlinks the domains of a document record, and lists them.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */
class DocumentDomainService {

	/**
	 * Constructor.
	 *
	 * @param FinalDocumentRepository $repository The document record store.
	 * @param IUserSession $userSession The current session.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly FinalDocumentRepository $repository,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Link one document record to one domain.
	 *
	 * Linking the same domain twice changes nothing: a list of domains is a
	 * set, and a duplicate would make the record look like it serves four
	 * domains when it serves three.
	 *
	 * @param string $uuid The document record.
	 * @param array<string, mixed> $domain The domain, as register, schema and id.
	 *
	 * @return array<string, mixed> The record, with its domains.
	 *
	 * @throws RuntimeException When the record does not exist or the domain is incomplete.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function link(string $uuid, array $domain): array {
		$domain = $this->requireDomain(domain: $domain);
		$record = $this->requireRecord(uuid: $uuid);

		$domains = $this->domainsOf(record: $record);
		foreach ($domains as $existing) {
			if ($this->same(left: $existing, right: $domain) === true) {
				return $record;
			}
		}

		$domains[] = $domain;
		$record['domains'] = $domains;
		if ((string)($record['createdByUser'] ?? '') === '') {
			$record['createdByUser'] = $this->currentUserId();
		}

		return $this->repository->save(record: $record, uuid: $uuid);

	}//end link()

	/**
	 * Unlink one domain from one document record.
	 *
	 * @param string $uuid The document record.
	 * @param array<string, mixed> $domain The domain to remove.
	 *
	 * @return array<string, mixed> The record, which still exists.
	 *
	 * @throws RuntimeException When the record does not exist or the domain is incomplete.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function unlink(string $uuid, array $domain): array {
		$domain = $this->requireDomain(domain: $domain);
		$record = $this->requireRecord(uuid: $uuid);

		$remaining = [];
		foreach ($this->domainsOf(record: $record) as $existing) {
			if ($this->same(left: $existing, right: $domain) === false) {
				$remaining[] = $existing;
			}
		}

		$record['domains'] = $remaining;
		if ($remaining === []) {
			// The record stays. It belongs to whoever made it, and it is on
			// their own documents list, so an unlink never makes a document
			// disappear without somebody having decided to delete it.
			$owner = (string)($record['createdByUser'] ?? '');
			if ($owner === '') {
				$record['createdByUser'] = $this->currentUserId();
			}

			$this->logger->info(
				message: '[DocumentDomainService] the last domain link was removed; the record stays with its creator',
				context: ['file' => __FILE__, 'line' => __LINE__, 'uuid' => $uuid, 'owner' => $record['createdByUser']]
			);
		}

		return $this->repository->save(record: $record, uuid: $uuid);

	}//end unlink()

	/**
	 * The document records one person created.
	 *
	 * @param string $userId The user id, or an empty string for the current user.
	 *
	 * @return array<int, array<string, mixed>> Their records.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function listMine(string $userId = ''): array {
		if ($userId === '') {
			$userId = $this->currentUserId();
		}

		if ($userId === '') {
			return [];
		}

		return $this->repository->findByCreator(userId: $userId);

	}//end listMine()

	/**
	 * The domains a record carries.
	 *
	 * @param array<string, mixed> $record The record.
	 *
	 * @return array<int, array<string, mixed>> The domains.
	 *
	 * @spec exclude Shape accessor with no behaviour of its own.
	 */
	private function domainsOf(array $record): array {
		if (isset($record['domains']) === true && is_array($record['domains']) === true) {
			return array_values($record['domains']);
		}

		return [];

	}//end domainsOf()

	/**
	 * Read one domain reference, or refuse.
	 *
	 * @param array<string, mixed> $domain The domain as it came in.
	 *
	 * @return array<string, string> The domain, as register, schema and id.
	 *
	 * @throws RuntimeException When it does not name all three.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	private function requireDomain(array $domain): array {
		$reference = [
			'register' => trim((string)($domain['register'] ?? '')),
			'schema' => trim((string)($domain['schema'] ?? '')),
			'id' => trim((string)($domain['id'] ?? '')),
		];

		foreach ($reference as $value) {
			if ($value === '') {
				throw new RuntimeException(
					message: 'A domain names the register, the schema and the id of the record it is.'
				);
			}
		}

		return $reference;

	}//end requireDomain()

	/**
	 * Read one document record, or refuse.
	 *
	 * @param string $uuid The record.
	 *
	 * @return array<string, mixed> The record.
	 *
	 * @throws RuntimeException When there is no such record.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	private function requireRecord(string $uuid): array {
		$record = $this->repository->findByUuid(uuid: $uuid);
		if ($record === null) {
			throw new RuntimeException(message: 'There is no document record with that id.');
		}

		return $record;

	}//end requireRecord()

	/**
	 * Whether two domain references are the same domain.
	 *
	 * @param array<string, mixed> $left One reference.
	 * @param array<string, mixed> $right The other.
	 *
	 * @return bool True when they point at the same record.
	 *
	 * @spec exclude Comparison helper.
	 */
	private function same(array $left, array $right): bool {
		foreach (['register', 'schema', 'id'] as $key) {
			if ((string)($left[$key] ?? '') !== (string)($right[$key] ?? '')) {
				return false;
			}
		}

		return true;

	}//end same()

	/**
	 * The user id of the person at the keyboard.
	 *
	 * @return string The user id, or an empty string when there is no session.
	 *
	 * @spec exclude Session accessor with no behaviour of its own.
	 */
	private function currentUserId(): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return '';
		}

		return $user->getUID();

	}//end currentUserId()
}//end class
