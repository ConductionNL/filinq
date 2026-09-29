<?php

/**
 * Unit tests for the printJob schema
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
 * @spec openspec/specs/print-preview/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Settings;

require_once __DIR__ . '/../Service/PrintJobDoubles.php';

use OCA\Filinq\Repair\MigratePrintJobsOutOfAppConfig;
use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\PrintJobService;
use OCA\Filinq\Service\TemplateService;
use OCA\Filinq\Tests\Unit\Service\PrintJobDoubles;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * What the register declares about a print job, and that every payload the
 * app writes validates against it.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class PrintJobSchemaTest extends TestCase {
	use PrintJobDoubles;

	/**
	 * The parsed register descriptor.
	 *
	 * @return array<string, mixed> The descriptor.
	 */
	private function descriptor(): array {
		$parsed = json_decode((string) file_get_contents(__DIR__ . '/../../../lib/Settings/filinq_register.json'), true);
		$this->assertIsArray($parsed);

		return $parsed;

	}//end descriptor()

	/**
	 * The printJob schema as a JSON Schema a validator reads: the declared
	 * `required` list, and the OpenRegister-only per-property flags removed.
	 *
	 * @return string The JSON Schema.
	 */
	private function jsonSchema(): string {
		$schema = $this->descriptor()['components']['schemas']['printJob'];
		$properties = [];
		foreach ($schema['properties'] as $name => $property) {
			unset($property['required'], $property['visible'], $property['order'], $property['facetable'], $property['x-enum-labels']);
			$properties[$name] = $property;
		}

		return (string) json_encode(
			[
				'type' => 'object',
				'required' => $schema['required'],
				'properties' => $properties,
				'additionalProperties' => false,
			]
		);

	}//end jsonSchema()

	/**
	 * Validate one payload.
	 *
	 * @param array<string, mixed> $payload The payload
	 *
	 * @return void
	 */
	private function assertValidPrintJob(array $payload): void {
		$result = (new Validator())->validate(json_decode((string) json_encode($payload)), $this->jsonSchema());
		$message = '';
		if ($result->isValid() === false) {
			$message = (string) json_encode((new \Opis\JsonSchema\Errors\ErrorFormatter())->format($result->error()));
		}

		$this->assertTrue($result->isValid(), $message);

	}//end assertValidPrintJob()

	/**
	 * The register lists the schema at a version that carries it.
	 *
	 * @return void
	 */
	public function testTheRegisterListsThePrintJob(): void {
		$descriptor = $this->descriptor();

		$this->assertContains('printJob', $descriptor['components']['registers']['filinq']['schemas']);
		$this->assertTrue(version_compare((string) $descriptor['info']['version'], '8.22.0', '>='));
		$this->assertTrue($descriptor['components']['schemas']['printJob']['hardValidation']);

	}//end testTheRegisterListsThePrintJob()

	/**
	 * Printed and failed are terminal, and no transition leaves them.
	 *
	 * @return void
	 */
	public function testPrintedAndFailedAreTerminal(): void {
		$lifecycle = $this->descriptor()['components']['schemas']['printJob']['x-openregister-lifecycle'];

		$this->assertTrue($lifecycle['states']['printed']['terminal']);
		$this->assertTrue($lifecycle['states']['failed']['terminal']);
		foreach ($lifecycle['transitions'] as $name => $transition) {
			$this->assertNotContains($transition['from'], ['printed', 'failed'], $name . ' leaves a terminal state.');
			$this->assertArrayHasKey($transition['to'], $lifecycle['states'], $name);
		}

		$this->assertSame(
			array_keys($lifecycle['states']),
			$this->descriptor()['components']['schemas']['printJob']['properties']['status']['enum']
		);

	}//end testPrintedAndFailedAreTerminal()

	/**
	 * Every payload PrintJobService writes validates: creation, rendering,
	 * a failed letter, a status report and a large batch.
	 *
	 * @return void
	 */
	public function testEveryPayloadTheServiceWritesValidates(): void {
		$pdf = $this->createMock(PdfService::class);
		$pdf->method('renderPdf')->willReturnCallback(
			static fn (string $templateContent, array $data, array $options): string => ($data['naam'] ?? '') === 'kapot' ? throw new \RuntimeException('x') : '%PDF'
		);
		$templates = $this->createMock(TemplateService::class);
		$templates->method('getTemplate')->willReturn(['id' => 't', 'name' => 'Brief', 'content' => '']);
		$service = new PrintJobService(
			$pdf,
			$templates,
			$this->createMock(DataResolverService::class),
			$this->printJobRepository(),
			$this->printJobFileStore(),
			$this->createMock(IJobList::class),
			$this->createMock(LoggerInterface::class)
		);

		$job = $service->createBatchJob(templateId: 't', items: [['data' => ['naam' => 'a']], ['data' => ['naam' => 'kapot']]], userId: 'u', options: ['duplex' => true]);
		$service->recordExternalStatus(job: $service->getJob($job['jobId']), externalStatus: 'printed', details: 'done');
		$service->createBatchJob(templateId: 't', items: array_fill(0, 12, ['data' => []]), userId: 'u');

		$this->assertGreaterThanOrEqual(4, count($this->written));
		foreach ($this->written as $payload) {
			$this->assertValidPrintJob($payload);
		}

	}//end testEveryPayloadTheServiceWritesValidates()

	/**
	 * The payload the repair step writes for a legacy entry validates.
	 *
	 * @return void
	 */
	public function testTheMigratedPayloadValidates(): void {
		$step = new MigratePrintJobsOutOfAppConfig(
			$this->createMock(IAppConfig::class),
			$this->printJobRepository(),
			$this->printJobFileStore(),
			$this->createMock(LoggerInterface::class)
		);

		$this->assertValidPrintJob(
			$step->toPrintJob(
				legacy: ['status' => 'completed', 'total' => 1, 'completed' => 1, 'errors' => 0, 'filename' => 'a.pdf', 'ownerUserId' => 'u', 'printConfig' => ['duplex' => false], 'manifest' => [['index' => 0]], 'externalStatus' => 'printing', 'externalDetails' => ['tray' => 2]],
				files: ['x-0.pdf']
			)
		);

	}//end testTheMigratedPayloadValidates()
}//end class
