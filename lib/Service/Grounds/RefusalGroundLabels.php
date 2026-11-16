<?php

/**
 * Filinq Woo Ground Labels
 *
 * @category Service
 * @package  OCA\Filinq\Service\Grounds
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-every-grounds-reader-in-filinq-goes-through-the-resolver-req-grd-002
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Grounds;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * The Woo refusal grounds as labels and picker options, for the readers that
 * also know a tenant's own bases. Every read goes through the resolver; a list
 * or map that cannot be read gives nothing rather than an error, so a page
 * still renders.
 *
 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-every-grounds-reader-in-filinq-goes-through-the-resolver-req-grd-002
 */
class RefusalGroundLabels {

	/**
	 * The label of a Woo reference that resolves to no ground, before the reference.
	 */
	public const UNKNOWN = 'unknown ground';

	/**
	 * The grounds keyed by code, once read (empty when the read failed).
	 *
	 * @var array<string, array{name: string, description: string}>|null
	 */
	private ?array $byCode = null;

	/**
	 * Constructor.
	 *
	 * @param RefusalGroundsResolver   $grounds The one grounds reader.
	 * @param RefusalGroundsLegacyMap|null $legacy  Re-points an old Woo `base` slug.
	 * @param LoggerInterface          $logger  Records a list or map that cannot be read.
	 */
	public function __construct(
		private readonly RefusalGroundsResolver $grounds,
		private readonly ?RefusalGroundsLegacyMap $legacy,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * The grounds keyed by code, as labels; empty when no list can be read.
	 *
	 * @return array<string, array{name: string, description: string}>
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-every-grounds-reader-in-filinq-goes-through-the-resolver-req-grd-002
	 */
	public function byCode(): array {
		if ($this->byCode !== null) {
			return $this->byCode;
		}

		try {
			$list = $this->grounds->list();
		} catch (Throwable $e) {
			$this->logger->warning('Filinq: the Woo refusal grounds cannot be read', ['error' => $e->getMessage()]);
			$this->byCode = [];
			return [];
		}

		$this->byCode = [];
		foreach ((array)($list['grounds'] ?? []) as $ground) {
			$label = self::labelOf(ground: $ground);
			if ($label !== null) {
				$this->byCode[$label['code']] = ['name' => $label['name'], 'description' => $label['description']];
			}
		}

		return $this->byCode;
	}//end byCode()

	/**
	 * The label of a Woo reference: a code directly, an old slug through the
	 * legacy map, "unknown ground" with the reference when it resolves to
	 * nothing; null for a reference that is not a Woo ground.
	 *
	 * @param string $ref The stored reference.
	 *
	 * @return array{name: string, description: string}|null
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-every-grounds-reader-in-filinq-goes-through-the-resolver-req-grd-002
	 */
	public function labelFor(string $ref): ?array {
		$byCode = $this->byCode();
		$code = ($this->codeFor(ref: $ref) ?? $ref);
		if (isset($byCode[$code]) === true) {
			return $byCode[$code];
		}

		if ($this->isWooReference(ref: $ref) === true) {
			return ['name' => self::UNKNOWN . ' ' . $ref, 'description' => ''];
		}

		return null;
	}//end labelFor()

	/**
	 * The grounds as picker options, the code as the stored value.
	 *
	 * @return list<array{slug: string, name: string, description: string}>
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-every-grounds-reader-in-filinq-goes-through-the-resolver-req-grd-002
	 */
	public function options(): array {
		$options = [];
		foreach ($this->byCode() as $code => $label) {
			$options[] = ['slug' => (string)$code, 'name' => $label['name'], 'description' => $label['description']];
		}

		return $options;
	}//end options()

	/**
	 * The code an old Woo slug re-points to, or null; an unreadable map re-points nothing.
	 *
	 * @param string $ref The stored reference.
	 *
	 * @return string|null
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-every-grounds-reader-in-filinq-goes-through-the-resolver-req-grd-002
	 */
	public function codeFor(string $ref): ?string {
		try {
			return $this->legacy?->codeFor(slug: $ref);
		} catch (Throwable $e) {
			$this->logger->warning('Filinq: the Woo grounds legacy map cannot be read', ['error' => $e->getMessage()]);
			return null;
		}
	}//end codeFor()

	/**
	 * Whether a reference names a Woo ground: a Woo article code or an old Woo slug.
	 *
	 * @param string $ref The stored reference.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-every-grounds-reader-in-filinq-goes-through-the-resolver-req-grd-002
	 */
	public function isWooReference(string $ref): bool {
		if (preg_match('/^5\.\d/', $ref) === 1) {
			return true;
		}

		try {
			return ($this->legacy?->isLegacySlug(slug: $ref) === true);
		} catch (Throwable) {
			return false;
		}
	}//end isWooReference()

	/**
	 * One ground's code, label and description, or null when it has no code or label.
	 *
	 * @param mixed $ground One ground of the list.
	 *
	 * @return array{code: string, name: string, description: string}|null
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-every-grounds-reader-in-filinq-goes-through-the-resolver-req-grd-002
	 */
	private static function labelOf(mixed $ground): ?array {
		if (is_array($ground) === false) {
			return null;
		}

		$code = (string)($ground['code'] ?? '');
		$name = (string)($ground['label'] ?? '');
		if ($code === '' || $name === '') {
			return null;
		}

		return ['code' => $code, 'name' => $name, 'description' => (string)($ground['description'] ?? '')];
	}//end labelOf()
}//end class
