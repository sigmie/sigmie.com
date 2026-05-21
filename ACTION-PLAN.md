# Sigmie.com — SEO Action Plan (post-100% push)

Generated: 2026-05-21 — paired with `FULL-AUDIT-REPORT.md`.

**Current score: ~88 / 100.** All structural SEO is done. The remaining gap is field-data + content investment + active acquisition (backlinks, citations, brand mentions).

---

## What got shipped this session (already done — for the record)

### Critical (Run 1 → Run 2)
- ✅ SSR daemon fix (`useTheme()` `localStorage` guard) — unblocked SSR sitewide
- ✅ Per-page `<title>` / `<meta description>` / canonical / OG via Blade fallback
- ✅ Organization + WebSite + SoftwareApplication / Article / TechArticle JSON-LD
- ✅ `/og-image.png` created (and resized to 1200×630)
- ✅ Blog `"lorem"` description replaced
- ✅ HSTS / Referrer-Policy / Permissions-Policy added; deprecated `X-XSS-Protection` removed
- ✅ Nginx duplicate-header cleanup (manual edit on the box)
- ✅ `/llms.txt` route, generated from sitemap + frontmatter
- ✅ Homepage H1, viewport/robots duplicates removed, author meta consolidated, robots.txt cleaned

### High → Low (Run 2 → Run 3)
- ✅ `Post.vue` strips its rendered markdown's leading `<h1>` (no more duplicate H1 on blog posts)
- ✅ Server-rendered `Footer` component with Docs / Blog / Search / Resumes / GitHub / Packagist / Discussions links on every page — homepage internal-link count went from 1 → 9+
- ✅ `/search` and `/resumes` got proper SEO shells: H1 + intro paragraph, title/description/canonical via routes
- ✅ `/search` SSR fix: deep-imported `lodash/debounce.js` to dodge the Node ESM "Named export 'debounce' not found" bootloop
- ✅ All 37 doc page `short_description` values rewritten in `sigmie/sigmie` source repo to 143–163 chars (was 49–78)
- ✅ BreadcrumbList JSON-LD on `/docs/v2/*` and `/blog/*` (Home → Hub → Page)
- ✅ `datePublished` + `dateModified` on Article and TechArticle from markdown file mtime
- ✅ CollectionPage + ItemList JSON-LD on `/blog` index
- ✅ Content-Security-Policy-Report-Only header (ready to promote to enforcing CSP after a week of clean reports)
- ✅ Doc titles extended to `{Topic} — Sigmie Docs for PHP` (33–43 chars); blog post titles `{Topic} — Sigmie Blog`; `pageHeading` prop split keeps visible H1 short
- ✅ Title callback (Blade + Inertia) uses `str_contains('Sigmie')` so embedded brand name doesn't duplicate
- ✅ Homepage SSR copy expanded from 174 → 538 words (What is Sigmie / Who it's for / What makes it different)
- ✅ `/og-image.png` regenerated at the canonical 1200×630

---

## What's left to do (to break 90 → 95+)

### HIGH-LEVERAGE — needs external setup

#### 1. Wire Google Search Console + CrUX + GA4
**Impact:** ★★★★ (+5 to Performance, unlocks indexation truth, real CWV, search query share)
**Effort:** Medium — verify domain in GSC, create Google Cloud project, enable APIs, OAuth or service account.
**How:**
1. Verify `sigmie.com` in Search Console (DNS TXT record is easiest with Cloudflare DNS).
2. Enable Search Console API, CrUX API, PageSpeed Insights API, GA4 Data API in Google Cloud.
3. Download a service account JSON or run `python scripts/google_auth.py` from the seo-google skill.
4. Re-run the audit with the `seo-google` agent enabled.

#### 2. Submit to the HSTS preload list
**Impact:** ★★ (browser-bundled HSTS, eliminates first-request downgrade risk)
**Effort:** Trivial — 1 form submission.
**How:** After 1 week of stable HSTS, submit at https://hstspreload.org. The current header already has `preload` directive.

#### 3. Edge-cache HTML for read-only routes
**Impact:** ★★★ (+3 Performance; TTFB ~80 ms → ~10 ms repeat)
**Effort:** Medium — needs a "no-session" middleware applied to `/`, `/docs/v2/*`, `/blog/*`, `/search`, `/resumes`.
**How:**
- Create `WithoutSession` middleware that runs BEFORE `StartSession` and short-circuits with `Cache-Control: public, max-age=60, s-maxage=300, stale-while-revalidate=600` and no `Set-Cookie`.
- Apply on a route-group basis. Keep the existing CSRF middleware for POST API routes only.
- Alternative: Cloudflare Page Rule + Cookie ignore for these path patterns.

### CONTENT — needs writing/voice approval

#### 4. Expand homepage to 1,000+ words
**Impact:** ★★ (+3 Content)
**Effort:** Medium (writing). The current 538-word section is fine; another 500 words of code samples + testimonials + comparison would push it firmly into "comprehensive landing page" territory.

#### 5. Lengthen the short blog posts
**Impact:** ★★ (+2 Content)
**Effort:** Variable. `/blog/high-level-properties` is 265 words, `/blog/why-are-search-services-expensive` is 354. Either extend with more depth or roll into longer canonical articles.

#### 6. Add `Organization.sameAs` entries
**Impact:** ★★ (+2 Schema, AI)
**Effort:** 5 min once you confirm handles.
**How:** Add to `resources/views/app.blade.php`:
```php
'sameAs' => [
    'https://github.com/sigmie',
    'https://packagist.org/packages/sigmie/sigmie',
    'https://x.com/<handle>',
    'https://www.linkedin.com/company/<slug>',
    'https://mastodon.social/@<handle>',
    'https://bsky.app/profile/<handle>',
],
```

### SCHEMA / TECHNICAL POLISH

#### 7. FAQPage markup on doc pages with Q&A patterns
**Impact:** ★★ (+2 Schema, rich-result candidacy)
**Effort:** Per-page, ~15 min each.
**How:** Detect Q&A heading patterns (e.g., "How do I install Sigmie?" sections) and append a `FAQPage` block to the `@graph` in `app.blade.php` keyed by doc slug.

#### 8. Per-doc OG cards
**Impact:** ★★ (+2 Images, page-specific social previews)
**Effort:** Medium — generate cards at deploy time from doc titles.
**How:** Use `claude-seo:seo-image-gen` (Gemini-via-nanobanana-mcp) to generate `/cards/docs/{slug}.png` at deploy. Pass `card` per-route.

#### 9. Promote CSP to enforcing
**Impact:** ★★ (+2 Technical)
**Effort:** Low — change the header name from `Content-Security-Policy-Report-Only` to `Content-Security-Policy` after reviewing 1 week of report-uri violations.
**How:** Edit `app/Http/Middleware/SecurityHeaders.php`. If no `report-uri` configured yet, set one (Cloudflare Reports or a Laravel route that logs).

### LOW-IMPACT POLISH

#### 10. `loading="lazy"` + explicit `width`/`height` on `<img>`
**Impact:** ★ (CLS reduction)
**Effort:** Low — sweep `Welcome.vue`, blog markdown rendered HTML.

#### 11. Convert demo images to WebP
**Impact:** ★ (smaller bytes, better LCP)
**Effort:** Trivial — `cwebp` pass on `public/`.

#### 12. Pin the nginx security-header cleanup in Forge
**Impact:** ★ (idempotent infra)
**Effort:** 5 min in Forge UI.
**How:** I removed three `add_header` lines from `/etc/nginx/sites-enabled/sigmie.com` directly on the box earlier. If you re-provision or use Forge's "Edit Nginx Configuration" the old lines may come back. Open Forge → Server → Nginx config and delete:
```
add_header X-Frame-Options "SAMEORIGIN";
add_header X-XSS-Protection "1; mode=block";
add_header X-Content-Type-Options "nosniff";
```
Laravel's `SecurityHeaders` middleware already sets these (and excludes the deprecated `X-XSS-Protection`).

---

## Implementation order (week-long sprint to ~95)

| Day | Task |
|---|---|
| Mon | Wire GSC + CrUX + GA4 (task 1) |
| Tue | Edge-cache middleware (task 3); HSTS preload submission (task 2) |
| Wed | Homepage expansion (task 4); add sameAs (task 6) |
| Thu | FAQ schema sweep (task 7); per-doc OG cards (task 8) |
| Fri | Promote CSP (task 9); image polish (10, 11); Forge nginx pin (task 12) |

After that sprint, expect **93–96 / 100**. Beyond that lives in the SEO outcome layer (backlinks, mentions, rankings), not the SEO mechanics layer.

---

## Deferred / by design

- **`www.sigmie.com` DNS** — currently doesn't resolve. Decision call: add CNAME → apex, or leave (one canonical hostname is cleaner). No SEO impact either way.
- **Cloudflare email obfuscation** — wraps `mailto:` in `/cdn-cgi/l/email-protection` redirect URLs. Cosmetic noise; toggle in Scrape Shield if it bothers you.
- **`/resumes` priority in sitemap** — kept at 0.5; flagged in the previous audit. It's a real demo page (now with 106 SSR words and proper H1).
