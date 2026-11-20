<?php

/**
 * SealedArtifact: the sealed bytes, their record and the sibling file name.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Kernel
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Kernel;

use OCA\Filinq\Kernel\SealedArtifact;
use PHPUnit\Framework\TestCase;

/**
 * Sibling names keep the source's base name and add the suffix; the artifact
 * carries the bytes and record it was built with.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Kernel
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Kernel\SealedArtifact
 */
class SealedArtifactTest extends TestCase {

	/**
	 * Sibling names keep the base and add the suffix.
	 *
	 * @return void
	 */
	public function testSiblingName(): void {
		$this->assertSame('besluit-gewaarmerkt.pdf', SealedArtifact::siblingName(sourceName: 'besluit.pdf', suffix: 'gewaarmerkt'));
		$this->assertSame('a.b-x.pdf', SealedArtifact::siblingName(sourceName: 'a.b.docx', suffix: 'x'));
		$this->assertSame('document-x.pdf', SealedArtifact::siblingName(sourceName: '', suffix: 'x'));
	}//end testSiblingName()

	/**
	 * The artifact exposes the bytes and record it was built with.
	 *
	 * @return void
	 */
	public function testCarriesItsBytesAndRecord(): void {
		$artifact = new SealedArtifact(pdf: '%PDF sealed', record: ['verificationCode' => 'ABC']);
		$this->assertSame('%PDF sealed', $artifact->pdf);
		$this->assertSame(['verificationCode' => 'ABC'], $artifact->record);
	}//end testCarriesItsBytesAndRecord()
}//end class
