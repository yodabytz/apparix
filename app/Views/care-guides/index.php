<!-- Care & Guides Index Page -->
<section class="care-guides-page">
    <div class="container">
        <div class="care-guides-header">
            <h1>Care & Guides</h1>
            <p class="care-guides-intro">
                Your trusted resource for caring for quality woolens, tweeds, and knitwear.
                Our expert guides help you protect your investment and keep your favorite pieces looking beautiful for years to come.
            </p>
        </div>

        <?php if (!empty($categories)): ?>
        <div class="care-guides-filters">
            <button class="filter-btn active" data-category="all">All Guides</button>
            <?php foreach ($categories as $category): ?>
            <button class="filter-btn" data-category="<?php echo escape(strtolower(str_replace(' ', '-', $category))); ?>">
                <?php echo escape($category); ?>
            </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($guides)): ?>
        <div class="care-guides-empty">
            <p>Care guides coming soon. Check back for expert tips on caring for your woolens and knitwear.</p>
        </div>
        <?php else: ?>
        <div class="care-guides-grid">
            <?php foreach ($guides as $guide): ?>
            <article class="care-guide-card" data-categories="<?php echo escape(implode(' ', array_map(fn($c) => strtolower(str_replace(' ', '-', $c)), $guide['categories'] ?? []))); ?>">
                <a href="/care-guides/<?php echo escape($guide['slug']); ?>" class="care-guide-link">
                    <div class="care-guide-content">
                        <?php if (!empty($guide['categories'])): ?>
                        <div class="care-guide-categories">
                            <?php foreach (array_slice($guide['categories'], 0, 2) as $cat): ?>
                            <span class="care-guide-category"><?php echo escape($cat); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <h2 class="care-guide-title"><?php echo escape($guide['title']); ?></h2>
                        <p class="care-guide-excerpt"><?php echo escape($guide['description']); ?></p>
                        <div class="care-guide-meta">
                            <span class="read-time"><?php echo $guide['readTime']; ?> min read</span>
                        </div>
                    </div>
                </a>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.care-guides-page {
    padding: 2rem 0 4rem;
    background: linear-gradient(180deg, #fdf2f8 0%, #fff 100%);
    min-height: 60vh;
}

.care-guides-header {
    text-align: center;
    margin-bottom: 2.5rem;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
}

.care-guides-header h1 {
    font-size: 2.25rem;
    color: #1f2937;
    margin-bottom: 1rem;
    font-weight: 600;
}

.care-guides-intro {
    font-size: 1.1rem;
    color: #6b7280;
    line-height: 1.7;
}

.care-guides-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: center;
    margin-bottom: 2rem;
}

.filter-btn {
    padding: 0.5rem 1rem;
    border: 1px solid #e5e7eb;
    background: #fff;
    border-radius: 2rem;
    cursor: pointer;
    font-size: 0.875rem;
    color: #4b5563;
    transition: all 0.2s ease;
}

.filter-btn:hover {
    border-color: #FF68C5;
    color: #FF68C5;
}

.filter-btn.active {
    background: #FF68C5;
    border-color: #FF68C5;
    color: #fff;
}

.care-guides-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}

.care-guide-card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.care-guide-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(255, 104, 197, 0.15);
}

.care-guide-card.hidden {
    display: none;
}

.care-guide-link {
    display: block;
    text-decoration: none;
    color: inherit;
}

.care-guide-content {
    padding: 1.5rem;
}

.care-guide-categories {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.care-guide-category {
    font-size: 0.75rem;
    padding: 0.25rem 0.75rem;
    background: #fdf2f8;
    color: #FF68C5;
    border-radius: 1rem;
    font-weight: 500;
}

.care-guide-title {
    font-size: 1.25rem;
    color: #1f2937;
    margin-bottom: 0.75rem;
    font-weight: 600;
    line-height: 1.4;
}

.care-guide-excerpt {
    font-size: 0.95rem;
    color: #6b7280;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.care-guide-meta {
    display: flex;
    align-items: center;
    font-size: 0.85rem;
    color: #9ca3af;
}

.read-time::before {
    content: '';
    display: inline-block;
    width: 4px;
    height: 4px;
    background: #d1d5db;
    border-radius: 50%;
    margin-right: 0.5rem;
    vertical-align: middle;
}

.care-guides-empty {
    text-align: center;
    padding: 3rem;
    color: #6b7280;
}

@media (max-width: 768px) {
    .care-guides-header h1 {
        font-size: 1.75rem;
    }

    .care-guides-grid {
        grid-template-columns: 1fr;
    }

    .care-guides-filters {
        padding: 0 1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const cards = document.querySelectorAll('.care-guide-card');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const category = this.dataset.category;

            // Update active state
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // Filter cards
            cards.forEach(card => {
                if (category === 'all') {
                    card.classList.remove('hidden');
                } else {
                    const cardCategories = card.dataset.categories || '';
                    if (cardCategories.includes(category)) {
                        card.classList.remove('hidden');
                    } else {
                        card.classList.add('hidden');
                    }
                }
            });
        });
    });
});
</script>
