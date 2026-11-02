<?php

/**
 * Settings: every feature toggle, its read and its type, as golden files.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/admin-settings/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\LegalBasisProposalService;
use OCA\Filinq\Service\OcrService;
use OCA\Filinq\Service\OpenRegisterAvailabilityService;
use OCA\Filinq\Service\RegisterDiscoveryService;
use OCA\Filinq\Service\SettingsInitializer;
use OCA\Filinq\Service\SettingsService;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Locks the keys, order, defaults, reads and types of getFeatureToggles().
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SettingsFeatureTogglesGoldenTest extends TestCase {

	/**
	 * Every config read, in order, with its default.
	 *
	 * @var array<int, string>
	 */
	private array $reads = [];

	/**
	 * Stored values per scenario: none, everything '0', everything 'x', everything '12'.
	 *
	 * @return array<string, array{0: string|null}>
	 */
	public static function storedProvider(): array {
		return [
			'defaults' => [null],
			'zero'     => ['0'],
			'text'     => ['x'],
			'number'   => ['12'],
		];
	}//end storedProvider()

	/**
	 * The toggles and the reads behind them match the golden file of the scenario.
	 *
	 * @param string|null $stored The value every key holds, or null for unset.
	 *
	 * @dataProvider storedProvider
	 *
	 * @return void
	 */
	public function testFeatureTogglesMatchGolden(?string $stored): void {
		$config = $this->createMock(originalClassName: IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			function (string $app, string $key, string $default = '') use ($stored): string {
				$this->reads[] = 'string ' . $key . ' ' . $default;
				return ($stored ?? $default);
			}
		);
		$config->method('getValueBool')->willReturnCallback(
			function (string $app, string $key, bool $default = false) use ($stored): bool {
				$this->reads[] = 'bool ' . $key . ' ' . var_export($default, true);
				return ($stored === null && $default) || $stored === '12';
			}
		);

		$basis = $this->createMock(originalClassName: LegalBasisProposalService::class);
		$basis->method('getMapping')->willReturn(['person' => ['b-1']]);
		$basis->method('getEnabledEntityTypes')->willReturn(['person']);

		$service = new SettingsService(
			config: $config,
			logger: $this->createMock(originalClassName: LoggerInterface::class),
			discoveryService: $this->createMock(originalClassName: RegisterDiscoveryService::class),
			initializer: $this->createMock(originalClassName: SettingsInitializer::class),
			ocrService: $this->createMock(originalClassName: OcrService::class),
			legalBasisProposal: $basis,
			openRegister: $this->createMock(originalClassName: OpenRegisterAvailabilityService::class)
		);

		$toggles = $service->getFeatureToggles();
		$types = array_map(static fn (mixed $value): string => get_debug_type($value), $toggles);
		$json = json_encode(
			value: ['toggles' => $toggles, 'types' => $types, 'reads' => $this->reads],
			flags: JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		) . "\n";

		$golden = __DIR__ . '/../../fixtures/settings/feature-toggles-' . ($stored ?? 'defaults') . '.json';
		$this->assertSame(expected: file_get_contents(filename: $golden), actual: $json);
	}//end testFeatureTogglesMatchGolden()
}//end class
