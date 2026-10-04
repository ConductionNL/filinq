<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DocumentDownloadRecorder.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\DocumentDownloadRecorder
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-f073c88b82eb816d6288c03683ecf217d2e6b39072832b5294ad78559578b403',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\DocumentDownloadRecorder',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/DocumentDownloadRecorder.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\DocumentDownloadRecorder',
    'shortName' => 'DocumentDownloadRecorder',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * One download, recorded, with the notification enqueued rather than sent.
 *
 * 🔴 A PUBLIC LINK NAMES THE LINK, NOT A PERSON. Whoever opened it is
 * unauthenticated, so the only identity available is a guess from an IP or a
 * session, and writing a guessed name into an audit record is worse than writing
 * none: a record that says "J. de Vries downloaded this" is read as fact by
 * whoever reads it next. The link is the thing that was actually used, and it is
 * the thing that can be revoked.
 *
 * 🔴 THE NOTIFICATION IS ENQUEUED, NEVER DELIVERED HERE. This runs on the
 * download path, where the person is waiting for bytes. A mail server that is
 * slow makes the download slow; a mail server that is down makes the download
 * fail, and the document then did not leave the building because a notification
 * could not be sent. Enqueuing separates "it happened" from "somebody was told".
 *
 * 🔑 THE COLLAPSE LIVES HERE BECAUSE THE PLATFORM DIALECT CANNOT DO IT.
 * `x-openregister-notifications` supports `trigger.dedupeFields`, and it is
 * honoured ONLY by `ScheduledNotificationJob` and `TaskScheduledNotificationJob`
 * — both scheduled paths. There is no collapsing for an event-driven
 * notification and no time-window concept in the dialect at all. Declaring a
 * window there would be a key nobody reads.
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 52,
    'endLine' => 151,
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
      'COLLAPSE_SECONDS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentDownloadRecorder',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentDownloadRecorder',
        'name' => 'COLLAPSE_SECONDS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '900',
          'attributes' => 
          array (
            'startLine' => 61,
            'endLine' => 61,
            'startTokenPos' => 40,
            'startFilePos' => 2408,
            'endTokenPos' => 40,
            'endFilePos' => 2410,
          ),
        ),
        'docComment' => '/**
 * How long repeats of one download collapse into one notification.
 *
 * Somebody opening a document three times while reading it is one event to
 * anybody being told about it, and three notifications is how a useful
 * signal becomes noise that gets muted.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 61,
        'endLine' => 61,
        'startColumn' => 2,
        'endColumn' => 37,
      ),
      'ANONYMOUS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentDownloadRecorder',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentDownloadRecorder',
        'name' => 'ANONYMOUS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'public-link\'',
          'attributes' => 
          array (
            'startLine' => 66,
            'endLine' => 66,
            'startTokenPos' => 53,
            'startFilePos' => 2507,
            'endTokenPos' => 53,
            'endFilePos' => 2519,
          ),
        ),
        'docComment' => '/**
 * What is written when the downloader cannot be named.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 66,
        'endLine' => 66,
        'startColumn' => 2,
        'endColumn' => 40,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'record' => 
      array (
        'name' => 'record',
        'parameters' => 
        array (
          'fileId' => 
          array (
            'name' => 'fileId',
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
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 3,
            'endColumn' => 13,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'version' => 
          array (
            'name' => 'version',
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
            'startLine' => 84,
            'endLine' => 84,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'route' => 
          array (
            'name' => 'route',
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
            'startLine' => 85,
            'endLine' => 85,
            'startColumn' => 3,
            'endColumn' => 15,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'userId' => 
          array (
            'name' => 'userId',
            'default' => NULL,
            'type' => 
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 86,
            'endLine' => 86,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'linkId' => 
          array (
            'name' => 'linkId',
            'default' => NULL,
            'type' => 
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 87,
            'endLine' => 87,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'moment' => 
          array (
            'name' => 'moment',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'DateTimeImmutable',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 88,
            'endLine' => 88,
            'startColumn' => 3,
            'endColumn' => 27,
            'parameterIndex' => 5,
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
 * One download, as a record.
 *
 * @param int                 $fileId   The file.
 * @param string              $version  The version taken, or \'\' for the current one.
 * @param string              $route    The route it was taken through.
 * @param string|null         $userId   The caller, or null on a public link.
 * @param string|null         $linkId   The link used, when there is one.
 * @param DateTimeImmutable   $moment   When.
 *
 * @return array<string, mixed> The record.
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
 */',
        'startLine' => 82,
        'endLine' => 108,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentDownloadRecorder',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentDownloadRecorder',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentDownloadRecorder',
        'aliasName' => NULL,
      ),
      'shouldNotify' => 
      array (
        'name' => 'shouldNotify',
        'parameters' => 
        array (
          'record' => 
          array (
            'name' => 'record',
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
            'startLine' => 125,
            'endLine' => 125,
            'startColumn' => 31,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'last' => 
          array (
            'name' => 'last',
            'default' => NULL,
            'type' => 
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
                      'name' => 'array',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 125,
            'endLine' => 125,
            'startColumn' => 46,
            'endColumn' => 57,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Whether this download should raise a notification, given the last one.
 *
 * 🔑 THE COLLAPSE IS PER FILE AND PER IDENTITY, NOT PER FILE ALONE. Two
 * different people downloading the same document within the window are two
 * facts, and collapsing them would hide the second person entirely — which
 * on a confidential document is the one you most want to know about.
 *
 * @param array<string, mixed>      $record The download just recorded.
 * @param array<string, mixed>|null $last   The last notified download of that file, or null.
 *
 * @return bool Whether to enqueue a notification.
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
 */',
        'startLine' => 125,
        'endLine' => 150,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\DocumentDownloadRecorder',
        'implementingClassName' => 'OCA\\Filinq\\Service\\DocumentDownloadRecorder',
        'currentClassName' => 'OCA\\Filinq\\Service\\DocumentDownloadRecorder',
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