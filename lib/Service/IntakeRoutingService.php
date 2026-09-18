<?php

/**
 * Intake Routing Service
 *
 * Stores what a consuming app declared about inbound documents on one of its
 * record types: which group they go to, and whether somebody has to accept
 * them. Filinq applies the declaration and never invents one: the record type
 * vocabulary belongs to the app that owns the records.
 *
 * A record type with no declaration routes nowhere and needs no acceptance.
 * That is an ordinary answer, not a failure: most types have no routing, and a
 * default invented here would route documents to a group nobody chose.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Reads and writes the routing a consuming app declared per record type.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 */
class IntakeRoutingService {

	/**
	 * The schema holding the declarations.
	 *
	 * @var string
	 */
	public const SCHEMA = 'intakeRoutingRule';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Store one declaration, replacing the app's earlier one for that type.
	 *
	 * @param string $declaringApp The app id making the declaration.
	 * @param string $typeReference The record type, in that app's vocabulary.
	 * @param string $routeTo The group inbound documents go to, or an empty string.
	 * @param bool $requiresAcceptance Whether the group has to accept the document.
	 *
	 * @return array<string, mixed> The stored declaration.
	 *
	 * @throws RuntimeException When the write fails.
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function declare(
		string $declaringApp,
		string $typeReference,
		string $routeTo,
		bool $requiresAcceptance,
	): array {
		if ($declaringApp === '' || $typeReference === '') {
			throw new RuntimeException(
				message: 'A routing declaration names the app making it and the record type it is about.'
			);
		}

		$declaration = [
			'declaringApp' => $declaringApp,
			'typeReference' => $typeReference,
			'routeTo' => $routeTo,
			'requiresAcceptance' => $requiresAcceptance,
		];

		$existing = $this->find(declaringApp: $declaringApp, typeReference: $typeReference);

		try {
			$arguments = [
				'object' => $declaration,
				'register' => IntakeRepository::REGISTER,
				'schema' => self::SCHEMA,
			];
			if ($existing !== null && ($existing['uuid'] ?? '') !== '') {
				$arguments['uuid'] = (string)$existing['uuid'];
			}

			$this->objectResolver->resolve()->saveObject(...$arguments);
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'Could not store the routing declaration: ' . $e->getMessage(),
				code: 0,
				previous: $e
			);
		}

		return $declaration;

	}//end declare()

	/**
	 * The routing that applies to one record type.
	 *
	 * @param string $declaringApp The app that owns the record type.
	 * @param string $typeReference The record type.
	 *
	 * @return array<string, mixed>|null The declaration, or null when there is none.
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function find(string $declaringApp, string $typeReference): ?array {
		if ($declaringApp === '' || $typeReference === '') {
			return null;
		}

		try {
			$results = $this->objectResolver->resolve()->searchObjects(
				query: [
					'@self' => [
						'register' => IntakeRepository::REGISTER,
						'schema' => self::SCHEMA,
					],
					'declaringApp' => $declaringApp,
					'typeReference' => $typeReference,
				]
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[IntakeRoutingService] could not read the routing declarations',
				context: ['file' => __FILE__, 'line' => __LINE__, 'error' => $e->getMessage()]
			);

			return null;
		}

		if (is_array($results) === false || $results === []) {
			return null;
		}

		$row = $results[0];
		if (is_object($row) === true && method_exists($row, 'jsonSerialize') === true) {
			$row = $row->jsonSerialize();
		}

		if (is_array($row) === false) {
			return null;
		}

		$fields = $row;
		if (isset($row['object']) === true && is_array($row['object']) === true) {
			$fields = $row['object'];
		}

		$fields['uuid'] = (string)($fields['uuid'] ?? ($row['uuid'] ?? ($row['@self']['id'] ?? ($row['id'] ?? ''))));

		return $fields;

	}//end find()

	/**
	 * Apply the declaration of one record type to a document being filed.
	 *
	 * @param array<string, mixed> $document The intake document being assigned.
	 * @param string $declaringApp The app that owns the target record type.
	 * @param string $typeReference The target record type.
	 *
	 * @return array<string, mixed> The document, routed or deliberately not.
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function apply(array $document, string $declaringApp, string $typeReference): array {
		$declaration = $this->find(declaringApp: $declaringApp, typeReference: $typeReference);
		if ($declaration === null) {
			$document['routing'] = '';
			$document['acceptance'] = ['required' => false];

			return $document;
		}

		$document['routing'] = (string)($declaration['routeTo'] ?? '');
		$document['acceptance'] = [
			'required' => (bool)($declaration['requiresAcceptance'] ?? false),
			'acceptedBy' => '',
			'acceptedAt' => '',
		];

		return $document;

	}//end apply()
}//end class
