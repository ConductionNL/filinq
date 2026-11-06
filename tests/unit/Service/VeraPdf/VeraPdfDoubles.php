<?php

/**
 * The real veraPDF service over a recorded veraPDF, and the fixture PDFs.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\VeraPdf
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\VeraPdf;

use OCA\Filinq\Service\VeraPdf\VeraPdfProcess;
use OCA\Filinq\Service\VeraPdf\VeraPdfReportParser;
use OCA\Filinq\Service\VeraPdf\VeraPdfService;
use OCP\IAppConfig;
use OCP\ITempManager;

trait VeraPdfDoubles {

	/**
	 * App config values, keyed by config key.
	 *
	 * @var array<string, string>
	 */
	protected array $config = [];

	/**
	 * Temporary files the service asked for.
	 *
	 * @var array<int, string>
	 */
	protected array $tempFiles = [];

	/**
	 * The recorded veraPDF: real reports of veraPDF 1.30.2 for the fixture PDFs.
	 *
	 * @return string The path.
	 */
	protected function fakeBinary(): string {
		return realpath(__DIR__ . '/../../../fixtures/verapdf/fake-verapdf');

	}//end fakeBinary()

	/**
	 * A fixture PDF's bytes.
	 *
	 * @param string $name The fixture name without .pdf.
	 *
	 * @return string The bytes.
	 */
	protected function pdf(string $name): string {
		return (string) file_get_contents(__DIR__ . '/../../../sample-documents/pdfa/' . $name . '.pdf');

	}//end pdf()

	/**
	 * App config over $this->config; the veraPDF binary is the recorded one
	 * unless configured.
	 *
	 * @return IAppConfig The config.
	 */
	protected function appConfig(): IAppConfig {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			fn (string $app, string $key, string $default = ''): string => ($this->config[$key] ?? ($key === VeraPdfService::CFG_BINARY_PATH ? $this->fakeBinary() : $default))
		);
		$config->method('getValueInt')->willReturnCallback(
			fn (string $app, string $key, int $default = 0): int => (int) ($this->config[$key] ?? $default)
		);

		return $config;

	}//end appConfig()

	/**
	 * The real service; the binary is the recorded veraPDF unless configured.
	 *
	 * @return VeraPdfService The service.
	 */
	protected function veraPdf(): VeraPdfService {
		$config = $this->appConfig();
		$temp = $this->createMock(ITempManager::class);
		$temp->method('getTemporaryFile')->willReturnCallback(
			function (string $postFix = ''): string {
				$base = (string) tempnam(sys_get_temp_dir(), 'filinq-verapdf-test-');
				$path = $base . $postFix;
				rename($base, $path);
				$this->tempFiles[] = $path;

				return $path;
			}
		);

		return new VeraPdfService($config, $temp, new VeraPdfProcess(), new VeraPdfReportParser());

	}//end veraPdf()
}//end trait
