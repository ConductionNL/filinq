<?php

/**
 * Tests for the verdict that outlives the request that produced it.
 *
 * 🔴 THE DIRECTION THAT MATTERS IS `unverifiable`. A copy the verifier could
 * not read must not record nothing, because nothing and "clean" are the same
 * value to anything filtering on "was this checked", and one of them means
 * nobody looked. Two of the cases below are about exactly that.
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

use OCA\Filinq\Service\Redaction\RedactionIrreversibilityVerifier;
use OCA\Filinq\Service\Redaction\RedactionVerdictRecorder;
use OCP\Files\File;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * Tests for RedactionVerdictRecorder.
 */
class RedactionVerdictRecorderTest extends TestCase {

	/**
	 * The recorder under test, over the real verifier.
	 *
	 * @var RedactionVerdictRecorder
	 */
	private RedactionVerdictRecorder $recorder;

	/**
	 * Wire the recorder.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->recorder = new RedactionVerdictRecorder(
			verifier: new RedactionIrreversibilityVerifier(),
			logger: new NullLogger()
		);

	}//end setUp()

	/**
	 * A copy with the name still in it records a leaking verdict.
	 *
	 * @return void
	 */
	public function testACopyThatStillCarriesTheNameIsRecordedAsLeaking(): void {
		$node = $this->fileHolding(bytes: "%PDF-1.7\nBT (Fatima El-Amrani) Tj ET\n%%EOF");

		$result = $this->recorder->record(
			resultInfo: [],
			anonymisedNode: $node,
			redactedValues: [['text' => 'Fatima El-Amrani']],
			outputMode: 'pdf-only'
		);

		$this->assertSame(
			RedactionIrreversibilityVerifier::LEAKING,
			$result['redactionVerification']['verdict']
		);
		$this->assertFalse($result['redactionVerification']['mayBePublished']);
		$this->assertSame('pdf-only', $result['redactionVerification']['outputMode']);

	}//end testACopyThatStillCarriesTheNameIsRecordedAsLeaking()

	/**
	 * A copy with nothing left of the name records a clean verdict and its routes.
	 *
	 * @return void
	 */
	public function testACleanCopyRecordsTheRoutesThatWereChecked(): void {
		$node = $this->fileHolding(bytes: "%PDF-1.7\nBT ([PERSOON-1]) Tj ET\n%%EOF");

		$result = $this->recorder->record(
			resultInfo: [],
			anonymisedNode: $node,
			redactedValues: [['text' => 'Fatima El-Amrani']],
			outputMode: 'pdf/ua'
		);

		$verification = $result['redactionVerification'];
		$this->assertSame(RedactionIrreversibilityVerifier::CLEAN, $verification['verdict']);
		$this->assertNotEmpty(
			$verification['routesChecked'],
			'a clean verdict that names no route cannot be told from one that checked nothing'
		);

	}//end testACleanCopyRecordsTheRoutesThatWereChecked()

	/**
	 * A copy that cannot be read back records `unverifiable`, not nothing.
	 *
	 * @return void
	 */
	public function testACopyThatCannotBeReadBackIsRecordedAsUnverifiable(): void {
		$node = $this->createMock(File::class);
		$node->method('getContent')->willThrowException(new RuntimeException(message: 'storage is gone'));

		$result = $this->recorder->record(
			resultInfo: ['anonymizedFileId' => 99],
			anonymisedNode: $node,
			redactedValues: [['text' => 'Fatima El-Amrani']]
		);

		$this->assertSame(
			RedactionIrreversibilityVerifier::UNVERIFIABLE,
			$result['redactionVerification']['verdict'],
			'an unreadable copy must record that nobody looked, not an empty field'
		);
		$this->assertFalse($result['redactionVerification']['mayBePublished']);

	}//end testACopyThatCannotBeReadBackIsRecordedAsUnverifiable()

	/**
	 * A node that is not a file at all is still recorded as unverifiable.
	 *
	 * @return void
	 */
	public function testANodeThatIsNotAFileIsUnverifiable(): void {
		$result = $this->recorder->record(
			resultInfo: [],
			anonymisedNode: null,
			redactedValues: [['text' => 'Fatima El-Amrani']]
		);

		$this->assertSame(
			RedactionIrreversibilityVerifier::UNVERIFIABLE,
			$result['redactionVerification']['verdict']
		);

	}//end testANodeThatIsNotAFileIsUnverifiable()

	/**
	 * Verifying against nothing does not pass the document.
	 *
	 * @return void
	 */
	public function testACopyWithNoRedactedValuesDoesNotPass(): void {
		$node = $this->fileHolding(bytes: "%PDF-1.7\nBT (Fatima El-Amrani) Tj ET\n%%EOF");

		$result = $this->recorder->record(
			resultInfo: [],
			anonymisedNode: $node,
			redactedValues: []
		);

		$this->assertNotSame(
			RedactionIrreversibilityVerifier::CLEAN,
			$result['redactionVerification']['verdict'],
			'a check against nothing passes every document, including an unredacted one'
		);

	}//end testACopyWithNoRedactedValuesDoesNotPass()

	/**
	 * Entities are read for their values, however they are shaped.
	 *
	 * @return void
	 */
	public function testEntityValuesAreReadFromEitherKey(): void {
		$node = $this->fileHolding(bytes: "%PDF-1.7\nBT (Henk Bakker) Tj ET\n%%EOF");

		$result = $this->recorder->record(
			resultInfo: [],
			anonymisedNode: $node,
			redactedValues: [['value' => 'Henk Bakker']]
		);

		$this->assertSame(
			RedactionIrreversibilityVerifier::LEAKING,
			$result['redactionVerification']['verdict']
		);

	}//end testEntityValuesAreReadFromEitherKey()

	/**
	 * A file double holding the given bytes.
	 *
	 * @param string $bytes The content.
	 *
	 * @return File The double.
	 */
	private function fileHolding(string $bytes): File {
		$node = $this->createMock(File::class);
		$node->method('getContent')->willReturn($bytes);

		return $node;

	}//end fileHolding()
}//end class
