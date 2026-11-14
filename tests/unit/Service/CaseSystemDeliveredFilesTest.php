<?php
/**
 * Unit tests for CaseSystemDeliveredFiles: one query for a page of files,
 * and only asked, written-back files in the answer.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\CaseSystemDeliveredFiles;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CaseSystemDeliveredFiles::class)]
class CaseSystemDeliveredFilesTest extends TestCase {

	private function delivered(ObjectService $objects): CaseSystemDeliveredFiles {
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);

		return new CaseSystemDeliveredFiles($resolver);

	}//end delivered()

	public function testOneQueryAsksForThePageAndOnlyAskedWrittenBackFilesComeBack(): void {
		$calls   = [];
		$entity  = new class {
			public function jsonSerialize(): array {
				return ['fileId' => 41, 'deliveryStatus' => 'written_back'];
			}
		};
		$objects = $this->getMockBuilder(ObjectService::class)->onlyMethods(['findAll'])->getMock();
		$objects->expects($this->once())->method('findAll')->willReturnCallback(
			static function (array $config=[], bool $_rbac=true) use (&$calls, $entity): array {
				$calls[] = ['config' => $config, 'rbac' => $_rbac];
				return [
					['fileId' => 12, 'deliveryStatus' => 'written_back'],
					['fileId' => '7', 'deliveryStatus' => 'written_back'],
					['fileId' => 99, 'deliveryStatus' => 'written_back'],
					['fileId' => 30, 'deliveryStatus' => 'writeback_failed'],
					['deliveryStatus' => 'written_back'],
					$entity,
				];
			}
		);

		$this->assertSame([7, 12, 41], $this->delivered($objects)->deliveredFileIds([7, 12, 30, 7, 41]));
		$this->assertSame(
			['register' => 'filinq', 'schema' => 'caseSystemDelivery', 'deliveryStatus' => 'written_back', 'fileId' => [7, 12, 30, 41]],
			$calls[0]['config']['filters']
		);
		$this->assertSame(20, $calls[0]['config']['limit']);
		$this->assertFalse($calls[0]['rbac']);

	}//end testOneQueryAsksForThePageAndOnlyAskedWrittenBackFilesComeBack()

	public function testNoFilesMeansNoQuery(): void {
		$objects = $this->getMockBuilder(ObjectService::class)->onlyMethods(['findAll'])->getMock();
		$objects->expects($this->never())->method('findAll');

		$this->assertSame([], $this->delivered($objects)->deliveredFileIds([]));

	}//end testNoFilesMeansNoQuery()
}//end class
