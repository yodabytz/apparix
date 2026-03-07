<div class="page-header">
    <h1>Create Page</h1>
    <a href="/admin/pages" class="btn btn-outline">Back to Pages</a>
</div>

<?php if ($flash = getFlash('error')): ?>
    <div class="alert alert-error"><?php echo escape($flash); ?></div>
<?php endif; ?>

<form action="/admin/pages/store" method="POST">
    <?php echo csrfField(); ?>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
        <!-- Main Content -->
        <div>
            <div class="admin-card">
                <h3 class="card-title">Page Details</h3>

                <div class="form-group">
                    <label class="form-label" for="title">Title *</label>
                    <input type="text" id="title" name="title" class="form-input" required placeholder="Page Title">
                </div>

                <div class="form-group">
                    <label class="form-label" for="slug">URL Slug</label>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="color: #6b7280; white-space: nowrap;">/pages/</span>
                        <input type="text" id="slug" name="slug" class="form-input" placeholder="auto-generated-from-title">
                    </div>
                    <small style="color: #9ca3af;">Leave blank to auto-generate from title</small>
                </div>
            </div>

            <div class="admin-card editor-card">
                <label style="display: block; margin-bottom: 0.75rem; font-weight: 500;">Content *</label>
                <?php include __DIR__ . '/../partials/mintaro-editor.php'; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div>
            <div class="admin-card">
                <h3 class="card-title">Publishing</h3>

                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <span>Active (published)</span>
                    </label>
                </div>

                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="show_title" value="1" checked>
                        <span>Show title on page</span>
                    </label>
                </div>

                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="show_in_footer" value="1">
                        <span>Show in footer</span>
                    </label>
                </div>

                <div class="form-group" id="footer-label-group" style="display: none;">
                    <label class="form-label" for="footer_label">Footer Label</label>
                    <input type="text" id="footer_label" name="footer_label" class="form-input" placeholder="Short label for footer link">
                    <small style="color: #9ca3af;">Leave blank to use page title</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="sort_order">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" class="form-input" value="0" min="0">
                </div>
            </div>

            <div class="admin-card">
                <h3 class="card-title">SEO</h3>

                <div class="form-group">
                    <label class="form-label" for="meta_title">Meta Title</label>
                    <input type="text" id="meta_title" name="meta_title" class="form-input" placeholder="Custom title for search engines">
                    <small style="color: #9ca3af;">Defaults to page title if blank</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="meta_description">Meta Description</label>
                    <textarea id="meta_description" name="meta_description" class="form-textarea" rows="3" placeholder="Description for search engines"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="keywords">Keywords</label>
                    <input type="text" id="keywords" name="keywords" class="form-input" placeholder="e.g. SEO, website, analytics">
                    <small style="color: #9ca3af;">Comma-separated tags shown at top of page</small>
                </div>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <a href="/admin/pages" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary btn-large">Create Page</button>
    </div>
</form>

<script>
let editor;

document.addEventListener('DOMContentLoaded', function() {
    editor = new Mintaro({
        containerId: 'mintaro-editor',
        toolbarId: 'mintaro-toolbar',
        placeholder: 'Write your page content here...',
        height: '400px',
        enablePreview: false
    });

    // Toggle footer label field
    const footerCheckbox = document.querySelector('input[name="show_in_footer"]');
    const footerLabelGroup = document.getElementById('footer-label-group');
    footerCheckbox.addEventListener('change', function() {
        footerLabelGroup.style.display = this.checked ? '' : 'none';
    });

    // Auto-generate slug from title
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    let slugManuallyEdited = false;

    slugInput.addEventListener('input', function() {
        slugManuallyEdited = this.value.length > 0;
    });

    titleInput.addEventListener('keyup', function() {
        if (!slugManuallyEdited) {
            slugInput.value = this.value.toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-|-$/g, '');
        }
    });
});

document.querySelector('form').addEventListener('submit', function(e) {
    const content = editor.getHTML();
    document.getElementById('content').value = content;

    if (!content || content === '<p><br></p>' || content.trim() === '') {
        e.preventDefault();
        alert('Please enter page content');
    }
});
</script>

<style>
.editor-card { padding-bottom: 1rem; }
.mintaro-container { border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
.form-actions { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem; }
</style>
