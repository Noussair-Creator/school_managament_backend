<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller

{
    // Apply authentication middleware
    public function __construct()
    {
        $this->middleware('auth'); // Ensure user is authenticated
        $this->middleware('role.permission:create comment')->only('addComment');
        $this->middleware('role.permission:update comment')->only('updateComment');
        $this->middleware('role.permission:delete comment')->only('deleteComment');
        $this->middleware('role.permission:show comment')->only('showComments');
    }
    // Show all comments for a post
    public function showComments($postId)
    {
        $post = Post::find($postId);

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $comments = $post->comments; // Get all comments for the post

        return response()->json($comments);
    }

    // Add a comment to a post
    public function addComment(Request $request, $postId)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $post = Post::find($postId);

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        // Create the comment
        $comment = Comment::create([
            'content' => $validated['content'],
            'user_id' => Auth::id(), // The authenticated user
            'post_id' => $postId
        ]);

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => $comment
        ], 201);
    }

    // Update a comment
    public function updateComment(Request $request, $commentId)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000', // Validation for the comment content
        ]);

        // Find the comment by ID
        $comment = Comment::find($commentId);

        if (!$comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        // Ensure the user is the one who created the comment (authorization check)
        if ($comment->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Update the content of the comment
        $comment->content = $validated['content'];
        $comment->save();

        return response()->json([
            'message' => 'Comment updated successfully',
            'comment' => $comment
        ]);
    }

    // Delete a comment
    public function deleteComment($commentId)
    {
        $comment = Comment::find($commentId);

        if (!$comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        // Ensure the user is the one who created the comment (optional)
        if ($comment->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully']);
    }
}
