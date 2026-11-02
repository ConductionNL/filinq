<?php

/**
 * Domain reference
 *
 * One domain a document record can sit in, as register, schema and id.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * A domain matched on all three of register, schema and id.
 *
 * Matching on fewer would put a record in a domain it only half belongs to,
 * and the domains are what decides who may read it.
 *
 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
 */
final class DomainReference {

	/**
	 * The domain as text, keyed register, schema and id.
	 *
	 * @var array{register: string, schema: string, id: string}
	 */
	private readonly array $parts;

	/**
	 * Read a domain from its array form; missing parts are empty.
	 *
	 * @param array<string, mixed> $domain The domain, as register, schema and id.
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function __construct(array $domain) {
		$this->parts = [
			'register' => (string)($domain['register'] ?? ''),
			'schema'   => (string)($domain['schema'] ?? ''),
			'id'       => (string)($domain['id'] ?? ''),
		];
	}//end __construct()

	/**
	 * Whether all three parts are given.
	 *
	 * @return bool True when register, schema and id are all non-empty.
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function isComplete(): bool {
		return in_array('', $this->parts, true) === false;
	}//end isComplete()

	/**
	 * Whether a record names this domain among its `domains`.
	 *
	 * @param array<string, mixed> $record The record.
	 *
	 * @return bool True when one of its domains matches on all three parts.
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function isNamedBy(array $record): bool {
		$domains = ($record['domains'] ?? []);
		if (is_array($domains) === false) {
			return false;
		}

		foreach ($domains as $candidate) {
			if (is_array($candidate) === true && $this->matches(candidate: $candidate) === true) {
				return true;
			}
		}

		return false;
	}//end isNamedBy()

	/**
	 * Whether one domain entry is this domain.
	 *
	 * @param array<string, mixed> $candidate The entry on the record.
	 *
	 * @return bool True when all three parts match as text.
	 */
	private function matches(array $candidate): bool {
		foreach ($this->parts as $key => $value) {
			if ((string)($candidate[$key] ?? '') !== $value) {
				return false;
			}
		}

		return true;
	}//end matches()
}//end class
