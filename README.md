# Ivan's Jamstack and Portal Integrator - For Wordpress, Airtable and Vue

## General

### UI and Code

This project is built around a shared UI and helper layer that all integration tools plug into, rather than isolated sub-projects. A single Vue-based interface pattern is reused across features (fetching, inspecting, approving data), while PHP helpers provide common infrastructure for API requests, configuration, and persistence. New capabilities extend the system by adding focused helpers (e.g. database interaction) without re-implementing UI or transport logic. This keeps the system cohesive, extensible, and fast to evolve as new integration concerns are introduced.

### Overview

This repository implements a super-stack integration strategy for WordPress, Airtable, and Vue.  
The core philosophy is spiritual integration over physical integration: instead of building monolithic systems or duplicating existing services, we compose proven platforms through explicit integration layers.  
This approach prioritises rapid development, lower cost, and experimentation. By piggybacking on mature tools and integrating them cleanly, small teams can validate ideas faster without committing to heavy infrastructure upfront.

### Rough Layout

portal -> data acceptance -> cms -> data caching -> site -> html caching -> seo

## Jamstack Intergration (JSON and HTML caching)

A **Vue-based caching service** for websites built with **Vue + Airtable**, designed to improve **performance, SEO, and stability** by caching both **API data** and **fully rendered HTML pages**.

This project is **not the website itself** — it is a **cache management service** that lives _alongside_ the target site inside the same webroot.

---

### 🧠 Architecture Overview

```
/webroot
  /            → Target website (Vue + Airtable)
  /integrator      → This caching service (Vue UI + PHP cache engine)
```

- The **target website** lives at the webroot (`/`)
- The **caching service** lives in `/integrator`
- The cacher **reads and writes files into the parent directory**
- Same-origin is assumed (full control of the site)

This allows the service to:

- Fetch live Vue-rendered pages
- Save static HTML directly into the site
- Proxy and cache Airtable API requests safely

---

### 🔹 What This Service Does

The system manages **five cache strategies**, split across **data caching** and **HTML caching**.

---

### 📦 DATA CACHING

#### 1️⃣ Data – Simple Data Cache

A **generic HTTP proxy cache** used primarily for Airtable API calls.

**Features**

- URL + method–based caching
- Supports GET, POST, PUT, DELETE
- Optional forced regeneration
- Header passthrough
- OAuth support
- Attachment/image proxying

**Use cases**

- Avoid Airtable rate limits
- Cache image attachments
- Speed up repeated API calls

---

#### 2️⃣ Data – Page Binder Cache

A **dataset compiler** for Airtable.

**What it does**

- Fetches _all paginated pages_ from Airtable
- Merges them into **one JSON file**
- Stores compile metadata (record count, duration, source URL)
- Optionally pre-caches attachments via the data proxy

**Use cases**

- Large Airtable tables (100–10k+ records)
- Predictable, fast-loading datasets
- Reduced client-side pagination logic

---

### 🧊 HTML CACHING (STATIC FREEZING)

All HTML caching is done by:

1. Rendering the Vue page in an iframe
2. Extracting the final DOM
3. Writing `/slug/index.html` into the **parent webroot**

---

#### 3️⃣ HTML – Cache from URL

- Cache a single Vue route
- Saves rendered HTML to disk
- Cleans output by removing:

  - JSON-LD
  - canonical links
  - OG / Twitter meta
  - Google Tag Manager

**Use cases**

- SEO-critical landing pages
- Manual page freezing

---

#### 4️⃣ HTML – Cache from List

- Uses a predefined `pages.json`
- Allows multi-select
- Cache or delete pages in bulk
- Homepage handled with backup/restore logic

**Use cases**

- Controlled, explicit page caching
- Editorial or curated sites

---

#### 5️⃣ HTML – Cache from Sitemap

- Reads sitemap URLs from `sitemaps.json`
- Supports:

  - Normal sitemaps (`<urlset>`)
  - Sitemap indexes (`<sitemapindex>`)

