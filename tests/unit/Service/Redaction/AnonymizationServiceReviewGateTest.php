<?php

/**
 * The anonymise path really asks the gate, and a refusal writes nothing.
 *
 * 🔴 THE FAILURE THIS FILE EXISTS FOR IS A GATE THAT PASSES ITS OWN TESTS AND
 * IS NEVER CALLED. `RedactionReviewGate` shipped with eleven green cases and
 * no call site: every output path ran past it, and the suite said so nowhere.
 * So these assertions are not about the gate's answer. They are about the
 * runner: when the gate refuses, `DocumentAnonymizeRunner::run()` must not
 * happen at all, because a document that reaches the runner gets a file
 * written for it and that file is the thing that leaves the building.
 *
 * 🔴 AND IT IS ASSERTED ON BOTH PUBLIC ENTRY POINTS. `anonymizeDocument()` and
 * `anonymizeDocumentWithBasisSummary()` are the two doors the API, the batch
 * path and the folder job come through. A guard on one of them is a guard with
 * a way around it.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\Redaction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Redaction;

use OCA\Filinq\Exception\RedactionNotReviewedException;
use OCA\Filinq\Service\AnonymizationService;
use OCA\Filinq\Service\DocumentAnonymizeRunner;
use OCA\Filinq\Service\Redaction\DetectionRunIdentity;
use OCA\Filinq\Service\Redaction\RedactionOutputGuard;
use OCA\Filinq\Service\Redaction\RedactionReviewGate;
use OCA\Filinq\Service\Redaction\RedactionReviewMarkRepository;
use OCA\Filinq\Tests\Unit\Service\BuildsAnonymizationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests that AnonymizationService enforces the review gate.
 */
class AnonymizationServiceReviewGateTest extends TestCase {

	use BuildsAnonymizationService;

	/**
	 * What one detection run found.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private const FOUND = [
		['entityType' => 'PERSON', 'text' => 'Fatima El-Amrani', 'start' => 120, 'end' => 136],
	];

	/**
	 * An unreviewed document produces no file through the ordinary door.
	 *
	 * @return void
	 */
	public function testAnUnreviewedDocumentNeverReachesTheRunner(): void {
		$runner = $this->runnerThatMustNotRun();
		$service = $this->serviceWith(runner: $runner, mark: null);

		$this->expectException(RedactionNotReviewedException::class);

		$service->anonymizeDocument(fileId: 42, entities: self::FOUND);

	}//end testAnUnreviewedDocumentNeverReachesTheRunner()

	/**
	 * Nor through the grondslagen door.
	 *
	 * @return void
	 */
	public function testTheSummaryEntryPointIsGatedToo(): void {
		$runner = $this->runnerThatMustNotRun();
		$service = $this->serviceWith(runner: $runner, mark: null);

		$this->expectException(RedactionNotReviewedException::class);

		$service->anonymizeDocumentWithBasisSummary(fileId: 42, entities: self::FOUND);

	}//end testTheSummaryEntryPointIsGatedToo()

	/**
	 * A mark from an earlier detection run does not open the door.
	 *
	 * @return void
	 */
	public function testAStaleMarkDoesNotOpenTheDoor(): void {
		$runs = new DetectionRunIdentity();
		$runner = $this->runnerThatMustNotRun();
		$service = $this->serviceWith(
			runner: $runner,
			mark: [
				'document' => '42',
				'detectionRun' => $runs->of(fileId: 42, entities: []),
				'checkedBy' => 'noor',
				'checkedAt' => '2026-09-18T09:00:00+00:00',
			]
		);

		$this->expectException(RedactionNotReviewedException::class);

		$service->anonymizeDocument(fileId: 42, entities: self::FOUND);

	}//end testAStaleMarkDoesNotOpenTheDoor()

	/**
	 * A checked document does reach the runner, so the gate is a gate and not a wall.
	 *
	 * @return void
	 */
	public function testACheckedDocumentReachesTheRunner(): void {
		$runs = new DetectionRunIdentity();

		$runner = $this->getMockBuilder(DocumentAnonymizeRunner::class)
			->disableOriginalConstructor()
			->onlyMethods(['run'])
			->getMock();
		$runner->expects($this->once())
			->method('run')
			->willReturn(['anonymizedFileId' => 99]);

		$service = $this->serviceWith(
			runner: $runner,
			mark: [
				'document' => '42',
				'detectionRun' => $runs->of(fileId: 42, entities: self::FOUND),
				'checkedBy' => 'noor',
				'checkedAt' => '2026-09-18T09:00:00+00:00',
			]
		);

		$result = $service->anonymizeDocument(fileId: 42, entities: self::FOUND);

		$this->assertSame(99, $result['anonymizedFileId']);

	}//end testACheckedDocumentReachesTheRunner()

	/**
	 * A runner that fails the test if anything calls it.
	 *
	 * @return DocumentAnonymizeRunner&MockObject The runner.
	 */
	private function runnerThatMustNotRun(): DocumentAnonymizeRunner {
		$runner = $this->getMockBuilder(DocumentAnonymizeRunner::class)
			->disableOriginalConstructor()
			->onlyMethods(['run'])
			->getMock();

		$runner->expects($this->never())->method('run');

		return $runner;

	}//end runnerThatMustNotRun()

	/**
	 * The real service, over a real guard, over the given mark.
	 *
	 * The guard is REAL here. A guard double would assert that the service
	 * calls something, which is weaker than asserting that an unreviewed
	 * document produces no file.
	 *
	 * @param DocumentAnonymizeRunner   $runner The runner.
	 * @param array<string, mixed>|null $mark   The mark the store holds.
	 *
	 * @return AnonymizationService The service.
	 */
	private function serviceWith(DocumentAnonymizeRunner $runner, ?array $mark): AnonymizationService {
		$marks = $this->getMockBuilder(RedactionReviewMarkRepository::class)
			->disableOriginalConstructor()
			->onlyMethods(['findFor', 'save'])
			->getMock();
		$marks->method('findFor')->willReturn($mark);

		$guard = new RedactionOutputGuard(
			gate: new RedactionReviewGate(),
			marks: $marks,
			runs: new DetectionRunIdentity()
		);

		return $this->makeAnonymizationServiceFrom(
			deps: [
				'anonymizeRunner' => $runner,
				'reviewGuard' => $guard,
			]
		);

	}//end serviceWith()
}//end class
