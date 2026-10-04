<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Command/OfficeProbeCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Command\OfficeProbeCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-72f0c271225ac268981e5e770362de5440ae7da859f3bbaf393ec1b8e0f124c8',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Command\\OfficeProbeCommand',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Command/OfficeProbeCommand.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Command',
    'name' => 'OCA\\Filinq\\Command\\OfficeProbeCommand',
    'shortName' => 'OfficeProbeCommand',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * `occ filinq:office:probe` — the command the setup documentation told people to run.
 *
 * It was documented before it existed. The setup guide instructed operators to
 * verify with this command and showed its expected output; running it returned
 * "command not found". A verification step that cannot be run is worse than none,
 * because the reader believes verification happened.
 *
 * Probes each suite SEPARATELY and reports each on its own line. No suite\'s result
 * is inferred from another\'s: on 2026-08-16 an ONLYOFFICE measurement was reported
 * under a Euro-Office heading, and per-suite output is the smallest thing that makes
 * that impossible to do by accident.
 *
 * @category Command
 * @package  OCA\\Filinq\\Command
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
 *
 * @suite-registry This command\'s whole purpose is to enumerate the known suites and
 *                 probe each one SEPARATELY, so it necessarily names their app ids.
 *                 It exposes no capability that depends on a suite being present —
 *                 every suite\'s absence is simply reported. ADR-087 §5 bans a
 *                 capability that requires a named suite, not a diagnostic that
 *                 lists them.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 67,
    'endLine' => 193,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Symfony\\Component\\Console\\Command\\Command',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'SUITES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Command\\OfficeProbeCommand',
        'implementingClassName' => 'OCA\\Filinq\\Command\\OfficeProbeCommand',
        'name' => 'SUITES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'onlyoffice\' => [\'app\' => \'onlyoffice\', \'key\' => \'DocumentServerInternalUrl\', \'paths\' => [\'/hosting/discovery\', \'/hosting/capabilities\']], \'eurooffice\' => [\'app\' => \'eurooffice\', \'key\' => \'DocumentServerInternalUrl\', \'paths\' => [\'/hosting/discovery\', \'/hosting/capabilities\']], \'collabora\' => [\'app\' => \'richdocuments\', \'key\' => \'wopi_url\', \'paths\' => [\'/hosting/discovery\']]]',
          'attributes' => 
          array (
            'startLine' => 78,
            'endLine' => 94,
            'startTokenPos' => 69,
            'startFilePos' => 2810,
            'endTokenPos' => 173,
            'endFilePos' => 3238,
          ),
        ),
        'docComment' => '/**
 * Suites this command knows how to locate, and the app-config key holding
 * each one\'s server URL.
 *
 * Listing a suite here is NOT a claim that it is supported — only that we know
 * where to look for it. The probe\'s output is the claim.
 *
 * @var array<string, array{app: string, key: string, paths: array<int, string>}>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 78,
        'endLine' => 94,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'capability' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Command\\OfficeProbeCommand',
        'implementingClassName' => 'OCA\\Filinq\\Command\\OfficeProbeCommand',
        'name' => 'capability',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 105,
        'endLine' => 105,
        'startColumn' => 3,
        'endColumn' => 59,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'appConfig' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Command\\OfficeProbeCommand',
        'implementingClassName' => 'OCA\\Filinq\\Command\\OfficeProbeCommand',
        'name' => 'appConfig',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IAppConfig',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 106,
        'endLine' => 106,
        'startColumn' => 3,
        'endColumn' => 40,
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
          'capability' => 
          array (
            'name' => 'capability',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Office\\OfficeSuiteCapabilityService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 105,
            'endLine' => 105,
            'startColumn' => 3,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'appConfig' => 
          array (
            'name' => 'appConfig',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IAppConfig',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 106,
            'endLine' => 106,
            'startColumn' => 3,
            'endColumn' => 40,
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
 * @param OfficeSuiteCapabilityService $capability The WOPI capability probe.
 * @param IAppConfig $appConfig App configuration.
 *
 * @return void
 */',
        'startLine' => 104,
        'endLine' => 109,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Command',
        'declaringClassName' => 'OCA\\Filinq\\Command\\OfficeProbeCommand',
        'implementingClassName' => 'OCA\\Filinq\\Command\\OfficeProbeCommand',
        'currentClassName' => 'OCA\\Filinq\\Command\\OfficeProbeCommand',
        'aliasName' => NULL,
      ),
      'configure' => 
      array (
        'name' => 'configure',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Define the command.
 *
 * Registers `filinq:office:probe` — the verification step the setup guide
 * already instructed operators to run. The requirement below forbids inferring
 * availability from installation, which is only enforceable if the probe the
 * documentation names actually exists.
 *
 * @return void
 *
 * @spec openspec/specs/office-suite-portability/spec.md#requirement-wopi-availability-must-be-probed-never-inferred-from-installation
 */',
        'startLine' => 123,
        'endLine' => 127,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'OCA\\Filinq\\Command',
        'declaringClassName' => 'OCA\\Filinq\\Command\\OfficeProbeCommand',
        'implementingClassName' => 'OCA\\Filinq\\Command\\OfficeProbeCommand',
        'currentClassName' => 'OCA\\Filinq\\Command\\OfficeProbeCommand',
        'aliasName' => NULL,
      ),
      'execute' => 
      array (
        'name' => 'execute',
        'parameters' => 
        array (
          'input' => 
          array (
            'name' => 'input',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Symfony\\Component\\Console\\Input\\InputInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 144,
            'endLine' => 144,
            'startColumn' => 29,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'output' => 
          array (
            'name' => 'output',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Symfony\\Component\\Console\\Output\\OutputInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 144,
            'endLine' => 144,
            'startColumn' => 52,
            'endColumn' => 74,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Run the probe.
 *
 * Probes each configured suite on its own and prints one line per suite. No
 * suite\'s verdict is derived from another\'s — that is the failure the
 * requirement below records, where an ONLYOFFICE measurement was reported under
 * a Euro-Office heading.
 *
 * @param InputInterface $input The console input.
 * @param OutputInterface $output The console output.
 *
 * @return int The exit code.
 *
 * @spec openspec/specs/office-suite-portability/spec.md#requirement-a-suite-must-not-be-claimed-as-supported-until-it-has-been-run
 */',
        'startLine' => 144,
        'endLine' => 162,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'OCA\\Filinq\\Command',
        'declaringClassName' => 'OCA\\Filinq\\Command\\OfficeProbeCommand',
        'implementingClassName' => 'OCA\\Filinq\\Command\\OfficeProbeCommand',
        'currentClassName' => 'OCA\\Filinq\\Command\\OfficeProbeCommand',
        'aliasName' => NULL,
      ),
      'reportSuite' => 
      array (
        'name' => 'reportSuite',
        'parameters' => 
        array (
          'output' => 
          array (
            'name' => 'output',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Symfony\\Component\\Console\\Output\\OutputInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 173,
            'endLine' => 173,
            'startColumn' => 31,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'name' => 
          array (
            'name' => 'name',
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
            'startLine' => 173,
            'endLine' => 173,
            'startColumn' => 56,
            'endColumn' => 67,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'suite' => 
          array (
            'name' => 'suite',
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
            'startLine' => 173,
            'endLine' => 173,
            'startColumn' => 70,
            'endColumn' => 81,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Report one suite.
 *
 * @param OutputInterface $output The console output.
 * @param string $name The suite name.
 * @param array $suite The suite mapping.
 *
 * @return void
 */',
        'startLine' => 173,
        'endLine' => 192,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Command',
        'declaringClassName' => 'OCA\\Filinq\\Command\\OfficeProbeCommand',
        'implementingClassName' => 'OCA\\Filinq\\Command\\OfficeProbeCommand',
        'currentClassName' => 'OCA\\Filinq\\Command\\OfficeProbeCommand',
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