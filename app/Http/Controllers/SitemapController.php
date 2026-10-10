<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function sitemap(): Response
    {
        $urls = [
            ['loc' => $this->urlFor('/'), 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => $this->urlFor('/danh-muc'), 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['loc' => $this->urlFor('/san-pham'), 'changefreq' => 'daily', 'priority' => '0.9'],
            ['loc' => $this->urlFor('/dieu-khoan-giao-dich'), 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => $this->urlFor('/chinh-sach-doi-tra'), 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => $this->urlFor('/chinh-sach-bao-mat'), 'changefreq' => 'monthly', 'priority' => '0.6'],
        ];

        Category::query()->orderBy('id')->get()->each(function (Category $category) use (&$urls): void {
            $urls[] = [
                'loc' => $this->urlFor('/danh-muc/'.rawurlencode($category->slug)),
                'lastmod' => $category->updated_at?->toDateString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        });

        Product::query()->where('is_active', true)->orderBy('id')->get()->each(function (Product $product) use (&$urls): void {
            $urls[] = [
                'loc' => $this->urlFor('/san-pham/'.rawurlencode($product->slug)),
                'lastmod' => $product->updated_at?->toDateString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            foreach (['loc', 'lastmod', 'changefreq', 'priority'] as $key) {
                if (! empty($url[$key])) {
                    $xml .= '    <'.$key.'>'.$this->escape($url[$key]).'</'.$key.">\n";
                }
            }
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $content = implode("\n", [
            'User-agent: *',
            'Disallow: /admin/',
            'Disallow: /cart/',
            'Disallow: /checkout/',
            'Disallow: /password/',
            '',
            'Allow: /',
            'Sitemap: '.$this->urlFor('/sitemap.xml'),
        ]);

        return response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function urlFor(string $path): string
    {
        return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
    }
}
