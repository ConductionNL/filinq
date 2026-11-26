<?php

/**
 * The anonymise commit honours a whole-document scope decision: the document
 * is withheld, no output is written, and the batch entry says so.
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
 * @spec openspec/changes/image-redaction/tasks.md#task-6.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Review;

use OCA\Filinq\Service\AnonymizationService;
use OCA\Filinq\Service\DocumentAnonymizeRunner;
use OCA\Filinq\Service\Review\ScopeDecisionStore;
use OCA\Filinq\Tests\Unit\Service\BuildsAnonymizationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * A stored `document` decision is committed as a withhold, on every path that runs the commit.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Review
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Service\AnonymizationService
 */
class AnonymizationServiceWithholdTest extends TestCase {

	use BuildsAnonymizationService;

	/**
	 * A page decision, the kind that does not withhold.
	 *
	 * @var array<string, mixed>
	 */
	private const PAGES = ['scope' => 'pageRange', 'fromPage' => 3, 'toPage' => 5, 'bases' => ['5.1.2.e']];

	public function testAWithheldDocumentIsCommittedWithoutOutput(): void {
		$scopes = $this->scopesWith(decisions: [self::PAGES, ['scope' => 'document', 'bases' => ['5.1.1.c']]]);
		$scopes->expects($this->once())->method('withhold')->with(812004, ['5.1.1.c'], 'alice');
		$runner = $this->createMock(DocumentAnonymizeRunner::class);
		$runner->expects($this->never())->method('run');

		$result = $this->service(scopes: $scopes, runner: $runner)->anonymizeDocumentAs(fileId: 812004, entities: [], userId: 'alice');

		$this->assertTrue($result['withheld']);
		$this->assertSame(['5.1.1.c'], $result['withheldBases']);
		$this->assertNull($result['anonymizedFileId'], 'A withheld document has no output.');
		$this->assertSame(0, $result['replacementCount']);
		$this->assertSame('withheld', $result['detection']['outcome']);

	}//end testAWithheldDocumentIsCommittedWithoutOutput()

	public function testTheLatestDocumentDecisionGivesTheGrounds(): void {
		$scopes = $this->scopesWith(
			decisions: [
				['scope' => 'document', 'bases' => ['5.1.1.c']],
				['scope' => 'document', 'bases' => ['5.1.2.e', 7, ['not a ground']]],
			]
		);
		$scopes->expects($this->once())->method('withhold')->with(812004, ['5.1.2.e', '7'], '');

		$result = $this->service(scopes: $scopes, runner: $this->createMock(DocumentAnonymizeRunner::class))
			->anonymizeDocument(fileId: 812004, entities: []);

		$this->assertSame(['5.1.2.e', '7'], $result['withheldBases']);

	}//end testTheLatestDocumentDecisionGivesTheGrounds()

	public function testPageDecisionsLeaveTheRunAsItWas(): void {
		$scopes = $this->scopesWith(decisions: [self::PAGES]);
		$scopes->expects($this->never())->method('withhold');
		$runner = $this->createMock(DocumentAnonymizeRunner::class);
		$runner->expects($this->once())->method('run')->willReturn(['anonymizedFileId' => 812441, 'replacementCount' => 2]);

		$result = $this->service(scopes: $scopes, runner: $runner)->anonymizeDocument(fileId: 812004, entities: []);

		$this->assertArrayNotHasKey('withheld', $result);
		$this->assertSame(812441, $result['anonymizedFileId']);

	}//end testPageDecisionsLeaveTheRunAsItWas()

	public function testWithoutTheStoreNothingIsWithheld(): void {
		$runner = $this->createMock(DocumentAnonymizeRunner::class);
		$runner->expects($this->once())->method('run')->willReturn(['anonymizedFileId' => 812441, 'replacementCount' => 0]);

		$result = $this->makeAnonymizationServiceFrom(deps: ['anonymizeRunner' => $runner])
			->anonymizeDocument(fileId: 812004, entities: []);

		$this->assertArrayNotHasKey('withheld', $result);

	}//end testWithoutTheStoreNothingIsWithheld()

	/**
	 * A store that holds the given decisions.
	 *
	 * @param list<array<string, mixed>> $decisions The decisions, oldest first.
	 *
	 * @return ScopeDecisionStore&MockObject The store.
	 */
	private function scopesWith(array $decisions): ScopeDecisionStore&MockObject {
		$scopes = $this->createMock(ScopeDecisionStore::class);
		$scopes->method('decisionsFor')->with(812004)->willReturn($decisions);

		return $scopes;

	}//end scopesWith()

	/**
	 * The service over the store and the runner.
	 *
	 * @param ScopeDecisionStore      $scopes The scope decisions.
	 * @param DocumentAnonymizeRunner $runner The anonymise runner.
	 *
	 * @return AnonymizationService The service.
	 */
	private function service(ScopeDecisionStore $scopes, DocumentAnonymizeRunner $runner): AnonymizationService {
		return $this->makeAnonymizationServiceFrom(deps: ['scopes' => $scopes, 'anonymizeRunner' => $runner]);

	}//end service()
}//end class
