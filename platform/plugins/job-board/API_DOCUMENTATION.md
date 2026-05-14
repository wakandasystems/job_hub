# Job Board API Documentation

This document describes the REST API endpoints available for the Job Board plugin.

## Base URL
All API endpoints are prefixed with `/api/v1/`

## Authentication
Most endpoints are public and don't require authentication. Endpoints that require authentication use Laravel Sanctum and will be marked with 🔒.

To authenticate API requests:
1. Obtain an API token by logging in through the web interface or authentication endpoints
2. Include the token in the Authorization header: `Authorization: Bearer {your-token}`

### Authentication Required Endpoints
- Job applications
- Account management
- Job management (for employers)
- Analytics
- Reviews submission

## Jobs API

### List Jobs
- **GET** `/api/v1/jobs`
- **Description**: Get a paginated list of jobs with filtering options
- **Parameters**:
  - `keyword` (string): Search keyword
  - `company_id` (integer): Filter by company ID
  - `categories[]` (array): Filter by category IDs
  - `job_types[]` (array): Filter by job type IDs
  - `job_experiences[]` (array): Filter by experience level IDs
  - `job_skills[]` (array): Filter by skill IDs
  - `salary_from` (number): Minimum salary
  - `salary_to` (number): Maximum salary
  - `date_posted` (string): Filter by posting date
  - `city_id` (integer): Filter by city ID
  - `state_id` (integer): Filter by state ID
  - `location` (string): Location search
  - `per_page` (integer): Items per page (max 50, default 12)
  - `page` (integer): Page number

### Get Job Details
- **GET** `/api/v1/jobs/{id}`
- **Description**: Get detailed information about a specific job

### Get Related Jobs
- **GET** `/api/v1/jobs/{id}/related`
- **Description**: Get jobs related to the specified job
- **Parameters**:
  - `limit` (integer): Number of related jobs (max 20, default 5)

### Apply for Job 🔒
- **POST** `/api/v1/jobs/{id}/apply`
- **Description**: Submit a job application
- **Authentication**: Required (Sanctum token)
- **Body**: Form data with application details

### Get Featured Jobs
- **GET** `/api/v1/jobs/featured`
- **Parameters**:
  - `limit` (integer): Number of jobs (max 50, default 10)

### Get Recent Jobs
- **GET** `/api/v1/jobs/recent`
- **Parameters**:
  - `limit` (integer): Number of jobs (max 50, default 10)

### Get Popular Jobs
- **GET** `/api/v1/jobs/popular`
- **Parameters**:
  - `limit` (integer): Number of jobs (max 50, default 10)

## Companies API

### List Companies
- **GET** `/api/v1/companies`
- **Parameters**:
  - `keyword` (string): Search keyword
  - `city_id` (integer): Filter by city
  - `state_id` (integer): Filter by state
  - `country_id` (integer): Filter by country
  - `per_page` (integer): Items per page (max 50, default 12)

### Get Company Details
- **GET** `/api/v1/companies/{id}`

### Get Company Jobs
- **GET** `/api/v1/companies/{id}/jobs`
- **Parameters**:
  - `per_page` (integer): Items per page (max 50, default 12)
  - `page` (integer): Page number

### Get Featured Companies
- **GET** `/api/v1/companies/featured`
- **Parameters**:
  - `limit` (integer): Number of companies (max 50, default 10)

### Search Companies
- **GET** `/api/v1/companies/search`
- **Parameters**:
  - `q` (string): Search query
  - `limit` (integer): Number of results (max 50, default 10)
  - `paginate` (boolean): Whether to paginate results
  - `per_page` (integer): Items per page if paginating

## Categories API

### List Categories
- **GET** `/api/v1/categories`
- **Parameters**:
  - `keyword` (string): Search keyword
  - `per_page` (integer): Items per page (max 100, default 20)

### Get Category Details
- **GET** `/api/v1/categories/{id}`

### Get Category Jobs
- **GET** `/api/v1/categories/{id}/jobs`
- **Parameters**:
  - `per_page` (integer): Items per page (max 50, default 12)
  - `page` (integer): Page number

### Get Featured Categories
- **GET** `/api/v1/categories/featured`
- **Parameters**:
  - `limit` (integer): Number of categories (max 50, default 8)

## Job Types API

### List Job Types
- **GET** `/api/v1/job-types`
- **Parameters**:
  - `keyword` (string): Search keyword
  - `per_page` (integer): Items per page (max 100, default 20)

### Get Job Type Details
- **GET** `/api/v1/job-types/{id}`

## Job Skills API

### List Job Skills
- **GET** `/api/v1/job-skills`
- **Parameters**:
  - `keyword` (string): Search keyword
  - `per_page` (integer): Items per page (max 100, default 50)

