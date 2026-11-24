<?php

/**
 * Form Document Filing
 *
 * The filinq half of forms as documents. The surface that defines a form owns
 * its fields, the document-reference field type included; filinq checks the
 * reference such a field holds, and renders a submitted form to a document
 * filed on the record.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-24-forms-as-documents/specs/document-creatie-sjablonen/spec.md#requirement-a-file-is-a-value-a-field-can-hold-and-a-form-becomes-a-document-req-dft-05
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Kernel\Contracts\DocumentServiceInterface;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindOptions;
use OCA\OpenRegister\Service\ObjectService;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Checks document references in form fields and files a submitted form as a document.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-24-forms-as-documents/specs/document-creatie-sjablonen/spec.md#requirement-a-file-is-a-value-a-field-can-hold-and-a-form-becomes-a-document-req-dft-05
 */
class FormDocumentFiling {

	/**
	 * The register a reference names when it names none.
	 *
	 * @var string
	 */
	public const DEFAULT_REGISTER = 'filinq';

	/**
	 * The schema a reference names when it names none: filinq's document record.
	 *
	 * @var string
	 */
	public const DEFAULT_SCHEMA = 'document';

	/**
	 * The answer for a reference that does not resolve or that the caller may not read.
	 *
	 * @var string
	 */
	public const REFERENCE_NOT_READABLE = 'The linked document could not be found, or you do not have access to it.';

	/**
	 * The formats a submitted form can be filed as.
	 *
	 * @var list<string>
	 */
	public const FORMATS = ['pdf', 'docx', 'odf', 'html'];

	/**
	 * OpenRegister's file service, which stores a file in an object's folder.
	 *
	 * @var string
	 */
	private const FILE_SERVICE = 'OCA\OpenRegister\Service\FileService';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objects   Resolver for OpenRegister's ObjectService.
	 * @param ContainerInterface            $container Resolves OpenRegister's FileService.
	 * @param DocumentServiceInterface      $documents filinq's template render.
	 * @param LoggerInterface               $logger    Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objects,
		private readonly ContainerInterface $container,
		private readonly DocumentServiceInterface $documents,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Check a document reference a form field holds: it resolves and the caller may read it.
	 *
	 * The answer is the reference itself and the record's title; the file is
	 * never read or copied, so the field stores a pointer and the file stays
	 * stored once (REQ-DFT-05).
	 *
	 * @param array<string, mixed> $reference `{register?, schema?, id}`.
	 *
	 * @return array{register: string, schema: string, id: string, title: string} The checked reference.
	 *
	 * @throws FormDocumentRefused 400 malformed, 404 when it does not resolve or the caller may not read it.
	 *
	 * @spec openspec/changes/archive/2026-11-24-forms-as-documents/tasks.md#1-build
	 */
	public function checkReference(array $reference): array {
		$ref = $this->normaliseReference(reference: $reference, defaults: true);
		if ($ref === null) {
			throw new FormDocumentRefused(message: 'A document reference needs the id of a document record.', code: 400);
		}

		$record = $this->readAsCaller(ref: $ref, message: self::REFERENCE_NOT_READABLE);

		return [
			'register' => $ref['register'],
			'schema' => $ref['schema'],
			'id' => $ref['id'],
			'title' => self::titleOf(record: $record),
		];

	}//end checkReference()

