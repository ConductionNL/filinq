<?php

/**
 * Filinq signing retention deploy check
 *
 * Fails when the signing register keeps its audit trail for less than the
 * Archiefwet 1995 minimum of 3650 days.
 *
 * @category Command
 * @package  OCA\Filinq\Command
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://filinq.app
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 */

declare(strict_types=1);

namespace OCA\Filinq\Command;

use OCA\Filinq\Kernel\Contracts\SigningRetentionCheckInterface;
use OCA\OpenRegister\Db\SchemaMapper;
use OCP\IAppConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * `occ filinq:signing:retention-check` — the deploy check for signing audit retention.
 *
 * Signing audit entries live in OpenRegister's audit trail and are bound to the
 * signing-request object, so OpenRegister stamps their expiry from that schema's
 * retention. This command reads the configured signing schemas from the live
 * instance and exits non-zero, naming the signing register and Archiefwet 1995,
 * when any of them keeps its records for less than 3650 days. A schema that is
 * not configured or cannot be read also fails: a check that cannot see the
 * retention has not verified it.
 *
 * @category Command
 * @package  OCA\Filinq\Command
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://filinq.app
 *
 * @spec openspec/changes/signing-trust-rebuild/specs/signing-audit-via-or/spec.md
 */
class SigningRetentionCheckCommand extends Command {

	/**
	 * The signing schemas whose retention the audit trail depends on.
	 *
	 * `signingRequest` is the object every signing audit entry is bound to;
	 * `signerRecord` holds the per-signer evidence.
	 *
	 * @var array<int, string>
	 */
	public const SIGNING_SCHEMAS = ['signingRequest', 'signerRecord'];

	/**
	 * Constructor.
	 *
	 * @param SigningRetentionCheckInterface $check The retention evaluation.
	 * @param SchemaMapper $schemaMapper OpenRegister's schema mapper.
	 * @param IAppConfig $appConfig App configuration (the configured schema ids).
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SigningRetentionCheckInterface $check,
		private readonly SchemaMapper $schemaMapper,
		private readonly IAppConfig $appConfig,
	) {
		parent::__construct();
	}//end __construct()

	/**
	 * Define the command.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signing-trust-rebuild/specs/signing-audit-via-or/spec.md
	 */
	protected function configure(): void {
		$this->setName(name: 'filinq:signing:retention-check')
			->setDescription('Check that the signing register keeps its audit trail for at least 3650 days (Archiefwet 1995)');
	}//end configure()

	/**
	 * Run the check over every signing schema.
	 *
	 * @param InputInterface $input The console input.
	 * @param OutputInterface $output The console output.
	 *
	 * @return int Command::SUCCESS when every schema meets the minimum, else Command::FAILURE.
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter) Command::execute() passes the input; this check takes no options.
	 *
	 * @spec openspec/changes/signing-trust-rebuild/specs/signing-audit-via-or/spec.md
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$failed = false;
		foreach (self::SIGNING_SCHEMAS as $slug) {
			$result = $this->checkSchema(slug: $slug);
			if ($result['passed'] === true) {
				$output->writeln('<info>OK</info>   ' . $result['message']);
				continue;
			}

			$failed = true;
			$output->writeln('<error>FAIL</error> ' . $result['message']);
		}

		if ($failed === true) {
			return Command::FAILURE;
		}

		return Command::SUCCESS;
	}//end execute()

	/**
	 * Check one signing schema on this instance.
	 *
	 * @param string $slug The schema slug (also the prefix of its config key).
	 *
	 * @return array{passed: bool, message: string}
	 */
	private function checkSchema(string $slug): array {
		$schemaId = $this->appConfig->getValueString('filinq', $slug . '_schema', '');
		if ($schemaId === '') {
			return [
				'passed' => false,
				'message' => 'Signing register schema "' . $slug . '" is not configured (' . $slug . '_schema is empty); '
					. 'its ' . SigningRetentionCheckInterface::LEGAL_BASIS . ' retention cannot be verified.',
			];
		}

		try {
			$schema = $this->schemaMapper->find(id: $schemaId);
		} catch (Throwable $e) {
			return [
				'passed' => false,
				'message' => 'Signing register schema "' . $slug . '" (' . $schemaId . ') could not be read: '
					. $e->getMessage() . '; its ' . SigningRetentionCheckInterface::LEGAL_BASIS . ' retention cannot be verified.',
			];
		}

		return $this->check->evaluate(
			schemaSlug: $slug,
			archive: $schema->getArchive(),
			configuration: $schema->getConfiguration()
		);
	}//end checkSchema()
}//end class
