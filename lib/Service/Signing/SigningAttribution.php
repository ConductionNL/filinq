<?php

/**
 * Signing Attribution
 *
 * How a signing request was started: by an agent through the MCP tool, or by Filinq itself.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Signing
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

namespace OCA\Filinq\Service\Signing;

/**
 * Marks a request an agent started, and its CREATED audit entry; leaves Filinq's own requests as they were.
 *
 * @spec openspec/changes/hermiq-ai-tooling/specs/filinq-mcp-surface/spec.md#requirement-agent-initiated-writes-are-attributable-req-ddhat-003
 */
class SigningAttribution {

	/**
	 * Constructor.
	 *
	 * @param string $via `mcp` for an agent's request, '' for Filinq's own paths.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly string $via,
	) {

	}//end __construct()

	/**
	 * The request, with `initiatedVia` when an agent started it.
	 *
	 * @param array<string, mixed> $request The request being created.
	 *
	 * @return array<string, mixed> The request.
	 */
	public function onto(array $request): array {
		if ($this->via !== '') {
			$request['initiatedVia'] = $this->via;
		}

		return $request;

	}//end onto()

	/**
	 * The CREATED audit entry's extra context: how the request was started.
	 *
	 * @return array<string, string> `['via' => 'mcp']` for an agent's request, empty otherwise.
	 */
	public function auditMetadata(): array {
		if ($this->via === '') {
			return [];
		}

		return ['via' => $this->via];

	}//end auditMetadata()
}//end class
