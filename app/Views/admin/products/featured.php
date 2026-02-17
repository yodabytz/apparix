<div class="page-header">
    <h1>Featured Products Order</h1>
    <a href="/admin/products" class="btn btn-outline">Back to Products</a>
</div>

<div class="card">
    <?php if (!empty($products)): ?>
        <p style="margin-bottom: 1rem; color: var(--admin-text-light);">
            Drag products to reorder. The order shown here is how they appear on the homepage.
        </p>

        <div id="featuredList" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
            <?php foreach ($products as $index => $product): ?>
                <div class="featured-item" data-id="<?php echo $product['id']; ?>" draggable="true"
                     style="background: var(--admin-bg); border-radius: 8px; padding: 1rem; cursor: grab; border: 2px solid transparent; transition: all 0.2s; position: relative;">
                    <button type="button" onclick="removeFeatured(<?php echo $product['id']; ?>, this)" class="remove-btn" title="Remove from Featured">&times;</button>
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                        <span style="font-weight: 700; color: var(--admin-primary); font-size: 1.25rem;">#<?php echo $index + 1; ?></span>
                        <span style="color: var(--admin-text-light);">&#9776;</span>
                    </div>
                    <?php if ($product['image']): ?>
                        <img src="<?php echo escape($product['image']); ?>" alt="" style="width: 100%; height: 120px; object-fit: cover; border-radius: 6px; margin-bottom: 0.75rem;">
                    <?php else: ?>
                        <div style="width: 100%; height: 120px; background: #ddd; border-radius: 6px; margin-bottom: 0.75rem;"></div>
                    <?php endif; ?>
                    <p style="font-weight: 500; font-size: 0.9rem; margin: 0;"><?php echo escape($product['name']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="saveStatus" style="margin-top: 1rem; padding: 0.75rem 1rem; background: #d1fae5; color: #065f46; border-radius: 6px; display: none;">
            Order saved!
        </div>
    <?php else: ?>
        <div class="empty-state">
            <h3>No featured products</h3>
            <p>Mark products as "Featured" in the product edit page to add them here.</p>
            <a href="/admin/products" class="btn btn-primary" style="margin-top: 1rem;">View Products</a>
        </div>
    <?php endif; ?>
</div>

<style>
.remove-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #ef4444;
    color: white;
    border: none;
    cursor: pointer;
    font-size: 16px;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0.8;
    transition: opacity 0.2s, transform 0.2s;
    z-index: 10;
}
.remove-btn:hover {
    opacity: 1;
    transform: scale(1.1);
}
</style>

<script>
const csrfToken = '<?php echo csrf_token(); ?>';
const list = document.getElementById('featuredList');

async function removeFeatured(productId, btn) {
    if (!confirm('Remove this product from Featured?')) return;

    btn.disabled = true;
    btn.textContent = '...';

    try {
        const response = await fetch('/admin/products/remove-featured', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `_csrf_token=${csrfToken}&id=${productId}`
        });

        const data = await response.json();

        if (data.success) {
            // Remove the item from DOM with animation
            const item = btn.closest('.featured-item');
            item.style.opacity = '0';
            item.style.transform = 'scale(0.8)';
            setTimeout(() => {
                item.remove();
                updateNumbers();

                // Check if no items left
                if (list.children.length === 0) {
                    location.reload();
                }
            }, 200);
        } else {
            alert('Failed to remove: ' + (data.error || 'Unknown error'));
            btn.disabled = false;
            btn.textContent = '×';
        }
    } catch (error) {
        alert('Error removing product');
        btn.disabled = false;
        btn.textContent = '×';
    }
}

if (list) {
    let draggedItem = null;

    list.querySelectorAll('.featured-item').forEach(item => {
        item.addEventListener('dragstart', function(e) {
            draggedItem = this;
            this.style.opacity = '0.5';
            this.style.borderColor = 'var(--admin-primary)';
            e.dataTransfer.effectAllowed = 'move';
        });

        item.addEventListener('dragend', function() {
            this.style.opacity = '1';
            this.style.borderColor = 'transparent';
            draggedItem = null;
            updateNumbers();
            saveOrder();
        });

        item.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            this.style.borderColor = 'var(--admin-primary)';
        });

        item.addEventListener('dragleave', function() {
            if (this !== draggedItem) {
                this.style.borderColor = 'transparent';
            }
        });

        item.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = 'transparent';
            if (draggedItem && draggedItem !== this) {
                const rect = this.getBoundingClientRect();
                const midX = rect.left + rect.width / 2;
                if (e.clientX < midX) {
                    list.insertBefore(draggedItem, this);
                } else {
                    list.insertBefore(draggedItem, this.nextSibling);
                }
            }
        });
    });

    function updateNumbers() {
        list.querySelectorAll('.featured-item').forEach((item, index) => {
            item.querySelector('span').textContent = '#' + (index + 1);
        });
    }

    function saveOrder() {
        const ids = [];
        list.querySelectorAll('.featured-item').forEach(item => {
            ids.push(item.dataset.id);
        });

        fetch('/admin/products/reorder-featured', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
            credentials: 'same-origin',
            body: `_csrf_token=${csrfToken}&ids=${ids.join(',')}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const status = document.getElementById('saveStatus');
                status.style.display = 'block';
                setTimeout(() => { status.style.display = 'none'; }, 3000);
            }
        });
    }
}
</script>
