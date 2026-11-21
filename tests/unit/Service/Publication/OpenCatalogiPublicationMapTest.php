<?php

/**
 * Tests for OpenCatalogiPublicationMap
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Publication
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Publication;

use OCA\Filinq\Service\Publication\OpenCatalogiPublicationMap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The DiWoo document type goes to OpenCatalogi's own field, never to the summary.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Publication
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/diwoo-documentsoort-to-opencatalogi/tasks.md#task-1
 */
#[CoversClass(OpenCatalogiPublicationMap::class)]
class OpenCatalogiPublicationMapTest extends TestCase {

	/**
	 * A record with documentsoort besluit maps to the title and documentsoort, with no summary.
	 *
	 * @return void
	 */
	public function testTheDocumentTypeGoesToItsOwnField(): void {
		$publication = (new OpenCatalogiPublicationMap())->toPublication(
			record: ['officieleTitel' => 'Besluit 2025-017', 'documentsoort' => 'besluit']
		);

		$this->assertSame(expected: ['title' => 'Besluit 2025-017', 'documentsoort' => 'besluit'], actual: $publication);
		$this->assertArrayNotHasKey(key: 'summary', array: $publication);

	}//end testTheDocumentTypeGoesToItsOwnField()

	/**
	 * A record without a document type writes neither documentsoort nor summary.
	 *
	 * @return void
	 */
	public function testNoDocumentTypeWritesNoTypeField(): void {
		$publication = (new OpenCatalogiPublicationMap())->toPublication(
			record: ['officieleTitel' => 'Besluit 2025-017', 'wooCategory' => 'infocat009', 'publicatiedatum' => '2026-09-29']
		);

		$this->assertSame(
			expected: ['title' => 'Besluit 2025-017', 'wooCategory' => 'infocat009', 'publicationDate' => '2026-09-29T00:00:00+00:00'],
			actual: $publication
		);

	}//end testNoDocumentTypeWritesNoTypeField()

	/**
	 * Every key the map writes is one the field list names.
	 *
	 * @return void
	 */
	public function testEveryWrittenKeyIsAKnownField(): void {
		$publication = (new OpenCatalogiPublicationMap())->toPublication(
			record: ['officieleTitel' => 'T', 'documentsoort' => 'advies', 'wooCategory' => 'infocat009', 'publicatiedatum' => '2026-09-29']
		);

		$this->assertSame(expected: [], actual: array_diff(array_keys($publication), OpenCatalogiPublicationMap::FIELDS));
		$this->assertContains(needle: 'documentsoort', haystack: OpenCatalogiPublicationMap::FIELDS);

	}//end testEveryWrittenKeyIsAKnownField()
}//end class
