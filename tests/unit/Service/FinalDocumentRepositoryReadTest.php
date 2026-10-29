<?php

/**
 * Final document repository: the dossier-by-file map and the domain read.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use JsonSerializable;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\FinalDocumentRepository;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * Locks which files a dossier claims and which records sit in a domain.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FinalDocumentRepositoryReadTest extends TestCase {

	/**
	 * How often the register was read.
	 *
	 * @var integer
	 */
	private int $reads = 0;

	/**
	 * A repository over these rows, or over a register that fails to read.
	 *
	 * @param array<int, mixed>|null $rows The rows, or null for a failing read.
	 *
	 * @return FinalDocumentRepository The repository under test.
	 */
	private function repository(?array $rows): FinalDocumentRepository {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('searchObjectsBySlug')->willReturnCallback(
			function () use ($rows): array {
				$this->reads++;
				if ($rows === null) {
					throw new RuntimeException('register down');
				}

				return $rows;
			}
		);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		return new FinalDocumentRepository($resolver, new NullLogger());
	}//end repository()

	/**
	 * A row as an entity that serialises to these data.
	 *
	 * @param array<string, mixed> $data The serialised data.
	 *
	 * @return JsonSerializable The entity.
	 */
	private function entity(array $data): JsonSerializable {
		return new class($data) implements JsonSerializable {
			/**
			 * Hold the data.
			 *
			 * @param array<string, mixed> $data The serialised data.
			 */
			public function __construct(private readonly array $data) {
			}

			/**
			 * The data.
			 *
			 * @return array<string, mixed>
			 */
			public function jsonSerialize(): array {
				return $this->data;
			}
		};
	}//end entity()

	/**
	 * Every member shape, every skipped dossier, and first-dossier-wins.
	 *
	 * @return void
	 */
	public function testDossiersByFileGolden(): void {
		$rows = [
			['uuid' => 'd-1', 'name' => 'Bouw', '@self' => ['files' => [11, '12', ['id' => 13], ['id' => 0], ['name' => 'x']]], 'documents' => ['14', 15]],
			['uuid' => 'd-2', 'name' => 'Sloop', '@self' => ['files' => [11, 16]], 'documents' => [['id' => '17'], -3, 'abc']],
			['uuid' => 'd-3', 'name' => '', '@self' => ['files' => [20]]],
			['uuid' => 'd-4', 'name' => 42, '@self' => ['files' => [21]]],
			['name' => 'Zonder uuid', '@self' => ['files' => [22]]],
			['@self' => ['uuid' => 'd-5', 'files' => [23]], 'name' => 'Uuid op self'],
			['object' => ['uuid' => 'd-6', 'name' => 'Ingepakt', 'documents' => [24]], '@self' => ['files' => [25]]],
			$this->entity(data: ['uuid' => 'd-7', 'name' => 'Entiteit', '@self' => ['files' => [26]], 'documents' => 27]),
			['id' => 'd-8', 'name' => 'Alleen id', '@self' => 'geen lijst', 'documents' => [28]],
			'geen rij',
			['uuid' => 'd-9', 'name' => 'Leeg'],
		];

		$this->assertSame(
			expected: [
				11 => ['id' => 'd-1', 'name' => 'Bouw'],
				12 => ['id' => 'd-1', 'name' => 'Bouw'],
				13 => ['id' => 'd-1', 'name' => 'Bouw'],
				14 => ['id' => 'd-1', 'name' => 'Bouw'],
				15 => ['id' => 'd-1', 'name' => 'Bouw'],
				16 => ['id' => 'd-2', 'name' => 'Sloop'],
				17 => ['id' => 'd-2', 'name' => 'Sloop'],
				23 => ['id' => 'd-5', 'name' => 'Uuid op self'],
				25 => ['id' => 'd-6', 'name' => 'Ingepakt'],
				24 => ['id' => 'd-6', 'name' => 'Ingepakt'],
				26 => ['id' => 'd-7', 'name' => 'Entiteit'],
				27 => ['id' => 'd-7', 'name' => 'Entiteit'],
				28 => ['id' => 'd-8', 'name' => 'Alleen id'],
			],
			actual: $this->repository(rows: $rows)->dossiersByFile()
		);
	}//end testDossiersByFileGolden()

	/**
	 * An unreadable dossier register answers an empty map.
	 *
	 * @return void
	 */
	public function testDossiersByFileOnAFailedRead(): void {
		$this->assertSame(expected: [], actual: $this->repository(rows: null)->dossiersByFile());
	}//end testDossiersByFileOnAFailedRead()

	/**
	 * Only records naming all three of register, schema and id are in the domain.
	 *
	 * @return void
	 */
	public function testFindByDomainGolden(): void {
		$rows = [
			['uuid' => 'r-1', 'domains' => [['register' => 'zaken', 'schema' => 'zaak', 'id' => 'z-1']]],
			['uuid' => 'r-2', 'domains' => [['register' => 'zaken', 'schema' => 'zaak', 'id' => 'z-2']]],
			['uuid' => 'r-3', 'domains' => [['register' => 'zaken', 'schema' => 'besluit', 'id' => 'z-1']]],
			['uuid' => 'r-4', 'domains' => ['zaken', ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'z-1', 'extra' => true]]],
			['uuid' => 'r-5', 'domains' => 'zaken/zaak/z-1'],
			['uuid' => 'r-6'],
			['uuid' => 'r-7', 'domains' => [['register' => 'zaken', 'schema' => 'zaak']]],
			['object' => ['uuid' => 'r-8', 'domains' => [['register' => 'zaken', 'schema' => 'zaak', 'id' => 'z-1']]]],
			$this->entity(data: ['uuid' => 'r-9', 'domains' => [['schema' => 'zaak', 'id' => 'z-1', 'register' => 'zaken']]]),
		];

		$found = $this->repository(rows: $rows)->findByDomain(domain: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'z-1', 'label' => 'Zaak 1']);

		$this->assertSame(expected: ['r-1', 'r-4', 'r-8', 'r-9'], actual: array_column($found, 'uuid'));
		$this->assertSame(expected: $rows[0], actual: $found[0]);
	}//end testFindByDomainGolden()

	/**
	 * A numeric id matches its text form.
	 *
	 * @return void
	 */
	public function testFindByDomainComparesAsText(): void {
		$rows = [['uuid' => 'r-1', 'domains' => [['register' => 'zaken', 'schema' => 'zaak', 'id' => 7]]]];

		$found = $this->repository(rows: $rows)->findByDomain(domain: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 7]);

		$this->assertSame(expected: ['r-1'], actual: array_column($found, 'uuid'));
	}//end testFindByDomainComparesAsText()

	/**
	 * An incomplete domain finds nothing and does not read the register.
	 *
	 * @return void
	 */
	public function testFindByDomainNeedsAllThreeParts(): void {
		$repository = $this->repository(rows: [['uuid' => 'r-1', 'domains' => [['register' => 'zaken', 'schema' => 'zaak', 'id' => '']]]]);

		$this->assertSame(expected: [], actual: $repository->findByDomain(domain: ['register' => 'zaken', 'schema' => 'zaak']));
		$this->assertSame(expected: [], actual: $repository->findByDomain(domain: ['register' => '', 'schema' => 'zaak', 'id' => 'z-1']));
		$this->assertSame(expected: [], actual: $repository->findByDomain(domain: ['register' => 'zaken', 'id' => 'z-1']));
		$this->assertSame(expected: 0, actual: $this->reads);
	}//end testFindByDomainNeedsAllThreeParts()

	/**
	 * An unreadable register finds nothing.
	 *
	 * @return void
	 */
	public function testFindByDomainOnAFailedRead(): void {
		$this->assertSame(
			expected: [],
			actual: $this->repository(rows: null)->findByDomain(domain: ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'z-1'])
		);
	}//end testFindByDomainOnAFailedRead()
}//end class
