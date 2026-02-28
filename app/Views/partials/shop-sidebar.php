<!-- Shop Sidebar - Categories -->
<?php
$sidebarMenuMode = 'hover';
if (class_exists('\App\Core\ThemeService')) {
    $ts = new \App\Core\ThemeService();
    $sidebarMenuMode = $ts->getSidebarMenuMode();
}
$menuModeClass = 'menu-mode-' . $sidebarMenuMode;
?>
<aside class="shop-sidebar">
    <div class="sidebar-section">
        <h3>Categories</h3>
        <ul class="category-list category-accordion <?php echo $menuModeClass; ?>">
            <li>
                <a href="/products" class="<?php echo !isset($currentCategory) ? 'active' : ''; ?>">
                    All Products
                </a>
            </li>
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $cat): ?>
                    <?php
                    $hasChildren = !empty($cat['children']);
                    $isActive = isset($currentCategory) && $currentCategory === $cat['slug'];
                    $isChildActive = false;
                    if ($hasChildren && isset($currentCategory)) {
                        foreach ($cat['children'] as $child) {
                            if ($currentCategory === $child['slug']) {
                                $isChildActive = true;
                                break;
                            }
                            if (!empty($child['children'])) {
                                foreach ($child['children'] as $grandchild) {
                                    if ($currentCategory === $grandchild['slug']) {
                                        $isChildActive = true;
                                        break 2;
                                    }
                                }
                            }
                        }
                    }
                    $isExpanded = $isActive || $isChildActive;
                    if ($sidebarMenuMode === 'expanded' && $hasChildren) {
                        $isExpanded = true;
                    }
                    ?>
                    <li class="parent-category <?php echo $hasChildren ? 'has-children' : ''; ?> <?php echo $isExpanded ? 'expanded' : ''; ?>">
                        <div class="category-header">
                            <a href="/category/<?php echo escape($cat['slug']); ?>"
                               class="<?php echo $isActive ? 'active' : ''; ?>">
                                <?php echo escape($cat['name']); ?>
                                <span class="count"><?php echo (int)$cat['product_count']; ?></span>
                            </a>
                            <?php if ($hasChildren && $sidebarMenuMode !== 'expanded'): ?>
                            <span class="category-indicator"></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($hasChildren): ?>
                            <ul class="subcategory-list">
                                <?php foreach ($cat['children'] as $child): ?>
                                    <?php
                                    $childHasChildren = !empty($child['children']);
                                    $childIsActive = isset($currentCategory) && $currentCategory === $child['slug'];
                                    $grandchildActive = false;
                                    if ($childHasChildren && isset($currentCategory)) {
                                        foreach ($child['children'] as $grandchild) {
                                            if ($currentCategory === $grandchild['slug']) {
                                                $grandchildActive = true;
                                                break;
                                            }
                                        }
                                    }
                                    $childExpanded = $childIsActive || $grandchildActive;
                                    if ($sidebarMenuMode === 'expanded' && $childHasChildren) {
                                        $childExpanded = true;
                                    }
                                    ?>
                                    <li class="<?php echo $childHasChildren ? 'has-children' : ''; ?> <?php echo $childExpanded ? 'expanded' : ''; ?>">
                                        <div class="category-header">
                                            <a href="/category/<?php echo escape($child['slug']); ?>"
                                               class="<?php echo $childIsActive ? 'active' : ''; ?>">
                                                <?php echo escape($child['name']); ?>
                                                <span class="count"><?php echo (int)$child['product_count']; ?></span>
                                            </a>
                                            <?php if ($childHasChildren && $sidebarMenuMode !== 'expanded'): ?>
                                            <span class="category-indicator"></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($childHasChildren): ?>
                                            <ul class="grandchild-list">
                                                <?php foreach ($child['children'] as $grandchild): ?>
                                                    <li>
                                                        <a href="/category/<?php echo escape($grandchild['slug']); ?>"
                                                           class="<?php echo (isset($currentCategory) && $currentCategory === $grandchild['slug']) ? 'active' : ''; ?>">
                                                            <?php echo escape($grandchild['name']); ?>
                                                            <span class="count"><?php echo (int)$grandchild['product_count']; ?></span>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</aside>

<style>
/* Category Accordion Base Styles */
.category-accordion.category-list,
.category-accordion.category-list ul {
    margin: 0 !important;
    padding: 0 !important;
    list-style: none !important;
}
.category-accordion.category-list li {
    margin: 0 !important;
    padding: 0 !important;
    line-height: 1.3 !important;
}
.category-accordion.category-list a {
    padding: 6px 8px !important;
    line-height: 1.3 !important;
    font-size: 0.9rem !important;
}
.category-accordion .category-header {
    display: flex;
    align-items: center;
    margin: 0 !important;
    padding: 0 !important;
}
.category-accordion .category-header a {
    flex: 1;
}

