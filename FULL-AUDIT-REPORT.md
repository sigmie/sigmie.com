# Sigmie.com — Full SEO Audit Report

- **URL audited:** https://sigmie.com
- **Audit date:** 2026-05-21
- **Pages analyzed:** 19 (homepage + docs hub + 10 docs pages + blog hub + 4 blog posts + assets/manifest/robots/sitemap)
- **Source:** Production HTTP fetches (Mozilla + Googlebot UAs), sitemap parse, header inspection.

---

## Executive Summary

**Overall SEO Health Score: 40 / 100 — Poor**

| Category | Weight | Score | Weighted |
|---|---|---|---|
| Technical SEO | 22% | 55 | 12.1 |
| Content Quality | 23% | 40 | 9.2 |
| On-Page SEO | 20% | 25 | 5.0 |
| Schema / Structured Data | 10% | 15 | 1.5 |
| Performance (CWV, lab) | 10% | 70 | 7.0 |
| AI Search Readiness | 10% | 25 | 2.5 |
| Images | 5% | 50 | 2.5 |
| **Total** | **100%** | | **~39.8** |

**Business type detected:** Developer tool / open-source SaaS — PHP Elasticsearch/OpenSearch library + docs hub + blog. Not local, not e-commerce. SXO target personas: PHP/Laravel developers, search engineers, technical decision-makers.

### Top 5 critical issues

1. **All documentation pages render with zero body content in HTML.** Every `/docs/v2/*` page returns 100–290 KB of HTML where `<body>` text length is **0 characters** — the entire docs experience is bolted onto a client-rendered Inertia.js SPA shell. The full markdown lives only inside a `data-page` JSON attribute on `#app` and requires JavaScript execution to become readable text. Doc pages also have no per-page `<title>`, `<meta description>`, `<link rel=canonical>`, or `<h1>` in the initial HTML.
2. **Every single page on the site has the title tag `Sigmie`** (6 characters). This kills CTR in SERPs, prevents per-page targeting, and creates near-total title duplication across the site.
3. **`https://sigmie.com/og-image.png` returns HTTP 404** even though every page references it as `og:image` and `twitter:image`. Social previews on LinkedIn, Twitter/X, Slack, iMessage, Discord, etc. are broken sitewide.
4. **`/blog/a-different-approach` ships with the meta description `"lorem"`** — a placeholder shipped to production. It is also referenced in the sitemap and is the first blog link.
5. **No HSTS, no Content-Security-Policy, no Referrer-Policy** in response headers. Only legacy `X-Frame-Options`, `X-Content-Type-Options`, and the deprecated `X-XSS-Protection` are set.

### Top 5 quick wins

1. Add a per-page `<title>` and `<meta description>` template in the Inertia/Blade layout based on the page route/frontmatter — fixes title duplication on 45+ URLs.
2. Replace the missing `/og-image.png` (and verify each `/cards/{slug}.png` exists for blog posts) — instantly restores social previews on all pages.
3. Add an `Article` JSON-LD block to every `/blog/{slug}` page (author, datePublished, headline, image) and a `SoftwareApplication` block to the homepage. Easy to template, big AI-citation upside.
4. Replace the `"lorem"` description in `/blog/a-different-approach` and audit other blog frontmatter for similar placeholders.
5. Publish `/llms.txt` (and ideally `/llms-full.txt`) listing the 45 sitemap URLs with one-line descriptions — cheapest possible AI-search readiness win.

---

## Site Inventory

### robots.txt
```
User-agent: *
Allow: /
Sitemap: https://sigmie.com/sitemap.xml
User-agent: *
Crawl-delay: 1
```
- **OK:** Sitemap referenced; everything allowed.
- **Issue:** `User-agent: *` block is duplicated — the second one (`Crawl-delay: 1`) overrides the first. Functionally harmless but messy. Remove duplicate block.
- **Minor:** `Crawl-delay: 1` is ignored by Googlebot but respected by Bing/Yandex. For a 45-URL site, no need to throttle — consider removing.

### sitemap.xml
- 45 URLs, all under `https://sigmie.com/`. Well-formed XML with `lastmod`, `changefreq`, `priority`.
- Covers `/`, `/docs`, `/blog`, `/search`, `/resumes`, 35 docs pages under `/docs/v2/*`, 4 blog posts.
- All `lastmod` values are valid dates ≤ today.
- **Missing:** No `/llms.txt`, no `/humans.txt`. (Not required, but missing.)

