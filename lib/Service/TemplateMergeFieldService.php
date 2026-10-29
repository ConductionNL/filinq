<?php

/**
 * Template merge fields
 *
 * Names the fields a template fills: the variables its print tags read, and
 * the value each one has in a data context. A template may label its fields
 * for people with `mergeFields`; without them the printed paths are listed.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/template-merge-preview/specs/document-creatie-sjablonen/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Throwable;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Source;
use Twig\Token;

/**
 * Extracts and resolves a template's merge fields.
 *
 * @spec openspec/changes/template-merge-preview/specs/document-creatie-sjablonen/spec.md
 */
class TemplateMergeFieldService {

	/**
	 * The variable paths a template prints, in order of first use.
	 *
	 * A path is a name followed by `.attribute` parts, read from `{{ … }}`
	 * tags. Filters, functions, and names a `{% for %}` or `{% set %}` binds
	 * are not fields. A template that does not lex lists no fields.
	 *
	 * @param string $content The Twig template content.
	 *
	 * @return array<int, string> The paths.
	 *
	 * @spec openspec/changes/template-merge-preview/specs/document-creatie-sjablonen/spec.md
	 */
	public function paths(string $content): array {
		$tokens = $this->tokens(content: $content);
		$bound = $this->boundNames(tokens: $tokens);
		$paths = [];
		foreach ($this->printedPaths(tokens: $tokens) as $path) {
			$root = explode('.', $path)[0];
			if (isset($bound[$root]) === true || in_array($path, $paths, true) === true) {
				continue;
			}

			$paths[] = $path;
		}

		return $paths;
	}//end paths()

	/**
	 * The tokens of a template; none when it does not lex.
	 *
	 * @param string $content The Twig template content.
	 *
	 * @return array<int, Token> The tokens.
	 */
	private function tokens(string $content): array {
		try {
			$twig = new Environment(loader: new ArrayLoader(templates: []));
			$stream = $twig->tokenize(source: new Source(code: $content, name: 'merge-fields'));
		} catch (Throwable $e) {
			return [];
		}

		$tokens = [];
		while ($stream->isEOF() === false) {
			$tokens[] = $stream->next();
		}

		return $tokens;
	}//end tokens()

	/**
	 * Every variable path inside `{{ … }}` tags, in order and with repeats.
	 *
	 * Filters, attributes after a filter and function or method calls are
	 * left out.
	 *
	 * @param array<int, Token> $tokens The template's tokens.
	 *
	 * @return array<int, string> The paths.
	 */
	private function printedPaths(array $tokens): array {
		$paths = [];
		$inPrint = false;
		$count = count($tokens);
		for ($i = 0; $i < $count; $i++) {
			$token = $tokens[$i];
			if ($token->test(type: Token::VAR_START_TYPE) === true || $token->test(type: Token::VAR_END_TYPE) === true) {
				$inPrint = $token->test(type: Token::VAR_START_TYPE);
				continue;
			}

			if ($inPrint === false || $this->startsPath(tokens: $tokens, index: $i) === false) {
				continue;
			}

			[$path, $next] = $this->readPath(tokens: $tokens, start: $i);
			$i = $next - 1;
			$following = $tokens[$next] ?? null;
			if ($following === null || $this->isSymbol(token: $following, symbols: ['(']) === false) {
				$paths[] = $path;
			}
		}

		return $paths;
	}//end printedPaths()

	/**
	 * Whether a path starts at this token: a name that no filter bar or
	 * attribute dot precedes.
	 *
	 * @param array<int, Token> $tokens The template's tokens.
	 * @param int               $index  The index of the token.
	 *
	 * @return bool True when a path starts here.
	 */
	private function startsPath(array $tokens, int $index): bool {
		if ($tokens[$index]->test(type: Token::NAME_TYPE) === false) {
			return false;
		}

		$previous = $tokens[$index - 1] ?? null;
		return $previous === null || $this->isSymbol(token: $previous, symbols: ['|', '.']) === false;
	}//end startsPath()

