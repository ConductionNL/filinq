<?php

/**
 * In-memory doubles for the bulk-send tests
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\BulkSigning
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service\BulkSigning;

use OCA\Filinq\Service\BulkSigning\BulkSigningBatchRepository;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;

/**
 * A batch repository over an in-memory ObjectService.
 */
trait BulkSigningDoubles {

	/**
	 * Stored batches by uuid.
	 *
	 * @var array<string, array>
	 */
	protected array $batches = [];

	/**
	 * Every payload written, in order.
	 *
	 * @var list<array>
	 */
	protected array $batchWrites = [];

	/**
	 * Build the repository over the in-memory store.
	 *
	 * @return BulkSigningBatchRepository
	 */
	protected function batchRepository(): BulkSigningBatchRepository {
		$objects = $this->createMock(ObjectService::class);
		$objects->method('saveObject')->willReturnCallback(
			function (array $object = [], string $register = '', string $schema = '', ?string $uuid = null) {
				$this->batchWrites[] = $object;
				$uuid = $uuid ?? ('batch-' . (count($this->batches) + 1));
				$this->batches[$uuid] = array_merge($object, ['uuid' => $uuid]);

				return $this->batches[$uuid];
			}
		);
		$objects->method('find')->willReturnCallback(
			fn (string $id = '') => $this->batches[$id] ?? null
		);
		$objects->method('searchObjectsBySlug')->willReturnCallback(
			fn (string $registerSlug, string $schemaSlug, array $filters = []) => array_values(
				array_filter(
					$this->batches,
					static fn (array $row): bool => isset($filters['createdBy']) === false || ($row['createdBy'] ?? null) === $filters['createdBy']
				)
			)
		);

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($objects);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);

		return new BulkSigningBatchRepository(new DocumentObjectServiceResolver($container, $apps));

	}//end batchRepository()
}//end trait
