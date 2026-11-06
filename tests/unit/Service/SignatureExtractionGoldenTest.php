<?php

/**
 * Golden records of SigningVerificationService::extractSignatures over a set of PDFs.
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
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * The signature records for each PDF, compared with JSON recorded from the
 * implementation before it was split.
 *
 * @covers \OCA\Filinq\Service\SigningVerificationService
 * @uses   \OCA\Filinq\Service\Signing\AssertionCanonicalizer
 */
class SignatureExtractionGoldenTest extends TestCase {

	private const SECRET = 'golden-verification-secret';

	private const ORIGINAL = "%PDF-1.4\ngovernment besluit content\n%%EOF\n";

	/**
	 * The PDFs, by name.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function pdfs(): array {
		$sig = "\n1 0 obj\n<< /Type /Sig /SubFilter /DocuDesk.SES >>\n";
		$external = "<< /Type /Sig /Filter /Adobe.PPKLite >>\n";
		$marker = static fn (array $data): string => '/DocuDesk-Signature(' . base64_encode((string)json_encode($data)) . ")\n";
		return [
			'no signature' => [self::ORIGINAL],
			'two external signatures' => [self::ORIGINAL . $external . $external],
			'a genuine v2 marker' => [self::v2Signed(['signer' => 'Alice'])],
			'a v2 marker over changed content' => [str_replace('besluit', 'beslu1t', self::v2Signed(['signer' => 'Alice']))],
			'a legacy v1 marker' => [self::ORIGINAL . $sig . $marker(['signer' => 'Bob', 'level' => 'AES', 'method' => 'native', 'mac' => 'abc'])],
			'a marker with no fields' => [self::ORIGINAL . $sig . $marker(['x' => 1])],
			'a marker that is not json' => [self::ORIGINAL . $sig . '/DocuDesk-Signature(bm90IGpzb24=)' . "\n"],
			'a marker with a scalar' => [self::ORIGINAL . $sig . $marker([]) . '/DocuDesk-Signature(' . base64_encode('42') . ")\n"],
			'one marker among two signatures' => [self::ORIGINAL . $sig . $external . $marker(['signer' => 'Carol', 'timestamp' => 't', 'ip' => '10.0.0.1'])],
			'two markers' => [self::ORIGINAL . $sig . $marker(['signer' => 'Dave']) . $marker(['signer' => 'Eve', 'method' => 'portal'])],
			'a marker without a signature entry' => [self::ORIGINAL . $marker(['signer' => 'Frank'])],
			'a SigFlags entry beside one signature' => [self::ORIGINAL . $external . "<< /SigFlags 3 >>\n"],
			'an empty marker' => [self::ORIGINAL . $sig . "/DocuDesk-Signature()\n"],
		];
	}//end pdfs()

	/**
	 * The records match the recorded ones.
	 *
	 * @param string $pdf The PDF content.
	 *
	 * @dataProvider pdfs
	 *
	 * @return void
	 */
	public function testRecordsMatchTheRecordedOnes(string $pdf): void {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => $key === 'signing_verification_secret' ? self::SECRET : $default
		);
		$service = new SigningVerificationService(rootFolder: $this->createMock(IRootFolder::class), config: $config);

		$method = (new ReflectionClass($service))->getMethod('extractSignatures');
		$records = $method->invoke($service, $pdf);

		$path = __DIR__ . '/golden/signature-extraction.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $records;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $records);
	}//end testRecordsMatchTheRecordedOnes()

	/**
	 * A v2 signed PDF, made with the writer's canonical form and MAC formula.
	 *
	 * @param array<string, mixed> $extra Fields over the base assertion.
	 *
	 * @return string The signed bytes.
	 */
	private static function v2Signed(array $extra): string {
		$assemble = static fn (string $payload): string => self::ORIGINAL . "\n1 0 obj\n<< /Type /Sig /SubFilter /DocuDesk.SES >>\n/DocuDesk-Signature(" . $payload . ")\nendobj\n";
		$assertion = array_merge(
			['v' => 2, 'signer' => 'Alice', 'signers' => [], 'timestamp' => '2026-07-23T12:00:00+00:00', 'level' => 'SES', 'method' => 'native', 'ip' => '127.0.0.1'],
			$extra
		);
		$core = (new AssertionCanonicalizer())->canonicalJson(data: $assertion);
		$assertion['mac'] = hash_hmac('sha256', hash('sha256', $assemble('')) . "\n" . $core, self::SECRET);
		return $assemble(base64_encode((string)json_encode($assertion)));
	}//end v2Signed()
}//end class
