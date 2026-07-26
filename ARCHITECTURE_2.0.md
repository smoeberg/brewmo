# BrewMo 2.0 Architectural & Functional Blueprint

This document outlines the full functional mapping, architectural principles, and implementation strategy to ensure BrewMo supports every capability required by commercial breweries while taking full advantage of standard Dolibarr ERP/CRM functionality.

---

## 🏛 1. Core Architectural Strategy

BrewMo is designed as a **native Dolibarr custom module** extended with a clean, domain-driven architecture (**DDD**). 

### Separation of Responsibilities
* **Standard Dolibarr Core:** Manages generic business functions—Invoicing, Payments, General Ledger Accounting, Standard CRM, Customers/Suppliers (Thirdparties), Commercial Quotes, Standard Stock Control, and Core User Permissions.
* **BrewMo 2.0 Extension:** Handles all brewery-specific domain logic—Brew Workflows (State Machine), Recipe Engine, Batch Traceability (Lot/UUID), Fermentation & Quality Control, Tank/Equipment Occupancy & Maintenance, Packaging Line Operations, Keg/Container Tracking, IoT Sensor Ingestion, Route Optimization, and Brewery Integrations.

---

## 🗺 2. Complete Functional Mapping & Handling Guide

Below is the definitive breakdown of how every required feature is handled across BrewMo and Dolibarr.

### A. Production & Quality Control (`brewmo_production`, `brewmo_qc`)

| Feature Requirement | Primary Handling Engine | Technical Implementation Details |
| :--- | :--- | :--- |
| **End-to-End Brew Workflow** | BrewMo Module | Implemented using a strict **State Machine** (`Draft` → `Planned` → `Mashing` → `Boiling` → `Fermenting` → `Packaging` → `Completed`). State changes trigger automated stock reservations and movement logs. |
| **Batch Traceability & Lot Tracking** | BrewMo + Dolibarr | BrewMo extends Dolibarr’s `llx_product_lot` table with Lot/UUID lineage connecting raw ingredients (hops, malt, yeast batches) to vessel logs and packaging outputs. |
| **Fermentation & Quality Control Logs** | BrewMo Module | Custom tables `llx_brew_qc_log` recording gravity (OG/SG/FG), pH, temperature, dissolved oxygen, and sensory ratings. Includes target vs. actual variance reporting. |
| **Gyle & Batch Scheduling / Calendar** | BrewMo Module | Interactive Gantt and vessel scheduling calendar built on FullCalendar UI, mapping tank occupancy and brewing equipment allocation. |
| **Yeast Generation & Pitch Management** | BrewMo Module | Dedicated `YeastBatch` entity tracking yeast strain generations, repitching logs, viability %, mutation counts, and serial cost allocation. |
| **Clean-In-Place (CIP) & Maintenance** | BrewMo Module | Equipment log for CIP cycles, chemical consumption, maintenance schedules, and sanitary validation status. |
| **Automated IoT Sensor Ingestion** | BrewMo API | REST/MQTT endpoint endpoints (`/api/v1/sensors/readings`) for automated ingestion from Plaato Pro, TankNet, Tilt, etc. |
| **Mobile Floor App (Scanning)** | BrewMo Mobile API | RESTful endpoints providing JSON endpoints for barcode/QR scanning of raw ingredient lots and tank transfers. |

---

### B. Sales, Order Processing & CRM (`dolibarr_crm`, `brewmo_sales`)

| Feature Requirement | Primary Handling Engine | Technical Implementation Details |
| :--- | :--- | :--- |
| **Customer Orders, Invoices & Payments** | Dolibarr Core | Standard Dolibarr `llx_commande`, `llx_facture`, and payment modules. |
| **Lead Tracking & Sales Pipelines** | Dolibarr Core | Standard Dolibarr Lead & Opportunity management (`llx_c_lead_status`). |
| **Custom Customer Fields** | Dolibarr Core | Utilizes Dolibarr’s native Extrafields feature on Thirdparties (`llx_societe_extrafields`). |
| **Customer Order History & Stock Availability** | Dolibarr Core + BrewMo | Dolibarr core shows commercial history; BrewMo adds real-time available-to-promise (ATP) inventory based on batch status. |
| **Tiered & Customer-Specific Pricing** | Dolibarr Core | Native Dolibarr Multi-Price / Customer Pricing Rules per product. |
| **Ecommerce / POS Order Import** | BrewMo Integrations | Middleware importers mapping Shopify, WooCommerce, SIBA Beerflex, and POS orders directly into Dolibarr sales orders with batch allocation. |
| **Automated Sales Forecasting** | BrewMo Analytics | Historical consumption trend algorithms forecasting ingredient demand and production scheduling. |

---

### C. Distribution & Logistics (`brewmo_logistics`)

