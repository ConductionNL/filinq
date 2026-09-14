<?php

/**
 * Document Finality Rule Service
 *
 * A consuming app declares which states of one of its record types make that
 * type's documents final. Filinq stores the declaration against the type
 * reference and applies it when the state changes, so a besluit freezes
 * because the decision was taken rather than because somebody remembered to
 * press a button.
 *
 * Filinq does not define the record type vocabulary. Case types and result
 * types belong to dossiq, and this service only records what dossiq declared
 * (ADR-022).
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use OCA\Filinq\Exception\DocumentFinalException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Stores and applies the declarations that make a document final.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */
class DocumentFinalityRuleService {

	/**
	 * The OpenRegister register holding Filinq's schemas.
	 *
	 * @var string
	 */
	private const REGISTER = 'filinq';

	/**
	 * The schema holding the declarations.
	 *
	 * @var string
	 */
	private const SCHEMA = 'documentFinalityRule';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService.
	 * @param FinalDocumentService $finalDocuments The finalisation service.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly FinalDocumentService $finalDocuments,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Store a consuming app's declaration for one record type.
	 *
	 * @param string $declaringApp App id of the consuming app, such as dossiq.
	 * @param string $typeReference That app's own reference for the record type.
	 * @param array<int, string> $finalStates The states that make its documents final.
	 * @param string $documentRole Which document of the record the rule covers, or '' for every one.
	 * @param string $reasonTemplate The reason written on the version, or '' to name the transition.
	 * @param string|null $declaredBy The user id storing the declaration.
	 *
	 * @return array<string, mixed> The stored declaration.
	 *
	 * @throws RuntimeException When the declaration is incomplete or cannot be stored.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function declareRule(
		string $declaringApp,
		string $typeReference,
		array $finalStates,
		string $documentRole = '',
		string $reasonTemplate = '',
		?string $declaredBy = null,
	): array {
		if (trim($declaringApp) === '' || trim($typeReference) === '') {
			throw new RuntimeException(
				message: 'A finality declaration names the app that made it and the record type it is about.'
			);
		}

		$states = [];
		foreach ($finalStates as $state) {
			$state = trim((string)$state);
			if ($state !== '') {
				$states[] = $state;
			}
		}

		if ($states === []) {
			throw new RuntimeException(
				message: 'A finality declaration names at least one state that makes a document final.'
			);
		}

		$existing = $this->find(declaringApp: $declaringApp, typeReference: $typeReference, documentRole: $documentRole);

		$record = [
			'declaringApp' => $declaringApp,
			'typeReference' => $typeReference,
			'finalStates' => $states,
			'documentRole' => $documentRole,
			'reasonTemplate' => $reasonTemplate,
			'declaredBy' => (string)$declaredBy,
			'declaredAt' => (new DateTimeImmutable())->format(DATE_ATOM),
		];

		try {
			$objectService = $this->objectResolver->resolve();
			$uuid = (string)($existing['uuid'] ?? '');
			if ($uuid === '') {
				$stored = $objectService->saveObject(
					object: $record,
					register: self::REGISTER,
					schema: self::SCHEMA
				);
			} else {
				$stored = $objectService->saveObject(
					object: $record,
					register: self::REGISTER,
					schema: self::SCHEMA,
					uuid: $uuid
				);
			}
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'Could not store the finality declaration: ' . $e->getMessage(),
				code: 0,
				previous: $e
			);
		}//end try

