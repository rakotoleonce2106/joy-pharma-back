# File Upload Pattern Documentation

## Overview

This API follows a **separated upload pattern** where file uploads are handled separately from entity create/update operations. This pattern uses **JSON-LD (application/ld+json)** for all entity operations, with multipart/form-data **only** for the MediaObject upload endpoint.

## Why POST Only for File Uploads?

### Technical Reasons

1. **PHP's `$_FILES` Superglobal**
   - PHP's `$_FILES` superglobal only populates with POST requests
   - PUT/PATCH requests don't populate `$_FILES`, making file uploads impossible

2. **php://input Limitations**
   - `php://input` doesn't parse multipart/form-data for PUT/PATCH requests
   - The raw input stream is available, but parsing multipart boundaries manually is complex and error-prone

3. **Symfony's Native Behavior**
   - Symfony follows PHP's native behavior for file handling
   - `Request::files` only contains files for POST requests

4. **HTTP Specification**
   - While HTTP spec allows PUT with multipart, PHP's implementation doesn't support it
   - This is a limitation of the PHP runtime, not the HTTP protocol

## Recommended Pattern

### Step 1: Upload File (POST Only - MediaObject Endpoint)

Upload your file using the dedicated MediaObject endpoint:

**Create a new MediaObject:**
```http
POST /api/media_objects
Content-Type: multipart/form-data

file: [binary file data]
mapping: "category_images" (optional)
```

**Update an existing MediaObject:**
```http
POST /api/media_objects
Content-Type: multipart/form-data

id: 123 (optional - if provided and MediaObject exists, it will be updated)
file: [binary file data]
mapping: "category_images" (optional)
```

**Response (JSON-LD):**
```json
{
  "@id": "/api/media_objects/123",
  "@type": "https://schema.org/MediaObject",
  "contentUrl": "/images/categories/abc123.jpg",
  "id": 123
}
```

**Notes:**
- If `id` is provided and the MediaObject exists, it will be updated with the new file
- If `id` is provided but the MediaObject doesn't exist, a new MediaObject will be created

### Step 2: Create/Update Entity (PUT/PATCH with JSON-LD)

Use the returned MediaObject IRI in your create/update request. API Platform automatically deserializes the IRI to a MediaObject entity:

```http
PUT /api/admin/categories/1
Content-Type: application/ld+json

{
  "@context": "/api/contexts/Category",
  "@type": "Category",
  "name": "Updated Category",
  "image": "/api/media_objects/123"
}
```

**Or with standard JSON:**
```http
PUT /api/admin/categories/1
Content-Type: application/json

{
  "name": "Updated Category",
  "image": "/api/media_objects/123"
}
```

**Note:** Old images are automatically detected and deleted when replaced. API Platform automatically deserializes the IRI string to a MediaObject entity.

## Client-Side Examples

### JavaScript (Fetch API)

#### Upload File First (Create)
```javascript
// Step 1: Upload file (multipart - POST only)
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('mapping', 'category_images');

const uploadResponse = await fetch('/api/media_objects', {
  method: 'POST',
  body: formData
});

const mediaObject = await uploadResponse.json();
const imageIri = mediaObject['@id']; // "/api/media_objects/123"
```

#### Update Existing MediaObject
```javascript
// Step 1: Update existing MediaObject (multipart - POST only)
const formData = new FormData();
formData.append('id', 123); // Update existing MediaObject with ID 123
formData.append('file', fileInput.files[0]);
formData.append('mapping', 'category_images');

const updateResponse = await fetch('/api/media_objects', {
  method: 'POST',
  body: formData
});

const mediaObject = await updateResponse.json();
const imageIri = mediaObject['@id']; // "/api/media_objects/123"

// Step 2: Update entity with IRI reference (JSON-LD)
const updateResponse = await fetch('/api/admin/categories/1', {
  method: 'PUT',
  headers: {
    'Content-Type': 'application/ld+json', // or 'application/json'
  },
  body: JSON.stringify({
    '@context': '/api/contexts/Category',
    '@type': 'Category',
    name: 'Updated Category',
    image: imageIri // API Platform deserializes this automatically
  })
});
```

### cURL Examples

#### Upload File (Create)
```bash
curl -X POST "https://api.example.com/api/media_objects" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@/path/to/image.jpg" \
  -F "mapping=category_images"
```

#### Update Existing MediaObject
```bash
curl -X POST "https://api.example.com/api/media_objects" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "id=123" \
  -F "file=@/path/to/image.jpg" \
  -F "mapping=category_images"
```

#### Update Entity with IRI
```bash
curl -X PUT "https://api.example.com/api/admin/categories/1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Updated Category",
    "image": "/api/media_objects/123"
  }'
```

## API Platform Native Features

This implementation uses **API Platform's native features**:

1. **Automatic IRI Deserialization**: API Platform automatically converts IRI strings (e.g., `/api/media_objects/123`) to entity objects
2. **JSON-LD Support**: Full support for `application/ld+json` format with `@id` and `@type` properties
3. **Type Safety**: DTOs use `MediaObject` type instead of strings, providing better type safety
4. **No Custom Processors Needed**: Uses API Platform's built-in denormalization for IRI references

## Format Support

- ✅ **POST** `/api/media_objects` - Upload files (multipart/form-data only)
- ✅ **PUT/PATCH** with JSON-LD - Update entities with MediaObject IRI references
- ✅ **PUT/PATCH** with JSON - Also supported (API Platform handles both)
- ❌ **PUT/PATCH** with multipart - Not supported (PHP limitation)

## Supported Mappings

When uploading files, you can specify a mapping to organize files:

- `media_object` - Default mapping (stored in `/public/media/`)
- `category_images` - Category images (stored in `/public/images/categories/`)
- `category_icons` - Category icons/SVG (stored in `/public/icons/categories/`)
- `product_images` - Product images (stored in `/public/images/products/`)

## Error Handling

### Invalid MediaObject Reference
If you provide an invalid IRI or non-existent MediaObject ID:
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "Bad Request",
  "detail": "Invalid image MediaObject reference: MediaObject not found with ID: 999"
}
```

### File Upload Errors
If file upload fails:
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "Bad Request",
  "detail": "No file provided"
}
```

## Best Practices

1. **Always upload files first** using POST `/api/media_objects`
2. **Use `id` field** to update existing MediaObjects instead of creating new ones
3. **Store the returned IRI** for use in subsequent requests
4. **Use PUT/PATCH with JSON** for entity updates
5. **Handle errors gracefully** - check if MediaObject upload succeeded before updating entity

## Migration Guide

If you're currently using multipart POST for create/update:

1. **No changes needed** - POST with multipart still works
2. **For PUT/PATCH updates** - Switch to JSON with IRI references
3. **Upload files separately** - Use `/api/media_objects` endpoint first
4. **Update your client code** - Use the two-step pattern described above

## Summary

- ✅ **POST** `/api/media_objects` - Upload files (multipart/form-data)
- ✅ **PUT/PATCH** with JSON - Update entities with MediaObject IRI references
- ✅ **POST** with multipart - Still supported for backward compatibility
- ❌ **PUT/PATCH** with multipart - Not supported (PHP limitation)

This pattern provides the best of both worlds: reliable file uploads and clean JSON-based updates.