	/**
	 * The merge fields of a template in a data context.
	 *
	 * Fields the template declares in `mergeFields` come first, in their
	 * order and with their labels; printed paths it does not declare follow,
	 * labelled by their path.
	 *
	 * @param array<string, mixed> $template The template (content, mergeFields).
	 * @param array<string, mixed> $data     The data context the template renders with.
	 *
	 * @return array{fields: array<int, array{path: string, label: string, source: string, value: string, filled: bool}>, complete: bool}
	 *
	 * @spec openspec/changes/template-merge-preview/specs/document-creatie-sjablonen/spec.md
	 */
	public function resolve(array $template, array $data): array {
		$declared = [];
		foreach ((array)($template['mergeFields'] ?? []) as $field) {
			$path = trim((string)(((array)$field)['path'] ?? ''));
			if ($path === '' || isset($declared[$path]) === true) {
				continue;
			}

			$declared[$path] = [
				'label'  => trim((string)(((array)$field)['label'] ?? '')),
				'source' => trim((string)(((array)$field)['source'] ?? '')),
			];
		}

		$paths = array_keys($declared);
		foreach ($this->paths(content: (string)($template['content'] ?? '')) as $path) {
			if (isset($declared[$path]) === false) {
				$paths[] = $path;
			}
		}

		$fields = [];
		$complete = true;
		foreach ($paths as $path) {
			$value = $this->valueAt(data: $data, path: $path);
			$filled = $value !== '';
			$complete = $complete && $filled;
			$label = ($declared[$path]['label'] ?? '');
			if ($label === '') {
				$label = $path;
			}

			$fields[] = [
				'path'   => $path,
				'label'  => $label,
				'source' => ($declared[$path]['source'] ?? ''),
				'value'  => $value,
				'filled' => $filled,
			];
		}

		return ['fields' => $fields, 'complete' => $complete];
	}//end resolve()

	/**
	 * The names `{% for %}` and `{% set %}` tags bind.
	 *
	 * @param array<int, Token> $tokens The template's tokens.
	 *
	 * @return array<string, true> The bound names.
	 */
	private function boundNames(array $tokens): array {
		$bound = [];
		$count = count($tokens);
		for ($i = 0; $i < $count; $i++) {
			if ($tokens[$i]->test(type: Token::BLOCK_START_TYPE) === false) {
				continue;
			}

			$tag = ($tokens[$i + 1] ?? null);
			if ($tag === null || $tag->test(type: Token::NAME_TYPE, values: ['for', 'set']) === false) {
				continue;
			}

			for ($j = $i + 2; $j < $count; $j++) {
				$token = $tokens[$j];
				if ($token->test(type: Token::NAME_TYPE) === true) {
					$bound[(string)$token->getValue()] = true;
					continue;
				}

				if ($this->isSymbol(token: $token, symbols: [',']) === false) {
					break;
				}
			}
		}//end for

		return $bound;
	}//end boundNames()

	/**
	 * Read a dotted path starting at a name token.
	 *
	 * @param array<int, Token> $tokens The template's tokens.
	 * @param int               $start  The index of the first name.
	 *
	 * @return array{0: string, 1: int} The path and the index after it.
	 */
	private function readPath(array $tokens, int $start): array {
		$parts = [(string)$tokens[$start]->getValue()];
		$index = $start + 1;
		while (isset($tokens[$index], $tokens[$index + 1]) === true
			&& $this->isSymbol(token: $tokens[$index], symbols: ['.']) === true
			&& $tokens[$index + 1]->test(type: Token::NAME_TYPE) === true
		) {
			$parts[] = (string)$tokens[$index + 1]->getValue();
			$index += 2;
		}

		return [implode('.', $parts), $index];
	}//end readPath()

	/**
	 * Whether a token is one of these symbols. Twig lexes `.` as an operator
	 * and `|`, `(` and `,` as punctuation, so both types are asked.
	 *
	 * @param Token              $token   The token.
	 * @param array<int, string> $symbols The symbols.
	 *
	 * @return bool True when it is one of them.
	 */
	private function isSymbol(Token $token, array $symbols): bool {
		return $token->test(type: Token::PUNCTUATION_TYPE, values: $symbols) === true
			|| $token->test(type: Token::OPERATOR_TYPE, values: $symbols) === true;
	}//end isSymbol()

	/**
	 * The value at a dotted path, as text; '' when absent or empty.
	 *
	 * @param array<string, mixed> $data The data context.
	 * @param string               $path The dotted path.
	 *
	 * @return string The value.
	 */
	private function valueAt(array $data, string $path): string {
		$value = $data;
		foreach (explode('.', $path) as $part) {
			if (is_array($value) === false || array_key_exists($part, $value) === false) {
				return '';
			}

			$value = $value[$part];
		}

		if (is_scalar($value) === true) {
			return trim((string)$value);
		}

		if (is_array($value) === true) {
			$items = array_filter($value, static fn ($item): bool => is_scalar($item) === true && trim((string)$item) !== '');
			return implode(', ', array_map('strval', $items));
		}

		return '';
	}//end valueAt()
}//end class
