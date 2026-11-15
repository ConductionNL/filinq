<?php

/**
 * Order tests for PortalAssertionVerifier's rejection reasons
 *
 * A token that fails two guards must be refused by the earlier one, so the
 * logged reason shows the guard order: use before issuer, lifetime before
 * subject.
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
 * The first failing guard names the refusal.
 *
 * @covers \OCA\Filinq\Portal\PortalAssertionVerifier
 */
class PortalAssertionVerifierReasonOrderTest extends TestCase {

	private const SECRET = 'filinq-test-secret-0123456789';

	/**
	 * An expired token without a subject is refused for its lifetime.
	 *
	 * @return void
	 */
	public function testLifetimeIsCheckedBeforeTheSubject(): void {
		$now = time();

		$this->assertSame(['expired or missing exp'], $this->reasons(claims: ['sub' => '', 'exp' => ($now - 1), 'iat' => ($now - 10)]));

	}//end testLifetimeIsCheckedBeforeTheSubject()

	/**
	 * A session token from another issuer is refused as not an assertion.
	 *
	 * @return void
	 */
	public function testUseIsCheckedBeforeTheIssuer(): void {
		$this->assertSame(['not an assertion'], $this->reasons(claims: ['use' => 'session', 'iss' => 'elsewhere']));

	}//end testUseIsCheckedBeforeTheIssuer()

	/**
	 * Verify a signed token with $claims over valid defaults, and return the logged reasons.
	 *
	 * @param array<string, mixed> $claims Overrides.
	 *
	 * @return array<int, string> The reasons logged.
	 */
	private function reasons(array $claims): array {
		$reasons = [];
		$logger = $this->createMock(LoggerInterface::class);
		$logger->method('debug')->willReturnCallback(
			static function (string|\Stringable $message, array $context = []) use (&$reasons): void {
				$reasons[] = $context['reason'];
			}
		);

		$now = time();
		$all = array_merge(['sub' => 'subject-0', 'use' => 'assertion', 'iss' => 'portaliq', 'iat' => $now, 'exp' => ($now + 60)], $claims);
		$b64 = static fn (string $bytes): string => rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
		$hPart = $b64((string)json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
		$cPart = $b64((string)json_encode($all));
		$jwt = $hPart . '.' . $cPart . '.' . $b64(hash_hmac('sha256', $hPart . '.' . $cPart, self::SECRET, true));

		$this->assertNull((new PortalAssertionVerifier(secretOverride: self::SECRET, logger: $logger))->verify($jwt));

		return $reasons;
	}//end reasons()
}//end class
