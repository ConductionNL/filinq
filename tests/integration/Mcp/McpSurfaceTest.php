<?php

/**
 * MCP surface probe: the Filinq tool surface a live OpenRegister serves
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Integration\Mcp
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Integration\Mcp;

use PHPUnit\Framework\TestCase;

/**
 * Asks a running OpenRegister's MCP server for its tool list and checks the
 * Filinq part of it, instead of grepping registries or bundles.
 *
 * The expected derived tools are read from the register JSON this app ships
 * (every schema whose `configuration.x-openregister-mcp` is enabled, one tool
 * per declared verb), so the probe follows the register rather than a number
 * written down here; it also pins today's count, 16, so a schema that is
 * switched on silently still breaks it. The curated tools are read the same
 * way, from the `#[McpTool]` attributes in lib/: the live surface must be
 * exactly what the source declares, so a tool registered from anywhere else
 * still breaks it, while a change that adds a curated tool on purpose does not.
 *
 * Needs a Nextcloud with OpenRegister and Filinq installed. The target comes
 * from the environment, never a default:
 *
 *     NC_BASE_URL=http://nextcloud.test NC_ADMIN_USER=admin NC_ADMIN_PASSWORD=... \
 *         vendor/bin/phpunit tests/integration/Mcp/McpSurfaceTest.php
 *
 * `MCP_URL` names the endpoint directly instead of NC_BASE_URL. Without either
 * the test is skipped, and it says so.
 *
 * @spec openspec/changes/mcp-generation-tools/specs/mcp-generation-tools/spec.md#requirement-assistant-document-operations-map-onto-the-existing-surface-without-duplication-req-ddmgt-001
 * @spec openspec/changes/mcp-generation-tools/specs/mcp-generation-tools/spec.md#requirement-the-adoption-baselines-refusals-remain-binding-req-ddmgt-006
 * @spec openspec/changes/mcp-generation-tools/tasks.md#task-2.2
 *
 * @coversNothing
 */
class McpSurfaceTest extends TestCase
{

	/**
	 * The curated tools and the hints each must declare.
	 *
	 * @var array<string, array{scope: string, readOnlyHint: bool, destructiveHint: bool, idempotentHint: bool}|null>
	 */
	private const CURATED = [
		'filinq.generateCorrespondence' => null,
		'filinq.getDocumentStatus' => [
			'scope' => 'read',
			'readOnlyHint' => true,
			'destructiveHint' => false,
			'idempotentHint' => true,
		],
		'filinq.anonymizeDocument' => [
			'scope' => 'create',
			'readOnlyHint' => false,
			'destructiveHint' => false,
			'idempotentHint' => false,
		],
	];

	/**
	 * The hint keys a curated tool must declare.
	 *
	 * @var string[]
	 */
	private const HINT_KEYS = ['readOnlyHint', 'destructiveHint', 'idempotentHint'];

	/**
	 * The Filinq tools in the live listing, by id.
	 *
	 * @var array<string, array<string, mixed>>|null
	 */
	private static ?array $tools = null;


	/**
	 * The live surface is the expected derived read tools plus the curated tools the source declares, nothing more.
	 *
	 * @return void
	 */
	public function testTheSurfaceIsTheDerivedReadToolsAndTheDeclaredCuratedTools(): void
	{
		$tools    = $this->filinqTools();
		$derived  = $this->expectedDerivedTools();
		$curated  = $this->declaredCuratedTools();
		$expected = array_merge($derived, $curated);
		sort($expected);

		$actual = array_keys($tools);
		sort($actual);

		$this->assertCount(16, $derived, 'the register declares 16 derived read tools');
		foreach (array_keys(self::CURATED) as $id) {
			$this->assertContains(needle: $id, haystack: $curated, message: $id.' is declared with #[McpTool]');
		}

		$this->assertSame($expected, $actual);

	}//end testTheSurfaceIsTheDerivedReadToolsAndTheDeclaredCuratedTools()


	/**
	 * No Filinq tool writes through a derived verb.
	 *
	 * @return void
	 */
	public function testNoDerivedWriteVerbIsExposed(): void
	{
		foreach (array_keys($this->filinqTools()) as $id) {
			$this->assertDoesNotMatchRegularExpression('/\.(create|update|delete|patch|save)$/i', $id);
		}

	}//end testNoDerivedWriteVerbIsExposed()


