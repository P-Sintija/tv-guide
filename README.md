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
