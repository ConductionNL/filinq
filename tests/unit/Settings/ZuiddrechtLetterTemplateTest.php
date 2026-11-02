<?php

/**
 * Example data for PqBriefOpstellen: a letter template in the pipelinq
 * namespace that names the fields it fills.
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
 * The letter template pipelinq's letter dialog previews.
 */
class ZuiddrechtLetterTemplateTest extends TestCase {

	/**
	 * "Uitnodiging gesprek vergunning" sits in the pipelinq namespace, keeps its Twig
	 * content verbatim, and declares every field it prints, each one printed.
	 *
	 * @return void
	 */
	public function testTheLetterTemplateNamesTheFieldsItFills(): void {
		$file     = __DIR__.'/../../../lib/Settings/example-sets/zuiddrecht.d/20-pipelinq-letter-template.json';
		$part     = json_decode((string)file_get_contents($file), true);
		$template = $part['objects'][0];

		$this->assertSame(expected: ['filinq', 'template'], actual: [$template['register'], $template['schema']]);
		$this->assertSame(expected: ['content'], actual: $template['verbatimFields']);
		$this->assertSame(expected: 'Uitnodiging gesprek vergunning', actual: $template['data']['name']);
		$this->assertSame(expected: 'pipelinq', actual: $template['data']['namespace']);

		preg_match_all(pattern: '/\{\{\s*([a-z]+\.[a-zA-Z.]+)\s*\}\}/', subject: $template['data']['content'], matches: $printed);
		$declared = array_column($template['data']['mergeFields'], 'path');
		$this->assertSame(expected: ['client.name', 'client.address', 'ticket.reference', 'ticket.title'], actual: $declared);
		$this->assertEqualsCanonicalizing(expected: $declared, actual: array_values(array_unique($printed[1])));
		foreach ($template['data']['mergeFields'] as $field) {
			$this->assertNotSame(expected: '', actual: $field['label'] ?? '', message: $field['path']);
		}
	}//end testTheLetterTemplateNamesTheFieldsItFills()
}//end class
