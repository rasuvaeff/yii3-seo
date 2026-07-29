# yii3-seo roadmap

This roadmap describes how `rasuvaeff/yii3-seo` can grow from a typed head
metadata renderer into a complete, attractive SEO toolkit for Yii3. It is a
direction document, not a compatibility promise; public API details still need
design and release review.

## Current position

The package already has a strong technical foundation:

- immutable, declarative page metadata with site-wide defaults;
- title templates and explicit absolute titles;
- canonical and hreflang links;
- Open Graph and Twitter Card fallbacks;
- robots directives, icons, verification tags and custom metadata;
- safely encoded JSON-LD;
- Yii3 DI, event and reusable-runtime reset integration;
- strict static analysis, mutation testing and an integration test that renders
  a complete head.

The main product gap is not the number of supported meta tags. The package
still asks an application to perform several integration steps and manually
handle title and JSON-LD output. It also stops at HTML head metadata rather than
covering crawlability through sitemaps and `robots.txt`.

As of 2026-07-28, Packagist reports two downloads and no dependents. There is
no visible direct Yii3 SEO competitor, while mature Yii2 sitemap packages have
tens or hundreds of thousands of downloads. This suggests that reducing setup
friction and covering a complete SEO workflow will create more value than
adding isolated, uncommon metadata fields.

## Product principles

1. Prefer useful defaults and short integration paths over a larger surface.
2. Keep all inferred public URLs anchored to configured `metadataBase`; never
   trust an arbitrary request Host header when generating crawler-facing URLs.
3. Resolve metadata once, then render, validate and export the same result.
4. Add curated structured-data types rather than attempting to model all of
   schema.org.
5. Keep generic escape hatches (`MetaTag`, `JsonLd::fromArray()`) for uncommon
   and newly introduced standards.
6. Describe the API as Next.js-inspired and Yii3-native. Its merge and fallback
   behavior is intentionally not identical to Next.js.

## 1.1 - Frictionless metadata

### Integration

- [x] Expose `SeoInjection` to layouts as the `$seo` layout parameter through
  Yii's `LayoutParametersInjectionInterface`.
- [ ] Register title and JSON-LD through a Yii WebView integration so standard
  layouts do not need package-specific object injection. **Blocked upstream:**
  every `Yiisoft\View\Event\WebView\*` event extends `WebViewEvent`, which is
  `@internal` to Yiisoft, so a listener cannot call `getView()` without a psalm
  suppression. This needs a public extension point — a title/script injection
  interface in `yiisoft/yii-view-renderer`, or a non-internal view event.
- [x] Investigate automatic event-handler registration through `yiisoft/config`;
  retain direct `SeoInjection::setMetadata()` as the simplest alternative.
  Shipped as an `events-web` group. Verified against real `yiisoft/config`: the
  group only merges because `ApplicationRunner` applies `RecursiveMerge` to the
  events groups — without it two packages listening to one event collide with
  `Duplicate key`.
- [x] Replace the multi-step README introduction with a 60-second quickstart and
  show the exact rendered head.

### Resolution and export

- [x] Extract merge and fallback rules from `SeoInjection` into a dedicated
  `MetadataResolver`.
- [x] Introduce immutable `ResolvedMetadata` as the single normalized result.
- [x] Render Yii tags from that result; complete HTML and structured array
  exporters remain planned.
- [x] Add `toArray()` for API, SPA, preview and debugging use cases.

### Automatic canonical URL

- [x] Add an opt-in self-canonical strategy using `metadataBase` plus the
  current request path.
- [x] Ignore the request authority and strip query parameters by default.
- [x] Support an explicit query allow-list for pages whose canonical identity
  genuinely includes selected query parameters.
- [x] Keep explicit `Alternates::canonical` values authoritative.

### Diagnostics

- [x] Add `MetadataValidator` returning typed warning/error objects.
- [x] Detect missing title, description, canonical and social image.
- [x] Detect missing image alt/dimensions, duplicate custom tags, conflicting
  robots directives and canonical/`og:url` mismatches.
- [x] Keep suggested title/description lengths advisory rather than hard
  validation failures.

### Quality gate

- [x] Run the existing full-head Integration suite in CI in addition to the
  standard `composer build` gate.
- [x] Add snapshots for a minimal page, article, product and multilingual page.

## 1.2 - Crawlability

### XML sitemap

- [x] Add typed `SitemapUrl`, `Sitemap` and `SitemapIndex` APIs.
- [x] Add `SitemapProviderInterface` for application-owned URL sources.
- [x] Support `lastmod`, images and hreflang alternatives.
- [x] Stream XML so large sites are not held fully in memory. Documents
  implement `SitemapDocumentInterface::toChunks()` and pull one URL at a time.
- [x] Split output at protocol limits and generate the corresponding sitemap
  index. `SitemapFileExporter` enforces both the 50 000-URL and the 50 MiB
  limit by measuring each rendered entry before appending it.
- [x] Provide both PSR-7 responses and deterministic file export.
- [x] Prefer provider-based generation; defer automatic site crawling to an
  optional adapter or separate package.

### robots.txt and response policy

- [ ] Add typed `RobotsTxt` groups with `User-agent`, `Allow`, `Disallow` and
  `Sitemap` directives.
- [ ] Provide a PSR-7 response/action suitable for a Yii route.
- [ ] Support environment policies, including a forced non-production disallow.
- [ ] Allow a `Robots` policy to be serialized as `X-Robots-Tag` for non-HTML
  resources.

## 1.3 - Rich results and social metadata

### Curated JSON-LD

- [ ] Introduce a small structured-data contract that converts to JSON-LD.
- [ ] Add builders for Organization, WebSite, WebPage, BreadcrumbList, Article
  and Product.
- [ ] Add `SchemaGraph` with stable `@id` references and `@graph` output.
- [ ] Preserve `JsonLd::fromArray()` for schemas outside the curated set.
- [ ] Do not add a generated model for the complete schema.org vocabulary.

### International metadata

- [ ] Replace the narrow hreflang regex with correct BCP 47 handling, including
  script and multi-part tags such as `zh-Hant` and `sr-Latn-RS`.
- [ ] Add RSS/Atom type alternatives and media alternatives.
- [ ] Add Open Graph alternate locales.

### Open Graph and Twitter/X

- [ ] Add typed audio and video media.
- [ ] Add article/profile vertical properties where they produce standard
  metadata with clear consumer support.
- [ ] Replace Twitter image strings with a typed image carrying `alt`.
- [ ] Add site/creator IDs and app/player card properties.
- [ ] Expand robots support with `indexifembedded`, `nositelinkssearchbox`,
  `unavailable_after` and typed bot-specific policies.

## Documentation and adoption

- [x] Change “mirrors the Next.js Metadata API” to “Next.js-inspired,
  Yii3-native” and document behavioral differences.
- [ ] Add copy-paste recipes for a basic page, article, product and multilingual
  site.
- [ ] Add a real Yii application example instead of only an integration sketch.
- [ ] Document migration from common Yii2 SEO approaches.
- [ ] Add relevant GitHub topics and update Packagist keywords as features ship.
- [ ] Publish rendered-output examples and explain safe defaults near the top of
  the README.

## Explicit non-goals for now

- AI-generated titles or descriptions;
- a database-backed CMS/editor in the core package;
- a full schema.org class hierarchy;
- automatic crawling in the core package;
- low-value parity fields such as `archives`, `assets` and `bookmarks` before
  the crawlability and diagnostics milestones are complete.