### Get Job Skill Details
- **GET** `/api/v1/job-skills/{id}`

## Job Experiences API

### List Job Experiences
- **GET** `/api/v1/job-experiences`
- **Parameters**:
  - `keyword` (string): Search keyword
  - `per_page` (integer): Items per page (max 100, default 20)

### Get Job Experience Details
- **GET** `/api/v1/job-experiences/{id}`

## Career Levels API

### List Career Levels
- **GET** `/api/v1/career-levels`
- **Parameters**:
  - `keyword` (string): Search keyword
  - `per_page` (integer): Items per page (max 100, default 20)

### Get Career Level Details
- **GET** `/api/v1/career-levels/{id}`

## Job Shifts API

### List Job Shifts
- **GET** `/api/v1/job-shifts`
- **Parameters**:
  - `keyword` (string): Search keyword
  - `per_page` (integer): Items per page (max 100, default 20)

### Get Job Shift Details
- **GET** `/api/v1/job-shifts/{id}`

## Functional Areas API

### List Functional Areas
- **GET** `/api/v1/functional-areas`
- **Parameters**:
  - `keyword` (string): Search keyword
  - `per_page` (integer): Items per page (max 100, default 20)

### Get Functional Area Details
- **GET** `/api/v1/functional-areas/{id}`

## Tags API

### List Tags
- **GET** `/api/v1/tags`
- **Parameters**:
  - `keyword` (string): Search keyword
  - `per_page` (integer): Items per page (max 100, default 50)

### Get Tag Details
- **GET** `/api/v1/tags/{id}`

### Get Tag Jobs
- **GET** `/api/v1/tags/{id}/jobs`
- **Parameters**:
  - `per_page` (integer): Items per page (max 50, default 12)
  - `page` (integer): Page number

## Currencies API

### List Currencies
- **GET** `/api/v1/currencies`
- **Parameters**:
  - `keyword` (string): Search keyword
  - `per_page` (integer): Items per page (max 100, default 50)

### Get Currency Details
- **GET** `/api/v1/currencies/{id}`

## Packages API

### List Packages
- **GET** `/api/v1/packages`
- **Parameters**:
  - `keyword` (string): Search keyword
  - `per_page` (integer): Items per page (max 50, default 20)

### Get Package Details
- **GET** `/api/v1/packages/{id}`

## Candidates API

### List Candidates
- **GET** `/api/v1/candidates`
- **Description**: Get public job seeker profiles
- **Parameters**:
  - `keyword` (string): Search keyword
  - `city_id` (integer): Filter by city
  - `state_id` (integer): Filter by state
  - `country_id` (integer): Filter by country
  - `per_page` (integer): Items per page (max 50, default 12)

### Get Candidate Details
- **GET** `/api/v1/candidates/{id}`

### Search Candidates
- **GET** `/api/v1/candidates/search`
- **Parameters**:
  - `q` (string): Search query
  - `limit` (integer): Number of results (max 50, default 10)

## Reviews API

### List Reviews
- **GET** `/api/v1/reviews`
- **Parameters**:
  - `reviewable_type` (string): Type of reviewed item
  - `reviewable_id` (integer): ID of reviewed item
  - `rating` (integer): Filter by rating
  - `per_page` (integer): Items per page (max 50, default 20)

### Get Review Details
- **GET** `/api/v1/reviews/{id}`

### Submit Review 🔒
- **POST** `/api/v1/reviews`
- **Authentication**: Required (Sanctum token)
- **Body**: Review data

## Analytics API

### Job Analytics 🔒
- **GET** `/api/v1/analytics/jobs/{id}`
- **Authentication**: Required (Sanctum token)
- **Parameters**:
  - `period` (integer): Number of days (default 30)

### Company Analytics 🔒
- **GET** `/api/v1/analytics/companies/{id}`
- **Authentication**: Required (Sanctum token)
- **Parameters**:
  - `period` (integer): Number of days (default 30)

## Account Management API 🔒

### Get Profile
- **GET** `/api/v1/account/profile`
- **Authentication**: Required (Sanctum token)
- **Description**: Get authenticated user's profile

### Update Profile
- **PUT** `/api/v1/account/profile`
- **Authentication**: Required (Sanctum token)
- **Body**: Profile data including avatar, resume, cover letter uploads

### Upload Avatar
- **POST** `/api/v1/account/avatar`
- **Authentication**: Required (Sanctum token)
- **Body**: Multipart form with avatar image

### Get Applications
- **GET** `/api/v1/account/applications`
- **Authentication**: Required (Sanctum token)
- **Description**: Get user's job applications
- **Parameters**:
  - `status` (string): Filter by application status
  - `per_page` (integer): Items per page (max 50, default 12)

