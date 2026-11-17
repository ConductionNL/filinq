<?php

/**
 * Unit tests for SigningRetentionCheckCommand.
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

use OCA\Filinq\Command\SigningRetentionCheckCommand;
use OCA\Filinq\Service\Signing\SigningRetentionCheck;
use OCA\OpenRegister\Db\Schema;
use OCA\OpenRegister\Db\SchemaMapper;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * The deploy check for the signing register's Archiefwet 1995 retention.
 *
 * @covers \OCA\Filinq\Command\SigningRetentionCheckCommand
 * @uses   \OCA\Filinq\Service\Signing\SigningRetentionCheck
 */
class SigningRetentionCheckCommandTest extends TestCase {

	/**
	 * Build a tester over the given configured schema ids and schemas.
	 *
	 * @param array<string, string> $configured Config key => schema id.
	 * @param array<string, Schema|\Throwable> $schemas Schema id => schema or the error find() throws.
	 * @param array<int, string> $looked Receives every id find() was asked for.
	 *
	 * @return CommandTester The tester.
	 */
	private function tester(array $configured, array $schemas, array &$looked = []): CommandTester {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(
			static function (string $app, string $key) use ($configured): string {
				if ($app !== 'filinq') {
					return '';
				}

				return ($configured[$key] ?? '');
			}
		);

		$mapper = $this->createMock(SchemaMapper::class);
		$mapper->method('find')->willReturnCallback(
			static function ($id) use ($schemas, &$looked) {
				$looked[] = $id;
				$found = $schemas[$id];
				if ($found instanceof \Throwable) {
					throw $found;
				}

				return $found;
			}
		);

		return new CommandTester(
			new SigningRetentionCheckCommand(check: new SigningRetentionCheck(), schemaMapper: $mapper, appConfig: $appConfig)
		);
	}//end tester()

	/**
	 * A schema whose retention annotation is the given duration.
	 *
	 * @param string $duration The ISO-8601 retention default.
	 *
	 * @return Schema The schema mock.
	 */
	private function schemaRetaining(string $duration): Schema {
		$schema = $this->createMock(Schema::class);
		$schema->method('getArchive')->willReturn([]);
		$schema->method('getConfiguration')->willReturn(
			['x-openregister-archival' => ['retention' => ['default' => $duration]]]
		);

		return $schema;
	}//end schemaRetaining()

	/**
	 * The command is registered under the name the deployment guide gives.
	 *
	 * @return void
	 */
	public function testCommandName(): void {
		$command = new SigningRetentionCheckCommand(
			check: new SigningRetentionCheck(),
			schemaMapper: $this->createMock(SchemaMapper::class),
			appConfig: $this->createMock(IAppConfig::class)
		);

		self::assertSame('filinq:signing:retention-check', $command->getName());
	}//end testCommandName()

	/**
	 * Both signing schemas at P10Y: success, one OK line each, read from the configured ids.
	 *
	 * @return void
	 */
	public function testCompliantRetentionSucceeds(): void {
		$looked = [];
		$tester = $this->tester(
			configured: ['signingRequest_schema' => '11', 'signerRecord_schema' => '12'],
			schemas: ['11' => $this->schemaRetaining('P10Y'), '12' => $this->schemaRetaining('P10Y')],
			looked: $looked
		);

		self::assertSame(Command::SUCCESS, $tester->execute([]));
		self::assertSame(['11', '12'], $looked);
		self::assertSame(2, substr_count($tester->getDisplay(), 'OK'));
		self::assertStringNotContainsString('FAIL', $tester->getDisplay());
	}//end testCompliantRetentionSucceeds()

	/**
	 * A signing-request retention below 3650 days fails, naming the register and the law.
	 *
	 * @return void
	 */
	public function testShortRetentionFailsNamingTheSigningRegisterAndArchiefwet(): void {
		$tester = $this->tester(
			configured: ['signingRequest_schema' => '11', 'signerRecord_schema' => '12'],
			schemas: ['11' => $this->schemaRetaining('P7Y'), '12' => $this->schemaRetaining('P10Y')]
		);

		self::assertSame(Command::FAILURE, $tester->execute([]));
		$display = $tester->getDisplay();
		self::assertMatchesRegularExpression('/FAIL.*"signingRequest".*Archiefwet 1995 requires at least 3650 days for the signing register/', $display);
		self::assertMatchesRegularExpression('/OK.*"signerRecord"/', $display);
	}//end testShortRetentionFailsNamingTheSigningRegisterAndArchiefwet()

	/**
	 * An unconfigured schema fails instead of being skipped.
	 *
	 * @return void
	 */
	public function testUnconfiguredSchemaFails(): void {
		$tester = $this->tester(
			configured: ['signingRequest_schema' => '11'],
			schemas: ['11' => $this->schemaRetaining('P10Y')]
		);

		self::assertSame(Command::FAILURE, $tester->execute([]));
		self::assertStringContainsString('"signerRecord" is not configured', $tester->getDisplay());
	}//end testUnconfiguredSchemaFails()

	/**
	 * A schema that cannot be read fails instead of being assumed compliant.
	 *
	 * @return void
	 */
	public function testUnreadableSchemaFails(): void {
		$tester = $this->tester(
			configured: ['signingRequest_schema' => '11', 'signerRecord_schema' => '12'],
			schemas: ['11' => new RuntimeException('Schema not found'), '12' => $this->schemaRetaining('P10Y')]
		);

		self::assertSame(Command::FAILURE, $tester->execute([]));
		self::assertStringContainsString('"signingRequest" (11) could not be read: Schema not found', $tester->getDisplay());
	}//end testUnreadableSchemaFails()
}//end class
