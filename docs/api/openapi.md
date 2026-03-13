---
sidebar_position: 1
title: API Documentation
---

# DocuDesk API Documentation

DocuDesk provides a RESTful API that allows you to interact with documents, folders, and tags programmatically. This page explains how to use the API documentation.

## OpenAPI Specification

Our API is documented using the [OpenAPI Specification](https://swagger.io/specification/) (OAS), which provides a standardized way to describe RESTful APIs. The OAS files are available in the following formats:

- [YAML Format](/oas/docudesk-api.yaml)

## Using the API Documentation

You can use the OAS files with various tools:

### Swagger UI

You can view the API documentation using Swagger UI by:

1. Visiting our [Redocly UI page](/api) (coming soon)
2. Downloading the OAS file and importing it into the [Swagger Editor](https://editor.swagger.io/)

### Postman

To use the API with Postman:

1. Download the OAS file
2. In Postman, click 'Import' and select the downloaded file
3. Postman will create a collection with all available endpoints

## Authentication

All API requests require authentication. You need to include your Nextcloud authentication token in the request headers:

```
Authorization: Bearer YOUR_TOKEN
```

## API Endpoints

The DocuDesk API provides endpoints for managing:

- **Documents**: Create, read, update, and delete documents
- **Folders**: Organize documents in folders
- **Tags**: Categorize documents with tags
- **Search**: Find documents based on various criteria

For detailed information about each endpoint, including request parameters and response formats, please refer to the OpenAPI documentation.

## Data Models

The API uses the following main data models:

- **Document**: Represents a document in the system
- **Folder**: Represents a folder that can contain documents
- **Tag**: Represents a tag that can be applied to documents

## Error Handling

The API uses standard HTTP status codes to indicate the success or failure of requests. In case of an error, the response will include a JSON object with an error code and message.

## Versioning

The API is versioned to ensure backward compatibility. The current version is v1, which is reflected in the API base path: `/apps/docudesk/api/v1`.

## Contributing

If you find issues with the API or documentation, please report them on our [GitHub repository](https://github.com/yourusername/docudesk). 