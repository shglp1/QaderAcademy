<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $table = 'site_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'is_public',
    ];

    protected $casts = [
        'value' => 'array',
        'is_public' => 'boolean',
    ];

    /**
     * Get the setting value cast to the appropriate type.
     */
    public function getTypedValueAttribute()
    {
        return match ($this->type) {
            'boolean' => (bool) $this->value,
            'json' => $this->value,
            'number' => (float) $this->value,
            default => is_array($this->value) ? json_encode($this->value) : $this->value,
        };
    }

    /**
     * Helper method to get a setting by key.
     */
    public static function get($key, $default = null, $locale = 'en')
    {
        $setting = static::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        $value = $setting->value;
        
        // If value is array with locale keys, return the appropriate locale
        if (is_array($value) && isset($value[$locale])) {
            return $value[$locale];
        }
        
        return $value ?? $default;
    }

    /**
     * Helper method to set a setting by key.
     */
    public static function set($key, $value, $type = 'string', $group = null, $isPublic = false)
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'group' => $group,
                'is_public' => $isPublic,
            ]
        );
    }
}
