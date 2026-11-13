<?php

/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Extraction;

use OCA\Filinq\Service\Extraction\ExtractionDuplicateLookup;
use OCA\Filinq\Service\OpenRegisterResolver;
use OCA\Filinq\Service\SettingsService;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * REQ-ERP-003: an earlier extraction of the same invoice is found.
 *
 * @spec openspec/changes/extraction-receipt-photo-and-invoice-agent/tasks.md#3.1
 */
class ExtractionDuplicateLookupTest extends TestCase {

	/**
	 * Queries the object service received.
	 *
	 * @var list<array<string, mixed>>
	 */
	private array $queries = [];

	private function lookup(array|RuntimeException $rows, ?LoggerInterface $logger = null): ExtractionDuplicateLookup {
		// The real ObjectService::searchObjects() takes \$_rbac and
		// \$_multitenancy; the test stub does not, so this stand-in adds them.
		$objects = new class($rows, $this->queries) extends ObjectService {
			public function __construct(private array|RuntimeException $rows, private array &$queries) {
			}

			public function searchObjects(array $query = [], bool $_rbac = true, bool $_multitenancy = true) {
				$this->queries[] = ['query' => $query, 'rbac' => $_rbac, 'multitenancy' => $_multitenancy];
				if ($this->rows instanceof RuntimeException) {
					throw $this->rows;
				}

				return $this->rows;
			}
		};

		$settings = $this->createMock(SettingsService::class);
		$settings->method('getObjectService')->willReturn($objects);
		$settings->method('resolveFinancialExtractionBinding')->willReturn(['register' => 'filinq', 'schema' => 'financialExtraction']);

		return new ExtractionDuplicateLookup($settings, new OpenRegisterResolver(settingsService: $settings), $logger ?? $this->createMock(LoggerInterface::class));
	}

	private static function earlier(): array {
		return ['fields' => ['supplierIban' => 'NL91ABNA0417164300', 'invoiceNumber' => '2026-0412']];
	}

	public function testTheSameInvoiceSentTwiceIsFound(): void {
		$lookup = $this->lookup(rows: [['fields' => ['invoiceNumber' => '2026-0412', 'supplierKvk' => '99999999']], self::earlier()]);

		$this->assertTrue($lookup->hasEarlier(fields: ['supplierIban' => 'NL91 ABNA 0417 1643 00', 'invoiceNumber' => '2026-0412']));
		$this->assertSame(
			[['query' => ['@self' => ['register' => 'filinq', 'schema' => 'financialExtraction'], '_search' => '2026-0412', '_limit' => 100], 'rbac' => false, 'multitenancy' => false]],
			$this->queries
		);
	}

	public function testAnotherSupplierWithTheSameNumberIsNotADuplicate(): void {
		$lookup = $this->lookup(rows: [self::earlier()]);

		$this->assertFalse($lookup->hasEarlier(fields: ['supplierIban' => 'NL02RABO0123456789', 'invoiceNumber' => '2026-0412']));
	}

	public function testAnotherNumberFromTheSameSupplierIsNotADuplicate(): void {
		$lookup = $this->lookup(rows: [self::earlier()]);

		$this->assertFalse($lookup->hasEarlier(fields: ['supplierIban' => 'NL91ABNA0417164300', 'invoiceNumber' => '2026-0413']));
	}

	public function testWithoutAnInvoiceNumberOrIdentifierNothingIsLookedUp(): void {
		$lookup = $this->lookup(rows: [self::earlier()]);

		$this->assertFalse($lookup->hasEarlier(fields: ['supplierIban' => 'NL91ABNA0417164300', 'invoiceNumber' => null]));
		$this->assertFalse($lookup->hasEarlier(fields: ['supplierName' => 'Hostbaar', 'invoiceNumber' => '2026-0412']));
		$this->assertSame([], $this->queries);
	}

	public function testALookupThatCannotRunCountsAsADuplicate(): void {
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning');

		$this->assertTrue($this->lookup(rows: new RuntimeException('db down'), logger: $logger)->hasEarlier(fields: self::earlier()['fields']));
	}

	public function testResultObjectsAreReadThroughTheirSerialisation(): void {
		$row = new class(self::earlier()) implements \JsonSerializable {
			public function __construct(private array $data) {
			}

			public function jsonSerialize(): array {
				return $this->data;
			}
		};

		$this->assertTrue($this->lookup(rows: [$row])->hasEarlier(fields: self::earlier()['fields']));
	}
}
