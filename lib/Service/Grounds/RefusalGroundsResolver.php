<?php

/**
 * Filinq RefusalGroundsResolver
 *
 * The one place filinq reads the Woo refusal grounds from: dossiq's list when
 * dossiq is installed and enabled, else the read-only statutory snapshot
 * vendored from dossiq (decisions D3 and D12).
 *
 * @category Service
 * @package  OCA\Filinq\Service\Grounds
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-one-resolver-reads-the-grounds-from-dossiq-or-from-a-read-only-snapshot-req-grd-001
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Grounds;

use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Reads the Woo refusal grounds from dossiq, or from the snapshot.
 *
 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-one-resolver-reads-the-grounds-from-dossiq-or-from-a-read-only-snapshot-req-grd-001
 */
class RefusalGroundsResolver {

	/**
	 * dossiq's grounds list.
	 */
	public const DOSSIQ_GROUNDS = 'OCA\\Dossiq\\Woo\\WooRefusalGrounds';

	/**
	 * What dossiq throws when it cannot read its own list.
	 */
	public const DOSSIQ_UNAVAILABLE = 'OCA\\Dossiq\\Woo\\WooRefusalGroundsUnavailable';

	/**
	 * The vendored snapshot, copied verbatim from the dossiq release that ships it.
	 */
	public const SNAPSHOT = __DIR__ . '/../../Settings/woo-refusal-grounds.snapshot.json';

	/**
	 * Constructor.
	 *
	 * @param IAppManager        $appManager    Tells whether dossiq is installed and enabled.
	 * @param ContainerInterface $container     Resolves dossiq's list by name.
	 * @param LoggerInterface    $logger        Records a fall-back to the snapshot.
	 * @param string             $groundsClass  dossiq's list class; a test names a double.
	 * @param string             $snapshotPath  The snapshot file; a test names a fixture.
	 */
	public function __construct(
		private readonly IAppManager $appManager,
		private readonly ContainerInterface $container,
		private readonly LoggerInterface $logger,
		private readonly string $groundsClass = self::DOSSIQ_GROUNDS,
		private readonly string $snapshotPath = self::SNAPSHOT,
	) {
	}//end __construct()

	/**
	 * The grounds: from dossiq when it can answer, else from the snapshot.
	 *
	 * @return array{source: string, version: string, grounds: list<array<string, mixed>>}
	 *
	 * @throws RuntimeException When the snapshot is needed and cannot be read; an empty list is never answered.
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-one-resolver-reads-the-grounds-from-dossiq-or-from-a-read-only-snapshot-req-grd-001
	 */
	public function list(): array {
		$fromDossiq = $this->fromDossiq(withRetired: false);
		if ($fromDossiq !== null) {
			return $fromDossiq;
		}

		return $this->fromSnapshot(withRetired: false);
	}//end list()

	/**
	 * Every ground, retired ones included: from dossiq when it can answer, else from the snapshot.
	 *
	 * A decision may cite a ground that has since been retired; this is the
	 * list such a citation is looked up in.
	 *
	 * @return array{source: string, version: string, grounds: list<array<string, mixed>>}
	 *
	 * @throws RuntimeException When the snapshot is needed and cannot be read; an empty list is never answered.
	 *
	 * @spec openspec/changes/woo-request-workflow
	 */
	public function listWithRetired(): array {
		$fromDossiq = $this->fromDossiq(withRetired: true);
		if ($fromDossiq !== null) {
			return $fromDossiq;
		}

		return $this->fromSnapshot(withRetired: true);
	}//end listWithRetired()

	/**
	 * One ground by code, retired ones included, or null when no ground carries it.
	 *
	 * @param string $code The code, such as 5.1.2.e.
	 *
	 * @return array<string, mixed>|null The ground.
	 *
	 * @throws RuntimeException When the snapshot is needed and cannot be read.
	 *
	 * @spec openspec/changes/woo-request-workflow
	 */
	public function byCode(string $code): ?array {
		$code = trim($code);
		foreach ($this->listWithRetired()['grounds'] as $ground) {
			if (is_array($ground) === true && ($ground['code'] ?? null) === $code) {
				return $ground;
			}
		}

		return null;
	}//end byCode()

	/**
	 * dossiq's list, or null when dossiq is absent, disabled or says it cannot read it.
	 *
	 * @param bool $withRetired Whether retired grounds are included.
	 *
	 * @return array{source: string, version: string, grounds: list<array<string, mixed>>}|null
	 */
	private function fromDossiq(bool $withRetired): ?array {
		if ($this->appManager->isEnabledForAnyone('dossiq') === false || class_exists($this->groundsClass) === false) {
			return null;
		}

		try {
			$list = $this->container->get($this->groundsClass);
			if ($withRetired === true) {
				$grounds = $list->listWithRetired();
			} else {
				$grounds = $list->list();
			}
		} catch (Throwable $e) {
			$reason = $e->getMessage();
			if (is_a($e, self::DOSSIQ_UNAVAILABLE) === false) {
				throw $e;
			}

			$this->logger->warning('Filinq: dossiq cannot read its refusal grounds, using the snapshot', ['error' => $reason]);
			return null;
		}

		if (is_array($grounds) === false || $grounds === []) {
			return null;
		}

		$version = '';
		if (defined($this->groundsClass . '::LIST_VERSION') === true) {
			$version = (string)constant($this->groundsClass . '::LIST_VERSION');
		}

		return ['source' => 'dossiq', 'version' => $version, 'grounds' => array_values($grounds)];
	}//end fromDossiq()

	/**
	 * The snapshot's grounds: the active ones, or all of them with retired ones included.
	 *
	 * @param bool $withRetired Whether retired grounds are included.
	 *
	 * @return array{source: string, version: string, grounds: list<array<string, mixed>>}
	 *
	 * @throws RuntimeException When the snapshot cannot be read or holds no grounds.
	 */
	private function fromSnapshot(bool $withRetired): array {
		$snapshot = null;
		if (is_readable($this->snapshotPath) === true) {
			$raw = file_get_contents($this->snapshotPath);
			if (is_string($raw) === true) {
				$snapshot = json_decode($raw, true);
			}
		}

		if (is_array($snapshot) === false || is_array($snapshot['grounds'] ?? null) === false) {
			throw new RuntimeException('The Woo refusal grounds snapshot cannot be read.');
		}

		$grounds = array_values(
			array_filter(
				$snapshot['grounds'],
				static fn (mixed $ground): bool => is_array($ground) === true && ($withRetired === true || ($ground['status'] ?? '') === 'active')
			)
		);
		if ($grounds === []) {
			throw new RuntimeException('The Woo refusal grounds snapshot holds no active grounds.');
		}

		return ['source' => 'snapshot', 'version' => (string)($snapshot['version'] ?? ''), 'grounds' => $grounds];
	}//end fromSnapshot()
}//end class
