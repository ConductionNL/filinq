<?php

/**
 * In-memory OpenRegister rows and app data for the print job tests
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\PrintJobFileStore;
use OCA\Filinq\Service\PrintJobRepository;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\InMemoryFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use Psr\Container\ContainerInterface;

/**
 * Doubles with the real method names of ObjectService and IAppData, backed by
 * arrays the tests read back.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
trait PrintJobDoubles {

	/**
	 * Stored printJob rows by uuid.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	protected array $rows = [];

	/**
	 * Every payload handed to saveObject, in order.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected array $written = [];

	/**
	 * Stored files by name.
	 *
	 * @var array<string, string>
	 */
	protected array $stored = [];

	/**
	 * A repository over an in-memory ObjectService.
	 *
	 * @param bool $failWrites Whether saveObject throws
	 *
	 * @return PrintJobRepository
	 */
	protected function printJobRepository(bool $failWrites = false): PrintJobRepository {
		$objects = $this->createMock(ObjectService::class);
		$objects->method('saveObject')->willReturnCallback(
			function (array $object = [], string $register = '', string $schema = '', ?string $uuid = null) use ($failWrites) {
				if ($failWrites === true) {
					throw new \RuntimeException('Schema printJob not found');
				}

				$this->written[] = $object;
				$uuid = $uuid ?? ('job-' . (count($this->rows) + 1));
				$this->rows[$uuid] = array_merge($object, ['uuid' => $uuid]);

				return $this->rows[$uuid];
			}
		);
		$objects->method('find')->willReturnCallback(
			fn (string $id = '') => $this->rows[$id] ?? null
		);
		$objects->method('searchObjectsBySlug')->willReturnCallback(
			fn (string $registerSlug, string $schemaSlug, array $filters = []) => array_values(
				array_filter(
					$this->rows,
					static fn (array $row): bool => ($row['requestedBy'] ?? null) === ($filters['requestedBy'] ?? null)
				)
			)
		);

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($objects);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);

		return new PrintJobRepository(new DocumentObjectServiceResolver($container, $apps));

	}//end printJobRepository()

	/**
	 * A file store over an in-memory app data folder.
	 *
	 * @return PrintJobFileStore
	 */
	protected function printJobFileStore(): PrintJobFileStore {
		$folder = $this->createMock(ISimpleFolder::class);
		$folder->method('newFile')->willReturnCallback(
			function (string $name, $content = null) {
				$this->stored[$name] = (string) $content;
				return new InMemoryFile($name, (string) $content);
			}
		);
		$folder->method('getFile')->willReturnCallback(
			function (string $name) {
				if (isset($this->stored[$name]) === false) {
					throw new NotFoundException($name);
				}

				return new InMemoryFile($name, $this->stored[$name]);
			}
		);

		$appData = $this->createMock(IAppData::class);
		$appData->method('getFolder')->with(PrintJobFileStore::FOLDER)->willReturn($folder);

		return new PrintJobFileStore($appData);

	}//end printJobFileStore()
}//end trait
