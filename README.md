# MVD Intake Assignment - Property Bidding System

Een Symfony applicatie voor het plaatsen van biedingen op woningen met integratie van de Moving Digital API.

## Functionaliteiten

- **Bod Formulier**: Bezoekers kunnen een bod plaatsen met naam, email, telefoonnummer, bod en voorwaarden
- **Status Pagina**: Real-time status updates van biedingen
- **API Integratie**: Automatische verzending naar Moving Digital API
- **Admin Dashboard**: Overzicht van alle biedingen
- **Background Processing**: Status updates via console command

## Technische Architectuur

### SOLID Principes
- **Single Responsibility**: Elke service heeft één verantwoordelijkheid
- **Open/Closed**: Services zijn uitbreidbaar zonder wijziging
- **Liskov Substitution**: Interfaces kunnen worden vervangen
- **Interface Segregation**: Kleine, specifieke interfaces
- **Dependency Inversion**: Afhankelijkheden worden geïnjecteerd

### Design Patterns
- **Repository Pattern**: Data access abstraction
- **Service Layer**: Business logic separation
- **Command Pattern**: Console commands voor background tasks
- **Observer Pattern**: Event-driven status updates

## Installatie

### Vereisten
- PHP 8.1+
- Composer
- MySQL/MariaDB
- Symfony CLI (optioneel)

### Stappen

1. **Dependencies installeren**
   ```bash
   composer install
   ```

2. **Environment configureren**
   ```bash
   cp .env .env.local
   ```
   
   Pas de database URL aan in `.env.local`:
   ```
   DATABASE_URL=mysql://username:password@127.0.0.1:3306/database_name
   ```

3. **Database setup**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

4. **Server starten**
   ```bash
   php bin/console server:start
   ```

## API Configuratie

De Moving Digital API credentials zijn al geconfigureerd in `.env`:

```env
MOVING_CLIENT_ID=f2be12f4c8a6f8a0b470a48a7879d13e
MOVING_CLIENT_SECRET=7889ec9b6b0f4a15d31411fbbbfc111bd1c57c538ae20a69d8546424f11ccbfb51de300e90c4430b59ebf4b2ce0cb2c11e7a904ab8e85596d3651d1c03d30fa9
MOVING_API_BASE=https://devcase.moving.digital
```

## Gebruik

### Biedingen plaatsen
1. Ga naar de hoofdpagina: `http://localhost:8000`
2. Vul het formulier in
3. Bekijk de statuspagina

### Admin Dashboard
- Ga naar: `http://localhost:8000/admin/offers`
- Bekijk alle biedingen en statistieken

### Status Updates
```bash
# Handmatig status updates uitvoeren
php bin/console app:update-offer-status

# Automatisch elke 5 minuten (cron job)
*/5 * * * * php /path/to/project/bin/console app:update-offer-status
```

## API Endpoints

### Publiek
- `GET /` - Bod formulier
- `GET /status/{id}` - Status pagina
- `GET /api/status/{id}` - JSON status API

### Admin
- `GET /admin/offers` - Alle biedingen overzicht

## Database Schema

### Offers Table
```sql
CREATE TABLE offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    external_id VARCHAR(255) DEFAULT NULL,
    property_id VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    conditions LONGTEXT DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    meta JSON DEFAULT NULL
);
```

## Status Flow

1. **pending** - Bod is geplaatst, wacht op API response
2. **accepted** - Bod is geaccepteerd door Moving Digital
3. **rejected** - Bod is afgewezen door Moving Digital
4. **api_error** - Fout bij API communicatie

## Error Handling

- Form validatie met Symfony Validator
- API error handling met logging
- Graceful degradation bij API failures
- User-friendly error messages

## Logging

Alle API communicatie wordt gelogd in:
- `var/log/dev.log` (development)
- `var/log/prod.log` (production)

## Testing

```bash
# Unit tests
php bin/phpunit

# Functional tests
php bin/phpunit tests/Functional/
```

## Deployment

### Production Checklist
- [ ] Environment variables configureren
- [ ] Database migraties uitvoeren
- [ ] Cache clearen: `php bin/console cache:clear --env=prod`
- [ ] Assets compileren: `php bin/console assets:install --env=prod`
- [ ] Cron job instellen voor status updates
- [ ] Logging configureren
- [ ] SSL certificaat installeren

### Docker Support
```bash
# Start met Docker Compose
docker-compose up -d

# Database migraties
docker-compose exec app php bin/console doctrine:migrations:migrate
```

## Troubleshooting

### Database Connection Issues
```bash
# Test database connectie
php bin/console doctrine:database:create --if-not-exists
```

### API Issues
```bash
# Test API connectie
php bin/console app:update-offer-status -v
```

### Cache Issues
```bash
# Clear cache
php bin/console cache:clear
```

## Support

Voor vragen of problemen, neem contact op met het development team.
