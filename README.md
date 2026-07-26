# BrewMo - Brewery Management Module for Dolibarr

BrewMo is a specialized brewery management extension for [Dolibarr ERP/CRM](https://www.dolibarr.org/). It bridges commercial brewing requirements (recipes, brew sessions, tank management, packaging, batch traceability, and MRP/equipment planning) with Dolibarr's core business workflows.

---

## 📋 Overview & Vision

BrewMo leverages Dolibarr as a robust ERP foundation for standard operations (invoicing, customer management, inventory baselines) while introducing dedicated modules designed for craft and commercial breweries:

- **Recipe Builder & Calculations:** Gravity (OG/FG), ABV, IBU, EBC, strike water, mash profiles, and ingredient scaling (`BrewCalc`).
- **Brew Session Management:** Planning, mashing, fermentation tracking, vessel assignments, and real-time realization.
- **Vessel & Equipment Management:** Tank tracking, occupancy logs, cleaning/CIP status, and equipment allocation.
- **Packaging & Labeling:** Packaging lines, kegging/bottling runs, batch label generation, and stock transfers.
- **MRP & Capacity Planning:** Ingredient demand calculation based on scheduled brew sessions and stock availability.

---

## 🏗 Repository Structure

```
brewmo/
├── admin/          # Admin setup & module parameters (vessels, packaging lines, equipment)
├── api/            # API endpoints (MRP calculations, recipe math)
├── class/          # Core PHP class models (BrewSession, Recipe, Tank, Packaging, BrewCalc, etc.)
├── core/           # Dolibarr module configuration (modBrewmo.class.php) & event triggers
├── langs/          # Internationalization files (da_DK, en_US)
├── migrations/     # Database schemas & SQL migration scripts
├── tools/          # Maintenance scripts & utility SQL queries
└── www/            # Frontend interfaces (cards, lists, calendar, occupancy, recipe builder)
```

---

## 🚀 Installation & Setup

1. **Clone into Dolibarr Custom Modules Directory:**
   ```bash
   cd /path/to/dolibarr/htdocs/custom
   git clone https://github.com/smoeberg/brewmo.git brewmo
   ```

2. **Database Setup:**
   Run the schema initialization scripts found in `migrations/`:
   ```bash
   mysql -u dolibarr_user -p dolibarr_db < htdocs/custom/brewmo/migrations/install.sql
   ```

3. **Enable Module:**
   - Log into Dolibarr as an Administrator.
   - Navigate to **Home > Setup > Modules/Applications**.
   - Search for **BrewMo** under custom modules and click **Enable**.
   - Configure parameters under **Setup** (Vessels, Packaging Lines, Equipment Definitions).

---

## 🧭 BrewMo 2.0 Roadmap & Architecture Evolution

BrewMo is currently transitioning toward **BrewMo 2.0**, focusing on domain-driven design, enhanced security, test automation, and robust batch traceability.

### Key Objectives for 2.0:
* **Domain-Driven Design (DDD):** Clean separation between Domain Logic, Application Use Cases, Infrastructure, and UI layers.
* **State Machine Workflow:** Strict transitions for brew sessions (`Draft` → `Planned` → `Mashing` → `Boiling` → `Fermenting` → `Packaging` → `Finished`).
* **Full Batch Traceability:** Lot-level tracking from raw material intake through brew kettles, fermentation tanks, packaging, and end-customer delivery.
* **Dolibarr Core Integration:** Automated bi-directional stock synchronization with `llx_stock` and order reservation triggers.
* **IoT & Real-time Monitoring:** WebSockets / MQTT integration for temperature, gravity, and tank pressure sensors.
* **Mobile Operations:** Mobile-first app support for barcode scanning, keg tracking, and floor management.

---

## 📄 License

GPL-3.0 or later (aligned with Dolibarr licensing).
