<?php

/**
 * A page, a range of pages or a whole document is redacted in one action:
 * the decision is validated, stored once, and committed either as full-page
 * regions on OpenRegister's image seam or as a withheld document without
 * output.
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

use OCA\Filinq\Exception\ScopeDecisionRefusedException;
use OCA\Filinq\Service\Review\ImageSeamGateway;
use OCA\Filinq\Service\Review\ScopeDecisionStore;
use OCA\Filinq\Service\Review\ScopeRedactionService;
use OCP\Files\File;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OCA\Filinq\Service\Review\ScopeRedactionService
 * @covers \OCA\Filinq\Exception\ScopeDecisionRefusedException
 */
class ScopeRedactionServiceTest extends TestCase {

	/**
	 * The decision store.
	 *
	 * @var ScopeDecisionStore&MockObject
	 */
	private ScopeDecisionStore&MockObject $store;

	/**
	 * The image seam.
	 *
	 * @var ImageSeamGateway&MockObject
	 */
	private ImageSeamGateway&MockObject $seam;

	protected function setUp(): void {
		parent::setUp();
		$this->store = $this->createMock(ScopeDecisionStore::class);
		$this->seam = $this->createMock(ImageSeamGateway::class);

	}//end setUp()

	/**
	 * The service under test.
	 *
	 * @return ScopeRedactionService The service.
	 */
	private function service(): ScopeRedactionService {
		return new ScopeRedactionService(store: $this->store, seam: $this->seam);

	}//end service()

	/**
	 * A source file.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return File&MockObject The file.
	 */
	private function file(int $fileId = 812005): File&MockObject {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($fileId);
		$file->method('getMimeType')->willReturn('application/pdf');

		return $file;

	}//end file()

	public function testARangeBeyondTheLastPageIsRefused(): void {
		$this->expectException(ScopeDecisionRefusedException::class);
		$this->expectExceptionMessage('The document has 4 pages, so pages 3 to 6 cannot be redacted.');

		$this->service()->validate(input: ['scope' => 'pageRange', 'fromPage' => 3, 'toPage' => 6, 'bases' => ['5.1.2.e']], pageCount: 4);

	}//end testARangeBeyondTheLastPageIsRefused()

	public function testAScopeNeedsAGround(): void {
		$this->store->expects($this->never())->method('append');

		try {
			$this->service()->record(fileId: 812005, input: ['scope' => 'page', 'fromPage' => 2, 'bases' => [' ']], pageCount: 4, userId: 'petra');
			$this->fail('A decision without a ground was stored.');
		} catch (ScopeDecisionRefusedException $e) {
			$this->assertSame('A redaction needs at least one ground.', $e->getMessage());
		}

	}//end testAScopeNeedsAGround()

	public function testARangeThatRunsBackwardsIsRefused(): void {
		$this->expectExceptionMessage('A range must start at or before its last page (5 is after 3).');

		$this->service()->validate(input: ['scope' => 'pageRange', 'fromPage' => 5, 'toPage' => 3, 'bases' => ['5.1.2.e']], pageCount: 7);

	}//end testARangeThatRunsBackwardsIsRefused()

	public function testAnUnknownScopeIsRefused(): void {
		$this->expectExceptionMessage('Unknown scope "section"; use page, pageRange or document.');

		$this->service()->validate(input: ['scope' => 'section', 'bases' => ['5.1.2.e']], pageCount: 7);

	}//end testAnUnknownScopeIsRefused()

	public function testAPageScopeOnADocumentWithoutKnownPagesIsRefused(): void {
		$this->expectExceptionMessage('The page count of this document is not known, so it cannot be redacted by page.');

		$this->service()->validate(input: ['scope' => 'page', 'fromPage' => 1, 'bases' => ['5.1.2.e']], pageCount: 0);

	}//end testAPageScopeOnADocumentWithoutKnownPagesIsRefused()

	public function testAPageDecisionIsNormalisedAndStored(): void {
		$this->store->expects($this->once())->method('append')
			->with(
				812005,
				$this->callback(
					function (array $decision): bool {
						$this->assertSame('page', $decision['scope']);
						$this->assertSame(3, $decision['fromPage']);
						$this->assertSame(3, $decision['toPage']);
						$this->assertSame(['5.1.2.e', '5.1.1.c'], $decision['bases']);
						$this->assertSame('petra', $decision['decidedBy']);
						$this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T/', $decision['decidedAt']);

						return true;
					}
				)
			)
			->willReturnArgument(1);

		$stored = $this->service()->record(
			fileId: 812005,
			input: ['scope' => 'page', 'fromPage' => '3', 'bases' => ['5.1.2.e', '5.1.1.c', '5.1.2.e']],
			pageCount: 7,
			userId: 'petra'
		);

		$this->assertSame(3, $stored['toPage']);

	}//end testAPageDecisionIsNormalisedAndStored()