	/**
	 * A schema the register does not open to MCP has no tool at all.
	 *
	 * @return void
	 */
	public function testNoExcludedSchemaIsReachable(): void
	{
		$enabled = $this->enabledSchemas();
		foreach (array_keys($this->filinqTools()) as $id) {
			$parts = explode('.', $id);
			if (count($parts) !== 3) {
				continue;
			}

			$this->assertContains($parts[1], $enabled, $id.' belongs to a schema without x-openregister-mcp');
		}

	}//end testNoExcludedSchemaIsReachable()


	/**
	 * Template listing and generation each have exactly one tool, and no hand-written shadow.
	 *
	 * @return void
	 */
	public function testListingAndGenerationAreNotDuplicated(): void
	{
		$ids = array_keys($this->filinqTools());

		$this->assertSame([], preg_grep('/listTemplates|list_templates|generateDocument|generate_document/i', $ids));
		$this->assertSame(
			['filinq.generateCorrespondence'],
			array_values(preg_grep('/generat/i', array_filter($ids, static fn (string $id): bool => substr_count($id, '.') === 1)))
		);
		$this->assertContains('filinq.template.search', $ids);

	}//end testListingAndGenerationAreNotDuplicated()


	/**
	 * Every curated tool declares the full hint set, and the two new ones declare these values.
	 *
	 * @return void
	 */
	public function testCuratedToolsCarryCompleteHonestHints(): void
	{
		$tools = $this->filinqTools();
		foreach ($this->declaredCuratedTools() as $id) {
			$this->assertArrayHasKey($id, $tools);
			foreach (self::HINT_KEYS as $key) {
				$this->assertArrayHasKey($key, $tools[$id], $id.' declares '.$key);
				$this->assertIsBool($tools[$id][$key], $id.' '.$key.' is a boolean');
			}
		}

		foreach (self::CURATED as $id => $hints) {
			if ($hints === null) {
				continue;
			}

			foreach ($hints as $key => $value) {
				$this->assertSame($value, ($tools[$id][$key] ?? null), $id.' '.$key);
			}
		}//end foreach

	}//end testCuratedToolsCarryCompleteHonestHints()


	/**
	 * The anonymise tool takes the file id and nothing an agent could steer the gate with.
	 *
	 * @return void
	 */
	public function testAnonymizeTakesOnlyTheFileId(): void
	{
		$tools      = $this->filinqTools();
		$properties = (array) ($tools['filinq.anonymizeDocument']['inputSchema']['properties'] ?? []);

		$this->assertSame(['fileId'], array_keys($properties));

	}//end testAnonymizeTakesOnlyTheFileId()


	/**
	 * The ids of the derived tools the shipped register declares.
	 *
	 * @return string[]
	 */
	private function expectedDerivedTools(): array
	{
		$ids = [];
		foreach ($this->mcpSchemas() as $slug => $annotation) {
			foreach (array_keys((array) ($annotation['tools'] ?? [])) as $verb) {
				$ids[] = 'filinq.'.$slug.'.'.$verb;
			}
		}

		return $ids;

	}//end expectedDerivedTools()


	/**
	 * The ids of the curated tools the source declares with `#[McpTool]`.
	 *
	 * Read as text, so the probe needs neither OpenRegister's attribute class
	 * nor the service classes' dependencies on the autoload path.
	 *
	 * @return string[]
	 */
	private function declaredCuratedTools(): array
	{
		$ids   = [];
		$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(__DIR__.'/../../../lib', \FilesystemIterator::SKIP_DOTS));
		foreach ($files as $file) {
			if ($file->getExtension() !== 'php') {
				continue;
			}

			$source = (string) file_get_contents($file->getPathname());
			if (preg_match_all('/#\[McpTool\(\s*name:\s*\'([A-Za-z0-9_]+)\'/', $source, $matches) > 0) {
				foreach ($matches[1] as $name) {
					$ids[] = 'filinq.'.$name;
				}
			}
		}

		sort($ids);
		$this->assertSame(expected: array_values(array_unique($ids)), actual: $ids, message: 'no curated tool name is declared twice');

