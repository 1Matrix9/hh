<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    use ApiResponses;
    // Get all blogs (public - no auth required)
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        
        $blogs = Blog::orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->ok('Blogs retrieved successfully', [
            'blogs' => $blogs
        ]);
    }

    // Get single blog (public - no auth required)
    public function show($id)
    {
        $blog = Blog::find($id);

        if (!$blog) {
            return $this->error('Blog not found', 404);
        }

        return $this->ok('Blog retrieved successfully', [
            'blog' => $blog
        ]);
    }

    // Create blog (Admin only)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image_url' => 'nullable|string|url',
        ]);

        $blog = Blog::create($validated);

        return $this->ok('Blog created successfully', [
            'blog' => $blog
        ], 201);
    }

    
    public function update(Request $request, $id)
    {
        $blog = Blog::find($id);

        if (!$blog) {
            return $this->error('Blog not found', 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'image_url' => 'nullable|string|url',
        ]);

        $blog->update($validated);

        return $this->ok('Blog updated successfully', [
            'blog' => $blog
        ]);
    }

    // Delete blog (Admin only)
    public function destroy($id)
    {
        $blog = Blog::find($id);

        if (!$blog) {
            return $this->error('Blog not found', 404);
        }

        $blog->delete();

        return $this->ok('Blog deleted successfully');
    }
}