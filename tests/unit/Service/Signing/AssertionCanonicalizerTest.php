<?php

/**
 * Unit tests for AssertionCanonicalizer.
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

use OCA\Filinq\Service\Signing\AssertionCanonicalizer;
use PHPUnit\Framework\TestCase;

/**
 * The canonical JSON a signing assertion is hashed over.
 *
 * @covers \OCA\Filinq\Service\Signing\AssertionCanonicalizer
 */
class AssertionCanonicalizerTest extends TestCase {

	/**
	 * Keys are sorted at every depth; list order is kept.
	 *
	 * @return void
	 */
	public function testKeysAreSortedRecursively(): void {
		$json = (new AssertionCanonicalizer())->canonicalJson(
			data: ['zeta' => 1, 'alpha' => ['b' => 2, 'a' => ['y' => true, 'x' => null]], 'list' => [3, 1, 2]]
		);

		self::assertSame('{"alpha":{"a":{"x":null,"y":true},"b":2},"list":[3,1,2],"zeta":1}', $json);
	}//end testKeysAreSortedRecursively()

	/**
	 * Two arrays that differ only in key order produce the same text.
	 *
	 * @return void
	 */
	public function testKeyOrderDoesNotChangeTheOutput(): void {
		$canonicalizer = new AssertionCanonicalizer();

		self::assertSame(
			$canonicalizer->canonicalJson(data: ['signer' => ['name' => 'A', 'id' => 1], 'at' => 'now']),
			$canonicalizer->canonicalJson(data: ['at' => 'now', 'signer' => ['id' => 1, 'name' => 'A']])
		);
	}//end testKeyOrderDoesNotChangeTheOutput()

	/**
	 * Slashes and non-ASCII characters are written as is.
	 *
	 * @return void
	 */
	public function testSlashesAndUnicodeAreNotEscaped(): void {
		self::assertSame(
			'{"name":"Zoë","url":"https://x.test/a/b"}',
			(new AssertionCanonicalizer())->canonicalJson(data: ['url' => 'https://x.test/a/b', 'name' => 'Zoë'])
		);
	}//end testSlashesAndUnicodeAreNotEscaped()

	/**
	 * Text that cannot be encoded answers an empty string rather than failing.
	 *
	 * @return void
	 */
	public function testUnencodableTextAnswersAnEmptyString(): void {
		self::assertSame('', (new AssertionCanonicalizer())->canonicalJson(data: ['bad' => "\xB1\x31"]));
	}//end testUnencodableTextAnswersAnEmptyString()
}//end class
