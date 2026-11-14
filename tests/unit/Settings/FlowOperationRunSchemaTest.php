<?php

/**
 * Unit tests for the flowOperationRun declaration
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/flow-operations/specs/flow-operations/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * Asserts what the register declares about a Flow operation run: the fields
 * of the processing log, its lifecycle, its retention placeholder, the
 * failure notice to the file owner and the two seeded runs.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FlowOperationRunSchemaTest extends TestCase {

	/**
	 * The parsed register descriptor.
	 *
	 * @param string $file The descriptor file under lib/Settings.
	 *
	 * @return array<string, mixed> The descriptor.
	 */
	private function descriptor(string $file='filinq_register.json'): array {
		$raw = file_get_contents(__DIR__ . '/../../../lib/Settings/' . $file);
		$this->assertIsString($raw);

		$parsed = json_decode($raw, true);
		$this->assertIsArray($parsed);

		return $parsed;

	}//end descriptor()

	/**
	 * The run schema.
	 *
	 * @return array<string, mixed> The schema.
	 */
	private function runSchema(): array {
		$schemas = $this->descriptor()['components']['schemas'];
		$this->assertArrayHasKey('flowOperationRun', $schemas);

		return $schemas['flowOperationRun'];

	}//end runSchema()

	/**
	 * The register lists the schema, so the import reaches existing installs.
	 *
	 * @return void
	 */
	public function testTheRegisterListsTheRun(): void {
		$this->assertContains('flowOperationRun', $this->descriptor()['components']['registers']['filinq']['schemas']);
		$this->assertSame('flowOperationRun', $this->runSchema()['slug']);

	}//end testTheRegisterListsTheRun()

	/**
	 * Only the background job writes a run; people read it, an admin deletes it.
	 *
	 * @return void
	 */
	public function testOnlyTheServerWritesARun(): void {
		$this->assertSame(['read' => ['authenticated'], 'create' => [], 'update' => [], 'delete' => ['admin']], $this->runSchema()['authorization']);

	}//end testOnlyTheServerWritesARun()

	/**
	 * The run carries every field of the processing log, with the four operations and five statuses.
	 *
	 * @return void
	 */
	public function testTheRunCarriesTheProcessingLogFields(): void {
		$run = $this->runSchema();

		$this->assertSame(
			['operation', 'fileId', 'fileName', 'ownerUserId', 'status', 'statusReason', 'triggerEvent', 'startedAt', 'finishedAt', 'resultSummary', 'producedFileId', 'errorMessage'],
			array_keys($run['properties'])
		);
		$this->assertSame(['operation', 'fileId', 'ownerUserId', 'status'], $run['required']);
		$this->assertSame(['anonymize', 'ocr', 'pdfa', 'validate'], $run['properties']['operation']['enum']);
		$this->assertSame(['queued', 'running', 'succeeded', 'failed', 'skipped'], $run['properties']['status']['enum']);
		$this->assertSame('integer', $run['properties']['producedFileId']['type']);
		$this->assertSame('object', $run['properties']['resultSummary']['type']);

	}//end testTheRunCarriesTheProcessingLogFields()

	/**
	 * A run starts queued, runs, and ends succeeded or failed; a queued run can be skipped.
	 *
	 * @return void
	 */
	public function testTheLifecycleStartsQueuedAndEndsInATerminalState(): void {
		$lifecycle = $this->runSchema()['x-openregister-lifecycle'];

		$this->assertSame('status', $lifecycle['field']);
		$this->assertSame('queued', $lifecycle['initial']);
		$this->assertSame($this->runSchema()['properties']['status']['enum'], array_keys($lifecycle['states']));

		$moves = [];
		foreach ($lifecycle['transitions'] as $name => $transition) {
			$moves[$name] = $transition['from'] . '>' . $transition['to'];
		}

		$this->assertSame(['start' => 'queued>running', 'succeed' => 'running>succeeded', 'fail' => 'running>failed', 'skip' => 'queued>skipped'], $moves);

		$terminal = array_keys(array_filter($lifecycle['states'], static fn (array $state): bool => $state['terminal'] === true));
		$this->assertSame(['succeeded', 'failed', 'skipped'], $terminal);

	}//end testTheLifecycleStartsQueuedAndEndsInATerminalState()

	/**
	 * Retention is the OR object shape with the P1Y placeholder that task 1.3 must replace before apply.
	 *
	 * @return void
	 */
	public function testRetentionIsThePlaceholderAwaitingSignOff(): void {
		$archival = $this->runSchema()['x-openregister-archival'];

		$this->assertSame(['default' => 'P1Y'], $archival['retention']);
		$this->assertSame('destroy', $archival['action']);
		$this->assertStringStartsWith('TODO:', $archival['category']);

	}//end testRetentionIsThePlaceholderAwaitingSignOff()

	/**
	 * A failed run is reported to the file owner only, never to an outside address.
	 *
	 * @return void
	 */
	public function testAFailedRunIsReportedToTheFileOwner(): void {
		$notice = $this->runSchema()['x-openregister-notifications']['flowOperationFailed'];

		$this->assertSame(['status' => 'failed'], $notice['trigger']['filter']);
		$this->assertSame([['kind' => 'field', 'field' => 'ownerUserId']], $notice['recipients']);
		$this->assertSame(['nc-notification'], $notice['channels']);
		$this->assertArrayHasKey('nl', $notice['subject']);
		$this->assertArrayHasKey('en', $notice['subject']);

	}//end testAFailedRunIsReportedToTheFileOwner()

	/**
	 * Three demo runs ship in the mock register, each with every required field, and none in the production register.
	 *
	 * @return void
	 */
	public function testThreeRunsAreSeeded(): void {
		$runs = array_values(
			array_filter(
				$this->descriptor(file: 'filinq_mock_register.json')['components']['objects'],
				static fn (array $object): bool => ($object['@self']['schema'] ?? '') === 'flowOperationRun'
			)
		);

		$this->assertSame(['ocr', 'pdfa', 'pdfa'], array_column($runs, 'operation'));
		$this->assertSame(['succeeded', 'failed', 'skipped'], array_column($runs, 'status'));
		$this->assertSame('own_output', $runs[2]['statusReason']);
		$this->assertSame('conversion_error', $runs[1]['statusReason']);
		$production = array_filter(
			$this->descriptor()['components']['objects'],
			static fn (array $object): bool => ($object['@self']['schema'] ?? '') === 'flowOperationRun'
		);
		$this->assertSame([], $production, 'demo runs never ship in the production register');
		foreach ($runs as $run) {
			foreach ($this->runSchema()['required'] as $field) {
				$this->assertArrayHasKey($field, $run, $field);
			}

			$this->assertContains($run['status'], $this->runSchema()['properties']['status']['enum']);
		}

	}//end testThreeRunsAreSeeded()
}//end class
