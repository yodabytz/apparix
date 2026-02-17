<div class="page-header">
    <h1>Categories <span style="color: var(--admin-text-light); font-weight: normal;">(<?php echo count($categories); ?>)</span></h1>
    <button type="button" class="btn btn-primary" onclick="showAddForm()">+ Add Category</button>
</div>

<!-- Add Category Form -->
<div id="addCategoryForm" class="card" style="display: none; margin-bottom: 1.5rem;">
    <h3 class="card-title" style="margin-bottom: 1rem;">Add New Category</h3>
    <form action="/admin/categories/store" method="POST" enctype="multipart/form-data">
        <?php echo csrfField(); ?>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Category Name *</label>
                <input type="text" name="name" class="form-input" required placeholder="e.g., Clothing">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Parent Category</label>
                <select name="parent_id" class="form-input">
                    <option value="">— None (Top Level) —</option>
                    <?php foreach ($parentCategories as $parent): ?>
                        <option value="<?php echo $parent['id']; ?>">
                            <?php echo !empty($parent['parent_name']) ? escape($parent['parent_name']) . ' → ' : ''; ?><?php echo escape($parent['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Slug (optional)</label>
                <input type="text" name="slug" class="form-input" placeholder="auto-generated if empty">
            </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 1rem; align-items: end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Category Image (optional)</label>
                <input type="file" name="image" class="form-input" accept="image/*">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="show_subcategory_grid" value="1" style="width: auto;">
                    Show subcategory grid (display subcategories with images)
                </label>
            </div>
            <div class="action-buttons">
                <button type="submit" class="btn btn-primary">Add Category</button>
                <button type="button" class="btn btn-outline" onclick="hideAddForm()">Cancel</button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <?php if (!empty($categories)): ?>
        <div class="table-container">
            <table class="admin-table" id="categoriesTable">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Parent</th>
                        <th>Grid</th>
                        <th>Products</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="categoryList">
                    <?php foreach ($categories as $category): ?>
                        <tr data-id="<?php echo $category['id']; ?>"
                            data-parent="<?php echo $category['parent_id'] ?? ''; ?>"
                            data-image="<?php echo escape($category['image'] ?? ''); ?>"
                            data-show-grid="<?php echo $category['show_subcategory_grid'] ?? 0; ?>"
                            class="<?php echo !empty($category['is_child']) ? 'subcategory-row' : ''; ?>">
                            <td>
                                <span class="drag-handle" style="cursor: grab; color: var(--admin-text-light);">&#9776;</span>
                            </td>
                            <td style="width: 60px;">
                                <?php if (!empty($category['image'])): ?>
                                    <img src="<?php echo escape($category['image']); ?>" alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                <?php else: ?>
                                    <span style="color: var(--admin-text-light); font-size: 0.8rem;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $indentLevel = $category['indent_level'] ?? 0;
                                if ($indentLevel > 0):
                                    $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $indentLevel - 1);
                                ?>
                                    <span style="color: var(--admin-text-light); margin-right: 0.5rem;"><?php echo $indent; ?>└─</span>
                                <?php endif; ?>
                                <span class="category-name"><?php echo escape($category['name']); ?></span>
                                <input type="text" class="form-input edit-name" value="<?php echo escape($category['name']); ?>" style="display: none; font-size: 0.9rem;">
                            </td>
                            <td>
                                <span class="category-slug" style="color: var(--admin-text-light);"><?php echo escape($category['slug']); ?></span>
                                <input type="text" class="form-input edit-slug" value="<?php echo escape($category['slug']); ?>" style="display: none; font-size: 0.9rem;">
                            </td>
                            <td>
                                <span class="category-parent-display">
                                    <?php if ($category['parent_name']): ?>
                                        <span class="badge badge-gray"><?php echo escape($category['parent_name']); ?></span>
                                    <?php else: ?>
                                        <span style="color: var(--admin-text-light);">—</span>
                                    <?php endif; ?>
                                </span>
                                <select class="form-input edit-parent" style="display: none; font-size: 0.9rem;">
                                    <option value="">— None (Top Level) —</option>
                                    <?php foreach ($parentCategories as $parent): ?>
                                        <?php if ($parent['id'] != $category['id']): ?>
                                            <option value="<?php echo $parent['id']; ?>" <?php echo $category['parent_id'] == $parent['id'] ? 'selected' : ''; ?>>
                                                <?php echo !empty($parent['parent_name']) ? escape($parent['parent_name']) . ' → ' : ''; ?><?php echo escape($parent['name']); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <?php if (!empty($category['show_subcategory_grid'])): ?>
                                    <span class="badge" style="background: var(--admin-primary); color: white;">Yes</span>
                                <?php else: ?>
                                    <span style="color: var(--admin-text-light);">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-gray"><?php echo $category['product_count']; ?></span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="btn btn-sm btn-outline" onclick="openEditModal(<?php echo $category['id']; ?>)">Edit</button>
                                    <?php if ($category['product_count'] == 0): ?>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo escape($category['name']); ?>')">Delete</button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline" disabled title="Has products">Delete</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p style="font-size: 0.8rem; color: var(--admin-text-light); margin-top: 1rem;">
            Drag rows to reorder categories. Subcategories are shown indented under their parent.
        </p>
    <?php else: ?>
        <div class="empty-state">
            <h3>No categories yet</h3>
            <p>Add your first category to organize products.</p>
            <button type="button" class="btn btn-primary" onclick="showAddForm()" style="margin-top: 1rem;">+ Add Category</button>
        </div>
    <?php endif; ?>
</div>

<!-- Edit Category Modal -->
<div id="editModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3>Edit Category</h3>
            <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form action="/admin/categories/update" method="POST" enctype="multipart/form-data">
            <?php echo csrfField(); ?>
            <input type="hidden" name="id" id="editId">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Category Name *</label>
                    <input type="text" name="name" id="editName" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" id="editSlug" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Parent Category</label>
                    <select name="parent_id" id="editParent" class="form-input">
                        <option value="">— None (Top Level) —</option>
                        <?php foreach ($parentCategories as $parent): ?>
                            <option value="<?php echo $parent['id']; ?>">
                                <?php echo !empty($parent['parent_name']) ? escape($parent['parent_name']) . ' → ' : ''; ?><?php echo escape($parent['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Category Image</label>
                    <div id="currentImagePreview" style="margin-bottom: 0.5rem;"></div>
                    <input type="file" name="image" class="form-input" accept="image/*">
                    <div id="removeImageCheckbox" style="display: none; margin-top: 0.5rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="remove_image" value="1" style="width: auto;">
                            Remove current image
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="show_subcategory_grid" id="editShowGrid" value="1" style="width: auto;">
                        Show subcategory grid (display subcategories with images)
                    </label>
                    <p style="font-size: 0.8rem; color: var(--admin-text-light); margin-top: 0.25rem;">
                        When enabled, visitors see subcategory images with "Shop Now" buttons instead of products.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<style>
.subcategory-row {
    background: #f9fafb;
}
.subcategory-row td:first-child {
    padding-left: 2rem;
}
.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}
.modal-content {
    background: white;
    border-radius: 8px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #eee;
}
.modal-header h3 {
    margin: 0;
}
.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--admin-text-light);
}
.modal-body {
    padding: 1.5rem;
}
.modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}
</style>

