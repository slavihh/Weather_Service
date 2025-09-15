# Weather Service

A Symfony-based weather service that fetches temperatures from an external provider (e.g. OpenWeather), caches them, and persists history in PostgreSQL.

---

## Run Locally with Docker Compose

### 1. Clone the repository
```bash
git clone https://github.com/your-username/weather-service.git
cd weather-service
```

### 2. Copy .env.dev.local
```
cp .env .env.dev.local
```
Edit .env.dev.local and set your values.

### 3. Start services
```
docker compose up -d --build
```
### 4. Create database and run database migrations
```
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction
```

## Architecture

### Components

- **Controller** (`WeatherController`)  
  Defines the `/weather` endpoint. Accepts query parameters `city` and `country`, delegates logic to the service, and returns a JSON response.

- **Service** (`WeatherService`)  
  Orchestrates fetching weather data:
    1. Tries cache (fastest).
    2. Falls back to database history (if today's temperature is already stored).
    3. Calls the external provider (OpenWeather) if no fresh data is available.  
       Stores new results into both **cache** and **database**.

- **Provider** (`OpenWeatherProvider`)  
  Implements `ExternalProviderInterface`.  
  Responsible for calling the external OpenWeather API and mapping responses into `ProviderResponse`.  
  Handles network exceptions and non-200 responses gracefully.

- **Repository** (`WeatherHistoryRepository`)  
  Handles persistence of historical temperature records.  
  Provides helpers to:
    - Save a new record (`record`)
    - Get last N records for a city (`findLastForCity`)
    - Get today’s record if it exists (`findForToday`)

- **Entity** (`WeatherHistory`)  
  Doctrine ORM entity representing a temperature record.  
  Fields: `id`, `city`, `countryCode`, `temperature`, `recordedAt`.

- **Custom DTO** (`ProviderResponse`)  
  Simple object that represents an API provider response.  
  Contains temperature, success flag, status code, and raw JSON response.

---

## API Endpoint

### `GET /weather`

Fetches the current temperature for a given city and country code.

#### Query Parameters
- `city` (**string**, required): City name (e.g. `Sofia`)
- `country` (**string**, required): Two-letter country code (ISO 3166, e.g. `BG`)

#### Example Request
```bash
curl "http://localhost:8080/weather?city=Sofia&country=BG"
