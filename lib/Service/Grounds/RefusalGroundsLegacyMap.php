<?php

/**
 * Woo Grounds Legacy Map
 *
 * Reads lib/Settings/refusal-grounds-legacy-map.json: each of filinq's old Woo
 * `base` slugs to the dossiq refusal ground code it re-points to, or
 * `unmapped` when there is no one-to-one code.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Grounds
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-24-grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Grounds;

use OCA\Filinq\Kernel\Contracts\RefusalGroundsLegacyMapInterface;
use RuntimeException;

/**
 * The mapping from filinq's old Woo `base` slugs to dossiq codes.
 *
 * @spec openspec/changes/archive/2026-11-24-grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
 */
class RefusalGroundsLegacyMap implements RefusalGroundsLegacyMapInterface {

	/**
	 * The value that marks a slug without a one-to-one code.
	 */
	public const UNMAPPED = 'unmapped';

	/**
	 * The shipped mapping file.
	 */
	public const PATH = __DIR__ . '/../../Settings/refusal-grounds-legacy-map.json';

	/**
	 * The mapping, read once.
	 *
	 * @var array<string, string>|null
	 */
	private ?array $map = null;

	/**
	 * Constructor.
	 *
	 * @param string|null $path The mapping file; a test names a fixture, null is the shipped one.
	 */
	public function __construct(
		private readonly ?string $path = null,
	) {
	}//end __construct()

	/**
	 * Every old slug with its code or `unmapped`.
	 *
	 * @return array<string, string> Slug to code.
	 *
	 * @throws RuntimeException When the mapping file cannot be read.
	 *
	 * @spec openspec/changes/archive/2026-11-24-grondslagen-read-from-dossiq/tasks.md
	 */
	public function all(): array {
		if ($this->map !== null) {
			return $this->map;
		}

		$decoded = $this->decoded();

		$map = [];
		foreach ($decoded['map'] as $slug => $code) {
			if (self::isEntry(slug: $slug, code: $code) === true) {
				$map[$slug] = $code;
			}
		}

		$this->map = $map;
		return $this->map;
	}//end all()

	/**
	 * The mapping file, decoded.
	 *
	 * @return array{map: array<mixed>} The decoded file.
	 *
	 * @throws RuntimeException When the mapping file cannot be read.
	 *
	 * @spec openspec/changes/archive/2026-11-24-grondslagen-read-from-dossiq/tasks.md
	 */
	private function decoded(): array {
		$decoded = null;
		$path    = ($this->path ?? self::PATH);
		if (is_readable($path) === true) {
			$raw     = file_get_contents($path);
			$decoded = json_decode((string)$raw, true);
		}

		if (is_array($decoded) === false || is_array($decoded['map'] ?? null) === false) {
			throw new RuntimeException('The Woo grounds legacy map cannot be read.');
		}

		return $decoded;
	}//end decoded()

	/**
	 * Whether a row of the mapping file is a slug with a non-empty code.
	 *
	 * @param mixed $slug The key.
	 * @param mixed $code The value.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/archive/2026-11-24-grondslagen-read-from-dossiq/tasks.md
	 */
	private static function isEntry(mixed $slug, mixed $code): bool {
		return is_string($slug) === true && is_string($code) === true && $code !== '';
	}//end isEntry()

	/**
	 * Whether a reference is one of filinq's old Woo slugs.
	 *
	 * @param string $slug The reference.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/archive/2026-11-24-grondslagen-read-from-dossiq/tasks.md
	 */
	public function isLegacySlug(string $slug): bool {
		return array_key_exists($slug, $this->all());
	}//end isLegacySlug()

	/**
	 * The dossiq code an old slug re-points to, or null when it is not an old
	 * slug or is unmapped. Never a guess.
	 *
	 * @param string $slug The reference.
	 *
	 * @return string|null The code.
	 *
	 * @spec openspec/changes/archive/2026-11-24-grondslagen-read-from-dossiq/tasks.md
	 */
	public function codeFor(string $slug): ?string {
		$code = ($this->all()[$slug] ?? null);
		if ($code === null || $code === self::UNMAPPED) {
			return null;
		}

		return $code;
	}//end codeFor()
}//end class
