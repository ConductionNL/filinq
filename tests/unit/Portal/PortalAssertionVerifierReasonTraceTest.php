<?php

/**
 * Characterization tests for PortalAssertionVerifier's rejection reasons
 *
 * Every fail-closed guard is pinned by the reason it logs, so a guard that
 * stops firing, or fires in the wrong order, is visible even when a later
 * guard would also have refused the token.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Portal
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/portal-signing-actions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Portal;

use OCA\Filinq\Portal\PortalAssertionVerifier;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The reason each guard logs.
 *
 * @covers \OCA\Filinq\Portal\PortalAssertionVerifier
 */
class PortalAssertionVerifierReasonTraceTest extends TestCase {

	private const SECRET = 'filinq-test-secret-0123456789';

	/**
	 * Each malformed or hostile token is refused with its own reason.
	 *
	 * @return void
	 */
	public function testEachGuardLogsItsOwnReason(): void {
		$now = time();
		$cases = [
			'no usable signing secret' => [$this->token(), 'short'],
			'malformed structure (two parts)' => ['a.b', self::SECRET],
			'malformed structure (empty part)' => ['a..c', self::SECRET],
			'unexpected algorithm' => [$this->token(alg: 'none'), self::SECRET],
			'signature mismatch' => [$this->token(secret: 'attacker-secret-9876543210abcd'), self::SECRET],
			'malformed claims' => [$this->signed(claimsJson: '"just a string"'), self::SECRET],
			'not an assertion' => [$this->token(claims: ['use' => 'session']), self::SECRET],
			'unexpected issuer' => [$this->token(claims: ['iss' => 'elsewhere']), self::SECRET],
			'expired or missing exp' => [$this->token(claims: ['exp' => '9999999999']), self::SECRET],
			'implausible iat' => [$this->token(claims: ['iat' => ($now + 3600), 'exp' => ($now + 7200)]), self::SECRET],
			'missing subject' => [$this->token(claims: ['sub' => '']), self::SECRET],
			'missing subject (not a string)' => [$this->token(claims: ['sub' => 5]), self::SECRET],
			'implausible iat (after exp)' => [$this->token(claims: ['iat' => ($now + 30), 'exp' => ($now + 10)]), self::SECRET],
			'implausible iat (not an int)' => [$this->token(claims: ['iat' => (string)$now]), self::SECRET],
		];

		foreach ($cases as $label => [$jwt, $secret]) {
			$reasons = [];
			$this->assertNull($this->verifier(secret: $secret, reasons: $reasons)->verify($jwt), $label);
			$this->assertSame([preg_replace('/ \(.*\)$/', '', $label)], $reasons, $label);
		}

	}//end testEachGuardLogsItsOwnReason()

	/**
	 * A token is accepted with exp one second ahead, and refused at exp == now.
	 *
	 * @return void
	 */
	public function testExpIsStrictlyInTheFuture(): void {
		$reasons = [];
		$now = time();
		$this->assertNotNull($this->verifier(secret: self::SECRET, reasons: $reasons)->verify($this->token(claims: ['iat' => ($now - 10), 'exp' => ($now + 5)])));

		do {
			$now = time();
			$verdict = $this->verifier(secret: self::SECRET, reasons: $reasons)->verify($this->token(claims: ['iat' => ($now - 10), 'exp' => $now]));
		} while (time() !== $now);

		$this->assertNull($verdict);
		$this->assertSame(['expired or missing exp'], $reasons);

	}//end testExpIsStrictlyInTheFuture()

	/**
	 * The iat leeway is sixty seconds and no more.
	 *
	 * @return void
	 */
	public function testIatLeewayIsSixtySeconds(): void {
		$reasons = [];
		$now = time();
		$this->assertNotNull($this->verifier(secret: self::SECRET, reasons: $reasons)->verify($this->token(claims: ['iat' => ($now + 60), 'exp' => ($now + 600)])));

		// Mint and verify within one clock second, so the boundary is exact.
		do {
			$now = time();
			$verdict = $this->verifier(secret: self::SECRET, reasons: $reasons)->verify($this->token(claims: ['iat' => ($now + 61), 'exp' => ($now + 600)]));
		} while (time() !== $now);

		$this->assertNull($verdict);
		$this->assertSame(['implausible iat'], $reasons);

	}//end testIatLeewayIsSixtySeconds()

	/**
	 * A valid token comes back with its claims and logs nothing.
	 *
	 * @return void
	 */
	public function testValidTokenReturnsItsClaims(): void {
		$reasons = [];
		$claims = $this->verifier(secret: self::SECRET, reasons: $reasons)->verify($this->token(claims: ['sub' => 'subject-1']));

		$this->assertSame('subject-1', $claims['sub']);
		$this->assertSame('assertion', $claims['use']);
		$this->assertSame([], $reasons);

	}//end testValidTokenReturnsItsClaims()

	/**
	 * A verifier whose logger records each rejection reason.
	 *
	 * @param string $secret The signing secret.
	 * @param array<int, string> $reasons Recorded reasons, by reference; reset on each call.
	 *
	 * @return PortalAssertionVerifier The verifier.
	 */
	private function verifier(string $secret, array &$reasons): PortalAssertionVerifier {
		$reasons = [];
		$logger = $this->createMock(LoggerInterface::class);
		$logger->method('debug')->willReturnCallback(
			static function (string|\Stringable $message, array $context = []) use (&$reasons): void {
				$reasons[] = $context['reason'];
			}
		);

		return new PortalAssertionVerifier(secretOverride: $secret, logger: $logger);
	}//end verifier()

	/**
	 * A signed assertion with $claims overriding the defaults.
	 *
	 * @param array<string, mixed> $claims Overrides.
	 * @param string $alg The header algorithm.
	 * @param string $secret The signing secret.
	 *
	 * @return string The compact JWT.
	 */
	private function token(array $claims = [], string $alg = 'HS256', string $secret = self::SECRET): string {
		$now = time();
		$all = array_merge(['sub' => 'subject-0', 'use' => 'assertion', 'iss' => 'portaliq', 'iat' => $now, 'exp' => ($now + 60)], $claims);
		$hPart = $this->b64(bytes: (string)json_encode(['alg' => $alg, 'typ' => 'JWT']));
		$cPart = $this->b64(bytes: (string)json_encode($all));

		return $hPart . '.' . $cPart . '.' . $this->b64(bytes: hash_hmac('sha256', $hPart . '.' . $cPart, $secret, true));
	}//end token()

	/**
	 * A correctly signed HS256 token whose claims segment is $claimsJson.
	 *
	 * @param string $claimsJson The raw claims JSON.
	 *
	 * @return string The compact JWT.
	 */
	private function signed(string $claimsJson): string {
		$hPart = $this->b64(bytes: (string)json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
		$cPart = $this->b64(bytes: $claimsJson);

		return $hPart . '.' . $cPart . '.' . $this->b64(bytes: hash_hmac('sha256', $hPart . '.' . $cPart, self::SECRET, true));
	}//end signed()

	/**
	 * Base64url without padding.
	 *
	 * @param string $bytes The bytes.
	 *
	 * @return string The encoding.
	 */
	private function b64(string $bytes): string {
		return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
	}//end b64()
}//end class