### HTTPS / redirects
- `http://sigmie.com/` → 301 → `https://sigmie.com/` (correct).
- `http://sigmie.com/docs` → 301 → `https://sigmie.com/docs/v2/introduction` (2 hops, acceptable).
- `https://sigmie.com/docs/` → 301 → `https://sigmie.com/docs/v2/introduction` (1 hop, OK).
- `www.sigmie.com` DNS does **not resolve**. If users type `www.`, they get a hard failure instead of a redirect. Not a ranking issue, but a UX papercut.

### Headers (response on `/`)
| Header | Value | Verdict |
|---|---|---|
| `server` | `cloudflare` | OK — CDN in front |
| `content-encoding` | `br` | ✅ Brotli on |
| `vary` | `Accept-Encoding, X-Inertia` | OK |
| `cache-control` | `no-cache, private` | Acceptable (Laravel session) but blocks CDN caching of HTML |
| `cf-cache-status` | `DYNAMIC` | Confirms HTML is not CDN-cached |
| `x-frame-options` | `SAMEORIGIN` | ✅ |
| `x-content-type-options` | `nosniff` | ✅ |
| `x-xss-protection` | `1; mode=block` | ⚠️ Deprecated header — modern browsers ignore it |
| `strict-transport-security` | *(missing)* | ❌ Must add |
| `content-security-policy` | *(missing)* | ❌ Should add |
| `referrer-policy` | *(missing)* | ❌ Should add |
| `permissions-policy` | *(missing)* | ⚠️ Should add |

---

## 1. Technical SEO — 55/100

### Crawlability
- ✅ Site is reachable (HTTP 200), HTTP→HTTPS redirect works.
- ✅ robots.txt allows everything.
- ✅ sitemap.xml discoverable from robots.txt.
- ✅ Brotli compression; HTTP/2 (`alt-svc: h3` shows HTTP/3 advertised too).
- ❌ Doc pages return empty SSR body — **Googlebot must JS-render to see content**. Google does render, but with delay; Bing and most AI crawlers do not reliably JS-render. This puts the entire docs corpus at risk of being underindexed or misranked.

### Indexability
- ✅ `<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">` — correct directive on rendered pages.
- ⚠️ Duplicate `<meta name="robots">` tags on homepage: one says `all`, the other says `index, follow, ...`. The conflict is harmless (both permissive) but indicates layout inheriting from two places. Pick one.
- ❌ Doc pages contain no `<meta name="robots">` *and* no other head meta beyond the global shell. Googlebot will treat them as indexable, but the lack of per-page metadata means it must infer everything from JS-rendered DOM.

### Security headers
- Missing **HSTS**: `Strict-Transport-Security: max-age=31536000; includeSubDomains; preload` should be set. Without it, the first request can still be downgraded to HTTP.
- Missing **CSP**: With Inertia + Vite, a hash- or nonce-based CSP is feasible. Even `default-src 'self'; img-src 'self' data: https:; ...` would be a major improvement.
- Missing **Referrer-Policy**: Recommend `strict-origin-when-cross-origin`.
- Missing **Permissions-Policy**: Recommend `camera=(), microphone=(), geolocation=()`.

### URL structure
- ✅ Clean, lowercase, dash-separated slugs (`/blog/why-are-search-services-expensive`).
- ✅ No file extensions, no tracking params on canonicals.
- ✅ Doc paths versioned (`/docs/v2/...`) — gives a clean future-migration story.
- ⚠️ `/resumes` in sitemap — is this an intentional public route? It has priority 0.5 but it's unclear what it serves; verify it isn't an accidentally indexed admin or internal page.

### Mobile / viewport
- `<meta name="viewport" content="width=device-width, initial-scale=1">` and a second `<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">` — **duplicate viewport tag** with conflicting `maximum-scale`. `maximum-scale=5` reduces accessibility (limits pinch-zoom in some browsers). Pick one; recommend dropping the `maximum-scale` variant.

### Internationalization
- `<html lang="en">` and `<meta name="language" content="en-us">` — fine for a single-locale site. No hreflang needed.

---

## 2. Content Quality — 40/100

