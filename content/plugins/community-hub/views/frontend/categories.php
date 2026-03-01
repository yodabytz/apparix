<?php
if (!function_exists('forum_time_ago')) {
    function forum_time_ago($datetime) {
        $time = strtotime($datetime);
        $diff = time() - $time;
        if ($diff < 60) return 'just now';
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        if ($diff < 604800) return floor($diff / 86400) . 'd ago';
        return date('M j', $time);
    }
}

// Group categories: parents with their children
$parentCats = [];
$childCats = [];
foreach ($categories as $cat) {
    if (!empty($cat['parent_id'])) {
        $childCats[$cat['parent_id']][] = $cat;
    } else {
        $parentCats[] = $cat;
    }
}
?>

<div class="forum-container">
    <div class="forum-header">
        <h1><?php echo escape($forumName); ?></h1>
        <p class="forum-subtitle">Join the conversation with our community</p>
    </div>

    <?php if (!empty($parentCats)): ?>
        <div class="category-grid">
            <?php foreach ($parentCats as $cat): ?>
                <?php $hasSubs = !empty($childCats[$cat['id']]); ?>
                <?php if ($hasSubs): ?>
                    <!-- Parent with subcategories -->
                    <div class="category-card category-parent">
                        <div class="category-parent-header">
                            <div class="category-icon"><?php echo $cat['icon'] ? escape($cat['icon']) : '&#128172;'; ?></div>
                            <div class="category-info">
                                <h3 class="category-name"><?php echo escape($cat['name']); ?></h3>
                                <?php if ($cat['description']): ?>
                                    <p class="category-desc"><?php echo escape($cat['description']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="subcategory-list">
                            <?php foreach ($childCats[$cat['id']] as $sub): ?>
                                <a href="/community/category/<?php echo escape($sub['slug']); ?>" class="subcategory-item">
                                    <span class="subcategory-icon"><?php echo $sub['icon'] ? escape($sub['icon']) : '&#128196;'; ?></span>
                                    <div class="subcategory-info">
                                        <span class="subcategory-name"><?php echo escape($sub['name']); ?></span>
                                        <?php if ($sub['description']): ?>
                                            <span class="subcategory-desc"><?php echo escape($sub['description']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="subcategory-stats">
                                        <span><?php echo (int)$sub['topic_count']; ?> topics</span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Standalone category (no subcategories) -->
                    <a href="/community/category/<?php echo escape($cat['slug']); ?>" class="category-card">
                        <div class="category-icon"><?php echo $cat['icon'] ? escape($cat['icon']) : '&#128172;'; ?></div>
                        <div class="category-info">
                            <h3 class="category-name">
                                <?php echo escape($cat['name']); ?>
                                <?php if (!empty($cat['admin_only'])): ?>
                                    <span class="topic-badge badge-admin-only" style="font-size:10px;">Admin Only</span>
                                <?php endif; ?>
                            </h3>
                            <?php if ($cat['description']): ?>
                                <p class="category-desc"><?php echo escape($cat['description']); ?></p>
                            <?php endif; ?>
                            <div class="category-stats">
                                <span><?php echo (int)$cat['topic_count']; ?> topics</span>
                                <span><?php echo (int)$cat['reply_count']; ?> replies</span>
                            </div>
                            <?php if ($cat['last_reply_at']): ?>
                                <div class="category-last-activity">Last activity <?php echo forum_time_ago($cat['last_reply_at']); ?></div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="forum-empty">
            <p>No forum categories available yet. Check back soon!</p>
        </div>
    <?php endif; ?>
</div>
