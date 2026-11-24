<?php

/**
 * The Run validation checks operation: the configured profile is used, a
 * blocking finding fails the run and warnings do not (REQ-DDFLO-007).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Flow
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-4.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Flow;

use OCA\Filinq\Flow\FlowOperationOutcome;
use OCA\Filinq\Flow\Operation\RunValidationOperation;
use OCA\Filinq\Kernel\Contracts\DocumentValidationServiceInterface;
use OCA\Filinq\Service\Validation\ValidationProfileResolver;
use OCP\Files\File;
use OCP\Files\Folder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests RunValidationOperation::execute().
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Flow
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Flow\Operation\RunValidationOperation
 * @uses   \OCA\Filinq\Flow\Operation\AbstractFileOperation
 * @uses   \OCA\Filinq\Flow\FlowOperationOutcome
 */
class RunValidationOperationTest extends TestCase {
	use FlowOperationDoubles;

	/**
	 * @var DocumentValidationServiceInterface&MockObject
	 */
	private DocumentValidationServiceInterface $validation;

	/**
	 * The file.
	 *
	 * @var File&MockObject
	 */
	private File $file;

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->validation = $this->createMock(DocumentValidationServiceInterface::class);
		$this->file = $this->createMock(File::class);

	}//end setUp()

	/**
	 * Run the operation.
	 *
	 * @param array<string, mixed> $config The options.
	 *
	 * @return FlowOperationOutcome The outcome.
	 */
	private function execute(array $config): FlowOperationOutcome {
		return (new RunValidationOperation($this->l10n(), $this->urls(), $this->idleTrigger(), $this->validation, $this->createMock(ValidationProfileResolver::class)))
			->execute($this->file, $config, $this->createMock(Folder::class));

	}//end execute()

	/**
	 * A disallowed format under the besluit profile fails the run with the finding in the summary.
	 *
	 * @return void
	 */
	public function testABlockingFindingFailsTheRun(): void {
		$finding = ['check' => 'format-not-allowed', 'severity' => 'blocking', 'category' => 'document'];
		$this->validation->expects($this->once())->method('validate')->with($this->file, [], 'besluit')
			->willReturn(['validationStatus' => 'failed', 'validationFindings' => [$finding]]);

		$outcome = $this->execute(['documentType' => 'besluit']);

		$this->assertSame('failed', $outcome->status);
		$this->assertSame('validation_failed', $outcome->reason);
		$this->assertSame(['documentType' => 'besluit', 'verdict' => 'failed', 'findingCount' => 1, 'findings' => [$finding]], $outcome->resultSummary);

	}//end testABlockingFindingFailsTheRun()

	/**
	 * Warnings only leave the run succeeded with the warnings attached.
	 *
	 * @return void
	 */
	public function testWarningsDoNotFailTheRun(): void {
		$warning = ['check' => 'text-layer-missing', 'severity' => 'warning'];
		$this->validation->method('validate')->willReturn(['validationStatus' => 'warnings', 'validationFindings' => [$warning]]);

		$outcome = $this->execute(['documentType' => 'besluit']);

		$this->assertSame('succeeded', $outcome->status);
		$this->assertSame([$warning], $outcome->resultSummary['findings']);
		$this->assertSame('warnings', $outcome->resultSummary['verdict']);

	}//end testWarningsDoNotFailTheRun()

	/**
	 * Without a document type the default profile runs.
	 *
	 * @return void
	 */
	public function testWithoutADocumentTypeTheDefaultProfileRuns(): void {
		$this->validation->expects($this->once())->method('validate')->with($this->file, [], null)
			->willReturn(['validationStatus' => 'passed', 'validationFindings' => []]);

		$outcome = $this->execute([]);

		$this->assertSame('succeeded', $outcome->status);
		$this->assertSame('default', $outcome->resultSummary['documentType']);
		$this->assertSame(0, $outcome->resultSummary['findingCount']);

	}//end testWithoutADocumentTypeTheDefaultProfileRuns()

	/**
	 * Checks that cannot run fail the run.
	 *
	 * @return void
	 */
	public function testChecksThatCannotRunFailTheRun(): void {
		$this->validation->method('validate')->willThrowException(new RuntimeException('unreadable'));

		$outcome = $this->execute(['documentType' => 'besluit']);

		$this->assertSame('failed', $outcome->status);
		$this->assertSame('validation_error', $outcome->reason);

	}//end testChecksThatCannotRunFailTheRun()
}//end class
