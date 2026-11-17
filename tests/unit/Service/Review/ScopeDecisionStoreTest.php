<?php

/**
 * Scope decisions are kept on the document's review, and their outcome on
 * its anonymisation link: withheld with the grounds, or the burned output
 * with the regions actually burned.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Review
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/image-redaction/specs/image-redaction/spec.md#requirement-a-page-a-page-range-or-a-whole-document-is-redacted-in-one-action-req-ddimr-009
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Review;

use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCA\Filinq\Service\Review\ScopeDecisionStore;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\Review\ScopeDecisionStore
 * @uses   \OCA\Filinq\Service\ObjectResultExtractor
 */
class ScopeDecisionStoreTest extends TestCase {

	/**
	 * Stored objects by schema.
	 *
	 * @var array<string, list<array<string, mixed>>>
	 */
	public array $rows = [];

	/**
	 * Every save: schema, object, rbac, unowned.
	 *
	 * @var list<array{schema: string, object: array<string, mixed>, rbac: bool, unowned: bool}>
	 */
	public array $saves = [];

	/**
	 * Whether a save throws.
	 *
	 * @var bool
	 */
	public bool $failSave = false;

	/**
	 * The store over an in-memory ObjectService.
	 *
	 * @return ScopeDecisionStore The store.
	 */
	private function store(): ScopeDecisionStore {
		$test = $this;
		$objects = new class ($test) {

			public function __construct(private readonly ScopeDecisionStoreTest $test) {
			}

			public function searchObjectsBySlug(string $registerSlug, string $schemaSlug, array $filters = []): array {
				return array_values(
					array_filter(
						($this->test->rows[$schemaSlug] ?? []),
						static fn (array $row): bool => array_intersect_assoc($filters, $row) === $filters
					)
				);
			}

			public function saveObject(array $object, ObjectSaveOptions $options, bool $_rbac = true): array {
				if ($this->test->failSave === true) {
					throw new RuntimeException('database is down');
				}

				$this->test->saves[] = [
					'schema' => (string) $options->target->schema,
					'object' => $object,
					'rbac' => $_rbac,
					'unowned' => ($options->actor?->unowned ?? false),
				];

				return $object;
			}
		};
		$locator = $this->createMock(OpenRegisterServiceLocator::class);
		$locator->method('get')->willReturn($objects);

		return new ScopeDecisionStore(locator: $locator);

	}//end store()

	public function testADecisionIsAddedToTheExistingReview(): void {
		$this->rows['documentReview'] = [['fileId' => 812005, 'checkedBy' => 'petra', 'scopeDecisions' => [['scope' => 'document', 'bases' => ['5.1.1.c']]]]];

		$this->store()->append(fileId: 812005, decision: ['scope' => 'page', 'fromPage' => 2, 'toPage' => 2, 'bases' => ['5.1.2.e']]);

		$this->assertCount(1, $this->saves);
		$saved = $this->saves[0];
		$this->assertSame('documentReview', $saved['schema']);
		$this->assertTrue($saved['rbac'], 'A decision is the reviewer\'s own write.');
		$this->assertSame('petra', $saved['object']['checkedBy']);
		$this->assertSame(['document', 'page'], array_column($saved['object']['scopeDecisions'], 'scope'));

	}//end testADecisionIsAddedToTheExistingReview()

	public function testTheFirstDecisionCreatesTheReview(): void {
		$this->store()->append(fileId: 812005, decision: ['scope' => 'document', 'bases' => ['5.1.1.c']]);

		$object = $this->saves[0]['object'];
		$this->assertSame(812005, $object['fileId']);
		$this->assertSame(['register' => 'filinq', 'schema' => 'documentReview'], $object['@self']);
		$this->assertSame([['scope' => 'document', 'bases' => ['5.1.1.c']]], $object['scopeDecisions']);

	}//end testTheFirstDecisionCreatesTheReview()

	public function testDecisionsAreReadBack(): void {
		$this->rows['documentReview'] = [['fileId' => 812005, 'scopeDecisions' => [['scope' => 'page'], 'garbage']]];

		$this->assertSame([['scope' => 'page']], $this->store()->decisionsFor(fileId: 812005));
		$this->assertSame([], $this->store()->decisionsFor(fileId: 1));

	}//end testDecisionsAreReadBack()

	public function testWithholdingMarksTheLinkWithItsGrounds(): void {
		$this->rows['anonymizationLink'] = [['sourceFileId' => 812005, 'anonymizedFileId' => 812441, 'runCount' => 2]];

		$this->store()->withhold(fileId: 812005, bases: ['5.1.1.c'], userId: 'petra');

		$saved = $this->saves[0];
		$this->assertSame('anonymizationLink', $saved['schema']);
		$this->assertFalse($saved['rbac']);
		$this->assertTrue($saved['unowned']);
		$this->assertTrue($saved['object']['withheld']);
		$this->assertSame(['5.1.1.c'], $saved['object']['withheldBases']);
		$this->assertSame('withheld', $saved['object']['status']);
		$this->assertSame('petra', $saved['object']['anonymizedBy']);
		$this->assertSame(2, $saved['object']['runCount'], 'Withholding is not an anonymisation run.');

	}//end testWithholdingMarksTheLinkWithItsGrounds()

	public function testAWithheldDocumentWithoutALinkGetsOneWithoutOutput(): void {
		$this->store()->withhold(fileId: 812005, bases: ['5.1.1.c'], userId: '');

		$object = $this->saves[0]['object'];
		$this->assertSame(812005, $object['sourceFileId']);
		$this->assertArrayNotHasKey('anonymizedFileId', $object);
		$this->assertArrayNotHasKey('anonymizedBy', $object);

	}//end testAWithheldDocumentWithoutALinkGetsOneWithoutOutput()

	public function testABurnRecordsTheOutputAndTheBurnedRegions(): void {
		$this->rows['anonymizationLink'] = [['sourceFileId' => 812005, 'withheld' => true, 'runCount' => 1]];

		$this->store()->recordBurn(fileId: 812005, outputFileId: 812441, burnedRegionCount: 3);

		$object = $this->saves[0]['object'];
		$this->assertSame(812441, $object['anonymizedFileId']);
		$this->assertSame(3, $object['burnedRegionCount']);
		$this->assertFalse($object['imageRedactionPending']);
		$this->assertFalse($object['withheld']);
		$this->assertSame('anonymized', $object['status']);
		$this->assertSame(2, $object['runCount']);

	}//end testABurnRecordsTheOutputAndTheBurnedRegions()

	public function testAFailedWriteIsNeverReportedAsDone(): void {
		$this->failSave = true;

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The anonymizationLink could not be written, so nothing changed: database is down');

		$this->store()->withhold(fileId: 812005, bases: ['5.1.1.c'], userId: 'petra');

	}//end testAFailedWriteIsNeverReportedAsDone()
}//end class
