<?php

/**
 * Architecture tests for tmlo-mdto-metadata: Filinq never writes
 * OpenRegister's retention or tmlo structures and holds no e-depot transport
 * or HTTP code.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Architecture
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-mdto-core-attributes-have-a-single-source-of-truth-req-ddtmm-001
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class MdtoArchitectureTest extends TestCase {

	/**
	 * Every PHP file under lib/, by path relative to the app.
	 *
	 * @return array<string, string> Path => source.
	 */
	private static function lib(): array {
		$root    = dirname(__DIR__, 3);
		$sources = [];
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/lib')) as $file) {
			if ($file->isFile() === true && $file->getExtension() === 'php') {
				$sources[substr($file->getPathname(), (strlen($root) + 1))] = (string) file_get_contents($file->getPathname());
			}
		}

		ksort($sources);

		return $sources;

	}//end lib()

	/**
	 * The MDTO files: the ones this change adds.
	 *
	 * @return array<string, string> Path => source.
	 */
	private static function mdto(): array {
		$mdto = array_filter(self::lib(), static fn (string $path): bool => str_contains($path, '/Mdto'), ARRAY_FILTER_USE_KEY);
		self::assertGreaterThanOrEqual(7, count($mdto), 'the MDTO classes were found');

		return $mdto;

	}//end mdto()

	public function testNothingInLibWritesTheRetentionOrTmloField(): void {
		$writers = [];
		foreach (self::lib() as $path => $source) {
			if (preg_match('/->set(Retention|Tmlo)\s*\(/', $source) === 1) {
				$writers[] = $path;
			}
		}

		$this->assertSame([], $writers);

	}//end testNothingInLibWritesTheRetentionOrTmloField()

	public function testTheMdtoClassesNeverAssignARetentionOrTmloKey(): void {
		foreach (self::mdto() as $path => $source) {
			$this->assertDoesNotMatchRegularExpression('/\[\s*[\'"](retention|tmlo)[\'"]\s*\]\s*=[^=>]/', $source, $path);
			$this->assertDoesNotMatchRegularExpression('/(saveObject|patchObject)\s*\(/', $source, $path . ' writes objects');
		}

	}//end testTheMdtoClassesNeverAssignARetentionOrTmloKey()

	public function testNoClassImplementsAnEdepotTransport(): void {
		foreach (self::lib() as $path => $source) {
			$this->assertDoesNotMatchRegularExpression('/implements[^{]*Transport(Interface)?\b/', $source, $path);
		}

	}//end testNoClassImplementsAnEdepotTransport()

	public function testTheMdtoClassesHoldNoHttpOrProtocolClient(): void {
		$client = '/IClientService|GuzzleHttp|curl_|fsockopen|ssh2_|ftp_|stream_socket_client|phpseclib|->newClient\(/';
		foreach (self::mdto() as $path => $source) {
			$this->assertDoesNotMatchRegularExpression($client, $source, $path);
		}

	}//end testTheMdtoClassesHoldNoHttpOrProtocolClient()
}//end class
