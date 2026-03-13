# CI/CD Quality Checks

## Overview

The DocuDesk application includes comprehensive automated quality checks that run on every push and pull request. These checks ensure code quality, security, and compliance across both PHP and JavaScript dependencies.

## Code Quality Workflow

The workflow `.github/workflows/code-quality.yml` performs the following checks:

### 1. Repository Status Check
- Identifies stale branches (older than 30 days)
- Lists open pull requests with their status
- Generates a comprehensive repository health report

### 2. License Compliance Check
- Scans all PHP dependencies for license information
- Checks JavaScript dependencies for license compliance
- Warns about GPL or unknown licenses that might cause issues

### 3. Security Vulnerability Check
- **PHP Dependencies**: Uses `composer audit` to check for known security vulnerabilities
- **JavaScript Dependencies**: Uses `npm audit` to identify security issues in Node.js packages
- Generates detailed security reports with severity levels

### 4. Code Linting Check
- **PHP Syntax**: Basic PHP syntax validation using `php -l`
- **PHP CodeSniffer (PHPCS)**: Code style validation using project's phpcs.xml configuration
- **PHP CS Fixer**: Code formatting validation and automatic fixing capabilities
- **PHP Mess Detector (PHPMD)**: Detection of code smells and potential issues
- **Psalm Static Analysis**: Advanced static type checking and bug detection
- **JavaScript**: Executes ESLint and Stylelint for code quality

### 5. Unit Testing
- **PHP Unit Tests**: Runs PHPUnit tests following Nextcloud testing conventions
- **Test Coverage**: Generates code coverage reports when possible
- **Nextcloud Integration**: Uses proper Nextcloud test bootstrapping for app testing
- **Mocking**: Utilizes PHPUnit mocking for database and external dependencies

## Recent Improvements

### npm Audit Output Format Handling

The workflow has been updated to handle changes in npm audit output format across different npm versions:

#### Problem
Newer versions of npm (v7+) changed the JSON output structure for `npm audit`, causing the workflow to fail with:
```
jq: error (at <stdin>:1166): null (null) has no keys
```

#### Solution
The workflow now includes robust handling for both old and new npm audit formats:

1. **Format Detection**: Automatically detects whether the output uses the newer `vulnerabilities` format or older `advisories` format
2. **Null Value Handling**: Uses jq's `select(.value != null)` and `// "fallback"` operators to handle null values gracefully
3. **Error Recovery**: Includes fallback messages when audit tools fail or produce unexpected output
4. **Temporary File Management**: Uses intermediate files for better error handling and cleanup

#### Technical Details

**New Implementation for JavaScript Security Check:**
```bash
# Handle newer npm audit format with better error handling
npm audit --json > npm_audit_output.json 2>/dev/null || echo "No vulnerabilities found"

# Try newer format first (npm v7+)
if jq -e '.vulnerabilities' npm_audit_output.json >/dev/null 2>&1; then
  jq -r '.vulnerabilities | to_entries[] | select(.value != null) | "\(.value.name // "Unknown") - \(.value.title // "No title") - Severity: \(.value.severity // "Unknown")"'
# Fallback to older format (npm v6)
elif jq -e '.advisories' npm_audit_output.json >/dev/null 2>&1; then
  jq -r '.advisories | to_entries[] | select(.value != null) | "\(.value.module_name // .value.name // "Unknown") - \(.value.title // "No title") - Severity: \(.value.severity // "Unknown")"'
fi
```

**Similar improvements for PHP Security Check:**
```bash
composer audit --format=json > composer_audit_output.json 2>/dev/null
if [ -s composer_audit_output.json ] && jq -e '.advisories' composer_audit_output.json >/dev/null 2>&1; then
  jq -r '.advisories[] | select(. != null) | "\(.packageName // "Unknown") - \(.title // "No title") - Severity: \(.severity // "Unknown")"'
fi
```

## Report Generation

All checks generate individual reports that are:
1. Combined into a comprehensive quality report
2. Uploaded as build artifacts (including the combined report)
3. Can be sent to Slack channels for team notification (optional)

The combined report includes:
- Repository status (stale branches, open PRs)
- License compliance results
- Security vulnerability findings  
- Code linting results
- Unit test results and coverage

