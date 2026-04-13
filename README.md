# TV Guide
A Laravel application powered by Laravel Sail.

---

## Setup

### Clone the Repository
```bash
git clone https://github.com/P-Sintija/tv-guide.git
cd tv-guide
```

### Environment Setup
Copy the environment file:
```bash
cp .env.example .env
```
Update the database configuration in `.env`:
```bash
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```
Add authentication credentials (used for *POST /api/guide*) in `.env`:
```bash
BASIC_AUTH_USERNAME=
BASIC_AUTH_PASSWORD=
```

### Install Dependencies
```bash
composer install
```

### Generate Application Key
```bash
php artisan key:generate
```

### Start the Application
Make sure Docker is running, then start Laravel Sail:
```bash
./vendor/bin/sail up
```
Run database migrations
```bash
./vendor/bin/sail artisan migrate
```

### Stop the Application
```bash
./vendor/bin/sail down
```

### Running Tests
```bash
./vendor/bin/sail artisan test
```

## API Endpoints

### GET `/api/guide/{channel_nr}/{date}`
Returns TV schedule for a given channel and date.

|Parameter|Type|Description|
| :----------- |:--------------| :-------------|
|`channel_nr`|integer|Channel number (1–3)|
|`date`|string|Date in format `YYYY-MM-DD`|

#### Example Request
```bash
GET /api/guide/1/2024-01-01
```
#### Example Response
```json
{
    "data": [
        {
            "id": 2,
            "title": "Panorāma",
            "channel_nr": 1,
            "starts_at": "2024-01-01 20:00:00",
            "ends_at": "2024-01-01 20:36:00",
            "adjusted_ends_at": "2024-01-01 20:37:00"
        },
        {
            "id": 1,
            "title": "Šodienas jautājums",
            "channel_nr": 1,
            "starts_at": "2024-01-01 20:37:00",
            "ends_at": "2024-01-01 20:56:00",
            "adjusted_ends_at": "2024-01-01 20:56:10"
        },
        {
            "id": 3,
            "title": "Sporta ziņas",
            "channel_nr": 1,
            "starts_at": "2024-01-01 20:56:10",
            "ends_at": "2024-01-01 21:02:00",
            "adjusted_ends_at": "2024-01-01 21:02:00"
        }
    ]
}
```
#### Example Validation Error
```json
{
    "message": "The date field must match the format Y-m-d.",
    "errors": {
        "date": [
            "The date field must match the format Y-m-d."
        ]
    }
}
```

### GET `/api/on-air/{channel_nr}`
Returns the currently airing program for the given channel. If no program is currently airing, a `204 No Content` response is returned.

|Parameter|Type|Description|
| :----------- |:--------------| :-------------|
|`channel_nr`|integer|Channel number (1–3)|

#### Example Request
```bash
GET /api/on-air/1
```
#### Example Response
```json
{
    "data": {
        "id": 1,
        "title": "Panorāma",
        "channel_nr": 1,
        "starts_at": "2024-01-01 20:00:00",
        "ends_at": "2024-01-01 20:36:00",
        "adjusted_ends_at": "2024-01-01 20:37:00"
    }
}
```

### GET `/api/upcoming/{channel_nr}`
Returns the next 10 upcoming programs for the given channel, including the program that is currently on air (if any).

|Parameter|Type|Description|
| :----------- |:--------------| :-------------|
|`channel_nr`|integer|Channel number (1–3)|

#### Example Request
```bash
GET /api/upcoming/1
```
#### Example Response
```json
{
    "data": [
        {
            "id": 2,
            "title": "Panorāma",
            "channel_nr": 1,
            "starts_at": "2024-01-01 20:00:00",
            "ends_at": "2024-01-01 20:36:00",
            "adjusted_ends_at": "2024-01-01 20:37:00"
        },
        {
            "id": 1,
            "title": "Šodienas jautājums",
            "channel_nr": 1,
            "starts_at": "2024-01-01 20:37:00",
            "ends_at": "2024-01-01 20:56:00",
            "adjusted_ends_at": "2024-01-01 20:56:10"
        },
        {
            "id": 3,
            "title": "Sporta ziņas",
            "channel_nr": 1,
            "starts_at": "2024-01-01 20:56:10",
            "ends_at": "2024-01-01 21:02:00",
            "adjusted_ends_at": "2024-01-01 21:02:00"
        },
        ...
    ]
}
```

### POST `/api/guide`
Creates a new TV program entry. This endpoint is protected by **Basic Authentication**, [Credentials are defined in `.env`](#environment-setup):

#### Request Body
|Parameter|Type|Description|
| :----------- |:--------------| :-------------|
|`title`|string|Program title (max 100 characters)|
|`channel_nr`|integer|Channel number (1–3)|
|`starts_at`|string|Start datetime (`YYYY-MM-DD HH:mm:ss`) |
|`ends_at`|string|End datetime (`YYYY-MM-DD HH:mm:ss`, must be after `starts_at`)|

#### Request Example
```bash
POST /api/guide
Content-Type: application/json
Authorization: Basic YWRtaW46cGFzc3dvcmQ=
{
    "title": "Rīta Panorāma",
    "channel_nr": 1,
    "starts_at": "2024-01-01 06:30:00",
    "ends_at": "2024-01-01 08:35:00"
}
```
#### Response Example
```json
{
    "data": {
        "id": 4,
        "title": "Rīta Panorāma",
        "channel_nr": 1,
        "starts_at": "2024-01-01 06:30:00",
        "ends_at": "2024-01-01 08:35:00",
        "adjusted_ends_at": "2024-01-01 20:00:00"
    }
}
```
#### Example Validation Error
```json
{
    "message": "The given time range overlaps with an existing guide for this channel.",
    "errors": {
        "ends_at": [
            "The given time range overlaps with an existing guide for this channel."
        ]
    }
}
```
#### Example Unauthorized Response
```json
{
    "error": "Unauthorized"
}
```