# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.0.1 — 2026-06-27

- Migrate test suite from PHPUnit to Testo. Internal change, no public API impact.
- CI: bump actions/checkout to v7.0.0.

## 1.0.0 — 2026-06-04

Next.js-style declarative SEO metadata for Yii3.

- `Metadata` — single declarative value object describing a page's SEO (title, description, keywords, authors, robots, alternates, OpenGraph, Twitter, icons, manifest, verification, JSON-LD, custom meta).
- `MetadataDefaults` — site-wide defaults merged into every page; provided via the `rasuvaeff/yii3-seo` `defaults` DI parameter. Carries `metadataBase`, title template, default OpenGraph/Twitter, icons, verification and robots.
- `Title` — `Title::of()` (template applied), `Title::absolute()` (template bypassed), `Title::template('%s | Acme', default: 'Acme')`.
- `Alternates` — canonical URL plus `hreflang` languages as a `locale => url` map.
- `OpenGraph` + `OgImage` — full `og:*` tags including multiple images with `width`/`height`/`alt`/`type` and `og:locale`.
- `TwitterCard` — `twitter:*` tags with `card` whitelist; falls back to OpenGraph for title/description/images.
- `Robots` — directives with static factories plus `withMaxSnippet()`, `withMaxImagePreview()`, `withMaxVideoPreview()` and a separate `googlebot` tag via `withGoogleBot()`.
- `Icons`/`Icon`, `Verification`, `Author` — typed icon links, search-engine verification meta and authorship.
- `metadataBase` resolves relative crawler-facing URLs (canonical, hreflang, `og:image`, `og:url`, `twitter:image`); a relative URL without a base throws.
- Fallback cascade (enabled by default): `og:title`/`og:description` derive from the page title/description, and `twitter:*` derive from OpenGraph.
- `SeoInjection` — implements `MetaTagsInjectionInterface` + `LinkTagsInjectionInterface`; merges defaults with the per-request `Metadata` and wires into `WebViewRenderer` automatically. `getTitle()` and `getJsonLdHtml()` for manual rendering in layout.
- `SeoMetadataEvent` + `SetSeoMetadataEventHandler` — event-based pattern for setting metadata from actions.
- `MetaTag` — typed custom `name`, `property`, `http-equiv` meta tags.
- `JsonLd` — `<script type="application/ld+json">` with `JSON_HEX_TAG` injection prevention.

