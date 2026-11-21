<?php

/**
 * The office fill places native charts and images through the chart and
 * image fillers (office-charts-and-images REQ-DDTCH-003, REQ-DDTCH-008).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-charts-and-images/specs/template-charts/spec.md#REQ-DDTCH-003
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\OfficeTemplate;

use OCA\Filinq\Service\Charts\ChartDataNormalizer;
use OCA\Filinq\Service\Charts\TemplateImageResolver;
use OCA\Filinq\Service\Office\OfficeChartFiller;
use OCA\Filinq\Service\Office\OfficeImageFiller;
use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateFiller;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\ITempManager;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * The fixture `chart-template.docx` carries `${chart:bezwaren}`, `${image:logo}`
 * and plain tags; the fill must turn the first two into a chart part and an
 * image, and leave the plain tags to the plain pass.
 *
 * @covers \OCA\Filinq\Service\OfficeTemplate\OfficeTemplateFiller
 * @uses   \OCA\Filinq\Service\OfficeTemplate\OfficeFillRun
 * @uses   \OCA\Filinq\Service\Office\OfficeChartFiller
 * @uses   \OCA\Filinq\Service\Office\OfficeImageFiller
 * @uses   \OCA\Filinq\Service\Charts\ChartDataNormalizer
 * @uses   \OCA\Filinq\Service\Charts\TemplateImageResolver
 */
class OfficeTemplateFillerChartsTest extends TestCase {

	/**
	 * The office template with a chart, an image and plain tags.
	 *
	 * @var string
	 */
	private const FIXTURE = __DIR__ . '/../../../sample-documents/chart-template.docx';

	/**
	 * A 1x1 PNG.
	 *
	 * @var string
	 */
	private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

	/**
	 * Temp files the image filler wrote.
	 *
	 * @var array<int, string>
	 */
	private array $tempFiles = [];