### Get Application Details
- **GET** `/api/v1/account/applications/{id}`
- **Authentication**: Required (Sanctum token)

### Delete Application
- **DELETE** `/api/v1/account/applications/{id}`
- **Authentication**: Required (Sanctum token)

### Get Saved Jobs
- **GET** `/api/v1/account/saved-jobs`
- **Authentication**: Required (Sanctum token)
- **Parameters**:
  - `per_page` (integer): Items per page (max 50, default 12)

### Save Job
- **POST** `/api/v1/account/saved-jobs/{jobId}`
- **Authentication**: Required (Sanctum token)

### Unsave Job
- **DELETE** `/api/v1/account/saved-jobs/{jobId}`
- **Authentication**: Required (Sanctum token)

### Get Companies (Employers Only)
- **GET** `/api/v1/account/companies`
- **Authentication**: Required (Sanctum token)
- **Description**: Get employer's companies

### Get Jobs (Employers Only)
- **GET** `/api/v1/account/jobs`
- **Authentication**: Required (Sanctum token)
- **Description**: Get employer's posted jobs
- **Parameters**:
  - `per_page` (integer): Items per page (max 50, default 12)

### Create Job (Employers Only)
- **POST** `/api/v1/account/jobs`
- **Authentication**: Required (Sanctum token)
- **Body**: Job data

### Update Job (Employers Only)
- **PUT** `/api/v1/account/jobs/{id}`
- **Authentication**: Required (Sanctum token)
- **Body**: Job data

### Delete Job (Employers Only)
- **DELETE** `/api/v1/account/jobs/{id}`
- **Authentication**: Required (Sanctum token)

### Get Transactions
- **GET** `/api/v1/account/transactions`
- **Authentication**: Required (Sanctum token)
- **Parameters**:
  - `per_page` (integer): Items per page (max 50, default 20)

### Get Invoices
- **GET** `/api/v1/account/invoices`
- **Authentication**: Required (Sanctum token)
- **Parameters**:
  - `per_page` (integer): Items per page (max 50, default 20)

### Get Invoice Details
- **GET** `/api/v1/account/invoices/{id}`
- **Authentication**: Required (Sanctum token)

## Job Applications Management API 🔒

### List Applications (Employers Only)
- **GET** `/api/v1/job-applications`
- **Authentication**: Required (Sanctum token)
- **Description**: Get applications for employer's jobs
- **Parameters**:
  - `job_id` (integer): Filter by job ID
  - `status` (string): Filter by application status
  - `company_id` (integer): Filter by company ID
  - `per_page` (integer): Items per page (max 50, default 20)

### Get Application Details (Employers Only)
- **GET** `/api/v1/job-applications/{id}`
- **Authentication**: Required (Sanctum token)

### Update Application (Employers Only)
- **PUT** `/api/v1/job-applications/{id}`
- **Authentication**: Required (Sanctum token)
- **Body**: Application status update

### Delete Application (Employers Only)
- **DELETE** `/api/v1/job-applications/{id}`
- **Authentication**: Required (Sanctum token)

### Download CV (Employers Only)
- **GET** `/api/v1/job-applications/{id}/download-cv`
- **Authentication**: Required (Sanctum token)
- **Description**: Download applicant's CV/resume

## Location API (if location plugin is active)

### List Countries
- **GET** `/api/v1/locations/countries`
- **Parameters**:
  - `keyword` (string): Search keyword
  - `per_page` (integer): Items per page (max 200, default 50)

### List States
- **GET** `/api/v1/locations/states/{countryId}`
- **Parameters**:
  - `keyword` (string): Search keyword
  - `per_page` (integer): Items per page (max 200, default 50)

### List Cities
- **GET** `/api/v1/locations/cities/{stateId}`
- **Parameters**:
  - `keyword` (string): Search keyword
  - `per_page` (integer): Items per page (max 200, default 50)

## Response Format

All API responses follow this format:

```json
{
  "error": false,
  "message": "Success message",
  "data": {
    // Response data here
  }
}
```

For paginated responses:

```json
{
  "error": false,
  "message": null,
  "data": {
    "data": [
      // Array of items
    ],
    "current_page": 1,
    "per_page": 12,
    "total": 100,
    "last_page": 9,
    // Other pagination metadata
  }
}
```

## Error Responses

Error responses include:

```json
{
  "error": true,
  "message": "Error message",
  "data": null
}
```

Common HTTP status codes:
- `200`: Success
- `400`: Bad Request
- `401`: Unauthorized
- `404`: Not Found
- `422`: Validation Error
- `500`: Internal Server Error