### Slack Integration (Optional)

The workflow can optionally send notifications to Slack when quality checks complete:

#### Setup Instructions
1. **Create a Slack App**: Go to https://api.slack.com/apps and create a new app
2. **Generate Bot Token**: Create a bot token with appropriate permissions
3. **Add Secret**: Add the token as `SLACK_BOT_TOKEN` in your repository secrets
4. **Configure Channel**: Update the `channel-id` in the workflow file

#### Behavior
- **With Token**: Sends quality report summary to configured Slack channel
- **Without Token**: Workflow completes successfully, logs that Slack is skipped
- **Never Fails**: Missing Slack configuration will not cause the workflow to fail

## Configuration Files

The quality checks use several configuration files in the project root, all explicitly referenced to ensure proper usage:

- **`phpcs.xml`** - PHP CodeSniffer rules and standards (comprehensive PEAR-based ruleset)
- **`phpmd.xml`** - PHP Mess Detector rules for code quality analysis
- **`psalm.xml`** - Psalm static analysis configuration with strict error level
- **`.php-cs-fixer.dist.php`** - PHP CS Fixer formatting rules (PSR-12 + additional rules)
- **`phpunit.xml`** - PHPUnit testing configuration for unit tests
- **`.eslintrc.js`** - ESLint configuration for JavaScript quality
- **`stylelint.config.js`** - Stylelint configuration for CSS quality

### Composer Scripts Configuration

All composer scripts explicitly reference the configuration files to ensure they're found:

```json
{
  "phpcs": "phpcs --standard=./phpcs.xml",
  "phpmd": "phpmd lib text ./phpmd.xml", 
  "psalm": "psalm --config=./psalm.xml --threads=1 --no-cache",
  "cs:check": "php-cs-fixer fix --dry-run --diff --config=.php-cs-fixer.dist.php"
}
```

### Workflow Validation

The workflow includes validation steps to ensure all configuration files are present and accessible:

- ✅ **Working Directory Check**: Verifies the correct project root
- ✅ **Config File Detection**: Confirms all config files are found
- ✅ **Tool Availability**: Checks that all static analysis tools are installed
- ✅ **Explicit Paths**: Uses absolute paths to prevent config file lookup issues

## Unit Testing Setup

### PHP Unit Testing Configuration

