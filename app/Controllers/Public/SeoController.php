<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\Blog;
use App\Models\Service;

final class SeoController extends Controller
{
    public function sitemap(): void
    {
        $config = require dirname(__DIR__, 3) . '/config/config.php';
        $baseUrl = $config['app']['url'];

        $urls = [
            ['loc' => '/', 'priority' => '1.0'],
            ['loc' => '/about', 'priority' => '0.8'],
            ['loc' => '/services', 'priority' => '0.9'],
            ['loc' => '/team', 'priority' => '0.7'],
            ['loc' => '/gallery', 'priority' => '0.5'],
            ['loc' => '/blog', 'priority' => '0.6'],
            ['loc' => '/testimonials', 'priority' => '0.5'],
            ['loc' => '/contact', 'priority' => '0.6'],
            ['loc' => '/book-appointment', 'priority' => '0.9'],
        ];

        foreach (Service::activeOrdered() as $service) {
            $urls[] = ['loc' => '/services/' . $service['slug'], 'priority' => '0.7'];
        }

        $posts = Blog::publishedPaginated(1, 1000);
        foreach ($posts['data'] as $post) {
            $urls[] = ['loc' => '/blog/' . $post['slug'], 'priority' => '0.6'];
        }

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $url) {
            echo '  <url><loc>' . htmlspecialchars($baseUrl . $url['loc'], ENT_QUOTES, 'UTF-8') . '</loc>'
                . '<priority>' . $url['priority'] . '</priority></url>' . "\n";
        }
        echo '</urlset>';
    }

    public function robots(): void
    {
        $config = require dirname(__DIR__, 3) . '/config/config.php';
        $baseUrl = $config['app']['url'];

        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\n";
        echo "Disallow: /admin\n";
        echo "Disallow: /patient\n";
        echo "Allow: /\n";
        echo "Sitemap: {$baseUrl}/sitemap.xml\n";
    }
}
