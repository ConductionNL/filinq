<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionClass-ziparchive
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-dev-master@709e512-8.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\InternalLocatedSource',
      'data' => 
      array (
        'name' => 'ZipArchive',
        'filename' => 'phpstorm-stubs:zip/zip.stub',
        'extensionName' => 'zip',
        'aliasName' => NULL,
      ),
    ),
    'namespace' => NULL,
    'name' => 'ZipArchive',
    'shortName' => 'ZipArchive',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * A file archive, compressed with Zip.
 * @link https://php.net/manual/en/class.ziparchive.php
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 8,
    'endLine' => 1543,
    'startColumn' => 5,
    'endColumn' => 5,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Countable',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'LIBZIP_VERSION' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'LIBZIP_VERSION',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'1.7.3\'',
          'attributes' => 
          array (
            'startLine' => 15,
            'endLine' => 15,
            'startTokenPos' => 28,
            'startFilePos' => 364,
            'endTokenPos' => 28,
            'endFilePos' => 370,
          ),
        ),
        'docComment' => '/**
 * Zip library version
 * @link https://php.net/manual/en/zip.constants.php
 * @since 7.4
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 15,
        'endLine' => 15,
        'startColumn' => 9,
        'endColumn' => 46,
      ),
      'CREATE' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'CREATE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 20,
            'startTokenPos' => 41,
            'startFilePos' => 540,
            'endTokenPos' => 41,
            'endFilePos' => 540,
          ),
        ),
        'docComment' => '/**
 * Create the archive if it does not exist.
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 9,
        'endColumn' => 32,
      ),
      'EXCL' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'EXCL',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '2',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 54,
            'startFilePos' => 700,
            'endTokenPos' => 54,
            'endFilePos' => 700,
          ),
        ),
        'docComment' => '/**
 * Error if archive already exists.
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 9,
        'endColumn' => 30,
      ),
      'CHECKCONS' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'CHECKCONS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '4',
          'attributes' => 
          array (
            'startLine' => 30,
            'endLine' => 30,
            'startTokenPos' => 67,
            'startFilePos' => 910,
            'endTokenPos' => 67,
            'endFilePos' => 910,
          ),
        ),
        'docComment' => '/**
 * Perform additional consistency checks on the archive, and error if they fail.
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 9,
        'endColumn' => 35,
      ),
      'OVERWRITE' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'OVERWRITE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '8',
          'attributes' => 
          array (
            'startLine' => 36,
            'endLine' => 36,
            'startTokenPos' => 80,
            'startFilePos' => 1137,
            'endTokenPos' => 80,
            'endFilePos' => 1137,
          ),
        ),
        'docComment' => '/**
 * Always start a new archive, this mode will overwrite the file if
 * it already exists.
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 36,
        'endLine' => 36,
        'startColumn' => 9,
        'endColumn' => 35,
      ),
      'FL_NOCASE' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'FL_NOCASE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1',
          'attributes' => 
          array (
            'startLine' => 41,
            'endLine' => 41,
            'startTokenPos' => 93,
            'startFilePos' => 1296,
            'endTokenPos' => 93,
            'endFilePos' => 1296,
          ),
        ),
        'docComment' => '/**
 * Ignore case on name lookup
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 41,
        'endLine' => 41,
        'startColumn' => 9,
        'endColumn' => 35,
      ),
      'FL_NODIR' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'FL_NODIR',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '2',
          'attributes' => 
          array (
            'startLine' => 46,
            'endLine' => 46,
            'startTokenPos' => 106,
            'startFilePos' => 1454,
            'endTokenPos' => 106,
            'endFilePos' => 1454,
          ),
        ),
        'docComment' => '/**
 * Ignore directory component
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 46,
        'endLine' => 46,
        'startColumn' => 9,
        'endColumn' => 34,
      ),
      'FL_COMPRESSED' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'FL_COMPRESSED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '4',
          'attributes' => 
          array (
            'startLine' => 51,
            'endLine' => 51,
            'startTokenPos' => 119,
            'startFilePos' => 1611,
            'endTokenPos' => 119,
            'endFilePos' => 1611,
          ),
        ),
        'docComment' => '/**
 * Read compressed data
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 51,
        'endLine' => 51,
        'startColumn' => 9,
        'endColumn' => 39,
      ),
      'FL_UNCHANGED' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'FL_UNCHANGED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '8',
          'attributes' => 
          array (
            'startLine' => 56,
            'endLine' => 56,
            'startTokenPos' => 132,
            'startFilePos' => 1783,
            'endTokenPos' => 132,
            'endFilePos' => 1783,
          ),
        ),
        'docComment' => '/**
 * Use original data, ignoring changes.
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 56,
        'endLine' => 56,
        'startColumn' => 9,
        'endColumn' => 38,
      ),
      'FL_RECOMPRESS' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'FL_RECOMPRESS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '16',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 57,
            'startTokenPos' => 143,
            'startFilePos' => 1823,
            'endTokenPos' => 143,
            'endFilePos' => 1824,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 9,
        'endColumn' => 40,
      ),
      'FL_ENCRYPTED' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'FL_ENCRYPTED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '32',
          'attributes' => 
          array (
            'startLine' => 58,
            'endLine' => 58,
            'startTokenPos' => 154,
            'startFilePos' => 1863,
            'endTokenPos' => 154,
            'endFilePos' => 1864,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 58,
        'endLine' => 58,
        'startColumn' => 9,
        'endColumn' => 39,
      ),
      'FL_OVERWRITE' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'FL_OVERWRITE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '8192',
          'attributes' => 
          array (
            'startLine' => 59,
            'endLine' => 59,
            'startTokenPos' => 165,
            'startFilePos' => 1903,
            'endTokenPos' => 165,
            'endFilePos' => 1906,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 59,
        'endLine' => 59,
        'startColumn' => 9,
        'endColumn' => 41,
      ),
      'FL_LOCAL' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'FL_LOCAL',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '256',
          'attributes' => 
          array (
            'startLine' => 60,
            'endLine' => 60,
            'startTokenPos' => 176,
            'startFilePos' => 1941,
            'endTokenPos' => 176,
            'endFilePos' => 1943,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 60,
        'endLine' => 60,
        'startColumn' => 9,
        'endColumn' => 36,
      ),
      'FL_CENTRAL' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'FL_CENTRAL',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '512',
          'attributes' => 
          array (
            'startLine' => 61,
            'endLine' => 61,
            'startTokenPos' => 187,
            'startFilePos' => 1980,
            'endTokenPos' => 187,
            'endFilePos' => 1982,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 61,
        'endLine' => 61,
        'startColumn' => 9,
        'endColumn' => 38,
      ),
      'EM_TRAD_PKWARE' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'EM_TRAD_PKWARE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1',
          'attributes' => 
          array (
            'startLine' => 62,
            'endLine' => 62,
            'startTokenPos' => 198,
            'startFilePos' => 2023,
            'endTokenPos' => 198,
            'endFilePos' => 2023,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 9,
        'endColumn' => 40,
      ),
      'EM_UNKNOWN' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'EM_UNKNOWN',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '65535',
          'attributes' => 
          array (
            'startLine' => 63,
            'endLine' => 63,
            'startTokenPos' => 209,
            'startFilePos' => 2060,
            'endTokenPos' => 209,
            'endFilePos' => 2064,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 63,
        'endLine' => 63,
        'startColumn' => 9,
        'endColumn' => 40,
      ),
      'CM_DEFAULT' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'CM_DEFAULT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '-1',
          'attributes' => 
          array (
            'startLine' => 68,
            'endLine' => 68,
            'startTokenPos' => 222,
            'startFilePos' => 2225,
            'endTokenPos' => 223,
            'endFilePos' => 2226,
          ),
        ),
        'docComment' => '/**
 * better of deflate or store.
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 68,
        'endLine' => 68,
        'startColumn' => 9,
        'endColumn' => 37,
      ),
      'CM_STORE' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'CM_STORE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0',
          'attributes' => 
          array (
            'startLine' => 73,
            'endLine' => 73,
            'startTokenPos' => 236,
            'startFilePos' => 2380,
            'endTokenPos' => 236,
            'endFilePos' => 2380,
          ),
        ),
        'docComment' => '/**
 * stored (uncompressed).
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 9,
        'endColumn' => 34,
      ),
      'CM_SHRINK' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'CM_SHRINK',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1',
          'attributes' => 
          array (
            'startLine' => 78,
            'endLine' => 78,
            'startTokenPos' => 249,
            'startFilePos' => 2519,
            'endTokenPos' => 249,
            'endFilePos' => 2519,
          ),
        ),
        'docComment' => '/**
 * shrunk
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 78,
        'endLine' => 78,
        'startColumn' => 9,
        'endColumn' => 35,
      ),
      'CM_REDUCE_1' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'CM_REDUCE_1',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '2',
          'attributes' => 
          array (
            'startLine' => 83,
            'endLine' => 83,
            'startTokenPos' => 262,
            'startFilePos' => 2675,
            'endTokenPos' => 262,
            'endFilePos' => 2675,
          ),
        ),
        'docComment' => '/**
 * reduced with factor 1
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 83,
        'endLine' => 83,
        'startColumn' => 9,
        'endColumn' => 37,
      ),
      'CM_REDUCE_2' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'CM_REDUCE_2',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '3',
          'attributes' => 
          array (
            'startLine' => 88,
            'endLine' => 88,
            'startTokenPos' => 275,
            'startFilePos' => 2831,
            'endTokenPos' => 275,
            'endFilePos' => 2831,
          ),
        ),
        'docComment' => '/**
 * reduced with factor 2
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 88,
        'endLine' => 88,
        'startColumn' => 9,
        'endColumn' => 37,
      ),
      'CM_REDUCE_3' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'CM_REDUCE_3',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '4',
          'attributes' => 
          array (
            'startLine' => 93,
            'endLine' => 93,
            'startTokenPos' => 288,
            'startFilePos' => 2987,
            'endTokenPos' => 288,
            'endFilePos' => 2987,
          ),
        ),
        'docComment' => '/**
 * reduced with factor 3
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 93,
        'endLine' => 93,
        'startColumn' => 9,
        'endColumn' => 37,
      ),
      'CM_REDUCE_4' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'CM_REDUCE_4',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '5',
          'attributes' => 
          array (
            'startLine' => 98,
            'endLine' => 98,
            'startTokenPos' => 301,
            'startFilePos' => 3143,
            'endTokenPos' => 301,
            'endFilePos' => 3143,
          ),
        ),
        'docComment' => '/**
 * reduced with factor 4
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 98,
        'endLine' => 98,
        'startColumn' => 9,
        'endColumn' => 37,
      ),
      'CM_IMPLODE' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'CM_IMPLODE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '6',
          'attributes' => 
          array (
            'startLine' => 103,
            'endLine' => 103,
            'startTokenPos' => 314,
            'startFilePos' => 3285,
            'endTokenPos' => 314,
            'endFilePos' => 3285,
          ),
        ),
        'docComment' => '/**
 * imploded
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 103,
        'endLine' => 103,
        'startColumn' => 9,
        'endColumn' => 36,
      ),
      'CM_DEFLATE' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'CM_DEFLATE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '8',
          'attributes' => 
          array (
            'startLine' => 108,
            'endLine' => 108,
            'startTokenPos' => 327,
            'startFilePos' => 3427,
            'endTokenPos' => 327,
            'endFilePos' => 3427,
          ),
        ),
        'docComment' => '/**
 * deflated
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 108,
        'endLine' => 108,
        'startColumn' => 9,
        'endColumn' => 36,
      ),
      'CM_DEFLATE64' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'CM_DEFLATE64',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '9',
          'attributes' => 
          array (
            'startLine' => 113,
            'endLine' => 113,
            'startTokenPos' => 340,
            'startFilePos' => 3572,
            'endTokenPos' => 340,
            'endFilePos' => 3572,
          ),
        ),
        'docComment' => '/**
 * deflate64
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 113,
        'endLine' => 113,
        'startColumn' => 9,
        'endColumn' => 38,
      ),
      'CM_PKWARE_IMPLODE' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'CM_PKWARE_IMPLODE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '10',
          'attributes' => 
          array (
            'startLine' => 118,
            'endLine' => 118,
            'startTokenPos' => 353,
            'startFilePos' => 3729,
            'endTokenPos' => 353,
            'endFilePos' => 3730,
          ),
        ),
        'docComment' => '/**
 * PKWARE imploding
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 118,
        'endLine' => 118,
        'startColumn' => 9,
        'endColumn' => 44,
      ),
      'CM_BZIP2' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'CM_BZIP2',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '12',
          'attributes' => 
          array (
            'startLine' => 123,
            'endLine' => 123,
            'startTokenPos' => 366,
            'startFilePos' => 3877,
            'endTokenPos' => 366,
            'endFilePos' => 3878,
          ),
        ),
        'docComment' => '/**
 * BZIP2 algorithm
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 123,
        'endLine' => 123,
        'startColumn' => 9,
        'endColumn' => 35,
      ),
      'CM_LZMA' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'CM_LZMA',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '14',
          'attributes' => 
          array (
            'startLine' => 124,
            'endLine' => 124,
            'startTokenPos' => 377,
            'startFilePos' => 3912,
            'endTokenPos' => 377,
            'endFilePos' => 3913,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 124,
        'endLine' => 124,
        'startColumn' => 9,
        'endColumn' => 34,
      ),
      'CM_TERSE' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'CM_TERSE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '18',
          'attributes' => 
          array (
            'startLine' => 125,
            'endLine' => 125,
            'startTokenPos' => 388,
            'startFilePos' => 3948,
            'endTokenPos' => 388,
            'endFilePos' => 3949,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 125,
        'endLine' => 125,
        'startColumn' => 9,
        'endColumn' => 35,
      ),
      'CM_LZ77' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'CM_LZ77',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '19',
          'attributes' => 
          array (
            'startLine' => 126,
            'endLine' => 126,
            'startTokenPos' => 399,
            'startFilePos' => 3983,
            'endTokenPos' => 399,
            'endFilePos' => 3984,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 126,
        'endLine' => 126,
        'startColumn' => 9,
        'endColumn' => 34,
      ),
      'CM_WAVPACK' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'CM_WAVPACK',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '97',
          'attributes' => 
          array (
            'startLine' => 127,
            'endLine' => 127,
            'startTokenPos' => 410,
            'startFilePos' => 4021,
            'endTokenPos' => 410,
            'endFilePos' => 4022,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 127,
        'endLine' => 127,
        'startColumn' => 9,
        'endColumn' => 37,
      ),
      'CM_PPMD' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'CM_PPMD',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '98',
          'attributes' => 
          array (
            'startLine' => 128,
            'endLine' => 128,
            'startTokenPos' => 421,
            'startFilePos' => 4056,
            'endTokenPos' => 421,
            'endFilePos' => 4057,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 128,
        'endLine' => 128,
        'startColumn' => 9,
        'endColumn' => 34,
      ),
      'ER_OK' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_OK',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0',
          'attributes' => 
          array (
            'startLine' => 133,
            'endLine' => 133,
            'startTokenPos' => 434,
            'startFilePos' => 4195,
            'endTokenPos' => 434,
            'endFilePos' => 4195,
          ),
        ),
        'docComment' => '/**
 * No error.
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 133,
        'endLine' => 133,
        'startColumn' => 9,
        'endColumn' => 31,
      ),
      'ER_MULTIDISK' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_MULTIDISK',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1',
          'attributes' => 
          array (
            'startLine' => 138,
            'endLine' => 138,
            'startTokenPos' => 447,
            'startFilePos' => 4369,
            'endTokenPos' => 447,
            'endFilePos' => 4369,
          ),
        ),
        'docComment' => '/**
 * Multi-disk zip archives not supported.
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 138,
        'endLine' => 138,
        'startColumn' => 9,
        'endColumn' => 38,
      ),
      'ER_RENAME' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_RENAME',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '2',
          'attributes' => 
          array (
            'startLine' => 143,
            'endLine' => 143,
            'startTokenPos' => 460,
            'startFilePos' => 4533,
            'endTokenPos' => 460,
            'endFilePos' => 4533,
          ),
        ),
        'docComment' => '/**
 * Renaming temporary file failed.
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 143,
        'endLine' => 143,
        'startColumn' => 9,
        'endColumn' => 35,
      ),
      'ER_CLOSE' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_CLOSE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '3',
          'attributes' => 
          array (
            'startLine' => 148,
            'endLine' => 148,
            'startTokenPos' => 473,
            'startFilePos' => 4691,
            'endTokenPos' => 473,
            'endFilePos' => 4691,
          ),
        ),
        'docComment' => '/**
 * Closing zip archive failed
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 148,
        'endLine' => 148,
        'startColumn' => 9,
        'endColumn' => 34,
      ),
      'ER_SEEK' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_SEEK',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '4',
          'attributes' => 
          array (
            'startLine' => 153,
            'endLine' => 153,
            'startTokenPos' => 486,
            'startFilePos' => 4832,
            'endTokenPos' => 486,
            'endFilePos' => 4832,
          ),
        ),
        'docComment' => '/**
 * Seek error
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 153,
        'endLine' => 153,
        'startColumn' => 9,
        'endColumn' => 33,
      ),
      'ER_READ' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_READ',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '5',
          'attributes' => 
          array (
            'startLine' => 158,
            'endLine' => 158,
            'startTokenPos' => 499,
            'startFilePos' => 4973,
            'endTokenPos' => 499,
            'endFilePos' => 4973,
          ),
        ),
        'docComment' => '/**
 * Read error
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 158,
        'endLine' => 158,
        'startColumn' => 9,
        'endColumn' => 33,
      ),
      'ER_WRITE' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_WRITE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '6',
          'attributes' => 
          array (
            'startLine' => 163,
            'endLine' => 163,
            'startTokenPos' => 512,
            'startFilePos' => 5116,
            'endTokenPos' => 512,
            'endFilePos' => 5116,
          ),
        ),
        'docComment' => '/**
 * Write error
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 163,
        'endLine' => 163,
        'startColumn' => 9,
        'endColumn' => 34,
      ),
      'ER_CRC' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_CRC',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '7',
          'attributes' => 
          array (
            'startLine' => 168,
            'endLine' => 168,
            'startTokenPos' => 525,
            'startFilePos' => 5255,
            'endTokenPos' => 525,
            'endFilePos' => 5255,
          ),
        ),
        'docComment' => '/**
 * CRC error
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 168,
        'endLine' => 168,
        'startColumn' => 9,
        'endColumn' => 32,
      ),
      'ER_ZIPCLOSED' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_ZIPCLOSED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '8',
          'attributes' => 
          array (
            'startLine' => 173,
            'endLine' => 173,
            'startTokenPos' => 538,
            'startFilePos' => 5424,
            'endTokenPos' => 538,
            'endFilePos' => 5424,
          ),
        ),
        'docComment' => '/**
 * Containing zip archive was closed
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 173,
        'endLine' => 173,
        'startColumn' => 9,
        'endColumn' => 38,
      ),
      'ER_NOENT' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_NOENT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '9',
          'attributes' => 
          array (
            'startLine' => 178,
            'endLine' => 178,
            'startTokenPos' => 551,
            'startFilePos' => 5569,
            'endTokenPos' => 551,
            'endFilePos' => 5569,
          ),
        ),
        'docComment' => '/**
 * No such file.
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 178,
        'endLine' => 178,
        'startColumn' => 9,
        'endColumn' => 34,
      ),
      'ER_EXISTS' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_EXISTS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '10',
          'attributes' => 
          array (
            'startLine' => 183,
            'endLine' => 183,
            'startTokenPos' => 564,
            'startFilePos' => 5721,
            'endTokenPos' => 564,
            'endFilePos' => 5722,
          ),
        ),
        'docComment' => '/**
 * File already exists
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 183,
        'endLine' => 183,
        'startColumn' => 9,
        'endColumn' => 36,
      ),
      'ER_OPEN' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_OPEN',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '11',
          'attributes' => 
          array (
            'startLine' => 188,
            'endLine' => 188,
            'startTokenPos' => 577,
            'startFilePos' => 5868,
            'endTokenPos' => 577,
            'endFilePos' => 5869,
          ),
        ),
        'docComment' => '/**
 * Can\'t open file
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 188,
        'endLine' => 188,
        'startColumn' => 9,
        'endColumn' => 34,
      ),
      'ER_TMPOPEN' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_TMPOPEN',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '12',
          'attributes' => 
          array (
            'startLine' => 193,
            'endLine' => 193,
            'startTokenPos' => 590,
            'startFilePos' => 6036,
            'endTokenPos' => 590,
            'endFilePos' => 6037,
          ),
        ),
        'docComment' => '/**
 * Failure to create temporary file.
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 193,
        'endLine' => 193,
        'startColumn' => 9,
        'endColumn' => 37,
      ),
      'ER_ZLIB' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_ZLIB',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '13',
          'attributes' => 
          array (
            'startLine' => 198,
            'endLine' => 198,
            'startTokenPos' => 603,
            'startFilePos' => 6178,
            'endTokenPos' => 603,
            'endFilePos' => 6179,
          ),
        ),
        'docComment' => '/**
 * Zlib error
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 198,
        'endLine' => 198,
        'startColumn' => 9,
        'endColumn' => 34,
      ),
      'ER_MEMORY' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_MEMORY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '14',
          'attributes' => 
          array (
            'startLine' => 203,
            'endLine' => 203,
            'startTokenPos' => 616,
            'startFilePos' => 6337,
            'endTokenPos' => 616,
            'endFilePos' => 6338,
          ),
        ),
        'docComment' => '/**
 * Memory allocation failure
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 203,
        'endLine' => 203,
        'startColumn' => 9,
        'endColumn' => 36,
      ),
      'ER_CHANGED' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_CHANGED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '15',
          'attributes' => 
          array (
            'startLine' => 208,
            'endLine' => 208,
            'startTokenPos' => 629,
            'startFilePos' => 6494,
            'endTokenPos' => 629,
            'endFilePos' => 6495,
          ),
        ),
        'docComment' => '/**
 * Entry has been changed
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 208,
        'endLine' => 208,
        'startColumn' => 9,
        'endColumn' => 37,
      ),
      'ER_COMPNOTSUPP' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_COMPNOTSUPP',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '16',
          'attributes' => 
          array (
            'startLine' => 213,
            'endLine' => 213,
            'startTokenPos' => 642,
            'startFilePos' => 6666,
            'endTokenPos' => 642,
            'endFilePos' => 6667,
          ),
        ),
        'docComment' => '/**
 * Compression method not supported.
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 213,
        'endLine' => 213,
        'startColumn' => 9,
        'endColumn' => 41,
      ),
      'ER_EOF' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_EOF',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '17',
          'attributes' => 
          array (
            'startLine' => 218,
            'endLine' => 218,
            'startTokenPos' => 655,
            'startFilePos' => 6810,
            'endTokenPos' => 655,
            'endFilePos' => 6811,
          ),
        ),
        'docComment' => '/**
 * Premature EOF
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 218,
        'endLine' => 218,
        'startColumn' => 9,
        'endColumn' => 33,
      ),
      'ER_INVAL' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_INVAL',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '18',
          'attributes' => 
          array (
            'startLine' => 223,
            'endLine' => 223,
            'startTokenPos' => 668,
            'startFilePos' => 6959,
            'endTokenPos' => 668,
            'endFilePos' => 6960,
          ),
        ),
        'docComment' => '/**
 * Invalid argument
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 223,
        'endLine' => 223,
        'startColumn' => 9,
        'endColumn' => 35,
      ),
      'ER_NOZIP' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_NOZIP',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '19',
          'attributes' => 
          array (
            'startLine' => 228,
            'endLine' => 228,
            'startTokenPos' => 681,
            'startFilePos' => 7109,
            'endTokenPos' => 681,
            'endFilePos' => 7110,
          ),
        ),
        'docComment' => '/**
 * Not a zip archive
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 228,
        'endLine' => 228,
        'startColumn' => 9,
        'endColumn' => 35,
      ),
      'ER_INTERNAL' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_INTERNAL',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '20',
          'attributes' => 
          array (
            'startLine' => 233,
            'endLine' => 233,
            'startTokenPos' => 694,
            'startFilePos' => 7259,
            'endTokenPos' => 694,
            'endFilePos' => 7260,
          ),
        ),
        'docComment' => '/**
 * Internal error
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 233,
        'endLine' => 233,
        'startColumn' => 9,
        'endColumn' => 38,
      ),
      'ER_INCONS' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_INCONS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '21',
          'attributes' => 
          array (
            'startLine' => 238,
            'endLine' => 238,
            'startTokenPos' => 707,
            'startFilePos' => 7417,
            'endTokenPos' => 707,
            'endFilePos' => 7418,
          ),
        ),
        'docComment' => '/**
 * Zip archive inconsistent
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 238,
        'endLine' => 238,
        'startColumn' => 9,
        'endColumn' => 36,
      ),
      'ER_REMOVE' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_REMOVE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '22',
          'attributes' => 
          array (
            'startLine' => 243,
            'endLine' => 243,
            'startTokenPos' => 720,
            'startFilePos' => 7568,
            'endTokenPos' => 720,
            'endFilePos' => 7569,
          ),
        ),
        'docComment' => '/**
 * Can\'t remove file
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 243,
        'endLine' => 243,
        'startColumn' => 9,
        'endColumn' => 36,
      ),
      'ER_DELETED' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_DELETED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '23',
          'attributes' => 
          array (
            'startLine' => 248,
            'endLine' => 248,
            'startTokenPos' => 733,
            'startFilePos' => 7725,
            'endTokenPos' => 733,
            'endFilePos' => 7726,
          ),
        ),
        'docComment' => '/**
 * Entry has been deleted
 * @link https://php.net/manual/en/zip.constants.php
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 248,
        'endLine' => 248,
        'startColumn' => 9,
        'endColumn' => 37,
      ),
      'EM_NONE' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'EM_NONE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0',
          'attributes' => 
          array (
            'startLine' => 254,
            'endLine' => 254,
            'startTokenPos' => 746,
            'startFilePos' => 7899,
            'endTokenPos' => 746,
            'endFilePos' => 7899,
          ),
        ),
        'docComment' => '/**
 * No encryption
 * @link https://secure.php.net/manual/en/zip.constants.php
 * @since 7.2
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 254,
        'endLine' => 254,
        'startColumn' => 9,
        'endColumn' => 33,
      ),
      'EM_AES_128' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'EM_AES_128',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '257',
          'attributes' => 
          array (
            'startLine' => 260,
            'endLine' => 260,
            'startTokenPos' => 759,
            'startFilePos' => 8080,
            'endTokenPos' => 759,
            'endFilePos' => 8082,
          ),
        ),
        'docComment' => '/**
 * AES 128 encryption
 * @link https://secure.php.net/manual/en/zip.constants.php
 * @since 7.2
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 260,
        'endLine' => 260,
        'startColumn' => 9,
        'endColumn' => 38,
      ),
      'EM_AES_192' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'EM_AES_192',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '258',
          'attributes' => 
          array (
            'startLine' => 266,
            'endLine' => 266,
            'startTokenPos' => 772,
            'startFilePos' => 8263,
            'endTokenPos' => 772,
            'endFilePos' => 8265,
          ),
        ),
        'docComment' => '/**
 * AES 192 encryption
 * @link https://secure.php.net/manual/en/zip.constants.php
 * @since 7.2
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 266,
        'endLine' => 266,
        'startColumn' => 9,
        'endColumn' => 38,
      ),
      'EM_AES_256' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'EM_AES_256',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '259',
          'attributes' => 
          array (
            'startLine' => 272,
            'endLine' => 272,
            'startTokenPos' => 785,
            'startFilePos' => 8446,
            'endTokenPos' => 785,
            'endFilePos' => 8448,
          ),
        ),
        'docComment' => '/**
 * AES 256 encryption
 * @link https://secure.php.net/manual/en/zip.constants.php
 * @since 7.2
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 272,
        'endLine' => 272,
        'startColumn' => 9,
        'endColumn' => 38,
      ),
      'RDONLY' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'RDONLY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '16',
          'attributes' => 
          array (
            'startLine' => 278,
            'endLine' => 278,
            'startTokenPos' => 798,
            'startFilePos' => 8637,
            'endTokenPos' => 798,
            'endFilePos' => 8638,
          ),
        ),
        'docComment' => '/**
 * Open archive in read only mode
 * @link https://secure.php.net/manual/en/zip.constants.php
 * @since 7.4
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 278,
        'endLine' => 278,
        'startColumn' => 9,
        'endColumn' => 33,
      ),
      'FL_ENC_GUESS' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'FL_ENC_GUESS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0',
          'attributes' => 
          array (
            'startLine' => 284,
            'endLine' => 284,
            'startTokenPos' => 811,
            'startFilePos' => 8837,
            'endTokenPos' => 811,
            'endFilePos' => 8837,
          ),
        ),
        'docComment' => '/**
 * Guess string encoding (is default)
 * @link https://secure.php.net/manual/en/zip.constants.php
 * @since 7.0
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 284,
        'endLine' => 284,
        'startColumn' => 9,
        'endColumn' => 38,
      ),
      'FL_ENC_RAW' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'FL_ENC_RAW',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '64',
          'attributes' => 
          array (
            'startLine' => 290,
            'endLine' => 290,
            'startTokenPos' => 824,
            'startFilePos' => 9021,
            'endTokenPos' => 824,
            'endFilePos' => 9022,
          ),
        ),
        'docComment' => '/**
 * Get unmodified string
 * @link https://secure.php.net/manual/en/zip.constants.php
 * @since 7.0
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 290,
        'endLine' => 290,
        'startColumn' => 9,
        'endColumn' => 37,
      ),
      'FL_ENC_STRICT' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'FL_ENC_STRICT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '128',
          'attributes' => 
          array (
            'startLine' => 296,
            'endLine' => 296,
            'startTokenPos' => 837,
            'startFilePos' => 9217,
            'endTokenPos' => 837,
            'endFilePos' => 9219,
          ),
        ),
        'docComment' => '/**
 * Follow specification strictly
 * @link https://secure.php.net/manual/en/zip.constants.php
 * @since 7.0
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 296,
        'endLine' => 296,
        'startColumn' => 9,
        'endColumn' => 41,
      ),
      'FL_ENC_UTF_8' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'FL_ENC_UTF_8',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '2048',
          'attributes' => 
          array (
            'startLine' => 302,
            'endLine' => 302,
            'startTokenPos' => 850,
            'startFilePos' => 9407,
            'endTokenPos' => 850,
            'endFilePos' => 9410,
          ),
        ),
        'docComment' => '/**
 * String is UTF-8 encoded
 * @link https://secure.php.net/manual/en/zip.constants.php
 * @since 7.0
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 302,
        'endLine' => 302,
        'startColumn' => 9,
        'endColumn' => 41,
      ),
      'FL_ENC_CP437' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'FL_ENC_CP437',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '4096',
          'attributes' => 
          array (
            'startLine' => 308,
            'endLine' => 308,
            'startTokenPos' => 863,
            'startFilePos' => 9598,
            'endTokenPos' => 863,
            'endFilePos' => 9601,
          ),
        ),
        'docComment' => '/**
 * String is CP437 encoded
 * @link https://secure.php.net/manual/en/zip.constants.php
 * @since 7.0
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 308,
        'endLine' => 308,
        'startColumn' => 9,
        'endColumn' => 41,
      ),
      'CM_LZMA2' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'CM_LZMA2',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '33',
          'attributes' => 
          array (
            'startLine' => 314,
            'endLine' => 314,
            'startTokenPos' => 876,
            'startFilePos' => 9777,
            'endTokenPos' => 876,
            'endFilePos' => 9778,
          ),
        ),
        'docComment' => '/**
 * LZMA2 algorithm
 * @link https://secure.php.net/manual/en/zip.constants.php
 * @since 7.4
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 314,
        'endLine' => 314,
        'startColumn' => 9,
        'endColumn' => 35,
      ),
      'CM_XZ' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'CM_XZ',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '95',
          'attributes' => 
          array (
            'startLine' => 320,
            'endLine' => 320,
            'startTokenPos' => 889,
            'startFilePos' => 9948,
            'endTokenPos' => 889,
            'endFilePos' => 9949,
          ),
        ),
        'docComment' => '/**
 * XZ algorithm
 * @link https://secure.php.net/manual/en/zip.constants.php
 * @since 7.4
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 320,
        'endLine' => 320,
        'startColumn' => 9,
        'endColumn' => 32,
      ),
      'ER_ENCRNOTSUPP' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_ENCRNOTSUPP',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '24',
          'attributes' => 
          array (
            'startLine' => 326,
            'endLine' => 326,
            'startTokenPos' => 902,
            'startFilePos' => 10145,
            'endTokenPos' => 902,
            'endFilePos' => 10146,
          ),
        ),
        'docComment' => '/**
 * Encryption method not support
 * @link https://secure.php.net/manual/en/zip.constants.php
 * @since 7.4
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 326,
        'endLine' => 326,
        'startColumn' => 9,
        'endColumn' => 41,
      ),
      'ER_RDONLY' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_RDONLY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '25',
          'attributes' => 
          array (
            'startLine' => 332,
            'endLine' => 332,
            'startTokenPos' => 915,
            'startFilePos' => 10325,
            'endTokenPos' => 915,
            'endFilePos' => 10326,
          ),
        ),
        'docComment' => '/**
 * Read-only archive
 * @link https://secure.php.net/manual/en/zip.constants.php
 * @since 7.4
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 332,
        'endLine' => 332,
        'startColumn' => 9,
        'endColumn' => 36,
      ),
      'ER_NOPASSWD' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_NOPASSWD',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '26',
          'attributes' => 
          array (
            'startLine' => 338,
            'endLine' => 338,
            'startTokenPos' => 928,
            'startFilePos' => 10510,
            'endTokenPos' => 928,
            'endFilePos' => 10511,
          ),
        ),
        'docComment' => '/**
 * No password provided
 * @link https://secure.php.net/manual/en/zip.constants.php
 * @since 7.4
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 338,
        'endLine' => 338,
        'startColumn' => 9,
        'endColumn' => 38,
      ),
      'ER_WRONGPASSWD' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_WRONGPASSWD',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '27',
          'attributes' => 
          array (
            'startLine' => 344,
            'endLine' => 344,
            'startTokenPos' => 941,
            'startFilePos' => 10701,
            'endTokenPos' => 941,
            'endFilePos' => 10702,
          ),
        ),
        'docComment' => '/**
 * Wrong password provided
 * @link https://secure.php.net/manual/en/zip.constants.php
 * @since 7.4
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 344,
        'endLine' => 344,
        'startColumn' => 9,
        'endColumn' => 41,
      ),
      'ER_OPNOTSUPP' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_OPNOTSUPP',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '28',
          'attributes' => 
          array (
            'startLine' => 350,
            'endLine' => 350,
            'startTokenPos' => 954,
            'startFilePos' => 10890,
            'endTokenPos' => 954,
            'endFilePos' => 10891,
          ),
        ),
        'docComment' => '/**
 * Operation not supported
 * @link https://secure.php.net/manual/en/zip.constants.php
 * @since 7.4
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 350,
        'endLine' => 350,
        'startColumn' => 9,
        'endColumn' => 39,
      ),
      'ER_INUSE' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_INUSE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '29',
          'attributes' => 
          array (
            'startLine' => 356,
            'endLine' => 356,
            'startTokenPos' => 967,
            'startFilePos' => 11073,
            'endTokenPos' => 967,
            'endFilePos' => 11074,
          ),
        ),
        'docComment' => '/**
 * Resource still in use
 * @link https://secure.php.net/manual/en/zip.constants.php
 * @since 7.4
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 356,
        'endLine' => 356,
        'startColumn' => 9,
        'endColumn' => 35,
      ),
      'ER_TELL' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_TELL',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '30',
          'attributes' => 
          array (
            'startLine' => 362,
            'endLine' => 362,
            'startTokenPos' => 980,
            'startFilePos' => 11244,
            'endTokenPos' => 980,
            'endFilePos' => 11245,
          ),
        ),
        'docComment' => '/**
 * Tell error
 * @link https://secure.php.net/manual/en/zip.constants.php
 * @since 7.4
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 362,
        'endLine' => 362,
        'startColumn' => 9,
        'endColumn' => 34,
      ),
      'ER_COMPRESSED_DATA' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_COMPRESSED_DATA',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '31',
          'attributes' => 
          array (
            'startLine' => 368,
            'endLine' => 368,
            'startTokenPos' => 993,
            'startFilePos' => 11439,
            'endTokenPos' => 993,
            'endFilePos' => 11440,
          ),
        ),
        'docComment' => '/**
 * Compressed data invalid
 * @link https://secure.php.net/manual/en/zip.constants.php
 * @since 7.4
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 368,
        'endLine' => 368,
        'startColumn' => 9,
        'endColumn' => 45,
      ),
      'ER_CANCELLED' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_CANCELLED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '32',
          'attributes' => 
          array (
            'startLine' => 374,
            'endLine' => 374,
            'startTokenPos' => 1006,
            'startFilePos' => 11624,
            'endTokenPos' => 1006,
            'endFilePos' => 11625,
          ),
        ),
        'docComment' => '/**
 * Operation cancelled
 * @link https://secure.php.net/manual/en/zip.constants.php
 * @since 7.4
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 374,
        'endLine' => 374,
        'startColumn' => 9,
        'endColumn' => 39,
      ),
      'OPSYS_DOS' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'OPSYS_DOS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0',
          'attributes' => 
          array (
            'startLine' => 379,
            'endLine' => 379,
            'startTokenPos' => 1019,
            'startFilePos' => 11807,
            'endTokenPos' => 1019,
            'endFilePos' => 11807,
          ),
        ),
        'docComment' => '/**
 * @link https://www.php.net/manual/en/zip.constants.php#ziparchive.constants.opsys.default
 * @since 5.6
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 379,
        'endLine' => 379,
        'startColumn' => 9,
        'endColumn' => 35,
      ),
      'OPSYS_AMIGA' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'OPSYS_AMIGA',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1',
          'attributes' => 
          array (
            'startLine' => 384,
            'endLine' => 384,
            'startTokenPos' => 1032,
            'startFilePos' => 11991,
            'endTokenPos' => 1032,
            'endFilePos' => 11991,
          ),
        ),
        'docComment' => '/**
 * @link https://www.php.net/manual/en/zip.constants.php#ziparchive.constants.opsys.default
 * @since 5.6
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 384,
        'endLine' => 384,
        'startColumn' => 9,
        'endColumn' => 37,
      ),
      'OPSYS_OPENVMS' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'OPSYS_OPENVMS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '2',
          'attributes' => 
          array (
            'startLine' => 389,
            'endLine' => 389,
            'startTokenPos' => 1045,
            'startFilePos' => 12177,
            'endTokenPos' => 1045,
            'endFilePos' => 12177,
          ),
        ),
        'docComment' => '/**
 * @link https://www.php.net/manual/en/zip.constants.php#ziparchive.constants.opsys.default
 * @since 5.6
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 389,
        'endLine' => 389,
        'startColumn' => 9,
        'endColumn' => 39,
      ),
      'OPSYS_UNIX' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'OPSYS_UNIX',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '3',
          'attributes' => 
          array (
            'startLine' => 394,
            'endLine' => 394,
            'startTokenPos' => 1058,
            'startFilePos' => 12360,
            'endTokenPos' => 1058,
            'endFilePos' => 12360,
          ),
        ),
        'docComment' => '/**
 * @link https://www.php.net/manual/en/zip.constants.php#ziparchive.constants.opsys.default
 * @since 5.6
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 394,
        'endLine' => 394,
        'startColumn' => 9,
        'endColumn' => 36,
      ),
      'OPSYS_VM_CMS' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'OPSYS_VM_CMS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '4',
          'attributes' => 
          array (
            'startLine' => 399,
            'endLine' => 399,
            'startTokenPos' => 1071,
            'startFilePos' => 12545,
            'endTokenPos' => 1071,
            'endFilePos' => 12545,
          ),
        ),
        'docComment' => '/**
 * @link https://www.php.net/manual/en/zip.constants.php#ziparchive.constants.opsys.default
 * @since 5.6
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 399,
        'endLine' => 399,
        'startColumn' => 9,
        'endColumn' => 38,
      ),
      'OPSYS_ATARI_ST' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'OPSYS_ATARI_ST',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '5',
          'attributes' => 
          array (
            'startLine' => 404,
            'endLine' => 404,
            'startTokenPos' => 1084,
            'startFilePos' => 12732,
            'endTokenPos' => 1084,
            'endFilePos' => 12732,
          ),
        ),
        'docComment' => '/**
 * @link https://www.php.net/manual/en/zip.constants.php#ziparchive.constants.opsys.default
 * @since 5.6
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 404,
        'endLine' => 404,
        'startColumn' => 9,
        'endColumn' => 40,
      ),
      'OPSYS_OS_2' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'OPSYS_OS_2',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '6',
          'attributes' => 
          array (
            'startLine' => 409,
            'endLine' => 409,
            'startTokenPos' => 1097,
            'startFilePos' => 12915,
            'endTokenPos' => 1097,
            'endFilePos' => 12915,
          ),
        ),
        'docComment' => '/**
 * @link https://www.php.net/manual/en/zip.constants.php#ziparchive.constants.opsys.default
 * @since 5.6
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 409,
        'endLine' => 409,
        'startColumn' => 9,
        'endColumn' => 36,
      ),
      'OPSYS_MACINTOSH' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'OPSYS_MACINTOSH',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '7',
          'attributes' => 
          array (
            'startLine' => 414,
            'endLine' => 414,
            'startTokenPos' => 1110,
            'startFilePos' => 13103,
            'endTokenPos' => 1110,
            'endFilePos' => 13103,
          ),
        ),
        'docComment' => '/**
 * @link https://www.php.net/manual/en/zip.constants.php#ziparchive.constants.opsys.default
 * @since 5.6
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 414,
        'endLine' => 414,
        'startColumn' => 9,
        'endColumn' => 41,
      ),
      'OPSYS_Z_SYSTEM' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'OPSYS_Z_SYSTEM',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '8',
          'attributes' => 
          array (
            'startLine' => 419,
            'endLine' => 419,
            'startTokenPos' => 1123,
            'startFilePos' => 13290,
            'endTokenPos' => 1123,
            'endFilePos' => 13290,
          ),
        ),
        'docComment' => '/**
 * @link https://www.php.net/manual/en/zip.constants.php#ziparchive.constants.opsys.default
 * @since 5.6
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 419,
        'endLine' => 419,
        'startColumn' => 9,
        'endColumn' => 40,
      ),
      'OPSYS_WINDOWS_NTFS' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'OPSYS_WINDOWS_NTFS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '10',
          'attributes' => 
          array (
            'startLine' => 424,
            'endLine' => 424,
            'startTokenPos' => 1136,
            'startFilePos' => 13481,
            'endTokenPos' => 1136,
            'endFilePos' => 13482,
          ),
        ),
        'docComment' => '/**
 * @link https://www.php.net/manual/en/zip.constants.php#ziparchive.constants.opsys.default
 * @since 5.6
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 424,
        'endLine' => 424,
        'startColumn' => 9,
        'endColumn' => 45,
      ),
      'OPSYS_MVS' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'OPSYS_MVS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '11',
          'attributes' => 
          array (
            'startLine' => 429,
            'endLine' => 429,
            'startTokenPos' => 1149,
            'startFilePos' => 13664,
            'endTokenPos' => 1149,
            'endFilePos' => 13665,
          ),
        ),
        'docComment' => '/**
 * @link https://www.php.net/manual/en/zip.constants.php#ziparchive.constants.opsys.default
 * @since 5.6
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 429,
        'endLine' => 429,
        'startColumn' => 9,
        'endColumn' => 36,
      ),
      'OPSYS_VSE' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'OPSYS_VSE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '12',
          'attributes' => 
          array (
            'startLine' => 434,
            'endLine' => 434,
            'startTokenPos' => 1162,
            'startFilePos' => 13847,
            'endTokenPos' => 1162,
            'endFilePos' => 13848,
          ),
        ),
        'docComment' => '/**
 * @link https://www.php.net/manual/en/zip.constants.php#ziparchive.constants.opsys.default
 * @since 5.6
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 434,
        'endLine' => 434,
        'startColumn' => 9,
        'endColumn' => 36,
      ),
      'OPSYS_ACORN_RISC' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'OPSYS_ACORN_RISC',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '13',
          'attributes' => 
          array (
            'startLine' => 439,
            'endLine' => 439,
            'startTokenPos' => 1175,
            'startFilePos' => 14037,
            'endTokenPos' => 1175,
            'endFilePos' => 14038,
          ),
        ),
        'docComment' => '/**
 * @link https://www.php.net/manual/en/zip.constants.php#ziparchive.constants.opsys.default
 * @since 5.6
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 439,
        'endLine' => 439,
        'startColumn' => 9,
        'endColumn' => 43,
      ),
      'OPSYS_VFAT' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'OPSYS_VFAT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '14',
          'attributes' => 
          array (
            'startLine' => 444,
            'endLine' => 444,
            'startTokenPos' => 1188,
            'startFilePos' => 14221,
            'endTokenPos' => 1188,
            'endFilePos' => 14222,
          ),
        ),
        'docComment' => '/**
 * @link https://www.php.net/manual/en/zip.constants.php#ziparchive.constants.opsys.default
 * @since 5.6
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 444,
        'endLine' => 444,
        'startColumn' => 9,
        'endColumn' => 37,
      ),
      'OPSYS_ALTERNATE_MVS' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'OPSYS_ALTERNATE_MVS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '15',
          'attributes' => 
          array (
            'startLine' => 449,
            'endLine' => 449,
            'startTokenPos' => 1201,
            'startFilePos' => 14414,
            'endTokenPos' => 1201,
            'endFilePos' => 14415,
          ),
        ),
        'docComment' => '/**
 * @link https://www.php.net/manual/en/zip.constants.php#ziparchive.constants.opsys.default
 * @since 5.6
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 449,
        'endLine' => 449,
        'startColumn' => 9,
        'endColumn' => 46,
      ),
      'OPSYS_BEOS' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'OPSYS_BEOS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '16',
          'attributes' => 
          array (
            'startLine' => 454,
            'endLine' => 454,
            'startTokenPos' => 1214,
            'startFilePos' => 14598,
            'endTokenPos' => 1214,
            'endFilePos' => 14599,
          ),
        ),
        'docComment' => '/**
 * @link https://www.php.net/manual/en/zip.constants.php#ziparchive.constants.opsys.default
 * @since 5.6
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 454,
        'endLine' => 454,
        'startColumn' => 9,
        'endColumn' => 37,
      ),
      'OPSYS_TANDEM' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'OPSYS_TANDEM',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '17',
          'attributes' => 
          array (
            'startLine' => 459,
            'endLine' => 459,
            'startTokenPos' => 1227,
            'startFilePos' => 14784,
            'endTokenPos' => 1227,
            'endFilePos' => 14785,
          ),
        ),
        'docComment' => '/**
 * @link https://www.php.net/manual/en/zip.constants.php#ziparchive.constants.opsys.default
 * @since 5.6
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 459,
        'endLine' => 459,
        'startColumn' => 9,
        'endColumn' => 39,
      ),
      'OPSYS_OS_400' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'OPSYS_OS_400',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '18',
          'attributes' => 
          array (
            'startLine' => 464,
            'endLine' => 464,
            'startTokenPos' => 1240,
            'startFilePos' => 14970,
            'endTokenPos' => 1240,
            'endFilePos' => 14971,
          ),
        ),
        'docComment' => '/**
 * @link https://www.php.net/manual/en/zip.constants.php#ziparchive.constants.opsys.default
 * @since 5.6
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 464,
        'endLine' => 464,
        'startColumn' => 9,
        'endColumn' => 39,
      ),
      'OPSYS_OS_X' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'OPSYS_OS_X',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '19',
          'attributes' => 
          array (
            'startLine' => 469,
            'endLine' => 469,
            'startTokenPos' => 1253,
            'startFilePos' => 15154,
            'endTokenPos' => 1253,
            'endFilePos' => 15155,
          ),
        ),
        'docComment' => '/**
 * @link https://www.php.net/manual/en/zip.constants.php#ziparchive.constants.opsys.default
 * @since 5.6
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 469,
        'endLine' => 469,
        'startColumn' => 9,
        'endColumn' => 37,
      ),
      'OPSYS_CPM' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'OPSYS_CPM',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '9',
          'attributes' => 
          array (
            'startLine' => 473,
            'endLine' => 473,
            'startTokenPos' => 1266,
            'startFilePos' => 15315,
            'endTokenPos' => 1266,
            'endFilePos' => 15315,
          ),
        ),
        'docComment' => '/**
 * @link https://www.php.net/manual/en/zip.constants.php#ziparchive.constants.opsys.default
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 473,
        'endLine' => 473,
        'startColumn' => 9,
        'endColumn' => 35,
      ),
      'OPSYS_DEFAULT' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'OPSYS_DEFAULT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '3',
          'attributes' => 
          array (
            'startLine' => 478,
            'endLine' => 478,
            'startTokenPos' => 1279,
            'startFilePos' => 15501,
            'endTokenPos' => 1279,
            'endFilePos' => 15501,
          ),
        ),
        'docComment' => '/**
 * @link https://www.php.net/manual/en/zip.constants.php#ziparchive.constants.opsys.default
 * @since 5.6
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 478,
        'endLine' => 478,
        'startColumn' => 9,
        'endColumn' => 39,
      ),
      'FL_OPEN_FILE_NOW' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'FL_OPEN_FILE_NOW',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1073741824',
          'attributes' => 
          array (
            'startLine' => 479,
            'endLine' => 479,
            'startTokenPos' => 1290,
            'startFilePos' => 15544,
            'endTokenPos' => 1290,
            'endFilePos' => 15553,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 479,
        'endLine' => 479,
        'startColumn' => 9,
        'endColumn' => 51,
      ),
      'CM_ZSTD' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'CM_ZSTD',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '93',
          'attributes' => 
          array (
            'startLine' => 480,
            'endLine' => 480,
            'startTokenPos' => 1301,
            'startFilePos' => 15587,
            'endTokenPos' => 1301,
            'endFilePos' => 15588,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 480,
        'endLine' => 480,
        'startColumn' => 9,
        'endColumn' => 34,
      ),
      'ER_DATA_LENGTH' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_DATA_LENGTH',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '33',
          'attributes' => 
          array (
            'startLine' => 481,
            'endLine' => 481,
            'startTokenPos' => 1312,
            'startFilePos' => 15629,
            'endTokenPos' => 1312,
            'endFilePos' => 15630,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 481,
        'endLine' => 481,
        'startColumn' => 9,
        'endColumn' => 41,
      ),
      'ER_NOT_ALLOWED' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'ER_NOT_ALLOWED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '34',
          'attributes' => 
          array (
            'startLine' => 482,
            'endLine' => 482,
            'startTokenPos' => 1323,
            'startFilePos' => 15671,
            'endTokenPos' => 1323,
            'endFilePos' => 15672,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 482,
        'endLine' => 482,
        'startColumn' => 9,
        'endColumn' => 41,
      ),
      'AFL_RDONLY' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'AFL_RDONLY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '2',
          'attributes' => 
          array (
            'startLine' => 483,
            'endLine' => 483,
            'startTokenPos' => 1334,
            'startFilePos' => 15709,
            'endTokenPos' => 1334,
            'endFilePos' => 15709,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 483,
        'endLine' => 483,
        'startColumn' => 9,
        'endColumn' => 36,
      ),
      'AFL_IS_TORRENTZIP' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'AFL_IS_TORRENTZIP',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '4',
          'attributes' => 
          array (
            'startLine' => 484,
            'endLine' => 484,
            'startTokenPos' => 1345,
            'startFilePos' => 15753,
            'endTokenPos' => 1345,
            'endFilePos' => 15753,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 484,
        'endLine' => 484,
        'startColumn' => 9,
        'endColumn' => 43,
      ),
      'AFL_WANT_TORRENTZIP' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'AFL_WANT_TORRENTZIP',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '8',
          'attributes' => 
          array (
            'startLine' => 485,
            'endLine' => 485,
            'startTokenPos' => 1356,
            'startFilePos' => 15799,
            'endTokenPos' => 1356,
            'endFilePos' => 15799,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 485,
        'endLine' => 485,
        'startColumn' => 9,
        'endColumn' => 45,
      ),
      'AFL_CREATE_OR_KEEP_FILE_FOR_EMPTY_ARCHIVE' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'AFL_CREATE_OR_KEEP_FILE_FOR_EMPTY_ARCHIVE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '16',
          'attributes' => 
          array (
            'startLine' => 486,
            'endLine' => 486,
            'startTokenPos' => 1367,
            'startFilePos' => 15867,
            'endTokenPos' => 1367,
            'endFilePos' => 15868,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 486,
        'endLine' => 486,
        'startColumn' => 9,
        'endColumn' => 68,
      ),
      'LENGTH_TO_END' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'LENGTH_TO_END',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0',
          'attributes' => 
          array (
            'startLine' => 487,
            'endLine' => 487,
            'startTokenPos' => 1378,
            'startFilePos' => 15908,
            'endTokenPos' => 1378,
            'endFilePos' => 15908,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 487,
        'endLine' => 487,
        'startColumn' => 9,
        'endColumn' => 39,
      ),
      'LENGTH_UNCHECKED' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'LENGTH_UNCHECKED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '-2',
          'attributes' => 
          array (
            'startLine' => 488,
            'endLine' => 488,
            'startTokenPos' => 1389,
            'startFilePos' => 15951,
            'endTokenPos' => 1390,
            'endFilePos' => 15952,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 488,
        'endLine' => 488,
        'startColumn' => 9,
        'endColumn' => 43,
      ),
    ),
    'immediateProperties' => 
    array (
      'status' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'status',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Status of the Zip Archive
 * @var int
 */',
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '[\'8.1\' => \'int\']',
                'attributes' => 
                array (
                  'startLine' => 493,
                  'endLine' => 493,
                  'startTokenPos' => 1398,
                  'startFilePos' => 16098,
                  'endTokenPos' => 1404,
                  'endFilePos' => 16113,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 493,
                  'endLine' => 493,
                  'startTokenPos' => 1410,
                  'startFilePos' => 16125,
                  'endTokenPos' => 1410,
                  'endFilePos' => 16126,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 493,
        'endLine' => 494,
        'startColumn' => 9,
        'endColumn' => 27,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'statusSys' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'statusSys',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * System status of the Zip Archive
 * @var int
 */',
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '[\'8.1\' => \'int\']',
                'attributes' => 
                array (
                  'startLine' => 499,
                  'endLine' => 499,
                  'startTokenPos' => 1426,
                  'startFilePos' => 16308,
                  'endTokenPos' => 1432,
                  'endFilePos' => 16323,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 499,
                  'endLine' => 499,
                  'startTokenPos' => 1438,
                  'startFilePos' => 16335,
                  'endTokenPos' => 1438,
                  'endFilePos' => 16336,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 499,
        'endLine' => 500,
        'startColumn' => 9,
        'endColumn' => 30,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'numFiles' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'numFiles',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Number of files in archive
 * @var int
 */',
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '[\'8.1\' => \'int\']',
                'attributes' => 
                array (
                  'startLine' => 505,
                  'endLine' => 505,
                  'startTokenPos' => 1454,
                  'startFilePos' => 16515,
                  'endTokenPos' => 1460,
                  'endFilePos' => 16530,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 505,
                  'endLine' => 505,
                  'startTokenPos' => 1466,
                  'startFilePos' => 16542,
                  'endTokenPos' => 1466,
                  'endFilePos' => 16543,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 505,
        'endLine' => 506,
        'startColumn' => 9,
        'endColumn' => 29,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'filename' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'filename',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * File name in the file system
 * @var string
 */',
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '[\'8.1\' => \'string\']',
                'attributes' => 
                array (
                  'startLine' => 511,
                  'endLine' => 511,
                  'startTokenPos' => 1482,
                  'startFilePos' => 16726,
                  'endTokenPos' => 1488,
                  'endFilePos' => 16744,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 511,
                  'endLine' => 511,
                  'startTokenPos' => 1494,
                  'startFilePos' => 16756,
                  'endTokenPos' => 1494,
                  'endFilePos' => 16757,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 511,
        'endLine' => 512,
        'startColumn' => 9,
        'endColumn' => 32,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'comment' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'comment',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Comment for the archive
 * @var string
 */',
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '[\'8.1\' => \'string\']',
                'attributes' => 
                array (
                  'startLine' => 517,
                  'endLine' => 517,
                  'startTokenPos' => 1510,
                  'startFilePos' => 16938,
                  'endTokenPos' => 1516,
                  'endFilePos' => 16956,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 517,
                  'endLine' => 517,
                  'startTokenPos' => 1522,
                  'startFilePos' => 16968,
                  'endTokenPos' => 1522,
                  'endFilePos' => 16969,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 517,
        'endLine' => 518,
        'startColumn' => 9,
        'endColumn' => 31,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'lastId' => 
      array (
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'name' => 'lastId',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * @var int
 */',
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '[\'8.1\' => \'int\']',
                'attributes' => 
                array (
                  'startLine' => 522,
                  'endLine' => 522,
                  'startTokenPos' => 1538,
                  'startFilePos' => 17111,
                  'endTokenPos' => 1544,
                  'endFilePos' => 17126,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 522,
                  'endLine' => 522,
                  'startTokenPos' => 1550,
                  'startFilePos' => 17138,
                  'endTokenPos' => 1550,
                  'endFilePos' => 17139,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 522,
        'endLine' => 523,
        'startColumn' => 9,
        'endColumn' => 27,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      'open' => 
      array (
        'name' => 'open',
        'parameters' => 
        array (
          'filename' => 
          array (
            'name' => 'filename',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 602,
                      'endLine' => 602,
                      'startTokenPos' => 1577,
                      'startFilePos' => 19194,
                      'endTokenPos' => 1583,
                      'endFilePos' => 19212,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 602,
                      'endLine' => 602,
                      'startTokenPos' => 1589,
                      'startFilePos' => 19224,
                      'endTokenPos' => 1589,
                      'endFilePos' => 19225,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 602,
            'endLine' => 603,
            'startColumn' => 13,
            'endColumn' => 28,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 605,
                'endLine' => 605,
                'startTokenPos' => 1623,
                'startFilePos' => 19382,
                'endTokenPos' => 1623,
                'endFilePos' => 19385,
              ),
            ),
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
                      'name' => 'int',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 604,
                      'endLine' => 604,
                      'startTokenPos' => 1601,
                      'startFilePos' => 19325,
                      'endTokenPos' => 1607,
                      'endFilePos' => 19340,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 604,
                      'endLine' => 604,
                      'startTokenPos' => 1613,
                      'startFilePos' => 19352,
                      'endTokenPos' => 1613,
                      'endFilePos' => 19353,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 604,
            'endLine' => 605,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 1,
            'isOptional' => true,
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
                  'name' => 'int',
                  'isIdentifier' => true,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'bool',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.2.0, PECL zip &gt;= 1.1.0)<br/>
 * Open a ZIP file archive
 *
 * @link https://php.net/manual/en/ziparchive.open.php
 *
 * @param string $filename <p>
 * The file name of the ZIP archive to open.
 * </p>
 * @param int $flags [optional] <p>
 * The mode to use to open the archive.
 * </p>
 * <p>
 * <b>ZipArchive::OVERWRITE</b>
 * </p>
 *
 * @return int|bool <i>Error codes</i>
 * <p>
 * Returns <b>TRUE</b> on success, <b>FALSE</b> or the error code on error.
 * </p>
 * <p>
 * <b>ZipArchive::ER_EXISTS</b>
 * </p>
 * <p>
 * File already exists.
 * </p>
 * <p>
 * <b>ZipArchive::ER_INCONS</b>
 * </p>
 * <p>
 * Zip archive inconsistent.
 * </p>
 * <p>
 * <b>ZipArchive::ER_INVAL</b>
 * </p>
 * <p>
 * Invalid argument.
 * </p>
 * <p>
 * <b>ZipArchive::ER_MEMORY</b>
 * </p>
 * <p>
 * Malloc failure.
 * </p>
 * <p>
 * <b>ZipArchive::ER_NOENT</b>
 * </p>
 * <p>
 * No such file.
 * </p>
 * <p>
 * <b>ZipArchive::ER_NOZIP</b>
 * </p>
 * <p>
 * Not a zip archive.
 * </p>
 * <p>
 * <b>ZipArchive::ER_OPEN</b>
 * </p>
 * <p>
 * Can\'t open file.
 * </p>
 * <p>
 * <b>ZipArchive::ER_READ</b>
 * </p>
 * <p>
 * Read error.
 * </p>
 * <p>
 * <b>ZipArchive::ER_SEEK</b>
 * </p>
 * <p>
 * Seek error.
 * </p>
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 600,
        'endLine' => 608,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'close' => 
      array (
        'name' => 'close',
        'parameters' => 
        array (
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.2.0, PECL zip &gt;= 1.1.0)<br/>
 * Close the active archive (opened or newly created)
 * @link https://php.net/manual/en/ziparchive.close.php
 * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 616,
        'endLine' => 619,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'count' => 
      array (
        'name' => 'count',
        'parameters' => 
        array (
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 7 &gt;= 7.2.0, PECL zip &gt;= 1.15.0)<br/>
 * Counts the number of files in the archive.
 * @link https://www.php.net/manual/en/ziparchive.count.php
 * @return int
 * @since 7.2
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 628,
        'endLine' => 631,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'getStatusString' => 
      array (
        'name' => 'getStatusString',
        'parameters' => 
        array (
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Returns the status error message, system and/or zip messages
 * @link https://php.net/manual/en/ziparchive.getstatusstring.php
 * @return string|false a string with the status message on success or <b>FALSE</b> on failure.
 * @since 5.2
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 639,
        'endLine' => 642,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'addEmptyDir' => 
      array (
        'name' => 'addEmptyDir',
        'parameters' => 
        array (
          'dirname' => 
          array (
            'name' => 'dirname',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 656,
                      'endLine' => 656,
                      'startTokenPos' => 1715,
                      'startFilePos' => 21441,
                      'endTokenPos' => 1721,
                      'endFilePos' => 21459,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 656,
                      'endLine' => 656,
                      'startTokenPos' => 1727,
                      'startFilePos' => 21471,
                      'endTokenPos' => 1727,
                      'endFilePos' => 21472,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 656,
            'endLine' => 657,
            'startColumn' => 13,
            'endColumn' => 27,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 659,
                'endLine' => 659,
                'startTokenPos' => 1761,
                'startFilePos' => 21628,
                'endTokenPos' => 1761,
                'endFilePos' => 21628,
              ),
            ),
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 658,
                      'endLine' => 658,
                      'startTokenPos' => 1739,
                      'startFilePos' => 21571,
                      'endTokenPos' => 1745,
                      'endFilePos' => 21586,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 658,
                      'endLine' => 658,
                      'startTokenPos' => 1751,
                      'startFilePos' => 21598,
                      'endTokenPos' => 1751,
                      'endFilePos' => 21599,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 658,
            'endLine' => 659,
            'startColumn' => 13,
            'endColumn' => 26,
            'parameterIndex' => 1,
            'isOptional' => true,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.2.0, PECL zip &gt;= 1.8.0)<br/>
 * Add a new directory
 * @link https://php.net/manual/en/ziparchive.addemptydir.php
 * @param string $dirname <p>
 * The directory to add.
 * </p>
 * @param int $flags [optional] Set how to manage name encoding (ZipArchive::FL_ENC_*) and entry replacement (ZipArchive::FL_OVERWRITE)
 * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 654,
        'endLine' => 662,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'addFromString' => 
      array (
        'name' => 'addFromString',
        'parameters' => 
        array (
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 680,
                      'endLine' => 680,
                      'startTokenPos' => 1788,
                      'startFilePos' => 22550,
                      'endTokenPos' => 1794,
                      'endFilePos' => 22568,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 680,
                      'endLine' => 680,
                      'startTokenPos' => 1800,
                      'startFilePos' => 22580,
                      'endTokenPos' => 1800,
                      'endFilePos' => 22581,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 680,
            'endLine' => 681,
            'startColumn' => 13,
            'endColumn' => 24,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'content' => 
          array (
            'name' => 'content',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 682,
                      'endLine' => 682,
                      'startTokenPos' => 1812,
                      'startFilePos' => 22677,
                      'endTokenPos' => 1818,
                      'endFilePos' => 22695,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 682,
                      'endLine' => 682,
                      'startTokenPos' => 1824,
                      'startFilePos' => 22707,
                      'endTokenPos' => 1824,
                      'endFilePos' => 22708,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 682,
            'endLine' => 683,
            'startColumn' => 13,
            'endColumn' => 27,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '8192',
              'attributes' => 
              array (
                'startLine' => 685,
                'endLine' => 685,
                'startTokenPos' => 1858,
                'startFilePos' => 22864,
                'endTokenPos' => 1858,
                'endFilePos' => 22867,
              ),
            ),
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 684,
                      'endLine' => 684,
                      'startTokenPos' => 1836,
                      'startFilePos' => 22807,
                      'endTokenPos' => 1842,
                      'endFilePos' => 22822,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 684,
                      'endLine' => 684,
                      'startTokenPos' => 1848,
                      'startFilePos' => 22834,
                      'endTokenPos' => 1848,
                      'endFilePos' => 22835,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 684,
            'endLine' => 685,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 2,
            'isOptional' => true,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.2.0, PECL zip &gt;= 1.1.0)<br/>
 * Add a file to a ZIP archive using its contents
 * @link https://php.net/manual/en/ziparchive.addfromstring.php
 * @param string $name <p>
 * The name of the entry to create.
 * </p>
 * @param string $content <p>
 * The contents to use to create the entry. It is used in a binary
 * safe mode.
 * </p>
 * @param int $flags [optional] Set how to manage name encoding (ZipArchive::FL_ENC_*) and entry replacement (ZipArchive::FL_OVERWRITE)
 * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 678,
        'endLine' => 688,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'addFile' => 
      array (
        'name' => 'addFile',
        'parameters' => 
        array (
          'filepath' => 
          array (
            'name' => 'filepath',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 711,
                      'endLine' => 711,
                      'startTokenPos' => 1885,
                      'startFilePos' => 24092,
                      'endTokenPos' => 1891,
                      'endFilePos' => 24110,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 711,
                      'endLine' => 711,
                      'startTokenPos' => 1897,
                      'startFilePos' => 24122,
                      'endTokenPos' => 1897,
                      'endFilePos' => 24123,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 711,
            'endLine' => 712,
            'startColumn' => 13,
            'endColumn' => 28,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'entryname' => 
          array (
            'name' => 'entryname',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 714,
                'endLine' => 714,
                'startTokenPos' => 1931,
                'startFilePos' => 24290,
                'endTokenPos' => 1931,
                'endFilePos' => 24293,
              ),
            ),
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 713,
                      'endLine' => 713,
                      'startTokenPos' => 1909,
                      'startFilePos' => 24223,
                      'endTokenPos' => 1915,
                      'endFilePos' => 24241,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 713,
                      'endLine' => 713,
                      'startTokenPos' => 1921,
                      'startFilePos' => 24253,
                      'endTokenPos' => 1921,
                      'endFilePos' => 24254,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 713,
            'endLine' => 714,
            'startColumn' => 13,
            'endColumn' => 36,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'start' => 
          array (
            'name' => 'start',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 716,
                'endLine' => 716,
                'startTokenPos' => 1959,
                'startFilePos' => 24419,
                'endTokenPos' => 1959,
                'endFilePos' => 24419,
              ),
            ),
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 715,
                      'endLine' => 715,
                      'startTokenPos' => 1937,
                      'startFilePos' => 24362,
                      'endTokenPos' => 1943,
                      'endFilePos' => 24377,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 715,
                      'endLine' => 715,
                      'startTokenPos' => 1949,
                      'startFilePos' => 24389,
                      'endTokenPos' => 1949,
                      'endFilePos' => 24390,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 715,
            'endLine' => 716,
            'startColumn' => 13,
            'endColumn' => 26,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'length' => 
          array (
            'name' => 'length',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 718,
                'endLine' => 718,
                'startTokenPos' => 1987,
                'startFilePos' => 24546,
                'endTokenPos' => 1987,
                'endFilePos' => 24546,
              ),
            ),
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 717,
                      'endLine' => 717,
                      'startTokenPos' => 1965,
                      'startFilePos' => 24488,
                      'endTokenPos' => 1971,
                      'endFilePos' => 24503,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 717,
                      'endLine' => 717,
                      'startTokenPos' => 1977,
                      'startFilePos' => 24515,
                      'endTokenPos' => 1977,
                      'endFilePos' => 24516,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 717,
            'endLine' => 718,
            'startColumn' => 13,
            'endColumn' => 27,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '8192',
              'attributes' => 
              array (
                'startLine' => 720,
                'endLine' => 720,
                'startTokenPos' => 2015,
                'startFilePos' => 24672,
                'endTokenPos' => 2015,
                'endFilePos' => 24675,
              ),
            ),
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 719,
                      'endLine' => 719,
                      'startTokenPos' => 1993,
                      'startFilePos' => 24615,
                      'endTokenPos' => 1999,
                      'endFilePos' => 24630,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 719,
                      'endLine' => 719,
                      'startTokenPos' => 2005,
                      'startFilePos' => 24642,
                      'endTokenPos' => 2005,
                      'endFilePos' => 24643,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 719,
            'endLine' => 720,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 4,
            'isOptional' => true,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.2.0, PECL zip &gt;= 1.1.0)<br/>
 * Adds a file to a ZIP archive from the given path
 * @link https://php.net/manual/en/ziparchive.addfile.php
 * @param string $filepath <p>
 * The path to the file to add.
 * </p>
 * @param string $entryname [optional] <p>
 * If supplied, this is the local name inside the ZIP archive that will override the <i>filename</i>.
 * </p>
 * @param int $start [optional] <p>
 * This parameter is not used but is required to extend <b>ZipArchive</b>.
 * </p>
 * @param int $length [optional] <p>
 * This parameter is not used but is required to extend <b>ZipArchive</b>.
 * </p>
 * @param int $flags [optional] Set how to manage name encoding (ZipArchive::FL_ENC_*) and entry replacement (ZipArchive::FL_OVERWRITE)
 * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 709,
        'endLine' => 723,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'addGlob' => 
      array (
        'name' => 'addGlob',
        'parameters' => 
        array (
          'pattern' => 
          array (
            'name' => 'pattern',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 751,
                      'endLine' => 751,
                      'startTokenPos' => 2042,
                      'startFilePos' => 25817,
                      'endTokenPos' => 2048,
                      'endFilePos' => 25835,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 751,
                      'endLine' => 751,
                      'startTokenPos' => 2054,
                      'startFilePos' => 25847,
                      'endTokenPos' => 2054,
                      'endFilePos' => 25848,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 751,
            'endLine' => 752,
            'startColumn' => 13,
            'endColumn' => 27,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 754,
                'endLine' => 754,
                'startTokenPos' => 2088,
                'startFilePos' => 26004,
                'endTokenPos' => 2088,
                'endFilePos' => 26004,
              ),
            ),
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 753,
                      'endLine' => 753,
                      'startTokenPos' => 2066,
                      'startFilePos' => 25947,
                      'endTokenPos' => 2072,
                      'endFilePos' => 25962,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 753,
                      'endLine' => 753,
                      'startTokenPos' => 2078,
                      'startFilePos' => 25974,
                      'endTokenPos' => 2078,
                      'endFilePos' => 25975,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 753,
            'endLine' => 754,
            'startColumn' => 13,
            'endColumn' => 26,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 755,
                'endLine' => 755,
                'startTokenPos' => 2097,
                'startFilePos' => 26036,
                'endTokenPos' => 2098,
                'endFilePos' => 26037,
              ),
            ),
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
            'startLine' => 755,
            'endLine' => 755,
            'startColumn' => 13,
            'endColumn' => 31,
            'parameterIndex' => 2,
            'isOptional' => true,
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
                  'name' => 'array',
                  'isIdentifier' => true,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'false',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.3.0, PECL zip &gt;= 1.9.0)<br/>
 * Add files from a directory by glob pattern
 * @link https://php.net/manual/en/ziparchive.addglob.php
 * @param string $pattern <p>
 * A <b>glob</b> pattern against which files will be matched.
 * </p>
 * @param int $flags [optional] <p>
 * A bit mask of glob() flags.
 * </p>
 * @param array $options [optional] <p>
 * An associative array of options. Available options are:
 * </p>
 * <p>
 * "add_path"
 * </p>
 * <p>
 * Prefix to prepend when translating to the local path of the file within
 * the archive. This is applied after any remove operations defined by the
 * "remove_path" or "remove_all_path"
 * options.
 * </p>
 * @return array|false
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 749,
        'endLine' => 758,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'addPattern' => 
      array (
        'name' => 'addPattern',
        'parameters' => 
        array (
          'pattern' => 
          array (
            'name' => 'pattern',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 777,
                      'endLine' => 777,
                      'startTokenPos' => 2127,
                      'startFilePos' => 26938,
                      'endTokenPos' => 2133,
                      'endFilePos' => 26956,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 777,
                      'endLine' => 777,
                      'startTokenPos' => 2139,
                      'startFilePos' => 26968,
                      'endTokenPos' => 2139,
                      'endFilePos' => 26969,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 777,
            'endLine' => 778,
            'startColumn' => 13,
            'endColumn' => 27,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'path' => 
          array (
            'name' => 'path',
            'default' => 
            array (
              'code' => '\'.\'',
              'attributes' => 
              array (
                'startLine' => 780,
                'endLine' => 780,
                'startTokenPos' => 2173,
                'startFilePos' => 27130,
                'endTokenPos' => 2173,
                'endFilePos' => 27132,
              ),
            ),
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 779,
                      'endLine' => 779,
                      'startTokenPos' => 2151,
                      'startFilePos' => 27068,
                      'endTokenPos' => 2157,
                      'endFilePos' => 27086,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 779,
                      'endLine' => 779,
                      'startTokenPos' => 2163,
                      'startFilePos' => 27098,
                      'endTokenPos' => 2163,
                      'endFilePos' => 27099,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 779,
            'endLine' => 780,
            'startColumn' => 13,
            'endColumn' => 30,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 781,
                'endLine' => 781,
                'startTokenPos' => 2182,
                'startFilePos' => 27164,
                'endTokenPos' => 2183,
                'endFilePos' => 27165,
              ),
            ),
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
            'startLine' => 781,
            'endLine' => 781,
            'startColumn' => 13,
            'endColumn' => 31,
            'parameterIndex' => 2,
            'isOptional' => true,
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
                  'name' => 'array',
                  'isIdentifier' => true,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'false',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.3.0, PECL zip &gt;= 1.9.0)<br/>
 * Add files from a directory by PCRE pattern
 * @link https://php.net/manual/en/ziparchive.addpattern.php
 * @param string $pattern <p>
 * A PCRE pattern against which files will be matched.
 * </p>
 * @param string $path [optional] <p>
 * The directory that will be scanned. Defaults to the current working directory.
 * </p>
 * @param array $options [optional] <p>
 * An associative array of options accepted by <b>ZipArchive::addGlob</b>.
 * </p>
 * @return array|false
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 775,
        'endLine' => 784,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'renameIndex' => 
      array (
        'name' => 'renameIndex',
        'parameters' => 
        array (
          'index' => 
          array (
            'name' => 'index',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 800,
                      'endLine' => 800,
                      'startTokenPos' => 2212,
                      'startFilePos' => 27857,
                      'endTokenPos' => 2218,
                      'endFilePos' => 27872,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 800,
                      'endLine' => 800,
                      'startTokenPos' => 2224,
                      'startFilePos' => 27884,
                      'endTokenPos' => 2224,
                      'endFilePos' => 27885,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 800,
            'endLine' => 801,
            'startColumn' => 13,
            'endColumn' => 22,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'new_name' => 
          array (
            'name' => 'new_name',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 802,
                      'endLine' => 802,
                      'startTokenPos' => 2236,
                      'startFilePos' => 27979,
                      'endTokenPos' => 2242,
                      'endFilePos' => 27997,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 802,
                      'endLine' => 802,
                      'startTokenPos' => 2248,
                      'startFilePos' => 28009,
                      'endTokenPos' => 2248,
                      'endFilePos' => 28010,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 802,
            'endLine' => 803,
            'startColumn' => 13,
            'endColumn' => 28,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.2.0, PECL zip &gt;= 1.5.0)<br/>
 * Renames an entry defined by its index
 * @link https://php.net/manual/en/ziparchive.renameindex.php
 * @param int $index <p>
 * Index of the entry to rename.
 * </p>
 * @param string $new_name <p>
 * New name.
 * </p>
 * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 798,
        'endLine' => 806,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'renameName' => 
      array (
        'name' => 'renameName',
        'parameters' => 
        array (
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 822,
                      'endLine' => 822,
                      'startTokenPos' => 2281,
                      'startFilePos' => 28724,
                      'endTokenPos' => 2287,
                      'endFilePos' => 28742,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 822,
                      'endLine' => 822,
                      'startTokenPos' => 2293,
                      'startFilePos' => 28754,
                      'endTokenPos' => 2293,
                      'endFilePos' => 28755,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 822,
            'endLine' => 823,
            'startColumn' => 13,
            'endColumn' => 24,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'new_name' => 
          array (
            'name' => 'new_name',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 824,
                      'endLine' => 824,
                      'startTokenPos' => 2305,
                      'startFilePos' => 28851,
                      'endTokenPos' => 2311,
                      'endFilePos' => 28869,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 824,
                      'endLine' => 824,
                      'startTokenPos' => 2317,
                      'startFilePos' => 28881,
                      'endTokenPos' => 2317,
                      'endFilePos' => 28882,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 824,
            'endLine' => 825,
            'startColumn' => 13,
            'endColumn' => 28,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.2.0, PECL zip &gt;= 1.5.0)<br/>
 * Renames an entry defined by its name
 * @link https://php.net/manual/en/ziparchive.renamename.php
 * @param string $name <p>
 * Name of the entry to rename.
 * </p>
 * @param string $new_name <p>
 * New name.
 * </p>
 * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 820,
        'endLine' => 828,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'setArchiveComment' => 
      array (
        'name' => 'setArchiveComment',
        'parameters' => 
        array (
          'comment' => 
          array (
            'name' => 'comment',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 841,
                      'endLine' => 841,
                      'startTokenPos' => 2350,
                      'startFilePos' => 29533,
                      'endTokenPos' => 2356,
                      'endFilePos' => 29551,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 841,
                      'endLine' => 841,
                      'startTokenPos' => 2362,
                      'startFilePos' => 29563,
                      'endTokenPos' => 2362,
                      'endFilePos' => 29564,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 841,
            'endLine' => 842,
            'startColumn' => 13,
            'endColumn' => 27,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.2.0, PECL zip &gt;= 1.4.0)<br/>
 * Set the comment of a ZIP archive
 * @link https://php.net/manual/en/ziparchive.setarchivecomment.php
 * @param string $comment <p>
 * The contents of the comment.
 * </p>
 * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 839,
        'endLine' => 845,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'getArchiveComment' => 
      array (
        'name' => 'getArchiveComment',
        'parameters' => 
        array (
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 860,
                'endLine' => 860,
                'startTokenPos' => 2417,
                'startFilePos' => 30363,
                'endTokenPos' => 2417,
                'endFilePos' => 30366,
              ),
            ),
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
                      'name' => 'int',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 859,
                      'endLine' => 859,
                      'startTokenPos' => 2395,
                      'startFilePos' => 30306,
                      'endTokenPos' => 2401,
                      'endFilePos' => 30321,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 859,
                      'endLine' => 859,
                      'startTokenPos' => 2407,
                      'startFilePos' => 30333,
                      'endTokenPos' => 2407,
                      'endFilePos' => 30334,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 859,
            'endLine' => 860,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 0,
            'isOptional' => true,
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
                  'name' => 'false',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.2.0, PECL zip &gt;= 1.1.0)<br/>
 * Returns the Zip archive comment
 * @link https://php.net/manual/en/ziparchive.getarchivecomment.php
 * @param int $flags [optional] <p>
 * If flags is set to <b>ZipArchive::FL_UNCHANGED</b>, the original unchanged
 * comment is returned.
 * </p>
 * @return string|false the Zip archive comment or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 857,
        'endLine' => 863,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'setCommentIndex' => 
      array (
        'name' => 'setCommentIndex',
        'parameters' => 
        array (
          'index' => 
          array (
            'name' => 'index',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 879,
                      'endLine' => 879,
                      'startTokenPos' => 2446,
                      'startFilePos' => 31086,
                      'endTokenPos' => 2452,
                      'endFilePos' => 31101,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 879,
                      'endLine' => 879,
                      'startTokenPos' => 2458,
                      'startFilePos' => 31113,
                      'endTokenPos' => 2458,
                      'endFilePos' => 31114,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 879,
            'endLine' => 880,
            'startColumn' => 13,
            'endColumn' => 22,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'comment' => 
          array (
            'name' => 'comment',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 881,
                      'endLine' => 881,
                      'startTokenPos' => 2470,
                      'startFilePos' => 31208,
                      'endTokenPos' => 2476,
                      'endFilePos' => 31226,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 881,
                      'endLine' => 881,
                      'startTokenPos' => 2482,
                      'startFilePos' => 31238,
                      'endTokenPos' => 2482,
                      'endFilePos' => 31239,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 881,
            'endLine' => 882,
            'startColumn' => 13,
            'endColumn' => 27,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.2.0, PECL zip &gt;= 1.4.0)<br/>
 * Set the comment of an entry defined by its index
 * @link https://php.net/manual/en/ziparchive.setcommentindex.php
 * @param int $index <p>
 * Index of the entry.
 * </p>
 * @param string $comment <p>
 * The contents of the comment.
 * </p>
 * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 877,
        'endLine' => 885,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'setCommentName' => 
      array (
        'name' => 'setCommentName',
        'parameters' => 
        array (
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 901,
                      'endLine' => 901,
                      'startTokenPos' => 2515,
                      'startFilePos' => 31979,
                      'endTokenPos' => 2521,
                      'endFilePos' => 31997,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 901,
                      'endLine' => 901,
                      'startTokenPos' => 2527,
                      'startFilePos' => 32009,
                      'endTokenPos' => 2527,
                      'endFilePos' => 32010,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 901,
            'endLine' => 902,
            'startColumn' => 13,
            'endColumn' => 24,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'comment' => 
          array (
            'name' => 'comment',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 903,
                      'endLine' => 903,
                      'startTokenPos' => 2539,
                      'startFilePos' => 32106,
                      'endTokenPos' => 2545,
                      'endFilePos' => 32124,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 903,
                      'endLine' => 903,
                      'startTokenPos' => 2551,
                      'startFilePos' => 32136,
                      'endTokenPos' => 2551,
                      'endFilePos' => 32137,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 903,
            'endLine' => 904,
            'startColumn' => 13,
            'endColumn' => 27,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.2.0, PECL zip &gt;= 1.4.0)<br/>
 * Set the comment of an entry defined by its name
 * @link https://php.net/manual/en/ziparchive.setcommentname.php
 * @param string $name <p>
 * Name of the entry.
 * </p>
 * @param string $comment <p>
 * The contents of the comment.
 * </p>
 * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 899,
        'endLine' => 907,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'setCompressionIndex' => 
      array (
        'name' => 'setCompressionIndex',
        'parameters' => 
        array (
          'index' => 
          array (
            'name' => 'index',
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
            'startLine' => 919,
            'endLine' => 919,
            'startColumn' => 45,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'method' => 
          array (
            'name' => 'method',
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
            'startLine' => 919,
            'endLine' => 919,
            'startColumn' => 57,
            'endColumn' => 67,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'compflags' => 
          array (
            'name' => 'compflags',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 919,
                'endLine' => 919,
                'startTokenPos' => 2596,
                'startFilePos' => 32920,
                'endTokenPos' => 2596,
                'endFilePos' => 32920,
              ),
            ),
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
            'startLine' => 919,
            'endLine' => 919,
            'startColumn' => 70,
            'endColumn' => 87,
            'parameterIndex' => 2,
            'isOptional' => true,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Set the compression method of an entry defined by its index
 * @link https://php.net/manual/en/ziparchive.setcompressionindex.php
 * @param int $index Index of the entry.
 * @param int $method The compression method. Either ZipArchive::CM_DEFAULT, ZipArchive::CM_STORE or ZipArchive::CM_DEFLATE.
 * @param int $compflags [optional] Compression flags. Currently unused.
 * @return bool Returns TRUE on success or FALSE on failure.
 * @since 7.0
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 918,
        'endLine' => 921,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'setCompressionName' => 
      array (
        'name' => 'setCompressionName',
        'parameters' => 
        array (
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
            'startLine' => 933,
            'endLine' => 933,
            'startColumn' => 44,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'method' => 
          array (
            'name' => 'method',
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
            'startLine' => 933,
            'endLine' => 933,
            'startColumn' => 58,
            'endColumn' => 68,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'compflags' => 
          array (
            'name' => 'compflags',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 933,
                'endLine' => 933,
                'startTokenPos' => 2634,
                'startFilePos' => 33665,
                'endTokenPos' => 2634,
                'endFilePos' => 33665,
              ),
            ),
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
            'startLine' => 933,
            'endLine' => 933,
            'startColumn' => 71,
            'endColumn' => 88,
            'parameterIndex' => 2,
            'isOptional' => true,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Set the compression method of an entry defined by its name
 * https://secure.php.net/manual/en/ziparchive.setcompressionname.php
 * @param string $name Name of the entry.
 * @param int $method The compression method. Either ZipArchive::CM_DEFAULT, ZipArchive::CM_STORE or ZipArchive::CM_DEFLATE.
 * @param int $compflags [optional] Compression flags. Currently unused.
 * @return bool Returns TRUE on success or FALSE on failure.
 * @since 7.0
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 932,
        'endLine' => 935,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'setEncryptionIndex' => 
      array (
        'name' => 'setEncryptionIndex',
        'parameters' => 
        array (
          'index' => 
          array (
            'name' => 'index',
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
            'startLine' => 947,
            'endLine' => 947,
            'startColumn' => 44,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'method' => 
          array (
            'name' => 'method',
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
            'startLine' => 947,
            'endLine' => 947,
            'startColumn' => 56,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'password' => 
          array (
            'name' => 'password',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 947,
                'endLine' => 947,
                'startTokenPos' => 2673,
                'startFilePos' => 34393,
                'endTokenPos' => 2673,
                'endFilePos' => 34396,
              ),
            ),
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
            'startLine' => 947,
            'endLine' => 947,
            'startColumn' => 69,
            'endColumn' => 92,
            'parameterIndex' => 2,
            'isOptional' => true,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Set the encryption method of an entry defined by its index
 * @link https://php.net/manual/en/ziparchive.setencryptionindex.php
 * @param int $index Index of the entry.
 * @param int $method The encryption method defined by one of the ZipArchive::EM_ constants.
 * @param string|null $password [optional] Optional password, default used when missing.
 * @return bool Returns TRUE on success or FALSE on failure.
 * @since 7.2
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 946,
        'endLine' => 949,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'setEncryptionName' => 
      array (
        'name' => 'setEncryptionName',
        'parameters' => 
        array (
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
            'startLine' => 961,
            'endLine' => 961,
            'startColumn' => 43,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'method' => 
          array (
            'name' => 'method',
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
            'startLine' => 961,
            'endLine' => 961,
            'startColumn' => 57,
            'endColumn' => 67,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'password' => 
          array (
            'name' => 'password',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 961,
                'endLine' => 961,
                'startTokenPos' => 2712,
                'startFilePos' => 35124,
                'endTokenPos' => 2712,
                'endFilePos' => 35127,
              ),
            ),
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
            'startLine' => 961,
            'endLine' => 961,
            'startColumn' => 70,
            'endColumn' => 93,
            'parameterIndex' => 2,
            'isOptional' => true,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Set the encryption method of an entry defined by its name
 * @link https://php.net/manual/en/ziparchive.setencryptionname.php
 * @param string $name Name of the entry.
 * @param int $method The encryption method defined by one of the ZipArchive::EM_ constants.
 * @param string|null $password [optional] Optional password, default used when missing.
 * @return bool Returns TRUE on success or FALSE on failure.
 * @since 7.2
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 960,
        'endLine' => 963,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'setPassword' => 
      array (
        'name' => 'setPassword',
        'parameters' => 
        array (
          'password' => 
          array (
            'name' => 'password',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 972,
                      'endLine' => 972,
                      'startTokenPos' => 2738,
                      'startFilePos' => 35503,
                      'endTokenPos' => 2744,
                      'endFilePos' => 35521,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 972,
                      'endLine' => 972,
                      'startTokenPos' => 2750,
                      'startFilePos' => 35533,
                      'endTokenPos' => 2750,
                      'endFilePos' => 35534,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 972,
            'endLine' => 973,
            'startColumn' => 13,
            'endColumn' => 28,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.6.0, PECL zip &gt;= 1.12.0)<br/>
 * @param string $password
 * @return bool
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 970,
        'endLine' => 976,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'getCommentIndex' => 
      array (
        'name' => 'getCommentIndex',
        'parameters' => 
        array (
          'index' => 
          array (
            'name' => 'index',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 993,
                      'endLine' => 993,
                      'startTokenPos' => 2783,
                      'startFilePos' => 36373,
                      'endTokenPos' => 2789,
                      'endFilePos' => 36388,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 993,
                      'endLine' => 993,
                      'startTokenPos' => 2795,
                      'startFilePos' => 36400,
                      'endTokenPos' => 2795,
                      'endFilePos' => 36401,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 993,
            'endLine' => 994,
            'startColumn' => 13,
            'endColumn' => 22,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 996,
                'endLine' => 996,
                'startTokenPos' => 2829,
                'startFilePos' => 36552,
                'endTokenPos' => 2829,
                'endFilePos' => 36555,
              ),
            ),
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
                      'name' => 'int',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 995,
                      'endLine' => 995,
                      'startTokenPos' => 2807,
                      'startFilePos' => 36495,
                      'endTokenPos' => 2813,
                      'endFilePos' => 36510,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 995,
                      'endLine' => 995,
                      'startTokenPos' => 2819,
                      'startFilePos' => 36522,
                      'endTokenPos' => 2819,
                      'endFilePos' => 36523,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 995,
            'endLine' => 996,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 1,
            'isOptional' => true,
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
                  'name' => 'false',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.2.0, PECL zip &gt;= 1.4.0)<br/>
 * Returns the comment of an entry using the entry index
 * @link https://php.net/manual/en/ziparchive.getcommentindex.php
 * @param int $index <p>
 * Index of the entry
 * </p>
 * @param int $flags [optional] <p>
 * If flags is set to <b>ZipArchive::FL_UNCHANGED</b>, the original unchanged
 * comment is returned.
 * </p>
 * @return string|false the comment on success or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 991,
        'endLine' => 999,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'getCommentName' => 
      array (
        'name' => 'getCommentName',
        'parameters' => 
        array (
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 1016,
                      'endLine' => 1016,
                      'startTokenPos' => 2858,
                      'startFilePos' => 37369,
                      'endTokenPos' => 2864,
                      'endFilePos' => 37387,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1016,
                      'endLine' => 1016,
                      'startTokenPos' => 2870,
                      'startFilePos' => 37399,
                      'endTokenPos' => 2870,
                      'endFilePos' => 37400,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1016,
            'endLine' => 1017,
            'startColumn' => 13,
            'endColumn' => 24,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 1019,
                'endLine' => 1019,
                'startTokenPos' => 2904,
                'startFilePos' => 37553,
                'endTokenPos' => 2904,
                'endFilePos' => 37556,
              ),
            ),
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
                      'name' => 'int',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1018,
                      'endLine' => 1018,
                      'startTokenPos' => 2882,
                      'startFilePos' => 37496,
                      'endTokenPos' => 2888,
                      'endFilePos' => 37511,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1018,
                      'endLine' => 1018,
                      'startTokenPos' => 2894,
                      'startFilePos' => 37523,
                      'endTokenPos' => 2894,
                      'endFilePos' => 37524,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1018,
            'endLine' => 1019,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 1,
            'isOptional' => true,
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
                  'name' => 'false',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.2.0, PECL zip &gt;= 1.4.0)<br/>
 * Returns the comment of an entry using the entry name
 * @link https://php.net/manual/en/ziparchive.getcommentname.php
 * @param string $name <p>
 * Name of the entry
 * </p>
 * @param int $flags [optional] <p>
 * If flags is set to <b>ZipArchive::FL_UNCHANGED</b>, the original unchanged
 * comment is returned.
 * </p>
 * @return string|false the comment on success or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 1014,
        'endLine' => 1022,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'deleteIndex' => 
      array (
        'name' => 'deleteIndex',
        'parameters' => 
        array (
          'index' => 
          array (
            'name' => 'index',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1035,
                      'endLine' => 1035,
                      'startTokenPos' => 2933,
                      'startFilePos' => 38182,
                      'endTokenPos' => 2939,
                      'endFilePos' => 38197,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1035,
                      'endLine' => 1035,
                      'startTokenPos' => 2945,
                      'startFilePos' => 38209,
                      'endTokenPos' => 2945,
                      'endFilePos' => 38210,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1035,
            'endLine' => 1036,
            'startColumn' => 13,
            'endColumn' => 22,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.2.0, PECL zip &gt;= 1.5.0)<br/>
 * delete an entry in the archive using its index
 * @link https://php.net/manual/en/ziparchive.deleteindex.php
 * @param int $index <p>
 * Index of the entry to delete.
 * </p>
 * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 1033,
        'endLine' => 1039,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'deleteName' => 
      array (
        'name' => 'deleteName',
        'parameters' => 
        array (
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 1052,
                      'endLine' => 1052,
                      'startTokenPos' => 2978,
                      'startFilePos' => 38851,
                      'endTokenPos' => 2984,
                      'endFilePos' => 38869,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1052,
                      'endLine' => 1052,
                      'startTokenPos' => 2990,
                      'startFilePos' => 38881,
                      'endTokenPos' => 2990,
                      'endFilePos' => 38882,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1052,
            'endLine' => 1053,
            'startColumn' => 13,
            'endColumn' => 24,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.2.0, PECL zip &gt;= 1.5.0)<br/>
 * delete an entry in the archive using its name
 * @link https://php.net/manual/en/ziparchive.deletename.php
 * @param string $name <p>
 * Name of the entry to delete.
 * </p>
 * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 1050,
        'endLine' => 1056,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'statName' => 
      array (
        'name' => 'statName',
        'parameters' => 
        array (
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 1076,
                      'endLine' => 1076,
                      'startTokenPos' => 3023,
                      'startFilePos' => 40007,
                      'endTokenPos' => 3029,
                      'endFilePos' => 40025,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1076,
                      'endLine' => 1076,
                      'startTokenPos' => 3035,
                      'startFilePos' => 40037,
                      'endTokenPos' => 3035,
                      'endFilePos' => 40038,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1076,
            'endLine' => 1077,
            'startColumn' => 13,
            'endColumn' => 24,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 1079,
                'endLine' => 1079,
                'startTokenPos' => 3069,
                'startFilePos' => 40191,
                'endTokenPos' => 3069,
                'endFilePos' => 40194,
              ),
            ),
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
                      'name' => 'int',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1078,
                      'endLine' => 1078,
                      'startTokenPos' => 3047,
                      'startFilePos' => 40134,
                      'endTokenPos' => 3053,
                      'endFilePos' => 40149,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1078,
                      'endLine' => 1078,
                      'startTokenPos' => 3059,
                      'startFilePos' => 40161,
                      'endTokenPos' => 3059,
                      'endFilePos' => 40162,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1078,
            'endLine' => 1079,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 1,
            'isOptional' => true,
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
                  'name' => 'array',
                  'isIdentifier' => true,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'false',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.2.0, PECL zip &gt;= 1.5.0)<br/>
 * Get the details of an entry defined by its name.
 * @link https://php.net/manual/en/ziparchive.statname.php
 * @param string $name <p>
 * Name of the entry
 * </p>
 * @param int $flags [optional] <p>
 * The flags argument specifies how the name lookup should be done.
 * Also, <b>ZipArchive::FL_UNCHANGED</b> may be ORed to it to request
 * information about the original file in the archive,
 * ignoring any changes made.
 * <b>ZipArchive::FL_NOCASE</b>
 * </p>
 * @return array{name: string, index: int, crc: int, size: int, mtime: int, comp_size: int, comp_method: int, encryption_method: int}|false an array containing the entry details or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 1074,
        'endLine' => 1082,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'statIndex' => 
      array (
        'name' => 'statIndex',
        'parameters' => 
        array (
          'index' => 
          array (
            'name' => 'index',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1100,
                      'endLine' => 1100,
                      'startTokenPos' => 3098,
                      'startFilePos' => 41179,
                      'endTokenPos' => 3104,
                      'endFilePos' => 41194,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1100,
                      'endLine' => 1100,
                      'startTokenPos' => 3110,
                      'startFilePos' => 41206,
                      'endTokenPos' => 3110,
                      'endFilePos' => 41207,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1100,
            'endLine' => 1101,
            'startColumn' => 13,
            'endColumn' => 22,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 1103,
                'endLine' => 1103,
                'startTokenPos' => 3144,
                'startFilePos' => 41358,
                'endTokenPos' => 3144,
                'endFilePos' => 41361,
              ),
            ),
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
                      'name' => 'int',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1102,
                      'endLine' => 1102,
                      'startTokenPos' => 3122,
                      'startFilePos' => 41301,
                      'endTokenPos' => 3128,
                      'endFilePos' => 41316,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1102,
                      'endLine' => 1102,
                      'startTokenPos' => 3134,
                      'startFilePos' => 41328,
                      'endTokenPos' => 3134,
                      'endFilePos' => 41329,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1102,
            'endLine' => 1103,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 1,
            'isOptional' => true,
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
                  'name' => 'array',
                  'isIdentifier' => true,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'false',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.2.0, PECL zip &gt;= 1.1.0)<br/>
 * Get the details of an entry defined by its index.
 * @link https://php.net/manual/en/ziparchive.statindex.php
 * @param int $index <p>
 * Index of the entry
 * </p>
 * @param int $flags [optional] <p>
 * <b>ZipArchive::FL_UNCHANGED</b> may be ORed to it to request
 * information about the original file in the archive,
 * ignoring any changes made.
 * </p>
 * @return array{name: string, index: int, crc: int, size: int, mtime: int, comp_size: int, comp_method: int, encryption_method: int}|false an array containing the entry details or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 1098,
        'endLine' => 1106,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'locateName' => 
      array (
        'name' => 'locateName',
        'parameters' => 
        array (
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 1124,
                      'endLine' => 1124,
                      'startTokenPos' => 3173,
                      'startFilePos' => 42204,
                      'endTokenPos' => 3179,
                      'endFilePos' => 42222,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1124,
                      'endLine' => 1124,
                      'startTokenPos' => 3185,
                      'startFilePos' => 42234,
                      'endTokenPos' => 3185,
                      'endFilePos' => 42235,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1124,
            'endLine' => 1125,
            'startColumn' => 13,
            'endColumn' => 24,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 1127,
                'endLine' => 1127,
                'startTokenPos' => 3219,
                'startFilePos' => 42388,
                'endTokenPos' => 3219,
                'endFilePos' => 42391,
              ),
            ),
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
                      'name' => 'int',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1126,
                      'endLine' => 1126,
                      'startTokenPos' => 3197,
                      'startFilePos' => 42331,
                      'endTokenPos' => 3203,
                      'endFilePos' => 42346,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1126,
                      'endLine' => 1126,
                      'startTokenPos' => 3209,
                      'startFilePos' => 42358,
                      'endTokenPos' => 3209,
                      'endFilePos' => 42359,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1126,
            'endLine' => 1127,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 1,
            'isOptional' => true,
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
                  'name' => 'int',
                  'isIdentifier' => true,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'false',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.2.0, PECL zip &gt;= 1.5.0)<br/>
 * Returns the index of the entry in the archive
 * @link https://php.net/manual/en/ziparchive.locatename.php
 * @param string $name <p>
 * The name of the entry to look up
 * </p>
 * @param int $flags [optional] <p>
 * The flags are specified by ORing the following values,
 * or 0 for none of them.
 * <b>ZipArchive::FL_NOCASE</b>
 * </p>
 * @return int|false the index of the entry on success or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 1122,
        'endLine' => 1130,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'getNameIndex' => 
      array (
        'name' => 'getNameIndex',
        'parameters' => 
        array (
          'index' => 
          array (
            'name' => 'index',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1147,
                      'endLine' => 1147,
                      'startTokenPos' => 3248,
                      'startFilePos' => 43184,
                      'endTokenPos' => 3254,
                      'endFilePos' => 43199,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1147,
                      'endLine' => 1147,
                      'startTokenPos' => 3260,
                      'startFilePos' => 43211,
                      'endTokenPos' => 3260,
                      'endFilePos' => 43212,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1147,
            'endLine' => 1148,
            'startColumn' => 13,
            'endColumn' => 22,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 1150,
                'endLine' => 1150,
                'startTokenPos' => 3294,
                'startFilePos' => 43363,
                'endTokenPos' => 3294,
                'endFilePos' => 43366,
              ),
            ),
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
                      'name' => 'int',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1149,
                      'endLine' => 1149,
                      'startTokenPos' => 3272,
                      'startFilePos' => 43306,
                      'endTokenPos' => 3278,
                      'endFilePos' => 43321,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1149,
                      'endLine' => 1149,
                      'startTokenPos' => 3284,
                      'startFilePos' => 43333,
                      'endTokenPos' => 3284,
                      'endFilePos' => 43334,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1149,
            'endLine' => 1150,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 1,
            'isOptional' => true,
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
                  'name' => 'false',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.2.0, PECL zip &gt;= 1.5.0)<br/>
 * Returns the name of an entry using its index
 * @link https://php.net/manual/en/ziparchive.getnameindex.php
 * @param int $index <p>
 * Index of the entry.
 * </p>
 * @param int $flags [optional] <p>
 * If flags is set to <b>ZipArchive::FL_UNCHANGED</b>, the original unchanged
 * name is returned.
 * </p>
 * @return string|false the name on success or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 1145,
        'endLine' => 1153,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'unchangeArchive' => 
      array (
        'name' => 'unchangeArchive',
        'parameters' => 
        array (
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.2.0, PECL zip &gt;= 1.1.0)<br/>
 * Revert all global changes done in the archive.
 * @link https://php.net/manual/en/ziparchive.unchangearchive.php
 * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 1161,
        'endLine' => 1164,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'unchangeAll' => 
      array (
        'name' => 'unchangeAll',
        'parameters' => 
        array (
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.2.0, PECL zip &gt;= 1.1.0)<br/>
 * Undo all changes done in the archive
 * @link https://php.net/manual/en/ziparchive.unchangeall.php
 * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 1172,
        'endLine' => 1175,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'unchangeIndex' => 
      array (
        'name' => 'unchangeIndex',
        'parameters' => 
        array (
          'index' => 
          array (
            'name' => 'index',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1188,
                      'endLine' => 1188,
                      'startTokenPos' => 3365,
                      'startFilePos' => 44894,
                      'endTokenPos' => 3371,
                      'endFilePos' => 44909,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1188,
                      'endLine' => 1188,
                      'startTokenPos' => 3377,
                      'startFilePos' => 44921,
                      'endTokenPos' => 3377,
                      'endFilePos' => 44922,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1188,
            'endLine' => 1189,
            'startColumn' => 13,
            'endColumn' => 22,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.2.0, PECL zip &gt;= 1.1.0)<br/>
 * Revert all changes done to an entry at the given index
 * @link https://php.net/manual/en/ziparchive.unchangeindex.php
 * @param int $index <p>
 * Index of the entry.
 * </p>
 * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 1186,
        'endLine' => 1192,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'unchangeName' => 
      array (
        'name' => 'unchangeName',
        'parameters' => 
        array (
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 1205,
                      'endLine' => 1205,
                      'startTokenPos' => 3410,
                      'startFilePos' => 45568,
                      'endTokenPos' => 3416,
                      'endFilePos' => 45586,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1205,
                      'endLine' => 1205,
                      'startTokenPos' => 3422,
                      'startFilePos' => 45598,
                      'endTokenPos' => 3422,
                      'endFilePos' => 45599,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1205,
            'endLine' => 1206,
            'startColumn' => 13,
            'endColumn' => 24,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.2.0, PECL zip &gt;= 1.5.0)<br/>
 * Revert all changes done to an entry with the given name.
 * @link https://php.net/manual/en/ziparchive.unchangename.php
 * @param string $name <p>
 * Name of the entry.
 * </p>
 * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 1203,
        'endLine' => 1209,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'extractTo' => 
      array (
        'name' => 'extractTo',
        'parameters' => 
        array (
          'pathto' => 
          array (
            'name' => 'pathto',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 1226,
                      'endLine' => 1226,
                      'startTokenPos' => 3455,
                      'startFilePos' => 46416,
                      'endTokenPos' => 3461,
                      'endFilePos' => 46434,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1226,
                      'endLine' => 1226,
                      'startTokenPos' => 3467,
                      'startFilePos' => 46446,
                      'endTokenPos' => 3467,
                      'endFilePos' => 46447,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1226,
            'endLine' => 1227,
            'startColumn' => 13,
            'endColumn' => 26,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'files' => 
          array (
            'name' => 'files',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 1229,
                'endLine' => 1229,
                'startTokenPos' => 3505,
                'startFilePos' => 46630,
                'endTokenPos' => 3505,
                'endFilePos' => 46633,
              ),
            ),
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
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  2 => 
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'array|string|null\']',
                    'attributes' => 
                    array (
                      'startLine' => 1228,
                      'endLine' => 1228,
                      'startTokenPos' => 3479,
                      'startFilePos' => 46545,
                      'endTokenPos' => 3485,
                      'endFilePos' => 46574,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1228,
                      'endLine' => 1228,
                      'startTokenPos' => 3491,
                      'startFilePos' => 46586,
                      'endTokenPos' => 3491,
                      'endFilePos' => 46587,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1228,
            'endLine' => 1229,
            'startColumn' => 13,
            'endColumn' => 43,
            'parameterIndex' => 1,
            'isOptional' => true,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.2.0, PECL zip &gt;= 1.1.0)<br/>
 * Extract the archive contents
 * @link https://php.net/manual/en/ziparchive.extractto.php
 * @param string $pathto <p>
 * Location where to extract the files.
 * </p>
 * @param string[]|string|null $files [optional] <p>
 * The entries to extract. It accepts either a single entry name or
 * an array of names.
 * </p>
 * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 1224,
        'endLine' => 1232,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'getFromName' => 
      array (
        'name' => 'getFromName',
        'parameters' => 
        array (
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 1254,
                      'endLine' => 1254,
                      'startTokenPos' => 3532,
                      'startFilePos' => 47616,
                      'endTokenPos' => 3538,
                      'endFilePos' => 47634,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1254,
                      'endLine' => 1254,
                      'startTokenPos' => 3544,
                      'startFilePos' => 47646,
                      'endTokenPos' => 3544,
                      'endFilePos' => 47647,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1254,
            'endLine' => 1255,
            'startColumn' => 13,
            'endColumn' => 24,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'len' => 
          array (
            'name' => 'len',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 1257,
                'endLine' => 1257,
                'startTokenPos' => 3578,
                'startFilePos' => 47798,
                'endTokenPos' => 3578,
                'endFilePos' => 47798,
              ),
            ),
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1256,
                      'endLine' => 1256,
                      'startTokenPos' => 3556,
                      'startFilePos' => 47743,
                      'endTokenPos' => 3562,
                      'endFilePos' => 47758,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1256,
                      'endLine' => 1256,
                      'startTokenPos' => 3568,
                      'startFilePos' => 47770,
                      'endTokenPos' => 3568,
                      'endFilePos' => 47771,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1256,
            'endLine' => 1257,
            'startColumn' => 13,
            'endColumn' => 24,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 1259,
                'endLine' => 1259,
                'startTokenPos' => 3606,
                'startFilePos' => 47924,
                'endTokenPos' => 3606,
                'endFilePos' => 47927,
              ),
            ),
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
                      'name' => 'int',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1258,
                      'endLine' => 1258,
                      'startTokenPos' => 3584,
                      'startFilePos' => 47867,
                      'endTokenPos' => 3590,
                      'endFilePos' => 47882,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1258,
                      'endLine' => 1258,
                      'startTokenPos' => 3596,
                      'startFilePos' => 47894,
                      'endTokenPos' => 3596,
                      'endFilePos' => 47895,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1258,
            'endLine' => 1259,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 2,
            'isOptional' => true,
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
                  'name' => 'false',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.2.0, PECL zip &gt;= 1.1.0)<br/>
 * Returns the entry contents using its name
 * @link https://php.net/manual/en/ziparchive.getfromname.php
 * @param string $name <p>
 * Name of the entry
 * </p>
 * @param int $len [optional] <p>
 * The length to be read from the entry. If 0, then the
 * entire entry is read.
 * </p>
 * @param int $flags [optional] <p>
 * The flags to use to open the archive. the following values may
 * be ORed to it.
 * <b>ZipArchive::FL_UNCHANGED</b>
 * </p>
 * @return string|false the contents of the entry on success or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 1252,
        'endLine' => 1262,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'getFromIndex' => 
      array (
        'name' => 'getFromIndex',
        'parameters' => 
        array (
          'index' => 
          array (
            'name' => 'index',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1286,
                      'endLine' => 1286,
                      'startTokenPos' => 3635,
                      'startFilePos' => 48951,
                      'endTokenPos' => 3641,
                      'endFilePos' => 48966,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1286,
                      'endLine' => 1286,
                      'startTokenPos' => 3647,
                      'startFilePos' => 48978,
                      'endTokenPos' => 3647,
                      'endFilePos' => 48979,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1286,
            'endLine' => 1287,
            'startColumn' => 13,
            'endColumn' => 22,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'len' => 
          array (
            'name' => 'len',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 1289,
                'endLine' => 1289,
                'startTokenPos' => 3681,
                'startFilePos' => 49128,
                'endTokenPos' => 3681,
                'endFilePos' => 49128,
              ),
            ),
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1288,
                      'endLine' => 1288,
                      'startTokenPos' => 3659,
                      'startFilePos' => 49073,
                      'endTokenPos' => 3665,
                      'endFilePos' => 49088,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1288,
                      'endLine' => 1288,
                      'startTokenPos' => 3671,
                      'startFilePos' => 49100,
                      'endTokenPos' => 3671,
                      'endFilePos' => 49101,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1288,
            'endLine' => 1289,
            'startColumn' => 13,
            'endColumn' => 24,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 1291,
                'endLine' => 1291,
                'startTokenPos' => 3709,
                'startFilePos' => 49254,
                'endTokenPos' => 3709,
                'endFilePos' => 49257,
              ),
            ),
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
                      'name' => 'int',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1290,
                      'endLine' => 1290,
                      'startTokenPos' => 3687,
                      'startFilePos' => 49197,
                      'endTokenPos' => 3693,
                      'endFilePos' => 49212,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1290,
                      'endLine' => 1290,
                      'startTokenPos' => 3699,
                      'startFilePos' => 49224,
                      'endTokenPos' => 3699,
                      'endFilePos' => 49225,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1290,
            'endLine' => 1291,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 2,
            'isOptional' => true,
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
                  'name' => 'false',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.2.0, PECL zip &gt;= 1.3.0)<br/>
 * Returns the entry contents using its index
 * @link https://php.net/manual/en/ziparchive.getfromindex.php
 * @param int $index <p>
 * Index of the entry
 * </p>
 * @param int $len [optional] <p>
 * The length to be read from the entry. If 0, then the
 * entire entry is read.
 * </p>
 * @param int $flags [optional] <p>
 * The flags to use to open the archive. the following values may
 * be ORed to it.
 * </p>
 * <p>
 * <b>ZipArchive::FL_UNCHANGED</b>
 * </p>
 * @return string|false the contents of the entry on success or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 1284,
        'endLine' => 1294,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'getStream' => 
      array (
        'name' => 'getStream',
        'parameters' => 
        array (
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 1305,
                      'endLine' => 1305,
                      'startTokenPos' => 3734,
                      'startFilePos' => 49821,
                      'endTokenPos' => 3740,
                      'endFilePos' => 49839,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1305,
                      'endLine' => 1305,
                      'startTokenPos' => 3746,
                      'startFilePos' => 49851,
                      'endTokenPos' => 3746,
                      'endFilePos' => 49852,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1305,
            'endLine' => 1306,
            'startColumn' => 13,
            'endColumn' => 24,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * (PHP 5 &gt;= 5.2.0, PECL zip &gt;= 1.1.0)<br/>
 * Get a file handler to the entry defined by its name (read only).
 * @link https://php.net/manual/en/ziparchive.getstream.php
 * @param string $name <p>
 * The name of the entry to use.
 * </p>
 * @return resource|false a file pointer (resource) on success or <b>FALSE</b> on failure.
 */',
        'startLine' => 1304,
        'endLine' => 1309,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'getStreamIndex' => 
      array (
        'name' => 'getStreamIndex',
        'parameters' => 
        array (
          'index' => 
          array (
            'name' => 'index',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1323,
                      'endLine' => 1323,
                      'startTokenPos' => 3772,
                      'startFilePos' => 50586,
                      'endTokenPos' => 3778,
                      'endFilePos' => 50601,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1323,
                      'endLine' => 1323,
                      'startTokenPos' => 3784,
                      'startFilePos' => 50613,
                      'endTokenPos' => 3784,
                      'endFilePos' => 50614,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1323,
            'endLine' => 1324,
            'startColumn' => 13,
            'endColumn' => 22,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 1326,
                'endLine' => 1326,
                'startTokenPos' => 3818,
                'startFilePos' => 50765,
                'endTokenPos' => 3818,
                'endFilePos' => 50765,
              ),
            ),
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1325,
                      'endLine' => 1325,
                      'startTokenPos' => 3796,
                      'startFilePos' => 50708,
                      'endTokenPos' => 3802,
                      'endFilePos' => 50723,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1325,
                      'endLine' => 1325,
                      'startTokenPos' => 3808,
                      'startFilePos' => 50735,
                      'endTokenPos' => 3808,
                      'endFilePos' => 50736,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1325,
            'endLine' => 1326,
            'startColumn' => 13,
            'endColumn' => 26,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * (PHP 8 &gt;= 8.2.0, PECL zip &gt;= 1.20.0)<br/>
 * Get a file handler to the entry defined by its index (read only)
 * @link https://php.net/manual/en/ziparchive.getstreamindex.php
 * @param int $index <p>
 * Index of the entry
 * </p>
 * @param int $flags [optional] <p>
 * If flags is set to ZipArchive::FL_UNCHANGED, the original unchanged stream is returned.
 * </p>
 * @return resource|false a file pointer (resource) on success or <b>FALSE</b> on failure.
 */',
        'startLine' => 1322,
        'endLine' => 1329,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'setExternalAttributesName' => 
      array (
        'name' => 'setExternalAttributesName',
        'parameters' => 
        array (
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 1342,
                      'endLine' => 1342,
                      'startTokenPos' => 3842,
                      'startFilePos' => 51600,
                      'endTokenPos' => 3848,
                      'endFilePos' => 51618,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1342,
                      'endLine' => 1342,
                      'startTokenPos' => 3854,
                      'startFilePos' => 51630,
                      'endTokenPos' => 3854,
                      'endFilePos' => 51631,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1342,
            'endLine' => 1343,
            'startColumn' => 13,
            'endColumn' => 24,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'opsys' => 
          array (
            'name' => 'opsys',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1344,
                      'endLine' => 1344,
                      'startTokenPos' => 3866,
                      'startFilePos' => 51727,
                      'endTokenPos' => 3872,
                      'endFilePos' => 51742,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1344,
                      'endLine' => 1344,
                      'startTokenPos' => 3878,
                      'startFilePos' => 51754,
                      'endTokenPos' => 3878,
                      'endFilePos' => 51755,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1344,
            'endLine' => 1345,
            'startColumn' => 13,
            'endColumn' => 22,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'attr' => 
          array (
            'name' => 'attr',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1346,
                      'endLine' => 1346,
                      'startTokenPos' => 3890,
                      'startFilePos' => 51849,
                      'endTokenPos' => 3896,
                      'endFilePos' => 51864,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1346,
                      'endLine' => 1346,
                      'startTokenPos' => 3902,
                      'startFilePos' => 51876,
                      'endTokenPos' => 3902,
                      'endFilePos' => 51877,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1346,
            'endLine' => 1347,
            'startColumn' => 13,
            'endColumn' => 21,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 1349,
                'endLine' => 1349,
                'startTokenPos' => 3936,
                'startFilePos' => 52027,
                'endTokenPos' => 3936,
                'endFilePos' => 52030,
              ),
            ),
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
                      'name' => 'int',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1348,
                      'endLine' => 1348,
                      'startTokenPos' => 3914,
                      'startFilePos' => 51970,
                      'endTokenPos' => 3920,
                      'endFilePos' => 51985,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1348,
                      'endLine' => 1348,
                      'startTokenPos' => 3926,
                      'startFilePos' => 51997,
                      'endTokenPos' => 3926,
                      'endFilePos' => 51998,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1348,
            'endLine' => 1349,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 3,
            'isOptional' => true,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Set the external attributes of an entry defined by its name
 * @link https://www.php.net/manual/en/ziparchive.setexternalattributesname.php
 * @param string $name Name of the entry
 * @param int $opsys The operating system code defined by one of the ZipArchive::OPSYS_ constants.
 * @param int $attr The external attributes. Value depends on operating system.
 * @param int $flags [optional] Optional flags. Currently unused.
 * @return bool Returns <b>TRUE</b> on success or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 1340,
        'endLine' => 1352,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'getExternalAttributesName' => 
      array (
        'name' => 'getExternalAttributesName',
        'parameters' => 
        array (
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 1365,
                      'endLine' => 1365,
                      'startTokenPos' => 3963,
                      'startFilePos' => 52977,
                      'endTokenPos' => 3969,
                      'endFilePos' => 52995,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1365,
                      'endLine' => 1365,
                      'startTokenPos' => 3975,
                      'startFilePos' => 53007,
                      'endTokenPos' => 3975,
                      'endFilePos' => 53008,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1365,
            'endLine' => 1366,
            'startColumn' => 13,
            'endColumn' => 24,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'opsys' => 
          array (
            'name' => 'opsys',
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
            'byRef' => true,
            'isPromoted' => false,
            'attributes' => 
            array (
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1367,
                      'endLine' => 1367,
                      'startTokenPos' => 3987,
                      'startFilePos' => 53104,
                      'endTokenPos' => 3993,
                      'endFilePos' => 53119,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1367,
                      'endLine' => 1367,
                      'startTokenPos' => 3999,
                      'startFilePos' => 53131,
                      'endTokenPos' => 3999,
                      'endFilePos' => 53132,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1367,
            'endLine' => 1368,
            'startColumn' => 13,
            'endColumn' => 23,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'attr' => 
          array (
            'name' => 'attr',
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
            'byRef' => true,
            'isPromoted' => false,
            'attributes' => 
            array (
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1369,
                      'endLine' => 1369,
                      'startTokenPos' => 4012,
                      'startFilePos' => 53227,
                      'endTokenPos' => 4018,
                      'endFilePos' => 53242,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1369,
                      'endLine' => 1369,
                      'startTokenPos' => 4024,
                      'startFilePos' => 53254,
                      'endTokenPos' => 4024,
                      'endFilePos' => 53255,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1369,
            'endLine' => 1370,
            'startColumn' => 13,
            'endColumn' => 22,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 1372,
                'endLine' => 1372,
                'startTokenPos' => 4059,
                'startFilePos' => 53406,
                'endTokenPos' => 4059,
                'endFilePos' => 53409,
              ),
            ),
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
                      'name' => 'int',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1371,
                      'endLine' => 1371,
                      'startTokenPos' => 4037,
                      'startFilePos' => 53349,
                      'endTokenPos' => 4043,
                      'endFilePos' => 53364,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1371,
                      'endLine' => 1371,
                      'startTokenPos' => 4049,
                      'startFilePos' => 53376,
                      'endTokenPos' => 4049,
                      'endFilePos' => 53377,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1371,
            'endLine' => 1372,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 3,
            'isOptional' => true,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Retrieve the external attributes of an entry defined by its name
 * @link https://www.php.net/manual/en/ziparchive.getexternalattributesname.php
 * @param string $name Name of the entry
 * @param int &$opsys On success, receive the operating system code defined by one of the ZipArchive::OPSYS_ constants.
 * @param int &$attr On success, receive the external attributes. Value depends on operating system.
 * @param int $flags [optional] If flags is set to ZipArchive::FL_UNCHANGED, the original unchanged attributes are returned.
 * @return bool Returns <b>TRUE</b> on success or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 1363,
        'endLine' => 1375,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'setExternalAttributesIndex' => 
      array (
        'name' => 'setExternalAttributesIndex',
        'parameters' => 
        array (
          'index' => 
          array (
            'name' => 'index',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1388,
                      'endLine' => 1388,
                      'startTokenPos' => 4086,
                      'startFilePos' => 54253,
                      'endTokenPos' => 4092,
                      'endFilePos' => 54268,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1388,
                      'endLine' => 1388,
                      'startTokenPos' => 4098,
                      'startFilePos' => 54280,
                      'endTokenPos' => 4098,
                      'endFilePos' => 54281,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1388,
            'endLine' => 1389,
            'startColumn' => 13,
            'endColumn' => 22,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'opsys' => 
          array (
            'name' => 'opsys',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1390,
                      'endLine' => 1390,
                      'startTokenPos' => 4110,
                      'startFilePos' => 54375,
                      'endTokenPos' => 4116,
                      'endFilePos' => 54390,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1390,
                      'endLine' => 1390,
                      'startTokenPos' => 4122,
                      'startFilePos' => 54402,
                      'endTokenPos' => 4122,
                      'endFilePos' => 54403,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1390,
            'endLine' => 1391,
            'startColumn' => 13,
            'endColumn' => 22,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'attr' => 
          array (
            'name' => 'attr',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1392,
                      'endLine' => 1392,
                      'startTokenPos' => 4134,
                      'startFilePos' => 54497,
                      'endTokenPos' => 4140,
                      'endFilePos' => 54512,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1392,
                      'endLine' => 1392,
                      'startTokenPos' => 4146,
                      'startFilePos' => 54524,
                      'endTokenPos' => 4146,
                      'endFilePos' => 54525,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1392,
            'endLine' => 1393,
            'startColumn' => 13,
            'endColumn' => 21,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 1395,
                'endLine' => 1395,
                'startTokenPos' => 4180,
                'startFilePos' => 54675,
                'endTokenPos' => 4180,
                'endFilePos' => 54678,
              ),
            ),
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
                      'name' => 'int',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1394,
                      'endLine' => 1394,
                      'startTokenPos' => 4158,
                      'startFilePos' => 54618,
                      'endTokenPos' => 4164,
                      'endFilePos' => 54633,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1394,
                      'endLine' => 1394,
                      'startTokenPos' => 4170,
                      'startFilePos' => 54645,
                      'endTokenPos' => 4170,
                      'endFilePos' => 54646,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1394,
            'endLine' => 1395,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 3,
            'isOptional' => true,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Set the external attributes of an entry defined by its index
 * @link https://www.php.net/manual/en/ziparchive.setexternalattributesindex.php
 * @param int $index Index of the entry.
 * @param int $opsys The operating system code defined by one of the ZipArchive::OPSYS_ constants.
 * @param int $attr The external attributes. Value depends on operating system.
 * @param int $flags [optional] Optional flags. Currently unused.
 * @return bool Returns <b>TRUE</b> on success or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 1386,
        'endLine' => 1398,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'getExternalAttributesIndex' => 
      array (
        'name' => 'getExternalAttributesIndex',
        'parameters' => 
        array (
          'index' => 
          array (
            'name' => 'index',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1411,
                      'endLine' => 1411,
                      'startTokenPos' => 4207,
                      'startFilePos' => 55628,
                      'endTokenPos' => 4213,
                      'endFilePos' => 55643,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1411,
                      'endLine' => 1411,
                      'startTokenPos' => 4219,
                      'startFilePos' => 55655,
                      'endTokenPos' => 4219,
                      'endFilePos' => 55656,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1411,
            'endLine' => 1412,
            'startColumn' => 13,
            'endColumn' => 22,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'opsys' => 
          array (
            'name' => 'opsys',
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
            'byRef' => true,
            'isPromoted' => false,
            'attributes' => 
            array (
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1413,
                      'endLine' => 1413,
                      'startTokenPos' => 4231,
                      'startFilePos' => 55750,
                      'endTokenPos' => 4237,
                      'endFilePos' => 55765,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1413,
                      'endLine' => 1413,
                      'startTokenPos' => 4243,
                      'startFilePos' => 55777,
                      'endTokenPos' => 4243,
                      'endFilePos' => 55778,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1413,
            'endLine' => 1414,
            'startColumn' => 13,
            'endColumn' => 23,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'attr' => 
          array (
            'name' => 'attr',
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
            'byRef' => true,
            'isPromoted' => false,
            'attributes' => 
            array (
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1415,
                      'endLine' => 1415,
                      'startTokenPos' => 4256,
                      'startFilePos' => 55873,
                      'endTokenPos' => 4262,
                      'endFilePos' => 55888,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1415,
                      'endLine' => 1415,
                      'startTokenPos' => 4268,
                      'startFilePos' => 55900,
                      'endTokenPos' => 4268,
                      'endFilePos' => 55901,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1415,
            'endLine' => 1416,
            'startColumn' => 13,
            'endColumn' => 22,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 1418,
                'endLine' => 1418,
                'startTokenPos' => 4303,
                'startFilePos' => 56052,
                'endTokenPos' => 4303,
                'endFilePos' => 56055,
              ),
            ),
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
                      'name' => 'int',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1417,
                      'endLine' => 1417,
                      'startTokenPos' => 4281,
                      'startFilePos' => 55995,
                      'endTokenPos' => 4287,
                      'endFilePos' => 56010,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1417,
                      'endLine' => 1417,
                      'startTokenPos' => 4293,
                      'startFilePos' => 56022,
                      'endTokenPos' => 4293,
                      'endFilePos' => 56023,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1417,
            'endLine' => 1418,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 3,
            'isOptional' => true,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * Retrieve the external attributes of an entry defined by its index
 * @link https://www.php.net/manual/en/ziparchive.getexternalattributesindex.php
 * @param int $index Index of the entry.
 * @param int &$opsys On success, receive the operating system code defined by one of the ZipArchive::OPSYS_ constants.
 * @param int &$attr On success, receive the external attributes. Value depends on operating system.
 * @param int $flags [optional] If flags is set to ZipArchive::FL_UNCHANGED, the original unchanged attributes are returned.
 * @return bool Returns <b>TRUE</b> on success or <b>FALSE</b> on failure.
 * @betterReflectionTentativeReturnType
 */',
        'startLine' => 1409,
        'endLine' => 1421,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'isEncryptionMethodSupported' => 
      array (
        'name' => 'isEncryptionMethodSupported',
        'parameters' => 
        array (
          'method' => 
          array (
            'name' => 'method',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1424,
                      'endLine' => 1424,
                      'startTokenPos' => 4345,
                      'startFilePos' => 56314,
                      'endTokenPos' => 4351,
                      'endFilePos' => 56329,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1424,
                      'endLine' => 1424,
                      'startTokenPos' => 4357,
                      'startFilePos' => 56341,
                      'endTokenPos' => 4357,
                      'endFilePos' => 56342,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1424,
            'endLine' => 1425,
            'startColumn' => 13,
            'endColumn' => 23,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'enc' => 
          array (
            'name' => 'enc',
            'default' => 
            array (
              'code' => '\\true',
              'attributes' => 
              array (
                'startLine' => 1427,
                'endLine' => 1427,
                'startTokenPos' => 4391,
                'startFilePos' => 56494,
                'endTokenPos' => 4391,
                'endFilePos' => 56497,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'bool\']',
                    'attributes' => 
                    array (
                      'startLine' => 1426,
                      'endLine' => 1426,
                      'startTokenPos' => 4369,
                      'startFilePos' => 56437,
                      'endTokenPos' => 4375,
                      'endFilePos' => 56453,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1426,
                      'endLine' => 1426,
                      'startTokenPos' => 4381,
                      'startFilePos' => 56465,
                      'endTokenPos' => 4381,
                      'endFilePos' => 56466,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1426,
            'endLine' => 1427,
            'startColumn' => 13,
            'endColumn' => 28,
            'parameterIndex' => 1,
            'isOptional' => true,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '[\'8.0\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 1422,
                  'endLine' => 1422,
                  'startTokenPos' => 4317,
                  'startFilePos' => 56155,
                  'endTokenPos' => 4323,
                  'endFilePos' => 56171,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 1422,
                  'endLine' => 1422,
                  'startTokenPos' => 4329,
                  'startFilePos' => 56183,
                  'endTokenPos' => 4329,
                  'endFilePos' => 56184,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 1422,
        'endLine' => 1430,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'isCompressionMethodSupported' => 
      array (
        'name' => 'isCompressionMethodSupported',
        'parameters' => 
        array (
          'method' => 
          array (
            'name' => 'method',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1433,
                      'endLine' => 1433,
                      'startTokenPos' => 4433,
                      'startFilePos' => 56757,
                      'endTokenPos' => 4439,
                      'endFilePos' => 56772,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1433,
                      'endLine' => 1433,
                      'startTokenPos' => 4445,
                      'startFilePos' => 56784,
                      'endTokenPos' => 4445,
                      'endFilePos' => 56785,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1433,
            'endLine' => 1434,
            'startColumn' => 13,
            'endColumn' => 23,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'enc' => 
          array (
            'name' => 'enc',
            'default' => 
            array (
              'code' => '\\true',
              'attributes' => 
              array (
                'startLine' => 1436,
                'endLine' => 1436,
                'startTokenPos' => 4479,
                'startFilePos' => 56937,
                'endTokenPos' => 4479,
                'endFilePos' => 56940,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'bool\']',
                    'attributes' => 
                    array (
                      'startLine' => 1435,
                      'endLine' => 1435,
                      'startTokenPos' => 4457,
                      'startFilePos' => 56880,
                      'endTokenPos' => 4463,
                      'endFilePos' => 56896,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1435,
                      'endLine' => 1435,
                      'startTokenPos' => 4469,
                      'startFilePos' => 56908,
                      'endTokenPos' => 4469,
                      'endFilePos' => 56909,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1435,
            'endLine' => 1436,
            'startColumn' => 13,
            'endColumn' => 28,
            'parameterIndex' => 1,
            'isOptional' => true,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '[\'8.0\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 1431,
                  'endLine' => 1431,
                  'startTokenPos' => 4405,
                  'startFilePos' => 56597,
                  'endTokenPos' => 4411,
                  'endFilePos' => 56613,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 1431,
                  'endLine' => 1431,
                  'startTokenPos' => 4417,
                  'startFilePos' => 56625,
                  'endTokenPos' => 4417,
                  'endFilePos' => 56626,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 1431,
        'endLine' => 1439,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'registerCancelCallback' => 
      array (
        'name' => 'registerCancelCallback',
        'parameters' => 
        array (
          'callback' => 
          array (
            'name' => 'callback',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'callable\']',
                    'attributes' => 
                    array (
                      'startLine' => 1443,
                      'endLine' => 1443,
                      'startTokenPos' => 4506,
                      'startFilePos' => 57198,
                      'endTokenPos' => 4512,
                      'endFilePos' => 57218,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1443,
                      'endLine' => 1443,
                      'startTokenPos' => 4518,
                      'startFilePos' => 57230,
                      'endTokenPos' => 4518,
                      'endFilePos' => 57231,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1443,
            'endLine' => 1444,
            'startColumn' => 13,
            'endColumn' => 30,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/** @betterReflectionTentativeReturnType */',
        'startLine' => 1441,
        'endLine' => 1447,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'registerProgressCallback' => 
      array (
        'name' => 'registerProgressCallback',
        'parameters' => 
        array (
          'rate' => 
          array (
            'name' => 'rate',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'float',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'float\']',
                    'attributes' => 
                    array (
                      'startLine' => 1451,
                      'endLine' => 1451,
                      'startTokenPos' => 4551,
                      'startFilePos' => 57524,
                      'endTokenPos' => 4557,
                      'endFilePos' => 57541,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1451,
                      'endLine' => 1451,
                      'startTokenPos' => 4563,
                      'startFilePos' => 57553,
                      'endTokenPos' => 4563,
                      'endFilePos' => 57554,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1451,
            'endLine' => 1452,
            'startColumn' => 13,
            'endColumn' => 23,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'callback' => 
          array (
            'name' => 'callback',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'callable\']',
                    'attributes' => 
                    array (
                      'startLine' => 1453,
                      'endLine' => 1453,
                      'startTokenPos' => 4575,
                      'startFilePos' => 57649,
                      'endTokenPos' => 4581,
                      'endFilePos' => 57669,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1453,
                      'endLine' => 1453,
                      'startTokenPos' => 4587,
                      'startFilePos' => 57681,
                      'endTokenPos' => 4587,
                      'endFilePos' => 57682,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1453,
            'endLine' => 1454,
            'startColumn' => 13,
            'endColumn' => 30,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/** @betterReflectionTentativeReturnType */',
        'startLine' => 1449,
        'endLine' => 1457,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'setMtimeName' => 
      array (
        'name' => 'setMtimeName',
        'parameters' => 
        array (
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 1461,
                      'endLine' => 1461,
                      'startTokenPos' => 4620,
                      'startFilePos' => 57963,
                      'endTokenPos' => 4626,
                      'endFilePos' => 57981,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1461,
                      'endLine' => 1461,
                      'startTokenPos' => 4632,
                      'startFilePos' => 57993,
                      'endTokenPos' => 4632,
                      'endFilePos' => 57994,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1461,
            'endLine' => 1462,
            'startColumn' => 13,
            'endColumn' => 24,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'timestamp' => 
          array (
            'name' => 'timestamp',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1463,
                      'endLine' => 1463,
                      'startTokenPos' => 4644,
                      'startFilePos' => 58090,
                      'endTokenPos' => 4650,
                      'endFilePos' => 58105,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1463,
                      'endLine' => 1463,
                      'startTokenPos' => 4656,
                      'startFilePos' => 58117,
                      'endTokenPos' => 4656,
                      'endFilePos' => 58118,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1463,
            'endLine' => 1464,
            'startColumn' => 13,
            'endColumn' => 26,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 1466,
                'endLine' => 1466,
                'startTokenPos' => 4690,
                'startFilePos' => 58273,
                'endTokenPos' => 4690,
                'endFilePos' => 58276,
              ),
            ),
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
                      'name' => 'int',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1465,
                      'endLine' => 1465,
                      'startTokenPos' => 4668,
                      'startFilePos' => 58216,
                      'endTokenPos' => 4674,
                      'endFilePos' => 58231,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1465,
                      'endLine' => 1465,
                      'startTokenPos' => 4680,
                      'startFilePos' => 58243,
                      'endTokenPos' => 4680,
                      'endFilePos' => 58244,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1465,
            'endLine' => 1466,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 2,
            'isOptional' => true,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/** @betterReflectionTentativeReturnType */',
        'startLine' => 1459,
        'endLine' => 1469,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'setMtimeIndex' => 
      array (
        'name' => 'setMtimeIndex',
        'parameters' => 
        array (
          'index' => 
          array (
            'name' => 'index',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1473,
                      'endLine' => 1473,
                      'startTokenPos' => 4717,
                      'startFilePos' => 58525,
                      'endTokenPos' => 4723,
                      'endFilePos' => 58540,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1473,
                      'endLine' => 1473,
                      'startTokenPos' => 4729,
                      'startFilePos' => 58552,
                      'endTokenPos' => 4729,
                      'endFilePos' => 58553,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1473,
            'endLine' => 1474,
            'startColumn' => 13,
            'endColumn' => 22,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'timestamp' => 
          array (
            'name' => 'timestamp',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1475,
                      'endLine' => 1475,
                      'startTokenPos' => 4741,
                      'startFilePos' => 58647,
                      'endTokenPos' => 4747,
                      'endFilePos' => 58662,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1475,
                      'endLine' => 1475,
                      'startTokenPos' => 4753,
                      'startFilePos' => 58674,
                      'endTokenPos' => 4753,
                      'endFilePos' => 58675,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1475,
            'endLine' => 1476,
            'startColumn' => 13,
            'endColumn' => 26,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 1478,
                'endLine' => 1478,
                'startTokenPos' => 4787,
                'startFilePos' => 58830,
                'endTokenPos' => 4787,
                'endFilePos' => 58833,
              ),
            ),
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
                      'name' => 'int',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1477,
                      'endLine' => 1477,
                      'startTokenPos' => 4765,
                      'startFilePos' => 58773,
                      'endTokenPos' => 4771,
                      'endFilePos' => 58788,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1477,
                      'endLine' => 1477,
                      'startTokenPos' => 4777,
                      'startFilePos' => 58800,
                      'endTokenPos' => 4777,
                      'endFilePos' => 58801,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1477,
            'endLine' => 1478,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 2,
            'isOptional' => true,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/** @betterReflectionTentativeReturnType */',
        'startLine' => 1471,
        'endLine' => 1481,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'replaceFile' => 
      array (
        'name' => 'replaceFile',
        'parameters' => 
        array (
          'filepath' => 
          array (
            'name' => 'filepath',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 1485,
                      'endLine' => 1485,
                      'startTokenPos' => 4814,
                      'startFilePos' => 59080,
                      'endTokenPos' => 4820,
                      'endFilePos' => 59098,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1485,
                      'endLine' => 1485,
                      'startTokenPos' => 4826,
                      'startFilePos' => 59110,
                      'endTokenPos' => 4826,
                      'endFilePos' => 59111,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1485,
            'endLine' => 1486,
            'startColumn' => 13,
            'endColumn' => 28,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'index' => 
          array (
            'name' => 'index',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1487,
                      'endLine' => 1487,
                      'startTokenPos' => 4838,
                      'startFilePos' => 59211,
                      'endTokenPos' => 4844,
                      'endFilePos' => 59226,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1487,
                      'endLine' => 1487,
                      'startTokenPos' => 4850,
                      'startFilePos' => 59238,
                      'endTokenPos' => 4850,
                      'endFilePos' => 59239,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1487,
            'endLine' => 1488,
            'startColumn' => 13,
            'endColumn' => 22,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'start' => 
          array (
            'name' => 'start',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 1490,
                'endLine' => 1490,
                'startTokenPos' => 4884,
                'startFilePos' => 59390,
                'endTokenPos' => 4884,
                'endFilePos' => 59393,
              ),
            ),
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
                      'name' => 'int',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1489,
                      'endLine' => 1489,
                      'startTokenPos' => 4862,
                      'startFilePos' => 59333,
                      'endTokenPos' => 4868,
                      'endFilePos' => 59348,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1489,
                      'endLine' => 1489,
                      'startTokenPos' => 4874,
                      'startFilePos' => 59360,
                      'endTokenPos' => 4874,
                      'endFilePos' => 59361,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1489,
            'endLine' => 1490,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'length' => 
          array (
            'name' => 'length',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 1492,
                'endLine' => 1492,
                'startTokenPos' => 4912,
                'startFilePos' => 59520,
                'endTokenPos' => 4912,
                'endFilePos' => 59523,
              ),
            ),
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
                      'name' => 'int',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1491,
                      'endLine' => 1491,
                      'startTokenPos' => 4890,
                      'startFilePos' => 59462,
                      'endTokenPos' => 4896,
                      'endFilePos' => 59477,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1491,
                      'endLine' => 1491,
                      'startTokenPos' => 4902,
                      'startFilePos' => 59489,
                      'endTokenPos' => 4902,
                      'endFilePos' => 59490,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1491,
            'endLine' => 1492,
            'startColumn' => 13,
            'endColumn' => 30,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 1494,
                'endLine' => 1494,
                'startTokenPos' => 4940,
                'startFilePos' => 59649,
                'endTokenPos' => 4940,
                'endFilePos' => 59652,
              ),
            ),
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
                      'name' => 'int',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.0\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1493,
                      'endLine' => 1493,
                      'startTokenPos' => 4918,
                      'startFilePos' => 59592,
                      'endTokenPos' => 4924,
                      'endFilePos' => 59607,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1493,
                      'endLine' => 1493,
                      'startTokenPos' => 4930,
                      'startFilePos' => 59619,
                      'endTokenPos' => 4930,
                      'endFilePos' => 59620,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1493,
            'endLine' => 1494,
            'startColumn' => 13,
            'endColumn' => 29,
            'parameterIndex' => 4,
            'isOptional' => true,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\TentativeType',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/** @betterReflectionTentativeReturnType */',
        'startLine' => 1483,
        'endLine' => 1497,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'clearError' => 
      array (
        'name' => 'clearError',
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '[\'8.0\' => \'void\']',
                'attributes' => 
                array (
                  'startLine' => 1498,
                  'endLine' => 1498,
                  'startTokenPos' => 4954,
                  'startFilePos' => 59752,
                  'endTokenPos' => 4960,
                  'endFilePos' => 59768,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 1498,
                  'endLine' => 1498,
                  'startTokenPos' => 4966,
                  'startFilePos' => 59780,
                  'endTokenPos' => 4966,
                  'endFilePos' => 59781,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 1498,
        'endLine' => 1501,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'setArchiveFlag' => 
      array (
        'name' => 'setArchiveFlag',
        'parameters' => 
        array (
          'flag' => 
          array (
            'name' => 'flag',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.3\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1509,
                      'endLine' => 1509,
                      'startTokenPos' => 5016,
                      'startFilePos' => 60154,
                      'endTokenPos' => 5022,
                      'endFilePos' => 60169,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1509,
                      'endLine' => 1509,
                      'startTokenPos' => 5028,
                      'startFilePos' => 60181,
                      'endTokenPos' => 5028,
                      'endFilePos' => 60182,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1509,
            'endLine' => 1510,
            'startColumn' => 13,
            'endColumn' => 21,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'value' => 
          array (
            'name' => 'value',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.3\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1511,
                      'endLine' => 1511,
                      'startTokenPos' => 5040,
                      'startFilePos' => 60275,
                      'endTokenPos' => 5046,
                      'endFilePos' => 60290,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1511,
                      'endLine' => 1511,
                      'startTokenPos' => 5052,
                      'startFilePos' => 60302,
                      'endTokenPos' => 5052,
                      'endFilePos' => 60303,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1511,
            'endLine' => 1512,
            'startColumn' => 13,
            'endColumn' => 22,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '[\'8.0\' => \'bool\']',
                'attributes' => 
                array (
                  'startLine' => 1507,
                  'endLine' => 1507,
                  'startTokenPos' => 4990,
                  'startFilePos' => 60015,
                  'endTokenPos' => 4996,
                  'endFilePos' => 60031,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 1507,
                  'endLine' => 1507,
                  'startTokenPos' => 5002,
                  'startFilePos' => 60043,
                  'endTokenPos' => 5002,
                  'endFilePos' => 60044,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * @param int $flag
 * @param int $value
 * @return bool
 */',
        'startLine' => 1507,
        'endLine' => 1515,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'getArchiveFlag' => 
      array (
        'name' => 'getArchiveFlag',
        'parameters' => 
        array (
          'flag' => 
          array (
            'name' => 'flag',
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.3\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1523,
                      'endLine' => 1523,
                      'startTokenPos' => 5100,
                      'startFilePos' => 60670,
                      'endTokenPos' => 5106,
                      'endFilePos' => 60685,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1523,
                      'endLine' => 1523,
                      'startTokenPos' => 5112,
                      'startFilePos' => 60697,
                      'endTokenPos' => 5112,
                      'endFilePos' => 60698,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1523,
            'endLine' => 1524,
            'startColumn' => 13,
            'endColumn' => 21,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 1526,
                'endLine' => 1526,
                'startTokenPos' => 5146,
                'startFilePos' => 60848,
                'endTokenPos' => 5146,
                'endFilePos' => 60848,
              ),
            ),
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.3\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1525,
                      'endLine' => 1525,
                      'startTokenPos' => 5124,
                      'startFilePos' => 60791,
                      'endTokenPos' => 5130,
                      'endFilePos' => 60806,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1525,
                      'endLine' => 1525,
                      'startTokenPos' => 5136,
                      'startFilePos' => 60818,
                      'endTokenPos' => 5136,
                      'endFilePos' => 60819,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1525,
            'endLine' => 1526,
            'startColumn' => 13,
            'endColumn' => 26,
            'parameterIndex' => 1,
            'isOptional' => true,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '[\'8.0\' => \'int\']',
                'attributes' => 
                array (
                  'startLine' => 1521,
                  'endLine' => 1521,
                  'startTokenPos' => 5074,
                  'startFilePos' => 60532,
                  'endTokenPos' => 5080,
                  'endFilePos' => 60547,
                ),
              ),
              'default' => 
              array (
                'code' => '\'\'',
                'attributes' => 
                array (
                  'startLine' => 1521,
                  'endLine' => 1521,
                  'startTokenPos' => 5086,
                  'startFilePos' => 60559,
                  'endTokenPos' => 5086,
                  'endFilePos' => 60560,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * @param int $flag
 * @param int $flags
 * @return int
 */',
        'startLine' => 1521,
        'endLine' => 1529,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
        'aliasName' => NULL,
      ),
      'getStreamName' => 
      array (
        'name' => 'getStreamName',
        'parameters' => 
        array (
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.2\' => \'string\']',
                    'attributes' => 
                    array (
                      'startLine' => 1536,
                      'endLine' => 1536,
                      'startTokenPos' => 5169,
                      'startFilePos' => 61098,
                      'endTokenPos' => 5175,
                      'endFilePos' => 61116,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1536,
                      'endLine' => 1536,
                      'startTokenPos' => 5181,
                      'startFilePos' => 61128,
                      'endTokenPos' => 5181,
                      'endFilePos' => 61129,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1536,
            'endLine' => 1537,
            'startColumn' => 13,
            'endColumn' => 24,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 1539,
                'endLine' => 1539,
                'startTokenPos' => 5215,
                'startFilePos' => 61282,
                'endTokenPos' => 5215,
                'endFilePos' => 61282,
              ),
            ),
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
              0 => 
              array (
                'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
                'isRepeated' => false,
                'arguments' => 
                array (
                  0 => 
                  array (
                    'code' => '[\'8.2\' => \'int\']',
                    'attributes' => 
                    array (
                      'startLine' => 1538,
                      'endLine' => 1538,
                      'startTokenPos' => 5193,
                      'startFilePos' => 61225,
                      'endTokenPos' => 5199,
                      'endFilePos' => 61240,
                    ),
                  ),
                  'default' => 
                  array (
                    'code' => '\'\'',
                    'attributes' => 
                    array (
                      'startLine' => 1538,
                      'endLine' => 1538,
                      'startTokenPos' => 5205,
                      'startFilePos' => 61252,
                      'endTokenPos' => 5205,
                      'endFilePos' => 61253,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 1538,
            'endLine' => 1539,
            'startColumn' => 13,
            'endColumn' => 26,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param string $name
 * @param int $flags
 * @return void
 */',
        'startLine' => 1535,
        'endLine' => 1542,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'ZipArchive',
        'implementingClassName' => 'ZipArchive',
        'currentClassName' => 'ZipArchive',
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