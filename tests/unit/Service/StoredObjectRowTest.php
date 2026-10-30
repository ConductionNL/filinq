<?php

/**
 * Unit tests for the stored-object row normalisation shared by the repositories.
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

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;

/**
 * Pins what each repository makes of an OpenRegister row, per variant.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Service\Contract\ContractRepository
 * @covers \OCA\Filinq\Service\PrintJobRepository
 * @covers \OCA\Filinq\Service\VeraPdf\ConformanceReportRepository
 * @covers \OCA\Filinq\Service\SigningEnvelope\SigningEnvelopeRepository
 * @covers \OCA\Filinq\Service\Ocr\OcrResultRepository
 * @covers \OCA\Filinq\Service\BulkSigning\BulkSigningBatchRepository
 * @covers \OCA\Filinq\Service\MergeJobRepository
 * @covers \OCA\Filinq\Service\Redaction\RedactionReviewMarkRepository
 * @covers \OCA\Filinq\Service\ScanBatchRepository
 * @covers \OCA\Filinq\Service\DocumentReviewService
 * @covers \OCA\Filinq\Service\Redaction\DownloadAgreementRepository
 * @covers \OCA\Filinq\Service\Redaction\AnonymizationLinkReader
 * @covers \OCA\Filinq\Service\LegalHold\LegalHoldCaseRepository
 * @covers \OCA\Filinq\Service\SubjectErasure\SubjectErasureStore
 * @covers \OCA\Filinq\Service\Pseudonymisation\PseudonymMapRepository
 */
class StoredObjectRowTest extends TestCase {

	/**
	 * Each repository and the normalisation variant it implements.
	 */
	public const REPOSITORIES = [
		\OCA\Filinq\Service\Contract\ContractRepository::class => 'fields',
		\OCA\Filinq\Service\PrintJobRepository::class => 'fields',
		\OCA\Filinq\Service\VeraPdf\ConformanceReportRepository::class => 'fields',
		\OCA\Filinq\Service\SigningEnvelope\SigningEnvelopeRepository::class => 'fields',
		\OCA\Filinq\Service\Ocr\OcrResultRepository::class => 'fields',
		\OCA\Filinq\Service\BulkSigning\BulkSigningBatchRepository::class => 'fields',
		\OCA\Filinq\Service\MergeJobRepository::class => 'fieldsKeepingSelf',
		\OCA\Filinq\Service\Redaction\RedactionReviewMarkRepository::class => 'fieldsKeepingSelf',
		\OCA\Filinq\Service\ScanBatchRepository::class => 'fieldsKeepingSelf',
		\OCA\Filinq\Service\DocumentReviewService::class => 'fieldsKeepingSelf',
		\OCA\Filinq\Service\Redaction\DownloadAgreementRepository::class => 'payload',
		\OCA\Filinq\Service\Redaction\AnonymizationLinkReader::class => 'payload',
		\OCA\Filinq\Service\LegalHold\LegalHoldCaseRepository::class => 'entityFieldsOrBlankUuid',
		\OCA\Filinq\Service\SubjectErasure\SubjectErasureStore::class => 'entityFieldsOrBlankUuid',
		\OCA\Filinq\Service\Pseudonymisation\PseudonymMapRepository::class => 'entityFields',
	];

	/**
	 * The rows every variant is run over.
	 *
	 * @return array<string, mixed>
	 */
	public static function inputs(): array {
		$wrapped = ['object' => ['a' => 1, '@self' => ['x' => 1]], '@self' => ['id' => 's1'], 'id' => 'i1'];
		return [
			'plain with uuid' => ['uuid' => 'u1', 'a' => 1],
			'wrapped' => $wrapped,
			'self id' => ['a' => 1, '@self' => ['id' => 's2'], 'id' => 'i2'],
			'only id' => ['a' => 1, 'id' => 7],
			'no id' => ['a' => 1],
			'field uuid wins' => ['object' => ['uuid' => 'f'], 'uuid' => 'd'],
			'data uuid next' => ['object' => [], 'uuid' => 'd'],
			'object not an array' => ['object' => 'x', 'uuid' => 'u3'],
			'self not an array' => ['a' => 1, '@self' => 'z'],
			'serializable' => new class($wrapped) implements \JsonSerializable {
				public function __construct(
					private array $data,
				) {
				}

				public function jsonSerialize(): mixed {
					return $this->data;
				}
			},
			'serializes to scalar' => new class implements \JsonSerializable {
				public function jsonSerialize(): mixed {
					return 'flat';
				}
			},
			'entity' => new class implements \JsonSerializable {
				public function getObject(): array {
					return ['b' => 2, 'uuid' => 'stale'];
				}

				public function getUuid(): string {
					return 'e1';
				}

				public function jsonSerialize(): mixed {
					return ['rendered' => true, 'uuid' => 'r1'];
				}
			},
			'string' => 'str',
			'null' => null,
			'plain object' => new \stdClass(),
		];
	}

