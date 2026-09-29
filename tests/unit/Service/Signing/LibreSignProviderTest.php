<?php

/**
 * Unit tests for LibreSignProvider
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Signing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/libresign-signing-provider/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Signing;

use OCA\Filinq\Service\Signing\LibreSignClient;
use OCA\Filinq\Service\Signing\LibreSignProvider;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The provider against a fake LibreSign.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class LibreSignProviderTest extends TestCase {

	/**
	 * A LibreSign that remembers what it was asked and answers from a script.
	 *
	 * @var FakeLibreSignClient
	 */
	private FakeLibreSignClient $client;

	/**
	 * Set up the fake.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->client = new FakeLibreSignClient();

	}//end setUp()

	/**
	 * The provider, with the qualified flag as given.
	 *
	 * @param bool $qualified Whether the admin marked the certificate qualified
	 *
	 * @return LibreSignProvider
	 */
	private function provider(bool $qualified = false): LibreSignProvider {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => ($key === 'libresign_qualified') ? ($qualified ? '1' : '0') : $default
		);

		return new LibreSignProvider(config: $config, client: $this->client);

	}//end provider()

	/**
	 * QES is not advertised without a qualified certificate.
	 *
	 * @return void
	 */
	public function testQesIsNotAdvertisedWithoutAQualifiedCertificate(): void {
		$provider = $this->provider();

		$this->assertSame('libresign', $provider->getIdentifier());
		$this->assertTrue($provider->supportsLevel('SES'));
		$this->assertTrue($provider->supportsLevel('AdES'));
		$this->assertFalse($provider->supportsLevel('QES'));
		$this->assertTrue($this->provider(qualified: true)->supportsLevel('QES'));
		$this->assertFalse($this->provider(qualified: true)->supportsLevel('XYZ'));

	}//end testQesIsNotAdvertisedWithoutAQualifiedCertificate()

	/**
	 * An unsupported level is refused, not laundered, and LibreSign is not asked.
	 *
	 * @return void
	 */
	public function testUnsupportedLevelThrows(): void {
		$this->client->status = 3;

		try {
			$this->provider()->produceSignedArtifact('%PDF-original', ['level' => 'QES', 'externalId' => 'ls-1']);
			$this->fail('A QES request must not be served by a non-qualified certificate');
		} catch (RuntimeException $e) {
			$this->assertStringContainsString('QES', $e->getMessage());
		}

		$this->assertSame([], $this->client->calls);

		$this->expectException(RuntimeException::class);
		$this->provider()->initiateSigning('', 'Besluit', [['email' => 'a@example.org']], 'QES', ['fileId' => 12]);

	}//end testUnsupportedLevelThrows()

	/**
	 * A signing request is delegated to LibreSign with the exact payload its API takes.
	 *
	 * @return void
	 */
	public function testASigningRequestIsDelegatedToLibreSign(): void {
		$result = $this->provider()->initiateSigning(
			'',
			'Besluit 12',
			[
				['userId' => 'jan', 'displayName' => 'Jan'],
				['email' => 'piet@example.org', 'displayName' => 'Piet'],
			],
			'AdES',
			['fileId' => 12]
		);

		$this->assertSame('ls-uuid-1', $result['externalId']);
		$this->assertTrue($result['success']);
		$this->assertSame(
			[
				'requestSignature',
				['nodeId' => 12],
				'Besluit 12',
				[
					[
						'identifyMethods' => [['method' => 'account', 'value' => 'jan', 'requirement' => 'required']],
						'displayName' => 'Jan',
					],
					[
						'identifyMethods' => [['method' => 'email', 'value' => 'piet@example.org', 'requirement' => 'required']],
						'displayName' => 'Piet',
					],
				],
			],
			$this->client->calls[0]
		);

	}//end testASigningRequestIsDelegatedToLibreSign()

	/**
	 * A signer LibreSign cannot identify is refused before anything is sent.
	 *
	 * @return void
	 */
	public function testASignerWithoutAccountOrEmailIsRefused(): void {
		$this->expectException(\InvalidArgumentException::class);
		try {
			$this->provider()->initiateSigning('', 'Besluit', [['displayName' => 'Nobody']], 'SES', ['fileId' => 12]);
		} finally {
			$this->assertSame([], $this->client->calls);
		}

	}//end testASignerWithoutAccountOrEmailIsRefused()

	/**
	 * Signer flow status is read from LibreSign, mapped to the request states.
	 *
	 * @return void
	 */
	public function testSignerFlowStatusIsReadFromLibreSign(): void {
		$expected = [
			0 => 'PENDING',
			1 => 'PENDING',
			2 => 'IN_PROGRESS',
			3 => 'COMPLETED',
			4 => 'CANCELLED',
			5 => 'IN_PROGRESS',
			6 => 'CANCELLED',
		];
		foreach ($expected as $libreSignStatus => $status) {
			$this->client->status = $libreSignStatus;
			$result = $this->provider()->checkStatus('ls-1');
			$this->assertSame($status, $result['status'], 'LibreSign status ' . $libreSignStatus);
		}

		$this->assertSame(
			[['displayName' => 'Jan', 'signedAt' => '2026-09-29T10:00:00+00:00']],
			$result['signers']
		);

	}//end testSignerFlowStatusIsReadFromLibreSign()

	/**
	 * Incomplete LibreSign result never yields the unsigned original.
	 *
	 * @return void
	 */
	public function testHonestCompletionGate(): void {
		foreach ([0, 1, 2, 5] as $status) {
			$this->client->status = $status;
			try {
				$this->provider()->produceSignedArtifact('%PDF-original', ['level' => 'SES', 'externalId' => 'ls-1']);
				$this->fail('Status ' . $status . ' is not signed');
			} catch (RuntimeException $e) {
				$this->assertStringContainsString('not signed', $e->getMessage());
			}

			try {
				$this->provider()->downloadSignedDocument('ls-1');
				$this->fail('Status ' . $status . ' is not signed');
			} catch (RuntimeException $e) {
				$this->assertStringContainsString('not signed', $e->getMessage());
			}
		}

		// Signed, but what came back is not a PDF: refused too.
		$this->client->status = 3;
		$this->client->signed = '<html>login</html>';
		$this->expectException(RuntimeException::class);
		$this->provider()->downloadSignedDocument('ls-1');

	}//end testHonestCompletionGate()

	/**
	 * Without a LibreSign request there is nothing to hand back.
	 *
	 * @return void
	 */
	public function testNoExternalIdIsRefused(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('no LibreSign request');
		$this->provider()->produceSignedArtifact('%PDF-original', ['level' => 'SES']);

	}//end testNoExternalIdIsRefused()

	/**
	 * A signed request hands back LibreSign's signed bytes, never the original.
	 *
	 * @return void
	 */
	public function testASignedRequestHandsBackTheSignedFile(): void {
		$this->client->status = 3;

		$bytes = $this->provider()->produceSignedArtifact('%PDF-original', ['level' => 'AdES', 'externalId' => 'ls-1']);

		$this->assertSame('%PDF-1.7 signed by LibreSign', $bytes);
		$this->assertSame(['downloadSigned', 'ls-1'], end($this->client->calls));

	}//end testASignedRequestHandsBackTheSignedFile()

	/**
	 * Cancelling deletes the request in LibreSign by the file it was made for.
	 *
	 * @return void
	 */
	public function testCancelDeletesTheLibreSignRequest(): void {
		$this->client->status = 1;
		$this->provider()->cancelSigning('ls-1');

		$this->assertSame(['deleteRequest', 77], end($this->client->calls));

	}//end testCancelDeletesTheLibreSignRequest()
}//end class