		return $ids;

	}//end declaredCuratedTools()


	/**
	 * The slugs of the schemas the register opens to MCP.
	 *
	 * @return string[]
	 */
	private function enabledSchemas(): array
	{
		return array_keys($this->mcpSchemas());

	}//end enabledSchemas()


	/**
	 * The enabled `x-openregister-mcp` annotation of each schema, by slug.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function mcpSchemas(): array
	{
		$register = json_decode((string) file_get_contents(__DIR__.'/../../../lib/Settings/filinq_register.json'), true);
		$this->assertIsArray($register, 'lib/Settings/filinq_register.json parses');

		$found = [];
		foreach ((array) ($register['components']['schemas'] ?? []) as $slug => $schema) {
			$annotation = ($schema['configuration']['x-openregister-mcp'] ?? null);
			if (is_array($annotation) === true && ($annotation['enabled'] ?? false) === true) {
				$found[(string) $slug] = $annotation;
			}
		}

		return $found;

	}//end mcpSchemas()


	/**
	 * The Filinq tools of the live listing, by id, fetched once per run.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function filinqTools(): array
	{
		if (self::$tools !== null) {
			return self::$tools;
		}

		$url = $this->endpoint();
		[$status, $headers] = $this->post(url: $url, body: [
			'jsonrpc' => '2.0',
			'id' => 0,
			'method' => 'initialize',
			'params' => [
				'protocolVersion' => '2024-11-05',
				'capabilities' => new \stdClass(),
				'clientInfo' => ['name' => 'filinq-surface-probe', 'version' => '1'],
			],
		]);
		$this->assertSame(200, $status, 'MCP initialize answers 200');

		$session = '';
		foreach ($headers as $header) {
			if (stripos($header, 'Mcp-Session-Id:') === 0) {
				$session = trim(substr($header, strlen('Mcp-Session-Id:')));
			}
		}

		$this->assertNotSame('', $session, 'MCP initialize returns a session id');

		[$status, , $body] = $this->post(
			url: $url,
			body: ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'],
			session: $session
		);
		$this->assertSame(200, $status, 'MCP tools/list answers 200');

		$listing = json_decode($body, true);
		$this->assertIsArray($listing['result']['tools'] ?? null, 'tools/list returns a tool array');

		$tools = [];
		foreach ($listing['result']['tools'] as $tool) {
			$id = (string) ($tool['id'] ?? $tool['name'] ?? '');
			if (str_starts_with($id, 'filinq.') === true) {
				$tools[$id] = $tool;
			}
		}

		self::$tools = $tools;
		return $tools;

	}//end filinqTools()


	/**
	 * The MCP endpoint from the environment; skips the test when none is named.
	 *
	 * @return string
	 */
	private function endpoint(): string
	{
		$direct = (string) getenv('MCP_URL');
		if ($direct !== '') {
			return $direct;
		}

		$base = rtrim((string) getenv('NC_BASE_URL'), '/');
		if ($base === '') {
			$this->markTestSkipped('MCP surface probe: set NC_BASE_URL (or MCP_URL) to a Nextcloud running OpenRegister and Filinq.');
		}

		return $base.'/index.php/apps/openregister/api/mcp';

	}//end endpoint()


	/**
	 * POST one JSON-RPC call as the configured user.
	 *
	 * @param string               $url     The MCP endpoint.
	 * @param array<string, mixed> $body    The call.
	 * @param string               $session The MCP session id, when there is one.
	 *
	 * @return array{0: int, 1: string[], 2: string} Status, response headers and body.
	 */
	private function post(string $url, array $body, string $session=''): array
	{
		$user     = (string) (getenv('NC_ADMIN_USER') ?: 'admin');
		$password = (string) getenv('NC_ADMIN_PASSWORD');
		if ($password === '') {
			$this->markTestSkipped('MCP surface probe: set NC_ADMIN_PASSWORD for '.$user.'.');
		}

		$headers = [
			'Content-Type: application/json',
			'Accept: application/json, text/event-stream',
			'OCS-APIREQUEST: true',
			'Authorization: Basic '.base64_encode($user.':'.$password),
		];
		if ($session !== '') {
			$headers[] = 'Mcp-Session-Id: '.$session;
		}

		$context = stream_context_create(
			[
				'http' => [
					'method' => 'POST',
					'header' => implode("\r\n", $headers),
					'content' => (string) json_encode($body),
					'ignore_errors' => true,
					'timeout' => 60,
				],
			]
		);

		$response = file_get_contents($url, false, $context);
		$this->assertNotFalse($response, 'the MCP endpoint '.$url.' is reachable');

		$responseHeaders = ($http_response_header ?? []);
		$status          = 0;
		if (isset($responseHeaders[0]) === true && preg_match('/^HTTP\/\S+\s+(\d{3})/', $responseHeaders[0], $match) === 1) {
			$status = (int) $match[1];
		}

		return [$status, $responseHeaders, (string) $response];

	}//end post()
}//end class
