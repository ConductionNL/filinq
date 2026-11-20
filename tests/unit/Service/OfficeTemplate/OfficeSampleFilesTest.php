<?php

/**
 * The office source fixtures on disk: a valid DOCX, a DOCX carrying macros, a
 * corrupt DOCX and an ODT, each answered by the source guard as an upload of
 * that file would be.
 *
 * The in-code packages in OfficeFixtures cover each rule in isolation; these
 * files are what a person actually uploads, so a guard that reads a real
 * package differently from a hand-built one is caught here.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#task-5.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\OfficeTemplate;

use OCA\Filinq\Exception\OfficeSourceRefusedException;
use OCA\Filinq\Service\OfficeTemplate\MergeTagReader;
use OCA\Filinq\Service\OfficeTemplate\OfficeSourceGuard;
use PHPUnit\Framework\TestCase;

/**
 * The four fixture files the change names, through the guard.
 *
 * @covers \OCA\Filinq\Service\OfficeTemplate\OfficeSourceGuard
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class OfficeSampleFilesTest extends TestCase {

	/**
	 * The sample documents folder.
	 *
	 * @var string
	 */
	private const DIR = __DIR__.'/../../../sample-documents/';

	/**
	 * The guard under test.
	 *
	 * @var OfficeSourceGuard
	 */
	private OfficeSourceGuard $guard;

	/**
	 * A fresh guard per test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->guard = new OfficeSourceGuard();
	}//end setUp()

	/**
	 * The bytes of a fixture file.
	 *
	 * @param string $name The path under tests/sample-documents.
	 *
	 * @return string
	 */
	private function fixture(string $name): string {
		$this->assertFileExists(self::DIR.$name);

		return (string) file_get_contents(self::DIR.$name);
	}//end fixture()

	/**
	 * The reason the guard refuses a fixture with, or '' when it accepts it.
	 *
	 * @param string $fileName The upload name.
	 * @param string $bytes    The content.
	 *
	 * @return string
	 */
	private function refusal(string $fileName, string $bytes): string {
		try {
			$this->guard->inspect(fileName: $fileName, bytes: $bytes, maxBytes: OfficeSourceGuard::DEFAULT_MAX_BYTES);
		} catch (OfficeSourceRefusedException $e) {
			$this->assertSame(422, $e->getCode());

			return $e->getReason();
		}

		return '';
	}//end refusal()

	/**
	 * The valid DOCX is accepted as a DOCX and its merge tags read.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#task-5.1
	 */
	public function testTheValidDocxIsAccepted(): void {
		$bytes = $this->fixture(name: 'beschikking-parkeervergunning.docx');

		$this->assertSame('docx', $this->guard->inspect(fileName: 'beschikking-parkeervergunning.docx', bytes: $bytes, maxBytes: OfficeSourceGuard::DEFAULT_MAX_BYTES));
		$this->assertNotSame([], (new MergeTagReader())->tags(docx: $bytes));
	}//end testTheValidDocxIsAccepted()

	/**
	 * The ODT is accepted as an ODT.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#task-5.1
	 */
	public function testTheOdtIsAccepted(): void {
		$bytes = $this->fixture(name: 'office/letter.odt');

		$this->assertSame('odt', $this->guard->inspect(fileName: 'letter.odt', bytes: $bytes, maxBytes: OfficeSourceGuard::DEFAULT_MAX_BYTES));
	}//end testTheOdtIsAccepted()

	/**
	 * The DOCX carrying a VBA project is refused as macro-bearing, though its name says .docx.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#task-5.1
	 */
	public function testTheMacroDocxIsRefused(): void {
		$this->assertSame(
			OfficeSourceRefusedException::REASON_MACRO,
			$this->refusal(fileName: 'macro-letter.docx', bytes: $this->fixture(name: 'office/macro-letter.docx'))
		);
	}//end testTheMacroDocxIsRefused()

	/**
	 * The corrupt DOCX is refused: it is not the package its extension says.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#task-5.1
	 */
	public function testTheCorruptDocxIsRefused(): void {
		$this->assertSame(
			OfficeSourceRefusedException::REASON_TYPE,
			$this->refusal(fileName: 'corrupt-letter.docx', bytes: $this->fixture(name: 'office/corrupt-letter.docx'))
		);
	}//end testTheCorruptDocxIsRefused()
}//end class
