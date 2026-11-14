<?php

/**
 * Golden verification results of native SES artifacts, for the acting user.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-signing/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\Signing\AssertionCanonicalizer;
use OCA\Filinq\Service\SigningVerificationService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Each document's verification, compared with JSON recorded from the
 * implementation before verifyAssertion() was split. anna owns file 7; any
 * other user's folder holds nothing.
 *
 * @covers \OCA\Filinq\Service\SigningVerificationService
 * @uses   \OCA\Filinq\Service\Signing\AssertionCanonicalizer
 */
class SigningVerificationGoldenTest extends TestCase {

	private const SECRET = 'secret-1';

	/**
	 * Documents, the configured secret and the user asking.
	 *
	 * @return array<string, array{0: string, 1: string, 2: string}>
	 */
	public static function documents(): array {
		$base = ['v' => 2, 'signer' => 'Anna de Vries', 'signers' => [], 'timestamp' => '2026-10-01T09:30:00+02:00', 'level' => 'SES', 'method' => 'native', 'ip' => '10.0.0.7'];
		$doc = "%PDF-1.4\n%body\n";
		$genuine = self::signed(doc: $doc, assertion: $base);
		return [
			'a genuine signature' => [$genuine, self::SECRET, 'anna'],
			'a genuine signature with portal claims' => [self::signed(doc: $doc, assertion: $base + ['portalSubjectRef' => 'bsn-1']), self::SECRET, 'anna'],
			'a rewritten signer' => [self::rewrite(signed: $genuine, change: ['signer' => 'Mallory']), self::SECRET, 'anna'],
			'a flipped content byte' => [str_replace('%body', '%bodx', $genuine), self::SECRET, 'anna'],
			'a rotated secret' => [$genuine, 'secret-2', 'anna'],
			'no secret' => [$genuine, '', 'anna'],
			'no mac' => [self::rewrite(signed: $genuine, change: ['mac' => null]), self::SECRET, 'anna'],
			'an empty mac' => [self::rewrite(signed: $genuine, change: ['mac' => '']), self::SECRET, 'anna'],
			'a numeric mac' => [self::rewrite(signed: $genuine, change: ['mac' => 12345]), self::SECRET, 'anna'],
			'a v1 assertion' => [self::signed(doc: $doc, assertion: ['v' => 1] + $base), self::SECRET, 'anna'],
			'no version' => [self::rewrite(signed: $genuine, change: ['v' => null]), self::SECRET, 'anna'],
			'a string version' => [self::signed(doc: $doc, assertion: ['v' => '2'] + $base), self::SECRET, 'anna'],
			'a v1 assertion and no secret' => [self::signed(doc: $doc, assertion: ['v' => 1] + $base), '', 'anna'],
			'no mac and no secret' => [self::rewrite(signed: $genuine, change: ['mac' => null]), '', 'anna'],
			'the mac in the content' => [str_replace('%body', '%' . self::macOf(signed: $genuine), $genuine), self::SECRET, 'anna'],
			'no signature' => [$doc, self::SECRET, 'anna'],
			'another user asks' => [$genuine, self::SECRET, 'bob'],
		];
	}//end documents()

	/**
	 * The verification matches the recorded one.
	 *
	 * @param string $content The stored document bytes.
	 * @param string $secret The configured signing secret.
	 * @param string $userId The user asking.
	 *
	 * @dataProvider documents
	 *
	 * @return void
	 */
	public function testVerificationMatchesTheRecordedOne(string $content, string $secret, string $userId): void {
		$file = $this->createMock(File::class);
		$file->method('getContent')->willReturn($content);
		$file->method('getName')->willReturn('besluit.pdf');

		$folders = [];
		$rootFolder = $this->createMock(IRootFolder::class);
		$rootFolder->method('getUserFolder')->willReturnCallback(
			function (string $uid) use ($file, &$folders): Folder {
				$folders[] = $uid;
				$folder = $this->createMock(Folder::class);
				$folder->method('getById')->willReturnCallback(static fn (int $id): array => ($uid === 'anna' && $id === 7) ? [$file] : []);
				return $folder;
			}
		);

		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => ($app === 'filinq' && $key === 'signing_verification_secret') ? $secret : 'wrong-' . $key
		);

		$service = new SigningVerificationService(rootFolder: $rootFolder, config: $config, canonicalizer: new AssertionCanonicalizer());
		try {
			$result = $service->verifyDocument(fileId: 7, userId: $userId);
			unset($result['verifiedAt']);
		} catch (RuntimeException $e) {
			$result = ['refused' => $e->getMessage()];
		}

		$result['folders'] = $folders;

		$path = __DIR__ . '/golden/signing-verification.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $result;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $result);
	}//end testVerificationMatchesTheRecordedOne()

	/**
	 * A document signed the way the native provider signs it.
	 *
	 * @param string $doc The document bytes.
	 * @param array<string, mixed> $assertion The assertion, without its MAC.
	 *
	 * @return string The signed bytes.
	 */
	private static function signed(string $doc, array $assertion): string {
		$canonical = self::marked(doc: $doc, payload: '');
		$core = (new AssertionCanonicalizer())->canonicalJson(data: $assertion);
		$assertion['mac'] = hash_hmac('sha256', hash('sha256', $canonical) . "\n" . $core, self::SECRET);
		return self::marked(doc: $doc, payload: base64_encode((string)json_encode($assertion)));
	}//end signed()

	/**
	 * Rewrite fields in the embedded assertion without re-signing; a null removes the field.
	 *
	 * @param string $signed The signed bytes.
	 * @param array<string, mixed> $change The fields to set.
	 *
	 * @return string The rewritten bytes.
	 */
	private static function rewrite(string $signed, array $change): string {
		preg_match('#/DocuDesk-Signature\(([^)]*)\)#', $signed, $match);
		$assertion = json_decode((string)base64_decode($match[1]), true);
		foreach ($change as $key => $value) {
			unset($assertion[$key]);
			if ($value !== null) {
				$assertion[$key] = $value;
			}
		}

		return str_replace($match[1], base64_encode((string)json_encode($assertion)), $signed);
	}//end rewrite()

	/**
	 * The MAC embedded in signed bytes.
	 *
	 * @param string $signed The signed bytes.
	 *
	 * @return string The MAC.
	 */
	private static function macOf(string $signed): string {
		preg_match('#/DocuDesk-Signature\(([^)]*)\)#', $signed, $match);
		return (string)json_decode((string)base64_decode($match[1]), true)['mac'];
	}//end macOf()

	/**
	 * The document with a signature marker carrying the payload.
	 *
	 * @param string $doc The document bytes.
	 * @param string $payload The marker payload.
	 *
	 * @return string The marked bytes.
	 */
	private static function marked(string $doc, string $payload): string {
		return $doc . "\n1 0 obj\n<< /Type /Sig /SubFilter /DocuDesk.SES >>\n/DocuDesk-Signature(" . $payload . ")\nendobj\n";
	}//end marked()
}//end class
