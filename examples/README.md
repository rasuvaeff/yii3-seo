# Examples

| Script | Shows | Needs server? |
|---|---|---|
| `basic.php` | Event flow: `SeoMetadataEvent` → `SetSeoMetadataEventHandler` → `SeoInjection` | No |
| `full-page.php` | Defaults + page: title template, metadataBase resolution, OG images, Twitter, robots, verification, icons, hreflang, JSON-LD | No |
| `diagnostics.php` | `MetadataValidator` issues plus `ResolvedMetadata::toArray()` export | No |
| `sitemap.php` | `SitemapProviderInterface`, streamed `Sitemap`, and `SitemapFileExporter` splitting into chunks plus an index | No |
| `robots-txt.php` | Typed `RobotsTxt`, the forced non-production disallow, and `X-Robots-Tag` header values | No |
| `yii3-app.php` | Minimal Yii3 app wiring: params, DI, events, action, layout | No |

`basic.php` and `full-page.php` simulate the Yii3 event flow without a running framework — the handler is called directly instead of going through `EventDispatcherInterface`.
`yii3-app.php` is a copy-paste-oriented integration sketch for a real app.

## Running

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 php examples/basic.php
docker run --rm -v "$PWD":/app -w /app composer:2 php examples/full-page.php
docker run --rm -v "$PWD":/app -w /app composer:2 php examples/diagnostics.php
docker run --rm -v "$PWD":/app -w /app composer:2 php examples/sitemap.php
docker run --rm -v "$PWD":/app -w /app composer:2 php examples/robots-txt.php
docker run --rm -v "$PWD":/app -w /app composer:2 php examples/yii3-app.php
```
