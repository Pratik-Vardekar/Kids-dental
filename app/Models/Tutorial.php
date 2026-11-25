<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tutorial extends Model
{
    use HasFactory;

    protected $fillable = [
        'sub_category_id',
        'title',
        'video_url',
        'video_file',
        'video_id',
        'thumbnail',
        'description',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'sub_category_id' => 'integer',
        'sort_order' => 'integer',
        'status' => 'integer',
    ];

    /**
     * Subcategory relationship.
     */
    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id');
    }

    /**
     * Scope active tutorials.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Extract YouTube ID helper from various URL formats.
     */
    public static function extractYoutubeId(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        // Standard watch or embed: youtube.com/watch?v=XYZ or youtube.com/embed/XYZ or youtu.be/XYZ or shorts/XYZ
        $patterns = [
            '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Determine if this tutorial uses an uploaded/direct video file.
     */
    public function getIsDirectVideoAttribute(): bool
    {
        if (!empty($this->video_file)) {
            return true;
        }

        $url = (string) ($this->attributes['video_url'] ?? '');
        return (bool) preg_match('/\.(mp4|webm|ogg|mov|mkv)(\?.*)?$/i', $url)
            || str_contains($url, 'backend/assets/videos/');
    }

    /**
     * Get the resolved playable video source URL.
     */
    public function getVideoSourceUrlAttribute(): string
    {
        if (!empty($this->video_file)) {
            $filePath = 'backend/assets/videos/tutorials/' . $this->video_file;
            return asset($filePath);
        }

        $url = (string) ($this->attributes['video_url'] ?? '');
        if (!empty($url)) {
            if (str_starts_with($url, 'backend/')) {
                return asset($url);
            }
            return $url;
        }

        return '';
    }

    /**
     * Fallback for video_url attribute if empty but video_file exists.
     */
    public function getVideoUrlAttribute($value): ?string
    {
        if (!empty($value)) {
            if (str_starts_with($value, 'backend/')) {
                return asset($value);
            }
            return $value;
        }

        if (!empty($this->attributes['video_file'])) {
            return asset('backend/assets/videos/tutorials/' . $this->attributes['video_file']);
        }

        return null;
    }

    /**
     * Get Embed URL for iframes or direct video source.
     */
    public function getEmbedUrlAttribute(): string
    {
        if ($this->is_direct_video) {
            return $this->video_source_url;
        }

        $url = (string) ($this->attributes['video_url'] ?? '');
        $ytId = $this->video_id ?: self::extractYoutubeId($url);
        if ($ytId) {
            return "https://www.youtube.com/embed/{$ytId}";
        }

        // Vimeo: vimeo.com/123456789
        if (preg_match('/vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|video\/|)(\d+)/', $url, $matches)) {
            return 'https://player.vimeo.com/video/' . end($matches);
        }

        return $this->video_source_url;
    }

    /**
     * Get YouTube ID attribute.
     */
    public function getYoutubeIdAttribute(): ?string
    {
        return $this->attributes['video_id'] ?: self::extractYoutubeId($this->video_url);
    }

    /**
     * Get Thumbnail URL attribute.
     */
    public function getThumbnailUrlAttribute(): string
    {
        if (!empty($this->thumbnail)) {
            $imagePath = 'backend/assets/images/tutorials/' . $this->thumbnail;
            if (file_exists(public_path($imagePath))) {
                return asset($imagePath);
            }
            return asset($this->thumbnail);
        }

        $ytId = $this->youtube_id;
        if ($ytId) {
            return "https://img.youtube.com/vi/{$ytId}/hqdefault.jpg";
        }

        return asset('backend/assets/images/default-video.png');
    }
}
