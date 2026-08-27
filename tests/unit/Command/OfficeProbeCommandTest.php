<?php

/**
 * Unit tests for OfficeProbeCommand.
 *
 * openspec/specs/office-suite-portability.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Command
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Command;

use OCA\Filinq\Command\OfficeProbeCommand;
use OCA\Filinq\Service\Office\OfficeSuiteCapabilityService;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * The command exists to keep suites APART.
 *
 * Every test here asserts on ONE suite's line. A probe that reported a single
 * aggregate verdict is what allowed an ONLYOFFICE measurement to be published
 * under a Euro-Office heading, so "each suite gets its own line, derived from its
 * own request" is the behaviour under test rather than a presentation detail.
 */
class OfficeProbeCommandTest extends TestCase {

	/**
	 * Build a tester over a command wired to the given config and verdicts.
	 *
	 * @param array<string, string> $configured Suite app id => configured server URL.
	 * @param array<string, array{available:bool, reason?:string, suite?:string|null}> $verdicts Discovery URL => verdict.
	 *
	 * @return CommandTester The tester.
	 */
	private function tester(array $configured, array $verdicts = []): CommandTester {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(
			static function (string $app) use ($configured): string {
				return ($configured[$app] ?? '');
			}
		);

		$capability = $this->createMock(OfficeSuiteCapabilityService::class);
		$capability->method('probeDiscovery')->willReturnCallback(
			static function (string $discoveryUrl) use ($verdicts): array {
				return ($verdicts[$discoveryUrl] ?? [
					'available' => false,
					'reason' => 'no path answered',
					'suite' => null,
				]);
			}
		);

		return new CommandTester(new OfficeProbeCommand(capability: $capability, appConfig: $appConfig));
	}//end tester()

	/**
	 * REQ: the command the setup documentation names must exist and be runnable.
	 *
	 * The guide told operators to run `occ filinq:office:probe` and showed its
	 * output while the command did not exist. Asserting the registered name is what
	 * makes that particular regression impossible to repeat silently.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md#requirement-wopi-availability-must-be-probed-never-inferred-from-installation
	 */
	public function testCommandIsRegisteredUnderTheDocumentedName(): void {
		$command = new OfficeProbeCommand(
			capability: $this->createMock(OfficeSuiteCapabilityService::class),
			appConfig: $this->createMock(IAppConfig::class)
		);

		$this->assertSame('filinq:office:probe', $command->getName());
		$this->assertTrue($command->getDefinition()->hasOption('suite'));
	}//end testCommandIsRegisteredUnderTheDocumentedName()

	/**
	 * REQ: an unconfigured suite is reported absent, naming the missing key.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md#requirement-a-suite-must-not-be-claimed-as-supported-until-it-has-been-run
	 */
	public function testUnconfiguredSuitesAreReportedAbsent(): void {
		$tester = $this->tester(configured: []);

		$this->assertSame(0, $tester->execute([]));

		$output = $tester->getDisplay();
		$this->assertMatchesRegularExpression(
			'/onlyoffice\s+absent \(no DocumentServerInternalUrl configured\)/',
			$output
		);
		$this->assertMatchesRegularExpression(
			'/eurooffice\s+absent \(no DocumentServerInternalUrl configured\)/',
			$output
		);
		$this->assertMatchesRegularExpression('/collabora\s+absent \(no wopi_url configured\)/', $output);
	}//end testUnconfiguredSuitesAreReportedAbsent()

	/**
	 * REQ: a suite serving discovery is reported available, on its own line.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md#requirement-wopi-availability-must-be-probed-never-inferred-from-installation
	 */
	public function testServingSuiteIsReportedAvailable(): void {
		$tester = $this->tester(
			configured: ['onlyoffice' => 'http://oo:80/'],
			verdicts: [
				'http://oo:80/hosting/discovery' => [
					'available' => true,
					'reason' => 'WOPI discovery served',
					'suite' => 'Word',
				],
			]
		);

		$tester->execute([]);

		$this->assertMatchesRegularExpression(
			'#onlyoffice\s+available at /hosting/discovery#',
			$tester->getDisplay()
		);
	}//end testServingSuiteIsReportedAvailable()

