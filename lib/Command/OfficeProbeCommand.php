<?php

/**
 * DocuDesk office-suite probe command
 *
 * Reports, per configured suite, whether WOPI is actually usable on this instance.
 *
 * @category Command
 * @package  OCA\DocuDesk\Command
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://docudesk.app
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Command;

use OCA\DocuDesk\Service\Office\OfficeSuiteCapabilityService;
use OCP\IAppConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * `occ docudesk:office:probe` — the command the setup documentation told people to run.
 *
 * It was documented before it existed. The setup guide instructed operators to
 * verify with this command and showed its expected output; running it returned
 * "command not found". A verification step that cannot be run is worse than none,
 * because the reader believes verification happened.
 *
 * Probes each suite SEPARATELY and reports each on its own line. No suite's result
 * is inferred from another's: on 2026-08-16 an ONLYOFFICE measurement was reported
 * under a Euro-Office heading, and per-suite output is the smallest thing that makes
 * that impossible to do by accident.
 *
 * @category Command
 * @package  OCA\DocuDesk\Command
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://docudesk.app
 *
 * @spec openspec/specs/office-suite-portability/spec.md
 *
 * @suite-registry This command's whole purpose is to enumerate the known suites and
 *                 probe each one SEPARATELY, so it necessarily names their app ids.
 *                 It exposes no capability that depends on a suite being present —
 *                 every suite's absence is simply reported. ADR-087 §5 bans a
 *                 capability that requires a named suite, not a diagnostic that
 *                 lists them.
 */
class OfficeProbeCommand extends Command {

	/**
	 * Suites this command knows how to locate, and the app-config key holding
	 * each one's server URL.
	 *
	 * Listing a suite here is NOT a claim that it is supported — only that we know
	 * where to look for it. The probe's output is the claim.
	 *
	 * @var array<string, array{app: string, key: string, paths: array<int, string>}>
	 */
	private const SUITES = [
		'onlyoffice' => [
			'app'   => 'onlyoffice',
			'key'   => 'DocumentServerInternalUrl',
			'paths' => ['/hosting/discovery', '/hosting/capabilities'],
		],
		'eurooffice' => [
			'app'   => 'eurooffice',
			'key'   => 'DocumentServerInternalUrl',
			'paths' => ['/hosting/discovery', '/hosting/capabilities'],
		],
		'collabora'  => [
			'app'   => 'richdocuments',
			'key'   => 'wopi_url',
			'paths' => ['/hosting/discovery'],
		],
	];

	/**
	 * Constructor.
	 *
	 * @param OfficeSuiteCapabilityService $capability The WOPI capability probe.
	 * @param IAppConfig                   $appConfig  App configuration.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly OfficeSuiteCapabilityService $capability,
		private readonly IAppConfig $appConfig,
	) {
		parent::__construct();
	}//end __construct()

	/**
	 * Define the command.
	 *
	 * @return void
	 */
	protected function configure(): void {
		$this->setName(name: 'docudesk:office:probe')
			->setDescription('Probe each office suite separately and report whether WOPI is usable')
			->addOption('suite', null, InputOption::VALUE_REQUIRED, 'Probe only this suite.');
	}//end configure()

	/**
	 * Run the probe.
	 *
	 * @param InputInterface  $input  The console input.
	 * @param OutputInterface $output The console output.
	 *
	 * @return int The exit code.
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$only = $input->getOption('suite');

		foreach (self::SUITES as $name => $suite) {
			if ($only !== null && $only !== $name) {
				continue;
			}

			$this->reportSuite(output: $output, name: $name, suite: $suite);
		}

		$output->writeln('');
		$output->writeln(
			'<comment>Each line above is that suite only. A result for one suite says '
			. 'nothing about another, even where the products are related.</comment>'
		);

		return Command::SUCCESS;
	}//end execute()

	/**
	 * Report one suite.
	 *
	 * @param OutputInterface $output The console output.
	 * @param string          $name   The suite name.
	 * @param array           $suite  The suite mapping.
	 *
	 * @return void
	 */
	private function reportSuite(OutputInterface $output, string $name, array $suite): void {
		$base = trim($this->appConfig->getValueString($suite['app'], $suite['key'], ''));

		if ($base === '') {
			$output->writeln(sprintf('%-12s <comment>absent</comment> (no %s configured)', $name, $suite['key']));
			return;
		}

		foreach ($suite['paths'] as $path) {
			$verdict = $this->capability->probeDiscovery(discoveryUrl: rtrim($base, "/") . $path);
			if ($verdict['available'] === true) {
				$output->writeln(sprintf('%-12s <info>available</info> at %s', $name, $path));
				return;
			}
		}

		$output->writeln(
			sprintf('%-12s <comment>absent</comment> (%s)', $name, $verdict['reason'] ?? 'no path answered')
		);
	}//end reportSuite()
}//end class
