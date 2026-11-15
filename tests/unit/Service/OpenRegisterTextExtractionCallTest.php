<?php

/**
 * OpenRegisterTextExtractionCall passes OpenRegister's extraction methods the
 * arguments the installed OpenRegister declares.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-2.3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\OpenRegisterTextExtractionCall;
use OCA\OpenRegister\Files\Service\FileExtractionOptions;
use OCA\OpenRegister\Files\Service\ProvidedTextRequest;
use OCA\OpenRegister\Files\Service\TextExtractionService;
use PHPUnit\Framework\TestCase;

/**
 * Both OpenRegister shapes: value objects (2.2) and positional arguments (before 2.2).
 */
class OpenRegisterTextExtractionCallTest extends TestCase {

	/**
	 * OpenRegister 2.2 gets a FileExtractionOptions.
	 *
	 * @return void
	 */
	public function testExtractFilePassesOptionsToOpenRegister22(): void {
		$extractor = new class extends TextExtractionService {
			/**
			 * File id, force flag and entity types of every call.
			 *
			 * @var array<int, array<int, mixed>>
			 */
			public array $calls = [];

			/**
			 * Record the call.
			 *
			 * @param int $fileId The file id.
			 * @param FileExtractionOptions $options The options.
			 *
			 * @return void
			 */
			public function extractFile(int $fileId, FileExtractionOptions $options = new FileExtractionOptions()): void {
				$this->calls[] = [$fileId, $options->forceReExtract, $options->entityTypes];
			}
		};

		(new OpenRegisterTextExtractionCall())->extractFile(extractor: $extractor, fileId: 7, force: true, entityTypes: ['PERSON']);

		$this->assertSame(expected: [[7, true, ['PERSON']]], actual: $extractor->calls);

	}//end testExtractFilePassesOptionsToOpenRegister22()

	/**
	 * An OpenRegister before 2.2 gets positional arguments.
	 *
	 * @return void
	 */
	public function testExtractFilePassesPositionalArgumentsBefore22(): void {
		$extractor = new class {
			/**
			 * File id, force flag and entity types of every call.
			 *
			 * @var array<int, array<int, mixed>>
			 */
			public array $calls = [];

			/**
			 * Record the call.
			 *
			 * @param int $fileId The file id.
			 * @param bool $forceReExtract Whether to re-extract.
			 * @param array<int, string>|null $entityTypes The entity types.
			 *
			 * @return void
			 */
			public function extractFile(int $fileId, bool $forceReExtract = false, ?array $entityTypes = null): void {
				$this->calls[] = [$fileId, $forceReExtract, $entityTypes];
			}
		};

		(new OpenRegisterTextExtractionCall())->extractFile(extractor: $extractor, fileId: 7, force: false, entityTypes: null);

		$this->assertSame(expected: [[7, false, null]], actual: $extractor->calls);

	}//end testExtractFilePassesPositionalArgumentsBefore22()

	/**
	 * OpenRegister 2.2 gets a ProvidedTextRequest with the default method.
	 *
	 * @return void
	 */
	public function testProvideTextPassesARequestToOpenRegister22(): void {
		$extractor = new class extends TextExtractionService {
			/**
			 * The last request.
			 *
			 * @var ProvidedTextRequest|null
			 */
			public ?ProvidedTextRequest $request = null;

			/**
			 * Record the request.
			 *
			 * @param ProvidedTextRequest $request The provided text.
			 *
			 * @return void
			 */
			public function extractFromProvidedText(ProvidedTextRequest $request): void {
				$this->request = $request;
			}
		};

		(new OpenRegisterTextExtractionCall())->provideText(extractor: $extractor, fileId: 9, text: 'Jan Jansen');

		$request = $extractor->request;
		$this->assertSame(
			expected: [9, 'Jan Jansen', null, 'ocr'],
			actual: [$request?->fileId, $request?->text, $request?->entityTypes, $request?->method]
		);

	}//end testProvideTextPassesARequestToOpenRegister22()
}//end class
