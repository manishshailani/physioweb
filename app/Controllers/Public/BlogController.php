<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Setting;

final class BlogController extends Controller
{
    public function index(array $params, Request $request): void
    {
        $page = max(1, (int) $request->query('page', 1));
        $search = (string) $request->query('q', '');
        $result = Blog::publishedPaginated($page, 9, null, $search);

        $this->view('public.blog.index', [
            'posts' => $result['data'],
            'pagination' => $result,
            'categories' => BlogCategory::allOrdered(),
            'search' => $search,
            'settings' => Setting::allAsMap(),
            'page_title' => 'Blog',
        ], 'layouts.public');
    }

    public function category(array $params, Request $request): void
    {
        $category = BlogCategory::findBySlug($params['slug']);
        if ($category === null) {
            Response::notFound('Category not found.');
        }
        $page = max(1, (int) $request->query('page', 1));
        $result = Blog::publishedPaginated($page, 9, (int) $category['id']);

        $this->view('public.blog.index', [
            'posts' => $result['data'],
            'pagination' => $result,
            'categories' => BlogCategory::allOrdered(),
            'activeCategory' => $category,
            'search' => '',
            'settings' => Setting::allAsMap(),
            'page_title' => $category['name'],
        ], 'layouts.public');
    }

    public function show(array $params): void
    {
        $post = Blog::findPublishedBySlug($params['slug']);
        if ($post === null) {
            Response::notFound('Blog post not found.');
        }
        $this->view('public.blog.show', [
            'post' => $post,
            'settings' => Setting::allAsMap(),
            'page_title' => $post['meta_title'] ?: $post['title'],
        ], 'layouts.public');
    }
}
