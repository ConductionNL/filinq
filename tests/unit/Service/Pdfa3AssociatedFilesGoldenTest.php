<?php

/**
 * Golden test for the PDF/A-3 associated files Pdfa3MetadataAssembler builds.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\Pdfa3ConversionException;
use OCA\Filinq\Service\Pdfa3MetadataAssembler;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;

/**
 * Every attachment list, metadata and size cap through buildAssociatedFiles(),
 * compared with JSON recorded from the implementation before it was split.
 *
 * @covers \OCA\Filinq\Service\Pdfa3MetadataAssembler
 * @uses   \OCA\Filinq\Exception\Pdfa3ConversionException
 */
class Pdfa3AssociatedFilesGoldenTest extends TestCase {

	/**
	 * The attachments, the metadata and the configured cap.
	 *
	 * @return array<string, array{0: array<int, mixed>, 1: array<string, mixed>, 2: string}>
	 */
	public static function scenarios(): array {
		$meta = ['identifier' => 'Z-1', 'caseReference' => 'zaak 1'];
		$full = ['content' => 'abc', 'name' => 'bijlage.pdf', 'mime' => 'application/pdf', 'description' => 'Bijlage', 'AFRelationship' => 'Data'];
		return [
			'one full attachment' => [[$full], [], '100'],
			'defaults' => [[['content' => 'x']], [], '100'],
			'metadata adds a sidecar' => [[$full], $meta, '100'],
			'a caller xml sidecar' => [[$full, ['content' => '<x/>', 'name' => 'EIGEN.XML']], $meta, '100'],
			'an xml without metadata' => [[['content' => '<x/>', 'name' => 'a.xml']], [], '100'],
			'only metadata' => [[], $meta, '100'],
			'nothing' => [[], [], '100'],
			'under a larger cap' => [[['content' => str_repeat('b', 50), 'name' => 'vijftig.bin']], [], '100'],
			'at the cap' => [[['content' => str_repeat('a', 10), 'name' => 'tien.bin']], [], '10'],
			'over the cap' => [[$full, ['content' => str_repeat('a', 11), 'name' => 'elf.bin']], [], '10'],
			'over the cap without a name' => [[['content' => str_repeat('a', 11)]], [], '10'],
			'a cap of zero falls back' => [[['content' => 'abc']], [], '0'],
			'a cap that is not a number' => [[['content' => 'abc']], [], 'groot'],
			'numeric fields' => [[['content' => 12, 'name' => 34, 'mime' => 5]], [], '100'],
		];
	}//end scenarios()

	/**
	 * The files, or the refusal, match the recorded ones.
	 *
	 * @param array<int, mixed> $attachments The attachments.
	 * @param array<string, mixed> $metadata The metadata.
	 * @param string $cap The configured cap.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testFiles(array $attachments, array $metadata, string $cap): void {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => $cap
		);

		try {
			$files = (new Pdfa3MetadataAssembler($config))->buildAssociatedFiles($attachments, $metadata);
			foreach ($files as $index => $file) {
				$files[$index]['content'] = sha1((string)$file['content']);
			}

			$trace = ['files' => $files];
		} catch (Pdfa3ConversionException $e) {
			$trace = ['error' => [$e->getReason(), $e->getMessage(), $e->getAdminHint(), $e->getCode()]];
		}

		$path = __DIR__ . '/golden/pdfa3-associated-files.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $trace;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $trace);
	}//end testFiles()
}//end class