The project follows [Nextcloud unit testing guidelines](https://docs.nextcloud.com/server/latest/developer_manual/server/unit-testing.html) with the following setup:

#### Directory Structure
```
tests/
├── bootstrap.php          # Test environment bootstrap
└── unit/                  # Unit tests directory
    └── Service/           # Service layer tests
        └── TemplateServiceTest.php
```

#### Bootstrap Configuration
The `tests/bootstrap.php` file properly initializes the Nextcloud testing environment:
- Loads Nextcloud base libraries
- Sets up autoloading for test files
- Ensures the DocuDesk app is loaded
- Provides access to Nextcloud test utilities

#### Test Conventions
All test classes follow these conventions:
- Extend `\Test\TestCase` (Nextcloud's base test class)
- Use proper PHPUnit annotations and type hints
- Include comprehensive docblocks with @psalm and @phpstan annotations
- Follow Arrange-Act-Assert pattern in test methods
- Use meaningful test method names starting with 'test'

#### Example Test Structure
```php
class TemplateServiceTest extends TestCase
{
    private TemplateService $templateService;
    private TemplateMapper|MockObject $mockMapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockMapper = $this->createMock(TemplateMapper::class);
        $this->templateService = new TemplateService($this->mockMapper);
    }

    public function testCreateTemplateWithValidData(): void
    {
        // Arrange: Set up test data
        // Act: Call method under test
        // Assert: Verify results
    }
}
```

#### Mocking Strategy
- Use PHPUnit's `createMock()` for database mappers
- Mock external dependencies and services
- Verify method calls with `expects()` and `with()`
- Use callback functions for complex parameter validation

## Running Checks Locally

You can run individual quality checks locally:

```bash
# PHP syntax and linting
composer run lint                    # Basic PHP syntax check
composer run phpcs                   # PHP CodeSniffer style check
composer run cs:check                # PHP CS Fixer validation
composer run phpmd                   # PHP Mess Detector analysis
composer run psalm                   # Psalm static analysis

# PHP formatting (auto-fix)
composer run phpcbf                  # Auto-fix PHPCS issues
composer run cs:fix                  # Auto-fix PHP CS Fixer issues

# JavaScript linting  
npm run lint
npm run stylelint

# Security checks
composer audit
npm audit

# Unit tests
./vendor/bin/phpunit --configuration phpunit.xml
./vendor/bin/phpunit tests/unit --testdox
./vendor/bin/phpunit --coverage-text  # With coverage report
```

## Troubleshooting

### Common Issues

1. **npm audit jq errors**: Fixed in the latest workflow version with better null handling
2. **Missing dependencies**: Ensure all dev dependencies are installed with `composer install` and `npm ci`
3. **Psalm not found**: Install development tools using the composer bin plugin
4. **Artifact path errors**: Fixed in the latest version - artifacts are downloaded into directories, so file paths need to include the artifact directory name (e.g., `reports/repo-status/repo-status.txt` instead of `reports/repo-status`)
5. **PHPUnit not found**: Ensure PHPUnit is installed via composer and available in `vendor/bin/phpunit`
6. **Nextcloud test bootstrap errors**: Verify the bootstrap file correctly points to Nextcloud's base.php and test libraries
7. **Test database issues**: Unit tests should use mocks instead of real database connections
8. **Permission errors in tests**: Ensure test directories have proper read/write permissions
9. **Slack notification failures**: The workflow gracefully handles missing Slack tokens - no configuration needed unless you want notifications
10. **Static analysis tools not found**: Run `composer install --dev` to install all required development tools
11. **PHPCS/PHPMD configuration errors**: Verify configuration files (phpcs.xml, phpmd.xml) are properly formatted
12. **PHP CS Fixer permission issues**: Ensure the project directory is writable for auto-fixing

### Dependencies

The following development dependencies are automatically installed with specific versions for compatibility:
- **squizlabs/php_codesniffer**: ^3.10 - PHP CodeSniffer for style checking
- **friendsofphp/php-cs-fixer**: ^3.64 - PHP CS Fixer for code formatting
- **phpmd/phpmd**: ^2.15 - PHP Mess Detector for code quality analysis
- **vimeo/psalm**: ^5.26 - Psalm for static type analysis
- **phpunit/phpunit**: ^9.6 - PHPUnit for unit testing

All tools are available in `vendor/bin/` after running `composer install --dev`.

### Composer Configuration

The project uses specific platform and configuration settings to ensure compatibility:
```json
{
  "platform": { "php": "8.1.0" },
  "preferred-install": "dist",
  "process-timeout": 600
}
```

### Installation Troubleshooting

If composer installation fails, the workflow includes fallback strategies:
1. **Cache clearing**: Clears composer cache to resolve stale dependency data
2. **Individual installation**: Installs each tool separately if bulk install fails
3. **Conflict resolution**: Uses `--with-all-dependencies` to resolve version conflicts
4. **Graceful degradation**: Continues workflow even if some tools fail to install

### Recent Fixes

#### Artifact Download Path Issue
**Problem**: The combine reports step was failing with `cat: reports/repo-status: Is a directory` because `actions/download-artifact@v4` creates directories for each artifact.

**Solution**: Updated file paths to reference the actual files inside the artifact directories:
- `reports/repo-status` → `reports/repo-status/repo-status.txt`
- `reports/license-report` → `reports/license-report/license-report.txt`
- `reports/security-report` → `reports/security-report/security-report.txt`
- `reports/lint-report` → `reports/lint-report/lint-report.txt`

### Manual Workflow Trigger

The workflow can be manually triggered with a 'report only' option that skips actual checks and only generates status reports.

## Best Practices

1. **Regular Updates**: Keep dependencies updated to reduce security vulnerabilities
2. **License Review**: Regularly review license reports to ensure compliance
3. **Stale Branch Cleanup**: Use repository status reports to identify and clean up old branches
4. **Security Monitoring**: Monitor security reports and address vulnerabilities promptly 