	/**
	 * Render a submitted form from the values as submitted and file the document on the record.
	 *
	 * The values come from the submission and never from the record (D2), so
	 * a later change on the record does not change the filed document. Every
	 * field named in `documentFields` must hold a reference the caller may
	 * read; the template sees it under `documents.<field>`, never the file.
	 *
	 * @param array<string, mixed> $submission `{templateId, record: {register, schema, id}, values,
	 *                                         documentFields?, filename?, format?}`.
	 *
	 * @return array<string, mixed> The filed document.
	 *
	 * @throws FormDocumentRefused 400 malformed, 404 for the record or a reference, 502 when filing fails.
	 *
	 * @spec openspec/changes/archive/2026-11-24-forms-as-documents/tasks.md#1-build
	 */
	public function renderSubmission(array $submission): array {
		$templateId = trim((string) ($submission['templateId'] ?? ''));
		$values     = ($submission['values'] ?? null);
		$record     = $this->normaliseReference(reference: (array) ($submission['record'] ?? []), defaults: false);
		$format     = (string) ($submission['format'] ?? 'pdf');
		if ($templateId === '' || is_array($values) === false || $record === null) {
			throw new FormDocumentRefused(message: 'A submitted form needs a templateId, the record and its values.', code: 400);
		}

		if (in_array($format, self::FORMATS, true) === false) {
			throw new FormDocumentRefused(message: 'A submitted form is filed as pdf, docx, odf or html.', code: 400);
		}

		$this->readAsCaller(ref: $record, message: 'The record to file the form on could not be found, or you do not have access to it.');
		$references = $this->checkDocumentFields(values: $values, fields: (array) ($submission['documentFields'] ?? []));
		$fileName   = $this->fileNameOf(submission: $submission, format: $format);

		$rendered = $this->documents->generateDocument(
			templateId: $templateId,
			dataRefs: [],
			options: [
				'format' => $format,
				'filename' => $fileName,
				'adHocData' => ['form' => $values, 'documents' => $references, 'record' => $record],
			]
		);

		return [
			'fileName' => $fileName,
			'fileId' => $this->file(record: $record, fileName: $fileName, content: (string) $rendered['content']),
			'format' => $format,
			'sha256' => (string) ($rendered['sha256'] ?? hash('sha256', (string) $rendered['content'])),
			'templateVersion' => ($rendered['templateVersion'] ?? null),
			'record' => $record,
			'references' => $references,
			'warnings' => (array) ($rendered['warnings'] ?? []),
		];

	}//end renderSubmission()

	/**
	 * Check every document field of a submission and return its references by field.
	 *
	 * @param array<string, mixed> $values The values as submitted.
	 * @param array<int, mixed>    $fields The names of the fields that hold a document reference.
	 *
	 * @return array<string, array{register: string, schema: string, id: string, title: string}> The checked references.
	 *
	 * @throws FormDocumentRefused When a field holds no reference or one the caller may not read.
	 */
	private function checkDocumentFields(array $values, array $fields): array {
		$references = [];
		foreach ($fields as $field) {
			$name  = (string) $field;
			$value = ($values[$name] ?? null);
			if ($value === null || $value === '' || $value === []) {
				continue;
			}

			if (is_string($value) === true) {
				$value = ['id' => $value];
			}

			$references[$name] = $this->checkReference(reference: (array) $value);
		}

		return $references;

	}//end checkDocumentFields()

	/**
	 * A reference as register, schema and id, or null when it has no id.
	 *
	 * @param array<string, mixed> $reference The reference.
	 * @param bool                 $defaults  Whether a missing register and schema mean filinq's document.
	 *
	 * @return array{register: string, schema: string, id: string}|null The reference.
	 */
	private function normaliseReference(array $reference, bool $defaults): ?array {
		$ref = [
			'register' => trim((string) ($reference['register'] ?? '')),
			'schema' => trim((string) ($reference['schema'] ?? '')),
			'id' => trim((string) ($reference['id'] ?? '')),
		];
		if ($defaults === true) {
			$ref['register'] = self::orDefault(value: $ref['register'], default: self::DEFAULT_REGISTER);
			$ref['schema']   = self::orDefault(value: $ref['schema'], default: self::DEFAULT_SCHEMA);
		}

		if (in_array('', $ref, true) === true) {
			return null;
		}

		return $ref;

	}//end normaliseReference()

	/**
	 * Read a record with the caller's rights.
	 *
	 * A record the caller may not read answers the same 404 as one that does
	 * not exist, so the check never tells a caller that a record exists.
	 *
	 * @param array{register: string, schema: string, id: string} $ref     The record.
	 * @param string                                              $message The 404 message.
	 *
	 * @return array<string, mixed> The record as the caller reads it.
	 *
	 * @throws FormDocumentRefused 404.
	 */
	private function readAsCaller(array $ref, string $message): array {
		$record = self::rowOf(
			object: $this->findQuietly(id: $ref['id'], options: new ObjectFindOptions(register: $ref['register'], schema: $ref['schema']))
		);
		if ($record === null) {
			throw new FormDocumentRefused(message: $message, code: 404);
		}

		return $record;

	}//end readAsCaller()

