<?php
/**
 * Navigation Menus Editor
 */
?>
<div class="page-header">
    <h1>Navigation Menus</h1>
    <a href="/admin/settings" class="btn btn-outline">Back to Settings</a>
</div>

<?php if (!empty($flash)): ?>
    <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : 'success'; ?>" style="margin-bottom: 1.5rem;">
        <?php echo escape($flash['message']); ?>
    </div>
<?php endif; ?>

<form id="menusForm" method="POST" action="/admin/settings/menus">
    <?php echo csrfField(); ?>
    <input type="hidden" name="navbar_menu" id="navbarMenuJson">
    <input type="hidden" name="footer_menu" id="footerMenuJson">

    <!-- Navbar Menu -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title">Navbar Menu</h3>
            <button type="button" class="btn btn-sm btn-primary" onclick="addMenuItem('navbar')">+ Add Item</button>
        </div>
        <p style="color: var(--admin-text-light); font-size: 0.875rem; margin-bottom: 1rem;">
            These links appear in the main navigation bar. Special items (Search, Cart, Favorites, Account) are always shown automatically.
        </p>
        <div id="navbar-items" class="menu-items-list">
            <?php foreach ($navbarMenu as $i => $item): ?>
            <div class="menu-item" data-index="<?php echo $i; ?>">
                <div class="menu-item-handle" title="Drag to reorder">&#9776;</div>
                <div class="menu-item-fields">
                    <input type="text" class="form-input menu-label" value="<?php echo escape($item['label']); ?>" placeholder="Label (e.g. Home)">
                    <input type="text" class="form-input menu-url" value="<?php echo escape($item['url']); ?>" placeholder="URL (e.g. / or /products)">
                </div>
                <button type="button" class="btn btn-sm btn-danger menu-item-remove" onclick="removeMenuItem(this)" title="Remove">&times;</button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Footer Menu -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title">Footer Menu</h3>
            <button type="button" class="btn btn-sm btn-primary" onclick="addMenuItem('footer')">+ Add Item</button>
        </div>
        <p style="color: var(--admin-text-light); font-size: 0.875rem; margin-bottom: 1rem;">
            These links appear in the footer bar at the bottom of every page.
        </p>
        <div id="footer-items" class="menu-items-list">
            <?php foreach ($footerMenu as $i => $item): ?>
            <div class="menu-item" data-index="<?php echo $i; ?>">
                <div class="menu-item-handle" title="Drag to reorder">&#9776;</div>
                <div class="menu-item-fields">
                    <input type="text" class="form-input menu-label" value="<?php echo escape($item['label']); ?>" placeholder="Label (e.g. Privacy)">
                    <input type="text" class="form-input menu-url" value="<?php echo escape($item['url']); ?>" placeholder="URL (e.g. /privacy)">
                </div>
                <button type="button" class="btn btn-sm btn-danger menu-item-remove" onclick="removeMenuItem(this)" title="Remove">&times;</button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div style="display: flex; gap: 1rem;">
        <button type="submit" class="btn btn-primary">Save Menus</button>
    </div>
</form>

<style>
.menu-items-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.menu-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: var(--admin-bg, #f9fafb);
    border: 1px solid var(--admin-border, #e5e7eb);
    border-radius: 6px;
    cursor: grab;
}
.menu-item:active { cursor: grabbing; }
.menu-item.dragging { opacity: 0.5; border-style: dashed; }
.menu-item-handle {
    font-size: 1.2rem;
    color: #9ca3af;
    cursor: grab;
    user-select: none;
    flex-shrink: 0;
}
.menu-item-fields {
    display: flex;
    gap: 0.5rem;
    flex: 1;
}
.menu-item-fields .form-input {
    flex: 1;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}
.menu-item-remove {
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    padding: 0;
    font-size: 1.25rem;
    line-height: 1;
    border-radius: 6px;
}
@media (max-width: 640px) {
    .menu-item-fields { flex-direction: column; }
}
</style>

<script>
function addMenuItem(section) {
    var list = document.getElementById(section + '-items');
    var div = document.createElement('div');
    div.className = 'menu-item';
    div.innerHTML = '<div class="menu-item-handle" title="Drag to reorder">&#9776;</div>' +
        '<div class="menu-item-fields">' +
        '<input type="text" class="form-input menu-label" value="" placeholder="Label">' +
        '<input type="text" class="form-input menu-url" value="" placeholder="URL (e.g. /about)">' +
        '</div>' +
        '<button type="button" class="btn btn-sm btn-danger menu-item-remove" onclick="removeMenuItem(this)" title="Remove">&times;</button>';
    list.appendChild(div);
    initDragDrop(list);
    div.querySelector('.menu-label').focus();
}

function removeMenuItem(btn) {
    var item = btn.closest('.menu-item');
    item.remove();
}

function serializeMenus() {
    var navbar = [], footer = [];
    document.querySelectorAll('#navbar-items .menu-item').forEach(function(item) {
        var label = item.querySelector('.menu-label').value.trim();
        var url = item.querySelector('.menu-url').value.trim();
        if (label && url) navbar.push({label: label, url: url});
    });
    document.querySelectorAll('#footer-items .menu-item').forEach(function(item) {
        var label = item.querySelector('.menu-label').value.trim();
        var url = item.querySelector('.menu-url').value.trim();
        if (label && url) footer.push({label: label, url: url});
    });
    document.getElementById('navbarMenuJson').value = JSON.stringify(navbar);
    document.getElementById('footerMenuJson').value = JSON.stringify(footer);
}

// Drag and drop reordering
function initDragDrop(list) {
    var items = list.querySelectorAll('.menu-item');
    items.forEach(function(item) {
        item.setAttribute('draggable', 'true');
        item.removeEventListener('dragstart', handleDragStart);
        item.removeEventListener('dragover', handleDragOver);
        item.removeEventListener('drop', handleDrop);
        item.removeEventListener('dragend', handleDragEnd);
        item.addEventListener('dragstart', handleDragStart);
        item.addEventListener('dragover', handleDragOver);
        item.addEventListener('drop', handleDrop);
        item.addEventListener('dragend', handleDragEnd);
    });
}

var dragSrc = null;
function handleDragStart(e) {
    dragSrc = this;
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
}
function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    var target = this.closest('.menu-item');
    if (target && target !== dragSrc) {
        var list = target.parentNode;
        var items = Array.from(list.children);
        var dragIdx = items.indexOf(dragSrc);
        var targetIdx = items.indexOf(target);
        if (dragIdx < targetIdx) {
            list.insertBefore(dragSrc, target.nextSibling);
        } else {
            list.insertBefore(dragSrc, target);
        }
    }
}
function handleDrop(e) { e.preventDefault(); }
function handleDragEnd() { this.classList.remove('dragging'); }

// Init
document.getElementById('menusForm').addEventListener('submit', function(e) {
    serializeMenus();
});
initDragDrop(document.getElementById('navbar-items'));
initDragDrop(document.getElementById('footer-items'));
</script>
