<?php

/**
 * Golden native SES artifacts: the bytes, the assertion and what was drawn.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-signing/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Signing;

use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\Signing\AssertionCanonicalizer;
use OCA\Filinq\Service\Signing\FieldPlacementRenderer;
use OCA\Filinq\Service\Signing\NativeSigningProvider;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Each signing context's artifact, compared with JSON recorded from the
 * implementation before produceSignedArtifact() was split.
 *
 * @covers \OCA\Filinq\Service\Signing\NativeSigningProvider
 * @uses   \OCA\Filinq\Service\Signing\AssertionCanonicalizer
 */
class NativeSignedArtifactGoldenTest extends TestCase {

	/** @var array<int, array<string, mixed>> The render calls of the placement renderer. */
	private array $drawn = [];

	/**
	 * Signing contexts and the configured secret.
	 *
	 * @return array<string, array{0: array<string, mixed>, 1: string}>
	 */
	public static function contexts(): array {
		$at = '2026-10-01T09:30:00+02:00';
		$base = ['level' => 'SES', 'signer' => 'Anna de Vries', 'signers' => [['name' => 'Anna de Vries', 'email' => 'anna@example.nl']], 'timestamp' => $at, 'ip' => '10.0.0.7'];
		$portal = ['portalSubjectRef' => 'bsn-hash-1', 'portalIdentityRef' => 'digid:1', 'portalTrust' => 'substantial', 'portalJti' => 'jti-9'];
		$placements = [['page' => 1, 'x' => 10, 'y' => 20, 'signer' => 0]];
		return [
			'a plain SES signature' => [$base, 'secret-1'],
			'another secret' => [$base, 'secret-2'],
			'the defaults' => [['timestamp' => $at], 'secret-1'],
			'an unsupported level' => [['level' => 'AES'] + $base, 'secret-1'],
			'a lower case level' => [['level' => 'ses'] + $base, 'secret-1'],
			'no secret' => [$base, ''],
			'an unsupported level and no secret' => [['level' => 'QES'] + $base, ''],
			'portal claims' => [$base + $portal, 'secret-1'],
			'empty and missing portal claims' => [['portalSubjectRef' => '', 'portalTrust' => null, 'portalJti' => 42] + $base, 'secret-1'],
			'forged assertion fields are not taken from the context' => [['method' => 'forged', 'v' => 9, 'mac' => 'abc', 'extra' => 'x'] + $base, 'secret-1'],
			'consent and evidence lists' => [['consentBasis' => [['guardian' => 'Piet']], 'signerEvidence' => [['provider' => 'digid', 'assurance' => 'substantial']]] + $base, 'secret-1'],
			'empty and non-list bound fields' => [['consentBasis' => [], 'signerEvidence' => 'digid'] + $base, 'secret-1'],
			'field placements' => [['fieldPlacements' => $placements, 'signerLabels' => ['Anna de Vries']] + $base, 'secret-1'],
			'field placements without labels' => [['fieldPlacements' => $placements] + $base, 'secret-1'],
			'no field placements' => [['fieldPlacements' => []] + $base, 'secret-1'],
			'field placements that are not a list' => [['fieldPlacements' => 'page 1'] + $base, 'secret-1'],
			'everything' => [$base + $portal + ['consentBasis' => [['guardian' => 'Piet']], 'fieldPlacements' => $placements, 'signerLabels' => ['A']], 'secret-1'],
			'no timestamp' => [array_diff_key($base, ['timestamp' => true]), 'secret-1'],
		];
	}//end contexts()

	/**
	 * The artifact matches the recorded one.
	 *
	 * @param array<string, mixed> $context The signing context.
	 * @param string $secret The configured signing secret.
	 *
	 * @dataProvider contexts
	 *
	 * @return void
	 */
	public function testArtifactMatchesTheRecordedOne(array $context, string $secret): void {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => ($app === 'filinq' && $key === 'signing_verification_secret') ? $secret : 'wrong-' . $key
		);

		$renderer = $this->createMock(FieldPlacementRenderer::class);
		$renderer->method('render')->willReturnCallback(
			function (string $pdf, array $placements, array $signers, string $timestamp): string {
				$this->drawn[] = ['pdf' => $pdf, 'placements' => $placements, 'signers' => $signers, 'timestamp' => $timestamp];
				return $pdf . "% drawn\n";
			}
		);

		$provider = new NativeSigningProvider(
			logger: $this->createMock(LoggerInterface::class),
			settingsService: $this->createMock(SettingsService::class),
			config: $config,
			canonicalizer: new AssertionCanonicalizer(),
			placementRenderer: $renderer,
		);

		try {
			$bytes = $provider->produceSignedArtifact(documentContent: "%PDF-1.4\n%body\n", context: $context);
			preg_match('#/DocuDesk-Signature\(([^)]*)\)#', $bytes, $match);
			$assertion = json_decode((string)base64_decode($match[1]), true);
			$result = ['bytes' => $bytes, 'assertion' => $assertion];
			if (isset($context['timestamp']) === false) {
				$this->assertNotFalse(\DateTimeImmutable::createFromFormat(DATE_ATOM, $assertion['timestamp']));
				$assertion['timestamp'] = '<now>';
				unset($assertion['mac']);
				$result = ['assertion' => $assertion];
			}
		} catch (RuntimeException $e) {
			$result = ['refused' => $e->getMessage()];
		}

		$result['drawn'] = $this->drawn;

		$path = __DIR__ . '/golden/native-signed-artifact.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			if (is_dir(dirname($path)) === false) {
				mkdir(dirname($path));
			}

			$recorded[$name] = $result;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $result);
	}//end testArtifactMatchesTheRecordedOne()
}//end class
