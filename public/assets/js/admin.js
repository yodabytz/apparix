/**
 * Admin Panel JavaScript
 * Apparix E-Commerce
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize components
    initFlashMessages();
    initDeleteConfirmations();
    initVariantFilters();
});

/**
 * Auto-dismiss flash messages after 5 seconds
 */
function initFlashMessages() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
}

/**
 * Confirm before delete actions
 */
function initDeleteConfirmations() {
    document.querySelectorAll('[data-confirm]').forEach(element => {
        element.addEventListener('click', function(e) {
            const message = this.dataset.confirm || 'Are you sure you want to delete this item?';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

/**
 * Filter variants by option values
 */
function initVariantFilters() {
    const filterSelects = document.querySelectorAll('.variant-filter');
    filterSelects.forEach(select => {
        select.addEventListener('change', filterVariants);
    });
}

function filterVariants() {
    const filters = {};
    document.querySelectorAll('.variant-filter').forEach(select => {
        if (select.value) {
            filters[select.dataset.option] = select.value;
        }
    });

    document.querySelectorAll('.variant-card').forEach(card => {
        let show = true;
        for (const [option, value] of Object.entries(filters)) {
            if (card.dataset[option.toLowerCase()] !== value) {
                show = false;
                break;
            }
        }
        card.style.display = show ? 'block' : 'none';
    });
}

/**
 * Tab switching
 */
function switchTab(tabName) {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.tab === tabName);
    });
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.toggle('active', content.id === 'tab-' + tabName);
    });
}

/**
 * Debounce function for input handlers
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
