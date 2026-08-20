<?php

/**
 * PHP built-in-server router for the shared `E2E Tests (Playwright)` job.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * WHY THIS EXISTS
 * ---------------
 * The shared workflow serves Nextcloud with a bare
 *
 *     php -S 0.0.0.0:8080
 *
 * and no router script. On the GitHub runner that server never performs the
 * PATH_INFO dispatch Nextcloud depends on: a request whose path does not name
 * an existing file is answered by the document root's `index.php` instead of
 * by the entry script named in the path. Measured on run 30807123258:
 *
 *     /status.php                        -> 200   (exact file, works)
 *     /remote.php                        -> 404 "Path not found"  (remote.php RAN)
 *     /remote.php/dav/files/admin/x      -> 404 index.php's router page
 *     /ocs/v2.php/cloud/capabilities     -> 404 index.php's router page
 *
 * The discriminator is the hint paragraph: `remote.php` renders
 * `printErrorPage($message, '', $code)` with an EMPTY hint, while
 * `OC::handleRequest()` renders `printErrorPage('404', 'The page could not be
 * found on the server.', 404)`. The DAV 404s carry the hint, so index.php
 * answered them.
 *
 * Consequences for any Nextcloud app's e2e suite on this runner:
 *   - WebDAV (`/remote.php/dav/...`) is unreachable, so no spec can create a
 *     real file node — DocuDesk's signing and folder-anonymisation journeys
 *     both seed one.
 *   - OCS (`/ocs/v2.php/...`) is unreachable, so user-status, capabilities and
 *     every other OCS call 404s. (`tests/e2e/spec-coverage/_helpers.ts` already
 *     ignores that noise, attributing it to the dev container.)
 *
 * `/index.php/apps/...` works only because index.php IS the fallback — not
 * because PATH_INFO works — which is why the rest of the suite passes and this
 * stayed invisible.
 *
 * ⚠️ THIS BELONGS UPSTREAM, in `ConductionNL/.github`'s quality.yml, next to
 * the `php -S` invocation it patches — every fleet app that touches files or
 * OCS hits this. It lives here only because a caller cannot influence that
 * command, and `tests/e2e/ci-seed.sh` restarts the server with this router
 * rather than leaving two of DocuDesk's workflow specs failing against an
 * environment fault they cannot be written around. Delete this file and the
 * restart block in ci-seed.sh once the shared workflow passes its own router.
 *
 * WHAT IT DOES
 * ------------
 * Reproduces the two rules Nextcloud's shipped `.htaccess` relies on:
 *   1. An existing file is served as-is (static assets, top-level entry
 *      scripts) — `return false` hands it back to the built-in server.
 *   2. Otherwise the longest leading path prefix that names an existing `.php`
 *      file becomes the script, and the remainder becomes PATH_INFO. That is
 *      exactly the mapping `Request::getRawPathInfo()` reverses, so
 *      `SCRIPT_NAME` is what has to be right.
 *
 * @return bool True when this router handled the request.
 */

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);
if (!is_string($path) || $path === '') {
	$path = '/';
}
$path = rawurldecode($path);

// Refuse to climb out of the document root. The runner is disposable, but a
// router that resolves `..` is a directory-traversal primitive and this file
// is read by people looking for a pattern to copy.
if (str_contains($path, '..')) {
	http_response_code(400);
	return true;
}

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? getcwd()), '/');

// 1. Existing file → let the built-in server serve (or execute) it unchanged.
if ($path !== '/' && is_file($root . $path)) {
	return false;
}

// 2. PATH_INFO dispatch: longest leading `.php` prefix wins.
$prefix = '';
foreach (explode('/', ltrim($path, '/')) as $segment) {
	$prefix .= '/' . $segment;
	if (substr($prefix, -4) !== '.php') {
		continue;
	}
	$script = $root . $prefix;
	if (!is_file($script)) {
		continue;
	}
	$pathInfo = substr($path, strlen($prefix));
	$_SERVER['SCRIPT_NAME'] = $prefix;
	$_SERVER['SCRIPT_FILENAME'] = $script;
	$_SERVER['PHP_SELF'] = $prefix . $pathInfo;
	$_SERVER['PATH_INFO'] = $pathInfo;
	$_SERVER['PATH_TRANSLATED'] = $root . $pathInfo;
	require $script;
	return true;
}

// 3. Nothing matched — the document root's index.php is Nextcloud's front
// controller, which is also the built-in server's own fallback.
return false;