### Volume
| Page | SSR body chars | Approx words |
|---|---|---|
| `/` (homepage) | 985 | 174 |
| `/blog` | 293 | 56 |
| `/blog/a-different-approach` | 5,032 | 1,011 |
| `/blog/calculating-index-shards` | 3,135 | 540 |
| `/blog/high-level-properties` | 1,297 | 220 |
| `/blog/why-are-search-services-expensive` | 1,819 | 320 |
| `/docs/v2/introduction` | 0 | 0 (client-rendered only) |
| `/docs/v2/quick-start` | 0 | 0 |
| `/docs/v2/installation` | 0 | 0 |
| All other docs sampled (10) | 0 | 0 |

### E-E-A-T (Experience / Expertise / Authoritativeness / Trustworthiness)
- **Experience:** Homepage shows "About Me — Nico" and a personal "from a developer who built this because…" framing. Good signal, but only on the homepage and very brief (~174 words total).
- **Expertise:** Blog posts demonstrate technical depth (sharding math, library comparisons). Good.
- **Authoritativeness:** No `Organization` JSON-LD, no `sameAs` linking to GitHub / Twitter / LinkedIn / Mastodon profiles. The package is well-known in PHP/Laravel circles but the site doesn't surface authority signals.
- **Trustworthiness:** No privacy policy, no contact page, no imprint linked from the homepage HTML (may exist client-side). Author meta is **inconsistent**: one tag says `author: nico@sigmie.com`, another says `author: Sigmie Team` — pick one consistent author identity per page.

### Issues
- **`/blog/a-different-approach` has `<meta name="description" content="lorem">`** — a placeholder shipped to production. (Confirmed in fetched HTML.)
- **Heading typo on `/blog/a-different-approach`:** `Think of anIndicesas a collections.` — there's a code element rendering with no surrounding whitespace, but the underlying source needs fixing.
- **Homepage is thin (174 words).** For a developer tool's home, 600–900 words covering "what it is / why / for whom / how it differs / code sample / proof" is the floor.
- **`/blog` index has 56 words** and only 1 post visible (`You need a Search as a Service ?`) despite 4 posts in sitemap. The index either isn't enumerating all posts or the listing is short due to SSR partial render.

### Duplicate content risk
- No detected near-duplicates between pages. Doc pages all share the same shell HTML (since body is empty), which is technically duplicate boilerplate but doesn't compete with real content because each page has a unique URL.

---

## 3. On-Page SEO — 25/100

### Title tags
- **All 19 sampled pages return `<title>Sigmie</title>`.** 6 characters. Identical across the site.
- Recommended per page (examples):
  - `/`: `Sigmie — Modern Elasticsearch & OpenSearch library for PHP`
  - `/docs/v2/introduction`: `Introduction — Sigmie Docs`
  - `/docs/v2/quick-start`: `Quick Start — Sigmie Docs`
  - `/blog/a-different-approach`: `A different approach to Elasticsearch in PHP — Sigmie Blog`

### Meta descriptions
- `/` — present, 211 chars (slightly over the typical 160-char SERP truncation). Good content though.
- `/blog` — present, 141 chars. OK.
- `/blog/a-different-approach` — `lorem` (5 chars). **Critical.**
- `/blog/calculating-index-shards` — 46 chars. Too short.
- `/blog/high-level-properties` — 74 chars. Short.
- `/blog/why-are-search-services-expensive` — 57 chars. Short.
- **All docs pages — empty / missing.**

### Canonicals
- `/` — `https://sigmie.com/` ✅
- `/blog` — `https://sigmie.com/blog` ✅
- `/blog/{slug}` — set correctly ✅
- **All docs pages — no `<link rel=canonical>` in initial HTML.**
- ⚠️ Homepage has `<link rel="alternate" href="https://sigmie.com/">` pointing to itself with no `hreflang` or `type`. Either remove or supply a meaningful `type`/`hreflang`.

### Heading hierarchy
- **Homepage has no `<h1>`.** Only `<h2>` × 3 and `<h3>` × 1. Add one descriptive H1 (e.g., `A modern Elasticsearch library for PHP`).
- **`/blog` has H1 `Posts`.** Generic; consider `Sigmie Blog — Articles on PHP search and Elasticsearch`.
- **Blog posts have H1 ✓.**
- **Doc pages have no H1 in initial HTML** (only after JS hydration).