	/**
	 * What each variant makes of each input, recorded from the repositories.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function expected(): array {
		$json = <<<'JSON'
		{
			"fields": {
				"plain with uuid": {"uuid":"u1","a":1},
				"wrapped": {"a":1,"uuid":"s1"},
				"self id": {"a":1,"id":"i2","uuid":"s2"},
				"only id": {"a":1,"id":7,"uuid":"7"},
				"no id": {"a":1,"uuid":""},
				"field uuid wins": {"uuid":"f"},
				"data uuid next": {"uuid":"d"},
				"object not an array": {"object":"x","uuid":"u3"},
				"self not an array": {"a":1,"uuid":""},
				"serializable": {"a":1,"uuid":"s1"},
				"serializes to scalar": {},
				"entity": {"rendered":true,"uuid":"r1"},
				"string": {},
				"null": {},
				"plain object": {}
			},
			"fieldsKeepingSelf": {
				"plain with uuid": {"uuid":"u1","a":1},
				"wrapped": {"a":1,"@self":{"x":1},"uuid":"s1"},
				"self id": {"a":1,"@self":{"id":"s2"},"id":"i2","uuid":"s2"},
				"only id": {"a":1,"id":7,"uuid":"7"},
				"no id": {"a":1,"uuid":""},
				"field uuid wins": {"uuid":"f"},
				"data uuid next": {"uuid":"d"},
				"object not an array": {"object":"x","uuid":"u3"},
				"self not an array": {"a":1,"@self":"z","uuid":""},
				"serializable": {"a":1,"@self":{"x":1},"uuid":"s1"},
				"serializes to scalar": {},
				"entity": {"rendered":true,"uuid":"r1"},
				"string": {},
				"null": {},
				"plain object": {}
			},
			"payload": {
				"plain with uuid": {"uuid":"u1","a":1},
				"wrapped": {"a":1,"@self":{"x":1}},
				"self id": {"a":1,"@self":{"id":"s2"},"id":"i2"},
				"only id": {"a":1,"id":7},
				"no id": {"a":1},
				"field uuid wins": {"uuid":"f"},
				"data uuid next": {},
				"object not an array": {"object":"x","uuid":"u3"},
				"self not an array": {"a":1,"@self":"z"},
				"serializable": {"a":1,"@self":{"x":1}},
				"serializes to scalar": {},
				"entity": {"rendered":true,"uuid":"r1"},
				"string": {},
				"null": {},
				"plain object": {}
			},
			"entityFieldsOrBlankUuid": {
				"plain with uuid": {"uuid":"u1","a":1},
				"wrapped": {"a":1,"uuid":"s1"},
				"self id": {"a":1,"id":"i2","uuid":"s2"},
				"only id": {"a":1,"id":7,"uuid":"7"},
				"no id": {"a":1,"uuid":""},
				"field uuid wins": {"uuid":"f"},
				"data uuid next": {"uuid":"d"},
				"object not an array": {"object":"x","uuid":"u3"},
				"self not an array": {"a":1,"uuid":""},
				"serializable": {"a":1,"uuid":"s1"},
				"serializes to scalar": {"uuid":""},
				"entity": {"b":2,"uuid":"e1"},
				"string": {"uuid":""},
				"null": {"uuid":""},
				"plain object": {"uuid":""}
			},
			"entityFields": {
				"plain with uuid": {"uuid":"u1","a":1},
				"wrapped": {"a":1,"uuid":"s1"},
				"self id": {"a":1,"id":"i2","uuid":"s2"},
				"only id": {"a":1,"id":7,"uuid":"7"},
				"no id": {"a":1,"uuid":""},
				"field uuid wins": {"uuid":"f"},
				"data uuid next": {"uuid":"d"},
				"object not an array": {"object":"x","uuid":"u3"},
				"self not an array": {"a":1,"uuid":""},
				"serializable": {"a":1,"uuid":"s1"},
				"serializes to scalar": {},
				"entity": {"b":2,"uuid":"e1"},
				"string": {},
				"null": {},
				"plain object": {}
			}
		}
		JSON;
		return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * @return array<string, array{0: class-string, 1: string}>
	 */
	public static function repositories(): array {
		$cases = [];
		foreach (self::REPOSITORIES as $class => $variant) {
			$cases[substr(strrchr($class, '\\'), 1)] = [$class, $variant];
		}
		return $cases;
	}

	/**
	 * @dataProvider repositories
	 *
	 * @param class-string $class The repository.
	 * @param string $variant The variant it implements.
	 */
	public function testRepositoryNormalisesEveryRowAsItsVariant(string $class, string $variant): void {
		$method = new \ReflectionMethod($class, 'normalise');
		$repository = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
		foreach (self::inputs() as $name => $input) {
			$this->assertSame(self::expected()[$variant][$name], $method->invoke($repository, $input), $variant . ': ' . $name);
		}
	}
}
