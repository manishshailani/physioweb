<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Database;
use App\Core\FileUpload;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Service as ServiceModel;

final class BlogController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        $this->view('admin.blog.index', [
            'page_title' => 'Blog',
            'posts' => Database::fetchAll(
                'SELECT b.*, bc.name AS category_name FROM blogs b
                 LEFT JOIN blog_categories bc ON bc.id = b.category_id
                 ORDER BY b.created_at DESC'
            ),
        ], 'layouts.admin');
    }

    public function create(): void
    {
        $this->requireAdmin();
        $this->view('admin.blog.form', [
            'page_title' => 'New Blog Post',
            'post' => null,
            'categories' => BlogCategory::allOrdered(),
        ], 'layouts.admin');
    }

    public function store(Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);

        [$data, $errors] = $this->validatedData($request);
        if ($errors !== []) {
            $this->flashErrors($errors, $request->all());
            $this->redirect('/admin/blog/create');
        }

        $image = $request->file('featured_image');
        if ($image !== null) {
            $uploaded = FileUpload::store($image, dirname(__DIR__, 3) . '/public/uploads/blog', 'image', 5);
            if (!$uploaded['ok']) {
                $this->flashError($uploaded['error']);
                $this->redirect('/admin/blog/create');
            }
            $data['featured_image'] = '/uploads/blog/' . $uploaded['stored_name'];
        }

        $data['slug'] = Blog::uniqueSlug($data['title']);
        $data['author_user_id'] = $this->currentAdminId();
        $data['created_at'] = Database::now();
        $data['updated_at'] = Database::now();
        $id = Blog::create($data);
        AuditLogger::log($this->currentAdminId(), 'admin', 'create', 'blog', $id, 'Created blog post ' . $data['title']);

        $this->flashSuccess('Blog post created.');
        $this->redirect('/admin/blog');
    }

    public function edit(array $params): void
    {
        $this->requireAdmin();
        $post = Blog::find((int) $params['id']);
        if ($post === null) {
            Response::notFound('Blog post not found.');
        }
        $this->view('admin.blog.form', [
            'page_title' => 'Edit Blog Post',
            'post' => $post,
            'categories' => BlogCategory::allOrdered(),
        ], 'layouts.admin');
    }

    public function update(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $post = Blog::find($id);
        if ($post === null) {
            Response::notFound('Blog post not found.');
        }

        [$data, $errors] = $this->validatedData($request);
        if ($errors !== []) {
            $this->flashErrors($errors, $request->all());
            $this->redirect('/admin/blog/' . $id . '/edit');
        }

        $image = $request->file('featured_image');
        if ($image !== null) {
            $uploaded = FileUpload::store($image, dirname(__DIR__, 3) . '/public/uploads/blog', 'image', 5);
            if (!$uploaded['ok']) {
                $this->flashError($uploaded['error']);
                $this->redirect('/admin/blog/' . $id . '/edit');
            }
            $data['featured_image'] = '/uploads/blog/' . $uploaded['stored_name'];
        }

        if ($data['title'] !== $post['title']) {
            $data['slug'] = Blog::uniqueSlug($data['title'], $id);
        }
        $data['updated_at'] = Database::now();
        Blog::updateById($id, $data);
        AuditLogger::log($this->currentAdminId(), 'admin', 'update', 'blog', $id, 'Updated blog post ' . $data['title']);

        $this->flashSuccess('Blog post updated.');
        $this->redirect('/admin/blog');
    }

    public function destroy(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $post = Blog::find($id);
        if ($post !== null) {
            Blog::deleteById($id);
            AuditLogger::log($this->currentAdminId(), 'admin', 'delete', 'blog', $id, 'Deleted blog post ' . $post['title']);
        }
        $this->flashSuccess('Blog post deleted.');
        $this->redirect('/admin/blog');
    }

    public function categories(): void
    {
        $this->requireAdmin();
        $this->view('admin.blog.categories', [
            'page_title' => 'Blog Categories',
            'categories' => BlogCategory::allOrdered(),
        ], 'layouts.admin');
    }

    public function storeCategory(Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);

        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            $this->flashError('Category name is required.');
            $this->redirect('/admin/blog-categories');
        }

        $id = BlogCategory::create([
            'name' => $name,
            'slug' => ServiceModel::slugify($name),
            'created_at' => Database::now(),
        ]);
        AuditLogger::log($this->currentAdminId(), 'admin', 'create', 'blog_category', $id, 'Created blog category ' . $name);

        $this->flashSuccess('Category added.');
        $this->redirect('/admin/blog-categories');
    }

    public function destroyCategory(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $category = BlogCategory::find($id);
        if ($category !== null) {
            // blogs.category_id is ON DELETE SET NULL, so deleting a category simply
            // un-categorizes its posts rather than deleting them.
            BlogCategory::deleteById($id);
            AuditLogger::log($this->currentAdminId(), 'admin', 'delete', 'blog_category', $id, 'Deleted blog category ' . $category['name']);
        }
        $this->flashSuccess('Category deleted.');
        $this->redirect('/admin/blog-categories');
    }

    /** @return array{0: array, 1: array} [sanitized data, validation errors] */
    private function validatedData(Request $request): array
    {
        $data = $request->only(['category_id', 'title', 'excerpt', 'content', 'tags', 'meta_title', 'meta_description', 'status']);

        $v = new Validator($data);
        $v->required('title')->maxLength('title', 200);
        $v->required('content');
        $v->in('status', ['draft', 'published']);

        $sanitized = [
            'category_id' => $data['category_id'] !== '' ? (int) $data['category_id'] : null,
            'title' => $data['title'],
            'excerpt' => $data['excerpt'] !== '' ? $data['excerpt'] : null,
            'content' => $data['content'],
            'tags' => $data['tags'] !== '' ? $data['tags'] : null,
            'meta_title' => $data['meta_title'] !== '' ? $data['meta_title'] : null,
            'meta_description' => $data['meta_description'] !== '' ? $data['meta_description'] : null,
            'status' => $data['status'] !== '' ? $data['status'] : 'draft',
            'published_at' => $data['status'] === 'published' ? Database::now() : null,
        ];

        return [$sanitized, $v->errors()];
    }
}
