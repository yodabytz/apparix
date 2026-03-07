<div class="container" style="max-width: 900px; margin: 0 auto; padding: 2rem 1rem;">
    <!-- Breadcrumb -->
    <nav class="page-breadcrumb" aria-label="Breadcrumb">
        <a href="/">Home</a>
        <span class="separator">/</span>
        <span class="current"><?php echo escape($page['title']); ?></span>
    </nav>

    <?php if (!empty($page['keywords'])): ?>
    <div class="page-keywords">
        <?php foreach (array_map('trim', explode(',', $page['keywords'])) as $keyword): ?>
            <?php if ($keyword): ?>
            <span class="keyword-tag"><?php echo escape($keyword); ?></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($page['show_title'])): ?>
    <h1 style="margin-bottom: 1.5rem;"><?php echo escape($page['title']); ?></h1>
    <?php endif; ?>
    <div class="page-content">
        <?php echo $page['content']; ?>
    </div>
</div>

<style>
.page-breadcrumb {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 1.5rem;
}
.page-breadcrumb a {
    color: #6b7280;
    text-decoration: none;
}
.page-breadcrumb a:hover {
    color: #6366f1;
}
.page-breadcrumb .separator {
    margin: 0 0.5rem;
    color: #d1d5db;
}
.page-breadcrumb .current {
    color: #374151;
}
.page-keywords {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1.25rem;
}
.keyword-tag {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: #f3f4f6;
    color: #4b5563;
    border-radius: 9999px;
    font-size: 0.8rem;
    font-weight: 500;
}
.page-content {
    line-height: 1.8;
    font-size: 1.05rem;
    color: #374151;
}
.page-content h2 {
    margin-top: 2rem;
    margin-bottom: 0.75rem;
}
.page-content h3 {
    margin-top: 1.5rem;
    margin-bottom: 0.5rem;
}
.page-content p {
    margin-bottom: 1rem;
}
.page-content ul, .page-content ol {
    margin-bottom: 1rem;
    padding-left: 1.5rem;
}
.page-content li {
    margin-bottom: 0.5rem;
}
.page-content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 1rem 0;
}
.page-content table {
    width: 100%;
    border-collapse: collapse;
    margin: 1rem 0;
}
.page-content table th,
.page-content table td {
    padding: 0.75rem;
    border: 1px solid #e5e7eb;
    text-align: left;
}
.page-content table th {
    background: #f9fafb;
    font-weight: 600;
}
.page-content blockquote {
    border-left: 4px solid #6366f1;
    margin: 1rem 0;
    padding: 0.75rem 1.5rem;
    background: #f9fafb;
    border-radius: 0 8px 8px 0;
}
.page-content a {
    color: #6366f1;
    text-decoration: underline;
}
</style>
