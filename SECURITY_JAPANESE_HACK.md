# Japanese SEO Hack — Investigation & Remediation Guide

## What is a Japanese SEO Hack?

Attackers inject hidden Japanese-language spam links and pages into your website. These appear in Google Search from page 5+ under queries like:
- `site:dpmiindia.com` showing Japanese text results
- Titles/descriptions in Japanese (ブランド品, コピー品, etc.)

The injected pages are invisible to normal visitors but are crawled by Google.

## How Did It Happen?

Most common entry points:
1. **Outdated CMS / plugins** — unpatched Laravel packages, old dependencies
2. **Weak FTP/cPanel passwords**
3. **Writable upload directories** — PHP webshells uploaded as images
4. **Compromised hosting account**

---

## Immediate Steps

### Step 1 — Run the Scanner
```bash
php scripts/scan_japanese_hack.php
```

### Step 2 — Check for PHP webshells in uploads
```bash
find public/uploads -name "*.php" -o -name "*.phtml" -o -name "*.php5"
find storage/app/public -name "*.php"
```
Delete ANY .php files found there.

### Step 3 — Check database for injected content
Connect to your MySQL database and run:
```sql
-- Check pages/posts for Japanese characters
SELECT id, title, slug FROM pages WHERE content REGEXP '[ぁ-ん]|[ァ-ン]|[一-龯]';
SELECT id, title, slug FROM page_section_rows WHERE content REGEXP '[ぁ-ん]|[ァ-ン]|[一-龯]';

-- Check settings table
SELECT * FROM settings WHERE value REGEXP '[ぁ-ん]|[ァ-ン]|[一-龯]';
```

### Step 4 — Check .htaccess files for malicious rewrites
```bash
find . -name ".htaccess" | xargs grep -l "Rewrite" | head -20
cat public/.htaccess
```
Look for redirect rules pointing to Japanese domains.

### Step 5 — Check recently modified PHP files
```bash
find . -name "*.php" -newer public/index.php -not -path "./.git/*" | head -50
```

### Step 6 — Change all credentials
- cPanel / hosting panel password
- FTP password
- MySQL database password
- Laravel `.env` → generate new `APP_KEY`: `php artisan key:generate`
- Google Search Console access

### Step 7 — Fix file permissions
```bash
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod 600 .env
# Prevent PHP execution in uploads:
find public/uploads -type d -exec chmod 555 {} \;
```

### Step 8 — Remove spam URLs from Google
1. Go to [Google Search Console](https://search.google.com/search-console)
2. Select your property (`dpmiindia.com`)
3. Go to **Index > Removals** → request removal of Japanese spam URLs
4. Re-submit your sitemap: `https://www.dpmiindia.com/sitemap.xml`

### Step 9 — Deploy updated .htaccess
The `public/.htaccess` in this PR adds:
- Block Japanese characters in URLs
- Block PHP execution in uploads directory
- Block common webshell filenames
- Security headers (X-Frame-Options, X-Content-Type-Options, etc.)

### Step 10 — Consider a WAF
- [Cloudflare](https://cloudflare.com) — free tier blocks most bot traffic
- [Sucuri](https://sucuri.net) — specialized in WordPress/PHP hack cleanup
- [Imunify360](https://imunify360.com) — server-level protection (ask your host)

---

## Meta Tag Fixes (this PR)

| Issue | Before | After |
|-------|--------|-------|
| `og:type` | `content=""` (empty) | `content="website"` |
| Duplicate `og:image` | Two tags, second empty | One tag only |
| `twitter:title` | Missing | Added |
| `twitter:image` | Missing | Added |
| `twitter:card` | `summary` | `summary_large_image` |

These are in `resources/views/layouts/_meta_head.blade.php`.

Include this partial in your main layout `<head>` section:
```blade
@include('layouts._meta_head')
```

Pass page-specific values from controllers:
```php
return view('frontend.page', [
    'title'       => $page->title,
    'description' => $page->meta_description,
    'og_image'    => $page->og_image_url,
    'canonical'   => url()->current(),
]);
```
