<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Office/OfficeSuiteCapabilityService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Office\OfficeSuiteCapabilityService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-b975c709994433f1d74401ba9ea73ca5324c75ecf0a5fb54ca89f75f7cad5762',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Office/OfficeSuiteCapabilityService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Office',
    'name' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
    'shortName' => 'OfficeSuiteCapabilityService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Probes WOPI availability with a real CheckFileInfo.
 *
 * ADR-087 §3: availability is capability-probed per instance, never assumed, and
 * "the app is installed" is explicitly NOT the probe.
 *
 * That is not a theoretical caution. Measured against onlyoffice/documentserver on
 * 2026-08-16, with WOPI left at its shipped default:
 *
 *   container health .......... healthy
 *   GET /healthcheck .......... 200, body `true`
 *   GET / ..................... 302 (serving)
 *   GET /hosting/discovery .... 404
 *   GET /hosting/wopi/discovery 404
 *   GET /hosting/capabilities . 404
 *   default.json .............. "wopi": { "enable": false }
 *
 * Every check a person reaches for by instinct — is it up, does the port answer,
 * is the admin page green — returns YES in that state, and WOPI serves nothing.
 * Only asking WOPI a WOPI question separates the two.
 *
 * The probe therefore fails CLOSED. A wrong "absent" hides a feature that would
 * have worked; a wrong "available" ships a control that fails in a user\'s hands,
 * and the second is much the more expensive mistake.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Office
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://filinq.app
 *
 * @spec openspec/specs/office-suite-portability/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 70,
    'endLine' => 302,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'REQUIRED_FIELDS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
        'name' => 'REQUIRED_FIELDS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'BaseFileName\', \'Size\']',
          'attributes' => 
          array (
            'startLine' => 81,
            'endLine' => 81,
            'startTokenPos' => 50,
            'startFilePos' => 2591,
            'endTokenPos' => 55,
            'endFilePos' => 2614,
          ),
        ),
        'docComment' => '/**
 * Fields a CheckFileInfo response must carry to be usable.
 *
 * WOPI defines both as required. A 2xx body lacking either cannot support a
 * session, so treating the status alone as success would report available for
 * a host that cannot serve one.
 *
 * @var string[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 81,
        'endLine' => 81,
        'startColumn' => 2,
        'endColumn' => 58,
      ),
      'TIMEOUT_SECONDS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
        'name' => 'TIMEOUT_SECONDS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '5',
          'attributes' => 
          array (
            'startLine' => 92,
            'endLine' => 92,
            'startTokenPos' => 68,
            'startFilePos' => 2883,
            'endTokenPos' => 68,
            'endFilePos' => 2883,
          ),
        ),
        'docComment' => '/**
 * Probe timeout in seconds.
 *
 * Bounded because the probe can run inside a user-facing request. An
 * unreachable host must cost a bounded wait and then resolve absent, never
 * hang the response.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 92,
        'endLine' => 92,
        'startColumn' => 2,
        'endColumn' => 35,
      ),
    ),
    'immediateProperties' => 
    array (
      'clientService' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
        'name' => 'clientService',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\Http\\Client\\IClientService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 103,
        'endLine' => 103,
        'startColumn' => 3,
        'endColumn' => 48,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
        'name' => 'logger',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Psr\\Log\\LoggerInterface',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 104,
        'endLine' => 104,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'clientService' => 
          array (
            'name' => 'clientService',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Http\\Client\\IClientService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 103,
            'endLine' => 103,
            'startColumn' => 3,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'logger' => 
          array (
            'name' => 'logger',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Psr\\Log\\LoggerInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor.
 *
 * @param IClientService $clientService The HTTP client factory.
 * @param LoggerInterface $logger The logger.
 *
 * @return void
 */',
        'startLine' => 102,
        'endLine' => 106,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Office',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
        'aliasName' => NULL,
      ),
      'probeDiscovery' => 
      array (
        'name' => 'probeDiscovery',
        'parameters' => 
        array (
          'discoveryUrl' => 
          array (
            'name' => 'discoveryUrl',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 134,
            'endLine' => 134,
            'startColumn' => 33,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Probe a WOPI host\'s DISCOVERY endpoint.
 *
 * ADR-087 §3 says "a successful `CheckFileInfo` is the probe". That is not
 * achievable at capability-resolution time and the ADR was wrong to require it:
 * `CheckFileInfo` is a PER-FILE call authenticated by a short-lived access token
 * the host mints when a user opens a specific document. There is no file and no
 * token when a capability is being resolved.
 *
 * WOPI **discovery** is the endpoint that answers "is there a usable WOPI host
 * here" without a file: it returns XML listing the actions the host supports. A
 * suite with WOPI switched off does not serve it — measured on ONLYOFFICE
 * 2026-08-16, `/hosting/discovery` returned 404 until `WOPI_ENABLED=true`, so
 * discovery separates "installed" from "usable" exactly as intended.
 *
 * This method was originally written to validate a `CheckFileInfo` JSON body and
 * was then pointed at a discovery URL, so it reported every working suite as
 * absent for "missing the required field BaseFileName" — a probe failing on the
 * shape of a response it was never given.
 *
 * @param string $discoveryUrl Absolute WOPI discovery URL to probe.
 *
 * @return array{available:bool, reason:string, suite:string|null} The verdict.
 *
 * @spec openspec/specs/office-suite-portability/spec.md
 */',
        'startLine' => 134,
        'endLine' => 176,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Office',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
        'aliasName' => NULL,
      ),
      'identifyFromDiscovery' => 
      array (
        'name' => 'identifyFromDiscovery',
        'parameters' => 
        array (
          'body' => 
          array (
            'name' => 'body',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 189,
            'endLine' => 189,
            'startColumn' => 41,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
          'data' => 
          array (
            'types' => 
            array (
              0 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'string',
                  'isIdentifier' => true,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'null',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Name the suite from its discovery document, when it says.
 *
 * Reporting only. Nothing branches on which suite answered — a capability that
 * behaved differently per suite would be the per-suite driver set ADR-087 §5
 * bans.
 *
 * @param string $body The discovery XML.
 *
 * @return string|null The suite name, or null.
 */',
        'startLine' => 189,
        'endLine' => 195,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Office',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
        'aliasName' => NULL,
      ),
      'probe' => 
      array (
        'name' => 'probe',
        'parameters' => 
        array (
          'checkFileInfoUrl' => 
          array (
            'name' => 'checkFileInfoUrl',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 209,
            'endLine' => 209,
            'startColumn' => 24,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Probe a WOPI host\'s CheckFileInfo for a specific file.
 *
 * Only usable where a real file and access token exist — i.e. inside a session,
 * not at capability-resolution time. See {@see probeDiscovery()}.
 *
 * @param string $checkFileInfoUrl Absolute CheckFileInfo URL to probe.
 *
 * @return array{available:bool, reason:string, suite:string|null} The verdict.
 *
 * @spec openspec/specs/office-suite-portability/spec.md
 */',
        'startLine' => 209,
        'endLine' => 257,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Office',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
        'aliasName' => NULL,
      ),
      'absent' => 
      array (
        'name' => 'absent',
        'parameters' => 
        array (
          'reason' => 
          array (
            'name' => 'reason',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 270,
            'endLine' => 270,
            'startColumn' => 26,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Build an absent verdict and record why.
 *
 * The reason is retained rather than collapsed to a boolean because "no suite
 * installed" and "suite installed with WOPI disabled" need different actions
 * from an operator, and the second is invisible from every other angle.
 *
 * @param string $reason Why the probe resolved absent.
 *
 * @return array{available:bool, reason:string, suite:string|null} The verdict.
 */',
        'startLine' => 270,
        'endLine' => 281,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Office',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
        'aliasName' => NULL,
      ),
      'identifySuite' => 
      array (
        'name' => 'identifySuite',
        'parameters' => 
        array (
          'payload' => 
          array (
            'name' => 'payload',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 294,
            'endLine' => 294,
            'startColumn' => 33,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
          'data' => 
          array (
            'types' => 
            array (
              0 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'string',
                  'isIdentifier' => true,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'null',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Name the responding suite when it identifies itself.
 *
 * Reporting only. Nothing in Filinq branches on which suite answered — a
 * capability that behaved differently per suite would be the per-suite driver
 * set ADR-087 §5 bans.
 *
 * @param array $payload The decoded CheckFileInfo response.
 *
 * @return string|null The suite name, or null when it did not say.
 */',
        'startLine' => 294,
        'endLine' => 301,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Office',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
        'aliasName' => NULL,
      ),
    ),
    'traitsData' => 
    array (
      'aliases' => 
      array (
      ),
      'modifiers' => 
      array (
      ),
      'precedences' => 
      array (
      ),
      'hashes' => 
      array (
      ),
    ),
  ),
));