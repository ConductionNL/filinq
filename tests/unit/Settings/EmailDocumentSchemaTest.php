<?php

/**
 * emailDocument schema and the mailbox boundary
 *
 * The register declares the email record with its processing activity, the
 * outbound correspondence schema stays as it was, and nothing in filinq can
 * hold a mailbox credential or talk to a mail server.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Settings
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#1-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use OCA\Filinq\Service\EmailIngestion\EmailIngestionSettings;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * The email record in the register.
 */
class EmailDocumentSchemaTest extends TestCase {

	/**
	 * The decoded register.
	 *
	 * @var array<string, mixed>
	 */
	private array $register;

	/**
	 * Load the register.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->register = json_decode((string) file_get_contents(__DIR__ . '/../../../lib/Settings/filinq_register.json'), true);

	}//end setUp()

	/**
	 * The schema is in the filinq register, which moved to 8.42.0.
	 *
	 * @return void
	 */
	public function testTheSchemaIsDeclaredAndTheRegisterMoved(): void {
		$schemas = $this->register['components']['schemas'];
		$this->assertArrayHasKey('emailDocument', $schemas);
		$this->assertContains('emailDocument', $this->register['components']['registers']['filinq']['schemas']);
		$this->assertSame('8.42.0', $this->register['info']['version']);
		$schema = $schemas['emailDocument'];
		$this->assertSame('1.0.0', $schema['version']);
		$this->assertSame(['sourceFileRef', 'status', 'ingestedAt'], $schema['required']);
		$this->assertSame('subject', $schema['configuration']['objectNameField']);
		$this->assertSame(['received', 'filed', 'failed'], $schema['properties']['status']['enum']);
		$this->assertSame(['watched-folder', 'manual'], $schema['properties']['ingestSource']['enum']);
		foreach (['pdfFileRef', 'dossierRef', 'fromAddress', 'toAddresses', 'ccAddresses', 'sentAt', 'messageId', 'inReplyTo', 'references', 'threadKey', 'attachmentCount', 'attachmentNames', 'contentHash', 'failureReason'] as $field) {
			$this->assertArrayHasKey($field, $schema['properties'], $field);
		}

	}//end testTheSchemaIsDeclaredAndTheRegisterMoved()

	/**
	 * The activity is declared for the platform Art. 30 register.
	 *
	 * @return void
	 */
	public function testTheProcessingActivityIsDeclared(): void {
		$processing = $this->register['components']['schemas']['emailDocument']['x-openregister-processing'];
		$this->assertSame('filinq-email-ingestion', $processing['code']);
		foreach (['naam', 'doelbinding', 'rechtsgrond', 'dataCategories', 'backend', 'retentionReference', 'grondslagSource'] as $field) {
			$this->assertNotEmpty($processing[$field], $field);
		}

		$this->assertContains('EMAIL', $processing['dataCategories']);

	}//end testTheProcessingActivityIsDeclared()

	/**
	 * Inbound mail does not reuse the outbound correspondence schema.
	 *
	 * @return void
	 */
	public function testCorrespondenceIsUntouched(): void {
		$correspondence = $this->register['components']['schemas']['correspondence'];
		foreach (['messageId', 'threadKey', 'contentHash', 'ingestSource'] as $field) {
			$this->assertArrayNotHasKey($field, $correspondence['properties']);
		}

	}//end testCorrespondenceIsUntouched()

	/**
	 * No setting, schema field or code path can hold a mailbox credential or reach a mail server.
	 *
	 * @return void
	 */
	public function testNoMailboxCredentialOrClientExists(): void {
		$credential = '/imap|pop3|smtp|mailbox_?(host|user|password|token)|mail_?password/i';
		foreach (array_keys($this->register['components']['schemas']['emailDocument']['properties']) as $field) {
			$this->assertDoesNotMatchRegularExpression($credential, $field);
		}

		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(static fn (string $app, string $key, string $default = ''): string => $default);
		$this->assertSame(['inboxes', 'filesPerTick'], array_keys((new EmailIngestionSettings(appConfig: $config))->toArray()));

		$root = realpath(__DIR__ . '/../../..');
		$offenders = [];
		foreach (['lib', 'src'] as $dir) {
			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $dir));
			foreach ($files as $file) {
				if ($file->isFile() === false || preg_match('/\.(php|js|vue)$/', $file->getFilename()) !== 1) {
					continue;
				}

				$code = (string) file_get_contents($file->getPathname());
				if (preg_match('/\bimap_(open|fetch|search)|\bfsockopen\s*\(|Webklex|ddeboer\/imap|[\'"](imap|mailbox)_(host|user|username|password|token)[\'"]/i', $code) === 1) {
					$offenders[] = substr($file->getPathname(), strlen($root) + 1);
				}
			}
		}

		$this->assertSame([], $offenders);

	}//end testNoMailboxCredentialOrClientExists()

	/**
	 * The inbox mapping section explains who delivers mail into the watched folder.
	 *
	 * @return void
	 */
	public function testTheSettingsSectionStatesTheBoundary(): void {
		$section = (string) file_get_contents(__DIR__ . '/../../../src/views/settings/EmailIngestionSettings.vue');
		$this->assertStringContainsString('integriq', strtolower($section));
		$this->assertStringContainsString('.eml', $section);
		$this->assertStringNotContainsStringIgnoringCase('password', $section);

	}//end testTheSettingsSectionStatesTheBoundary()
}//end class
