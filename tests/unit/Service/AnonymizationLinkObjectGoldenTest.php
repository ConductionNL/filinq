<?php

/**
 * Golden anonymisation link objects as saved to OpenRegister.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/anonymization/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\AnonymizationPersistenceService;
use OCA\Filinq\Service\ConsentCrudService;
use OCA\Filinq\Service\ConsentService;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCP\App\IAppManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

/**
 * The lookup and the saved link object per run result, compared with JSON
 * recorded from the implementation before buildLinkObject() was split.
 *
 * @covers \OCA\Filinq\Service\AnonymizationPersistenceService
 * @uses   \OCA\Filinq\Service\OpenRegisterServiceLocator
 */
class AnonymizationLinkObjectGoldenTest extends TestCase {

	/**
	 * Existing search results and run results.
	 *
	 * @return array<string, array{0: mixed, 1: array<string, mixed>}>
	 */
	public static function runs(): array {
		$run = ['anonymizedFileId' => 12, 'anonymizedFileName' => 'brief_anonymized.pdf', 'anonymizedFilePath' => '/u/files/brief_anonymized.pdf', 'replacementCount' => 4];
		$verified = ['verdict' => 'clean', 'outputMode' => 'flattened', 'routesChecked' => ['text', 'ocr'], 'findings' => []];
		$prior = ['@self' => ['id' => 'link-1', 'register' => 'filinq', 'schema' => 'anonymizationLink'], 'sourceFileId' => 7, 'runCount' => 2, 'outputFormat' => 'docx', 'verificationVerdict' => 'leaking', 'note' => 'kept'];
		return [
			'a first run' => [[], $run],
			'no search result' => [null, $run],
			'a search result that is a string' => ['x', $run],
			'an existing array link' => [[$prior], $run],
			'an existing link without a count' => [[['sourceFileId' => 7, 'note' => 'kept']], $run],
			'an existing link with a string count' => [[['sourceFileId' => 7, 'runCount' => '5']], $run],
			'a search result that is a number' => [5, $run],
			'an existing link with a fractional count' => [[['sourceFileId' => 7, 'runCount' => 2.5]], $run],
			'an existing link of another file' => [[['sourceFileId' => 8, 'runCount' => 3]], $run],
			'an existing link without a file id' => [[['runCount' => '5']], $run],
			'a string replacement count' => [[], ['anonymizedFileId' => 12, 'replacementCount' => '4']],
			'an existing entity object' => [['entity'], $run],
			'an unreadable first result' => [[42], $run],
			'only the first result counts' => [[[], $prior], $run],
			'the bare minimum' => [[], ['anonymizedFileId' => '12']],
			'an upper case docx' => [[], ['anonymizedFileId' => 12, 'anonymizedFileName' => 'Brief.DOCX']],
			'odt' => [[], ['anonymizedFileId' => 12, 'anonymizedFileName' => 'brief.odt']],
			'txt' => [[], ['anonymizedFileId' => 12, 'anonymizedFileName' => 'brief.txt']],
			'html' => [[], ['anonymizedFileId' => 12, 'anonymizedFileName' => 'brief.html']],
			'an eml is no output format' => [[], ['anonymizedFileId' => 12, 'anonymizedFileName' => 'brief.eml']],
			'a name without an extension' => [[], ['anonymizedFileId' => 12, 'anonymizedFileName' => 'brief']],
			'an existing format and an unknown one' => [[$prior], ['anonymizedFileId' => 12, 'anonymizedFileName' => 'brief.md']],
			'a verified copy' => [[], $run + ['redactionVerification' => $verified]],
			'a leaking copy' => [[], $run + ['redactionVerification' => ['verdict' => 'leaking', 'routesChecked' => 'text', 'findings' => [['route' => 'text'], ['route' => 'ocr'], ['other' => 1]]]]],
			'an empty verification' => [[], $run + ['redactionVerification' => []]],
			'a verification that is not an array' => [[], $run + ['redactionVerification' => 'clean']],
			'a prior verdict and no verification' => [[$prior], $run],
			'structure preservation' => [[], $run + ['structurePreservation' => ['tags' => 3, 'lost' => ['table']], 'extra' => 'dropped']],
			'everything' => [[$prior], $run + ['redactionVerification' => $verified, 'structurePreservation' => ['tags' => 1]]],
		];
	}//end runs()

