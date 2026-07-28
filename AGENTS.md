# AGENTS.md — yii3-seo

Guidance for AI agents working on this package. Read before changing code.

## What this is

`rasuvaeff/yii3-seo` provides Next.js-inspired, Yii3-native typed SEO metadata.
A single declarative `Metadata` value object describes a page; site-wide
`MetadataDefaults` is merged by `MetadataResolver` into `ResolvedMetadata`.
`SeoInjection` wires the result into `WebViewRenderer` via
`MetaTagsInjectionInterface` and `LinkTagsInjectionInterface` so meta and link
tags land in `<head>` automatically.

Namespace: `Rasuvaeff\Yii3Seo`.

Public API (`@api`): `Metadata`, `MetadataDefaults`, `MetadataResolver`,
`ResolvedMetadata`, `MetadataValidator`, `MetadataValidationResult`,
`MetadataIssue`, `MetadataIssueSeverity`, `Title`, `Alternates`,
`SelfCanonical`, `SelfCanonicalMiddleware`,
`OpenGraph`, `OgImage`, `TwitterCard`, `Robots`, `Icons`, `Icon`, `Verification`,
`Author`, `MetaTag`, `JsonLd`, `SeoInjection`, `SeoMetadataEvent`,
`SetSeoMetadataEventHandler`. Internal (`@internal`): `UrlResolver`.

## Golden rules

1. **Verification is mandatory.** Never claim "done" without a fresh green
   `composer build`. "Should work" does not count.
2. **No suppressions.** No `@psalm-suppress`, no baseline. Fix the root cause.
3. **HTML escaping is Yiisoft\Html's job.** Never concatenate raw strings into
   HTML. Use `Html::meta()`, `Html::link()` and their fluent setters.
   JSON-LD must use `JSON_HEX_TAG` to prevent `</script>` injection.
4. **Preserve the public contract.** Update README + llms.txt + tests with any
   API change.

## Commands

