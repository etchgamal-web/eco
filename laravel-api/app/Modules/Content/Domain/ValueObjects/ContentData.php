<?php

namespace App\Modules\Content\Domain\ValueObjects;

final readonly class ContentData
{
    public function __construct(
        public string $type,
        public string $title,
        public string $slug,
        public ?string $excerpt = null,
        public ?string $body = null,
        public string $status = 'draft',
        public mixed $publishedAt = null,
        public ?string $seoTitle = null,
        public ?string $seoDescription = null,
        public ?string $canonicalUrl = null,
        public ?string $featuredImage = null,
        public ?int $authorId = null,
        public ?int $parentId = null,
        public array $productIds = [],
        public array $categoryIds = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type: (string) $data['type'],
            title: (string) $data['title'],
            slug: (string) $data['slug'],
            excerpt: $data['excerpt'] ?? null,
            body: $data['body'] ?? null,
            status: (string) ($data['status'] ?? 'draft'),
            publishedAt: $data['published_at'] ?? null,
            seoTitle: $data['seo_title'] ?? null,
            seoDescription: $data['seo_description'] ?? null,
            canonicalUrl: $data['canonical_url'] ?? null,
            featuredImage: $data['featured_image'] ?? null,
            authorId: isset($data['author_id']) ? (int) $data['author_id'] : null,
            parentId: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            productIds: array_map('intval', $data['product_ids'] ?? []),
            categoryIds: array_map('intval', $data['category_ids'] ?? []),
        );
    }

    public function persistenceData(): array
    {
        return [
            'type' => $this->type, 'title' => $this->title, 'slug' => $this->slug,
            'excerpt' => $this->excerpt, 'body' => $this->body, 'status' => $this->status,
            'published_at' => $this->publishedAt, 'seo_title' => $this->seoTitle,
            'seo_description' => $this->seoDescription, 'canonical_url' => $this->canonicalUrl,
            'featured_image' => $this->featuredImage, 'author_id' => $this->authorId,
            'parent_id' => $this->parentId,
        ];
    }
}