	/**
	 * Remove the temp files.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		foreach ($this->tempFiles as $file) {
			@unlink($file);
		}

		$this->tempFiles = [];
	}//end tearDown()

	/**
	 * The filler with both office fillers, over a user who can read file 42 only.
	 *
	 * @return OfficeTemplateFiller
	 */
	private function filler(): OfficeTemplateFiller {
		$png = $this->createMock(originalClassName: File::class);
		$png->method('getSize')->willReturn(strlen((string) base64_decode(self::PNG)));
		$png->method('isReadable')->willReturn(true);
		$png->method('getMimeType')->willReturn('image/png');
		$png->method('getContent')->willReturn((string) base64_decode(self::PNG));
		$folder = $this->createMock(originalClassName: Folder::class);
		$folder->method('getFirstNodeById')->willReturnCallback(fn (int $id): ?File => ($id === 42 ? $png : null));
		$root = $this->createMock(originalClassName: IRootFolder::class);
		$root->method('getUserFolder')->willReturn($folder);

		$user = $this->createMock(originalClassName: IUser::class);
		$user->method('getUID')->willReturn('alice');
		$session = $this->createMock(originalClassName: IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$config = $this->createMock(originalClassName: IAppConfig::class);
		$config->method('getValueInt')->willReturnCallback(static fn (string $app, string $key, int $default): int => $default);

		$temp = $this->createMock(originalClassName: ITempManager::class);
		$temp->method('getTemporaryFile')->willReturnCallback(
			function (): string {
				$path = (string) tempnam(sys_get_temp_dir(), 'filinq-img');
				$this->tempFiles[] = $path;
				return $path;
			}
		);

		return new OfficeTemplateFiller(
			charts: new OfficeChartFiller(normalizer: new ChartDataNormalizer()),
			images: new OfficeImageFiller(resolver: new TemplateImageResolver($root, $session, $config), tempManager: $temp)
		);
	}//end filler()

	/**
	 * The zip parts of a DOCX, keyed by path.
	 *
	 * @param string $docx The DOCX.
	 *
	 * @return array<string, string>
	 */
	private static function parts(string $docx): array {
		$path = (string) tempnam(sys_get_temp_dir(), 'filinq-docx');
		file_put_contents($path, $docx);
		$zip = new ZipArchive();
		$zip->open($path);
		$parts = [];
		for ($i = 0; $i < $zip->numFiles; $i++) {
			$name = (string) $zip->getNameIndex($i);
			$parts[$name] = (string) $zip->getFromIndex($i);
		}

		$zip->close();
		unlink($path);

		return $parts;
	}//end parts()

	/**
	 * A chart and a readable image are placed; the plain tags still fill.
	 *
	 * @return void
	 */
	public function testAChartAndAnImageArePlacedBeforeThePlainTags(): void {
		$result = $this->filler()->fill(
			docx: (string) file_get_contents(self::FIXTURE),
			data: [
				'zaak' => ['nummer' => 'Z-2026-1', 'status' => 'open'],
				'besluiten' => [],
				'charts' => [
					'bezwaren' => ['type' => 'bar', 'data' => ['labels' => ['Q1', 'Q2'], 'series' => [['name' => 'Bezwaren', 'values' => [4, 7]]]]],
				],
				'images' => ['logo' => ['fileId' => 42, 'width' => 80, 'height' => 80]],
			]
		);

		$parts = self::parts(docx: $result['docx']);
		$charts = array_filter(array_keys($parts), static fn (string $name): bool => preg_match('#^word/charts/chart\d+\.xml$#', $name) === 1);
		$media = array_filter(array_keys($parts), static fn (string $name): bool => str_starts_with($name, 'word/media/'));
		$this->assertCount(expectedCount: 1, haystack: $charts);
		$this->assertNotSame(expected: [], actual: $media);
		$this->assertStringNotContainsString(needle: 'chart:bezwaren', haystack: $parts['word/document.xml']);
		$this->assertStringNotContainsString(needle: 'image:logo', haystack: $parts['word/document.xml']);
		$this->assertStringContainsString(needle: 'Z-2026-1', haystack: $parts['word/document.xml']);
		$this->assertSame(expected: [], actual: array_values(array_filter($result['warnings'], static fn (string $w): bool => str_contains($w, 'chart') || str_contains($w, 'image'))));
	}//end testAChartAndAnImageArePlacedBeforeThePlainTags()

	/**
	 * Bad chart data and an unreadable image become markers, each with a warning.
	 *
	 * @return void
	 */
	public function testBadDataBecomesAMarkerWithAWarning(): void {
		$result = $this->filler()->fill(
			docx: (string) file_get_contents(self::FIXTURE),
			data: ['charts' => ['bezwaren' => ['type' => 'bar', 'data' => ['labels' => ['Q1']]]], 'images' => ['logo' => 7]]
		);

		$document = self::parts(docx: $result['docx'])['word/document.xml'];
		$this->assertStringContainsString(needle: 'image unavailable', haystack: $document);
		$this->assertCount(expectedCount: 1, haystack: array_filter($result['warnings'], static fn (string $w): bool => str_contains($w, 'image unavailable')));
		$this->assertNotSame(expected: [], actual: array_filter($result['warnings'], static fn (string $w): bool => str_contains(strtolower($w), 'chart')));
	}//end testBadDataBecomesAMarkerWithAWarning()

	/**
	 * Without the office fillers a chart tag is an ordinary tag, left empty and named.
	 *
	 * @return void
	 */
	public function testWithoutTheFillersAChartTagIsAPlainTag(): void {
		$result = (new OfficeTemplateFiller())->fill(docx: (string) file_get_contents(self::FIXTURE), data: []);

		$this->assertContains(needle: 'No value for merge tag "chart:bezwaren"; it is left empty.', haystack: $result['warnings']);
		$this->assertStringNotContainsString(needle: 'word/charts/', haystack: implode(' ', array_keys(self::parts(docx: $result['docx']))));
	}//end testWithoutTheFillersAChartTagIsAPlainTag()
}//end class
