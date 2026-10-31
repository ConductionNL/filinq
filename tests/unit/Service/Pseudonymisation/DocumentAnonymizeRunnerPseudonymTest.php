<?php

/**
 * The anonymise runner hands its run to the pseudonym map recorder
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Pseudonymisation
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-5.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
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
use OCA\Filinq\Service\ReplacementVerificationService;
use OCP\Files\File;
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
class DocumentAnonymizeRunnerPseudonymTest extends TestCase {
	use PseudonymDoubles;

	/**
	 * The real runner over the in-memory register and an OpenRegister FileService double.
	 *
	 * @return DocumentAnonymizeRunner The runner.
	 */
	private function runner(): DocumentAnonymizeRunner {
		$source = $this->createMock(File::class);
		$source->method('getId')->willReturn(812200);
		$source->method('getName')->willReturn('besluit.txt');
		$source->method('getMimeType')->willReturn('text/plain');
		$source->method('getContent')->willReturn('Jan Jansen schreef.');
		$copy = $this->createMock(File::class);
		$copy->method('getId')->willReturn(812201);
		$copy->method('getName')->willReturn('besluit_anonymized.txt');
		$copy->method('getPath')->willReturn('/alice/files/besluit_anonymized.txt');

		$fileService = new class ($source, $copy) {
			/**
			 * Constructor.
			 *
			 * @param File $source The original.
			 * @param File $copy The anonymised copy.
			 */
			public function __construct(private readonly File $source, private readonly File $copy) {
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

		$logger = new NullLogger();
		$locator = new OpenRegisterServiceLocator($this->apps(), $container);
		$persistence = new AnonymizationPersistenceService($logger, $locator, $this->createMock(ConsentCrudService::class), $this->createMock(ConsentService::class));
		$pdf = $this->createMock(AnonymisedPdfOutputService::class);
		$pdf->method('convertResultToPdf')->willReturnArgument(0);
		$eml = $this->createMock(EmlAnonymizationService::class);
		$eml->method('isEmlInput')->willReturn(false);
		$verdicts = $this->createMock(RedactionVerdictRecorder::class);
		$verdicts->method('record')->willReturnArgument(0);

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
			)
		);

	}//end runner()

	/**
	 * A reversible run leaves a key in the register and the link pointing at it;
	 * the default run leaves none.
	 *
	 * @return void
	 */
	public function testTheRunnerKeepsTheKeyOnlyWhenAsked(): void {
		$this->entityRows = ['Jan Jansen' => ['id' => 41, 'type' => 'PERSON']];
		$options = ['appendBasisSummary' => false, 'outputFormat' => 'preserve', 'unredactedEntities' => [], 'scope' => 'document', 'dossierKey' => null, 'userId' => 'alice'];
		$entities = [['value' => 'Jan Jansen', 'type' => 'PERSON']];

		$irreversible = $this->runner()->run(fileId: 812200, entities: $entities, options: $options);
		$this->assertArrayNotHasKey('pseudonymisation', $irreversible);
		$this->assertSame([], ($this->rows['pseudonymMap'] ?? []));

		$reversible = $this->runner()->run(fileId: 812200, entities: $entities, options: array_merge($options, ['reversible' => true]));

		$this->assertTrue($reversible['pseudonymisation']['keyKept']);
		$this->assertSame(1, $reversible['pseudonymisation']['entryCount']);
		$link = array_values($this->rows['anonymizationLink'])[0];
		$this->assertSame($reversible['pseudonymisation']['mappingRef'], $link['mappingRef']);
		$this->assertSame(2, $link['runCount']);

	}//end testTheRunnerKeepsTheKeyOnlyWhenAsked()
}//end class