	/**
	 * The lookup and the saved object match the recorded ones.
	 *
	 * @param mixed $searchResult What the slug search answers.
	 * @param array<string, mixed> $resultInfo The run result.
	 *
	 * @dataProvider runs
	 *
	 * @return void
	 */
	public function testLinkMatchesTheRecordedOne(mixed $searchResult, array $resultInfo): void {
		if ($searchResult === ['entity']) {
			$searchResult = [new class {
				/**
				 * The entity payload.
				 *
				 * @return array<string, mixed>
				 */
				public function getObject(): array {
					return ['sourceFileId' => 7, 'runCount' => 3, 'anonymizedFileId' => 1];
				}

				/**
				 * The entity id.
				 *
				 * @return string
				 */
				public function getUuid(): string {
					return 'uuid-9';
				}
			},
			];
		}

		$objectService = new class($searchResult) {
			/** @var array<string, mixed> */
			public array $search = [];

			/** @var array<string, mixed>|null */
			public ?array $saved = null;

			/**
			 * @param mixed $result What the search answers.
			 */
			public function __construct(
				private mixed $result,
			) {
			}

			/**
			 * Record the lookup and answer the canned result.
			 *
			 * @param string $registerSlug The register slug.
			 * @param string $schemaSlug The schema slug.
			 * @param array<string, mixed> $filters The filters.
			 *
			 * @return mixed
			 */
			public function searchObjectsBySlug(string $registerSlug = '', string $schemaSlug = '', array $filters = []): mixed {
				$this->search = ['registerSlug' => $registerSlug, 'schemaSlug' => $schemaSlug, 'filters' => $filters];
				return $this->result;
			}

			/**
			 * Record the saved object.
			 *
			 * @param array<string, mixed> $object The object.
			 * @param string $register The register.
			 * @param string $schema The schema.
			 * @param bool $_rbac Whether RBAC applies.
			 * @param bool $_unowned Whether the object is unowned.
			 *
			 * @return array<string, mixed>
			 */
			public function saveObject(array $object = [], string $register = '', string $schema = '', bool $_rbac = true, bool $_unowned = false): array {
				$this->saved = $object;
				return ['@self' => ['id' => 'saved-1']];
			}
		};

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($objectService);
		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn(['openregister']);
		$service = new AnonymizationPersistenceService(
			logger: new NullLogger(),
			locator: new OpenRegisterServiceLocator($appManager, $container),
			consentCrud: $this->createMock(ConsentCrudService::class),
			consentService: $this->createMock(ConsentService::class),
		);

		$returned = $service->recordAnonymizationLink(fileId: 7, sourceNode: null, resultInfo: $resultInfo);

		$saved = $objectService->saved;
		foreach (['anonymizedAt', 'verifiedAt'] as $stamp) {
			if (isset($saved[$stamp]) === true) {
				$this->assertNotFalse(\DateTimeImmutable::createFromFormat(DATE_ATOM, $saved[$stamp]), $stamp);
				$saved[$stamp] = '<timestamp>';
			}
		}

		$result = ['search' => $objectService->search, 'saved' => $saved, 'linkId' => ($returned['anonymizationLinkId'] ?? null)];

		$path = __DIR__ . '/golden/anonymization-link-object.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $result;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $result);
	}//end testLinkMatchesTheRecordedOne()
	/**
	 * Search answers for pointing a link at its key, and the outcome.
	 *
	 * @return array<string, array{0: mixed, 1: string, 2?: int}>
	 */
	public static function mappingRefLookups(): array {
		$link = ['@self' => ['id' => 'link-1'], 'sourceFileId' => 7, 'runCount' => 2];
		return [
			'the link of the file' => [[$link], 'saved'],
			'a link with a string file id' => [[['sourceFileId' => '7']], 'saved'],
			'only the first row counts' => [[['sourceFileId' => 8], $link], 'refused'],
			'a link of another file' => [[['sourceFileId' => 8]], 'refused'],
			'a link without a file id' => [[['runCount' => 1]], 'refused'],
			'no rows' => [[], 'refused'],
			'no answer' => [null, 'refused'],
			'a number' => [5, 'refused'],
			'no rows for file 0' => [[], 'refused', 0],
			'a link without a file id for file 0' => [[['runCount' => 1]], 'saved', 0],
		];
	}//end mappingRefLookups()

