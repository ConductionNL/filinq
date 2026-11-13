<?php

/**
 * Refusal Grounds Resolver
 *
 * The Woo refusal grounds, read from dossiq when it is there, and otherwise
 * from the read-only snapshot dossiq ships.
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
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Grounds;

use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * The one place filinq reads refusal grounds.
 *
 * The list is dossiq's (`OCA\Dossiq\Woo\WooRefusalGrounds`). When dossiq is
 * not installed, or cannot read its register, the vendored snapshot answers,
 * marked as the snapshot with its version. An empty list is never an answer:
 * a snapshot that cannot be read throws, because "no grounds exist" would let
 * every ground look unknown.
 *
 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-one-resolver-reads-the-grounds-from-dossiq-or-from-a-read-only-snapshot-req-grd-001
 */
class RefusalGroundsResolver {

	/**
	 * dossiq's list class.
	 *
	 * @var string
	 */
	public const DOSSIQ_CLASS = 'OCA\Dossiq\Woo\WooRefusalGrounds';

	/**
	 * The keys every ground carries (dossiq design D-3).
	 *
	 * @var array<int, string>
	 */
	public const KEYS = ['id', 'code', 'article', 'paragraph', 'letter', 'label', 'description', 'parent', 'status', 'legalSource'];

	/**
	 * Constructor.
	 *
	 * @param ContainerInterface $container    Resolves dossiq's list class.
	 * @param IAppManager        $appManager   Whether dossiq is installed.
	 * @param LoggerInterface    $logger       Logger.
	 * @param string|null        $snapshotFile The snapshot; defaults to the vendored one.
	 */
	public function __construct(
		private readonly ContainerInterface $container,
		private readonly IAppManager $appManager,
		private readonly LoggerInterface $logger,
		private readonly ?string $snapshotFile = null,
	) {
	}//end __construct()

	/**
	 * The active grounds, with where they came from.
	 *
	 * @return array{source: string, version: string, grounds: list<array<string, mixed>>}
	 *
	 * @throws RuntimeException When neither dossiq nor the snapshot can answer.
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-one-resolver-reads-the-grounds-from-dossiq-or-from-a-read-only-snapshot-req-grd-001
	 */
	public function list(): array {
		$all = $this->listWithRetired();
		$all['grounds'] = array_values(array_filter($all['grounds'], static fn (array $ground): bool => ($ground['status'] ?? '') === 'active'));

		return $all;
	}//end list()

	/**
	 * Every ground, retired ones included, with where they came from.
	 *
	 * @return array{source: string, version: string, grounds: list<array<string, mixed>>}
	 *
	 * @throws RuntimeException When neither dossiq nor the snapshot can answer.
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-one-resolver-reads-the-grounds-from-dossiq-or-from-a-read-only-snapshot-req-grd-001
	 */
	public function listWithRetired(): array {
		$dossiq = $this->fromDossiq();
		if ($dossiq !== null) {
			return $dossiq;
		}

		return $this->fromSnapshot();
	}//end listWithRetired()

	/**
	 * One ground by code, retired ones included, or null when no ground carries it.
	 *
	 * @param string $code The code, such as 5.1.2.e.
	 *
	 * @return array<string, mixed>|null The ground.
	 *
	 * @throws RuntimeException When neither dossiq nor the snapshot can answer.
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-one-resolver-reads-the-grounds-from-dossiq-or-from-a-read-only-snapshot-req-grd-001
	 */
	public function byCode(string $code): ?array {
		$code = trim($code);
		foreach ($this->listWithRetired()['grounds'] as $ground) {
			if (($ground['code'] ?? null) === $code) {
				return $ground;
			}
		}

		return null;
	}//end byCode()

	/**
	 * dossiq's list, or null when dossiq is absent or cannot answer.
	 *
	 * @return array{source: string, version: string, grounds: list<array<string, mixed>>}|null
	 */
	private function fromDossiq(): ?array {
		try {
			if ($this->appManager->isInstalled('dossiq') === false || class_exists(self::DOSSIQ_CLASS) === false) {
				return null;
			}

			$grounds = $this->container->get(self::DOSSIQ_CLASS)->listWithRetired();
		} catch (Throwable $e) {
			// WooRefusalGroundsUnavailable, or dossiq failing in any other way:
			// the snapshot answers, and the log says why.
			$this->logger->warning(
				'RefusalGroundsResolver: dossiq could not answer, the snapshot is used',
				['exception' => $e->getMessage()]
			);
			return null;
		}

		if (is_array($grounds) === false || $grounds === []) {
			return null;
		}

		$version = '';
		if (defined(self::DOSSIQ_CLASS . '::LIST_VERSION') === true) {
			$version = (string)constant(self::DOSSIQ_CLASS . '::LIST_VERSION');
		}

		return ['source' => 'dossiq', 'version' => $version, 'grounds' => array_values($grounds)];
	}//end fromDossiq()

	/**
	 * The vendored snapshot.
	 *
	 * @return array{source: string, version: string, grounds: list<array<string, mixed>>}
	 *
	 * @throws RuntimeException When the snapshot cannot be read or holds no grounds.
	 */
	private function fromSnapshot(): array {
		$file = ($this->snapshotFile ?? __DIR__ . '/../../Settings/woo-refusal-grounds.snapshot.json');
		$raw = @file_get_contents($file);
		$decoded = json_decode((string)$raw, true);
		$grounds = ($decoded['grounds'] ?? null);
		if ($raw === false || is_array($grounds) === false || $grounds === []) {
			throw new RuntimeException('The Woo refusal grounds cannot be read: dossiq is not available and the snapshot cannot be read.');
		}

		return [
			'source' => 'snapshot',
			'version' => (string)($decoded['version'] ?? ''),
			'grounds' => array_values(array_filter($grounds, 'is_array')),
		];
	}//end fromSnapshot()
}//end class
