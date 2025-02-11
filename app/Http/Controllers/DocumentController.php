<?php
namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    // Upload a document (Only authenticated users can upload)
    public function upload(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'file' => 'required|mimes:pdf,jpg,jpeg,png|max:2048' // Restrict file type and size
        ]);

        $file = $request->file('file');
        $filePath = $file->store('documents', 'public'); // Store file in 'storage/app/public/documents'

        $document = Document::create([
            'title' => $request->title,
            'file_path' => $filePath,
            'file_type' => $file->getClientMimeType(),
            'user_id' => Auth::id(), // Link document to authenticated user
        ]);

        return response()->json([
            'message' => 'Document uploaded successfully',
            'document' => $document
        ], 201);
    }

    // Get all documents (All users can see uploaded documents)
    public function index()
    {
        $documents = Document::with('user:id,name')->get(); // Load user name along with document

        return response()->json($documents);
    }

    // Download a document (Anyone can download any document)
    public function download($id)
    {
        $document = Document::findOrFail($id);

        return response()->download(storage_path("app/public/{$document->file_path}"));
    }

    // Delete a document (Only the owner can delete)
    public function delete($id)
    {
        $document = Document::findOrFail($id);

        // Check if the authenticated user is the owner of the document
        if ($document->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        Storage::disk('public')->delete($document->file_path); // Delete the file from storage
        $document->delete(); // Remove document record from database

        return response()->json(['message' => 'Document deleted successfully']);
    }
}
