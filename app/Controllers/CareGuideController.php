<?php

namespace App\Controllers;

use App\Core\Controller;

class CareGuideController extends Controller
{
    private string $contentPath;
    private string $baseUrl;

    public function __construct()
    {
        parent::__construct();
        $this->contentPath = __DIR__ . '/../Content/CareGuides/';
        $this->baseUrl = appUrl();
    }

    /**
     * Display care guides index page
     */
    public function index(): void
    {
        $guides = $this->getAllGuides();

        // Sort by date (newest first)
        usort($guides, function($a, $b) {
            return strtotime($b['date'] ?? '2024-01-01') - strtotime($a['date'] ?? '2024-01-01');
        });

        // Get unique categories for filtering
        $categories = [];
        foreach ($guides as $guide) {
            foreach ($guide['categories'] ?? [] as $cat) {
                if (!in_array($cat, $categories)) {
                    $categories[] = $cat;
                }
            }
        }
        sort($categories);

        $this->render('care-guides/index', [
            'title' => 'Care & Guides',
            'metaDescription' => 'Expert care guides for wool, tweed, and knitwear. Learn how to wash merino wool, care for Aran sweaters, store knitwear, and more from Lily\'s Pad Studio.',
            'metaKeywords' => 'wool care, merino wool washing, Aran sweater care, Harris Tweed care, knitwear storage, moth prevention, natural fiber care',
            'guides' => $guides,
            'categories' => $categories,
            'jsonLd' => $this->buildIndexJsonLd($guides)
        ]);
    }

    /**
     * Display individual care guide article
     */
    public function show(): void
    {
        $slug = $_GET['slug'] ?? '';

        if (!$slug) {
            http_response_code(404);
            $this->render('errors/404', ['title' => 'Page Not Found']);
            return;
        }

        $guide = $this->getGuideBySlug($slug);

        if (!$guide) {
            http_response_code(404);
            $this->render('errors/404', ['title' => 'Page Not Found']);
            return;
        }

        $this->render('care-guides/show', [
            'title' => $guide['title'],
            'metaDescription' => $guide['description'],
            'metaKeywords' => implode(', ', $guide['keywords'] ?? []),
            'guide' => $guide,
            'relatedGuides' => $this->getRelatedGuides($guide, 3),
            'jsonLd' => $this->buildArticleJsonLd($guide)
        ]);
    }

    /**
     * Get all care guides
     */
    private function getAllGuides(): array
    {
        $guides = [];

        if (!is_dir($this->contentPath)) {
            return $guides;
        }

        foreach (glob($this->contentPath . '*.php') as $file) {
            $guide = include $file;
            if (is_array($guide) && !empty($guide['slug'])) {
                $guide['readTime'] = $this->calculateReadTime($guide['content'] ?? '');
                $guides[] = $guide;
            }
        }

        return $guides;
    }

    /**
     * Get a single guide by slug
     */
    private function getGuideBySlug(string $slug): ?array
    {
        $filename = $this->contentPath . $slug . '.php';

        if (!file_exists($filename)) {
            return null;
        }

        $guide = include $filename;
        if (!is_array($guide)) {
            return null;
        }

        $guide['readTime'] = $this->calculateReadTime($guide['content'] ?? '');
        return $guide;
    }

    /**
     * Get related guides based on shared categories
     */
    private function getRelatedGuides(array $currentGuide, int $limit = 3): array
    {
        $all = $this->getAllGuides();
        $related = [];

        foreach ($all as $guide) {
            if ($guide['slug'] === $currentGuide['slug']) {
                continue;
            }

            // Calculate relevance score based on shared categories
            $sharedCategories = array_intersect(
                $currentGuide['categories'] ?? [],
                $guide['categories'] ?? []
            );

            if (count($sharedCategories) > 0) {
                $guide['relevanceScore'] = count($sharedCategories);
                $related[] = $guide;
            }
        }

        // Sort by relevance
        usort($related, fn($a, $b) => $b['relevanceScore'] - $a['relevanceScore']);

        return array_slice($related, 0, $limit);
    }

    /**
     * Calculate estimated read time
     */
    private function calculateReadTime(string $content): int
    {
        $wordCount = str_word_count(strip_tags($content));
        $readTime = ceil($wordCount / 200); // Average reading speed
        return max(1, $readTime);
    }

