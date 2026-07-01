<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * List all site settings grouped by category
     */
    public function index()
    {
        $settings = Setting::all()->groupBy('group');
        
        return response()->json(['settings' => $settings]);
    }

    /**
     * Get settings by group
     */
    public function getByGroup($group)
    {
        $settings = Setting::where('group', $group)->get()->keyBy('key');
        
        return response()->json(['settings' => $settings]);
    }

    /**
     * Update a setting by key
     */
    public function update(Request $request, $key)
    {
        $request->validate([
            'value' => 'required',
            'type' => 'nullable|in:string,boolean,json,number',
            'group' => 'nullable|string|max:100',
            'is_public' => 'nullable|boolean'
        ]);

        $setting = Setting::firstOrCreate(['key' => $key]);
        
        $setting->update([
            'value' => $request->value,
            'type' => $request->type ?? 'string',
            'group' => $request->group ?? $setting->group,
            'is_public' => $request->has('is_public') ? $request->is_public : $setting->is_public
        ]);

        return response()->json(['message' => 'Setting updated successfully', 'setting' => $setting]);
    }

    /**
     * Store a new setting
     */
    public function store(Request $request)
    {
        $request->validate([
            'key' => 'required|string|unique:site_settings,key',
            'value' => 'required',
            'type' => 'nullable|in:string,boolean,json,number',
            'group' => 'nullable|string|max:100',
            'is_public' => 'nullable|boolean'
        ]);

        $setting = Setting::create([
            'key' => $request->key,
            'value' => $request->value,
            'type' => $request->type ?? 'string',
            'group' => $request->group,
            'is_public' => $request->is_public ?? false
        ]);

        return response()->json(['message' => 'Setting created successfully', 'setting' => $setting], 201);
    }

    /**
     * Delete a setting
     */
    public function destroy($key)
    {
        $setting = Setting::where('key', $key)->first();
        
        if (!$setting) {
            return response()->json(['error' => 'Setting not found'], 404);
        }

        $setting->delete();

        return response()->json(['message' => 'Setting deleted successfully']);
    }
}