| Feature Requirement | Primary Handling Engine | Technical Implementation Details |
| :--- | :--- | :--- |
| **Delivery Route Optimization** | BrewMo Module | Integration with routing algorithms (OR-Tools API) to sort delivery stops based on vehicle capacities and distance. |
| **Delivery Zones & Schedule Days** | BrewMo Module | Geofencing rules mapping customer postal codes to specific delivery day schedules. |
| **Tracked Keg / Container Assignment** | BrewMo Module | QR/Barcode scanning assigning specific physical container IDs (`llx_brew_container`) to outbound delivery notes (`llx_expedition`). |
| **Mobile Delivery App (POD, Signatures)** | BrewMo Mobile API | Endpoints capturing digital signatures, GPS timestamps, proof-of-delivery photos, and instant status sync. |
| **Delivery Manifests & Pick Lists** | BrewMo + Dolibarr | Customized PDF generation via Dolibarr's PDF engine (TCPDF) incorporating batch/lot numbers and container IDs. |
| **Carrier Integrations & CSV Exports** | BrewMo Module | Direct API / CSV export routines for PostNord, DHL, FedEx, and local freight carriers. |

---

### D. Keg & Container Management (`brewmo_containers`)

| Feature Requirement | Primary Handling Engine | Technical Implementation Details |
| :--- | :--- | :--- |
| **Keg & Cask Asset Tracking** | BrewMo Module | Unique ID tracking for stainless steel kegs, plastic keykegs, and casks (`llx_brew_container`). |
| **Location & Trade Tracking** | BrewMo Module | Tracks physical location: `In Brewery`, `At Customer (Trade)`, `In Transit`, or `Lost/Maintenance`. |
| **Beer Freshness Monitoring** | BrewMo Module | Calculates real-time age and freshness windows based on racking date and style stability curves. |
| **Container Collection Planning** | BrewMo Module | Integrates empty keg pickup requests directly into outbound delivery routes. |

---

### E. Private B2B Customer Portal & Webshop (`brewmo_portal`)

| Feature Requirement | Primary Handling Engine | Technical Implementation Details |
| :--- | :--- | :--- |
| **Branded B2B Webshop Frontend** | BrewMo External Portal | Lightweight web portal / headless UI interacting with BrewMo via authenticated REST API. |
| **Invite-Only / Private Access** | BrewMo Module | Whitelisted customer login tied directly to Dolibarr Thirdparty contacts. |
| **Dynamic Customer Pricing & Credit Check** | Dolibarr Core + BrewMo | Real-time query checking Dolibarr outstanding balances against credit limits prior to order submission. |
| **Online Payment Gateway** | BrewMo Portal | Integrated Stripe Checkout / Payment Intent handling for instant invoice settlement. |

---

### F. Advanced Reporting & Business Intelligence (`brewmo_bi`)

| Feature Requirement | Primary Handling Engine | Technical Implementation Details |
| :--- | :--- | :--- |
| **Brewery KPI Dashboards** | BrewMo Module | Real-time widgets for Overall Equipment Effectiveness (OEE), extract yield efficiency, loss ratios, and tank turnover. |
| **Custom Report Builder & Scheduled Emails** | BrewMo Module | Cron-backed scheduled report generator sending PDF/Excel summaries via Dolibarr mail routines. |

---

## 🛠 3. Execution Roadmap for BrewMo 2.0

### Phase 1: Core Architecture & Foundations (Months 1–3)
- [x] Refactor module directory structure into Domain, Application, and Infrastructure layers.
- [ ] Implement `BrewSession` State Machine (`Draft` → `Planned` → `Mashing` → `Boiling` → `Fermenting` → `Packaging` → `Completed`).
- [ ] Establish `Batch/Lot` tracking schema linked with Dolibarr stock triggers.
- [ ] Develop REST API endpoints for external integrations.

### Phase 2: Production Planning, QC & Containers (Months 4–6)
- [ ] Build FullCalendar tank/vessel occupancy scheduler.
- [ ] Implement Quality Control (`QCLog`) and IoT sensor ingestion endpoints.
- [ ] Implement Keg/Container tracking (`llx_brew_container`) with QR barcode support.

### Phase 3: Logistics, B2B Portal & Mobile APIs (Months 7–9)
- [ ] Develop delivery route management and carrier export routines.
- [ ] Build external B2B portal interface with Stripe payment integration.
- [ ] Provide mobile app endpoints for stock scanning and Proof of Delivery.

---

## 🔒 4. Hosting, Security & Compliance
- **Authentication & Security:** Utilizes Dolibarr's native session handling and API token authentication, supporting 2FA via standard Dolibarr security plugins.
- **Data Integrity:** Database transactions ensure that brew sessions, inventory movements, and financial postings execute atomically.
- **Licensing:** Released under GPL-3.0 or later, maintaining full compliance with Dolibarr open-source licensing.
