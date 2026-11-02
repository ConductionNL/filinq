<?php

/**
 * Calls a controller action the way Nextcloud's Dispatcher does.
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Support;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;
use ReflectionMethod;
use ReflectionNamedType;
use TypeError;

/**
 * Mirrors OC\AppFramework\Http\Dispatcher::executeController() for actions
 * whose parameters are strings and bools: each argument is
 * `getParam(name, default)` (a required parameter defaults to null), the string
 * 'false' is false for a bool, int/bool/float parameters are cast, and a
 * TypeError raised by the call itself becomes a bare 400. The Dispatcher is a
 * strict_types file and calls the fully qualified \call_user_func_array(),
 * which keeps strict typing for the call; this class does the same.
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class NextcloudDispatch {

	/**
	 * Call one action with the request's parameters.
	 *
	 * @param object               $controller The controller.
	 * @param string               $method     The action.
	 * @param array<string, mixed> $params     The request parameters, as IRequest::getParams() holds them.
	 *
	 * @return mixed The action's response.
	 */
	public static function call(object $controller, string $method, array $params): mixed {
		$reflection = new ReflectionMethod($controller, $method);
		$arguments  = [];
		foreach ($reflection->getParameters() as $param) {
			$default = null;
			if ($param->isOptional() === true) {
				$default = $param->getDefaultValue();
			}

			$value    = (isset($params[$param->getName()]) === true ? $params[$param->getName()] : $default);
			$type     = $param->getType();
			$typeName = ($type instanceof ReflectionNamedType ? $type->getName() : null);
			if ($value === 'false' && $typeName === 'bool') {
				$value = false;
			} else if ($value !== null && in_array($typeName, ['int', 'bool', 'float'], true) === true) {
				settype($value, $typeName);
			}

			$arguments[] = $value;
		}

		try {
			return \call_user_func_array([$controller, $method], $arguments);
		} catch (TypeError $e) {
			if ($e->getFile() === $reflection->getFileName() && $e->getLine() === $reflection->getStartLine()) {
				return new Response(Http::STATUS_BAD_REQUEST);
			}

			throw $e;
		}

	}//end call()
}//end class
