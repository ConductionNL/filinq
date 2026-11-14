<?php

/**
 * Unit tests for ValidSignProvider artifact refusal and config parsing.
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

use OCA\Filinq\Service\Signing\ValidSignProvider;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * ValidSign never produces an artifact, and a malformed config reads as unconfigured.
 *
 * @covers \OCA\Filinq\Service\Signing\ValidSignProvider
 */
class ValidSignProviderArtifactTest extends TestCase {

	/**
	 * Build the provider over a raw config value.
	 *
	 * @param string $configJson The stored provider config.
	 *
	 * @return ValidSignProvider
	 */
	private function provider(string $configJson = '{}'): ValidSignProvider {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->with('filinq', 'signing_provider_config', '{}')->willReturn($configJson);
		return new ValidSignProvider(config: $config);
	}//end provider()

	/**
	 * An unsupported level is refused by name before anything else.
	 *
	 * @return void
	 */
	public function testUnsupportedLevelIsRefusedByName(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('ValidSign provider does not support signature level: XYZ');

		$this->provider()->produceSignedArtifact(documentContent: '%PDF', context: ['level' => 'XYZ']);
	}//end testUnsupportedLevelIsRefusedByName()

	/**
	 * A supported (or defaulted) level is still refused: the original is never passed off as signed.
	 *
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public static function supportedContexts(): array {
		return [
			'default level' => [[]],
			'QES'           => [['level' => 'QES']],
		];
	}//end supportedContexts()

	/**
	 * Each supported context throws the not-implemented refusal.
	 *
	 * @param array<string, mixed> $context The signing context.
	 *
	 * @dataProvider supportedContexts
	 *
	 * @return void
	 */
	public function testSupportedLevelsStillProduceNothing(array $context): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('ValidSign cannot yet produce a signed artifact');

		$this->provider()->produceSignedArtifact(documentContent: '%PDF', context: $context);
	}//end testSupportedLevelsStillProduceNothing()

	/**
	 * A config that is not a JSON object counts as not configured.
	 *
	 * @return void
	 */
	public function testNonObjectConfigIsNotConfigured(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('ValidSign provider is not configured.');

		$this->provider(configJson: '"sourceId"')->initiateSigning(documentPath: '/a.pdf', documentName: 'a', signers: [], level: 'SES');
	}//end testNonObjectConfigIsNotConfigured()
}//end class
