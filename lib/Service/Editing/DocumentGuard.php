<?php

/**
 * Document Guard
 *
 * The standing refusals for agent-reachable document operations. Each one is a
 * distinct, named condition rather than a shared "not allowed", because an
 * agent that cannot tell WHY it was refused will retry the same call.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Editing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/document-editing-tools/tasks.md#task-2-6
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Editing;

use OCA\DocuDesk\Service\DocumentObjectServiceResolver;
use OCP\Files\File;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Enforces the refusals that bound what an agent may do to a document.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-documents-under-signature-or-produced-by-anonymisation-are-not-editable
 */
class DocumentGuard {

	/**
	 * The one signing-request status under which the underlying document is
	 * still editable, because the process it belonged to was abandoned.
	 *
	 * @var string
	 */
	private const CANCELLED = 'cancelled';

	/**
	 * The OpenRegister register holding DocuDesk's document schemas.
	 *
	 * @var string
	 */
	private const REGISTER = 'document';

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
	 * Refuse to edit a file that is under a live signature process.
	 *
	 * Editing the artefact behind a signature invalidates what the signature
	 * attests to. This is checked before the lock is taken, so a refusal never
	 * disturbs a file it is about to decline to touch.
	 *
	 * FAILS CLOSED: if OpenRegister cannot be reached, the answer is "refuse",
	 * not "allow". An unreachable register is exactly when an unnoticed edit to
	 * a document under signature is most likely.
	 *
	 * @param File $file The file to check.
	 *
	 * @return string|null A refusal message, or null when the file is free to edit.
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-documents-under-signature-or-produced-by-anonymisation-are-not-editable
	 */
	public function signatureRefusal(File $file): ?string {
		$matches = $this->query(
			schema: 'signingRequest',
			filter: ['documentFileId' => (string)$file->getId()]
		);

		if ($matches === null) {
			return 'Could not verify whether this document is under signature '
				. '(the document register is unreachable), so it is not edited.';
		}

		foreach ($matches as $match) {
			$status = strtolower((string)($this->field(row: $match, key: 'status') ?? ''));
			if ($status === self::CANCELLED) {
				continue;
			}

			// An unreadable status is still a signing request. "I could not tell"
			// is not "it is fine" when the question is whether a signature
			// depends on this document.
			$reported = $status;
			if ($reported === '') {
				$reported = 'unknown';
			}

			return sprintf(
				'This document is part of a signing request (status "%s") and cannot be edited. '
				. 'Editing a document under signature invalidates the signature.',
				$reported
			);
		}

		return null;

	}//end signatureRefusal()

	/**
	 * Refuse to edit anonymisation output.
	 *
	 * A redacted document is a deliberate artefact produced under a legal basis.
	 * Re-editing it risks re-identification, and the edit would not be visible
	 * to whoever relies on the redaction.
	 *
	 * Unlike the signature check this one fails OPEN: an unreachable register
	 * here would block every ordinary edit on every instance that has never run
	 * an anonymisation, and the harm it guards against is narrower.
	 *
	 * @param File $file The file to check.
	 *
	 * @return string|null A refusal message, or null when the file is not redaction output.
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-documents-under-signature-or-produced-by-anonymisation-are-not-editable
	 */
	public function anonymisationRefusal(File $file): ?string {
		$matches = $this->query(
			schema: 'anonymizationLink',
			filter: ['anonymizedFileId' => $file->getId()]
		);

		if ($matches === null || $matches === []) {
			return null;
		}

		return 'This document is the output of an anonymisation run and cannot be edited. '
			. 'Edit the source document and re-anonymise it instead.';

	}//end anonymisationRefusal()

	/**
	 * Run a filtered OpenRegister query, distinguishing "no rows" from "no answer".
	 *
	 * @param string $schema The schema slug.
	 * @param array<string, mixed> $filter The property filter.
	 *
	 * @return array<int, mixed>|null The matching rows, or null when the register could not be read.
	 */
	private function query(string $schema, array $filter): ?array {
		try {
			$results = $this->objectResolver->resolve()->searchObjects(
				query: ([
					'@self' => [
						'register' => self::REGISTER,
						'schema' => $schema,
					],
				] + $filter)
			);

			if (is_array($results) === false) {
				return [];
			}

			return $results;
		} catch (Throwable $e) {
			$this->logger->warning(
				'DocuDesk document guard could not query ' . $schema . ': ' . $e->getMessage(),
				['schema' => $schema]
			);

			return null;
		}

	}//end query()

	/**
	 * Read one property off an OpenRegister row, whatever shape it came back in.
	 *
	 * @param mixed $row The row.
	 * @param string $key The property name.
	 *
	 * @return mixed The value, or null.
	 */
	private function field(mixed $row, string $key): mixed {
		if (is_array($row) === true) {
			return ($row[$key] ?? ($row['object'][$key] ?? null));
		}

		if (is_object($row) === true && method_exists($row, 'jsonSerialize') === true) {
			$data = $row->jsonSerialize();
			if (is_array($data) === true) {
				return ($data[$key] ?? ($data['object'][$key] ?? null));
			}
		}

		return null;

	}//end field()
}//end class
