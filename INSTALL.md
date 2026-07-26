# 🚀 BrewMo 2.0 - Installations- og Testvejledning

Denne guide beskriver, hvordan du installerer **BrewMo 2.0** i Dolibarr ERP/CRM samt hvordan du opsætter og tester modulet lokalt via Docker eller i et eksisterende Dolibarr-miljø.

---

## 📋 Forudsætninger
- **PHP**: 8.1 eller nyere med `pdo_mysql`, `mbstring` og `json` extensions.
- **Dolibarr ERP/CRM**: Version 16.0, 17.0, 18.0 eller 19.0.
- **Composer**: Til afvikling af Autoloading og PHPUnit.
- **Docker & Docker Compose** *(valgfrit - til hurtig lokal test)*.

---

## 🛠️ Mulighed 1: Installation i eksisterende Dolibarr (Manuelt)

### Trin 1: Placer modulet i Dolibarr `custom` mappen
Klon eller kopier modulet ind i Dolibarrs `htdocs/custom/` directory:

```bash
cd /sti/til/dolibarr/htdocs/custom/
git clone -b v2.0 https://github.com/smoeberg/brewmo.git brewmo
cd brewmo
composer install --no-dev
```

### Trin 2: Kør database migrations (SQL)
Importér v2.0 database-strukturen i din Dolibarr MySQL/MariaDB database:

```bash
mysql -u [db_user] -p [db_name] < migrations/brewmo_v2.sql
```

### Trin 3: Aktiver modulet i Dolibarr UI
1. Log ind på din Dolibarr som Administrator.
2. Gå til **Hovedmenu → Opsætning (Setup) → Moduler/Applikationer (Modules/Applications)**.
3. Søg efter **BrewMo** under fanen *Interface/Produktion*.
4. Slå modulet **TIL** (On).

---

## 🐳 Mulighed 2: Lokal test via Docker (Hurtigstart)

Vi har inkluderet et færdigt Docker-miljø med MariaDB og PHP/Apache.

### Trin 1: Start Docker-containerne
```bash
make up
# ELLER: docker-compose up -d
```

### Trin 2: Installer afhængigheder & kør migrations
```bash
make install
```

Dolibarr / BrewMo vil derefter være tilgængeligt på: `http://localhost:8080`

---

## 🧪 Kørsel af Automatiseret Testsuite (PHPUnit & Static Analysis)

For at verificere at alle domænelag, repositories, Value Objects og REST-endepunkter fungerer 100% fejlfrit:

```bash
# Kør alle enhedstests med PHPUnit
vendor/bin/phpunit

# Kør statisk kodediagnostik (PHPStan)
vendor/bin/phpstan analyse -c phpstan.neon

# Kør Psalm kodediagnostik
vendor/bin/psalm
```

---

## 📡 Test af REST API (v2)

### 1. Test Oprettelse af Brygsession (`POST /custom/brewmo/api/v2/BrewSessionController.php`)
**Request Body:**
```json
{
  "ref": "BREW-2026-TEST",
  "title": "IPA Batch #1",
  "recipe_id": 1,
  "planned_volume": 1000.0
}
```

### 2. Test Ingestion af IoT Sensor Data (`POST /custom/brewmo/api/v2/qc_ingest.php`)
**Request Body:**
```json
{
  "brew_session_id": 1,
  "measurement_type": "GRAVITY",
  "value": 1.052,
  "unit": "SG",
  "recorded_by": "Plaato Pro Sensor #4"
}
```
