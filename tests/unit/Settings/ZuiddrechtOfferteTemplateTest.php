<?php

/**
 * Example data for PqOfferte's Voorbeeld pdf: the quote template in the
 * pipelinq namespace.
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Settings
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @link https://conduction.nl
 *
 * @spec exclude example-set data integrity; no requirement covers the demo fragment
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * The Offerte template pipelinq names by its slug.
 */
class ZuiddrechtOfferteTemplateTest extends TestCase {

	/**
	 * "Offerte" sits in the pipelinq namespace under slug "offerte", keeps its Twig
	 * content verbatim, and reads the quote and its lines.
	 *
	 * @return void
	 */
	public function testTheOfferteTemplate(): void {
		$file     = __DIR__.'/../../../lib/Settings/example-sets/zuiddrecht.d/10-pipelinq-offerte-template.json';
		$part     = json_decode((string)file_get_contents($file), true);
		$template = $part['objects'][0];

		$this->assertSame(expected: ['content'], actual: $template['verbatimFields']);
		$this->assertSame(expected: 'Offerte', actual: $template['data']['name']);
		$this->assertSame(expected: 'offerte', actual: $template['data']['slug']);
		$this->assertSame(expected: 'pipelinq', actual: $template['data']['namespace']);
		$fields = [
			'salesQuote.number',
			'salesQuote.client.name',
			'for line in lines',
			'line.name ?: line.product.name',
			'line.total',
			'Btw {{ salesQuote.vatRate }}%',
			'salesQuote.total',
		];
		foreach ($fields as $needle) {
			$this->assertStringContainsString(needle: $needle, haystack: $template['data']['content']);
		}
	}//end testTheOfferteTemplate()
}//end class
