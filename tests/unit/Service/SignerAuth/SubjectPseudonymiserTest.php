<?php

/**
 * Unit tests for SubjectPseudonymiser
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\SignerAuth
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\SignerAuth;

use OCA\Filinq\Service\SignerAuth\SubjectPseudonymiser;
use OCP\IConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Signer pseudonyms: stable per issuer and subject, salted with the
 * instance secret, letters only after the prefix, and refused without a
 * subject or a secret.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[CoversClass(SubjectPseudonymiser::class)]
class SubjectPseudonymiserTest extends TestCase {

	/**
	 * A pseudonymiser over an instance with the given secret.
	 *
	 * @param string $secret The instance secret.
	 *
	 * @return SubjectPseudonymiser
	 */
	private function pseudonymiser(string $secret): SubjectPseudonymiser {
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueString')->with('secret', '')->willReturn($secret);
		return new SubjectPseudonymiser($config);
	}//end pseudonymiser()

	/**
	 * The pseudonym is the keyed digest with its hex digits mapped to letters.
	 *
	 * @return void
	 */
	public function testThePseudonymIsTheKeyedDigestInLetters(): void {
		$key = hash_hmac('sha256', 'filinq-signer-pseudonym', 'instance-secret');
		$digest = substr(hash_hmac('sha256', "https://broker.example\nsubject-1", $key), 0, 40);

		$pseudonym = $this->pseudonymiser('instance-secret')->pseudonym('https://broker.example', 'subject-1');

		$this->assertSame('ps-' . strtr($digest, '0123456789abcdef', 'ghijklmnopabcdef'), $pseudonym);
		$this->assertMatchesRegularExpression('/^ps-[a-p]{40}$/', $pseudonym);
	}//end testThePseudonymIsTheKeyedDigestInLetters()

	/**
	 * The same signer gets the same pseudonym; another subject, issuer or
	 * instance secret gets another.
	 *
	 * @return void
	 */
	public function testThePseudonymIsStableAndSeparatesSignersIssuersAndInstances(): void {
		$one = $this->pseudonymiser('s-1');
		$pseudonym = $one->pseudonym('iss', 'sub');

		$this->assertSame($pseudonym, $one->pseudonym('iss', 'sub'));
		$this->assertNotSame($pseudonym, $one->pseudonym('iss', 'sub-2'));
		$this->assertNotSame($pseudonym, $one->pseudonym('iss-2', 'sub'));
		$this->assertNotSame($pseudonym, $this->pseudonymiser('s-2')->pseudonym('iss', 'sub'));
	}//end testThePseudonymIsStableAndSeparatesSignersIssuersAndInstances()

	/**
	 * An empty subject is refused before the secret is read.
	 *
	 * @return void
	 */
	public function testAnEmptySubjectIsRefused(): void {
		$config = $this->createMock(IConfig::class);
		$config->expects($this->never())->method('getSystemValueString');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Cannot pseudonymise an empty subject');

		(new SubjectPseudonymiser($config))->pseudonym('iss', '');
	}//end testAnEmptySubjectIsRefused()

	/**
	 * An instance without a secret refuses rather than hashing unsalted.
	 *
	 * @return void
	 */
	public function testAnInstanceWithoutASecretIsRefused(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('This instance has no secret to salt signer pseudonyms with');

		$this->pseudonymiser('')->pseudonym('iss', 'sub');
	}//end testAnInstanceWithoutASecretIsRefused()
}//end class
