<?php $editing = !empty($newsletter); ?>
<div class="page-header">
    <h1><?php echo $editing ? 'Edit Newsletter' : 'Compose Newsletter'; ?></h1>
    <a href="/admin/newsletter" class="btn btn-outline">Back to Newsletter</a>
</div>

<form id="newsletterForm" action="/admin/newsletter/send" method="POST" class="admin-form">
    <?php echo csrfField(); ?>

    <div class="admin-card">
        <div class="form-group">
            <label for="subject">Subject Line *</label>
            <input type="text" id="subject" name="subject" required
                   placeholder="e.g., New Products Just Arrived!"
                   value="<?php echo escape($newsletter['subject'] ?? ''); ?>"
                   class="subject-input">
        </div>
    </div>

    <div class="admin-card editor-card">
        <label>Newsletter Content *</label>
        <?php include __DIR__ . '/../partials/mintaro-editor.php'; ?>
    </div>

    <div class="form-actions">
        <button type="button" class="btn btn-outline" onclick="previewNewsletter()">Preview</button>
        <button type="submit" class="btn btn-primary btn-large" id="sendBtn">Send Newsletter</button>
    </div>
</form>

<!-- Preview Modal -->
<div id="previewModal" class="modal" style="display: none;">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3>Newsletter Preview</h3>
            <button type="button" class="modal-close" onclick="closePreview()">&times;</button>
        </div>
        <div class="modal-body">
            <iframe id="previewFrame" style="width: 100%; height: 500px; border: 1px solid #e5e7eb; border-radius: 4px;"></iframe>
        </div>
    </div>
</div>

<!-- Send Confirmation Modal -->
<div id="confirmModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirm Send</h3>
            <button type="button" class="modal-close" onclick="closeConfirm()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to send this newsletter to all active subscribers?</p>
            <p class="warning-text">This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeConfirm()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="confirmSend()">Yes, Send Newsletter</button>
        </div>
    </div>
</div>

<script>
let editor;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Mintaro editor
    editor = new Mintaro({
        containerId: 'mintaro-editor',
        toolbarId: 'mintaro-toolbar',
        placeholder: 'Write your newsletter content here... Use the toolbar above to format your content.',
        height: '400px',
        enablePreview: false
    });
<?php if ($editing && !empty($newsletter['content'])): ?>
    // Pre-fill editor with existing newsletter content (admin-only, content from DB)
    document.getElementById('mintaro-editor').innerHTML = <?php echo json_encode($newsletter['content']); ?>;
<?php endif; ?>
});

// Form submission
document.getElementById('newsletterForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const subject = document.getElementById('subject').value.trim();
    const content = editor.getHTML();

    if (!subject) {
        alert('Please enter a subject line');
        return;
    }

    if (!content || content === '<p><br></p>' || content.trim() === '') {
        alert('Please enter newsletter content');
        return;
    }

    // Set content in hidden field
    document.getElementById('content').value = content;

    // Show confirmation modal
    document.getElementById('confirmModal').style.display = 'flex';
});

async function confirmSend() {
    closeConfirm();

    const btn = document.getElementById('sendBtn');
    btn.disabled = true;
    btn.textContent = 'Sending...';

    // Make sure content is set
    document.getElementById('content').value = editor.getHTML();

    const formData = new FormData(document.getElementById('newsletterForm'));

    try {
        const response = await fetch('/admin/newsletter/send', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e, text);
            alert('Server error. Please try again.');
            btn.disabled = false;
            btn.textContent = 'Send Newsletter';
            return;
        }

        if (data.success) {
            alert(data.message);
            window.location.href = '/admin/newsletter';
        } else {
            alert(data.error || 'Failed to send newsletter');
            btn.disabled = false;
            btn.textContent = 'Send Newsletter';
        }
    } catch (error) {
        console.error('Fetch error:', error);
        alert('An error occurred: ' + error.message);
        btn.disabled = false;
        btn.textContent = 'Send Newsletter';
    }
}

function previewNewsletter() {
    const subject = document.getElementById('subject').value.trim();
    const content = editor.getHTML();

    if (!content || content.trim() === '') {
        alert('Please enter some content to preview');
        return;
    }

    const formData = new FormData();
    formData.append('subject', subject || 'Newsletter Preview');
    formData.append('content', content);
    formData.append('_csrf_token', document.querySelector('input[name="_csrf_token"]').value);

    fetch('/admin/newsletter/preview', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        const frame = document.getElementById('previewFrame');
        frame.srcdoc = html;
        document.getElementById('previewModal').style.display = 'flex';
    });
}

function closePreview() {
    document.getElementById('previewModal').style.display = 'none';
}

function closeConfirm() {
    document.getElementById('confirmModal').style.display = 'none';
}

// Close modals on escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePreview();
        closeConfirm();
    }
});
</script>

<style>
.subject-input {
    font-size: 1.25rem;
    padding: 1rem;
}

.editor-card {
    padding-bottom: 1rem;
}

.editor-card > label {
    display: block;
    margin-bottom: 0.75rem;
    font-weight: 500;
}

.mintaro-container {
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow: auto;
}

.modal-large {
    max-width: 800px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6b7280;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

.warning-text {
    color: #dc3545;
    font-size: 0.9rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 1.5rem;
}
</style>
