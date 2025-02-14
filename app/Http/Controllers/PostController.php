<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    // Show all posts
    public function index()
    {
        $posts = Post::with('user', 'comments')->get(); // Eager load user and comments for each post

        return response()->json($posts);
    }

    // Show a specific post
    public function show($postId)
    {
        $post = Post::with('user', 'comments:id,user_id,post_id,content')->find($postId);

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        return response()->json($post);
    }

    // Create a new post
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $post = Post::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'user_id' => Auth::id(), // The authenticated user
        ]);

        return response()->json([
            'message' => 'Post created successfully',
            'post' => $post
        ], 201);
    }

    // Update a post
    public function update(Request $request, $postId)
    {
        $validated = $request->validate([
            'title' => 'string|max:255',
            'content' => 'string',
        ]);

        $post = Post::find($postId);

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        // Ensure the user is the one who created the post (authorization check)
        if ($post->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $post->title = $validated['title'] ?? $post->title;
        $post->content = $validated['content'] ?? $post->content;
        $post->save();

        return response()->json([
            'message' => 'Post updated successfully',
            'post' => $post
        ]);
    }

    // Delete a post
    public function delete($postId)
    {
        $post = Post::find($postId);

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        // Ensure the user is the one who created the post (authorization check)
        if ($post->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully']);
    }
}