	public function testADocumentScopeCarriesNoPages(): void {
		$decision = $this->service()->validate(input: ['scope' => 'document', 'fromPage' => 2, 'toPage' => 3, 'bases' => ['5.1.1.c']], pageCount: 0);

		$this->assertSame(['scope' => 'document', 'bases' => ['5.1.1.c']], $decision);

	}//end testADocumentScopeCarriesNoPages()

	public function testPagesBecomeFullPageRegions(): void {
		$file = $this->file();
		$output = $this->file(fileId: 812441);
		$this->seam->method('available')->willReturn(true);
		$this->seam->expects($this->once())->method('burn')
			->with(
				$file,
				[
					['page' => 3, 'box' => ['x' => 0.0, 'y' => 0.0, 'w' => 1.0, 'h' => 1.0]],
					['page' => 4, 'box' => ['x' => 0.0, 'y' => 0.0, 'w' => 1.0, 'h' => 1.0]],
					['page' => 5, 'box' => ['x' => 0.0, 'y' => 0.0, 'w' => 1.0, 'h' => 1.0]],
				],
				['5.1.2.e']
			)
			->willReturn($output);
		$this->store->expects($this->once())->method('recordBurn')->with(812005, 812441, 3);
		$this->store->expects($this->never())->method('withhold');

		$result = $this->service()->commit(
			file: $file,
			input: ['scope' => 'pageRange', 'fromPage' => 3, 'toPage' => 5, 'bases' => ['5.1.2.e']],
			pageCount: 7
		);

		$this->assertSame(['scope' => 'pageRange', 'withheld' => false, 'outputFileId' => 812441, 'burnedPages' => [3, 4, 5]], $result);

	}//end testPagesBecomeFullPageRegions()

	public function testAWholeDocumentIsWithheldWithoutOutput(): void {
		$file = $this->file();
		$this->seam->expects($this->never())->method('burn');
		$this->store->expects($this->once())->method('withhold')->with(812005, ['5.1.1.c'], 'petra');

		$result = $this->service()->commit(
			file: $file,
			input: ['scope' => 'document', 'bases' => ['5.1.1.c']],
			pageCount: 4,
			userId: 'petra'
		);

		$this->assertSame(['scope' => 'document', 'withheld' => true, 'outputFileId' => null, 'burnedPages' => []], $result);

	}//end testAWholeDocumentIsWithheldWithoutOutput()

	public function testPageScopesNeedTheSeam(): void {
		$this->seam->method('available')->willReturn(false);
		$this->seam->expects($this->never())->method('burn');
		$this->store->expects($this->never())->method('recordBurn');
		$this->store->expects($this->never())->method('withhold');

		$this->expectException(ScopeDecisionRefusedException::class);
		$this->expectExceptionMessage("Page redaction needs OpenRegister's image seam, so nothing was written.");

		$this->service()->commit(file: $this->file(), input: ['scope' => 'page', 'fromPage' => 2, 'bases' => ['5.1.2.e']], pageCount: 4);

	}//end testPageScopesNeedTheSeam()

	public function testAWholeDocumentWithholdWorksWithoutTheSeam(): void {
		$this->seam->method('available')->willReturn(false);
		$this->store->expects($this->once())->method('withhold');

		$result = $this->service()->commit(file: $this->file(), input: ['scope' => 'document', 'bases' => ['5.1.1.c']], pageCount: 0);

		$this->assertTrue($result['withheld']);

	}//end testAWholeDocumentWithholdWorksWithoutTheSeam()

	public function testAnInvalidCommitTouchesNothing(): void {
		$this->seam->method('available')->willReturn(true);
		$this->seam->expects($this->never())->method('burn');
		$this->store->expects($this->never())->method('withhold');

		$this->expectException(ScopeDecisionRefusedException::class);

		$this->service()->commit(file: $this->file(), input: ['scope' => 'page', 'fromPage' => 9, 'bases' => ['5.1.2.e']], pageCount: 4);

	}//end testAnInvalidCommitTouchesNothing()
}//end class
