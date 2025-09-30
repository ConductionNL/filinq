# Project Structure Rules

## Code Organization

- **Backend Code**: All PHP backend code is located in the `/lib` folder
  - This includes models, controllers, services, and other PHP classes
  - All PHP code should follow PSR standards and include proper docblocks
  - All PHP code should pass phpcs and phpstan checks

- **Frontend Code**: All frontend code is located in the `/src` folder
  - This includes JavaScript/TypeScript components, utilities, and styles
  - Frontend code follows modern ES6+ standards

- **Documentation**: All documentation is in the `/website` folder
  - We use Docusaurus for documentation
  - Technical and user documentation are in the `/website/docs` folder
  - API documentation is in the `/website/static/oas` folder as OpenAPI Specification files
  - Documentation should be kept in sync with code changes

## Coding Standards

- All PHP classes should begin with a docblock containing:
  - Class name
  - Category
  - Package
  - Author
  - Copyright
  - License
  - Version
  - Link to the application

- All methods should include:
  - Docblocks
  - Return types
  - Type hints
  - Default values where appropriate
  - PHPStan and Psalm annotations
  - PHPUnit tests

- Use readonly properties where appropriate
- Add inline comments to explain complex logic
- When writing documentation, use single quotes (') instead of backticks (`)

## Testing

- PHPUnit tests should be created for all PHP methods
- Jest tests should be created for all JavaScript/TypeScript components

## Documentation

- All code changes should be reflected in the documentation
- Documentation is written in Markdown and processed by Docusaurus
- API endpoints and data models should be documented in the OpenAPI Specification files
- When adding new API endpoints or models, update the corresponding OAS files in `/website/static/oas` 