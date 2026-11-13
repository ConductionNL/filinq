<?php

/**
 * Unit tests for IdTokenValidator
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

use OCA\Filinq\Service\SignerAuth\IdTokenValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The ID token's shape and claims: issuer, audience, expiry and issued-at
 * with a minute of skew, nonce and subject. The token is decoded, not
 * signature-checked, so every claim check is all that stands between a
 * forged token and a signer identity.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[CoversClass(IdTokenValidator::class)]
class IdTokenValidatorTest extends TestCase {

	private const NOW = 1800000000;

	/**
	 * Base64url-encode a JSON part.
	 *
	 * @param array $data The part.
	 *
	 * @return string
	 */
	private static function part(array $data): string {
		return rtrim(strtr(base64_encode((string)json_encode($data)), '+/', '-_'), '=');
	}//end part()

	/**
	 * A compact token with the given claims over the base claims.
	 *
	 * @param array $claims Claims to override (null removes one).
	 * @param array $header The header.
	 *
	 * @return string
	 */
	private static function token(array $claims = [], array $header = ['alg' => 'RS256']): string {
		$claims = array_filter(
			array_merge(
				[
					'iss' => 'https://broker.example',
					'aud' => 'filinq',
					'exp' => self::NOW + 300,
					'iat' => self::NOW,
					'nonce' => 'n-123',
					'sub' => 'subject-1',
				],
				$claims
			),
			static fn ($value): bool => $value !== null
		);

		return self::part($header) . '.' . self::part($claims) . '.signature';
	}//end token()

	/**
	 * Validate a token against the standard expectations.
	 *
	 * @param string $token The token.
	 * @param string $nonce The expected nonce.
	 *
	 * @return array The claims.
	 */
	private static function validate(string $token, string $nonce = 'n-123'): array {
		return (new IdTokenValidator())->validate($token, 'https://broker.example', 'filinq', $nonce, self::NOW);
	}//end validate()

	/**
	 * A valid token returns its claims.
	 *
	 * @return void
	 */
	public function testAValidTokenReturnsItsClaims(): void {
		$claims = self::validate(self::token(['acr' => 'substantial']));

		$this->assertSame('subject-1', $claims['sub']);
		$this->assertSame('substantial', $claims['acr']);
	}//end testAValidTokenReturnsItsClaims()

	/**
	 * Claims within a minute of skew still pass.
	 *
	 * @return void
	 */
	public function testAMinuteOfClockSkewIsAllowed(): void {
		$claims = self::validate(self::token(['exp' => self::NOW - 59, 'iat' => self::NOW + 60]));

		$this->assertSame(self::NOW - 59, $claims['exp']);
	}//end testAMinuteOfClockSkewIsAllowed()

	/**
	 * An audience list naming us is accepted alone, or with us as authorised party.
	 *
	 * @return void
	 */
	public function testAnAudienceListNamingUsIsAccepted(): void {
		$this->assertSame(['filinq'], self::validate(self::token(['aud' => ['filinq']]))['aud']);
		$this->assertSame('filinq', self::validate(self::token(['aud' => ['other', 'filinq'], 'azp' => 'filinq']))['azp']);
	}//end testAnAudienceListNamingUsIsAccepted()

	/**
	 * Tokens that fail a check, and the message naming that check.
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function refusedTokens(): array {
		return [
			'two parts'                    => ['a.b', 'ID token is not a compact JWT'],
			'four parts'                   => ['a.b.c.d', 'ID token is not a compact JWT'],
			'alg none'                     => [self::token([], ['alg' => 'none']), 'ID token declares no algorithm'],
			'alg NONE'                     => [self::token([], ['alg' => 'NONE']), 'ID token declares no algorithm'],
			'no alg'                       => [self::token([], ['typ' => 'JWT']), 'ID token declares no algorithm'],
			'header not json'              => ['bm90IGpzb24.' . self::part(['sub' => 'x']) . '.s', 'ID token part is not JSON'],
			'claims not json'              => [self::part(['alg' => 'RS256']) . '.!!!.s', 'ID token part is not JSON'],
			'other issuer'                 => [self::token(['iss' => 'https://evil.example']), 'ID token failed the issuer check'],
			'other audience'               => [self::token(['aud' => 'other']), 'ID token failed the audience check'],
			'audience list without us'     => [self::token(['aud' => ['other']]), 'ID token failed the audience check'],
			'shared audience, no azp'      => [self::token(['aud' => ['other', 'filinq']]), 'ID token failed the audience check'],
			'shared audience, other azp'   => [self::token(['aud' => ['other', 'filinq'], 'azp' => 'other']), 'ID token failed the audience check'],
			'audience a number'            => [self::token(['aud' => 7]), 'ID token failed the audience check'],
			'expired beyond skew'          => [self::token(['exp' => self::NOW - 60]), 'ID token failed the expiry check'],
			'no expiry'                    => [self::token(['exp' => null]), 'ID token failed the expiry check'],
			'issued beyond skew'           => [self::token(['iat' => self::NOW + 61]), 'ID token failed the issued-at check'],
			'issued-at not numeric'        => [self::token(['iat' => 'soon']), 'ID token failed the issued-at check'],
			'other nonce'                  => [self::token(['nonce' => 'n-999']), 'ID token failed the nonce check'],
			'no nonce'                     => [self::token(['nonce' => null]), 'ID token failed the nonce check'],
			'empty subject'                => [self::token(['sub' => '']), 'ID token failed the subject check'],
			'numeric subject'              => [self::token(['sub' => 42]), 'ID token failed the subject check'],
		];
	}//end refusedTokens()

	/**
	 * Every failed check refuses the token with its own message.
	 *
	 * @param string $token   The token.
	 * @param string $message The expected message.
	 *
	 * @return void
	 */
	#[DataProvider('refusedTokens')]
	public function testAFailedCheckRefusesTheToken(string $token, string $message): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage($message);

		self::validate($token);
	}//end testAFailedCheckRefusesTheToken()

	/**
	 * An empty expected nonce never matches, not even an empty claim.
	 *
	 * @return void
	 */
	public function testAnEmptyExpectedNonceIsRefused(): void {
		$this->expectExceptionMessage('ID token failed the nonce check');

		self::validate(self::token(['nonce' => '']), '');
	}//end testAnEmptyExpectedNonceIsRefused()
}//end class
