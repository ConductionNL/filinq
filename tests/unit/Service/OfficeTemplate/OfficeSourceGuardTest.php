<?php

/**
 * Which uploaded files may become office templates: a DOCX or ODT that is
 * what its extension says, under the size cap, without macros.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\OfficeTemplate;

use OCA\Filinq\Exception\OfficeSourceRefusedException;
use OCA\Filinq\Service\OfficeTemplate\OfficeSourceGuard;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/OfficeFixtures.php';

/**
 * @covers \OCA\Filinq\Service\OfficeTemplate\OfficeSourceGuard
 * @uses   \OCA\Filinq\Exception\OfficeSourceRefusedException
 */
class OfficeSourceGuardTest extends TestCase {

	private OfficeSourceGuard $guard;

	/**
	 * A guard.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->guard = new OfficeSourceGuard();
	}//end setUp()

	/**
	 * Assert a file is refused for a reason.
	 *
	 * @param string $reason   The expected reason.
	 * @param string $fileName The file name.
	 * @param string $bytes    The content.
	 * @param int    $maxBytes The size cap.
	 *
	 * @return void
	 */
	private function assertRefused(string $reason, string $fileName, string $bytes, int $maxBytes=OfficeSourceGuard::DEFAULT_MAX_BYTES): void {
		try {
			$this->guard->inspect(fileName: $fileName, bytes: $bytes, maxBytes: $maxBytes);
			$this->fail('Expected '.$fileName.' to be refused for '.$reason.'.');
		} catch (OfficeSourceRefusedException $e) {
			$this->assertSame($reason, $e->getReason());
			$this->assertSame(422, $e->getCode());
		}
	}//end assertRefused()

	/**
	 * A DOCX and an ODT are accepted and named by kind, whatever the extension's case.
	 *
	 * @return void
	 */
	public function testADocxAndAnOdtAreAccepted(): void {
		$this->assertSame('docx', $this->guard->inspect(fileName: 'Beschikking.DOCX', bytes: OfficeFixtures::docx(paragraphs: ['${zaaknummer}']), maxBytes: OfficeSourceGuard::DEFAULT_MAX_BYTES));
		$this->assertSame('odt', $this->guard->inspect(fileName: 'brief.odt', bytes: OfficeFixtures::bareOdt(), maxBytes: OfficeSourceGuard::DEFAULT_MAX_BYTES));
	}//end testADocxAndAnOdtAreAccepted()

	/**
	 * A macro-enabled extension is refused before the content is read.
	 *
	 * @return void
	 */
	public function testAMacroEnabledExtensionIsRefused(): void {
		$this->assertRefused(OfficeSourceRefusedException::REASON_MACRO, 'brief.docm', OfficeFixtures::bareDocx());
		$this->assertRefused(OfficeSourceRefusedException::REASON_MACRO, 'brief.DOTM', 'anything');
	}//end testAMacroEnabledExtensionIsRefused()

	/**
	 * A DOCX carrying a VBA project, or a macro-enabled content type, is refused.
	 *
	 * @return void
	 */
	public function testADocxCarryingMacrosIsRefused(): void {
		$this->assertRefused(OfficeSourceRefusedException::REASON_MACRO, 'brief.docx', OfficeFixtures::bareDocx(extra: ['word/vbaProject.bin' => 'x']));
		$this->assertRefused(
			OfficeSourceRefusedException::REASON_MACRO,
			'brief.docx',
			OfficeFixtures::bareDocx(types: '<Types><Override ContentType="application/vnd.ms-word.document.macroEnabled.main+xml"/></Types>')
		);
	}//end testADocxCarryingMacrosIsRefused()

	/**
	 * A file over the cap is refused; one exactly at the cap is not.
	 *
	 * @return void
	 */
	public function testAFileOverTheCapIsRefused(): void {
		$docx = OfficeFixtures::bareDocx();
		$this->assertRefused(OfficeSourceRefusedException::REASON_SIZE, 'brief.docx', $docx, strlen($docx) - 1);
		$this->assertSame('docx', $this->guard->inspect(fileName: 'brief.docx', bytes: $docx, maxBytes: strlen($docx)));
	}//end testAFileOverTheCapIsRefused()

	/**
	 * Content that is not what the extension says, or no office file at all, is refused.
	 *
	 * @return void
	 */
	public function testContentThatContradictsTheExtensionIsRefused(): void {
		$this->assertRefused(OfficeSourceRefusedException::REASON_TYPE, 'brief.docx', OfficeFixtures::bareOdt());
		$this->assertRefused(OfficeSourceRefusedException::REASON_TYPE, 'brief.odt', OfficeFixtures::bareDocx());
		$this->assertRefused(OfficeSourceRefusedException::REASON_TYPE, 'brief.docx', '%PDF-1.7 not a package');
		$this->assertRefused(OfficeSourceRefusedException::REASON_TYPE, 'brief.docx', OfficeFixtures::package(entries: ['readme.txt' => 'hi']));
		$this->assertRefused(OfficeSourceRefusedException::REASON_TYPE, 'brief.docx', "PK\x03\x04 broken");
	}//end testContentThatContradictsTheExtensionIsRefused()

	/**
	 * Another extension is refused even when the content is a DOCX.
	 *
	 * @return void
	 */
	public function testAnotherExtensionIsRefused(): void {
		$this->assertRefused(OfficeSourceRefusedException::REASON_TYPE, 'brief.pdf', OfficeFixtures::bareDocx());
		$this->assertRefused(OfficeSourceRefusedException::REASON_TYPE, 'brief', OfficeFixtures::bareDocx());
	}//end testAnotherExtensionIsRefused()
}//end class
