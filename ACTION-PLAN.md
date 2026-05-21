# Sigmie.com — SEO Action Plan

Generated: 2026-05-21 — paired with `FULL-AUDIT-REPORT.md`.

**Current score: 40 / 100. Realistic target after the Critical + High block: 75–80 / 100.**

Most of the upside is concentrated in two fixes: (1) restoring SSR on `/docs/v2/*`, and (2) injecting per-page `<title>` / `<meta description>` / `<link rel=canonical>` from route data. Together those two unblock ~36 of the 45 indexed URLs.

---

## CRITICAL — fix this week (blocks indexing or causes embarrassment)

### C1. Restore SSR for `/docs/v2/*` pages
**Impact:** ★★★★★ (affects 35 of 45 sitemap URLs)
**Effort:** Medium — investigation + fix in Inertia SSR.
**Why:** Every doc page currently returns 100–290 KB of HTML with **zero body text**. The markdown content lives only in `data-page` JSON and requires JS to hydrate. Google can render JS but with delay and reduced confidence. Bing and most AI crawlers will not see content.
**How:**
1. Confirm the SSR daemon (`inertia:start-ssr`, supervisor task `daemon-523660`) is alive on the server (`sudo supervisorctl status daemon-523660`).
2. Check the SSR build output for the docs Vue component — does it actually render the markdown? If markdown is rendered via a Vue-side library (e.g. `marked`/`shiki` in `onMounted`), move that to server-rendered output.
3. The Laravel side likely already parses markdown (see `app/Console/Commands/IndexDocs.php`). Pass the rendered HTML to Inertia as a prop instead of relying on client-side markdown parsing.
4. Verify by `curl -A "Mozilla/5.0" https://sigmie.com/docs/v2/introduction | grep -c "<h2"` — expect ≥ 1.

### C2. Add per-page `<title>` and `<meta description>`
**Impact:** ★★★★★ (affects every URL; current sitewide duplication of `<title>Sigmie</title>`)
**Effort:** Low — 1–2 hours.
**How:**
- In each Inertia page component, use `<Head>` from `@inertiajs/vue3`:
  ```vue
  <script setup>
  import { Head } from '@inertiajs/vue3'
  const props = defineProps({ doc: Object })
  </script>
  <template>
    <Head>
      <title>{{ doc.title }} — Sigmie Docs</title>
      <meta name="description" :content="doc.description" />
      <link rel="canonical" :href="`https://sigmie.com${doc.path}`" />
      <meta property="og:title" :content="`${doc.title} — Sigmie Docs`" />
      <meta property="og:description" :content="doc.description" />
      <meta property="og:url" :content="`https://sigmie.com${doc.path}`" />
    </Head>
  </template>
  ```
- Ensure SSR emits these into `<head>` (Inertia SSR does this automatically when `Head` is used and the SSR daemon is running — depends on C1).
- For Blade fallback, render the same tags from controller data.

### C3. Fix the missing `/og-image.png`
**Impact:** ★★★★ (every social share is broken)
**Effort:** Trivial — 15 minutes.
**How:**
1. Create a 1200×630 PNG or WebP at `public/og-image.png` (logo + tagline `Sigmie — A modern Elasticsearch library for PHP`).
2. Confirm `curl -I https://sigmie.com/og-image.png` returns `200` and `content-type: image/png`.
3. While there, verify the per-post `/cards/{slug}.png` assets exist for all four blog posts.

