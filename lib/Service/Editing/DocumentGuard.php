<?php

/**
 * Document Guard
 *
 * The standing refusals for agent-reachable document operations. Each one is a
 * distinct, named condition rather than a shared "not allowed", because an
 * agent that cannot tell WHY it was refused will retry the same call.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Editing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-editing-tools/tasks.md#task-2-6
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Editing;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCP\Files\File;
use Psr\Log\LoggerInterface;
use Throwable;
use ZipArchive;

/**
 * Enforces the refusals that bound what an agent may do to a document.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
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
	 * The OpenRegister register holding Filinq's document schemas.
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
				'Filinq document guard could not query ' . $schema . ': ' . $e->getMessage(),
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

	/**
	 * Refuse a file whose FORMAT must never be content-edited.
	 *
	 * 🔴 Determined from CONTENT, not from the filename. An extension is a
	 * caller's claim about a file; the bytes are the file. A `.docx` that
	 * actually carries a VBA project is exactly the input a name-based check
	 * waves through, and writing into a macro-bearing package is a
	 * code-execution vector in document clothing.
	 *
	 * Three standing refusals:
	 * - macro-bearing OOXML (`.docm`/`.xlsm`/`.pptm`, or ANY package carrying
	 *   `vbaProject.bin`)
	 * - `.odb`, which is a database rather than a document and has no
	 *   meaningful "edit a block" semantics
	 * - PDF content rewriting; a PDF is a final-form artefact and silently
	 *   rewriting its text produces something forgery-shaped
	 *
	 * @param File $file The file to check.
	 *
	 * @return string|null A refusal message, or null when the format is editable.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#3-refusals
	 */
	public function formatRefusal(File $file): ?string {
		$mime = strtolower((string)$file->getMimeType());

		if (str_contains($mime, 'pdf') === true) {
			return 'A PDF is a final-form artefact. Filinq annotates and fills PDF forms but never '
				. 'rewrites PDF content — a silently rewritten PDF is forgery-shaped.';
		}

		if (str_contains($mime, 'oasis.opendocument.database') === true) {
			return 'An .odb is a database, not a document. It has no "edit a block" semantics and its '
				. 'backing store is not a package.';
		}

		if ($this->carriesMacros(file: $file) === true) {
			return 'This file carries a VBA macro project. Writing into a macro-bearing package is a '
				. 'code-execution vector in document clothing, so it is refused regardless of its '
				. 'extension — the refusal is based on the bytes, not the file name.';
		}

		return null;
	}//end formatRefusal()

	/**
	 * Whether a package carries a VBA macro project.
	 *
	 * ⚠️ Reads the package rather than trusting `.docm`/`.xlsm`/`.pptm`. Both
	 * halves matter: a renamed `.docx` is caught, and an unreadable package is
	 * treated as macro-bearing — "I could not tell" must not resolve to "safe"
	 * for a code-execution check.
	 *
	 * @param File $file The file to inspect.
	 *
	 * @return bool True when macros are present or cannot be ruled out.
	 */
	private function carriesMacros(File $file): bool {
		$path = tempnam(sys_get_temp_dir(), 'ddmacro');
		if ($path === false) {
			return true;
		}

		try {
			file_put_contents($path, $file->getContent());

			$zip = new ZipArchive();
			if ($zip->open($path) !== true) {
				// Not a zip at all: no OOXML package, so no vbaProject to find.
				return false;
			}

			$found = false;
			for ($i = 0; $i < $zip->numFiles; $i++) {
				$name = strtolower((string)$zip->getNameIndex($i));
				if (str_contains($name, 'vbaproject.bin') === true) {
					$found = true;
					break;
				}
			}

			$zip->close();

			return $found;
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[DocumentGuard] could not inspect a package for macros; treating it as macro-bearing',
				context: ['file' => __FILE__, 'line' => __LINE__, 'error' => $e->getMessage()]
			);

			return true;
		} finally {
			// The temp file is ours and may already be gone if the write never
			// happened; a failure to remove it is not a reason to fail the
			// guard, but suppressing the diagnostic entirely hides a full disk.
			if (file_exists($path) === true && unlink($path) === false) {
				$this->logger->warning(
					message: '[DocumentGuard] could not remove a temporary package copy',
					context: ['file' => __FILE__, 'line' => __LINE__, 'path' => $path]
				);
			}
		}
	}//end carriesMacros()
}//end class
