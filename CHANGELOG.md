# Changelog

## Unreleased

- Add an opt-in self-canonical strategy: `SelfCanonical` (site-wide policy on
  `MetadataDefaults`) plus `SelfCanonicalMiddleware`, which records the current
  request path. Pages that do not declare `Alternates::canonical` get a canonical
  URL derived from `metadataBase` and the request path. The request authority is
  never used, query parameters are dropped unless allow-listed, allow-listed
  parameters are emitted in the configured order, and explicit canonical values
  stay authoritative.
- `SeoInjection` now resolves lazily and caches the result until `setMetadata()`,
  `setRequestPath()` or `clear()`/`reset()` is called; `clear()` drops the request
  path as well so nothing leaks between requests in a reusable runtime.
- Add `ResolvedMetadata::toArray()`: a normalized array export of the resolved
  metadata for JSON APIs, SPA payloads, preview tooling and debugging. It
  resolves crawler-facing URLs against `metadataBase` exactly as rendering does,
  keeps icon/manifest URLs as configured, and omits nulls and empty collections.
- Add `MetadataValidator` with `MetadataValidationResult`, `MetadataIssue` and
  `MetadataIssueSeverity`: typed SEO diagnostics for missing title, description,
  canonical and social image; missing image alt/dimensions; unresolvable
  crawler-facing URLs; canonical/`og:url` mismatch; conflicting robots
  directives and duplicate custom meta tags. Suggested title and description
  lengths are advisory warnings. The validator never throws.
- Require `ext-mbstring` for character-accurate title and description length
  diagnostics.
- Add `MetadataResolver` and immutable `ResolvedMetadata` as the public,
  renderer-independent result of defaults merging, title templates and social
  fallback rules; expose it through `SeoInjection::getResolvedMetadata()`.
- Refactor `SeoInjection` into a Yii/HTML adapter over the shared resolved
  result without changing rendered metadata.
- Position the package accurately as Next.js-inspired and Yii3-native, with its
  field-level social merge differences documented.
- Expose `SeoInjection` to Yii layouts as the `$seo` parameter through
  `LayoutParametersInjectionInterface`, removing the need to inject it into the
  layout separately.
- Run the full-head Integration suite in the build workflow on the supported
  PHP matrix and prefer-lowest dependencies.
- Add a prioritized product roadmap for frictionless metadata, crawlability and
  rich-result support.
- Align the existing source with the current Rector rules so `release-check`
  remains green.

## 1.0.3 — 2026-07-25

- Reject trailing newlines in hreflang/robots directive validation: anchor
  `Alternates::LOCALE_PATTERN` and the three `Robots` max-* directive regexes
  with `\z` instead of `$` (PCRE `$` matches before a trailing `\n`, which let
  `"<value>\n"` pass and reach the emitted HTML).

## 1.0.2 — 2026-06-30

- Add `/benchmarks` and `/Makefile` to `.gitattributes` export-ignore.

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
