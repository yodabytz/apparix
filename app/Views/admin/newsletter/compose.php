<div class="page-header">
    <h1>Compose Newsletter</h1>
    <a href="/admin/newsletter" class="btn btn-outline">Back to Newsletter</a>
</div>

<form id="newsletterForm" action="/admin/newsletter/send" method="POST" class="admin-form">
    <?php echo csrfField(); ?>

    <div class="admin-card">
        <div class="form-group">
            <label for="subject">Subject Line *</label>
            <input type="text" id="subject" name="subject" required
                   placeholder="e.g., New Products Just Arrived!"
                   class="subject-input">
        </div>
    </div>

    <div class="admin-card editor-card">
        <label>Newsletter Content *</label>

        <!-- Mintaro Editor Container -->
        <div class="mintaro-container">

            <!-- Mintaro Branding Header -->
            <div class="mintaro-header">
                <img src="/assets/images/mintaro-logo.png" alt="Mintaro" class="mintaro-logo">
            </div>

            <!-- Editor Toolbar -->
            <div class="mintaro-toolbar" id="mintaro-toolbar">
                <!-- Row 1: Text Formatting -->
                <div class="mintaro-toolbar-group">
                    <button type="button" class="mintaro-button" data-command="bold" title="Bold (Ctrl+B)"><strong>B</strong></button>
                    <button type="button" class="mintaro-button" data-command="italic" title="Italic (Ctrl+I)"><em>I</em></button>
                    <button type="button" class="mintaro-button" data-command="underline" title="Underline (Ctrl+U)"><u>U</u></button>
                    <button type="button" class="mintaro-button" data-command="strikethrough" title="Strikethrough"><s>S</s></button>
                    <button type="button" class="mintaro-button" data-command="superscript" title="Superscript">Sup</button>
                    <button type="button" class="mintaro-button" data-command="subscript" title="Subscript">Sub</button>
                </div>

                <div class="mintaro-toolbar-separator"></div>

                <!-- Color Pickers -->
                <div class="mintaro-toolbar-group">
                    <button type="button" class="mintaro-button" id="font-color-btn" title="Font Color">A</button>
                    <button type="button" class="mintaro-button" id="bg-color-btn" style="background: #000; color: #fff;" title="Background Color">A</button>
                </div>

                <div class="mintaro-toolbar-separator"></div>

                <!-- Font Controls -->
                <div class="mintaro-toolbar-group">
                    <button type="button" class="mintaro-button" id="font-family-btn" title="Font Family">Font</button>
                    <button type="button" class="mintaro-button" id="font-size-btn" title="Font Size">Size</button>
                    <button type="button" class="mintaro-button" id="line-height-btn" title="Line Height">Line</button>
                </div>

                <div class="mintaro-toolbar-separator"></div>

                <!-- Block Formatting -->
                <div class="mintaro-toolbar-group">
                    <button type="button" class="mintaro-button" id="formats-btn" title="Blocks">H1-H3</button>
                    <button type="button" class="mintaro-button" data-command="insertUnorderedList" title="Bullet List">UL</button>
                    <button type="button" class="mintaro-button" data-command="insertOrderedList" title="Numbered List">OL</button>
                    <button type="button" class="mintaro-button" id="checklist-btn" title="Checklist">✓</button>
                </div>

                <div class="mintaro-toolbar-separator"></div>

                <!-- Alignment -->
                <div class="mintaro-toolbar-group">
                    <button type="button" class="mintaro-button" data-command="justifyLeft" title="Align Left">
                        <svg class="mintaro-icon" viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
                            <path d="M3 3h18v2H3V3zm0 4h18v2H3V7zm0 4h18v2H3v-2zm0 4h12v2H3v-2z"/>
                        </svg>
                    </button>
                    <button type="button" class="mintaro-button" data-command="justifyCenter" title="Align Center">
                        <svg class="mintaro-icon" viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
                            <path d="M3 3h18v2H3V3zm2 4h14v2H5V7zm-2 4h18v2H3v-2zm2 4h14v2H5v-2z"/>
                        </svg>
                    </button>
                    <button type="button" class="mintaro-button" data-command="justifyRight" title="Align Right">
                        <svg class="mintaro-icon" viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
                            <path d="M3 3h18v2H3V3zm0 4h18v2H3V7zm0 4h18v2H3v-2zm6 4h12v2H9v-2z"/>
                        </svg>
                    </button>
                    <button type="button" class="mintaro-button" data-command="justifyFull" title="Justify">
                        <svg class="mintaro-icon" viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
                            <path d="M3 3h18v2H3V3zm0 4h18v2H3V7zm0 4h18v2H3v-2zm0 4h18v2H3v-2z"/>
                        </svg>
                    </button>
                </div>

                <div class="mintaro-toolbar-separator"></div>

                <!-- Lists & Indentation -->
                <div class="mintaro-toolbar-group">
                    <button type="button" class="mintaro-button" data-command="indent" title="Indent">In</button>
                    <button type="button" class="mintaro-button" data-command="outdent" title="Outdent">Out</button>
                </div>

                <div class="mintaro-toolbar-separator"></div>

                <!-- Code -->
                <div class="mintaro-toolbar-group">
                    <button type="button" class="mintaro-button" id="inline-code-btn" title="Inline Code">Code</button>
                    <button type="button" class="mintaro-button" data-command="formatBlock" data-value="pre" title="Code Block">
                        <svg class="mintaro-icon" viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
                            <path d="M9.4 16.6L4.8 12l4.6-4.6L6.6 6l-6 6 6 6 2.8-2.4zm5.2 0l4.6-4.6-4.6-4.6 2.8-2.8 6 6-6 6-2.8-2.4z"/>
                        </svg>
                    </button>
                </div>

                <div class="mintaro-toolbar-separator"></div>

                <!-- Special Formatting -->
                <div class="mintaro-toolbar-group">
                    <button type="button" class="mintaro-button" data-command="formatBlock" data-value="blockquote" title="Blockquote">
                        <svg class="mintaro-icon" viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
                            <path d="M6 17h3l2-4V7H5v6h3zm8 0h3l2-4V7h-6v6h3z"/>
                        </svg>
                    </button>
                    <button type="button" class="mintaro-button" data-command="removeFormat" title="Clear Formatting">✕</button>
                </div>

                <div class="mintaro-toolbar-separator"></div>

                <!-- Content Elements -->
                <div class="mintaro-toolbar-group">
                    <button type="button" class="mintaro-button" id="link-btn" title="Insert Link">
                        <svg class="mintaro-icon" viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
                            <path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/>
                        </svg>
                    </button>
                    <button type="button" class="mintaro-button" id="image-btn" title="Insert Image">
                        <svg class="mintaro-icon" viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
                            <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                        </svg>
                    </button>
                    <button type="button" class="mintaro-button" id="upload-btn" title="Upload Image">
                        <svg class="mintaro-icon" viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
                            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                        </svg>
                    </button>
                    <button type="button" class="mintaro-button" id="video-btn" title="Embed Video">
                        <svg class="mintaro-icon" viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
                            <path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/>
                        </svg>
                    </button>
                    <button type="button" class="mintaro-button" id="table-btn" title="Insert Table">
                        <svg class="mintaro-icon" viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
                            <path d="M10 10.5h4v3h-4zM16 10.5h3v3h-3zM16 4H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-1 9.5v3h-4v-3h4zM5 7h14v2.5H5zm0 9.5h3v3H5zm4 0h4v3H9z"/>
                        </svg>
                    </button>
                </div>

                <div class="mintaro-toolbar-separator"></div>

                <!-- Utility -->
                <div class="mintaro-toolbar-group">
                    <button type="button" class="mintaro-button" id="emoji-btn" title="Insert Emoji">😊</button>
                    <button type="button" class="mintaro-button" id="html-source-btn" title="HTML Source">HTML</button>
                    <button type="button" class="mintaro-button" id="fullscreen-btn" title="Full Screen">Full</button>
                    <button type="button" class="mintaro-button" data-command="undo" title="Undo (Ctrl+Z)">↶</button>
                    <button type="button" class="mintaro-button" data-command="redo" title="Redo (Ctrl+Y)">↷</button>
                    <button type="button" class="mintaro-button" id="print-btn" title="Print">Print</button>
                    <button type="button" class="mintaro-button" id="help-btn" title="Help">?</button>
                </div>
            </div>

            <!-- Editor Content Area -->
            <div id="mintaro-editor"></div>

            <!-- Status Bar -->
            <div class="mintaro-status-bar">
                <div class="mintaro-stats">
                    <div class="mintaro-stat">
                        <span class="mintaro-stat-icon">📄</span>
                        <span id="mintaro-word-count">Words: 0</span>
                    </div>
                    <div class="mintaro-stat">
                        <span class="mintaro-stat-icon">🔤</span>
                        <span id="mintaro-char-count">Characters: 0</span>
                    </div>
                </div>
            </div>

        </div><!-- Close mintaro-container -->

        <input type="hidden" id="content" name="content">
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

<link rel="stylesheet" href="/assets/css/mintaro.css">
<script src="/assets/js/mintaro/mintaro.js"></script>

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
    overflow: hidden;
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
