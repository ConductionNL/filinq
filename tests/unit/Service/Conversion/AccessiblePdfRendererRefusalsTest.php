<?php

/**
 * Unit tests for AccessiblePdfRenderer's title refusal, missing-title output and head insertion.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Conversion;

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\Conversion\AccessiblePdfRenderer;
use OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\Lock\ILockingProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * What the accessible renderer refuses, and how it gives a document its head.
 *
 * @covers \OCA\Filinq\Service\Conversion\AccessiblePdfRenderer
 *
 * @uses \OCA\Filinq\Exception\ConversionFailedException
 * @uses \OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend
 * @uses \OCA\Filinq\Service\Conversion\SofficeCommand
 * @uses \OCA\Filinq\Service\Conversion\SofficeExport
 * @uses \OCA\Filinq\Service\Validation\AccessibilityChecks
 */
class AccessiblePdfRendererRefusalsTest extends TestCase {

	/**
	 * The PDF/UA sample documents.
	 *
	 * @var string
	 */
	private const FIXTURES = __DIR__ . '/../../../sample-documents/pdfua/';

	/**
	 * The soffice stand-in of the last renderer built.
	 *
	 * @var DiskLikeSofficeRunner
	 */
	private DiskLikeSofficeRunner $runner;

	/**
	 * A renderer whose soffice emits the given fixture, with default_language nl.
	 *
	 * @param string $fixture The fixture soffice "produces".
	 *
	 * @return AccessiblePdfRenderer
	 */
	private function renderer(string $fixture = 'tagged-pdfua.pdf'): AccessiblePdfRenderer {
		$this->runner = new DiskLikeSofficeRunner((string) file_get_contents(self::FIXTURES . $fixture));
		$backend = new LibreOfficeHeadlessBackend(
			appConfig: $this->createMock(IAppConfig::class),
			lockingProvider: $this->createMock(ILockingProvider::class),
			logger: $this->createMock(LoggerInterface::class),
			processRunner: $this->runner,
		);
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueString')->willReturnCallback(
			static fn (string $key, string $default = ''): string => ($key === 'default_language' ? 'nl_NL' : $default)
		);

		return new AccessiblePdfRenderer(libreOffice: $backend, config: $config);
	}//end renderer()

	/**
	 * Without a usable title or template name nothing is exported.
	 *
	 * @return void
	 */
	public function testNoUsableTitleIsRefusedBeforeExport(): void {
		foreach ([[], ['title' => '  ', 'templateName' => ''], ['title' => ['Besluit'], 'templateName' => 42]] as $options) {
			$renderer = $this->renderer();
			try {
				$renderer->render(html: '<p>x</p>', options: $options);
				self::fail('Expected a refusal without a title.');
			} catch (ConversionFailedException $e) {
				self::assertSame('Accessible PDF output needs a document title: pass pdfOptions.title or use a named template.', $e->getMessage());
				self::assertSame(422, $e->getCode());
			}

			self::assertSame([], $this->runner->runs);
		}
	}//end testNoUsableTitleIsRefusedBeforeExport()

	/**
	 * A PDF that comes back without a title is refused as a bad gateway naming what it lacks.
	 *
	 * @return void
	 */
	public function testAResultWithoutTitleIsRefused(): void {
		try {
			$this->renderer(fixture: 'tagged-no-title.pdf')->render(html: '<p>x</p>', options: ['title' => 'T']);
			self::fail('Expected the untitled output to be refused.');
		} catch (ConversionFailedException $e) {
			self::assertSame('LibreOffice returned a PDF without title; it is not returned as accessible output.', $e->getMessage());
			self::assertSame(502, $e->getCode());
			self::assertSame(
				[['name' => 'libreoffice_headless', 'available' => true, 'supports' => true, 'reason' => 'output lacks title']],
				$e->getAttempts()
			);
		}
	}//end testAResultWithoutTitleIsRefused()

	/**
	 * A fragment is wrapped in a whole document; the instance language is normalised to a tag.
	 *
	 * @return void
	 */
	public function testAFragmentIsWrappedInADocument(): void {
		$this->renderer()->render(html: '<p>x</p>', options: ['title' => ' Brief ']);

		self::assertSame(
			'<!DOCTYPE html><html lang="nl-NL"><head><meta charset="utf-8"><title>Brief</title></head><body><p>x</p></body></html>',
			$this->runner->inputs[0]
		);
	}//end testAFragmentIsWrappedInADocument()

	/**
	 * A document without a head gets one after its html tag, and its old title is dropped.
	 *
	 * @return void
	 */
	public function testADocumentWithoutAHeadGetsOne(): void {
		$this->renderer()->render(html: '<HTML class="a"><title>Old</title><body>x</body></HTML>', options: ['title' => 'Kosten $1 & \\1', 'lang' => 'en']);

		self::assertSame(
			'<html lang="en"><head><meta charset="utf-8"><title>Kosten $1 &amp; \\1</title></head><body>x</body></HTML>',
			$this->runner->inputs[0]
		);
	}//end testADocumentWithoutAHeadGetsOne()

	/**
	 * A document with a head keeps it, with the charset and title first and the old title removed.
	 *
	 * @return void
	 */
	public function testADocumentWithAHeadKeepsIt(): void {
		$this->renderer()->render(
			html: '<html><head id="h"><TITLE>Old</TITLE><style>p{}</style></head><body>x</body></html>',
			options: ['title' => 'New $0', 'lang' => 'fr']
		);

		self::assertSame(
			'<html lang="fr"><head id="h"><meta charset="utf-8"><title>New $0</title><style>p{}</style></head><body>x</body></html>',
			$this->runner->inputs[0]
		);
	}//end testADocumentWithAHeadKeepsIt()
}//end class