### Internal linking (in initial SSR HTML)
- Homepage: 11 anchor tags, 10 of them external (mostly GitHub repo/author pages), only **1 internal link** (`/docs`). The site relies on JS-mounted navigation for everything else.
- Blog post `/blog/a-different-approach`: 5 anchors total, 3 internal. Sparse.
- Docs: 0 anchors in initial HTML.
- **Recommendation:** Add server-rendered navigation (header + footer) with internal links to `/docs`, `/blog`, `/search`, key doc sections, GitHub. This is doable inside the Vue/Inertia SSR layer or as static fallbacks in the Blade root template.

### Image alt text
- 8 images on homepage — **all have `alt`** ✓.
- Blog posts: 1 image on `/blog/a-different-approach`, has alt ✓.
- Doc pages: 0 images in SSR HTML (cannot verify).

---

## 4. Schema / Structured Data — 15/100

### Current state
- `/blog` has 1 `CollectionPage` JSON-LD with `publisher: Organization{Sigmie, logo: /logo.svg}` — good baseline.
- **Every other page (homepage, all docs, all blog posts) has zero JSON-LD.**

### Recommended additions

**Homepage** — add an `Organization` + `SoftwareApplication` graph:
```json
{
  "@context":"https://schema.org",
  "@graph":[
    {
      "@type":"Organization",
      "@id":"https://sigmie.com/#org",
      "name":"Sigmie",
      "url":"https://sigmie.com/",
      "logo":"https://sigmie.com/logo.svg",
      "sameAs":[
        "https://github.com/sigmie/sigmie",
        "https://twitter.com/sigmie_io",
        "https://www.linkedin.com/company/sigmie"
      ]
    },
    {
      "@type":"SoftwareApplication",
      "name":"Sigmie",
      "applicationCategory":"DeveloperApplication",
      "operatingSystem":"Cross-platform",
      "offers":{"@type":"Offer","price":"0","priceCurrency":"USD"},
      "url":"https://sigmie.com/",
      "publisher":{"@id":"https://sigmie.com/#org"}
    },
    {
      "@type":"WebSite",
      "url":"https://sigmie.com/",
      "name":"Sigmie",
      "potentialAction":{
        "@type":"SearchAction",
        "target":"https://sigmie.com/search?q={search_term_string}",
        "query-input":"required name=search_term_string"
      }
    }
  ]
}
```

**Blog post template** — add `Article`:
```json
{
  "@context":"https://schema.org",
  "@type":"Article",
  "headline":"{frontmatter.title}",
  "description":"{frontmatter.description}",
  "image":"https://sigmie.com/cards/{slug}.png",
  "author":{"@type":"Person","name":"Nico Orfanos","url":"https://github.com/nicoorfi"},
  "publisher":{"@type":"Organization","name":"Sigmie","logo":{"@type":"ImageObject","url":"https://sigmie.com/logo.svg"}},
  "datePublished":"{frontmatter.published_at}",
  "dateModified":"{lastmod}",
  "mainEntityOfPage":"https://sigmie.com/blog/{slug}"
}
```

**Doc page template** — add `TechArticle` and `BreadcrumbList`:
```json
{
  "@context":"https://schema.org",
  "@type":"TechArticle",
  "headline":"{frontmatter.title}",
  "description":"{frontmatter.description}",
  "proficiencyLevel":"Beginner",
  "dependencies":"PHP 8.1+, Laravel 9+, Elasticsearch 7/8 or OpenSearch 1/2",
  "datePublished":"{published_at}",
  "dateModified":"{lastmod}",
  "author":{"@type":"Organization","name":"Sigmie"}
}
```
Plus a `BreadcrumbList`: `Home → Docs → {category} → {page}`.

**FAQ candidates** — if the docs introduction or quick-start has Q&A patterns, mark them up as `FAQPage` for AI/SERP visibility.

---

## 5. Performance (lab estimate) — 70/100

> No CrUX field data was queried (no Google API credentials configured). Below are lab signals only.

### Network
- TTFB on `/`: ~70 ms (Cloudflare edge in ATH, then origin in GCP). Excellent.
- TTFB on assets: 70–160 ms.
- Brotli compression on HTML and assets.
- HTTP/2 enabled; HTTP/3 advertised via `alt-svc`.

### Resource weight (uncompressed where shown)
| Asset | Type | Size | Notes |
|---|---|---|---|
| `homepage.html` | HTML | 72 KB raw / 18.6 KB brotli | Inertia data-page attribute is heavy |
| `build/assets/app.*.css` | CSS | 110 KB | Likely Tailwind + design tokens; check unused class purging |
| `build/assets/app.*.js` | JS | 324 KB | Heavy for a marketing site |
| `build/assets/Welcome.*.js` | JS | 43 KB | Page-specific chunk |

