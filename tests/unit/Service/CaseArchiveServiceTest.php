<?php

/**
 * Unit tests for CaseArchiveService
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
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\CaseArchiveService;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\FlatFileListService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Asserts that nothing is dropped silently: a file over the ceiling and a file
 * the person may not read are both in the manifest, with their reason.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class CaseArchiveServiceTest extends TestCase {

	/**
	 * Jobs the fake OpenRegister was asked to store.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $written = [];

	/**
	 * The case every test bundles.
	 *
	 * @var array<string, string>
	 */
	private array $domain = ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1'];

	/**
	 * Build the service over a flat list and the records behind it.
	 *
	 * @param array<int, array<string, mixed>> $rows The readable files.
	 * @param array<int, array<string, mixed>> $records Every record of the object, readable or not.
	 *
	 * @return CaseArchiveService The service under test.
	 */
	private function service(array $rows, array $records): CaseArchiveService {
		$files = $this->createMock(FlatFileListService::class);
		$files->method('listFor')->willReturn(
			['results' => $rows, 'total' => count($rows), 'page' => 1, 'pages' => 1]
		);
		$files->method('recordsFor')->willReturn($records);

		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('saveObject')->willReturnCallback(
			function (...$arguments): array {
				$this->written[] = ($arguments[0] ?? []);

				return ($arguments[0] ?? []);
			}
		);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('anna');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		return new CaseArchiveService(
			$files,
			$resolver,
			$session,
			$this->createMock(LoggerInterface::class)
		);

	}//end service()

	/**
	 * One row of the flat list.
	 *
	 * @param int $fileId The file id.
	 * @param string $name The file name.
	 * @param int $size Its size in bytes.
	 *
	 * @return array<string, mixed> The row.
	 */
	private function row(int $fileId, string $name, int $size): array {
		return [
			'fileId' => $fileId,
			'name' => $name,
			'size' => $size,
			'record' => ['uuid' => 'record-' . $fileId, 'name' => $name, 'status' => 'draft'],
		];

	}//end row()

	/**
	 * Twenty readable files make one bundle of twenty, with a manifest.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testOneBundleForTheBezwaarcommissie(): void {
		$rows = [];
		$records = [];
		for ($index = 1; $index <= 20; $index++) {
			$rows[] = $this->row(fileId: (4700 + $index), name: sprintf('stuk-%02d.pdf', $index), size: 1024);
			$records[] = ['uuid' => 'record-' . (4700 + $index), 'fileId' => (4700 + $index)];
		}

		$manifest = $this->service(rows: $rows, records: $records)->manifestFor(domain: $this->domain);

		$this->assertCount(20, $manifest['included']);
		$this->assertSame([], $manifest['excluded']);
		$this->assertSame((20 * 1024), $manifest['bytes']);

	}//end testOneBundleForTheBezwaarcommissie()

	/**
	 * A file over the ceiling is named in the manifest, not dropped.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testNothingIsDroppedSilentlyWhenTheCeilingIsReached(): void {
		$rows = [
			$this->row(fileId: 1, name: 'a.pdf', size: 600),
			$this->row(fileId: 2, name: 'b.pdf', size: 600),
		];
		$records = [['uuid' => 'record-1', 'fileId' => 1], ['uuid' => 'record-2', 'fileId' => 2]];

		$manifest = $this->service(rows: $rows, records: $records)->manifestFor(
			domain: $this->domain,
			ceiling: 1000
		);

		$this->assertCount(1, $manifest['included']);
		$this->assertCount(1, $manifest['excluded']);
		$this->assertSame(CaseArchiveService::REASON_CEILING, $manifest['excluded'][0]['reason']);
		$this->assertSame('b.pdf', $manifest['excluded'][0]['name'], 'The manifest names WHICH file is missing.');

	}//end testNothingIsDroppedSilentlyWhenTheCeilingIsReached()

	/**
	 * A file the person may not read is in the manifest as a permission exclusion.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testAFileTheUserMayNotReadIsRecordedAsSuch(): void {
		$rows = [$this->row(fileId: 1, name: 'zichtbaar.pdf', size: 100)];
		$records = [
			['uuid' => 'record-1', 'fileId' => 1],
			['uuid' => 'record-2', 'fileId' => 2, 'documentName' => 'geheim.pdf'],
		];

		$manifest = $this->service(rows: $rows, records: $records)->manifestFor(domain: $this->domain);

		$this->assertCount(1, $manifest['included']);
		$this->assertCount(1, $manifest['excluded']);
		$this->assertSame(CaseArchiveService::REASON_PERMISSION, $manifest['excluded'][0]['reason']);
		$this->assertSame('geheim.pdf', $manifest['excluded'][0]['name']);

	}//end testAFileTheUserMayNotReadIsRecordedAsSuch()

	/**
	 * The preflight says the bundle is too big BEFORE the job starts.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testThePreflightWarnsBeforeTheJobStarts(): void {
		$rows = [
			$this->row(fileId: 1, name: 'a.pdf', size: 600),
			$this->row(fileId: 2, name: 'b.pdf', size: 600),
		];

		$preflight = $this->service(rows: $rows, records: [])->preflight(
			domain: $this->domain,
			ceiling: 1000
		);

		$this->assertTrue($preflight['exceedsCeiling']);
		$this->assertSame(2, $preflight['files']);
		$this->assertSame(1200, $preflight['bytes']);

	}//end testThePreflightWarnsBeforeTheJobStarts()

	/**
	 * A bundle that fits does not warn.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testABundleThatFitsDoesNotWarn(): void {
		$preflight = $this->service(
			rows: [$this->row(fileId: 1, name: 'a.pdf', size: 100)],
			records: []
		)->preflight(domain: $this->domain, ceiling: 1000);

		$this->assertFalse($preflight['exceedsCeiling']);

	}//end testABundleThatFitsDoesNotWarn()

	/**
	 * The job records who asked, the ceiling, and both counts.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function testTheJobRecordsWhatWasHandedOver(): void {
		$service = $this->service(
			rows: [$this->row(fileId: 1, name: 'a.pdf', size: 100)],
			records: [['uuid' => 'record-1', 'fileId' => 1], ['uuid' => 'record-2', 'fileId' => 2]]
		);

		$manifest = $service->manifestFor(domain: $this->domain);
		$job = $service->record(domain: $this->domain, manifest: $manifest, ceiling: 1000, fileId: 9001);

		$this->assertSame('anna', $job['requestedBy']);
		$this->assertSame(1000, $job['ceilingBytes']);
		$this->assertSame(1, $job['includedCount']);
		$this->assertSame(1, $job['excludedCount']);
		$this->assertCount(1, $this->written);
		$this->assertSame('completed', $this->written[0]['status']);

	}//end testTheJobRecordsWhatWasHandedOver()
}//end class
