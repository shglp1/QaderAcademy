<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * List all categories
     */
    public function index()
    {
        $categories = Category::withCount('courses')->get();
        
        return CategoryResource::collection($categories);
    }

    /**
     * Store a new category
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'type' => 'required|in:university,soft_skills,professional_development',
            'year' => 'nullable|integer|min:1|max:6',
            'semester' => 'nullable|in:first,second'
        ]);

        $category = Category::create($validated);

        return new CategoryResource($category);
    }

    /**
     * Display a specific category
     */
    public function show(Category $category)
    {
        return new CategoryResource($category->load('courses'));
    }

    /**
     * Update a category
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name_en' => 'sometimes|required|string|max:255',
            'name_ar' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:university,soft_skills,professional_development',
            'year' => 'nullable|integer|min:1|max:6',
            'semester' => 'nullable|in:first,second'
        ]);

        $category->update($validated);

        return new CategoryResource($category);
    }

    /**
     * Delete a category
     */
    public function destroy(Category $category)
    {
        // Prevent deletion if category has courses
        if ($category->courses()->count() > 0) {
            return response()->json(['error' => 'Cannot delete category with existing courses'], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