<script>
const csrfToken = '<?php echo csrf_token(); ?>';

function showAddForm() {
    document.getElementById('addCategoryForm').style.display = 'block';
}

function hideAddForm() {
    document.getElementById('addCategoryForm').style.display = 'none';
}

function openEditModal(id) {
    const row = document.querySelector(`tr[data-id="${id}"]`);
    const name = row.querySelector('.category-name').textContent.trim();
    const slug = row.querySelector('.category-slug').textContent.trim();
    const parentId = row.dataset.parent || '';
    const image = row.dataset.image || '';
    const showGrid = row.dataset.showGrid === '1';

    document.getElementById('editId').value = id;
    document.getElementById('editName').value = name;
    document.getElementById('editSlug').value = slug;
    document.getElementById('editParent').value = parentId;
    document.getElementById('editShowGrid').checked = showGrid;

    // Handle image preview
    const previewContainer = document.getElementById('currentImagePreview');
    const removeCheckbox = document.getElementById('removeImageCheckbox');
    if (image) {
        previewContainer.innerHTML = `<img src="${image}" alt="" style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px;">`;
        removeCheckbox.style.display = 'block';
    } else {
        previewContainer.innerHTML = '<span style="color: var(--admin-text-light);">No image</span>';
        removeCheckbox.style.display = 'none';
    }

    // Disable self as parent option
    const parentSelect = document.getElementById('editParent');
    Array.from(parentSelect.options).forEach(opt => {
        opt.disabled = opt.value == id;
    });

    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function deleteCategory(id, name) {
    if (!confirm(`Delete category "${name}"? This cannot be undone.`)) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/admin/categories/delete';
    form.innerHTML = `
        <input type="hidden" name="_csrf_token" value="${csrfToken}">
        <input type="hidden" name="id" value="${id}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Drag and drop reordering
const tbody = document.getElementById('categoryList');
if (tbody) {
    let draggedRow = null;

    tbody.querySelectorAll('tr').forEach(row => {
        const handle = row.querySelector('.drag-handle');

        handle.addEventListener('mousedown', () => {
            row.draggable = true;
        });

        row.addEventListener('dragstart', function(e) {
            draggedRow = this;
            this.style.opacity = '0.5';
            e.dataTransfer.effectAllowed = 'move';
        });

        row.addEventListener('dragend', function() {
            this.style.opacity = '1';
            this.draggable = false;
            draggedRow = null;
            saveOrder();
        });

        row.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });

        row.addEventListener('drop', function(e) {
            e.preventDefault();
            if (draggedRow && draggedRow !== this) {
                const rect = this.getBoundingClientRect();
                const midY = rect.top + rect.height / 2;
                if (e.clientY < midY) {
                    tbody.insertBefore(draggedRow, this);
                } else {
                    tbody.insertBefore(draggedRow, this.nextSibling);
                }
            }
        });
    });

    function saveOrder() {
        const ids = [];
        tbody.querySelectorAll('tr').forEach(row => {
            ids.push(row.dataset.id);
        });

        fetch('/admin/categories/reorder', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
            credentials: 'same-origin',
            body: `_csrf_token=${csrfToken}&ids=${ids.join(',')}`
        });
    }
}
</script>
