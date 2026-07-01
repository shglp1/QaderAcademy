<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * List all site settings
     */
    public function index()
    {
        $settings = Setting::all()->keyBy('key');
        
        return response()->json(['settings' => $settings]);
    }

    /**
     * Update a setting by key
     */
    public function update(Request $request, $key)
    {
        $request->validate([
            'value' => 'required|string',
            'type' => 'nullable|in:string,boolean,json'
        ]);

        $setting = Setting::firstOrCreate(['key' => $key]);
        
        $setting->update([
            'value' => $request->value,
            'type' => $request->type ?? 'string'
        ]);

        return response()->json(['message' => 'Setting updated successfully', 'setting' => $setting]);
    }
}
