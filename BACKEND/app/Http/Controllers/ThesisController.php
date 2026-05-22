<?php

namespace App\Http\Controllers;

use App\Models\Thesis;
use App\Models\Author;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ThesisController extends Controller
{
    // --- READ / SEARCH (Extra Features Rubric) ---
    public function index(Request $request)
    {
        $query = Thesis::with(['authors', 'uploader']);

        // Extra Feature: Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('abstract', 'LIKE', "%{$search}%");
        }

        // Extra Feature: Filtering by Course
        if ($request->has('course')) {
            $query->where('course_category', $request->course);
        }

        return response()->json($query->orderBy('created_at', 'desc')->get(), 200);
    }

    // --- CREATE ---
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'abstract' => 'required|string',
            'publication_year' => 'required|integer',
            'course_category' => 'required|string',
            'file' => 'required|mimes:pdf|max:10240',
            'authors' => 'required|json', // Accept the dynamic authors as a JSON string
        ]);

        DB::beginTransaction();

        try {
            $filePath = $request->file('file')->store('theses_files', 'public');

            $thesis = Thesis::create([
                'title' => $validated['title'],
                'abstract' => $validated['abstract'],
                'publication_year' => $validated['publication_year'],
                'course_category' => $validated['course_category'],
                'file_path' => $filePath,
                'uploaded_by' => $request->user()->id,
            ]);

            // Decode the authors JSON from the frontend
            $authorsList = json_decode($validated['authors'], true);
            $authorIds = [];

            foreach ($authorsList as $authorData) {
                // firstOrCreate will check if the author exists. If not, it creates them instantly!
                $author = Author::firstOrCreate([
                    'first_name' => $authorData['first_name'],
                    'last_name' => $authorData['last_name']
                ]);
                $authorIds[] = $author->id;
            }

            // Link the authors to the thesis
            $thesis->authors()->attach($authorIds);

            AuditLog::create([
                'admin_id' => $request->user()->id,
                'action' => "Uploaded new thesis: " . $thesis->title,
            ]);

            DB::commit();
            return response()->json(['message' => 'Thesis uploaded successfully', 'thesis' => $thesis], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Upload failed', 'error' => $e->getMessage()], 500);
        }
    }

    // --- UPDATE ---
    public function update(Request $request, $id)
    {
        $thesis = Thesis::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'abstract' => 'sometimes|string',
            'publication_year' => 'sometimes|integer',
            'course_category' => 'sometimes|string',
            'authors' => 'sometimes|json', // Added JSON validation
        ]);

        DB::beginTransaction();

        try {
            $thesis->update($request->only(['title', 'abstract', 'publication_year', 'course_category']));

            if ($request->has('authors')) {
                $authorsList = json_decode($validated['authors'], true);
                $authorIds = [];

                foreach ($authorsList as $authorData) {
                    $author = Author::firstOrCreate([
                        'first_name' => $authorData['first_name'],
                        'last_name' => $authorData['last_name']
                    ]);
                    $authorIds[] = $author->id;
                }

                // Sync automatically removes old authors and links the updated ones
                $thesis->authors()->sync($authorIds);
            }

            AuditLog::create([
                'admin_id' => $request->user()->id,
                'action' => "Updated thesis ID: " . $thesis->id,
            ]);

            DB::commit();
            return response()->json(['message' => 'Thesis updated successfully', 'thesis' => $thesis], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Update failed', 'error' => $e->getMessage()], 500);
        }
    }

    // --- READ SINGLE THESIS ---
    public function show($id)
    {
        $thesis = Thesis::with(['authors', 'uploader'])->findOrFail($id);
        return response()->json($thesis, 200);
    }


    // --- DELETE ---
    public function destroy(Request $request, $id)
    {
        $thesis = Thesis::findOrFail($id);

        try {
            // Delete the physical PDF file from storage
            if (Storage::disk('public')->exists($thesis->file_path)) {
                Storage::disk('public')->delete($thesis->file_path);
            }

            // Log the action before deleting
            AuditLog::create([
                'admin_id' => $request->user()->id,
                'action' => "Deleted thesis: " . $thesis->title,
            ]);

            $thesis->delete(); // This automatically deletes pivot table entries too because of 'cascade' in migration

            return response()->json(['message' => 'Thesis deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Deletion failed', 'error' => $e->getMessage()], 500);
        }
    }
}
