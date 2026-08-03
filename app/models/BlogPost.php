<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class BlogPost extends Model
{
    public int $id;
    public int $user_id;
    public string $title;
    public string $slug;
    public string $excerpt;
    public string $content;
    public ?string $featured_image = null;
    public ?string $media = null;
    public string $status = 'draft';
    public string $created_at;
    public string $updated_at;

    public function __construct()
    {
        $this->table = 'blog_posts';
        parent::__construct();
    }

    public function getMedia(): array
    {
        return $this->media ? (json_decode($this->media, true) ?? []) : [];
    }

    public function setMedia(array $media): void
    {
        $this->media = json_encode($media);
    }

    public static function generateSlug(string $title): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        $existing = static::findOneBy(['slug' => $slug]);
        return $existing ? $slug . '-' . time() : $slug;
    }
}
