# AGENTS.md — yii3-seo

Guidance for AI agents working on this package. Read before changing code.

## What this is

`rasuvaeff/yii3-seo` provides Next.js-style typed SEO metadata for Yii3. A single
declarative `Metadata` value object describes a page; site-wide `MetadataDefaults`
is merged in by `SeoInjection`, which wires into `WebViewRenderer` via
`MetaTagsInjectionInterface` and `LinkTagsInjectionInterface` so meta and link
tags land in `<head>` automatically.

Namespace: `Rasuvaeff\Yii3Seo`.

Public API (`@api`): `Metadata`, `MetadataDefaults`, `Title`, `Alternates`,
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

- Event flow: action dispatches `SeoMetadataEvent` → `SetSeoMetadataEventHandler::__invoke()` → `SeoInjection::setMetadata()` → `WebViewRenderer` reads tags.
- `SeoInjection` is mutable (`final class`) — it holds `?Metadata` state and a `readonly MetadataDefaults` (injected from the `rasuvaeff/yii3-seo` `defaults` param, default `new MetadataDefaults()`).
- All value objects are `final readonly class` except `Robots` (`final class`, clone-based `with*`) and `SeoInjection`.
- `Metadata`/`MetadataDefaults` normalize a `string` title to `Title::of()`.
- Title resolution lives in `SeoInjection::getTitle()`: page `Title` + defaults template/`default`. `Title::template()` validates the `%s` placeholder; substitution uses `str_replace` (NOT `sprintf`) so literal `%` is safe.
- Merge rules (defaults + page) live in `SeoInjection`: `openGraph`/`twitter` field-level inherit from defaults; `applicationName`/`generator`/`themeColor`/`colorScheme`/`robots`/`icons`/`verification` are page-or-default; `jsonLd`/`other` concatenate; the rest are page-only.
- Fallback cascade (on by default): `og:title`/`og:description` ← resolved title/description; `twitter:*` ← OpenGraph. Explicit values win. **This is our ergonomics, NOT Next.js behavior** (Next.js does not auto-derive `og:title`).
- `UrlResolver` resolves crawler-facing URLs against `metadataBase`: absolute → validated `FILTER_VALIDATE_URL`; relative → joined to base; relative without base → `InvalidArgumentException`. Applied to canonical, hreflang, `og:url`, `og:image`, `twitter:image`. Icons/manifest URLs are emitted as-is.
- URL VOs store raw strings (may be relative) — URL validation happens at render time in `UrlResolver`, NOT in the VO constructor. `MetadataDefaults::metadataBase` and `Author::url` are the exceptions: validated absolute in the constructor.
- `SeoInjection::getMetaTags()` returns `list<Yiisoft\Html\Tag\Meta>`; `getLinkTags()` returns `array<array-key, Yiisoft\Html\Tag\Link>` with `'canonical'`/`'manifest'` keys.
- `<title>` and JSON-LD have no injection interface — expose via `getTitle()` and `getJsonLdHtml()`.
- `Robots` whitelist: `all`, `index`, `noindex`, `follow`, `nofollow`, `none`, `noarchive`, `nosnippet`, `noimageindex`, `notranslate`, plus regex-validated `max-snippet:N`, `max-image-preview:none|standard|large`, `max-video-preview:N`.
- `Alternates` locale regex: `/^(?:[a-z]{2}(?:-[A-Z]{2})?|x-default)$/`; `languages` is a `locale => url` map.
- `TwitterCard` card whitelist: `summary`, `summary_large_image`, `app`, `player`.
- All `OpenGraph`/`TwitterCard` fields (including `type`/`card`) are nullable, so a page object inherits unset fields from the defaults field-by-field. The literal fallbacks `og:type` → `website` and `twitter:card` → `summary_large_image` are applied at render time in `SeoInjection`, not in the value objects.
- `config.platform.php = 8.3.20` in composer.json — needed because `yiisoft/csrf` (transitive dep) has a PHP upper bound; the actual runtime is 8.5.
- `httpsoft/http-message` in require-dev — satisfies `psr/http-factory-implementation` for composer resolution.
- PHP 8.3 target: no `new X()->method()` without parentheses — wrap as `(new X())->method()`.
- Code: `declare(strict_types=1)`, `final readonly class`, `#[\Override]`, explicit types, named arguments, trailing commas.

## When you finish

- Update `README.md`, `llms.txt` (and `examples/` if usage changed); update
  `CHANGELOG.md` when releasing.
- Re-run `composer build` and paste the output.
