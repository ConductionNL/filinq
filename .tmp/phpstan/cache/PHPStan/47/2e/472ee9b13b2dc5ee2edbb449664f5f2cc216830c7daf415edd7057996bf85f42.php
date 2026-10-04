<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Intake/IntakeNotificationReach.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Intake\IntakeNotificationReach
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-481d7da45b9d8465b09a9d8d34fe7987c33498f738102b3ed6af0d76cdb24873',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Intake\\IntakeNotificationReach',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Intake/IntakeNotificationReach.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Intake',
    'name' => 'OCA\\Filinq\\Service\\Intake\\IntakeNotificationReach',
    'shortName' => 'IntakeNotificationReach',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Says, on the inbox, whether the failure notification can reach anybody.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 48,
    'endLine' => 140,
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
      'RULE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeNotificationReach',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeNotificationReach',
        'name' => 'RULE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'readingFailed\'',
          'attributes' => 
          array (
            'startLine' => 55,
            'endLine' => 55,
            'startTokenPos' => 35,
            'startFilePos' => 2111,
            'endTokenPos' => 35,
            'endFilePos' => 2125,
          ),
        ),
        'docComment' => '/**
 * The rule declared on `intakeDocument`.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 55,
        'endLine' => 55,
        'startColumn' => 2,
        'endColumn' => 37,
      ),
      'GROUP' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeNotificationReach',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeNotificationReach',
        'name' => 'GROUP',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'docudesk-woo-officers\'',
          'attributes' => 
          array (
            'startLine' => 62,
            'endLine' => 62,
            'startTokenPos' => 48,
            'startFilePos' => 2215,
            'endTokenPos' => 48,
            'endFilePos' => 2237,
          ),
        ),
        'docComment' => '/**
 * The group the rule addresses.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 2,
        'endColumn' => 46,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'describe' => 
      array (
        'name' => 'describe',
        'parameters' => 
        array (
          'failureCount' => 
          array (
            'name' => 'failureCount',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 74,
            'endLine' => 74,
            'startColumn' => 27,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'groupMembers' => 
          array (
            'name' => 'groupMembers',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 74,
            'endLine' => 74,
            'startColumn' => 46,
            'endColumn' => 62,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * What the inbox shows about who will be told.
 *
 * @param int  $failureCount How many documents could not be read.
 * @param int  $groupMembers How many people are in the notified group.
 *
 * @return array<string, mixed> What to show.
 *
 * @spec openspec/changes/intake-failure-reaches-someone/specs/filinq-notifications/spec.md
 */',
        'startLine' => 74,
        'endLine' => 110,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Intake',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeNotificationReach',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeNotificationReach',
        'currentClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeNotificationReach',
        'aliasName' => NULL,
      ),
      'unstaffedWarning' => 
      array (
        'name' => 'unstaffedWarning',
        'parameters' => 
        array (
          'failureCount' => 
          array (
            'name' => 'failureCount',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 123,
            'endLine' => 123,
            'startColumn' => 36,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * What the inbox says when nobody is in the group.
 *
 * Names the group, because "nobody is configured" sends an administrator
 * hunting through settings, and the group name is the one thing that turns
 * this into a two-minute fix.
 *
 * @param int $failureCount How many documents could not be read.
 *
 * @return string The warning.
 */',
        'startLine' => 123,
        'endLine' => 139,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Intake',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeNotificationReach',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeNotificationReach',
        'currentClassName' => 'OCA\\Filinq\\Service\\Intake\\IntakeNotificationReach',
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