- Sitemap indexes expand into **sub-sitemap tabs**
- URLs are:

  - Stripped of protocol + domain
  - Treated identically to `pages.json` entries
  - Grouped into batches of 10
  - Selectable per-group or globally

**Use cases**

- SEO-driven caching
- Large dynamic catalogs
- Align cached pages with real crawl structure

---

### ⚙️ Configuration Files

#### `pages.json`

```json
["/all-artworks/fine-art/all-price-ranges/", "/all-artists/all-media/"]
```

---

#### `sitemaps.json`

```json
{
  "sitemaps": ["https://example.com/sitemaps/sitemap-index.xml"]
}
```

> Sitemap fetching is done **on-demand** when a sitemap tab is opened.

---

### 🧩 UI Stack

- Vue (Options API)
- Quasar UI components
- Utility classes / inline styles only
- Modular design (sitemap list handled via subcomponent)

Main UI components:

- `DataCacheBinder.vue`
- `HtmlCachePages.vue`
- `HtmlCachePageHelper.vue`

---

### 🔐 Assumptions & Constraints

- Full control of the target website
- Same-origin required (iframes + file writes)
- HTML caching tested on staging/production (not local)
- Apache/Nginx configured to allow PHP execution in `/integrator`

---

### ❌ What This Is NOT

- Not SSR
- Not a CDN
- Not a headless CMS
- Not framework-specific beyond Vue

This is a **pragmatic, site-owned caching layer**.

---

### 🎯 Why This Exists

- Vue SPAs are fast — but invisible to crawlers
- Airtable is flexible — but rate-limited
- This service bridges the gap **without changing your app architecture**

## Portal integration (Data Acceptance)

### Philosophy

**We will use a relationship-first integration model.**

Most integrations sync records.
Very few sync **relationships** — even though relationships are a **critical** part of any real integration.

Our model is about treating relationships as first-class concerns.

---

#### What our approach Does

**Relationship Syncing (Primary)**
our approach syncs relationships by assigning entities a stable internal identity and translating foreign keys between systems at sync time, rather than leaking IDs across schemas.
This allows each system to use its own native identifiers while relationships remain correct.

**Revision Tracking (Secondary)**
Because entities have stable identity, we can diff changes, commit them, and maintain a revision history.
This capability naturally emerges from the same foundation used for relationship syncing.

**Intervention Mode (Optional)**
We can require **moderator approval** before changes are committed, enabling controlled, review-based integrations.

---

#### Notes

- We also supports **image and media syncing** (adapter-specific where necessary)
- The system is **target- and source-agnostic**, where practical

### Development Milestones

The integration layer is designed to grow incrementally, with each milestone unlocking a new class of synchronization capability while keeping complexity contained.

**Milestone 1 – Registry-Assisted Syncing**  
Introduce a registry table that maps source records to target records across systems, decoupling external IDs from source data and enabling reliable, re-runnable syncs and foreign-key translation.

**Milestone 2 – Syncing Special Fields**  
Handle non-scalar fields that require custom logic rather than simple value copying.

- _Image Syncing_: pass attachment URLs directly to the target system and let it ingest assets natively.
- _Relationship Syncing_: use the registry to resolve and translate foreign keys between systems before linking records.

**Milestone 3 – Reverse Syncing with Ownership**  
Enable syncing from Airtable back to WordPress while respecting listing ownership, ensuring records are assigned to the correct contributor so they remain editable in the portal.

**Milestone 4 – Syncing Frivolous Fields**  
Add lower-priority enrichments that improve UX but do not block core flows.

- _Location Fields_: lightweight parsing and enrichment of place data without full geographic normalization.

**Milestone 5 – Change Tracking**  
Track when records change to support smarter sync decisions and reduce unnecessary operations; useful long-term, but intentionally deferred to avoid premature complexity.
