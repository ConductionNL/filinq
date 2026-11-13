<?php

/**
 * Reading and storing PDF/UA-1 accessibility conformance reports.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\VeraPdf
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\VeraPdf;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use OCA\Filinq\Service\VeraPdf\AccessibilityConformanceReportRepository;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\VeraPdf\AccessibilityConformanceReportRepository
 */
class AccessibilityConformanceReportRepositoryTest extends TestCase {

	/**
	 * The OpenRegister object service.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objects;

	private AccessibilityConformanceReportRepository $repository;

	protected function setUp(): void {
		$this->objects = $this->createMock(ObjectService::class);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objects);
		$this->repository = new AccessibilityConformanceReportRepository($resolver);

	}//end setUp()

	/**
	 * A stored row as OpenRegister serialises it.
	 *
	 * @param string               $uuid   The uuid.
	 * @param array<string, mixed> $fields The fields.
	 *
	 * @return array<string, mixed> The row.
	 */
	private function row(string $uuid, array $fields): array {
		return ['object' => $fields, '@self' => ['id' => $uuid]] + $fields;

	}//end row()

	public function testTheReportIsSearchedInItsOwnSchemaAndMatchedOnTheFile(): void {
		$this->objects->expects($this->once())->method('searchObjectsBySlug')
			->with(IntakeRepository::REGISTER, AccessibilityConformanceReportRepository::SCHEMA, ['fileId' => 7])
			->willReturn([$this->row('other', ['fileId' => 8]), $this->row('mine', ['fileId' => 7, 'compliant' => true])]);

		$report = $this->repository->findForFile(fileId: 7);

		$this->assertSame(7, $report['fileId']);
		$this->assertTrue($report['compliant']);

	}//end testTheReportIsSearchedInItsOwnSchemaAndMatchedOnTheFile()

	public function testAFileWithoutAReportHasNone(): void {
		$this->objects->method('searchObjectsBySlug')->willReturn([$this->row('other', ['fileId' => 8])]);

		$this->assertNull($this->repository->findForFile(fileId: 7));

	}//end testAFileWithoutAReportHasNone()

	public function testAnUnreadableRegisterIsAnError(): void {
		$this->objects->method('searchObjectsBySlug')->willThrowException(new \Exception('db down'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The accessibility conformance report could not be read: db down');
		$this->repository->findForFile(fileId: 7);

	}//end testAnUnreadableRegisterIsAnError()

	public function testANewReportIsCreatedWithoutAUuid(): void {
		$this->objects->method('searchObjectsBySlug')->willReturn([]);
		$this->objects->expects($this->once())->method('saveObject')
			->with(['fileId' => 7, 'compliant' => false], IntakeRepository::REGISTER, AccessibilityConformanceReportRepository::SCHEMA)
			->willReturn($this->row('new', ['fileId' => 7, 'compliant' => false]));

		$this->assertSame(7, $this->repository->save(report: ['uuid' => 'ignored', 'fileId' => 7, 'compliant' => false])['fileId']);

	}//end testANewReportIsCreatedWithoutAUuid()

	public function testARefusedSaveIsAnError(): void {
		$this->objects->method('searchObjectsBySlug')->willReturn([]);
		$this->objects->method('saveObject')->willThrowException(new \Exception('invalid'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Could not store the accessibility conformance report: invalid');
		$this->repository->save(report: ['fileId' => 7]);

	}//end testARefusedSaveIsAnError()
}//end class
