<?php

/**
 * Reads Typed Request Parameters
 *
 * Reads controller action parameters from the request with the type rules
 * Nextcloud's dispatcher applies to typed action arguments.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
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

namespace OCA\Filinq\Controller;

/**
 * Reads controller action parameters from `$this->request`.
 *
 * A missing or null value takes the default (IRequest::getParam). A bool
 * takes the string 'false' as false and casts anything else, as the
 * dispatcher does. A string parameter whose value is not a string refuses
 * the whole request, as the dispatcher answers a typed action argument it
 * cannot pass with a bare 400.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
trait ReadsTypedRequestParameters {

	/**
	 * Read the parameters a spec names, in the spec's order.
	 *
	 * @param array<string, array{0: string, 1: string|bool}> $spec Name => [type, default]; type is 'string' or 'bool'.
	 *
	 * @return array<string, string|bool>|null The values, or null when a string parameter is not a string.
	 *
	 * @spec exclude request-parameter plumbing shared by controller actions, no behavioural spec surface.
	 */
	private function typedRequestParameters(array $spec): ?array {
		$values = [];
		foreach ($spec as $name => [$type, $default]) {
			$value = $this->request->getParam($name, $default);
			if ($type === 'bool') {
				$values[$name] = $this->requestBool(value: $value);
				continue;
			}

			if (is_string($value) === false) {
				return null;
			}

			$values[$name] = $value;
		}

		return $values;

	}//end typedRequestParameters()

	/**
	 * A request value as the dispatcher passes it to a bool parameter.
	 *
	 * @param mixed $value The request value, never null.
	 *
	 * @return bool
	 *
	 * @spec exclude request-parameter plumbing shared by controller actions, no behavioural spec surface.
	 */
	private function requestBool(mixed $value): bool {
		if ($value === 'false') {
			return false;
		}

		return (bool) $value;

	}//end requestBool()
}//end trait
