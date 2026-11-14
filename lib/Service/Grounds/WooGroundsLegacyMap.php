<?php

/**
 * Woo Grounds Legacy Map
 *
 * Reads lib/Settings/woo-grounds-legacy-map.json: each of filinq's old Woo
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
 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Grounds;

use RuntimeException;

/**
 * The mapping from filinq's old Woo `base` slugs to dossiq codes.
 *
 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
 */
class WooGroundsLegacyMap {

	/**
	 * The value that marks a slug without a one-to-one code.
	 */
	public const UNMAPPED = 'unmapped';

	/**
	 * The shipped mapping file.
	 */
	public const PATH = __DIR__ . '/../../Settings/woo-grounds-legacy-map.json';

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
	 * @spec openspec/changes/grondslagen-read-from-dossiq/tasks.md
	 */
	public function all(): array {
		if ($this->map !== null) {
			return $this->map;
		}

		$decoded = null;
		$path = ($this->path ?? self::PATH);
		if (is_readable($path) === true) {
			$raw = file_get_contents($path);
			if (is_string($raw) === true) {
				$decoded = json_decode($raw, true);
			}
		}

		if (is_array($decoded) === false || is_array($decoded['map'] ?? null) === false) {
			throw new RuntimeException('The Woo grounds legacy map cannot be read.');
		}

		$map = [];
		foreach ($decoded['map'] as $slug => $code) {
			if (is_string($slug) === true && is_string($code) === true && $code !== '') {
				$map[$slug] = $code;
			}
		}

		$this->map = $map;
		return $this->map;
	}//end all()

	/**
	 * Whether a reference is one of filinq's old Woo slugs.
	 *
	 * @param string $slug The reference.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/tasks.md
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
	 * @spec openspec/changes/grondslagen-read-from-dossiq/tasks.md
	 */
	public function codeFor(string $slug): ?string {
		$code = ($this->all()[$slug] ?? null);
		if ($code === null || $code === self::UNMAPPED) {
			return null;
		}

		return $code;
	}//end codeFor()
}//end class