### Estimated Core Web Vitals (lab, not field)
- **LCP:** Likely 1.5–2.5 s on cable / fast 4G — should pass (the LCP is probably the hero text or a hero image; both load from edge CDN). **Field measurement required.**
- **CLS:** Cannot determine without rendered DOM measurement. Inertia hydration on a SPA can introduce CLS if heading sizes shift; recommend a real Lighthouse run.
- **INP:** No interactive elements analyzed.

### Recommendations
- Audit if `app.*.css` (110 KB) can be reduced by Tailwind purge / removing unused design tokens.
- Defer or async-load any third-party scripts (none detected in SSR HTML — good, but verify after hydration).
- Lazy-load below-the-fold images (`loading="lazy"`).
- Consider serving `og-image.png` (once it exists) as WebP at 1200×630.

---

## 6. AI Search Readiness — 25/100

### llms.txt / llms-full.txt
- **Both return HTTP 404.** Add them at the project root. Suggested `/llms.txt`:
```
# Sigmie
> A modern, developer-friendly Elasticsearch and OpenSearch library for PHP and Laravel.

## Documentation
- [Introduction](https://sigmie.com/docs/v2/introduction): What Sigmie is and why it exists
- [Quick Start](https://sigmie.com/docs/v2/quick-start): Get up and running in 5 minutes
- [Installation](https://sigmie.com/docs/v2/installation): Composer install and config
- [Core Concepts](https://sigmie.com/docs/v2/core-concepts): Indices, documents, mappings
- [Search](https://sigmie.com/docs/v2/search): Building queries with the fluent API
- [Semantic Search](https://sigmie.com/docs/v2/semantic-search): Vector & hybrid search
- [RAG](https://sigmie.com/docs/v2/rag): Retrieval-augmented generation patterns
- [MCP](https://sigmie.com/docs/v2/mcp): The Sigmie MCP server for AI agents

## Blog
- [A different approach](https://sigmie.com/blog/a-different-approach)
- [Calculating index shards](https://sigmie.com/blog/calculating-index-shards)
- [High-level properties](https://sigmie.com/blog/high-level-properties)
- [Why are search services expensive](https://sigmie.com/blog/why-are-search-services-expensive)
```

### AI crawler accessibility
- ✅ `robots.txt` does **not** block GPTBot, ClaudeBot, PerplexityBot, Google-Extended, anthropic-ai, or any AI crawler. AI search engines can crawl freely.
- ❌ The SSR-empty docs pages are likely **unusable** to crawlers that don't run JavaScript. ChatGPT's browse crawler and Perplexity sometimes run JS; Claude's web tool may or may not depending on mode. Bingbot for AI Copilot does render JS but with limits. **Implementing real SSR for docs pages is the single highest-leverage AI win.**

### Citability
- Blog posts have decent passage structure (headings, sub-headings) but lack `Article` JSON-LD, dates, and author markup that AI engines use to assign authority.
- Homepage `<title>` of just "Sigmie" is too generic — AI engines disambiguating "Sigmie" (the library) from other entities (a name? a brand?) won't have strong textual signals.

### Brand mention signals
- ✅ Brand name "Sigmie" appears consistently in OG, Twitter, and homepage copy.
- ❌ No `sameAs` array linking to GitHub, Packagist, X/Twitter, LinkedIn — these are the canonical signals AI crawlers use for entity grounding.

### MCP server
- The project already runs an MCP server at `/mcp` exposing `search_docs`, `read_doc`, `list_docs` to AI agents. This is an excellent moat for direct agent integration but is **separate from AI-search SEO**. Consider mentioning the MCP server prominently on the homepage and in `llms.txt` as a feature.

---

## 7. Images — 50/100

- ✅ All visible images on the homepage have `alt` attributes.
- ✅ Favicons (16, 32, apple-touch) referenced; `site.webmanifest` returns 200.
- ❌ **`/og-image.png` returns HTTP 404 with `content-type: text/html`** — meaning the Laravel fallback page is returned instead of an image. Every page references this URL via `og:image` and `twitter:image`, so every social share is broken.
- ⚠️ Blog post `/blog/a-different-approach` references `/cards/a-different-approach.png` — these per-post cards should each be verified (not tested in this audit).
- ⚠️ No `loading="lazy"`, `width`/`height`, or `srcset` attributes detected on homepage `<img>` tags (cannot fully verify since most images may render after hydration).