	/**
	 * setMappingRef() looks the link up by slug and saves it with the key, or refuses.
	 *
	 * @param mixed $searchResult What the slug search answers.
	 * @param string $expected 'saved' or 'refused'.
	 * @param int $fileId The source file id.
	 *
	 * @dataProvider mappingRefLookups
	 *
	 * @return void
	 */
	public function testMappingRefLookup(mixed $searchResult, string $expected, int $fileId = 7): void {
		$objectService = new class($searchResult) {
			/** @var array<int, array<string, mixed>> */
			public array $calls = [];

			/**
			 * @param mixed $result What the search answers.
			 */
			public function __construct(
				private mixed $result,
			) {
			}

			/**
			 * Record the lookup and answer the canned result.
			 *
			 * @param string $registerSlug The register slug.
			 * @param string $schemaSlug The schema slug.
			 * @param array<string, mixed> $filters The filters.
			 *
			 * @return mixed
			 */
			public function searchObjectsBySlug(string $registerSlug = '', string $schemaSlug = '', array $filters = []): mixed {
				$this->calls[] = ['search' => [$registerSlug, $schemaSlug, $filters]];
				return $this->result;
			}

			/**
			 * Record the save.
			 *
			 * @param array<string, mixed> $object The object.
			 * @param string $register The register.
			 * @param string $schema The schema.
			 * @param bool $_rbac Whether RBAC applies.
			 * @param bool $_unowned Whether the object is unowned.
			 *
			 * @return array<string, mixed>
			 */
			public function saveObject(array $object = [], string $register = '', string $schema = '', bool $_rbac = true, bool $_unowned = false): array {
				$this->calls[] = ['save' => [$object, $register, $schema, $_rbac, $_unowned]];
				return $object;
			}
		};

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($objectService);
		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn(['openregister']);
		$service = new AnonymizationPersistenceService(
			logger: new NullLogger(),
			locator: new OpenRegisterServiceLocator($appManager, $container),
			consentCrud: $this->createMock(ConsentCrudService::class),
			consentService: $this->createMock(ConsentService::class),
		);

		$outcome = 'saved';
		try {
			$service->setMappingRef(fileId: $fileId, mappingRef: 'map-uuid');
		} catch (\RuntimeException $e) {
			$outcome = 'refused';
			$this->assertSame('No anonymisation link for file ' . $fileId . ' to point at its key.', $e->getMessage());
		}

		$this->assertSame($expected, $outcome);
		$this->assertSame(['search' => ['filinq', 'anonymizationLink', ['sourceFileId' => $fileId]]], $objectService->calls[0]);
		if ($expected === 'refused') {
			$this->assertCount(1, $objectService->calls);
			return;
		}

		$saved = ($searchResult[0] + ['mappingRef' => 'map-uuid']);
		$saved['mappingRef'] = 'map-uuid';
		$this->assertSame(['save' => [$saved, 'filinq', 'anonymizationLink', false, false]], $objectService->calls[1]);
	}//end testMappingRefLookup()
}//end class
