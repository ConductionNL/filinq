<?php

/**
 * Unit tests for ContractTermSuggestionService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Contract
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#4-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Contract;

require_once __DIR__ . '/ContractSchemaValidation.php';
require_once __DIR__ . '/InMemoryContracts.php';

use OCA\Filinq\Service\Contract\ContractDocumentText;
use OCA\Filinq\Service\Contract\ContractTermSuggestionService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;

/**
 * Suggestions are stored as proposals; contract fields never move; the toggle
 * switches the pass off.
 */
class ContractTermSuggestionServiceTest extends TestCase {
	use ContractSchemaValidation;

	/**
	 * Build the service over one readable text file (id 42).
	 *
	 * @param InMemoryContracts $store  The store.
	 * @param string            $toggle The app setting value.
	 *
	 * @return ContractTermSuggestionService The service.
	 */
	private function service(InMemoryContracts $store, string $toggle): ContractTermSuggestionService {
		$file = $this->createMock(File::class);
		$file->method('getSize')->willReturn(200);
		$file->method('getMimeType')->willReturn('text/plain');
		$file->method('getContent')->willReturn('Einddatum: 31-12-2028. Contractwaarde € 86.000,00.');
		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->willReturnCallback(static fn (int $id): array => ($id === 42) ? [$file] : []);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willReturn($folder);
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default): string => ($key === ContractTermSuggestionService::TOGGLE) ? $toggle : $default
		);

		return new ContractTermSuggestionService(repository: $store, text: new ContractDocumentText(rootFolder: $root), appConfig: $config);

	}//end service()

	/**
	 * The pass proposes, stores valid suggestion records, and leaves the
	 * contract's own fields empty. Running it again adds nothing new.
	 *
	 * @return void
	 */
	public function testSuggestionsAreProposalsOnly(): void {
		$store = new InMemoryContracts();
		$uuid = $store->seed(['title' => 'Schoonmaak', 'status' => 'active', 'documents' => ['42', '43', 'generated:abc']]);
		$service = $this->service(store: $store, toggle: '1');

		$result = $service->suggest(contract: $store->find(uuid: $uuid), userId: 'alice');

		$this->assertSame(3, $result['added']);
		$stored = $store->rows[$uuid];
		$this->assertArrayNotHasKey('endDate', $stored);
		$this->assertArrayNotHasKey('value', $stored);
		$this->assertSame(['endDate', 'value', 'currency'], array_column($stored['keyTermSuggestions'], 'field'));
		$this->assertSame(['proposed'], array_values(array_unique(array_column($stored['keyTermSuggestions'], 'status'))));
		$this->assertSame('file:42', $stored['keyTermSuggestions'][0]['source']);
		$this->assertValidContract($store->writes[0]);

		$again = $service->suggest(contract: $store->find(uuid: $uuid), userId: 'alice');
		$this->assertSame(0, $again['added']);
		$this->assertCount(1, $store->writes);

	}//end testSuggestionsAreProposalsOnly()

	/**
	 * Switched off, nothing runs and nothing is written.
	 *
	 * @return void
	 */
	public function testTheToggleSwitchesThePassOff(): void {
		$store = new InMemoryContracts();
		$uuid = $store->seed(['title' => 'Schoonmaak', 'status' => 'active', 'documents' => ['42']]);
		$service = $this->service(store: $store, toggle: '0');

		$result = $service->suggest(contract: $store->find(uuid: $uuid), userId: 'alice');

		$this->assertFalse($result['enabled']);
		$this->assertSame(0, $result['added']);
		$this->assertSame([], $store->writes);
		$this->assertFalse($service->isEnabled());

	}//end testTheToggleSwitchesThePassOff()
}//end class