---

## Issue Table (sorted by severity)

| # | Severity | Area | Issue | Where | Fix |
|---|---|---|---|---|---|
| 1 | Critical | Tech / Content | All `/docs/v2/*` pages have empty `<body>` text in SSR HTML | 35 doc URLs | Fix Inertia SSR pipeline for docs route; render markdown to HTML server-side |
| 2 | Critical | On-Page | All pages have title `"Sigmie"` (6 chars, duplicated sitewide) | 45+ URLs | Inject per-page title via Inertia `Head` / Blade layout |
| 3 | Critical | On-Page | All doc pages missing `<meta description>`, `<link rel=canonical>`, `<h1>` in SSR HTML | 35 doc URLs | Move meta tags into the layout fed by route data |
| 4 | Critical | Content | `/blog/a-different-approach` ships `<meta description="lorem">` | 1 URL | Fix frontmatter description |
| 5 | Critical | Images | `/og-image.png` returns 404 | All pages | Upload 1200×630 PNG/WebP to `public/` |
| 6 | High | Schema | No JSON-LD on homepage, doc pages, blog posts | 44+ URLs | Add `Organization`, `SoftwareApplication`, `WebSite`, `Article`, `TechArticle`, `BreadcrumbList` |
| 7 | High | Tech | No HSTS, CSP, Referrer-Policy headers | Sitewide | Add via Laravel middleware or nginx response headers |
| 8 | High | AI | `/llms.txt` and `/llms-full.txt` return 404 | Site root | Generate at deploy time from sitemap |
| 9 | High | On-Page | Homepage has no `<h1>` | `/` | Add a descriptive H1 |
| 10 | High | On-Page | Only 1 internal link in homepage SSR HTML | `/` | Render nav and footer server-side |
| 11 | Medium | On-Page | Duplicate `<meta name=viewport>` with conflicting `maximum-scale` | Sitewide | Keep one viewport tag, drop `maximum-scale` |
| 12 | Medium | On-Page | Duplicate `<meta name=robots>` (`all` vs `index, follow, ...`) | Homepage | Keep one |
| 13 | Medium | Content | Homepage is thin (174 words) | `/` | Expand to 600–900 words with sections |
| 14 | Medium | Content | Author meta inconsistent (`nico@sigmie.com` vs `Sigmie Team`) | Sitewide | Pick one canonical author per page |
| 15 | Medium | Content | Heading rendering issue `"Think of anIndicesas a collections."` | `/blog/a-different-approach` | Fix code-element spacing |
| 16 | Medium | Tech | `robots.txt` has duplicate `User-agent: *` block | robots.txt | Merge into one block |
| 17 | Medium | Schema | No `sameAs` profiles for the Organization | Sitewide | Add GitHub, Packagist, X/Twitter, LinkedIn |
| 18 | Low | Tech | `www.sigmie.com` DNS does not resolve | Subdomain | Either set `www` CNAME → apex, or leave as-is |
| 19 | Low | Tech | `crawl-delay: 1` may slow Bing/Yandex unnecessarily | robots.txt | Remove |
| 20 | Low | Tech | `cache-control: no-cache, private` on HTML blocks CDN caching | Sitewide | Consider `public, max-age=60, s-maxage=300` for non-session pages |
| 21 | Low | On-Page | `/blog` index has only 56 SSR words and 1 visible post | `/blog` | Render all 4 posts server-side with title + excerpt + date |
| 22 | Low | Perf | `app.*.js` is 324 KB raw | Sitewide | Audit chunking and Tailwind purge |
| 23 | Low | Schema | `/blog` JSON-LD `publisher.logo` should be an `ImageObject` with width/height | `/blog` | Add `width: 600, height: 60` (or actuals) |

---

## What I did not test (and recommend running next)

- **Lighthouse / PageSpeed Insights** with field CrUX data — needs Google API credentials or PSI fetch from the script.
- **Mobile rendering screenshots** — Playwright is available in this repo; spawning `seo-visual` would add desktop/mobile screenshots.
- **JS-rendered DOM analysis** — since docs are client-rendered, a Playwright pass that waits for hydration would reveal what Googlebot (which does JS-render) actually sees.
- **Search Console field data** — would confirm whether the SSR issue is actually depressing indexation.
- **Backlink profile** — no API credentials available.