	/**
	 * One find, where OpenRegister's "not found" or "not allowed" reads as null.
	 *
	 * @param string            $id      The object id.
	 * @param ObjectFindOptions $options Where to look.
	 *
	 * @return mixed The object, or null.
	 */
	private function findQuietly(string $id, ObjectFindOptions $options): mixed {
		try {
			// The caller's rights apply: OpenRegister answers null or throws for a record they may not read.
			// The caller's rights apply through OpenRegister's ObjectService.
			$objectService = $this->objects->resolve();

			return $objectService->find(id: $id, options: $options);
		} catch (Throwable $e) {
			$this->logger->debug(
				message: '[FormDocumentFiling] a record did not resolve',
				context: ['file' => __FILE__, 'line' => __LINE__, 'id' => $id, 'error' => $e->getMessage()]
			);

			return null;
		}

	}//end findQuietly()

	/**
	 * Store the rendered form in the record's folder.
	 *
	 * @param array{register: string, schema: string, id: string} $record   The record.
	 * @param string                                              $fileName The file name.
	 * @param string                                              $content  The rendered bytes.
	 *
	 * @return int|null The stored file's id, when OpenRegister answers one.
	 *
	 * @throws FormDocumentRefused 502 when the file cannot be stored.
	 */
	private function file(array $record, string $fileName, string $content): ?int {
		try {
			$file = $this->container->get(self::FILE_SERVICE)->addFile(
				objectEntity: $record['id'],
				fileName: $fileName,
				content: $content
			);
		} catch (Throwable $e) {
			$this->logger->error(
				message: '[FormDocumentFiling] the submitted form could not be filed on the record',
				context: ['file' => __FILE__, 'line' => __LINE__, 'record' => $record['id'], 'error' => $e->getMessage()]
			);

			throw new FormDocumentRefused(message: 'The submitted form could not be filed on the record.', code: 502);
		}

		if (is_object($file) === true && method_exists($file, 'getId') === true) {
			return (int) $file->getId();
		}

		return null;

	}//end file()

	/**
	 * The file name of the filed form: the requested name, or the form's own.
	 *
	 * @param array<string, mixed> $submission The submission.
	 * @param string               $format     The format.
	 *
	 * @return string The file name with its extension.
	 */
	private function fileNameOf(array $submission, string $format): string {
		$base = trim((string) ($submission['filename'] ?? ''));
		$base = preg_replace('/[\\\\\/:*?"<>|]+/', '-', $base) ?? '';
		$base = preg_replace('/\.(pdf|docx|odt|odf|html)$/i', '', $base) ?? '';
		if ($base === '') {
			$base = 'form-' . gmdate('Ymd-His');
		}

		$extension = $format;
		if ($format === 'odf') {
			$extension = 'odt';
		}

		return $base . '.' . $extension;

	}//end fileNameOf()

	/**
	 * The value, or the default when the value is empty.
	 *
	 * @param string $value   The value.
	 * @param string $default The default.
	 *
	 * @return string The value or the default.
	 */
	private static function orDefault(string $value, string $default): string {
		if ($value === '') {
			return $default;
		}

		return $value;

	}//end orDefault()

	/**
	 * A found object as an array, or null.
	 *
	 * @param mixed $object What OpenRegister returned.
	 *
	 * @return array<string, mixed>|null The row.
	 */
	private static function rowOf(mixed $object): ?array {
		if (is_array($object) === true) {
			return $object;
		}

		if (is_object($object) === true && method_exists($object, 'jsonSerialize') === true) {
			return (array) $object->jsonSerialize();
		}

		return null;

	}//end rowOf()

	/**
	 * The title of a document record, from the fields documents usually carry.
	 *
	 * @param array<string, mixed> $record The record.
	 *
	 * @return string The title, or '' when it carries none.
	 */
	private static function titleOf(array $record): string {
		foreach (['title', 'name', 'fileName'] as $key) {
			if (is_string($record[$key] ?? null) === true && $record[$key] !== '') {
				return $record[$key];
			}
		}

		return '';

	}//end titleOf()
}//end class