### C4. Replace `"lorem"` description on `/blog/a-different-approach`
**Impact:** ★★★ (one URL, but it's literally the first result on `/blog`)
**Effort:** Trivial.
**How:** Edit the frontmatter `description:` field in the source markdown (likely under `resources/` or in the synced `docs/v2/` directory's blog equivalent). Suggested:
```
description: "Why Sigmie takes a different approach to Elasticsearch in PHP — fluent API, no boilerplate, focus on relevance instead of low-level mappings."
```
Audit the other three blog post descriptions while you're there (currently 46–74 chars; aim for 130–160).

### C5. Add JSON-LD on homepage and blog posts
**Impact:** ★★★★ (AI engines, rich results, brand entity grounding)
**Effort:** Low — 1 hour.
**How:** Drop the `Organization` + `SoftwareApplication` + `WebSite` graph from `FULL-AUDIT-REPORT.md` into the homepage `<Head>`. Add an `Article` block to the blog post template, using frontmatter values.

---

## HIGH — fix within 1 week (significantly impacts rankings)

### H1. Add security headers
**Impact:** ★★★ (trust signals, security score, future-proofing)
**Effort:** Low — Laravel middleware or nginx `add_header` lines.
**How (nginx):**
```nginx
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Permissions-Policy "camera=(), microphone=(), geolocation=()" always;
add_header Content-Security-Policy "default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; font-src 'self' data:; connect-src 'self' https:; frame-ancestors 'self'" always;
```
Remove the deprecated `X-XSS-Protection`. Submit the domain to https://hstspreload.org once HSTS has been live for a week.

### H2. Add `/llms.txt` and `/llms-full.txt`
**Impact:** ★★★ (cheap AI-search win)
**Effort:** Trivial — static file or route.
**How:** Use the content suggested in `FULL-AUDIT-REPORT.md § 6`. Wire to a route or static file in `public/llms.txt`. Generate at deploy time from the sitemap (already done by `artisan docs:index`).

### H3. Render navigation server-side
**Impact:** ★★★ (internal linking, crawl budget, AI signal)
**Effort:** Low–Medium.
**How:** Move the top nav and footer out of pure Inertia/Vue mount and into the Blade root layout, or ensure the Vue header/footer renders in SSR output. Initial HTML should contain anchors to `/docs/v2/introduction`, `/blog`, `/search`, GitHub repo, and the MCP page.

### H4. Add an H1 to the homepage
**Impact:** ★★ (sole H1 is a key on-page signal)
**Effort:** Trivial.
**How:** Add `<h1>A modern Elasticsearch library for PHP</h1>` (or similar) above the existing `<h2>Creating an AI Search Has Never Been Easier</h2>`. Style it inline if needed to keep the visual design unchanged.

### H5. Expand homepage content
**Impact:** ★★★ (currently 174 words is below the threshold for a developer-tool homepage)
**Effort:** Medium (content writing).
**How:** Aim for ~700 words split across:
- What Sigmie is (and isn't — not a hosted service, it's a library)
- Who it's for (PHP / Laravel devs adding search)
- What's different (fluent API, semantic+keyword hybrid, no boilerplate)
- A real code snippet (search builder example)
- Quickstart link + GitHub link
- Social proof (stars, downloads, testimonials)

---

## MEDIUM — fix within 1 month

### M1. Add `BreadcrumbList` JSON-LD on docs and blog posts
Cheap rich-result win. Use the schema in the report.

### M2. Fix duplicate viewport and robots meta tags
Two `<meta name="viewport">` and two `<meta name="robots">` on the homepage. Pick one of each in the Blade layout.

### M3. Clean up `robots.txt`
Remove the duplicate `User-agent: *` block. Decide if `crawl-delay: 1` is needed (it isn't — remove it).

### M4. Add `sameAs` profile links to Organization schema
Improves entity disambiguation in AI engines and Google Knowledge Panel candidacy. Include GitHub org, Packagist namespace, X/Twitter handle, LinkedIn page.

### M5. Audit and fix blog meta descriptions
- `/blog/calculating-index-shards`: extend from 46 → 130–160 chars.
- `/blog/high-level-properties`: extend from 74 → 130–160 chars.
- `/blog/why-are-search-services-expensive`: extend from 57 → 130–160 chars.

### M6. Consolidate author metadata
Pick one canonical pattern (e.g. `Sigmie Team` sitewide, `Nico Orfanos` on blog posts), used consistently in `<meta name="author">`, JSON-LD `author`, and any visible byline.

### M7. Fix the broken heading on `/blog/a-different-approach`
`Think of anIndicesas a collections.` — fix code-element spacing in the markdown source.

### M8. Verify `/resumes` is intentionally public
It's in the sitemap with priority 0.5. If it's not a marketing page, remove it from the sitemap and add `noindex`.

---

## LOW — backlog

### L1. Add a `www.` DNS CNAME pointing to apex (or explicit redirect)
Currently `www.sigmie.com` returns DNS failure. Users typing `www.` hit a hard wall. Cheap fix.

### L2. Consider edge-caching for static HTML
`/` and `/blog/*` are not session-dependent; switching from `cache-control: no-cache, private` to `public, max-age=60, s-maxage=300, stale-while-revalidate=600` would let Cloudflare cache HTML and slash TTFB to ~10 ms for repeat visitors. Be careful with `/docs/v2/*` once SSR is fixed — same caching strategy applies.

### L3. Audit `app.*.js` (324 KB) and `app.*.css` (110 KB)
For a marketing + docs site, this is heavy. Investigate:
- Tailwind purge: ensure `content` paths include all Vue components.
- Split out search/admin code that isn't needed on public pages.

### L4. Lazy-load below-the-fold images
Add `loading="lazy"` (and `decoding="async"`) to homepage images that are not LCP candidates.

### L5. Set up CrUX / Google Search Console / GA4 integrations
The audit had no Google API credentials configured. Wiring up CrUX, GSC, and GA4 would unblock the `seo-google` agent and give you field CWV plus indexation truth (rather than my SSR-based inference).

### L6. Promote the MCP server on the homepage
The `/mcp` endpoint is a genuinely interesting feature for an AI-agent-native developer tool. Surface it: hero bullet, dedicated section, and a featured entry in `llms.txt`.

---

## Suggested implementation order (single dev, one week)

| Day | Tasks |
|---|---|
| Mon | C1 (SSR fix) — investigate and ship |
| Tue | C2 (per-page title/desc/canonical) — wire `<Head>` into all Inertia pages |
| Wed | C3, C4, C5 (og-image, "lorem" fix, JSON-LD on home + blog posts) |
| Thu | H1 (security headers), H2 (llms.txt), H4 (homepage H1) |
| Fri | H3 (SSR nav), H5 (homepage copy expansion), verify via `curl` + Search Console |
| Mon next | M1–M8 punch list |

After the week, re-run this audit and expect a score in the **75–80** range. To push above 80 you'll need field CWV data (CrUX/GSC integration) and content depth investment on the docs.