    /**
     * Build JSON-LD for index page
     */
    private function buildIndexJsonLd(array $guides): string
    {
        $items = [];
        $position = 1;

        foreach ($guides as $guide) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'item' => [
                    '@type' => 'Article',
                    'name' => $guide['title'],
                    'url' => $this->baseUrl . '/care-guides/' . $guide['slug']
                ]
            ];
        }

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'CollectionPage',
                    'name' => 'Care & Guides',
                    'description' => 'Expert care guides for wool, tweed, and knitwear from Lily\'s Pad Studio.',
                    'url' => $this->baseUrl . '/care-guides',
                    'mainEntity' => [
                        '@type' => 'ItemList',
                        'itemListElement' => $items
                    ]
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $this->baseUrl],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Care & Guides']
                    ]
                ]
            ]
        ];

        return json_encode($jsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Build JSON-LD for article page
     */
    private function buildArticleJsonLd(array $guide): string
    {
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Article',
                    'headline' => $guide['title'],
                    'description' => $guide['description'],
                    'datePublished' => $guide['date'] ?? date('Y-m-d'),
                    'dateModified' => $guide['modified'] ?? $guide['date'] ?? date('Y-m-d'),
                    'author' => [
                        '@type' => 'Organization',
                        'name' => 'Lily\'s Pad Studio',
                        'url' => $this->baseUrl
                    ],
                    'publisher' => [
                        '@type' => 'Organization',
                        'name' => 'Lily\'s Pad Studio',
                        'logo' => [
                            '@type' => 'ImageObject',
                            'url' => $this->baseUrl . '/assets/images/lilys-pad-studio-logo.png'
                        ]
                    ],
                    'mainEntityOfPage' => [
                        '@type' => 'WebPage',
                        '@id' => $this->baseUrl . '/care-guides/' . $guide['slug']
                    ],
                    'articleSection' => implode(', ', $guide['categories'] ?? [])
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $this->baseUrl],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Care & Guides', 'item' => $this->baseUrl . '/care-guides'],
                        ['@type' => 'ListItem', 'position' => 3, 'name' => $guide['title']]
                    ]
                ]
            ]
        ];

        // Add HowTo schema for instructional guides
        if ($this->isHowToGuide($guide)) {
            $steps = $this->extractHowToSteps($guide['content'] ?? '');
            if (!empty($steps)) {
                $howTo = [
                    '@type' => 'HowTo',
                    'name' => $guide['title'],
                    'description' => $guide['description'],
                    'step' => $steps
                ];
                if (!empty($guide['readTime'])) {
                    $howTo['totalTime'] = 'PT' . ($guide['readTime'] * 5) . 'M';
                }
                $jsonLd['@graph'][] = $howTo;
            }
        }

        // Add FAQ if present
        if (!empty($guide['faq'])) {
            $faqItems = [];
            foreach ($guide['faq'] as $item) {
                $faqItems[] = [
                    '@type' => 'Question',
                    'name' => $item['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $item['answer']
                    ]
                ];
            }
            $jsonLd['@graph'][] = [
                '@type' => 'FAQPage',
                'mainEntity' => $faqItems
            ];
        }

        return json_encode($jsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Check if a guide is instructional (HowTo)
     */
    private function isHowToGuide(array $guide): bool
    {
        $title = strtolower($guide['title'] ?? '');
        $slug = strtolower($guide['slug'] ?? '');
        return str_contains($title, 'how to') || str_contains($slug, 'how-to')
            || str_contains($title, 'guide to') || str_contains($title, 'caring for')
            || str_contains($title, 'step') || str_contains($title, 'storing');
    }

    /**
     * Extract HowTo steps from guide HTML content
     */
    private function extractHowToSteps(string $html): array
    {
        $steps = [];
        $position = 1;

        // Extract h2/h3 sections as steps
        if (preg_match_all('/<h[23][^>]*>(.*?)<\/h[23]>/i', $html, $matches)) {
            foreach ($matches[1] as $heading) {
                $name = strip_tags($heading);
                if (empty($name)) continue;

                // Get the text after this heading until the next heading
                $pattern = '/' . preg_quote($matches[0][array_search($heading, $matches[1])], '/') . '\s*(.*?)(?=<h[23]|$)/is';
                $text = '';
                if (preg_match($pattern, $html, $textMatch)) {
                    $text = trim(strip_tags($textMatch[1]));
                    if (strlen($text) > 300) {
                        $text = substr($text, 0, 297) . '...';
                    }
                }

                $steps[] = [
                    '@type' => 'HowToStep',
                    'position' => $position++,
                    'name' => $name,
                    'text' => $text ?: $name
                ];
            }
        }

        return $steps;
    }

    /**
     * Get all guides for sitemap
     */
    public static function getGuidesForSitemap(): array
    {
        $contentPath = __DIR__ . '/../Content/CareGuides/';
        $guides = [];

        if (!is_dir($contentPath)) {
            return $guides;
        }

        foreach (glob($contentPath . '*.php') as $file) {
            $guide = include $file;
            if (is_array($guide) && !empty($guide['slug'])) {
                $guides[] = [
                    'slug' => $guide['slug'],
                    'date' => $guide['date'] ?? date('Y-m-d'),
                    'modified' => $guide['modified'] ?? $guide['date'] ?? date('Y-m-d')
                ];
            }
        }

        return $guides;
    }
}
