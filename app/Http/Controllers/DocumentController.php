<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    // Apply authentication middleware
    public function __construct()
{
    $this->middleware('auth')->only('upload', 'index'); // Ensure the user is authenticated
    $this->middleware('role.permission:,create document')->only('upload'); // Only users with permission to create documents
    $this->middleware('role.permission:,delete document')->only('delete'); // Only users with permission to delete documents
}

    // Upload a document (Only authenticated users can upload)
    public function upload(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'title' => 'required|string|max:255', // Validate title
            'file' => 'required|mimes:pdf,jpg,jpeg,png|max:2048' // Validate file type and size
        ]);

        // Handle the uploaded file
        $file = $request->file('file');
        $filePath = $file->store('documents', 'public'); // Store file in 'storage/app/public/documents'

        // Create a new document entry in the database
        $document = Document::create([
            'title' => $request->title,
            'file_path' => $filePath,
            'file_type' => $file->getClientMimeType(),
            'user_id' => Auth::id(), // Link document to the authenticated user
        ]);

        // Return a response with the created document information
        return response()->json([
            'message' => 'Document uploaded successfully',
            'document' => $document
        ], 201);
    }

    // Get all documents (All users can see uploaded documents)
    public function index()
    {
        // Retrieve all documents, including the associated user information
        $documents = Document::with('user:id,name')->get(); // Load user name along with document

        // Check if documents are empty
        if ($documents->isEmpty()) {
            // Return a custom message if no documents are found
            return response()->json([
                'message' => 'No documents found.'
            ], 404); // You can change the status code to 200 if you prefer.
        }

        // Return the documents as JSON
        return response()->json($documents);
    }

    // Download a document (Anyone can download any document)
    public function download($documentId)
    {
        // Find the document by ID or fail if not found
        $document = Document::findOrFail($documentId);

        // Return the document as a downloadable response
        return response()->download(storage_path("app/public/{$document->file_path}"));
    }

    // Delete a document (Only the owner can delete)
    public function delete($documentId)
    {
        // Find the document by ID
        $document = Document::find($documentId);

        // If the document doesn't exist, return a custom error message
        if (!$document) {
            return response()->json(['message' => 'Document not found.'], 404);
        }

        // Check if the authenticated user is the owner of the document
        if ($document->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete the document file from storage
        Storage::disk('public')->delete($document->file_path);

        // Delete the document record from the database
        $document->delete();

        // Return a success message
        return response()->json(['message' => 'Document deleted successfully']);
    }


    public function show($documentId){
        $document = Document::find($documentId);

        if (!$document) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        return response()->json($document);
    }
}
