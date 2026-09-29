<?php

/**
 * The runner asks OpenRegister to keep the tag structure and records what came back.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\Pseudonymisation
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-accessible-redaction-output/tasks.md#task-4.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Pseudonymisation;
use OCA\Filinq\Service\AnonymisationRunRecords;
use OCA\Filinq\Service\AnonymisedPdfOutputService;
use OCA\Filinq\Service\AnonymizationPersistenceService;
use OCA\Filinq\Service\AnonymizationResultParser;
use OCA\Filinq\Service\ConsentCrudService;
use OCA\Filinq\Service\ConsentService;
use OCA\Filinq\Service\DocumentAnonymizeRunner;
use OCA\Filinq\Service\EmlAnonymizationService;
use OCA\Filinq\Service\EntityDetectionService;
use OCA\Filinq\Service\GrondslagenSummaryAttacher;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCA\Filinq\Service\Pseudonymisation\PseudonymMapRecorder;
use OCA\Filinq\Service\Pseudonymisation\PseudonymPairs;
use OCA\Filinq\Service\Redaction\RedactionVerdictRecorder;
use OCA\Filinq\Service\RedactionAccessibilityService;
use OCA\Filinq\Service\ReplacementVerificationService;
use OCP\Files\File;
use OCP\IAppConfig;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

/**
 * The call site: a guard with a full suite and no caller protects nothing, so
 * this runs the real runner and reads what it left in the register.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Pseudonymisation
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class DocumentAnonymizeRunnerAccessibilityTest extends TestCase {
	use PseudonymDoubles;

	/** @var object The fake OpenRegister FileService of the last runner. */
	private object $fileService;

	private function runner(?array $report, string $preserveDefault = 'true'): DocumentAnonymizeRunner {
		$source = $this->createMock(File::class);
		$source->method('getId')->willReturn(812200);
		$source->method('getName')->willReturn('besluit.txt');
		$source->method('getMimeType')->willReturn('text/plain');
		$source->method('getContent')->willReturn('Jan Jansen schreef.');
		$copy = $this->createMock(File::class);
		$copy->method('getId')->willReturn(812201);
		$copy->method('getName')->willReturn('besluit_anonymized.txt');
		$copy->method('getPath')->willReturn('/alice/files/besluit_anonymized.txt');

		$fileService = new class ($source, $copy, $report) {
			/**
			 * The arguments of every anonymizeDocument() call.
			 *
			 * @var array<int, array<int, mixed>>
			 */
			public array $calls = [];

			/**
			 * Constructor.
			 *
			 * @param File       $source The original.
			 * @param File       $copy   The anonymised copy.
			 * @param array|null $report What getLastStructurePreservation() reports.
			 */
			public function __construct(private readonly File $source, private readonly File $copy, private readonly ?array $report) {
			}

			/**
			 * OpenRegister's StructurePreservation of the last run, or null.
			 *
			 * @return \JsonSerializable|null
			 */
			public function getLastStructurePreservation(): ?\JsonSerializable {
				if ($this->report === null) {
					return null;
				}

				return new class ($this->report) implements \JsonSerializable {
					/**
					 * @param array $report The report.
					 */
					public function __construct(private readonly array $report) {
					}

					/**
					 * @return array The report.
					 */
					public function jsonSerialize(): array {
						return $this->report;
					}
				};
			}

			/**
			 * The original.
			 *
			 * @param int $fileId The id.
			 *
			 * @return File The file.
			 */
			public function getFileById(int $fileId): File {
				unset($fileId);
				return $this->source;
			}

			/**
			 * OpenRegister's anonymise: returns the copy.
			 *
			 * @return File The copy.
			 */
			public function anonymizeDocument(): File {
				$this->calls[] = func_get_args();
				return $this->copy;
			}

			/**
			 * What OpenRegister emitted, keyed by its entity id.
			 *
			 * @return array<string, string> The map.
			 */
			public function getLastPlaceholderMap(): array {
				return ['41' => '[PERSOON: 1]'];
			}
		};

		$base = $this->container();
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			static fn (string $id) => match ($id) {
				'OCA\OpenRegister\Service\FileService' => $fileService,
				default => $base->get($id),
			}
		);

		$this->fileService = $fileService;
		$logger = new NullLogger();
		$locator = new OpenRegisterServiceLocator($this->apps(), $container);
		$persistence = new AnonymizationPersistenceService($logger, $locator, $this->createMock(ConsentCrudService::class), $this->createMock(ConsentService::class));
		$pdf = $this->createMock(AnonymisedPdfOutputService::class);
		$pdf->method('convertResultToPdf')->willReturnArgument(0);
		$eml = $this->createMock(EmlAnonymizationService::class);
		$eml->method('isEmlInput')->willReturn(false);
		$verdicts = $this->createMock(RedactionVerdictRecorder::class);
		$verdicts->method('record')->willReturnArgument(0);
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => $key === RedactionAccessibilityService::CFG_PRESERVE_DEFAULT ? $preserveDefault : $default
		);

		return new DocumentAnonymizeRunner(
			logger: $logger,
			locator: $locator,
			entityDetection: new EntityDetectionService(new AnonymizationResultParser()),
			emlAnonymizer: $eml,
			pdfOutput: $pdf,
			replacementVerifier: new ReplacementVerificationService(logger: $logger),
			persistence: $persistence,
			summaryAttacher: $this->createMock(GrondslagenSummaryAttacher::class),
			runRecords: new AnonymisationRunRecords(
				verdicts: $verdicts,
				persistence: $persistence,
				keys: new PseudonymMapRecorder(new PseudonymPairs(), $this->mapService(container: $container), $persistence, $locator, $logger)
			),
			accessibility: new RedactionAccessibilityService($appConfig)
		);

	}//end runner()

	/**
	 * Validate the link row against the register's own anonymizationLink schema.
	 *
	 * @param array<string, mixed> $link The row.
	 *
	 * @return void
	 */
	private function assertValidLink(array $link): void {
		$descriptor = json_decode((string) file_get_contents(__DIR__ . '/../../../../lib/Settings/filinq_register.json'), true);
		$schema = $descriptor['components']['schemas']['anonymizationLink'];
		$properties = [];
		foreach ($schema['properties'] as $name => $property) {
			unset($property['required'], $property['visible'], $property['order'], $property['facetable']);
			$properties[$name] = $property;
		}

		unset($link['@self'], $link['id'], $link['uuid']);
		$result = (new Validator())->validate(
			json_decode((string) json_encode($link)),
			(string) json_encode(['type' => 'object', 'properties' => $properties, 'additionalProperties' => false])
		);
		$message = '';
		if ($result->isValid() === false) {
			$message = (string) json_encode((new ErrorFormatter())->format($result->error()));
		}

		$this->assertTrue($result->isValid(), $message);
	}

	/**
	 * Options of a plain single-document run.
	 *
	 * @return array<string, mixed>
	 */
	private function options(): array {
		return ['appendBasisSummary' => false, 'outputFormat' => 'preserve', 'unredactedEntities' => [], 'scope' => 'document', 'dossierKey' => null, 'userId' => 'alice'];
	}

	public function testTheRedactionRunRecordsItsAccessibilityOutcome(): void {
		$this->entityRows = ['Jan Jansen' => ['id' => 41, 'type' => 'PERSON']];
		$result = $this->runner(report: ['requested' => true, 'preserved' => false, 'tagCountBefore' => 96, 'tagCountAfter' => 0, 'lossReasons' => ['structtreeroot-dropped-on-rebuild']])
			->run(fileId: 812200, entities: [['value' => 'Jan Jansen', 'type' => 'PERSON']], options: $this->options());

		$this->assertTrue($this->fileService->calls[0][4], 'Preservation is asked for by default.');
		$this->assertSame('degraded', $result['structurePreservation']['state']);
		$link = array_values($this->rows['anonymizationLink'])[0];
		$this->assertSame(
			['state' => 'degraded', 'requested' => true, 'preserved' => false, 'tagCountBefore' => 96, 'tagCountAfter' => 0, 'lossReasons' => ['structtreeroot-dropped-on-rebuild']],
			$link['structurePreservation']
		);
		$this->assertStringNotContainsString('Jan Jansen', (string) json_encode($link['structurePreservation']), 'No entity value on the outcome.');
		$this->assertValidLink($link);
	}

	public function testAnOpenRegisterThatReportsNothingIsUnknownAndTheSwitchIsHonoured(): void {
		$this->entityRows = ['Jan Jansen' => ['id' => 41, 'type' => 'PERSON']];
		$result = $this->runner(report: null, preserveDefault: 'false')
			->run(fileId: 812200, entities: [['value' => 'Jan Jansen', 'type' => 'PERSON']], options: $this->options());

		$this->assertFalse($this->fileService->calls[0][4]);
		$this->assertSame(['state' => 'unknown'], $result['structurePreservation']);
		$this->assertValidLink(array_values($this->rows['anonymizationLink'])[0]);
	}
}//end class