/**
 * A scripted LibreSign.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class FakeLibreSignClient implements LibreSignClient {

	/**
	 * Every call, in order.
	 *
	 * @var array<int, array<int, mixed>>
	 */
	public array $calls = [];

	/**
	 * The status validate() reports.
	 *
	 * @var int
	 */
	public int $status = 1;

	/**
	 * The bytes downloadSigned() returns.
	 *
	 * @var string
	 */
	public string $signed = '%PDF-1.7 signed by LibreSign';

	/**
	 * {@inheritDoc}
	 */
	public function requestSignature(array $file, string $name, array $signers): array {
		$this->calls[] = ['requestSignature', $file, $name, $signers];
		return ['uuid' => 'ls-uuid-1', 'status' => 1, 'nodeId' => 12];

	}//end requestSignature()

	/**
	 * {@inheritDoc}
	 */
	public function validate(string $uuid): array {
		$this->calls[] = ['validate', $uuid];
		return [
			'uuid' => $uuid,
			'status' => $this->status,
			'nodeId' => 77,
			'signers' => [['displayName' => 'Jan', 'sign_date' => '2026-09-29T10:00:00+00:00']],
		];

	}//end validate()

	/**
	 * {@inheritDoc}
	 */
	public function downloadSigned(string $uuid): string {
		$this->calls[] = ['downloadSigned', $uuid];
		return $this->signed;

	}//end downloadSigned()

	/**
	 * {@inheritDoc}
	 */
	public function deleteRequest(int $nodeId): void {
		$this->calls[] = ['deleteRequest', $nodeId];

	}//end deleteRequest()
}//end class