		return $this->flatten(row: $stored);

	}//end declare()

	/**
	 * Find the declaration for one record type, if a consuming app made one.
	 *
	 * @param string $declaringApp App id of the consuming app.
	 * @param string $typeReference That app's reference for the record type.
	 * @param string $documentRole Which document of the record, or '' for every one.
	 *
	 * @return array<string, mixed>|null The declaration, or null when there is none.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function find(string $declaringApp, string $typeReference, string $documentRole = ''): ?array {
		try {
			$results = $this->objectResolver->resolve()->searchObjects(
				query: [
					'@self' => [
						'register' => self::REGISTER,
						'schema' => self::SCHEMA,
					],
					'declaringApp' => $declaringApp,
					'typeReference' => $typeReference,
				]
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[DocumentFinalityRuleService] could not read the finality declarations',
				context: [
					'file' => __FILE__,
					'line' => __LINE__,
					'declaringApp' => $declaringApp,
					'typeReference' => $typeReference,
					'error' => $e->getMessage(),
				]
			);

			return null;
		}//end try

		if (is_array($results) === false) {
			return null;
		}

		foreach ($results as $result) {
			$record = $this->flatten(row: $result);
			if ((string)($record['documentRole'] ?? '') === $documentRole) {
				return $record;
			}
		}

		return null;

	}//end find()

	/**
	 * Apply the declaration when a consuming app reports a state change.
	 *
	 * Nothing is frozen when no declaration covers the type: a record type with
	 * no declaration changes state and no document changes with it. A document
	 * that is already final is left alone rather than re-finalised, so a
	 * repeated state change is harmless.
	 *
	 * @param string $declaringApp App id of the consuming app.
	 * @param string $typeReference That app's reference for the record type.
	 * @param string $state The state the record has reached.
	 * @param array<int, int> $fileIds The documents of that record.
	 * @param string $documentRole Which document of the record, or '' for every one.
	 *
	 * @return array<int, array<string, mixed>> The versions this call made final, empty when none.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function applyStateChange(
		string $declaringApp,
		string $typeReference,
		string $state,
		array $fileIds,
		string $documentRole = '',
	): array {
		$rule = $this->find(declaringApp: $declaringApp, typeReference: $typeReference, documentRole: $documentRole);
		if ($rule === null) {
			return [];
		}

		$states = $rule['finalStates'];
		if (is_array($states) === false || in_array($state, $states, true) === false) {
			return [];
		}

		$reason = (string)($rule['reasonTemplate'] ?? '');
		if ($reason === '') {
			$reason = sprintf('%s reached the status "%s".', $typeReference, $state);
		}

		$finalised = [];
		foreach ($fileIds as $fileId) {
			$fileId = (int)$fileId;
			if ($fileId <= 0) {
				continue;
			}

			try {
				$finalised[] = $this->finalDocuments->finalise(fileId: $fileId, reason: $reason);
			} catch (DocumentFinalException $e) {
				// Already final. The state change repeated, or two rules cover
				// the same document; either way the first moment is the one the
				// archive needs and it stays.
				$this->logger->debug(
					message: '[DocumentFinalityRuleService] a declared rule found a document already final',
					context: ['file' => __FILE__, 'line' => __LINE__, 'fileId' => $fileId, 'reason' => $e->getMessage()]
				);
			} catch (Throwable $e) {
				$this->logger->error(
					message: '[DocumentFinalityRuleService] could not apply a declared rule to a document',
					context: ['file' => __FILE__, 'line' => __LINE__, 'fileId' => $fileId, 'error' => $e->getMessage()]
				);
			}//end try
		}//end foreach

		return $finalised;

	}//end applyStateChange()

	/**
	 * Read one OpenRegister row into a flat record.
	 *
	 * @param mixed $row The row as OpenRegister returned it.
	 *
	 * @return array<string, mixed> The flat record, including its `uuid`.
	 *
	 * @spec exclude Shape adapter over an OpenRegister response; no behaviour of its own.
	 */
	private function flatten(mixed $row): array {
		$data = $row;
		if (is_object($row) === true && method_exists($row, 'jsonSerialize') === true) {
			$data = $row->jsonSerialize();
		}

		if (is_array($data) === false) {
			return [];
		}

		$fields = $data;
		if (isset($data['object']) === true && is_array($data['object']) === true) {
			$fields = $data['object'];
		}

		$fields['uuid'] = (string)($fields['uuid'] ?? ($data['uuid'] ?? ($data['id'] ?? '')));

		return $fields;

	}//end flatten()
}//end class
