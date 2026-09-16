<?php

declare(strict_types=1);

final class Seo
{
    public static function tags(
        string $title,
        string $description,
        string $path,
        ?string $image = null,
        string $type = 'website'
    ): string {
        $baseUrl = rtrim((string) (getenv('APP_URL') ?: ''), '/');
        if ($baseUrl === '') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $baseUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        }

        $url = $baseUrl . '/' . ltrim($path, '/');
        $imageUrl = $image ? $baseUrl . '/' . ltrim($image, '/') : null;
        $titleEscaped = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $descriptionEscaped = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
        $urlEscaped = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $tags = [
            '<meta name="description" content="' . $descriptionEscaped . '">',
            '<link rel="canonical" href="' . $urlEscaped . '">',
            '<meta property="og:title" content="' . $titleEscaped . '">',
            '<meta property="og:description" content="' . $descriptionEscaped . '">',
            '<meta property="og:type" content="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '">',
            '<meta property="og:url" content="' . $urlEscaped . '">',
            '<meta name="twitter:card" content="summary_large_image">',
            '<meta name="twitter:title" content="' . $titleEscaped . '">',
            '<meta name="twitter:description" content="' . $descriptionEscaped . '">',
        ];

        if ($imageUrl) {
            $imageEscaped = htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8');
            $tags[] = '<meta property="og:image" content="' . $imageEscaped . '">';
            $tags[] = '<meta name="twitter:image" content="' . $imageEscaped . '">';
        }

        return implode("\n    ", $tags);
    }
}
