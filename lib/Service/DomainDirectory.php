<?php

/**
 * Where the list of domains comes from.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 *
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Reads the domains whose folders filinq keeps in step.
 *
 * 🔴 A DOMAIN IS NOT FILINQ'S RECORD. A unit or case domain belongs to the app
 * that owns cases; filinq owns the documents and the folder underneath them. So
 * the directory does not define a domain schema here, it reads whatever register
 * and schema an administrator points it at, and it says so when nobody has
 * pointed it anywhere.
 *
 * 🔑 AN UNCONFIGURED DIRECTORY IS REPORTED, NEVER READ AS "NO DOMAINS". Those
 * two look identical to a caller that only counts rows, and the difference is
 * the whole question: no domains means there is nothing to reconcile, while
 * unconfigured means every domain there is went unreconciled and nobody was
 * told. The nightly job reports the second as a skip with its reason.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction Development Team <dev@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */
class DomainDirectory {

	/**
	 * The app config key naming the register domains live in.
	 *
	 * @var string
	 */
	public const REGISTER_KEY = 'domainFolder_register';

	/**
	 * The app config key naming the schema domains live in.
	 *
	 * @var string
	 */
	public const SCHEMA_KEY = 'domainFolder_schema';

	/**
	 * The app config key naming the user whose storage holds the folders.
	 *
	 * @var string
	 */
	public const OWNER_KEY = 'domainFolder_owner';

	/**
	 * Collaborators.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService.
	 * @param IAppConfig                    $config         Where the register, the schema and the owner are named.
	 * @param LoggerInterface               $logger         Structured logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly IAppConfig $config,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * The user whose storage holds the domain folders.
	 *
	 * @return string The owner, or an empty string when none is named.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function owner(): string {
		return trim($this->config->getValueString('filinq', self::OWNER_KEY, ''));

	}//end owner()

	/**
	 * Every domain the directory is pointed at.
	 *
	 * @return array{configured: bool, reason: string, domains: array<int, array<string, mixed>>} The domains, or why there are none.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function all(): array {
		$register = trim($this->config->getValueString('filinq', self::REGISTER_KEY, ''));
		$schema = trim($this->config->getValueString('filinq', self::SCHEMA_KEY, ''));

		if ($register === '' || $schema === '') {
			return [
				'configured' => false,
				'reason' => 'No domain register and schema are configured, so no folder was reconciled.',
				'domains' => [],
			];
		}

		if ($this->owner() === '') {
			return [
				'configured' => false,
				'reason' => 'No owner is configured for the domain folders, so no folder was reconciled.',
				'domains' => [],
			];
		}

		try {
			$results = $this->objectResolver->resolve()->searchObjects(
				query: ['@self' => ['register' => $register, 'schema' => $schema]]
			);
		} catch (Throwable $e) {
			// 🔴 A FAILED READ IS A SKIP WITH A REASON, NOT AN EMPTY DIRECTORY.
			// An empty directory reads as "nothing to do" and the job reports a
			// clean night while every folder drifted.
			$this->logger->warning(
				'filinq.domain-directory.read-refused',
				['register' => $register, 'schema' => $schema, 'error' => $e->getMessage()]
			);

			return [
				'configured' => false,
				'reason' => 'The domain register could not be read: ' . $e->getMessage(),
				'domains' => [],
			];
		}

		$domains = [];
		foreach ((array)$results as $row) {
			$domain = $this->plain(row: $row);
			if ($domain !== null) {
				$domains[] = $domain;
			}
		}

		return ['configured' => true, 'reason' => '', 'domains' => $domains];

	}//end all()

	/**
	 * One search result as a plain domain array.
	 *
	 * @param mixed $row The row as the object service returned it.
	 *
	 * @return array<string, mixed>|null The domain, or null when the row carries no id.
	 *
	 * @spec exclude Shape adapter over a search result; no behaviour of its own.
	 */
	private function plain(mixed $row): ?array {
		if (is_object($row) === true && method_exists($row, 'jsonSerialize') === true) {
			$row = $row->jsonSerialize();
		}

		if (is_array($row) === false) {
			return null;
		}

		$identifier = trim((string)($row['id'] ?? $row['uuid'] ?? ''));

		if (isset($row['object']) === true && is_array($row['object']) === true) {
			$object = $row['object'];
			if ($identifier === '') {
				$identifier = trim((string)($object['id'] ?? $object['uuid'] ?? ''));
			}

			$row = array_merge($object, ['id' => $identifier]);
		}

		if ($identifier === '') {
			return null;
		}

		$row['id'] = $identifier;

		return $row;

	}//end plain()
}//end class
