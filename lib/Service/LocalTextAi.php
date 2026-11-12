<?php

/**
 * Local text AI
 *
 * The local AI text-processing manager the AI-assisted services run their
 * prompts through, and the reading of its raw answer.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Psr\Container\ContainerInterface;
use Throwable;

/**
 * Resolves the preferred local AI manager and strips code fences from its answers.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 */
final class LocalTextAi {

	/**
	 * Constructor.
	 *
	 * @param ContainerInterface $container DI container, for the optional AI provider.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ContainerInterface $container,
	) {

	}//end __construct()

	/**
	 * Resolve the preferred available local AI text-processing manager.
	 *
	 * Prefers `OCP\TaskProcessing\IManager` (NC 30+), falls back to the
	 * deprecated `OCP\TextProcessing\IManager`. Both are resolved lazily and
	 * guarded so this class loads cleanly when neither namespace exists.
	 *
	 * @return array{type: string, manager: object}|null Null when unavailable.
	 *
	 * @spec openspec/specs/financial-document-field-extraction/spec.md
	 */
	public function manager(): ?array {
		return $this->available(type: 'task', interface: 'OCP\\TaskProcessing\\IManager')
			?? $this->available(type: 'text', interface: 'OCP\\TextProcessing\\IManager');

	}//end manager()

	/**
	 * Strip ```json ... ``` / ``` ... ``` code fences from a model response, if present.
	 *
	 * @param string $text Raw model output.
	 *
	 * @return string
	 *
	 * @spec openspec/specs/financial-document-field-extraction/spec.md
	 */
	public function stripCodeFences(string $text): string {
		$trimmed = trim($text);
		if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $trimmed, $matches) === 1) {
			return trim($matches[1]);
		}

		return $trimmed;

	}//end stripCodeFences()

	/**
	 * The manager behind an interface, when the interface exists and the
	 * container can resolve it.
	 *
	 * @param string $type      The manager kind the caller dispatches on ('task' or 'text').
	 * @param string $interface The manager interface.
	 *
	 * @return array{type: string, manager: object}|null Null when unavailable.
	 */
	private function available(string $type, string $interface): ?array {
		if (interface_exists($interface) === false) {
			return null;
		}

		try {
			return [
				'type' => $type,
				'manager' => $this->container->get($interface),
			];
		} catch (Throwable $e) {
			return null;
		}

	}//end available()
}//end class
