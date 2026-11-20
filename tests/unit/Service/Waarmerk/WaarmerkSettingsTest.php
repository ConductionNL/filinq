<?php

/**
 * Unit tests for WaarmerkSettings.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Waarmerk
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Waarmerk;

use DateTime;
use OCA\Filinq\Exception\WaarmerkRefusedException;
use OCA\Filinq\Service\Waarmerk\WaarmerkSettings;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IAppConfig;
use OCP\Security\ICrypto;
use PHPUnit\Framework\TestCase;

/**
 * Certificate settings: public material and references only.
 *
 * @covers \OCA\Filinq\Service\Waarmerk\WaarmerkSettings
 * @uses   \OCA\Filinq\Service\Waarmerk\WaarmerkCertificate
 * @uses   \OCA\Filinq\Exception\WaarmerkRefusedException
 */
class WaarmerkSettingsTest extends TestCase {

	/**
	 * The in-memory app config.
	 *
	 * @var array<string, string>
	 */
	private array $values = [];

	/**
	 * Keys written as sensitive.
	 *
	 * @var array<int, string>
	 */
	private array $sensitive = [];

	/**
	 * Settings over an in-memory config and a reversible fake crypto.
	 *
	 * @return WaarmerkSettings
	 */
	private function settings(): WaarmerkSettings {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(fn (string $app, string $key, string $default = ''): string => $this->values[$key] ?? $default);
		$config->method('setValueString')->willReturnCallback(
			function (string $app, string $key, string $value, bool $lazy = false, bool $sensitive = false): bool {
				$this->values[$key] = $value;
				if ($sensitive === true) {
					$this->sensitive[] = $key;
				}

				return true;
			}
		);
		$config->method('deleteKey')->willReturnCallback(function (string $app, string $key): void {
			unset($this->values[$key]);
		});
		$crypto = $this->createMock(ICrypto::class);
		$crypto->method('encrypt')->willReturnCallback(fn (string $plain): string => 'enc:' . base64_encode($plain));
		$crypto->method('decrypt')->willReturnCallback(fn (string $cipher): string => (string) base64_decode(substr($cipher, 4)));
		$time = $this->createMock(ITimeFactory::class);
		$time->method('getDateTime')->willReturn(new DateTime());

		return new WaarmerkSettings(config: $config, crypto: $crypto, time: $time);
	}//end settings()

	/**
	 * Saving with broker custody stores the certificate and reference, and the summary shows the facts.
	 *
	 * @return void
	 */
	public function testSaveWithBrokerCustody(): void {
		$pair = WaarmerkKeyPair::make();
		$summary = $this->settings()->save(input: ['certificate' => $pair['cert'], 'credentialRef' => 'doriath://org/demostad/waarmerk-seal', 'custody' => 'broker']);

		$this->assertTrue($summary['configured']);
		$this->assertSame('Gemeente Demostad', $summary['certificate']['organisation']);
		$this->assertSame('doriath://org/demostad/waarmerk-seal', $summary['credentialRef']);
		$this->assertSame('broker', $summary['custody']);
		$this->assertFalse($summary['localKeyStored']);
		$this->assertFalse($summary['qualified']);
		$this->assertSame('doriath://org/demostad/waarmerk-seal', $this->values[WaarmerkSettings::KEY_CREDENTIAL_REF]);
	}//end testSaveWithBrokerCustody()

	/**
	 * A local key is stored encrypted and sensitive, never in plain text, and no read returns it.
	 *
	 * @return void
	 */
	public function testLocalKeyIsEncryptedAndNeverReturned(): void {
		$pair = WaarmerkKeyPair::make();
		$settings = $this->settings();
		$summary = $settings->save(input: ['certificate' => $pair['cert'], 'custody' => 'local', 'localKey' => $pair['key']]);

		$this->assertTrue($summary['localKeyStored']);
		$this->assertSame('local', $summary['custody']);
		$this->assertSame([WaarmerkSettings::KEY_LOCAL_KEY], $this->sensitive);
		foreach ($this->values as $value) {
			$this->assertStringNotContainsString('PRIVATE KEY', $value);
		}

		$this->assertStringNotContainsString('PRIVATE KEY', (string) json_encode($summary));
		$this->assertSame(trim($pair['key']), $settings->localKey());

		// Back to broker custody: the local key is removed.
		$settings->save(input: ['custody' => 'broker']);
		$this->assertArrayNotHasKey(WaarmerkSettings::KEY_LOCAL_KEY, $this->values);
		$this->assertSame('', $settings->localKey());
	}//end testLocalKeyIsEncryptedAndNeverReturned()

	/**
	 * Invalid input is refused with 400 and nothing is written.
	 *
	 * @return void
	 */
	public function testRefusals(): void {
		$pair = WaarmerkKeyPair::make();
		$other = WaarmerkKeyPair::make(organisation: 'Gemeente Elders');
		$cases = [
			'custody' => ['custody' => 'cloud'],
			'key in ref' => ['credentialRef' => $pair['key']],
			'key without local custody' => ['certificate' => $pair['cert'], 'custody' => 'broker', 'localKey' => $pair['key']],
			'key of another certificate' => ['certificate' => $pair['cert'], 'custody' => 'local', 'localKey' => $other['key']],
			'local key, no certificate' => ['custody' => 'local', 'localKey' => $pair['key']],
		];
		foreach ($cases as $name => $input) {
			$this->values = [];
			try {
				$this->settings()->save(input: $input);
				$this->fail($name . ': expected a refusal');
			} catch (WaarmerkRefusedException $e) {
				$this->assertSame(400, $e->getStatus(), $name);
				$this->assertSame([], $this->values, $name);
			}
		}
	}//end testRefusals()

	/**
	 * Unconfigured: no certificate, broker custody, and a broken stored PEM is reported, not thrown.
	 *
	 * @return void
	 */
	public function testSummaryWhenUnconfiguredOrBroken(): void {
		$summary = $this->settings()->summary();
		$this->assertFalse($summary['configured']);
		$this->assertNull($summary['certificate']);
		$this->assertSame('broker', $summary['custody']);

		$this->values[WaarmerkSettings::KEY_CERTIFICATE] = 'garbage';
		$broken = $this->settings()->summary();
		$this->assertFalse($broken['configured']);
		$this->assertNotNull($broken['certificateError']);
	}//end testSummaryWhenUnconfiguredOrBroken()
}//end class