/* Stylish +/- indicator (hidden in expanded mode) */
.category-accordion .category-indicator {
    position: relative;
    width: 16px;
    height: 16px;
    margin-right: 8px;
    flex-shrink: 0;
    cursor: pointer;
}
.category-accordion .category-indicator::before,
.category-accordion .category-indicator::after {
    content: '';
    position: absolute;
    background: var(--primary-pink, #FF68C5);
    border-radius: 2px;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.category-accordion .category-indicator::before {
    width: 10px;
    height: 2px;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}
.category-accordion .category-indicator::after {
    width: 2px;
    height: 10px;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}
.category-accordion .has-children.expanded > .category-header .category-indicator::after {
    transform: translate(-50%, -50%) rotate(90deg);
    opacity: 0;
}
.category-accordion .has-children:hover > .category-header .category-indicator::before,
.category-accordion .has-children:hover > .category-header .category-indicator::after {
    background: var(--secondary-pink, #FF94C8);
}

/* Subcategory lists — click & hover modes: animated expand/collapse */
.category-accordion:not(.menu-mode-expanded) .has-children > .subcategory-list,
.category-accordion:not(.menu-mode-expanded) .has-children > .grandchild-list {
    max-height: 0;
    overflow: hidden;
    padding-left: 10px !important;
    margin: 0 !important;
    border-left: 2px solid var(--light-pink, #FFE4F3);
    margin-left: 8px !important;
    transition: max-height 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}
.category-accordion:not(.menu-mode-expanded) .has-children.expanded > .subcategory-list,
.category-accordion:not(.menu-mode-expanded) .has-children.expanded > .grandchild-list,
.category-accordion:not(.menu-mode-expanded) .subcategory-list .has-children.expanded > .grandchild-list {
    max-height: 1000px;
}

/* Expanded mode: all subcategories always visible */
.category-accordion.menu-mode-expanded .subcategory-list,
.category-accordion.menu-mode-expanded .grandchild-list {
    padding-left: 10px !important;
    margin: 0 !important;
    border-left: 2px solid var(--light-pink, #FFE4F3);
    margin-left: 8px !important;
}

.category-accordion .subcategory-list a {
    padding: 5px 6px !important;
    font-size: 0.85rem !important;
}
.category-accordion .grandchild-list a {
    padding: 4px 6px !important;
    font-size: 0.8rem !important;
}
</style>

<script>
// Category sidebar behavior based on menu mode
(function() {
    var accordion = document.querySelector('.category-accordion');
    if (!accordion) return;

    var mode = accordion.classList.contains('menu-mode-hover') ? 'hover' :
               accordion.classList.contains('menu-mode-click') ? 'click' : 'expanded';

    if (mode === 'expanded') return;

    if (mode === 'hover') {
        var closeDelay = 5000;
        var closeTimers = new WeakMap();

        accordion.querySelectorAll('.has-children').forEach(function(item) {
            item.addEventListener('mouseenter', function() {
                if (closeTimers.has(this)) {
                    clearTimeout(closeTimers.get(this));
                    closeTimers.delete(this);
                }
                this.classList.add('expanded');
            });

            item.addEventListener('mouseleave', function() {
                var self = this;
                var timer = setTimeout(function() {
                    self.classList.remove('expanded');
                    closeTimers.delete(self);
                }, closeDelay);
                closeTimers.set(this, timer);
            });

            var indicator = item.querySelector(':scope > .category-header .category-indicator');
            if (indicator) {
                indicator.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (closeTimers.has(item)) {
                        clearTimeout(closeTimers.get(item));
                        closeTimers.delete(item);
                    }
                    item.classList.toggle('expanded');
                });
            }
        });
    }

    if (mode === 'click') {
        accordion.querySelectorAll('.has-children').forEach(function(item) {
            var indicator = item.querySelector(':scope > .category-header .category-indicator');
            if (indicator) {
                indicator.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    item.classList.toggle('expanded');
                });
            }

            var header = item.querySelector(':scope > .category-header');
            if (header) {
                header.addEventListener('click', function(e) {
                    if (e.target.tagName !== 'A' && !e.target.closest('a')) {
                        e.preventDefault();
                        item.classList.toggle('expanded');
                    }
                });
            }
        });
    }
})();
</script>
