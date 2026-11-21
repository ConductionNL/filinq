<?php

/**
 * Unit tests for FieldPlacementCheck.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Signing;

use OCA\Filinq\Exception\SigningFieldsRefusedException;
use OCA\Filinq\Service\SignedArtifactProducer;
use OCA\Filinq\Service\Signing\FieldPlacementCheck;
use OCA\Filinq\Service\Signing\FieldPlacementRenderer;
use OCA\Filinq\Service\Signing\FieldPlacements;
use OCA\Filinq\Service\Signing\FieldValueChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Accepting field placements on a signing request.
 *
 * @covers \OCA\Filinq\Service\Signing\FieldPlacementCheck
 * @uses   \OCA\Filinq\Service\Signing\FieldPlacements
 * @uses   \OCA\Filinq\Service\Signing\FieldValueChecker
 * @uses   \OCA\Filinq\Exception\SigningFieldsRefusedException
 */
class FieldPlacementCheckTest extends TestCase {

	/**
	 * The page-count renderer double.
	 *
	 * @var FieldPlacementRenderer&MockObject
	 */
	private FieldPlacementRenderer&MockObject $renderer;

	/**
	 * Set up the renderer double.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->renderer = $this->createMock(FieldPlacementRenderer::class);
	}//end setUp()

	/**
	 * Build the check.
	 *
	 * @return FieldPlacementCheck
	 */
	private function check(): FieldPlacementCheck {
		return new FieldPlacementCheck(rules: new FieldPlacements(), renderer: $this->renderer);
	}//end check()

	/**
	 * One valid placement.
	 *
	 * @param int $page The page.
	 *
	 * @return array<string, mixed>
	 */
	private function placement(int $page): array {
		return ['signerIndex' => 0, 'page' => $page, 'x' => 0.1, 'y' => 0.2, 'width' => 0.3, 'height' => 0.1, 'type' => 'signature'];
	}//end placement()

	/**
	 * Invalid placements become a 400 RuntimeException carrying the rule's message.
	 *
	 * @return void
	 */
	public function testInvalidPlacementsAre400(): void {
		try {
			$this->check()->accept(placements: 'nope', signerCount: 1, provider: 'native');
			$this->fail('Expected RuntimeException');
		} catch (RuntimeException $e) {
			$this->assertSame(400, $e->getCode());
			$this->assertSame('fieldPlacements must be a list', $e->getMessage());
			$this->assertInstanceOf(\InvalidArgumentException::class, $e->getPrevious());
		}
	}//end testInvalidPlacementsAre400()

	/**
	 * LibreSign refuses placements; no placements are fine for it.
	 *
	 * @return void
	 */
	public function testLibreSignRefusesPlacements(): void {
		$this->assertSame([], $this->check()->accept(placements: null, signerCount: 1, provider: 'libresign'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionCode(400);
		$this->expectExceptionMessage('LibreSign places its own fields');

		$this->check()->accept(placements: [$this->placement(page: 1)], signerCount: 1, provider: 'libresign');
	}//end testLibreSignRefusesPlacements()

	/**
	 * A placement past the last page is refused with both numbers.
	 *
	 * @return void
	 */
	public function testPageBeyondDocumentIsRefused(): void {
		$this->renderer->method('pageCount')->with('%PDF')->willReturn(2);

		$this->check()->assertPagesExist(placements: [$this->placement(page: 2)], pdf: '%PDF');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionCode(400);
		$this->expectExceptionMessage('A field is placed on page 3 of a document with 2 pages');

		$this->check()->assertPagesExist(placements: [$this->placement(page: 1), $this->placement(page: 3)], pdf: '%PDF');
	}//end testPageBeyondDocumentIsRefused()

	/**
	 * An unreadable PDF is rethrown as 400.
	 *
	 * @return void
	 */
	public function testUnreadablePdfIs400(): void {
		$this->renderer->method('pageCount')->willThrowException(new RuntimeException('Fields cannot be placed on this document: broken'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionCode(400);
		$this->expectExceptionMessage('Fields cannot be placed on this document: broken');

		$this->check()->assertPagesExist(placements: [$this->placement(page: 1)], pdf: 'x');
	}//end testUnreadablePdfIs400()

	/**
	 * Without placements the request is returned untouched and the document is never read.
	 *
	 * @return void
	 */
	public function testApplyWithoutPlacementsLeavesRequest(): void {
		$producer = $this->createMock(SignedArtifactProducer::class);
		$producer->expects($this->never())->method('documentContent');

		$request = ['provider' => 'native', 'title' => 'x'];
		$this->assertSame($request, $this->check()->apply(request: $request, placements: [], signerCount: 2, producer: $producer));
	}//end testApplyWithoutPlacementsLeavesRequest()

	/**
	 * Accepted placements are normalised onto the request after the page check.
	 *
	 * @return void
	 */
	public function testApplyStoresNormalisedPlacements(): void {
		$request = ['provider' => 'native'];
		$producer = $this->createMock(SignedArtifactProducer::class);
		$producer->expects($this->once())->method('documentContent')->with($request)->willReturn('%PDF');
		$this->renderer->method('pageCount')->willReturn(1);

		$raw = ['signerIndex' => 1, 'page' => 1, 'x' => 0, 'y' => 0, 'width' => 1, 'height' => 0.5, 'type' => 'date'];
		$result = $this->check()->apply(request: $request, placements: [$raw], signerCount: 2, producer: $producer);

		$this->assertSame(
			[
				'provider' => 'native',
				'fieldPlacements' => [['signerIndex' => 1, 'page' => 1, 'x' => 0.0, 'y' => 0.0, 'width' => 1.0, 'height' => 0.5, 'type' => 'date']],
			],
			$result
		);
	}//end testApplyStoresNormalisedPlacements()

	/**
	 * A text field whose check OpenRegister cannot run is refused at creation (REQ-SFV-002).
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signing-field-validation/tasks.md#task-2.1
	 */
	public function testACheckThisServerCannotRunIsRefused(): void {
		$field = ['type' => 'text', 'label' => 'Account for the payment', 'required' => true, 'validation' => 'iban-nl'] + $this->placement(page: 1);
		$missing = new FieldPlacementCheck(
			rules: new FieldPlacements(),
			renderer: $this->renderer,
			valueChecker: new FieldValueChecker(formatLookup: fn (string $name): ?object => null)
		);

		try {
			$missing->accept(placements: [$this->placement(page: 1), $field], signerCount: 1, provider: 'native');
			$this->fail('Expected the unavailable check to be refused');
		} catch (SigningFieldsRefusedException $e) {
			$this->assertSame(400, $e->getCode());
			$this->assertSame([1 => 'This check is not available on this server'], $e->fieldErrors());
		}

		$present = new FieldPlacementCheck(
			rules: new FieldPlacements(),
			renderer: $this->renderer,
			valueChecker: new FieldValueChecker(formatLookup: fn (string $name): ?object => new class { public function validate(mixed $data): bool { return true; } })
		);
		$accepted = $present->accept(placements: [$field], signerCount: 1, provider: 'native');
		$this->assertSame('iban-nl', $accepted[0]['validation']);
	}//end testACheckThisServerCannotRunIsRefused()
}//end class
