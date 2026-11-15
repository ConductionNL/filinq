<?php

/**
 * Generation source object
 *
 * Records which object a generated document was made on, and files the
 * document in that object's folder when the caller names no folder itself.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/template-library-leaf/specs/template-management/spec.md#requirement-generate-from-template-is-a-render-surface-leaf-req-tmpl-15
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Exception;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindOptions;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use Throwable;

/**
 * Reads `options.sourceObject` of a generate request.
 *
 * The source object must be one of the request's own data references, so a
 * request can only name an object it already renders; anything else is
 * refused with 400. The object is then written to the generated document's
 * entry as `sourceObject`. When the document is stored and the request gives
 * no `output.targetPath`, the document goes into the source object's folder,
 * looked up under the caller's own OpenRegister access and Files view. An
 * object without a folder the caller can reach keeps the default folder.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/template-library-leaf/specs/template-management/spec.md#requirement-generate-from-template-is-a-render-surface-leaf-req-tmpl-15
 */
class GenerationSourceObject {

	/**
	 * A folder path segment the document storage accepts.
	 */
	private const SEGMENT_PATTERN = '/^[A-Za-z0-9 _.\-]+$/';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objects    OpenRegister's object service, when installed.
	 * @param IRootFolder                   $rootFolder The Files root.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objects,
		private readonly IRootFolder $rootFolder,
	) {

	}//end __construct()

	/**
	 * Take `sourceObject` out of the options and work out what it changes.
	 *
	 * @param array<int, mixed>    $dataRefs The request's data references.
	 * @param array<string, mixed> $options  The request's options.
	 * @param string               $userId   The caller.
	 *
	 * @return array{options: array<string, mixed>, recordFields: array<string, mixed>}
	 *     The options without `sourceObject` (with the host folder as target when it applies),
	 *     and the fields for the generated document's entry.
	 *
	 * @throws Exception Code 400 when the source object is not one of the data references.
	 *
	 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.3
	 */
	public function apply(array $dataRefs, array $options, string $userId): array {
		if (array_key_exists('sourceObject', $options) === false) {
			return ['options' => $options, 'recordFields' => []];
		}

		$source = $this->matchingReference(candidate: $options['sourceObject'], dataRefs: $dataRefs);
		unset($options['sourceObject']);

		$output = [];
		if (isset($options['output']) === true && is_array($options['output']) === true) {
			$output = $options['output'];
		}

		$stored = in_array(($output['mode'] ?? 'return'), ['files', 'both'], true);
		if ($stored === true && trim((string)($output['targetPath'] ?? '')) === '') {
			$path = $this->hostFolderPath(source: $source, userId: $userId);
			if ($path !== null) {
				$options['output'] = array_merge($output, ['targetPath' => $path]);
			}
		}

		return ['options' => $options, 'recordFields' => ['sourceObject' => $source]];

	}//end apply()

	/**
	 * The data reference the candidate names.
	 *
	 * @param mixed             $candidate The given source object.
	 * @param array<int, mixed> $dataRefs  The request's data references.
	 *
	 * @return array{register: string, schema: string, id: string} The source object.
	 *
	 * @throws Exception Code 400 when it names none of them.
	 *
	 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.3
	 */
	private function matchingReference(mixed $candidate, array $dataRefs): array {
		$wanted = self::reference(value: $candidate);
		if ($wanted !== null) {
			foreach ($dataRefs as $dataRef) {
				if (self::reference(value: $dataRef) === $wanted) {
					return $wanted;
				}
			}
		}

		throw new Exception(message: 'options.sourceObject must be one of the dataRefs', code: 400);

	}//end matchingReference()

	/**
	 * A reference as `{register, schema, id}` strings, or null when one is missing.
	 *
	 * @param mixed $value The reference.
	 *
	 * @return array{register: string, schema: string, id: string}|null The reference.
	 *
	 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.3
	 */
	private static function reference(mixed $value): ?array {
		if (is_array($value) === false) {
			return null;
		}

		$reference = [];
		foreach (['register', 'schema', 'id'] as $key) {
			$part = ($value[$key] ?? null);
			if ((is_string($part) === false && is_int($part) === false) || trim((string)$part) === '') {
				return null;
			}

			$reference[$key] = (string)$part;
		}

		return $reference;

	}//end reference()

	/**
	 * The source object's folder, relative to the caller's Files, or null.
	 *
	 * @param array{register: string, schema: string, id: string} $source The source object.
	 * @param string                                              $userId The caller.
	 *
	 * @return string|null The path, or null when the object has no folder the caller can reach.
	 *
	 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.3
	 */
	private function hostFolderPath(array $source, string $userId): ?string {
		try {
			$found = $this->objects->resolve()->find(
				id: $source['id'],
				options: new ObjectFindOptions(register: $source['register'], schema: $source['schema'])
			);
			$self  = ((new StoredRecordFields())->read(row: $found)['@self'] ?? []);
			$folderId = 0;
			if (is_array($self) === true && is_numeric($self['folder'] ?? null) === true) {
				$folderId = (int)$self['folder'];
			}

			if ($folderId <= 0 || $userId === '') {
				return null;
			}

			$userFolder = $this->rootFolder->getUserFolder($userId);
			$node = $userFolder->getFirstNodeById($folderId);
			if (($node instanceof Folder) === false) {
				return null;
			}

			$path = trim((string)$userFolder->getRelativePath($node->getPath()), '/');
		} catch (Throwable) {
			return null;
		}//end try

		if ($path === '') {
			return null;
		}

		foreach (explode('/', $path) as $segment) {
			if ($segment === '.' || $segment === '..' || preg_match(self::SEGMENT_PATTERN, $segment) !== 1) {
				return null;
			}
		}

		return $path;

	}//end hostFolderPath()
}//end class