No PHP/Composer on the host — run in Docker via the `composer:2` image.

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
docker run --rm -v "$PWD":/app -w /app composer:2 composer cs:fix
docker run --rm -v "$PWD":/app -w /app composer:2 composer psalm
docker run --rm -v "$PWD":/app -w /app composer:2 composer test
```

Or with Make: `make build`, `make cs-fix`, `make psalm`, `make test`.

`make test-coverage` and `make mutation` temporarily install and enable `pcov`
inside the `composer:2` container because the base image has no coverage driver.

## Invariants & gotchas

- Event flow: action dispatches `SeoMetadataEvent` → `SetSeoMetadataEventHandler::__invoke()`
  → `SeoInjection::setMetadata()` → `WebViewRenderer` reads meta/link tags. The
  listener ships in `config/events-web.php` and is registered automatically by
  `yiisoft/config`, so applications do not wire it themselves.
- **Do not build on `Yiisoft\View\Event\WebView\*`.** `WebViewEvent` — and with it
  `getView()` — is `@internal` to Yiisoft, so a `PageBegin` listener that sets the
  `<title>` or registers JSON-LD fails psalm (`InternalMethod`) and would need a
  suppression. That is why `<title>` and JSON-LD still come from the `$seo` layout
  parameter; it needs a public extension point upstream (`yiisoft/view` or a title
  injection interface in `yiisoft/yii-view-renderer`).
- **The `events-web` group only merges because the runner asks it to.**
  `ApplicationRunner::createDefaultConfig()` applies `RecursiveMerge`/`ReverseMerge`
  to the events groups (`HttpApplicationRunner` defaults the group to `events-web`),
  so two packages listening to the same event stack up. Without that modifier
  `yiisoft/config` throws `Duplicate key`. Verified against real `yiisoft/config`
  with a fake vendor layout; re-verify the same way before adding another group.
- `SeoInjection` is mutable (`final class`) — it holds the per-request inputs
  (`Metadata`, request path) plus readonly `MetadataDefaults`/`MetadataResolver`
  dependencies, and resolves **lazily and once**: `getResolvedMetadata()` caches,
  every setter invalidates the cache. **Any new per-request field must be nulled
  in `clear()`** or it leaks into the next request under a reusable runtime
  (RoadRunner) — `config/di.php` wires `reset` exactly for this.
- Self-canonical: `MetadataDefaults::selfCanonical` (a `SelfCanonical` policy,
  validated to require `metadataBase`) + `SelfCanonicalMiddleware` recording the
  request path. The policy is applied inside `MetadataResolver`, never in the
  middleware, so render/validate/export all see the same canonical URL. The
  request authority is never read — canonical URLs stay anchored to
  `metadataBase`. Allow-listed query parameters are emitted in allow-list order
  (canonical stability), and only string values survive.
- All value objects are `final readonly class` except `Robots` (`final class`, clone-based `with*`) and `SeoInjection`.
- `Metadata`/`MetadataDefaults` normalize a `string` title to `Title::of()`.
- Title resolution and merge rules live in `MetadataResolver`; `SeoInjection`
  only adapts `ResolvedMetadata` to Yii/HTML. `Title::template()` validates the
  `%s` placeholder; substitution uses `str_replace` (NOT `sprintf`) so literal
  `%` is safe.
- `openGraph`/`twitter` field-level inherit from defaults;
  `applicationName`/`generator`/`themeColor`/`colorScheme`/`robots`/`icons`/`verification`
  are page-or-default; `jsonLd`/`other` concatenate; the rest are page-only.
- Fallback cascade (on by default): `og:title`/`og:description` ← resolved title/description; `twitter:*` ← OpenGraph. Explicit values win. **This is our ergonomics, NOT Next.js behavior** (Next.js does not auto-derive `og:title`).
- `UrlResolver` resolves crawler-facing URLs against `metadataBase`: absolute → validated `FILTER_VALIDATE_URL`; relative → joined to base; relative without base → `InvalidArgumentException`. Applied to canonical, hreflang, `og:url`, `og:image`, `twitter:image`. Icons/manifest URLs are emitted as-is.
- URL VOs store raw strings (may be relative) — URL validation happens at render time in `UrlResolver`, NOT in the VO constructor. `MetadataDefaults::metadataBase` and `Author::url` are the exceptions: validated absolute in the constructor.
- `ResolvedMetadata::toArray()` is the export twin of rendering: it resolves the
  same crawler-facing URLs against `metadataBase` (and throws the same
  `InvalidArgumentException`), emits icon/manifest URLs as configured, applies
  the same `website`/`summary_large_image` defaults, and omits nulls and empty
  collections. Any change to what `SeoInjection` renders must be mirrored there.
- `MetadataValidator` must never throw: unresolvable URLs are reported as the
  `url.unresolvable` issue instead of propagating. Issue codes are a public
  contract — adding one is a minor change, renaming one is breaking.
- `MetadataValidator` uses `mb_strlen`, so `ext-mbstring` is in `require` and
  `mbstring` is in `extensions:` of every CI job. Do not drop either.
- `SeoInjection::getMetaTags()` returns `list<Yiisoft\Html\Tag\Meta>`; `getLinkTags()` returns `array<array-key, Yiisoft\Html\Tag\Link>` with `'canonical'`/`'manifest'` keys.
- `SeoInjection` implements layout/meta/link injection. The layout receives it
  as `$seo`; `<title>` and JSON-LD use `$seo->getTitle()` and
  `$seo->getJsonLdHtml()` because Yii has no dedicated injection interfaces for
  those elements.
- `Robots` whitelist: `all`, `index`, `noindex`, `follow`, `nofollow`, `none`, `noarchive`, `nosnippet`, `noimageindex`, `notranslate`, plus regex-validated `max-snippet:N`, `max-image-preview:none|standard|large`, `max-video-preview:N`.
- `Alternates` locale regex: `/^(?:[a-z]{2}(?:-[A-Z]{2})?|x-default)\z/`; `languages` is a `locale => url` map.
- `TwitterCard` card whitelist: `summary`, `summary_large_image`, `app`, `player`.
- All input `OpenGraph`/`TwitterCard` fields (including `type`/`card`) are
  nullable, so a page object inherits unset fields from defaults field by field.
  `MetadataResolver` applies the final `website` and `summary_large_image`
  fallbacks in `ResolvedMetadata`; input value objects remain nullable.
- `config.platform.php = 8.3.20` in composer.json — needed because `yiisoft/csrf` (transitive dep) has a PHP upper bound; the actual runtime is 8.5.
- `httpsoft/http-message` in require-dev — satisfies `psr/http-factory-implementation` for composer resolution.
- PHP 8.3 target: no `new X()->method()` without parentheses — wrap as `(new X())->method()`.
- Code: `declare(strict_types=1)`, `final readonly class`, `#[\Override]`, explicit types, named arguments, trailing commas.

- `resources/skills/rasuvaeff-yii3-seo/SKILL.md` is distributed to consumers by the
  `llm/skills` Composer plugin (declared via `extra.skills`). It is a thin wrapper
  over `llms.txt`, not a third copy of the API: keep the safety rules and merge
  table in sync, and leave the exhaustive reference in `llms.txt`. Never add
  `resources` to `.gitattributes` `export-ignore` — the skill must reach dist.

## When you finish

- Update `README.md`, `llms.txt` (and `examples/` if usage changed); update
  `CHANGELOG.md` when releasing.
- Re-run `composer build` and paste the output.
