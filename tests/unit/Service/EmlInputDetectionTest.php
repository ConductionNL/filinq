<?php

/**
 * Which file nodes count as an EML input.
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
 * @spec openspec/specs/anonymization/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\EmlAnonymizationService;
use OCA\Filinq\Service\EmlPdfAssemblyService;
use OCA\Filinq\Service\EntityDetectionService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * isEmlInput() for nodes with and without a MIME type and a name.
 *
 * @covers \OCA\Filinq\Service\EmlAnonymizationService
 */
class EmlInputDetectionTest extends TestCase {

	/**
	 * Nodes and whether each is an EML input.
	 *
	 * @return array<string, array{0: mixed, 1: bool}>
	 */
	public static function nodes(): array {
		return [
			'not an object' => ['mail.eml', false],
			'null' => [null, false],
			'an object without either method' => [new \stdClass(), false],
			'the rfc822 type' => [self::node(mime: 'message/rfc822', name: 'mail.txt'), true],
			'the rfc822 type without a name' => [self::node(mime: 'message/rfc822', name: null), true],
			'a pdf type and an eml name' => [self::node(mime: 'application/pdf', name: 'mail.eml'), true],
			'an upper case extension' => [self::node(mime: 'text/plain', name: 'MAIL.EML'), true],
			'an eml name without a type' => [self::node(mime: null, name: 'mail.eml'), true],
			'a double extension' => [self::node(mime: 'text/plain', name: 'mail.eml.pdf'), false],
			'eml without a dot' => [self::node(mime: 'text/plain', name: 'eml'), false],
			'eml after one letter without a dot' => [self::node(mime: 'text/plain', name: 'xeml'), false],
			'a name with two dots' => [self::node(mime: 'text/plain', name: 'my.mail.eml'), true],
			'only the extension' => [self::node(mime: 'text/plain', name: '.eml'), true],
			'a trailing dot' => [self::node(mime: 'text/plain', name: 'mail.'), false],
			'a longer extension' => [self::node(mime: 'text/plain', name: 'mail.emlx'), false],
			'a type with parameters' => [self::node(mime: 'message/rfc822; charset=utf-8', name: 'mail.txt'), false],
			'neither' => [self::node(mime: 'text/plain', name: 'mail.txt'), false],
		];
	}//end nodes()

	/**
	 * A node that answers only the methods given a value.
	 *
	 * @param string|null $mime The MIME type, or null for no getMimeType().
	 * @param string|null $name The name, or null for no getName().
	 *
	 * @return object The node.
	 */
	private static function node(?string $mime, ?string $name): object {
		if ($mime !== null && $name !== null) {
			return new class($mime, $name) {
				public function __construct(private string $mime, private string $name) {
				}
				public function getMimeType(): string {
					return $this->mime;
				}
				public function getName(): string {
					return $this->name;
				}
			};
		}

		if ($mime !== null) {
			return new class($mime) {
				public function __construct(private string $mime) {
				}
				public function getMimeType(): string {
					return $this->mime;
				}
			};
		}

		return new class((string)$name) {
			public function __construct(private string $name) {
			}
			public function getName(): string {
				return $this->name;
			}
		};
	}//end node()

	/**
	 * The node is classified as recorded on the implementation before the split.
	 *
	 * @param mixed $node     The node.
	 * @param bool  $expected Whether it is an EML input.
	 *
	 * @dataProvider nodes
	 *
	 * @return void
	 */
	public function testClassification(mixed $node, bool $expected): void {
		$service = new EmlAnonymizationService(
			logger: $this->createMock(LoggerInterface::class),
			entityDetection: $this->createMock(EntityDetectionService::class),
			emlAssembly: $this->createMock(EmlPdfAssemblyService::class),
		);

		$this->assertSame($expected, $service->isEmlInput(node: $node));
	}//end testClassification()
}//end class
