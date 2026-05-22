<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Fetch all users
    public function index()
    {
        return response()->json(User::orderBy('created_at', 'desc')->get(), 200);
    }

    // Create a new user (Admin or Student)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'identifier' => 'required|string|unique:users,identifier',
            'name' => 'required|string|max:255',
            'role' => 'required|in:admin,student',
            'course' => 'nullable|string',
            'year_level' => 'nullable|integer',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'identifier' => $validated['identifier'],
            'name' => $validated['name'],
            'role' => $validated['role'],
            'course' => $validated['role'] === 'student' ? $validated['course'] : null,
            'year_level' => $validated['role'] === 'student' ? $validated['year_level'] : null,
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json(['message' => 'User created successfully', 'user' => $user], 201);
    }

    // Delete a user
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Prevent the admin from accidentally deleting themselves!
        if (request()->user()->id === $user->id) {
            return response()->json(['message' => 'You cannot delete yourself!'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'User deleted successfully'], 200);
    }


    // Update an existing user
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            // The unique rule needs to ignore the current user's ID so they don't trigger an error on their own email
            'identifier' => 'sometimes|string|unique:users,identifier,' . $id,
            'name' => 'sometimes|string|max:255',
            'role' => 'sometimes|in:admin,student',
            'course' => 'nullable|string',
            'year_level' => 'nullable|integer',
            'password' => 'nullable|string|min:8', // Password is optional on update
        ]);

        // Only hash and update the password if the Admin actually typed a new one
        if (!empty($validated['password'])) {
            $validated['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        } else {
            unset($validated['password']); // Remove it from the array so we don't overwrite it with null
        }

        // If they changed a student to an admin, clear the course/year data
        if (isset($validated['role']) && $validated['role'] === 'admin') {
            $validated['course'] = null;
            $validated['year_level'] = null;
        }

        $user->update($validated);

        return response()->json(['message' => 'User updated successfully', 'user' => $user], 200);
    }
}
