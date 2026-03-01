<!-- Care Guide Article Page -->
<article class="care-guide-article">
    <div class="container">
        <!-- Breadcrumb -->
        <nav class="care-guide-breadcrumb" aria-label="Breadcrumb">
            <a href="/">Home</a>
            <span class="separator">/</span>
            <a href="/care-guides">Care & Guides</a>
            <span class="separator">/</span>
            <span class="current"><?php echo escape($guide['title']); ?></span>
        </nav>

        <!-- Article Header -->
        <header class="care-guide-header">
            <?php if (!empty($guide['categories'])): ?>
            <div class="care-guide-categories">
                <?php foreach ($guide['categories'] as $cat): ?>
                <span class="care-guide-category"><?php echo escape($cat); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <h1><?php echo escape($guide['title']); ?></h1>
            <p class="care-guide-description"><?php echo escape($guide['description']); ?></p>
            <div class="care-guide-meta">
                <span class="read-time"><?php echo $guide['readTime']; ?> min read</span>
            </div>
        </header>

        <!-- Article Content -->
        <div class="care-guide-body">
            <?php echo $guide['content']; ?>
        </div>

        <!-- FAQ Section -->
        <?php if (!empty($guide['faq'])): ?>
        <section class="care-guide-faq">
            <h2>Frequently Asked Questions</h2>
            <div class="faq-list">
                <?php foreach ($guide['faq'] as $item): ?>
                <div class="faq-item">
                    <h3 class="faq-question"><?php echo escape($item['question']); ?></h3>
                    <p class="faq-answer"><?php echo escape($item['answer']); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Related Products Section -->
        <?php if (!empty($guide['relatedProducts'])): ?>
        <section class="care-guide-products">
            <h2>Related Products</h2>
            <p class="products-intro">Shop our collection of quality items mentioned in this guide:</p>
            <div class="products-links">
                <?php foreach ($guide['relatedProducts'] as $product): ?>
                <a href="<?php echo escape($product['url']); ?>" class="product-link">
                    <?php echo escape($product['name']); ?>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Related Guides -->
        <?php if (!empty($relatedGuides)): ?>
        <section class="care-guide-related">
            <h2>Related Guides</h2>
            <div class="related-grid">
                <?php foreach ($relatedGuides as $related): ?>
                <a href="/care-guides/<?php echo escape($related['slug']); ?>" class="related-card">
                    <h3><?php echo escape($related['title']); ?></h3>
                    <p><?php echo escape(substr($related['description'], 0, 100)); ?>...</p>
                    <span class="read-more">Read guide</span>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Back to Guides -->
        <div class="care-guide-back">
            <a href="/care-guides" class="back-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to all guides
            </a>
        </div>
    </div>
</article>

