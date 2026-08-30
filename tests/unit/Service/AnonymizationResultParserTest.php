<?php

/**
 * Unit tests for AnonymizationResultParser
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\AnonymizationResultParser;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AnonymizationResultParser
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AnonymizationResultParserTest extends TestCase {

	/**
	 * @var AnonymizationResultParser
	 */
	private AnonymizationResultParser $parser;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->parser = new AnonymizationResultParser();

	}//end setUp()

	/**
	 * Test parseResult with an object that has getId, getName, getPath
	 *
	 * @return void
	 */
	public function testParseResultWithObject(): void {
		$mockResult = new class {

			/**
			 * Get ID
			 *
			 * @return int
			 */
			public function getId(): int {
				return 42;
			}//end getId()

			/**
			 * Get name
			 *
			 * @return string
			 */
			public function getName(): string {
				return 'anonymized.pdf';
			}//end getName()

			/**
			 * Get path
			 *
			 * @return string
			 */
			public function getPath(): string {
				return '/files/anonymized.pdf';
			}//end getPath()

		};

		$result = $this->parser->parseResult($mockResult);

		$this->assertEquals(42, $result['anonymizedFileId']);
		$this->assertEquals('anonymized.pdf', $result['anonymizedFileName']);
		$this->assertEquals('/files/anonymized.pdf', $result['anonymizedFilePath']);

	}//end testParseResultWithObject()

	/**
	 * Test parseResult with an array using fileId keys
	 *
	 * @return void
	 */
	public function testParseResultWithArrayFileIdKeys(): void {
		$result = $this->parser->parseResult([
			'fileId' => 99,
			'fileName' => 'test.pdf',
			'filePath' => '/path/test.pdf',
		]);

		$this->assertEquals(99, $result['anonymizedFileId']);
		$this->assertEquals('test.pdf', $result['anonymizedFileName']);
		$this->assertEquals('/path/test.pdf', $result['anonymizedFilePath']);

	}//end testParseResultWithArrayFileIdKeys()

	/**
	 * Test parseResult with an array using id/name/path keys
	 *
	 * @return void
	 */
	public function testParseResultWithArrayIdKeys(): void {
		$result = $this->parser->parseResult([
			'id' => 77,
			'name' => 'doc.pdf',
			'path' => '/docs/doc.pdf',
		]);

		$this->assertEquals(77, $result['anonymizedFileId']);
		$this->assertEquals('doc.pdf', $result['anonymizedFileName']);
		$this->assertEquals('/docs/doc.pdf', $result['anonymizedFilePath']);

	}//end testParseResultWithArrayIdKeys()

	/**
	 * Test parseResult with non-array non-object returns nulls
	 *
	 * @return void
	 */
	public function testParseResultWithScalarReturnsNulls(): void {
		$result = $this->parser->parseResult('some string');

		$this->assertNull($result['anonymizedFileId']);
		$this->assertNull($result['anonymizedFileName']);
		$this->assertNull($result['anonymizedFilePath']);

	}//end testParseResultWithScalarReturnsNulls()

	/**
	 * Test parseResult with null returns nulls
	 *
	 * @return void
	 */
	public function testParseResultWithNullReturnsNulls(): void {
		$result = $this->parser->parseResult(null);

		$this->assertNull($result['anonymizedFileId']);
		$this->assertNull($result['anonymizedFileName']);
		$this->assertNull($result['anonymizedFilePath']);

	}//end testParseResultWithNullReturnsNulls()

}//end class