	/**
	 * REQ: one suite answering says NOTHING about another.
	 *
	 * This is the measured failure the change exists to correct: ONLYOFFICE was
	 * running and Euro-Office was not, and the two were reported as one result.
	 * With ONLYOFFICE available and eurooffice unconfigured, eurooffice MUST still
	 * read absent.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md#requirement-a-suite-must-not-be-claimed-as-supported-until-it-has-been-run
	 */
	public function testOneSuiteAnsweringDoesNotMakeAnotherAvailable(): void {
		$tester = $this->tester(
			configured: ['onlyoffice' => 'http://oo:80'],
			verdicts: [
				'http://oo:80/hosting/discovery' => [
					'available' => true,
					'reason' => 'WOPI discovery served',
					'suite' => 'Word',
				],
			]
		);

		$tester->execute([]);
		$output = $tester->getDisplay();

		$this->assertMatchesRegularExpression('/onlyoffice\s+available/', $output);
		$this->assertMatchesRegularExpression('/eurooffice\s+absent/', $output);
		$this->assertStringContainsString('says nothing about another', $output);
	}//end testOneSuiteAnsweringDoesNotMakeAnotherAvailable()

	/**
	 * REQ: a configured suite that answers nowhere reports the probe's own reason.
	 *
	 * "Absent" must distinguish "not installed" from "installed, WOPI off". The
	 * reason carried back from the last probed path is what does that.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md#requirement-wopi-availability-must-be-probed-never-inferred-from-installation
	 */
	public function testConfiguredButSilentSuiteReportsTheProbeReason(): void {
		$tester = $this->tester(
			configured: ['richdocuments' => 'http://collabora:9980'],
			verdicts: [
				'http://collabora:9980/hosting/discovery' => [
					'available' => false,
					'reason' => 'discovery returned 404',
					'suite' => null,
				],
			]
		);

		$tester->execute([]);

		$this->assertMatchesRegularExpression(
			'/collabora\s+absent \(discovery returned 404\)/',
			$tester->getDisplay()
		);
	}//end testConfiguredButSilentSuiteReportsTheProbeReason()

	/**
	 * REQ: a suite is probed on every path it declares before being called absent.
	 *
	 * ONLYOFFICE declares two. Failing on the first and stopping would report a
	 * usable host as absent.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md#requirement-wopi-availability-must-be-probed-never-inferred-from-installation
	 */
	public function testSecondPathIsTriedWhenTheFirstDoesNotAnswer(): void {
		$tester = $this->tester(
			configured: ['onlyoffice' => 'http://oo:80'],
			verdicts: [
				'http://oo:80/hosting/discovery' => [
					'available' => false,
					'reason' => 'discovery returned 404',
					'suite' => null,
				],
				'http://oo:80/hosting/capabilities' => [
					'available' => true,
					'reason' => 'WOPI discovery served',
					'suite' => null,
				],
			]
		);

		$tester->execute([]);

		$this->assertMatchesRegularExpression(
			'#onlyoffice\s+available at /hosting/capabilities#',
			$tester->getDisplay()
		);
	}//end testSecondPathIsTriedWhenTheFirstDoesNotAnswer()

	/**
	 * REQ: `--suite` narrows the report to that suite and probes no other.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md#requirement-a-suite-must-not-be-claimed-as-supported-until-it-has-been-run
	 */
	public function testSuiteOptionProbesOnlyTheNamedSuite(): void {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->expects($this->once())
			->method('getValueString')
			->with('richdocuments', 'wopi_url', '')
			->willReturn('');

		$capability = $this->createMock(OfficeSuiteCapabilityService::class);
		$capability->expects($this->never())->method('probeDiscovery');

		$tester = new CommandTester(
			new OfficeProbeCommand(capability: $capability, appConfig: $appConfig)
		);

		$this->assertSame(0, $tester->execute(['--suite' => 'collabora']));

		$output = $tester->getDisplay();
		$this->assertStringContainsString('collabora', $output);
		$this->assertStringNotContainsString('onlyoffice', $output);
		$this->assertStringNotContainsString('eurooffice', $output);
	}//end testSuiteOptionProbesOnlyTheNamedSuite()
}//end class
