<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Seo;

/**
 * `<changefreq>` hint of a {@see SitemapUrl}.
 *
 * The value is a hint, not a command: crawlers are free to ignore it, and
 * Google states that it does not use it at all. Omitting it is a valid and
 * common choice.
 *
 * @api
 */
enum ChangeFrequency: string
{
    case Always = 'always';
    case Hourly = 'hourly';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';
    case Never = 'never';
}