<style>
.care-guide-article {
    padding: 1.5rem 0 4rem;
    background: linear-gradient(180deg, #fdf2f8 0%, #fff 50px, #fff 100%);
}

.care-guide-breadcrumb {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 2rem;
}

.care-guide-breadcrumb a {
    color: #6b7280;
    text-decoration: none;
}

.care-guide-breadcrumb a:hover {
    color: #FF68C5;
}

.care-guide-breadcrumb .separator {
    margin: 0 0.5rem;
    color: #d1d5db;
}

.care-guide-breadcrumb .current {
    color: #374151;
}

.care-guide-header {
    max-width: 800px;
    margin-bottom: 2.5rem;
}

.care-guide-header .care-guide-categories {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.care-guide-header .care-guide-category {
    font-size: 0.75rem;
    padding: 0.25rem 0.75rem;
    background: #fdf2f8;
    color: #FF68C5;
    border-radius: 1rem;
    font-weight: 500;
}

.care-guide-header h1 {
    font-size: 2.25rem;
    color: #1f2937;
    margin-bottom: 1rem;
    font-weight: 600;
    line-height: 1.3;
}

.care-guide-description {
    font-size: 1.2rem;
    color: #4b5563;
    line-height: 1.7;
    margin-bottom: 1rem;
}

.care-guide-header .care-guide-meta {
    font-size: 0.9rem;
    color: #9ca3af;
}

/* Article Body */
.care-guide-body {
    max-width: 800px;
    font-size: 1.05rem;
    line-height: 1.8;
    color: #374151;
}

.care-guide-body h2 {
    font-size: 1.5rem;
    color: #1f2937;
    margin: 2.5rem 0 1rem;
    font-weight: 600;
}

.care-guide-body h3 {
    font-size: 1.25rem;
    color: #1f2937;
    margin: 2rem 0 0.75rem;
    font-weight: 600;
}

.care-guide-body p {
    margin-bottom: 1.25rem;
}

.care-guide-body ul,
.care-guide-body ol {
    margin: 1.25rem 0;
    padding-left: 1.5rem;
}

.care-guide-body li {
    margin-bottom: 0.5rem;
}

.care-guide-body strong {
    color: #1f2937;
    font-weight: 600;
}

/* Quick Tips / Checklist Box */
.care-guide-body .tips-box,
.care-guide-body .checklist-box {
    background: #fdf2f8;
    border-radius: 12px;
    padding: 1.5rem 2rem;
    margin: 2rem 0;
    border-left: 4px solid #FF68C5;
}

.care-guide-body .tips-box h3,
.care-guide-body .checklist-box h3 {
    color: #FF68C5;
    margin-top: 0;
    font-size: 1.1rem;
}

.care-guide-body .tips-box ul,
.care-guide-body .checklist-box ul {
    margin-bottom: 0;
}

/* Image Placeholder */
.care-guide-body .image-placeholder {
    background: #f3f4f6;
    border-radius: 8px;
    padding: 3rem 2rem;
    text-align: center;
    color: #9ca3af;
    margin: 2rem 0;
    font-style: italic;
}

/* FAQ Section */
.care-guide-faq {
    max-width: 800px;
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid #e5e7eb;
}

.care-guide-faq h2 {
    font-size: 1.5rem;
    color: #1f2937;
    margin-bottom: 1.5rem;
}

.faq-item {
    margin-bottom: 1.5rem;
}

.faq-question {
    font-size: 1.1rem;
    color: #1f2937;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.faq-answer {
    color: #4b5563;
    line-height: 1.7;
}

/* Related Products */
.care-guide-products {
    max-width: 800px;
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid #e5e7eb;
}

.care-guide-products h2 {
    font-size: 1.5rem;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.products-intro {
    color: #6b7280;
    margin-bottom: 1rem;
}

.products-links {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.product-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    color: #374151;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s ease;
}

.product-link:hover {
    border-color: #FF68C5;
    color: #FF68C5;
    background: #fdf2f8;
}

.product-link svg {
    opacity: 0.5;
}

.product-link:hover svg {
    opacity: 1;
}

/* Related Guides */
.care-guide-related {
    max-width: 800px;
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid #e5e7eb;
}

.care-guide-related h2 {
    font-size: 1.5rem;
    color: #1f2937;
    margin-bottom: 1.5rem;
}

.related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.related-card {
    display: block;
    padding: 1.25rem;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    text-decoration: none;
    transition: all 0.2s ease;
}

.related-card:hover {
    border-color: #FF68C5;
    box-shadow: 0 4px 12px rgba(255, 104, 197, 0.1);
}

.related-card h3 {
    font-size: 1rem;
    color: #1f2937;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.related-card p {
    font-size: 0.9rem;
    color: #6b7280;
    line-height: 1.5;
    margin-bottom: 0.5rem;
}

.related-card .read-more {
    font-size: 0.875rem;
    color: #FF68C5;
    font-weight: 500;
}

/* Back Link */
.care-guide-back {
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid #e5e7eb;
    max-width: 800px;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: #6b7280;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}

.back-link:hover {
    color: #FF68C5;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .care-guide-header h1 {
        font-size: 1.75rem;
    }

    .care-guide-description {
        font-size: 1.1rem;
    }

    .care-guide-body {
        font-size: 1rem;
    }

    .care-guide-body .tips-box,
    .care-guide-body .checklist-box {
        padding: 1.25rem 1.5rem;
    }

    .products-links {
        flex-direction: column;
    }

    .product-link {
        justify-content: space-between;
    }
}
</style>
