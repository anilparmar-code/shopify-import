# Shopify CSV Product Importer

A modern, high-performance Laravel application built to seamlessly import, validate, and synchronize CSV product data with Shopify's GraphQL Admin API (version `2026-07`).

---

### [Watch the demo](https://www.loom.com/share/dfd0ba21015f468e9b7afff6d5f84db9)</small>

---

## Tech Stack
- **Backend:** Laravel 13 (PHP 8.4)
- **Frontend:** Vue 3, Inertia.js v3, Tailwind CSS
- **Authentication:** Laravel Fortify (with 2FA & Passkeys support)
- **Wayfinder:** Auto-generated typed TypeScript routes
- **Formatting & Style:** Laravel Pint
- **Testing:** Pest PHP 4

---

## Key Features
1. **Queued CSV Imports:** Asynchronous processing of product catalog imports using Laravel Excel.
2. **Shopify GraphQL Integration:** Uses the modern declarative `productSet` mutation, re-nested physical/measurement attributes, and `@idempotent` inventory level modifications.
3. **Polymorphic System Logging:** robust `error_logs` table (`loggable` morph relation) tracking execution logs and debugging contexts.
4. **Interactive UI Dashboards:**
   - **Uploads:** List and track import states (`pending`, `processing`, `completed`, `failed`).
   - **Import Records:** Deep inspect raw CSV properties and execution logs for individual import records.
   - **Synced Products:** Browse successfully imported local products and check their status/pricing.
   - **System Logs:** Review all logs, warning indicators, and network errors.
5. **Automatic Email Notifications:** Dispatches clean summary emails to uploading users when processing finishes.

---

## Getting Started

### 1. Prerequisites
- **PHP** >= 8.4
- **Composer**
- **Node.js & npm**
- **MySQL** (or any supported relational database)

### 2. Installation
Clone the repository, then run the setup steps:
```bash
# Install PHP dependencies
composer install

# Install NPM dependencies
npm install

# Copy environment configuration
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 3. Shopify API Configuration
Update your `.env` file with your Shopify store credentials and collection target:
```env
SHOPIFY_SHOP_DOMAIN="your-store.myshopify.com"
SHOPIFY_ACCESS_TOKEN="shpat_xxxxxxxxxxxxxxxxxxxxxxxx"
SHOPIFY_COLLECTION_ID="xxxxxxxxxxxxx"
```

### 4. Database Setup & Seed
Run migrations and seed default developer credentials:
```bash
# Run migrations and seed data
php artisan migrate:fresh --seed
```

### 5. Running the Application
Start the local servers:
```bash
# Generate Wayfinder TypeScript routes
php artisan wayfinder:generate
```

```bash
# Run the Laravel dev server
composer run dev
```

```bash
# Run the Laravel dev server
composer run dev
```

### 5. Login Credentials
#### Email: admin@shopify-import.com
#### Password: password

---

### Running Tests
Execute the Pest test suite to verify the importer flow and integrations:
```bash
vendor/bin/pest
```


