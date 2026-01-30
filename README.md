#  Superstack Integrators - For Wordpress, Airtable and Vue

## Overview

This repository implements a super-stack integration strategy for WordPress, Airtable, and Vue.

The core philosophy is spiritual integration over physical integration: instead of building monolithic systems or duplicating existing services, we compose proven platforms through explicit integration layers.

This approach prioritises rapid development, lower cost, and experimentation. By piggybacking on mature tools and integrating them cleanly, small teams can validate ideas faster without committing to heavy infrastructure upfront.

## Rough Layout

portal -> data acceptance -> cms -> data caching -> site -> html caching -> seo


## 🧊 Data and Page

A **Vue-based caching service** for websites built with **Vue + Airtable**, designed to improve **performance, SEO, and stability** by caching both **API data** and **fully rendered HTML pages**.

This project is **not the website itself** — it is a **cache management service** that lives *alongside* the target site inside the same webroot.

---

### 🧠 Architecture Overview

```
/webroot
  /            → Target website (Vue + Airtable)
  /integrator      → This caching service (Vue UI + PHP cache engine)
```

* The **target website** lives at the webroot (`/`)
* The **caching service** lives in `/integrator`
* The cacher **reads and writes files into the parent directory**
* Same-origin is assumed (full control of the site)

This allows the service to:

* Fetch live Vue-rendered pages
* Save static HTML directly into the site
* Proxy and cache Airtable API requests safely

---

### 🔹 What This Service Does

The system manages **five cache strategies**, split across **data caching** and **HTML caching**.

---

### 📦 DATA CACHING

#### 1️⃣ Data – Simple Data Cache

A **generic HTTP proxy cache** used primarily for Airtable API calls.

**Features**

* URL + method–based caching
* Supports GET, POST, PUT, DELETE
* Optional forced regeneration
* Header passthrough
* OAuth support
* Attachment/image proxying

**Use cases**

* Avoid Airtable rate limits
* Cache image attachments
* Speed up repeated API calls

---

#### 2️⃣ Data – Page Binder Cache

A **dataset compiler** for Airtable.

**What it does**

* Fetches *all paginated pages* from Airtable
* Merges them into **one JSON file**
* Stores compile metadata (record count, duration, source URL)
* Optionally pre-caches attachments via the data proxy

**Use cases**

* Large Airtable tables (100–10k+ records)
* Predictable, fast-loading datasets
* Reduced client-side pagination logic

---

### 🧊 HTML CACHING (STATIC FREEZING)

All HTML caching is done by:

1. Rendering the Vue page in an iframe
2. Extracting the final DOM
3. Writing `/slug/index.html` into the **parent webroot**

---

#### 3️⃣ HTML – Cache from URL

* Cache a single Vue route
* Saves rendered HTML to disk
* Cleans output by removing:

  * JSON-LD
  * canonical links
  * OG / Twitter meta
  * Google Tag Manager

**Use cases**

* SEO-critical landing pages
* Manual page freezing

---

#### 4️⃣ HTML – Cache from List

* Uses a predefined `pages.json`
* Allows multi-select
* Cache or delete pages in bulk
* Homepage handled with backup/restore logic

**Use cases**

* Controlled, explicit page caching
* Editorial or curated sites

---

#### 5️⃣ HTML – Cache from Sitemap

* Reads sitemap URLs from `sitemaps.json`
* Supports:

  * Normal sitemaps (`<urlset>`)
  * Sitemap indexes (`<sitemapindex>`)
* Sitemap indexes expand into **sub-sitemap tabs**
* URLs are:

  * Stripped of protocol + domain
  * Treated identically to `pages.json` entries
  * Grouped into batches of 10
  * Selectable per-group or globally

**Use cases**

* SEO-driven caching
* Large dynamic catalogs
* Align cached pages with real crawl structure

---

### ⚙️ Configuration Files

#### `pages.json`

```json
[
  "/all-artworks/fine-art/all-price-ranges/",
  "/all-artists/all-media/"
]
```

---

#### `sitemaps.json`

```json
{
  "sitemaps": [
    "https://example.com/sitemaps/sitemap-index.xml"
  ]
}
```

> Sitemap fetching is done **on-demand** when a sitemap tab is opened.

---

### 🧩 UI Stack

* Vue (Options API)
* Quasar UI components
* Utility classes / inline styles only
* Modular design (sitemap list handled via subcomponent)

Main UI components:

* `DataCacheBinder.vue`
* `HtmlCachePages.vue`
* `HtmlCachePageHelper.vue`

---

### 🔐 Assumptions & Constraints

* Full control of the target website
* Same-origin required (iframes + file writes)
* HTML caching tested on staging/production (not local)
* Apache/Nginx configured to allow PHP execution in `/integrator`

---

### ❌ What This Is NOT

* Not SSR
* Not a CDN
* Not a headless CMS
* Not framework-specific beyond Vue

This is a **pragmatic, site-owned caching layer**.

---

### 🎯 Why This Exists

* Vue SPAs are fast — but invisible to crawlers
* Airtable is flexible — but rate-limited
* This service bridges the gap **without changing your app architecture**




## Data Acceptance (IRIS — Ivan’s Robust Integration System)

### Philosophy

**IRIS is a relationship-first integration system.**

Most integrations sync records.
Very few sync **relationships** — even though relationships are a **critical** part of any real integration.

IRIS exists to treat relationships as first-class concerns.

---

#### What IRIS Does

**Relationship Syncing (Primary)**
IRIS syncs relationships by assigning entities a stable internal identity and translating foreign keys between systems at sync time, rather than leaking IDs across schemas.
This allows each system to use its own native identifiers while relationships remain correct.

**Revision Tracking (Secondary)**
Because entities have stable identity, IRIS can diff changes, commit them, and maintain a revision history.
This capability naturally emerges from the same foundation used for relationship syncing.

**Intervention Mode (Optional)**
IRIS can require **moderator approval** before changes are committed, enabling controlled, review-based integrations.

---

#### Notes

* IRIS also supports **image and media syncing** (adapter-specific where necessary)
* The system is **target- and source-agnostic**, where practical
