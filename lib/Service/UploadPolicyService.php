<?php

/**
 * Upload Policy Service
 *
 * The one place that answers whether a file may be stored. The policy is an
 * administered object rather than a list in code, and every write path asks the
 * same question of the same object: the API, the intake channels and the
 * upload surface. A policy enforced on one of four paths is not a policy.
 *
 * The media type is read from the BYTES. A .exe renamed to bijlage.pdf is the
 * case this check exists for, and reading the name would pass it.
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

use finfo;
use OCA\Filinq\Exception\UploadRefusedException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Checks one upload against the administered policy.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */
class UploadPolicyService {

	/**
	 * The schema holding the policies.
	 *
	 * @var string
	 */
	public const SCHEMA = 'uploadPolicy';

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
	 * Check one file against the active policy, or refuse it.
	 *
	 * @param string $fileName The name the file arrived under.
	 * @param string $contents The bytes, which is what the media type is read from.
	 *
	 * @return array<string, mixed> What the check found: the detected type and the policy that allowed it.
	 *
	 * @throws UploadRefusedException When the policy refuses the file.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function check(string $fileName, string $contents): array {
		$policy = $this->activePolicy();
		if ($policy === null) {
			// No policy is not a refusal. An instance that never declared one
			// worked before this change and keeps working; refusing everything
			// on an undeclared policy would take every upload down on upgrade.
			return ['detectedType' => $this->detect(contents: $contents), 'policy' => '', 'checked' => false];
		}

		$name = (string)($policy['name'] ?? '');
		$size = strlen($contents);
		$maximum = (int)($policy['maxSizeBytes'] ?? 0);
		if ($maximum > 0 && $size > $maximum) {
			throw new UploadRefusedException(
				message: 'This file is ' . $this->megabytes($size) . ' and the policy allows ' . $this->megabytes($maximum) . '.',
				detectedType: '',
				policy: $name
			);
		}

		$extensions = $this->stringList(value: ($policy['allowedExtensions'] ?? []));
		$extension = strtolower((string)pathinfo($fileName, PATHINFO_EXTENSION));
		if ($extensions !== [] && in_array($extension, $extensions, true) === false) {
			throw new UploadRefusedException(
				message: 'The policy does not allow ' . ($extension === '' ? 'a file without an extension' : '.' . $extension) . '.',
				detectedType: '',
				policy: $name
			);
		}

		$detected = $this->detect(contents: $contents);
		if ($detected === '') {
			if (($policy['refuseUnknownType'] ?? true) === true) {
				throw new UploadRefusedException(
					message: 'The type of this file could not be read from its contents, and the policy refuses those.',
					detectedType: '',
					policy: $name
				);
			}

			$this->logger->warning(
				message: '[UploadPolicyService] stored a file whose type could not be read, as the policy allows',
				context: ['file' => __FILE__, 'line' => __LINE__, 'fileName' => $fileName]
			);

			return ['detectedType' => '', 'policy' => $name, 'checked' => true];
		}

		$types = $this->stringList(value: ($policy['allowedMediaTypes'] ?? []));
		if ($types !== [] && in_array($detected, $types, true) === false) {
			throw new UploadRefusedException(
				message: 'This file is a ' . $detected . ', which the policy does not allow.',
				detectedType: $detected,
				policy: $name
			);
		}

		return ['detectedType' => $detected, 'policy' => $name, 'checked' => true];

	}//end check()

	/**
	 * The policy in force, or null when none is declared.
	 *
	 * @return array<string, mixed>|null The active policy.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function activePolicy(): ?array {
		try {
			// 🔴 SLUGS GO THROUGH `searchObjectsBySlug`, NEVER `searchObjects`.
			// `searchObjects` answers a slug with zero rows and no error, so
			// activePolicy() was always null, check() returned
			// `checked: false` and every upload passed unexamined on an
			// instance that had declared a policy.
			$results = $this->objectResolver->resolve()->searchObjectsBySlug(
				registerSlug: IntakeRepository::REGISTER,
				schemaSlug: self::SCHEMA,
				filters: ['active' => true]
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[UploadPolicyService] could not read the upload policy',
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

		if (isset($row['object']) === true && is_array($row['object']) === true) {
			return $row['object'];
		}

		return $row;

	}//end activePolicy()

	/**
	 * The media type of some bytes.
	 *
	 * @param string $contents The bytes.
	 *
	 * @return string The media type, or an empty string when it could not be read.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	private function detect(string $contents): string {
		if ($contents === '') {
			return '';
		}

		try {
			$info = new finfo(FILEINFO_MIME_TYPE);
			$detected = $info->buffer($contents);
		} catch (Throwable $e) {
			return '';
		}

		if (is_string($detected) === false || $detected === '' || $detected === 'application/octet-stream') {
			return '';
		}

		return $detected;

	}//end detect()

	/**
	 * Read a declared list into lower-case strings.
	 *
	 * @param mixed $value The declared value.
	 *
	 * @return array<int, string> The list.
	 *
	 * @spec exclude Shape adapter over a declared list; no behaviour of its own.
	 */
	private function stringList(mixed $value): array {
		if (is_array($value) === false) {
			return [];
		}

		$list = [];
		foreach ($value as $item) {
			$item = strtolower(trim((string)$item));
			if ($item !== '') {
				$list[] = $item;
			}
		}

		return $list;

	}//end stringList()

	/**
	 * A byte count in megabytes, for a sentence somebody reads.
	 *
	 * @param int $bytes The byte count.
	 *
	 * @return string The size.
	 *
	 * @spec exclude Formatting helper.
	 */
	private function megabytes(int $bytes): string {
		return round(($bytes / 1048576), 1) . ' MB';

	}//end megabytes()
}//end class
