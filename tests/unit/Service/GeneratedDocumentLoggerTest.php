<?php

/**
 * Tests for GeneratedDocumentLogger: the generatedDocument audit entry it writes.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Service\DocumentGenerationRecord;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\GeneratedDocumentLogger;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The audit entry, field by field, and what log() answers.
 *
 * @covers \OCA\Filinq\Service\GeneratedDocumentLogger
 * @uses   \OCA\Filinq\Service\DocumentGenerationRecord
 */
class GeneratedDocumentLoggerTest extends TestCase {

	/**
	 * The saveObject calls: [object, register, schema].
	 *
	 * @var array<int, array<int, mixed>>
	 */
	private array $saves = [];

	/**
	 * Build the logger over an ObjectService answering $answer (or throwing it).
	 *
	 * @param mixed                $answer What saveObject returns, or the exception it throws.
	 * @param LoggerInterface|null $logger The logger.
	 *
	 * @return GeneratedDocumentLogger The logger.
	 */
	private function documentLogger(mixed $answer, ?LoggerInterface $logger = null): GeneratedDocumentLogger {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('saveObject')->willReturnCallback(
			function (array $object, string $register, string $schema) use ($answer): mixed {
				$this->saves[] = [$object, $register, $schema];
				if ($answer instanceof Exception) {
					throw $answer;
				}

				return $answer;
			}
		);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		return new GeneratedDocumentLogger($resolver, ($logger ?? $this->createMock(LoggerInterface::class)));

	}//end documentLogger()

	/**
	 * The generated outcome every case writes.
	 *
	 * @return array<string, mixed> The outcome.
	 */
	private function outcome(): array {
		return [
			'status' => 'generated',
			'warnings' => ['w1'],
			'caseId' => 'case-9',
			'errorMessage' => null,
			'fileId' => 77,
			'filePath' => '/Docs/brief.pdf',
		];

	}//end outcome()

	/**
	 * Every canonical field lands on the entry, extra fields join it, and the canonical ones win.
	 *
	 * @return void
	 */
	public function testTheEntryCarriesEveryFieldAndTheCanonicalOnesWin(): void {
		$answer = ['id' => 'entry-1'];

		$result = $this->documentLogger(answer: $answer)->log(
			generation: new DocumentGenerationRecord(
				template: ['id' => 'tpl-1', 'version' => 3, 'name' => 'Brief'],
				dataRefs: [['type' => 'case', 'id' => 'case-9']],
				format: 'pdf',
				userId: 'alice',
				extra: ['plainRendition' => 'b1', 'status' => 'forged', 'templateId' => 'other']
			),
			outcome: $this->outcome()
		);

		$this->assertSame($answer, $result);
		[$entry, $register, $schema] = $this->saves[0];
		$this->assertSame(['filinq', 'generatedDocument'], [$register, $schema]);
		$this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T/', $entry['generatedAt']);
		unset($entry['generatedAt']);
		$this->assertSame(
			[
				'templateId' => 'tpl-1',
				'templateVersion' => 3,
				'templateName' => 'Brief',
				'dataRefs' => [['type' => 'case', 'id' => 'case-9']],
				'format' => 'pdf',
				'status' => 'generated',
				'generatedBy' => 'alice',
				'warnings' => ['w1'],
				'caseId' => 'case-9',
				'errorMessage' => null,
				'fileId' => 77,
				'filePath' => '/Docs/brief.pdf',
				'plainRendition' => 'b1',
			],
			$entry
		);

	}//end testTheEntryCarriesEveryFieldAndTheCanonicalOnesWin()

	/**
	 * An unknown version is left out, and a saved entity answers with its serialised form.
	 *
	 * @return void
	 */
	public function testAnUnknownVersionIsLeftOutAndAnEntityIsSerialised(): void {
		$entity = new class implements \JsonSerializable {
			public function jsonSerialize(): array {
				return ['id' => 'entry-2'];
			}
		};

		$result = $this->documentLogger(answer: $entity)->log(
			generation: new DocumentGenerationRecord(
				template: ['id' => 'tpl-1', 'version' => null, 'name' => 'Brief'],
				dataRefs: [],
				format: 'docx',
				userId: 'alice'
			),
			outcome: ['fileId' => null] + $this->outcome()
		);

		$this->assertSame(['id' => 'entry-2'], $result);
		$this->assertArrayNotHasKey('templateVersion', $this->saves[0][0]);

	}//end testAnUnknownVersionIsLeftOutAndAnEntityIsSerialised()

	/**
	 * An answer that is neither an entity nor an array reads as an empty entry.
	 *
	 * @return void
	 */
	public function testAnUnreadableAnswerIsAnEmptyEntry(): void {
		$result = $this->documentLogger(answer: 'saved')->log(
			generation: new DocumentGenerationRecord(
				template: ['id' => 'tpl-1', 'version' => 1, 'name' => ''],
				dataRefs: [],
				format: 'pdf',
				userId: 'alice'
			),
			outcome: $this->outcome()
		);

		$this->assertSame([], $result);

	}//end testAnUnreadableAnswerIsAnEmptyEntry()

	/**
	 * A failed write is logged with the template and status, and answers an empty entry.
	 *
	 * @return void
	 */
	public function testAFailedWriteIsLoggedAndAnswersEmpty(): void {
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('error')->with(
			'Failed to log generated document: register down',
			['templateId' => 'tpl-1', 'templateVersion' => 2, 'status' => 'generated']
		);

		$result = $this->documentLogger(answer: new Exception('register down'), logger: $logger)->log(
			generation: new DocumentGenerationRecord(
				template: ['id' => 'tpl-1', 'version' => 2, 'name' => 'Brief'],
				dataRefs: [],
				format: 'pdf',
				userId: 'alice'
			),
			outcome: $this->outcome()
		);

		$this->assertSame([], $result);

	}//end testAFailedWriteIsLoggedAndAnswersEmpty()

	/**
	 * A failure entry carries what was attempted, why it failed, and no version or file.
	 *
	 * @return void
	 */
	public function testAFailureEntry(): void {
		$this->documentLogger(answer: [])->logFailure(
			attempt: ['templateId' => 'tpl-1', 'dataRefs' => ['r'], 'format' => 'pdf', 'userId' => 'bob'],
			errorMessage: 'Template not found'
		);

		$entry = $this->saves[0][0];
		unset($entry['generatedAt']);
		$this->assertSame(
			[
				'templateId' => 'tpl-1',
				'templateName' => '',
				'dataRefs' => ['r'],
				'format' => 'pdf',
				'status' => 'failed',
				'generatedBy' => 'bob',
				'warnings' => [],
				'caseId' => null,
				'errorMessage' => 'Template not found',
				'fileId' => null,
				'filePath' => null,
			],
			$entry
		);

	}//end testAFailureEntry()
}//end class
