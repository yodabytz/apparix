/**
 * Mintaro - Custom Rich Text Editor
 * A lightweight WYSIWYG editor for FightPulse story publishing
 */

class Mintaro {
    constructor(config = {}) {
        this.config = {
            containerId: config.containerId || 'mintaro-editor',
            toolbarId: config.toolbarId || 'mintaro-toolbar',
            previewId: config.previewId || 'mintaro-preview',
            height: config.height || '500px',
            placeholder: config.placeholder || 'Start writing your story...',
            enablePreview: config.enablePreview !== false,
            onSave: config.onSave || null,
            ...config
        };

        this.editor = null;
        this.toolbar = null;
        this.preview = null;
        this.history = [];
        this.historyIndex = -1;
        this.isComposing = false;
        this.saveStateTimeout = null;
        this.lastSavedState = null;
        this.lastKeyWasEnter = false;
        this.selectedCell = null;
        this.tableContextMenu = null;

        this.init();
    }

    /**
     * Initialize the editor
     */
    init() {
        this.editor = document.getElementById(this.config.containerId);
        this.toolbar = document.getElementById(this.config.toolbarId);
        if (this.config.enablePreview) {
            this.preview = document.getElementById(this.config.previewId);
        }

        if (!this.editor) {
            console.error(`Mintaro: Container with ID "${this.config.containerId}" not found`);
            return;
        }

        // Add branding header to the editor container (FIRST, before toolbar)
        this.addBrandingHeader();

        // Setup editor
        this.editor.contentEditable = true;
        this.editor.spellcheck = true;
        this.editor.style.minHeight = this.config.height;
        this.editor.className = 'mintaro-editor-content';
        this.editor.setAttribute('data-placeholder', this.config.placeholder);

        // Setup toolbar if it exists
        if (this.toolbar) {
            this.setupToolbar();
        }

        // Setup image upload (drag and drop)
        this.setupImageUpload();

        // Setup table editing
        this.setupTableEditing();

        // Attach event listeners
        this.attachEventListeners();

        // Initialize history
        this.saveState();

        console.log('Mintaro editor initialized successfully');
    }

    /**
     * Setup table editing with context menu and floating toolbar
     */
    setupTableEditing() {
        // Create floating table toolbar
        this.tableToolbar = document.createElement('div');
        this.tableToolbar.className = 'mintaro-table-toolbar';
        this.tableToolbar.innerHTML = `
            <button data-action="insertRowAbove" title="Insert Row Above">↑ Row</button>
            <button data-action="insertRowBelow" title="Insert Row Below">↓ Row</button>
            <button data-action="insertColumnLeft" title="Insert Column Left">← Col</button>
            <button data-action="insertColumnRight" title="Insert Column Right">→ Col</button>
            <span class="mintaro-toolbar-divider"></span>
            <button data-action="deleteRow" title="Delete Row">✕ Row</button>
            <button data-action="deleteColumn" title="Delete Column">✕ Col</button>
            <span class="mintaro-toolbar-divider"></span>
            <button data-action="cellProperties" title="Cell Properties">Cell</button>
            <button data-action="tableProperties" title="Table Properties">Table</button>
            <button data-action="deleteTable" title="Delete Table" class="mintaro-danger">🗑</button>
        `;
        this.tableToolbar.style.display = 'none';
        document.body.appendChild(this.tableToolbar);

        // Handle toolbar actions
        this.tableToolbar.addEventListener('click', (e) => {
            const action = e.target.dataset.action;
            if (action) {
                this.handleTableAction(action);
            }
        });

        // Create context menu element (for right-click)
        this.tableContextMenu = document.createElement('div');
        this.tableContextMenu.className = 'mintaro-table-context-menu';
        this.tableContextMenu.innerHTML = `
            <div class="mintaro-context-section">
                <div class="mintaro-context-title">Row</div>
                <button data-action="insertRowAbove">Insert Row Above</button>
                <button data-action="insertRowBelow">Insert Row Below</button>
                <button data-action="deleteRow">Delete Row</button>
            </div>
            <div class="mintaro-context-section">
                <div class="mintaro-context-title">Column</div>
                <button data-action="insertColumnLeft">Insert Column Left</button>
                <button data-action="insertColumnRight">Insert Column Right</button>
                <button data-action="deleteColumn">Delete Column</button>
            </div>
            <div class="mintaro-context-section">
                <div class="mintaro-context-title">Cell</div>
                <button data-action="mergeCells">Merge Cells</button>
                <button data-action="splitCell">Split Cell</button>
                <button data-action="cellProperties">Cell Properties</button>
            </div>
            <div class="mintaro-context-section">
                <div class="mintaro-context-title">Table</div>
                <button data-action="tableProperties">Table Properties</button>
                <button data-action="deleteTable">Delete Table</button>
            </div>
        `;
        this.tableContextMenu.style.display = 'none';
        document.body.appendChild(this.tableContextMenu);

        // Handle context menu actions
        this.tableContextMenu.addEventListener('click', (e) => {
            const action = e.target.dataset.action;
            if (action) {
                this.handleTableAction(action);
                this.hideTableContextMenu();
            }
        });

        // Right-click on table cells
        this.editor.addEventListener('contextmenu', (e) => {
            const cell = e.target.closest('td, th');
            if (cell) {
                e.preventDefault();
                this.selectedCell = cell;
                this.showTableContextMenu(e.clientX, e.clientY);
            }
        });

        // Click on table cells to select and show toolbar
        this.editor.addEventListener('click', (e) => {
            const cell = e.target.closest('td, th');
            if (cell) {
                this.selectedCell = cell;
                // Highlight selected cell
                this.editor.querySelectorAll('td, th').forEach(c => c.classList.remove('mintaro-cell-selected'));
                cell.classList.add('mintaro-cell-selected');
                // Show floating toolbar above the table
                this.showTableToolbar(cell);
            } else {
                this.selectedCell = null;
                this.editor.querySelectorAll('td, th').forEach(c => c.classList.remove('mintaro-cell-selected'));
                this.hideTableToolbar();
            }
            this.hideTableContextMenu();
        });

        // Hide menus on scroll or click outside
        document.addEventListener('click', (e) => {
            if (!this.tableContextMenu.contains(e.target) && !e.target.closest('td, th')) {
                this.hideTableContextMenu();
            }
            if (!this.tableToolbar.contains(e.target) && !e.target.closest('td, th') && !e.target.closest('.mintaro-modal')) {
                this.hideTableToolbar();
            }
        });

        document.addEventListener('scroll', () => {
            this.hideTableContextMenu();
            this.hideTableToolbar();
        }, true);
    }

    /**
     * Show floating table toolbar above the table
     */
    showTableToolbar(cell) {
        const table = cell.closest('table');
        if (!table) return;

        const tableRect = table.getBoundingClientRect();
        const editorRect = this.editor.getBoundingClientRect();

        this.tableToolbar.style.display = 'flex';

        // Position above the table
        let top = tableRect.top - this.tableToolbar.offsetHeight - 8;
        if (top < editorRect.top) {
            // If no room above, put below the table
            top = tableRect.bottom + 8;
        }

        this.tableToolbar.style.top = top + 'px';
        this.tableToolbar.style.left = tableRect.left + 'px';

        // Make sure it doesn't go off-screen
        const toolbarRect = this.tableToolbar.getBoundingClientRect();
        if (toolbarRect.right > window.innerWidth - 10) {
            this.tableToolbar.style.left = (window.innerWidth - toolbarRect.width - 10) + 'px';
        }
    }

    /**
     * Hide floating table toolbar
     */
    hideTableToolbar() {
        if (this.tableToolbar) {
            this.tableToolbar.style.display = 'none';
        }
    }

    /**
     * Show table context menu at position
     */
    showTableContextMenu(x, y) {
        this.tableContextMenu.style.display = 'block';
        this.tableContextMenu.style.left = x + 'px';
        this.tableContextMenu.style.top = y + 'px';

        // Adjust if menu goes off screen
        const rect = this.tableContextMenu.getBoundingClientRect();
        if (rect.right > window.innerWidth) {
            this.tableContextMenu.style.left = (x - rect.width) + 'px';
        }
        if (rect.bottom > window.innerHeight) {
            this.tableContextMenu.style.top = (y - rect.height) + 'px';
        }
    }

    /**
     * Hide table context menu
     */
    hideTableContextMenu() {
        if (this.tableContextMenu) {
            this.tableContextMenu.style.display = 'none';
        }
    }

    /**
     * Handle table editing actions
     */
    handleTableAction(action) {
        if (!this.selectedCell) return;

        const table = this.selectedCell.closest('table');
        const row = this.selectedCell.closest('tr');
        if (!table || !row) return;

        const rowIndex = Array.from(table.querySelectorAll('tr')).indexOf(row);
        const cellIndex = Array.from(row.children).indexOf(this.selectedCell);
        const colCount = row.children.length;

        switch (action) {
            case 'insertRowAbove':
                this.insertTableRowAt(table, rowIndex, 'before');
                break;
            case 'insertRowBelow':
                this.insertTableRowAt(table, rowIndex, 'after');
                break;
            case 'deleteRow':
                if (table.querySelectorAll('tr').length > 1) {
                    row.remove();
                } else {
                    alert('Cannot delete the last row');
                }
                break;
            case 'insertColumnLeft':
                this.insertTableColumnAt(table, cellIndex, 'before');
                break;
            case 'insertColumnRight':
                this.insertTableColumnAt(table, cellIndex, 'after');
                break;
            case 'deleteColumn':
                if (colCount > 1) {
                    this.deleteTableColumnAt(table, cellIndex);
                } else {
                    alert('Cannot delete the last column');
                }
                break;
            case 'mergeCells':
                this.showMergeCellsDialog();
                break;
            case 'splitCell':
                this.showSplitCellDialog();
                break;
            case 'cellProperties':
                this.showCellPropertiesDialog();
                break;
            case 'tableProperties':
                this.showTablePropertiesDialog(table);
                break;
            case 'deleteTable':
                if (confirm('Are you sure you want to delete this table?')) {
                    table.remove();
                }
                break;
        }

        this.saveState();
    }

    /**
     * Insert row at specific position
     */
    insertTableRowAt(table, rowIndex, position) {
        const rows = table.querySelectorAll('tr');
        const referenceRow = rows[rowIndex];
        const colCount = referenceRow.children.length;

        const newRow = document.createElement('tr');
        for (let i = 0; i < colCount; i++) {
            const cell = document.createElement('td');
            cell.innerHTML = '&nbsp;';
            cell.style.cssText = 'border: 1px solid #ddd; padding: 8px;';
            newRow.appendChild(cell);
        }

        if (position === 'before') {
            referenceRow.parentNode.insertBefore(newRow, referenceRow);
        } else {
            referenceRow.parentNode.insertBefore(newRow, referenceRow.nextSibling);
        }
    }

    /**
     * Insert column at specific position
     */
    insertTableColumnAt(table, colIndex, position) {
        const rows = table.querySelectorAll('tr');
        rows.forEach((row, rowIdx) => {
            const isHeader = rowIdx === 0 && row.querySelector('th');
            const cell = document.createElement(isHeader ? 'th' : 'td');
            cell.innerHTML = '&nbsp;';
            cell.style.cssText = 'border: 1px solid #ddd; padding: 8px;';

            if (isHeader) {
                cell.style.backgroundColor = '#f3f4f6';
                cell.style.fontWeight = 'bold';
            }

            const referenceCell = row.children[colIndex];
            if (position === 'before') {
                row.insertBefore(cell, referenceCell);
            } else {
                row.insertBefore(cell, referenceCell ? referenceCell.nextSibling : null);
            }
        });
    }

    /**
     * Delete column at specific position
     */
    deleteTableColumnAt(table, colIndex) {
        const rows = table.querySelectorAll('tr');
        rows.forEach(row => {
            if (row.children[colIndex]) {
                row.children[colIndex].remove();
            }
        });
    }

    /**
     * Show cell properties dialog
     */
    showCellPropertiesDialog() {
        if (!this.selectedCell) return;

        const cell = this.selectedCell;
        const currentBg = cell.style.backgroundColor || '#ffffff';
        const currentColor = cell.style.color || '#000000';
        const currentAlign = cell.style.textAlign || 'left';
        const currentVAlign = cell.style.verticalAlign || 'middle';
        const currentPadding = parseInt(cell.style.padding) || 8;

        const modal = document.createElement('div');
        modal.className = 'mintaro-modal';
        modal.innerHTML = `
            <div class="mintaro-modal-dialog">
                <div class="mintaro-modal-header">
                    <h3>Cell Properties</h3>
                    <button class="mintaro-modal-close">&times;</button>
                </div>
                <div class="mintaro-modal-body">
                    <div class="mintaro-form-group">
                        <label>Background Color:</label>
                        <input type="color" id="cell-bg-color" value="${this.rgbToHex2(currentBg)}">
                    </div>
                    <div class="mintaro-form-group">
                        <label>Text Color:</label>
                        <input type="color" id="cell-text-color" value="${this.rgbToHex2(currentColor)}">
                    </div>
                    <div class="mintaro-form-group">
                        <label>Horizontal Align:</label>
                        <select id="cell-align">
                            <option value="left" ${currentAlign === 'left' ? 'selected' : ''}>Left</option>
                            <option value="center" ${currentAlign === 'center' ? 'selected' : ''}>Center</option>
                            <option value="right" ${currentAlign === 'right' ? 'selected' : ''}>Right</option>
                        </select>
                    </div>
                    <div class="mintaro-form-group">
                        <label>Vertical Align:</label>
                        <select id="cell-valign">
                            <option value="top" ${currentVAlign === 'top' ? 'selected' : ''}>Top</option>
                            <option value="middle" ${currentVAlign === 'middle' ? 'selected' : ''}>Middle</option>
                            <option value="bottom" ${currentVAlign === 'bottom' ? 'selected' : ''}>Bottom</option>
                        </select>
                    </div>
                    <div class="mintaro-form-group">
                        <label>Padding (px):</label>
                        <input type="number" id="cell-padding" value="${currentPadding}" min="0" max="50">
                    </div>
                </div>
                <div class="mintaro-modal-footer">
                    <button id="cell-props-apply" class="mintaro-btn-primary">Apply</button>
                    <button id="cell-props-cancel" class="mintaro-btn-secondary">Cancel</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const applyBtn = modal.querySelector('#cell-props-apply');
        const cancelBtn = modal.querySelector('#cell-props-cancel');
        const closeBtn = modal.querySelector('.mintaro-modal-close');

        applyBtn.addEventListener('click', () => {
            cell.style.backgroundColor = modal.querySelector('#cell-bg-color').value;
            cell.style.color = modal.querySelector('#cell-text-color').value;
            cell.style.textAlign = modal.querySelector('#cell-align').value;
            cell.style.verticalAlign = modal.querySelector('#cell-valign').value;
            cell.style.padding = modal.querySelector('#cell-padding').value + 'px';
            this.saveState();
            modal.remove();
        });

        const closeModal = () => modal.remove();
        cancelBtn.addEventListener('click', closeModal);
        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
    }

    /**
     * Show table properties dialog
     */
    showTablePropertiesDialog(table) {
        const currentWidth = table.style.width || '100%';
        const currentBorder = parseInt(table.style.borderWidth) || 1;
        const currentSpacing = parseInt(table.style.borderSpacing) || 0;

        const modal = document.createElement('div');
        modal.className = 'mintaro-modal';
        modal.innerHTML = `
            <div class="mintaro-modal-dialog">
                <div class="mintaro-modal-header">
                    <h3>Table Properties</h3>
                    <button class="mintaro-modal-close">&times;</button>
                </div>
                <div class="mintaro-modal-body">
                    <div class="mintaro-form-group">
                        <label>Width:</label>
                        <input type="text" id="table-width" value="${currentWidth}" placeholder="100% or 500px">
                    </div>
                    <div class="mintaro-form-group">
                        <label>Border Width (px):</label>
                        <input type="number" id="table-border" value="${currentBorder}" min="0" max="10">
                    </div>
                    <div class="mintaro-form-group">
                        <label>Cell Spacing (px):</label>
                        <input type="number" id="table-spacing" value="${currentSpacing}" min="0" max="20">
                    </div>
                    <div class="mintaro-form-group">
                        <label>Border Style:</label>
                        <select id="table-border-style">
                            <option value="solid">Solid</option>
                            <option value="dashed">Dashed</option>
                            <option value="dotted">Dotted</option>
                            <option value="none">None</option>
                        </select>
                    </div>
                    <div class="mintaro-form-group">
                        <label>Border Color:</label>
                        <input type="color" id="table-border-color" value="#dddddd">
                    </div>
                    <div class="mintaro-form-group">
                        <label><input type="checkbox" id="table-striped"> Striped Rows</label>
                    </div>
                </div>
                <div class="mintaro-modal-footer">
                    <button id="table-props-apply" class="mintaro-btn-primary">Apply</button>
                    <button id="table-props-cancel" class="mintaro-btn-secondary">Cancel</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const applyBtn = modal.querySelector('#table-props-apply');
        const cancelBtn = modal.querySelector('#table-props-cancel');
        const closeBtn = modal.querySelector('.mintaro-modal-close');

        applyBtn.addEventListener('click', () => {
            const width = modal.querySelector('#table-width').value;
            const border = modal.querySelector('#table-border').value;
            const spacing = modal.querySelector('#table-spacing').value;
            const borderStyle = modal.querySelector('#table-border-style').value;
            const borderColor = modal.querySelector('#table-border-color').value;
            const striped = modal.querySelector('#table-striped').checked;

            table.style.width = width;
            table.style.borderCollapse = spacing > 0 ? 'separate' : 'collapse';
            table.style.borderSpacing = spacing + 'px';

            // Apply border to all cells
            table.querySelectorAll('td, th').forEach(cell => {
                cell.style.border = `${border}px ${borderStyle} ${borderColor}`;
            });

            // Apply striped rows
            if (striped) {
                table.querySelectorAll('tr').forEach((row, idx) => {
                    if (idx > 0 && idx % 2 === 0) {
                        row.style.backgroundColor = '#f9fafb';
                    }
                });
            }

            this.saveState();
            modal.remove();
        });

        const closeModal = () => modal.remove();
        cancelBtn.addEventListener('click', closeModal);
        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
    }

    /**
     * Show merge cells dialog
     */
    showMergeCellsDialog() {
        if (!this.selectedCell) return;

        const modal = document.createElement('div');
        modal.className = 'mintaro-modal';
        modal.innerHTML = `
            <div class="mintaro-modal-dialog">
                <div class="mintaro-modal-header">
                    <h3>Merge Cells</h3>
                    <button class="mintaro-modal-close">&times;</button>
                </div>
                <div class="mintaro-modal-body">
                    <div class="mintaro-form-group">
                        <label>Columns to merge:</label>
                        <input type="number" id="merge-cols" value="2" min="1" max="10">
                    </div>
                    <div class="mintaro-form-group">
                        <label>Rows to merge:</label>
                        <input type="number" id="merge-rows" value="1" min="1" max="10">
                    </div>
                </div>
                <div class="mintaro-modal-footer">
                    <button id="merge-apply" class="mintaro-btn-primary">Merge</button>
                    <button id="merge-cancel" class="mintaro-btn-secondary">Cancel</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const applyBtn = modal.querySelector('#merge-apply');
        const cancelBtn = modal.querySelector('#merge-cancel');
        const closeBtn = modal.querySelector('.mintaro-modal-close');

        applyBtn.addEventListener('click', () => {
            const colSpan = parseInt(modal.querySelector('#merge-cols').value) || 1;
            const rowSpan = parseInt(modal.querySelector('#merge-rows').value) || 1;

            if (colSpan > 1) this.selectedCell.colSpan = colSpan;
            if (rowSpan > 1) this.selectedCell.rowSpan = rowSpan;

            // Remove cells covered by the merge
            const table = this.selectedCell.closest('table');
            const row = this.selectedCell.closest('tr');
            const cellIndex = Array.from(row.children).indexOf(this.selectedCell);
            const rowIndex = Array.from(table.querySelectorAll('tr')).indexOf(row);

            // Remove extra columns in current row
            for (let i = 1; i < colSpan && row.children[cellIndex + 1]; i++) {
                row.children[cellIndex + 1].remove();
            }

            // Remove cells in rows below
            const rows = table.querySelectorAll('tr');
            for (let r = 1; r < rowSpan && rows[rowIndex + r]; r++) {
                for (let c = 0; c < colSpan && rows[rowIndex + r].children[cellIndex]; c++) {
                    rows[rowIndex + r].children[cellIndex].remove();
                }
            }

            this.saveState();
            modal.remove();
        });

        const closeModal = () => modal.remove();
        cancelBtn.addEventListener('click', closeModal);
        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
    }

    /**
     * Show split cell dialog
     */
    showSplitCellDialog() {
        if (!this.selectedCell) return;

        const cell = this.selectedCell;
        if (cell.colSpan === 1 && cell.rowSpan === 1) {
            alert('This cell is not merged.');
            return;
        }

        cell.colSpan = 1;
        cell.rowSpan = 1;
        this.saveState();
    }

    /**
     * Convert RGB to Hex helper
     */
    rgbToHex2(rgb) {
        if (!rgb || rgb === 'transparent') return '#ffffff';
        if (rgb.startsWith('#')) return rgb;
        const match = rgb.match(/\d+/g);
        if (!match || match.length < 3) return '#ffffff';
        return '#' + match.slice(0, 3).map(x => {
            const hex = parseInt(x).toString(16);
            return hex.length === 1 ? '0' + hex : hex;
        }).join('');
    }

    /**
     * Insert horizontal rule
     */
    insertHorizontalRule() {
        document.execCommand('insertHorizontalRule');
        this.saveState();
    }

    /**
     * Open special characters dialog
     */
    openSpecialCharsDialog() {
        const chars = [
            '©', '®', '™', '€', '£', '¥', '¢', '§', '¶', '•',
            '†', '‡', '°', '±', '×', '÷', '≠', '≤', '≥', '∞',
            '←', '→', '↑', '↓', '↔', '⇐', '⇒', '⇑', '⇓', '⇔',
            '♠', '♣', '♥', '♦', '★', '☆', '✓', '✗', '✔', '✘',
            'α', 'β', 'γ', 'δ', 'ε', 'π', 'Ω', 'μ', 'σ', 'λ',
            '½', '¼', '¾', '⅓', '⅔', '⅛', '⅜', '⅝', '⅞', '‰'
        ];

        const modal = document.createElement('div');
        modal.className = 'mintaro-modal';
        modal.innerHTML = `
            <div class="mintaro-modal-dialog">
                <div class="mintaro-modal-header">
                    <h3>Special Characters</h3>
                    <button class="mintaro-modal-close">&times;</button>
                </div>
                <div class="mintaro-modal-body">
                    <div class="mintaro-special-chars-grid">
                        ${chars.map(c => `<button class="mintaro-special-char" data-char="${c}">${c}</button>`).join('')}
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        modal.querySelectorAll('.mintaro-special-char').forEach(btn => {
            btn.addEventListener('click', () => {
                document.execCommand('insertText', false, btn.dataset.char);
                this.saveState();
                modal.remove();
            });
        });

        const closeBtn = modal.querySelector('.mintaro-modal-close');
        closeBtn.addEventListener('click', () => modal.remove());
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });
    }

    /**
     * Open find and replace dialog
     */
    openFindReplaceDialog() {
        const modal = document.createElement('div');
        modal.className = 'mintaro-modal';
        modal.innerHTML = `
            <div class="mintaro-modal-dialog">
                <div class="mintaro-modal-header">
                    <h3>Find and Replace</h3>
                    <button class="mintaro-modal-close">&times;</button>
                </div>
                <div class="mintaro-modal-body">
                    <div class="mintaro-form-group">
                        <label>Find:</label>
                        <input type="text" id="find-text" placeholder="Text to find">
                    </div>
                    <div class="mintaro-form-group">
                        <label>Replace with:</label>
                        <input type="text" id="replace-text" placeholder="Replacement text">
                    </div>
                    <div class="mintaro-form-group">
                        <label><input type="checkbox" id="match-case"> Match case</label>
                    </div>
                </div>
                <div class="mintaro-modal-footer">
                    <button id="find-next" class="mintaro-btn-secondary">Find Next</button>
                    <button id="replace-one" class="mintaro-btn-secondary">Replace</button>
                    <button id="replace-all" class="mintaro-btn-primary">Replace All</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const findInput = modal.querySelector('#find-text');
        const replaceInput = modal.querySelector('#replace-text');
        const matchCase = modal.querySelector('#match-case');

        modal.querySelector('#find-next').addEventListener('click', () => {
            const text = findInput.value;
            if (text) window.find(text, matchCase.checked);
        });

        modal.querySelector('#replace-one').addEventListener('click', () => {
            const sel = window.getSelection();
            if (sel.toString()) {
                document.execCommand('insertText', false, replaceInput.value);
                this.saveState();
            }
        });

        modal.querySelector('#replace-all').addEventListener('click', () => {
            const find = findInput.value;
            const replace = replaceInput.value;
            if (!find) return;

            const content = this.editor.innerHTML;
            const flags = matchCase.checked ? 'g' : 'gi';
            const regex = new RegExp(find.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), flags);
            const count = (content.match(regex) || []).length;

            this.editor.innerHTML = content.replace(regex, replace);
            this.saveState();
            alert(`Replaced ${count} occurrence(s)`);
        });

        const closeBtn = modal.querySelector('.mintaro-modal-close');
        closeBtn.addEventListener('click', () => modal.remove());
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });
    }

    /**
     * Add branding header to editor container
     */
    addBrandingHeader() {
        const container = this.editor.closest('.mintaro-container');
        if (!container) return;

        // Check if header already exists
        if (container.querySelector('.mintaro-header')) {
            return;
        }

        // Create header element with just the logo
        const header = document.createElement('div');
        header.className = 'mintaro-header';
        header.innerHTML = `
            <img src="/mintaro/mintaro-logo.png" alt="Mintaro" class="mintaro-logo">
        `;

        // Insert header as first child of container
        container.insertBefore(header, container.firstChild);
    }

    /**
     * Setup toolbar buttons and event handlers
     */
    setupToolbar() {
        const buttons = this.toolbar.querySelectorAll('[data-command]');
        buttons.forEach(button => {
            const command = button.dataset.command;
            const value = button.dataset.value || null;

            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.executeCommand(command, value);
                this.editor.focus();
            });

            // Highlight active formatting buttons
            this.editor.addEventListener('selectionchange', () => {
                this.updateButtonStates();
            });
        });

        // Save button
        const saveBtn = this.toolbar.querySelector('[data-command="save"]');
        if (saveBtn) {
            saveBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.save();
            });
        }

        // Undo button
        const undoBtn = this.toolbar.querySelector('[data-command="undo"]');
        if (undoBtn) {
            undoBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.undo();
            });
        }

        // Redo button
        const redoBtn = this.toolbar.querySelector('[data-command="redo"]');
        if (redoBtn) {
            redoBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.redo();
            });
        }

        // Clear formatting button
        const clearBtn = this.toolbar.querySelector('[data-command="removeFormat"]');
        if (clearBtn) {
            clearBtn.addEventListener('click', (e) => {
                e.preventDefault();
                document.execCommand('removeFormat', false, null);
                this.saveState();
            });
        }

        // Special button handlers
        this.setupSpecialButtons();
    }

    /**
     * Setup special buttons (formats, emoji, table, etc.)
     */
    setupSpecialButtons() {
        // Block formats button
        const formatsBtn = this.toolbar.querySelector('#formats-btn');
        if (formatsBtn) {
            formatsBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.openFormatsMenu();
            });
        }

        // Font color button
        const fontColorBtn = this.toolbar.querySelector('#font-color-btn');
        if (fontColorBtn) {
            fontColorBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.openFontColorPicker();
            });
        }

        // Background color button
        const bgColorBtn = this.toolbar.querySelector('#bg-color-btn');
        if (bgColorBtn) {
            bgColorBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.openBackgroundColorPicker();
            });
        }

        // Font family button
        const fontFamilyBtn = this.toolbar.querySelector('#font-family-btn');
        if (fontFamilyBtn) {
            fontFamilyBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.openFontFamilyMenu();
            });
        }

        // Font size button
        const fontSizeBtn = this.toolbar.querySelector('#font-size-btn');
        if (fontSizeBtn) {
            fontSizeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.openFontSizeMenu();
            });
        }

        // Line height button
        const lineHeightBtn = this.toolbar.querySelector('#line-height-btn');
        if (lineHeightBtn) {
            lineHeightBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.openLineHeightMenu();
            });
        }

        // Checklist button
        const checklistBtn = this.toolbar.querySelector('#checklist-btn');
        if (checklistBtn) {
            checklistBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.insertChecklist();
            });
        }

        // Inline code button
        const inlineCodeBtn = this.toolbar.querySelector('#inline-code-btn');
        if (inlineCodeBtn) {
            inlineCodeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                document.execCommand('formatBlock', false, '<code>');
                this.saveState();
                this.editor.focus();
            });
        }

        // Link button
        const linkBtn = this.toolbar.querySelector('#link-btn');
        if (linkBtn) {
            linkBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.openLinkDialog();
            });
        }

        // Image button
        const imageBtn = this.toolbar.querySelector('#image-btn');
        if (imageBtn) {
            imageBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.openImageDialog();
            });
        }

        // Upload button
        const uploadBtn = this.toolbar.querySelector('#upload-btn');
        if (uploadBtn) {
            uploadBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.openImageUploadDialog();
            });
        }

        // Video button
        const videoBtn = this.toolbar.querySelector('#video-btn');
        if (videoBtn) {
            videoBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.openVideoEmbedDialog();
            });
        }

        // Table button
        const tableBtn = this.toolbar.querySelector('#table-btn');
        if (tableBtn) {
            tableBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.openTableDialog();
            });
        }

        // Emoji button
        const emojiBtn = this.toolbar.querySelector('#emoji-btn');
        if (emojiBtn) {
            emojiBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.openEmojiPicker();
            });
        }

        // HTML source button
        const htmlSourceBtn = this.toolbar.querySelector('#html-source-btn');
        if (htmlSourceBtn) {
            htmlSourceBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.openHTMLSourceEditor();
            });
        }

        // Fullscreen button
        const fullscreenBtn = this.toolbar.querySelector('#fullscreen-btn');
        if (fullscreenBtn) {
            fullscreenBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleFullscreen();
            });
        }

        // Print button
        const printBtn = this.toolbar.querySelector('#print-btn');
        if (printBtn) {
            printBtn.addEventListener('click', (e) => {
                e.preventDefault();
                window.print();
            });
        }

        // Help button
        const helpBtn = this.toolbar.querySelector('#help-btn');
        if (helpBtn) {
            helpBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.showHelp();
            });
        }
    }

    /**
     * Attach editor event listeners
     */
    attachEventListeners() {
        // Handle input with debouncing for undo/redo
        this.editor.addEventListener('input', () => {
            if (!this.isComposing) {
                this.updatePreview();
                this.updateWordCount();
                this.debouncedSaveState();
            }
        });

        // Prevent composition from creating multiple states
        this.editor.addEventListener('compositionstart', () => {
            this.isComposing = true;
        });

        this.editor.addEventListener('compositionend', () => {
            this.isComposing = false;
            this.saveState();
            this.updatePreview();
            this.updateWordCount();
        });

        // Keyboard shortcuts and block element exit
        this.editor.addEventListener('keydown', (e) => {
            this.handleKeyboardShortcuts(e);
            this.handleBlockElementExit(e);
        });

        // Paste handling - clean up pasted content
        this.editor.addEventListener('paste', (e) => {
            e.preventDefault();
            const text = e.clipboardData.getData('text/html') || e.clipboardData.getData('text/plain');
            this.insertCleanHTML(text);
        });

        // Placeholder handling
        this.editor.addEventListener('focus', () => {
            if (this.editor.textContent.trim() === '') {
                this.editor.classList.add('placeholder-active');
            }
        });

        this.editor.addEventListener('blur', () => {
            if (this.editor.textContent.trim() === '') {
                this.editor.classList.add('placeholder-active');
            } else {
                this.editor.classList.remove('placeholder-active');
            }
        });
    }

    /**
     * Execute formatting commands
     */
    executeCommand(command, value = null) {
        if (['insertLink', 'insertImage', 'createLink'].includes(command)) {
            if (command === 'insertLink' || command === 'createLink') {
                const url = prompt('Enter URL:', 'https://');
                if (url) {
                    document.execCommand('createLink', false, url);
                }
            } else if (command === 'insertImage') {
                const url = prompt('Enter image URL:', 'https://');
                if (url) {
                    document.execCommand('insertImage', false, url);
                }
            }
        } else {
            document.execCommand(command, false, value);
        }

        this.saveState();
        this.updatePreview();
    }

    /**
     * Handle keyboard shortcuts
     */
    handleKeyboardShortcuts(e) {
        if (!e.ctrlKey && !e.metaKey) return;

        switch (e.key.toLowerCase()) {
            case 'b':
                e.preventDefault();
                document.execCommand('bold', false);
                this.saveState();
                break;
            case 'i':
                e.preventDefault();
                document.execCommand('italic', false);
                this.saveState();
                break;
            case 'u':
                e.preventDefault();
                document.execCommand('underline', false);
                this.saveState();
                break;
            case 'z':
                // Let browser handle undo/redo natively but also use our history
                if (e.shiftKey) {
                    // Don't prevent default - let browser handle it
                    this.redo();
                } else {
                    // Don't prevent default - let browser handle it
                    this.undo();
                }
                break;
            case 'y':
                // Ctrl+Y for redo
                e.preventDefault();
                this.redo();
                break;
        }
    }

    /**
     * Handle exiting block elements (blockquote, code block, etc.)
     * Allows users to exit with Ctrl+Enter or double Enter
     */
    handleBlockElementExit(e) {
        // Block elements we support exiting from
        const blockElements = ['blockquote', 'pre', 'code'];

        // Check if we're inside a block element
        const selection = window.getSelection();
        if (!selection.rangeCount) return;

        const range = selection.getRangeAt(0);
        let node = range.commonAncestorContainer;

        // Traverse up to find parent block element
        while (node && node !== this.editor) {
            const nodeName = node.nodeName.toLowerCase();
            if (blockElements.includes(nodeName)) {
                break;
            }
            node = node.parentNode;
        }

        // If we're in a block element
        if (node && node !== this.editor && blockElements.includes(node.nodeName.toLowerCase())) {
            // Ctrl+Enter or double Enter to exit
            if ((e.ctrlKey && e.key === 'Enter') || (e.key === 'Enter' && this.lastKeyWasEnter)) {
                e.preventDefault();

                // Find the block element container
                let blockElement = node;
                while (blockElement && !blockElements.includes(blockElement.nodeName.toLowerCase())) {
                    blockElement = blockElement.parentNode;
                }

                if (blockElement) {
                    // Create a new paragraph after the block element
                    const newParagraph = document.createElement('p');
                    newParagraph.innerHTML = '<br>';

                    // Insert after the block element
                    blockElement.parentNode.insertBefore(newParagraph, blockElement.nextSibling);

                    // Move cursor to the new paragraph
                    const newRange = document.createRange();
                    newRange.setStart(newParagraph, 0);
                    newRange.collapse(true);
                    selection.removeAllRanges();
                    selection.addRange(newRange);

                    this.saveState();
                }

                this.lastKeyWasEnter = false;
            } else if (e.key === 'Enter') {
                this.lastKeyWasEnter = true;
                setTimeout(() => {
                    this.lastKeyWasEnter = false;
                }, 500);
            }
        } else {
            this.lastKeyWasEnter = false;
        }
    }

    /**
     * Update button active states based on current selection
     */
    updateButtonStates() {
        const buttons = {
            'bold': document.queryCommandState('bold'),
            'italic': document.queryCommandState('italic'),
            'underline': document.queryCommandState('underline'),
            'strikethrough': document.queryCommandState('strikethrough'),
            'insertUnorderedList': document.queryCommandState('insertUnorderedList'),
            'insertOrderedList': document.queryCommandState('insertOrderedList'),
        };

        Object.entries(buttons).forEach(([command, isActive]) => {
            const btn = this.toolbar?.querySelector(`[data-command="${command}"]`);
            if (btn) {
                if (isActive) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            }
        });
    }

    /**
     * Insert clean HTML (sanitized)
     */
    insertCleanHTML(html) {
        // Basic sanitization - remove script tags and dangerous content
        const temp = document.createElement('div');
        temp.innerHTML = html;

        // Remove script tags and event handlers
        const scripts = temp.querySelectorAll('script');
        scripts.forEach(script => script.remove());

        // Get the cleaned text
        const cleaned = temp.innerHTML;
        document.execCommand('insertHTML', false, cleaned);
        this.saveState();
    }

    /**
     * Debounced save state - prevents saving on every keystroke
     */
    debouncedSaveState() {
        clearTimeout(this.saveStateTimeout);
        this.saveStateTimeout = setTimeout(() => {
            this.saveState();
        }, 500); // Wait 500ms after last change before saving
    }

    /**
     * Save current state to history
     */
    saveState() {
        const currentState = this.editor.innerHTML;

        // Don't save if content hasn't actually changed
        if (this.lastSavedState === currentState) {
            return;
        }

        // Remove any redo history
        this.history = this.history.slice(0, this.historyIndex + 1);

        // Add new state
        this.history.push(currentState);
        this.historyIndex++;
        this.lastSavedState = currentState;

        // Limit history to 50 states
        if (this.history.length > 50) {
            this.history.shift();
            this.historyIndex--;
        }
    }

    /**
     * Undo last action
     */
    undo() {
        if (this.historyIndex > 0) {
            this.historyIndex--;
            this.editor.innerHTML = this.history[this.historyIndex];
            this.updatePreview();
            this.updateWordCount();
        }
    }

    /**
     * Redo last action
     */
    redo() {
        if (this.historyIndex < this.history.length - 1) {
            this.historyIndex++;
            this.editor.innerHTML = this.history[this.historyIndex];
            this.updatePreview();
            this.updateWordCount();
        }
    }

    /**
     * Update preview pane
     */
    updatePreview() {
        if (!this.preview) return;
        this.preview.innerHTML = this.editor.innerHTML;
    }

    /**
     * Update word and character count
     */
    updateWordCount() {
        const text = this.editor.textContent || '';
        const words = text.trim().split(/\s+/).filter(w => w.length > 0).length;
        const chars = text.length;

        const wordCountEl = document.getElementById('mintaro-word-count');
        const charCountEl = document.getElementById('mintaro-char-count');

        if (wordCountEl) {
            wordCountEl.textContent = `Words: ${words}`;
        }
        if (charCountEl) {
            charCountEl.textContent = `Characters: ${chars}`;
        }
    }

    /**
     * Get editor content as HTML
     */
    getHTML() {
        return this.editor.innerHTML;
    }

    /**
     * Get editor content as text
     */
    getText() {
        return this.editor.textContent;
    }

    /**
     * Set editor content
     */
    setContent(content, isHTML = true) {
        if (isHTML) {
            this.editor.innerHTML = content;
        } else {
            this.editor.textContent = content;
        }
        this.saveState();
        this.updatePreview();
        this.updateWordCount();
    }

    /**
     * Clear editor
     */
    clear() {
        this.editor.innerHTML = '';
        this.history = [];
        this.historyIndex = -1;
        this.saveState();
        this.updatePreview();
        this.updateWordCount();
    }

    /**
     * Save content (trigger callback)
     */
    save() {
        const content = this.getHTML();
        const text = this.getText();

        const saveData = {
            html: content,
            text: text,
            timestamp: new Date().toISOString(),
            wordCount: text.trim().split(/\s+/).filter(w => w.length > 0).length
        };

        if (typeof this.config.onSave === 'function') {
            this.config.onSave(saveData);
        }

        // Also show a save indicator
        const saveBtn = this.toolbar?.querySelector('[data-command="save"]');
        if (saveBtn) {
            const originalText = saveBtn.textContent;
            saveBtn.textContent = '✓ Saved!';
            setTimeout(() => {
                saveBtn.textContent = originalText;
            }, 2000);
        }

        return saveData;
    }

    /**
     * Export content as downloadable file
     */
    exportAsHTML(filename = 'document.html') {
        const html = `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>${filename}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 900px; margin: 0 auto; padding: 20px; }
        img { max-width: 100%; height: auto; }
    </style>
</head>
<body>
${this.getHTML()}
</body>
</html>`;

        const blob = new Blob([html], { type: 'text/html' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        URL.revokeObjectURL(url);
    }

    /**
     * Smart embed handler - detects and converts YouTube, Twitter, Instagram URLs
     */
    insertSmartEmbed() {
        const url = prompt('Paste URL (YouTube, Twitter, Instagram, or any embed URL):');
        if (!url) return;

        let embedCode = this.detectAndConvertEmbed(url);
        if (embedCode) {
            document.execCommand('insertHTML', false, embedCode);
            this.saveState();
        } else {
            alert('Could not detect embed type. Please paste a valid URL.');
        }
    }

    /**
     * Detect and convert various embed URLs
     */
    detectAndConvertEmbed(url) {
        const trimmedUrl = url.trim();

        // YouTube detection
        const youtubeMatch = trimmedUrl.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
        if (youtubeMatch) {
            const videoId = youtubeMatch[1];
            return `<iframe width="560" height="315" src="https://www.youtube.com/embed/${videoId}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
        }

        // Twitter/X detection
        if (trimmedUrl.includes('twitter.com') || trimmedUrl.includes('x.com')) {
            return `<blockquote class="twitter-tweet"><a href="${trimmedUrl}">Tweet</a></blockquote><script async src="https://platform.twitter.com/widgets.js"></script>`;
        }

        // Instagram detection
        if (trimmedUrl.includes('instagram.com')) {
            return `<blockquote class="instagram-media" data-instgrm-permalink="${trimmedUrl}"><a href="${trimmedUrl}">Instagram Post</a></blockquote><script async src="//www.instagram.com/embed.js"></script>`;
        }

        // Vimeo detection
        const vimeoMatch = trimmedUrl.match(/vimeo\.com\/(\d+)/);
        if (vimeoMatch) {
            const videoId = vimeoMatch[1];
            return `<iframe src="https://player.vimeo.com/video/${videoId}" width="560" height="315" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>`;
        }

        return null;
    }

    /**
     * Find and Replace dialog
     */
    openFindReplace() {
        const findText = prompt('Find:');
        if (!findText) return;

        const replaceText = prompt('Replace with:');
        if (replaceText === null) return;

        const replaceAll = confirm('Replace all occurrences?');
        this.findAndReplace(findText, replaceText, replaceAll);
    }

    /**
     * Find and replace text
     */
    findAndReplace(findText, replaceText, replaceAll = false) {
        const content = this.editor.innerHTML;
        let newContent = content;
        let count = 0;

        if (replaceAll) {
            const regex = new RegExp(this.escapeRegex(findText), 'g');
            newContent = content.replace(regex, replaceText);
            count = (content.match(regex) || []).length;
        } else {
            if (content.includes(findText)) {
                newContent = content.replace(findText, replaceText);
                count = 1;
            }
        }

        if (count > 0) {
            this.editor.innerHTML = newContent;
            this.saveState();
            alert(`Replaced ${count} occurrence${count !== 1 ? 's' : ''}`);
        } else {
            alert('No matches found');
        }
    }

    /**
     * Escape special regex characters
     */
    escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    /**
     * Insert table with advanced styling
     */
    insertTable() {
        const rows = prompt('Number of rows:', '3');
        if (!rows) return;

        const cols = prompt('Number of columns:', '3');
        if (!cols) return;

        let tableHTML = `<table style="border-collapse: collapse; width: 100%; margin: 12px 0; font-size: 14px;">
            <tbody>`;

        for (let i = 0; i < parseInt(rows); i++) {
            tableHTML += '<tr>';
            for (let j = 0; j < parseInt(cols); j++) {
                const cellType = i === 0 ? 'th' : 'td';
                const bgColor = i === 0 ? '#10b981' : 'transparent';
                const textColor = i === 0 ? 'white' : '#1f2937';
                tableHTML += `<${cellType} style="border: 1px solid #ddd; padding: 10px; background-color: ${bgColor}; color: ${textColor}; text-align: left;">Cell</` + cellType + `>`;
            }
            tableHTML += '</tr>';
        }

        tableHTML += '</tbody></table><p></p>';
        document.execCommand('insertHTML', false, tableHTML);
        this.saveState();
    }

    /**
     * Delete current table
     */
    deleteTable() {
        const table = this.editor.querySelector('table');
        if (!table) {
            alert('No table found. Click inside a table first.');
            return;
        }
        table.remove();
        this.saveState();
    }

    /**
     * Insert row into table
     */
    insertTableRow(position = 'after') {
        const table = this.editor.querySelector('table');
        if (!table) {
            alert('Please click inside a table first.');
            return;
        }

        const tbody = table.querySelector('tbody');
        const rows = tbody.querySelectorAll('tr');
        if (rows.length === 0) return;

        const firstRow = rows[0];
        const colCount = firstRow.querySelectorAll('td, th').length;

        // Create new row
        const newRow = document.createElement('tr');
        for (let i = 0; i < colCount; i++) {
            const cell = document.createElement('td');
            cell.textContent = 'Cell';
            cell.style.cssText = 'border: 1px solid #ddd; padding: 10px;';
            newRow.appendChild(cell);
        }

        if (position === 'before') {
            tbody.insertBefore(newRow, rows[0]);
        } else {
            tbody.appendChild(newRow);
        }

        this.saveState();
    }

    /**
     * Delete current row
     */
    deleteTableRow() {
        const table = this.editor.querySelector('table');
        if (!table) {
            alert('Please click inside a table first.');
            return;
        }

        const selection = window.getSelection();
        if (selection.rangeCount === 0) return;

        const range = selection.getRangeAt(0);
        const row = range.commonAncestorContainer.closest('tr');

        if (!row) {
            alert('Please click inside a table row.');
            return;
        }

        row.remove();
        this.saveState();
    }

    /**
     * Insert column into table
     */
    insertTableColumn(position = 'after') {
        const table = this.editor.querySelector('table');
        if (!table) {
            alert('Please click inside a table first.');
            return;
        }

        const rows = table.querySelectorAll('tr');
        const currentCell = this.editor.querySelector('td:hover, th:hover');
        let colIndex = 0;

        // Get column index
        if (currentCell) {
            colIndex = Array.from(currentCell.parentElement.children).indexOf(currentCell);
        }

        rows.forEach(row => {
            const newCell = position === 'before'
                ? document.createElement(row.children[0].tagName)
                : document.createElement('td');

            newCell.textContent = 'Cell';
            newCell.style.cssText = 'border: 1px solid #ddd; padding: 10px;';

            if (position === 'before') {
                if (colIndex > 0) {
                    row.insertBefore(newCell, row.children[colIndex]);
                } else {
                    row.insertBefore(newCell, row.children[0]);
                }
            } else {
                row.appendChild(newCell);
            }
        });

        this.saveState();
    }

    /**
     * Delete column from table
     */
    deleteTableColumn() {
        const table = this.editor.querySelector('table');
        if (!table) {
            alert('Please click inside a table first.');
            return;
        }

        const rows = table.querySelectorAll('tr');
        if (rows.length === 0) return;

        // Delete last column
        rows.forEach(row => {
            const cells = row.querySelectorAll('td, th');
            if (cells.length > 1) {
                cells[cells.length - 1].remove();
            }
        });

        this.saveState();
    }

    /**
     * Merge table cells
     */
    mergeCells() {
        alert('Cell merge feature: Select cells and use this function');
        // Advanced feature - can be implemented with cell selection
    }

    /**
     * Format table cell
     */
    formatTableCell(bgColor = null, textColor = null) {
        const cell = this.editor.querySelector('td:focus, th:focus');
        if (!cell) {
            alert('Please click inside a cell first.');
            return;
        }

        if (bgColor) {
            cell.style.backgroundColor = bgColor;
        }
        if (textColor) {
            cell.style.color = textColor;
        }

        this.saveState();
    }

    /**
     * Get table info
     */
    getTableInfo() {
        const table = this.editor.querySelector('table');
        if (!table) {
            return { count: 0, tables: [] };
        }

        const tables = this.editor.querySelectorAll('table');
        return {
            count: tables.length,
            tables: Array.from(tables).map(tbl => {
                const rows = tbl.querySelectorAll('tr');
                const cols = rows.length > 0
                    ? rows[0].querySelectorAll('td, th').length
                    : 0;
                return { rows: rows.length, cols: cols };
            })
        };
    }

    /**
     * Get detailed statistics
     */
    getStatistics() {
        const text = this.getText();
        const words = text.trim().split(/\s+/).filter(w => w.length > 0);
        const sentences = text.split(/[.!?]+/).filter(s => s.trim().length > 0);
        const paragraphs = this.editor.querySelectorAll('p').length || 1;
        const images = this.editor.querySelectorAll('img').length;
        const links = this.editor.querySelectorAll('a').length;

        const avgWordsPerSentence = sentences.length > 0
            ? (words.length / sentences.length).toFixed(1)
            : 0;

        // Simple readability score (Flesch Reading Ease approximation)
        const syllableCount = this.estimateSyllables(text);
        const readabilityScore = Math.max(0, 206.835 - 1.015 * (words.length / sentences.length) - 84.6 * (syllableCount / words.length)).toFixed(1);

        return {
            words: words.length,
            characters: text.length,
            charactersNoSpaces: text.replace(/\s/g, '').length,
            sentences: sentences.length,
            paragraphs: paragraphs,
            images: images,
            links: links,
            avgWordsPerSentence: avgWordsPerSentence,
            readabilityScore: readabilityScore,
            readingTime: Math.ceil(words.length / 200) + ' min read'
        };
    }

    /**
     * Estimate syllable count for readability scoring
     */
    estimateSyllables(text) {
        const syllablePattern = /[aeiouy]|e(?=d?$)|[^aeiou]e[aeiou]|ia|io|dg|(?:qu|[aeiou])[aeiou]/g;
        const match = text.toLowerCase().match(syllablePattern);
        return match ? match.length : 0;
    }

    /**
     * Insert special characters/symbols
     */
    insertSpecialChar(char) {
        document.execCommand('insertText', false, char);
        this.saveState();
    }

    /**
     * Get word count goal progress
     */
    getWordCountProgress(goal) {
        const stats = this.getStatistics();
        const percentage = Math.min(100, (stats.words / goal) * 100);
        return {
            current: stats.words,
            goal: goal,
            percentage: percentage.toFixed(1),
            remaining: Math.max(0, goal - stats.words)
        };
    }

    /**
     * Setup drag and drop for image uploads
     */
    setupImageUpload() {
        // Prevent default drag and drop behavior
        this.editor.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.editor.style.backgroundColor = 'rgba(16, 185, 129, 0.05)';
        });

        this.editor.addEventListener('dragleave', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.editor.style.backgroundColor = '#ffffff';
        });

        this.editor.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.editor.style.backgroundColor = '#ffffff';

            const files = e.dataTransfer.files;
            for (let file of files) {
                if (file.type.startsWith('image/')) {
                    this.handleImageUpload(file);
                }
            }
        });
    }

    /**
     * Handle image file upload (converts to base64 for inline embedding)
     */
    handleImageUpload(file) {
        const reader = new FileReader();

        reader.onload = (e) => {
            const base64Image = e.target.result;
            this.insertImageFromBase64(base64Image, file.name);
        };

        reader.onerror = () => {
            alert('Error reading file. Please try again.');
        };

        reader.readAsDataURL(file);
    }

    /**
     * Insert image from base64 data
     */
    insertImageFromBase64(base64Data, filename = 'image') {
        const html = `<img src="${base64Data}" alt="${filename}" style="max-width: 100%; height: auto; margin: 12px 0; border-radius: 6px;">`;
        document.execCommand('insertHTML', false, html);
        this.saveState();
    }

    /**
     * Open file picker for image upload
     */
    pickImageFile() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.multiple = false;

        input.onchange = (e) => {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                this.handleImageUpload(file);
            }
        };

        input.click();
    }

    /**
     * Resize image by width percentage or pixels
     */
    resizeImage(widthValue, unit = '%') {
        // Find selected image
        const selection = window.getSelection();
        if (selection.rangeCount === 0) {
            alert('Please select an image first');
            return;
        }

        const range = selection.getRangeAt(0);
        const container = range.commonAncestorContainer;
        const img = container.nodeType === Node.TEXT_NODE
            ? container.parentElement.querySelector('img')
            : container.querySelector('img');

        if (!img) {
            alert('Please select or click on an image');
            return;
        }

        img.style.width = `${widthValue}${unit}`;
        img.style.height = 'auto';
        this.saveState();
    }

    /**
     * Get image upload stats
     */
    getImageStats() {
        const images = this.editor.querySelectorAll('img');
        return {
            count: images.length,
            images: Array.from(images).map(img => ({
                src: img.src.substring(0, 50) + (img.src.length > 50 ? '...' : ''),
                alt: img.alt || 'No alt text',
                width: img.width,
                height: img.height
            }))
        };
    }

    /**
     * Wrap image with a link
     */
    linkImage(imageUrl) {
        const linkUrl = prompt('Enter URL to link image to:', 'https://');
        if (!linkUrl) return;

        const img = this.editor.querySelector('img');
        if (!img) {
            alert('Please click on an image first');
            return;
        }

        // Create link wrapper
        const link = document.createElement('a');
        link.href = linkUrl;
        link.title = `Click to open: ${linkUrl}`;
        link.style.cursor = 'pointer';

        // Insert link before image
        img.parentNode.insertBefore(link, img);
        link.appendChild(img);

        this.saveState();
        alert('Image linked successfully!');
    }

    /**
     * Edit image properties (alt text, title, etc)
     */
    editImageProperties() {
        const img = this.editor.querySelector('img');
        if (!img) {
            alert('Please click on an image first');
            return;
        }

        const altText = prompt('Image alt text (for accessibility):', img.alt || '');
        if (altText !== null) {
            img.alt = altText;
        }

        const title = prompt('Image title (tooltip):', img.title || '');
        if (title !== null) {
            img.title = title;
        }

        this.saveState();
    }

    /**
     * Remove image link (unlink)
     */
    unlinkImage() {
        const img = this.editor.querySelector('img');
        if (!img) {
            alert('Please click on an image first');
            return;
        }

        const link = img.parentElement;
        if (link.tagName !== 'A') {
            alert('This image is not linked');
            return;
        }

        // Move image out of link
        link.parentNode.insertBefore(img, link);
        link.remove();

        this.saveState();
        alert('Image link removed');
    }

    /**
     * Set image as clickable link to larger version
     */
    makeImageClickable() {
        const img = this.editor.querySelector('img');
        if (!img) {
            alert('Please click on an image first');
            return;
        }

        // Use the image itself as the link target (opens in new tab)
        const link = document.createElement('a');
        link.href = img.src;
        link.target = '_blank';
        link.title = 'Click to view full size image';

        img.parentNode.insertBefore(link, img);
        link.appendChild(img);

        this.saveState();
    }

    /**
     * Get all linked images
     */
    getLinkedImages() {
        const links = Array.from(this.editor.querySelectorAll('a'));
        return links
            .filter(link => link.querySelector('img'))
            .map(link => ({
                imageUrl: link.querySelector('img').src.substring(0, 50),
                linkUrl: link.href,
                alt: link.querySelector('img').alt
            }));
    }

    /**
     * Open font color picker with eyedropper
     */
    openFontColorPicker() {
        this.openColorPicker('foreColor', 'Font Color');
    }

    /**
     * Open background color picker with eyedropper
     */
    openBackgroundColorPicker() {
        this.openColorPicker('backColor', 'Background Color');
    }

    /**
     * Open color picker dialog with color wheel and eyedropper support
     */
    openColorPicker(command, title) {
        const modal = document.createElement('div');
        modal.className = 'mintaro-color-picker-modal';
        modal.innerHTML = `
            <div class="mintaro-color-picker-dialog">
                <div class="mintaro-color-picker-header">
                    <h3>${title}</h3>
                    <button class="mintaro-color-picker-close">✕</button>
                </div>
                <div class="mintaro-color-picker-content">
                    <div class="mintaro-color-wheel-container">
                        <canvas id="mintaro-color-wheel" class="mintaro-color-wheel" width="240" height="240"></canvas>
                        <div id="mintaro-color-crosshair" class="mintaro-color-crosshair"></div>
                    </div>
                    <div class="mintaro-color-controls">
                        <div class="mintaro-brightness-control">
                            <label>Brightness:</label>
                            <input type="range" id="mintaro-brightness" class="mintaro-brightness-slider" min="0" max="100" value="100">
                        </div>
                        <div class="mintaro-color-input-group">
                            <label>Hex:</label>
                            <div class="mintaro-color-input-wrapper">
                                <input type="text" id="mintaro-hex-input" class="mintaro-hex-input" placeholder="#000000" maxlength="7">
                                <button id="mintaro-eyedropper-btn" class="mintaro-eyedropper-btn" title="Pick color from page">
                                    🧪
                                </button>
                            </div>
                        </div>
                        <div class="mintaro-color-preview-group">
                            <div id="mintaro-color-preview" class="mintaro-color-preview"></div>
                        </div>
                    </div>
                </div>
                <div class="mintaro-color-picker-footer">
                    <button id="mintaro-color-apply" class="mintaro-color-apply-btn">Apply Color</button>
                    <button id="mintaro-color-cancel" class="mintaro-color-cancel-btn">Cancel</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const canvas = modal.querySelector('#mintaro-color-wheel');
        const ctx = canvas.getContext('2d');
        const hexInput = modal.querySelector('#mintaro-hex-input');
        const brightnessSlider = modal.querySelector('#mintaro-brightness');
        const preview = modal.querySelector('#mintaro-color-preview');
        const eyedropperBtn = modal.querySelector('#mintaro-eyedropper-btn');
        const applyBtn = modal.querySelector('#mintaro-color-apply');
        const cancelBtn = modal.querySelector('#mintaro-color-cancel');
        const closeBtn = modal.querySelector('.mintaro-color-picker-close');
        const crosshair = modal.querySelector('#mintaro-color-crosshair');

        let selectedHue = 0;
        let selectedSaturation = 100;
        let selectedBrightness = 100;

        // Draw color wheel
        const drawColorWheel = () => {
            const centerX = canvas.width / 2;
            const centerY = canvas.height / 2;
            const radius = Math.min(centerX, centerY) - 10;

            // Clear canvas
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Draw color wheel
            for (let angle = 0; angle < 360; angle += 1) {
                const startAngle = (angle - 90) * Math.PI / 180;
                const endAngle = (angle + 1 - 90) * Math.PI / 180;

                const gradient = ctx.createLinearGradient(
                    centerX, centerY,
                    centerX + radius * Math.cos(startAngle),
                    centerY + radius * Math.sin(startAngle)
                );

                // Gradient from white (center) to full color (edge)
                gradient.addColorStop(0, `hsl(${angle}, 0%, ${selectedBrightness}%)`);
                gradient.addColorStop(1, `hsl(${angle}, 100%, ${selectedBrightness * 0.5}%)`);

                ctx.fillStyle = gradient;
                ctx.beginPath();
                ctx.moveTo(centerX, centerY);
                ctx.arc(centerX, centerY, radius, startAngle, endAngle);
                ctx.closePath();
                ctx.fill();
            }

            // Draw outer circle border
            ctx.strokeStyle = 'rgba(0, 0, 0, 0.2)';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.arc(centerX, centerY, radius, 0, Math.PI * 2);
            ctx.stroke();
        };

        // Update crosshair position
        const updateCrosshair = () => {
            const centerX = canvas.width / 2;
            const centerY = canvas.height / 2;
            const radius = Math.min(centerX, centerY) - 10;

            const angle = selectedHue * Math.PI / 180;
            const distance = (selectedSaturation / 100) * radius;

            const x = centerX + distance * Math.cos(angle - Math.PI / 2);
            const y = centerY + distance * Math.sin(angle - Math.PI / 2);

            crosshair.style.left = x + 'px';
            crosshair.style.top = y + 'px';
        };

        // Update color display
        const updateColor = () => {
            const color = `hsl(${selectedHue}, ${selectedSaturation}%, ${selectedBrightness * 0.5}%)`;
            const rgbColor = this.hslToRgb(selectedHue, selectedSaturation, selectedBrightness * 0.5);
            const hexColor = this.rgbToHex(rgbColor.r, rgbColor.g, rgbColor.b);

            preview.style.backgroundColor = color;
            preview.style.color = selectedBrightness * 0.5 > 50 ? '#1f2937' : '#ffffff';
            preview.textContent = hexColor.toUpperCase();
            hexInput.value = hexColor.toUpperCase();

            updateCrosshair();
        };

        // Handle canvas clicks
        canvas.addEventListener('click', (e) => {
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            const centerX = canvas.width / 2;
            const centerY = canvas.height / 2;

            const dx = x - centerX;
            const dy = y - centerY;

            selectedHue = (Math.atan2(dy, dx) * 180 / Math.PI + 90 + 360) % 360;
            const distance = Math.sqrt(dx * dx + dy * dy);
            const radius = Math.min(centerX, centerY) - 10;
            selectedSaturation = Math.min(100, (distance / radius) * 100);

            updateColor();
        });

        // Handle brightness slider
        brightnessSlider.addEventListener('input', (e) => {
            selectedBrightness = parseInt(e.target.value);
            drawColorWheel();
            updateColor();
        });

        // Handle hex input
        hexInput.addEventListener('input', (e) => {
            let hex = e.target.value.trim();
            if (!hex.startsWith('#')) hex = '#' + hex;

            if (/^#[0-9A-F]{6}$/i.test(hex)) {
                const rgb = this.hexToRgb(hex);
                const hsl = this.rgbToHsl(rgb.r, rgb.g, rgb.b);
                selectedHue = hsl.h;
                selectedSaturation = hsl.s;
                selectedBrightness = hsl.l * 2;
                brightnessSlider.value = selectedBrightness;
                drawColorWheel();
                updateColor();
            }
        });

        // Eyedropper functionality
        if (window.EyeDropper) {
            eyedropperBtn.addEventListener('click', async () => {
                try {
                    const result = await new EyeDropper().open();
                    hexInput.value = result.sRGBHex.toUpperCase();
                    hexInput.dispatchEvent(new Event('input'));
                } catch (err) {
                    // Cancelled
                }
            });
        } else {
            eyedropperBtn.style.opacity = '0.5';
        }

        // Apply color
        applyBtn.addEventListener('click', () => {
            const hex = hexInput.value.trim();
            if (/^#[0-9A-F]{6}$/i.test(hex)) {
                document.execCommand(command, false, hex);
                this.saveState();
            }
            modal.remove();
        });

        // Close
        const closeModal = () => modal.remove();
        cancelBtn.addEventListener('click', closeModal);
        closeBtn.addEventListener('click', closeModal);

        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeModal();
        });

        // Initialize
        drawColorWheel();
        updateColor();
    }

    /**
     * Convert RGB to Hex
     */
    rgbToHex(r, g, b) {
        return '#' + [r, g, b].map(x => {
            const hex = Math.round(x).toString(16);
            return hex.length === 1 ? '0' + hex : hex;
        }).join('').toUpperCase();
    }

    /**
     * Convert Hex to RGB
     */
    hexToRgb(hex) {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : { r: 0, g: 0, b: 0 };
    }

    /**
     * Convert RGB to HSL
     */
    rgbToHsl(r, g, b) {
        r /= 255;
        g /= 255;
        b /= 255;
        const max = Math.max(r, g, b);
        const min = Math.min(r, g, b);
        let h = 0, s = 0;
        const l = (max + min) / 2;

        if (max !== min) {
            const d = max - min;
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            switch (max) {
                case r: h = ((g - b) / d + (g < b ? 6 : 0)) / 6; break;
                case g: h = ((b - r) / d + 2) / 6; break;
                case b: h = ((r - g) / d + 4) / 6; break;
            }
        }

        return {
            h: Math.round(h * 360),
            s: Math.round(s * 100),
            l: Math.round(l * 100)
        };
    }

    /**
     * Convert HSL to RGB
     */
    hslToRgb(h, s, l) {
        h /= 360;
        s /= 100;
        l /= 100;
        let r, g, b;

        if (s === 0) {
            r = g = b = l;
        } else {
            const hue2rgb = (p, q, t) => {
                if (t < 0) t += 1;
                if (t > 1) t -= 1;
                if (t < 1/6) return p + (q - p) * 6 * t;
                if (t < 1/2) return q;
                if (t < 2/3) return p + (q - p) * (2/3 - t) * 6;
                return p;
            };
            const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
            const p = 2 * l - q;
            r = hue2rgb(p, q, h + 1/3);
            g = hue2rgb(p, q, h);
            b = hue2rgb(p, q, h - 1/3);
        }

        return {
            r: Math.round(r * 255),
            g: Math.round(g * 255),
            b: Math.round(b * 255)
        };
    }

    /**
     * Open formats menu for text and heading styles
     */
    openFormatsMenu() {
        const modal = document.createElement('div');
        modal.className = 'mintaro-formats-modal';
        modal.innerHTML = `
            <div class="mintaro-formats-dialog">
                <div class="mintaro-formats-header">
                    <h3>Format</h3>
                    <button class="mintaro-formats-close">✕</button>
                </div>
                <div class="mintaro-formats-content">
                    <div class="mintaro-formats-group">
                        <h4>Block Styles</h4>
                        <button class="mintaro-format-item" data-format="paragraph">Paragraph</button>
                        <button class="mintaro-format-item" data-format="h1">Heading 1</button>
                        <button class="mintaro-format-item" data-format="h2">Heading 2</button>
                        <button class="mintaro-format-item" data-format="h3">Heading 3</button>
                    </div>
                    <div class="mintaro-formats-group">
                        <h4>Font Family</h4>
                        <button class="mintaro-format-item" data-format="Arial">Arial</button>
                        <button class="mintaro-format-item" data-format="Georgia">Georgia</button>
                        <button class="mintaro-format-item" data-format="Courier New">Courier New</button>
                        <button class="mintaro-format-item" data-format="Verdana">Verdana</button>
                        <button class="mintaro-format-item" data-format="Times New Roman">Times New Roman</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const items = modal.querySelectorAll('.mintaro-format-item');
        items.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const format = item.dataset.format;

                // Handle block styles
                if (['paragraph', 'h1', 'h2', 'h3'].includes(format)) {
                    const tag = format === 'paragraph' ? 'p' : format;
                    document.execCommand('formatBlock', false, `<${tag}>`);
                } else {
                    // Handle font families
                    document.execCommand('fontName', false, format);
                }

                this.saveState();
                this.editor.focus();
                modal.remove();
            });
        });

        const closeBtn = modal.querySelector('.mintaro-formats-close');
        closeBtn.addEventListener('click', () => modal.remove());
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });
    }

    /**
     * Open emoji picker
     */
    openEmojiPicker() {
        const modal = document.createElement('div');
        modal.className = 'mintaro-emoji-modal';
        modal.innerHTML = `
            <div class="mintaro-emoji-dialog">
                <div class="mintaro-emoji-header">
                    <h3>Insert Emoji</h3>
                    <button class="mintaro-emoji-close">✕</button>
                </div>
                <div class="mintaro-emoji-tabs">
                    <button class="mintaro-emoji-tab active" data-category="smileys">😊</button>
                    <button class="mintaro-emoji-tab" data-category="hand">✋</button>
                    <button class="mintaro-emoji-tab" data-category="body">👋</button>
                    <button class="mintaro-emoji-tab" data-category="heart">❤️</button>
                    <button class="mintaro-emoji-tab" data-category="activity">⚽</button>
                    <button class="mintaro-emoji-tab" data-category="nature">🌿</button>
                    <button class="mintaro-emoji-tab" data-category="food">🍕</button>
                </div>
                <div class="mintaro-emoji-content" id="mintaro-emoji-content"></div>
            </div>
        `;

        document.body.appendChild(modal);

        const emojiMap = {
            smileys: ['😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂', '🙂', '🙃', '😉', '😊', '😇', '🥰', '😍', '🤩'],
            hand: ['👋', '🤚', '🖐️', '✋', '🖖', '👌', '🤌', '🤏', '✌️', '🤞', '🫰', '🤟', '🤘', '🤙'],
            body: ['💪', '🦵', '🦶', '👂', '👃', '🧠', '🦷', '🦴', '🫀', '🫁'],
            heart: ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤎', '🤍', '🩶'],
            activity: ['⚽', '🏀', '🏈', '⚾', '🥎', '🎾', '🏐', '🏉', '🥏', '🎳'],
            nature: ['🌿', '🍀', '☘️', '🌱', '🌲', '🌳', '🌴', '🌵', '🌾', '💐'],
            food: ['🍕', '🍔', '🍟', '🌭', '🍿', '🥓', '🍖', '🍗', '🥩', '🍝'],
            travel: ['✈️', '🚀', '🛸', '🚁', '🛩️', '💺', '🚂', '🚃', '🚄', '🚅']
        };

        const contentDiv = modal.querySelector('#mintaro-emoji-content');
        const tabs = modal.querySelectorAll('.mintaro-emoji-tab');

        const loadEmoji = (category) => {
            contentDiv.innerHTML = '';
            const emojis = emojiMap[category] || [];
            emojis.forEach(emoji => {
                const btn = document.createElement('button');
                btn.className = 'mintaro-emoji-item';
                btn.textContent = emoji;
                btn.addEventListener('click', () => {
                    this.insertEmoji(emoji);
                    modal.remove();
                });
                contentDiv.appendChild(btn);
            });
        };

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                loadEmoji(tab.dataset.category);
            });
        });

        // Load initial category
        loadEmoji('smileys');

        const closeBtn = modal.querySelector('.mintaro-emoji-close');
        closeBtn.addEventListener('click', () => modal.remove());
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });
    }

    /**
     * Insert emoji at cursor position
     */
    insertEmoji(emoji) {
        document.execCommand('insertText', false, emoji);
        this.saveState();
        this.editor.focus();
    }

    /**
     * Open image upload dialog
     */
    openImageUploadDialog() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                this.uploadImage(file);
            }
        });
        input.click();
    }

    /**
     * Upload image and insert into editor
     */
    uploadImage(file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.maxWidth = '100%';
            img.style.height = 'auto';
            img.style.cursor = 'pointer';
            img.style.border = '1px solid transparent';
            img.classList.add('mintaro-image');

            // Make image clickable to show resize options
            img.addEventListener('click', (evt) => {
                evt.stopPropagation();
                this.showImageResizeDialog(img);
            });

            const range = window.getSelection().getRangeAt(0);
            range.insertNode(img);

            this.saveState();
            this.editor.focus();
        };
        reader.readAsDataURL(file);
    }

    /**
     * Show image resize dialog
     */
    showImageResizeDialog(img) {
        const currentWidth = img.width || img.naturalWidth || 300;
        const currentHeight = img.height || img.naturalHeight || 200;
        const aspectRatio = img.naturalWidth / img.naturalHeight;

        const modal = document.createElement('div');
        modal.className = 'mintaro-formats-modal';
        modal.innerHTML = `
            <div class="mintaro-formats-dialog">
                <div class="mintaro-formats-header">
                    <h3>Resize Image</h3>
                    <button class="mintaro-formats-close">✕</button>
                </div>
                <div class="mintaro-formats-content">
                    <div class="mintaro-formats-group">
                        <label style="display:block; margin-bottom:8px;">
                            Width: <input type="number" id="img-width" value="${currentWidth}" min="50" max="2000"> px
                        </label>
                        <label style="display:block; margin-bottom:8px;">
                            Height: <input type="number" id="img-height" value="${currentHeight}" min="50" max="2000"> px
                        </label>
                        <label style="display:block; margin-bottom:8px;">
                            <input type="checkbox" id="img-aspect-ratio" checked> Keep aspect ratio
                        </label>
                    </div>
                </div>
                <div class="mintaro-formats-header" style="border-top: 1px solid #ddd; margin-top:10px; padding-top:10px;">
                    <button id="img-apply" class="mintaro-button-wide" style="margin-right:5px;">Apply</button>
                    <button id="img-cancel" class="mintaro-button-wide">Cancel</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const widthInput = modal.querySelector('#img-width');
        const heightInput = modal.querySelector('#img-height');
        const aspectRatioCheckbox = modal.querySelector('#img-aspect-ratio');
        const applyBtn = modal.querySelector('#img-apply');
        const cancelBtn = modal.querySelector('#img-cancel');
        const closeBtn = modal.querySelector('.mintaro-formats-close');

        // Handle width change
        widthInput.addEventListener('input', () => {
            if (aspectRatioCheckbox.checked) {
                heightInput.value = Math.round(widthInput.value / aspectRatio);
            }
        });

        // Handle height change
        heightInput.addEventListener('input', () => {
            if (aspectRatioCheckbox.checked) {
                widthInput.value = Math.round(heightInput.value * aspectRatio);
            }
        });

        applyBtn.addEventListener('click', () => {
            img.style.width = widthInput.value + 'px';
            img.style.height = heightInput.value + 'px';
            img.style.maxWidth = '100%';
            this.saveState();
            modal.remove();
        });

        cancelBtn.addEventListener('click', () => modal.remove());
        closeBtn.addEventListener('click', () => modal.remove());
    }

    /**
     * Open video embed dialog
     */
    openVideoEmbedDialog() {
        const url = prompt('Enter video URL (YouTube, Vimeo, or video file URL):');
        if (!url) return;

        let embedHTML = '';

        // YouTube detection
        if (url.includes('youtube.com') || url.includes('youtu.be')) {
            let videoId = '';
            if (url.includes('youtu.be/')) {
                videoId = url.split('youtu.be/')[1]?.split('?')[0];
            } else {
                videoId = new URLSearchParams(new URL(url).search).get('v');
            }
            if (videoId) {
                embedHTML = `<iframe width="560" height="315" src="https://www.youtube.com/embed/${videoId}" frameborder="0" allowfullscreen></iframe>`;
            }
        }
        // Vimeo detection
        else if (url.includes('vimeo.com')) {
            const videoId = url.split('vimeo.com/')[1]?.split('?')[0];
            if (videoId) {
                embedHTML = `<iframe src="https://player.vimeo.com/video/${videoId}" width="560" height="315" frameborder="0" allowfullscreen></iframe>`;
            }
        }
        // Direct video file
        else {
            embedHTML = `<video controls style="max-width: 100%; height: auto;"><source src="${url}"></video>`;
        }

        if (embedHTML) {
            this.insertHTML(embedHTML);
        }
    }

    /**
     * Open table insertion dialog
     */
    openTableDialog() {
        const rows = prompt('Number of rows:', '3');
        if (!rows) return;

        const cols = prompt('Number of columns:', '3');
        if (!cols) return;

        const numRows = parseInt(rows) || 3;
        const numCols = parseInt(cols) || 3;

        let html = '<table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse:collapse;"><tbody>';
        for (let i = 0; i < numRows; i++) {
            html += '<tr>';
            for (let j = 0; j < numCols; j++) {
                html += '<td><br></td>';
            }
            html += '</tr>';
        }
        html += '</tbody></table>';

        this.insertHTML(html);
    }

    /**
     * Insert HTML content
     */
    insertHTML(html) {
        document.execCommand('insertHTML', false, html);
        this.saveState();
        this.editor.focus();
    }

    /**
     * Open font family menu
     */
    openFontFamilyMenu() {
        const modal = document.createElement('div');
        modal.className = 'mintaro-formats-modal';
        modal.innerHTML = `
            <div class="mintaro-formats-dialog">
                <div class="mintaro-formats-header">
                    <h3>Font Family</h3>
                    <button class="mintaro-formats-close">✕</button>
                </div>
                <div class="mintaro-formats-content">
                    <div class="mintaro-formats-group">
                        <button class="mintaro-format-item" data-format="Arial">Arial</button>
                        <button class="mintaro-format-item" data-format="Georgia">Georgia</button>
                        <button class="mintaro-format-item" data-format="Courier New">Courier New</button>
                        <button class="mintaro-format-item" data-format="Verdana">Verdana</button>
                        <button class="mintaro-format-item" data-format="Times New Roman">Times New Roman</button>
                        <button class="mintaro-format-item" data-format="Trebuchet MS">Trebuchet MS</button>
                        <button class="mintaro-format-item" data-format="Comic Sans MS">Comic Sans MS</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const items = modal.querySelectorAll('.mintaro-format-item');
        items.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                document.execCommand('fontName', false, item.dataset.format);
                this.saveState();
                this.editor.focus();
                modal.remove();
            });
        });

        const closeBtn = modal.querySelector('.mintaro-formats-close');
        closeBtn.addEventListener('click', () => modal.remove());
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });
    }

    /**
     * Open font size menu
     */
    openFontSizeMenu() {
        const modal = document.createElement('div');
        modal.className = 'mintaro-formats-modal';
        modal.innerHTML = `
            <div class="mintaro-formats-dialog">
                <div class="mintaro-formats-header">
                    <h3>Font Size</h3>
                    <button class="mintaro-formats-close">✕</button>
                </div>
                <div class="mintaro-formats-content">
                    <div class="mintaro-formats-group">
                        <button class="mintaro-format-item" data-size="10px">10px</button>
                        <button class="mintaro-format-item" data-size="12px">12px</button>
                        <button class="mintaro-format-item" data-size="14px">14px</button>
                        <button class="mintaro-format-item" data-size="16px">16px</button>
                        <button class="mintaro-format-item" data-size="18px">18px</button>
                        <button class="mintaro-format-item" data-size="20px">20px</button>
                        <button class="mintaro-format-item" data-size="24px">24px</button>
                        <button class="mintaro-format-item" data-size="28px">28px</button>
                        <button class="mintaro-format-item" data-size="32px">32px</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const items = modal.querySelectorAll('.mintaro-format-item');
        items.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const size = item.dataset.size;

                const selection = window.getSelection();
                if (selection.rangeCount > 0) {
                    const range = selection.getRangeAt(0);
                    const span = document.createElement('span');
                    span.style.fontSize = size;
                    span.style.lineHeight = 'inherit';

                    try {
                        range.surroundContents(span);
                    } catch (e) {
                        // If surroundContents fails (complex selection), use insertNode
                        const contents = range.extractContents();
                        span.appendChild(contents);
                        range.insertNode(span);
                    }
                }

                this.saveState();
                this.editor.focus();
                modal.remove();
            });
        });

        const closeBtn = modal.querySelector('.mintaro-formats-close');
        closeBtn.addEventListener('click', () => modal.remove());
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });
    }

    /**
     * Open line height menu
     */
    openLineHeightMenu() {
        const modal = document.createElement('div');
        modal.className = 'mintaro-formats-modal';
        modal.innerHTML = `
            <div class="mintaro-formats-dialog">
                <div class="mintaro-formats-header">
                    <h3>Line Height</h3>
                    <button class="mintaro-formats-close">✕</button>
                </div>
                <div class="mintaro-formats-content">
                    <div class="mintaro-formats-group">
                        <button class="mintaro-format-item" data-height="1">Single (1)</button>
                        <button class="mintaro-format-item" data-height="1.15">1.15</button>
                        <button class="mintaro-format-item" data-height="1.5">1.5</button>
                        <button class="mintaro-format-item" data-height="1.75">1.75</button>
                        <button class="mintaro-format-item" data-height="2">Double (2)</button>
                        <button class="mintaro-format-item" data-height="2.5">2.5</button>
                        <button class="mintaro-format-item" data-height="3">Triple (3)</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const items = modal.querySelectorAll('.mintaro-format-item');
        items.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const height = item.dataset.height;
                const selection = window.getSelection();
                if (selection.rangeCount > 0) {
                    const range = selection.getRangeAt(0);
                    const span = document.createElement('span');
                    span.style.lineHeight = height;
                    range.surroundContents(span);
                }
                this.saveState();
                this.editor.focus();
                modal.remove();
            });
        });

        const closeBtn = modal.querySelector('.mintaro-formats-close');
        closeBtn.addEventListener('click', () => modal.remove());
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });
    }

    /**
     * Insert checklist
     */
    insertChecklist() {
        const html = `<ul style="list-style-type: none; padding-left: 0;">
            <li><input type="checkbox"> Item 1</li>
            <li><input type="checkbox"> Item 2</li>
            <li><input type="checkbox"> Item 3</li>
        </ul>`;
        this.insertHTML(html);
    }

    /**
     * Open link dialog
     */
    openLinkDialog() {
        const url = prompt('Enter URL:');
        if (!url) return;

        const text = prompt('Link text (optional):') || url;
        document.execCommand('createLink', false, url);
        this.saveState();
        this.editor.focus();
    }

    /**
     * Open image dialog with link or upload options
     */
    openImageDialog() {
        const modal = document.createElement('div');
        modal.className = 'mintaro-formats-modal';
        modal.innerHTML = `
            <div class="mintaro-formats-dialog">
                <div class="mintaro-formats-header">
                    <h3>Insert Image</h3>
                    <button class="mintaro-formats-close">✕</button>
                </div>
                <div class="mintaro-formats-content">
                    <div class="mintaro-formats-group">
                        <button class="mintaro-format-item" id="image-link-opt">From URL</button>
                        <button class="mintaro-format-item" id="image-upload-opt">Upload File</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const linkOpt = modal.querySelector('#image-link-opt');
        const uploadOpt = modal.querySelector('#image-upload-opt');
        const closeBtn = modal.querySelector('.mintaro-formats-close');

        linkOpt.addEventListener('click', (e) => {
            e.preventDefault();
            modal.remove();
            const url = prompt('Enter image URL:');
            if (!url) return;
            const alt = prompt('Image description (alt text):') || '';

            // Create image with click handler for resize
            const img = document.createElement('img');
            img.src = url;
            img.alt = alt;
            img.style.maxWidth = '100%';
            img.style.height = 'auto';
            img.style.cursor = 'pointer';
            img.style.border = '1px solid transparent';
            img.classList.add('mintaro-image');

            img.addEventListener('click', (evt) => {
                evt.stopPropagation();
                this.showImageResizeDialog(img);
            });

            const range = window.getSelection().getRangeAt(0);
            range.insertNode(img);
            this.saveState();
            this.editor.focus();
        });

        uploadOpt.addEventListener('click', (e) => {
            e.preventDefault();
            modal.remove();
            this.openImageUploadDialog();
        });

        closeBtn.addEventListener('click', () => modal.remove());
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });
    }

    /**
     * Open HTML source editor
     */
    openHTMLSourceEditor() {
        const currentHTML = this.getHTML();
        const modal = document.createElement('div');
        modal.className = 'mintaro-html-modal';
        modal.innerHTML = `
            <div class="mintaro-html-dialog">
                <div class="mintaro-html-header">
                    <h3>HTML Source Code</h3>
                    <button class="mintaro-html-close">✕</button>
                </div>
                <textarea class="mintaro-html-textarea"></textarea>
                <div class="mintaro-html-footer">
                    <button id="mintaro-html-cancel" class="mintaro-button-wide mintaro-btn-secondary">Cancel</button>
                    <button id="mintaro-html-apply" class="mintaro-button-wide mintaro-btn-primary">Apply Changes</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const textarea = modal.querySelector('.mintaro-html-textarea');
        const applyBtn = modal.querySelector('#mintaro-html-apply');
        const cancelBtn = modal.querySelector('#mintaro-html-cancel');
        const closeBtn = modal.querySelector('.mintaro-html-close');

        // Set HTML content safely (avoid template literal XSS issues)
        textarea.value = currentHTML;

        // Format HTML for readability
        textarea.value = this.formatHTML(textarea.value);

        applyBtn.addEventListener('click', () => {
            this.setContent(textarea.value);
            this.saveState();
            modal.remove();
            this.editor.focus();
        });

        cancelBtn.addEventListener('click', () => modal.remove());
        closeBtn.addEventListener('click', () => modal.remove());

        // Close on escape key
        modal.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') modal.remove();
        });

        // Focus textarea
        setTimeout(() => textarea.focus(), 100);
    }

    /**
     * Format HTML for readability
     */
    formatHTML(html) {
        // Simple HTML formatter
        let formatted = '';
        let indent = 0;
        const tab = '  ';

        // Split by tags
        html = html.replace(/>\s*</g, '><');
        const tokens = html.split(/(<[^>]+>)/g);

        tokens.forEach(token => {
            if (!token.trim()) return;

            if (token.match(/^<\/\w/)) {
                // Closing tag
                indent = Math.max(0, indent - 1);
            }

            if (token.startsWith('<')) {
                formatted += tab.repeat(indent) + token + '\n';
            } else {
                formatted += tab.repeat(indent) + token.trim() + '\n';
            }

            if (token.match(/^<\w[^>]*[^\/]>/) && !token.match(/^<(br|hr|img|input|meta|link)/i)) {
                // Opening tag (not self-closing)
                indent++;
            }
        });

        return formatted.trim();
    }

    /**
     * Toggle fullscreen mode
     */
    toggleFullscreen() {
        const container = this.container;
        if (!document.fullscreenElement) {
            // Try different fullscreen APIs for browser compatibility
            const requestFullscreen = container.requestFullscreen ||
                                     container.webkitRequestFullscreen ||
                                     container.mozRequestFullScreen ||
                                     container.msRequestFullscreen;

            if (requestFullscreen) {
                requestFullscreen.call(container).catch(err => {
                    console.error('Fullscreen request failed:', err);
                });
            } else {
                // Fallback: maximize editor without true fullscreen
                container.style.position = 'fixed';
                container.style.top = '0';
                container.style.left = '0';
                container.style.width = '100vw';
                container.style.height = '100vh';
                container.style.zIndex = '9999';
                container.style.maxWidth = '100%';
                document.body.style.overflow = 'hidden';
            }
        } else {
            // Exit fullscreen
            const exitFullscreen = document.exitFullscreen ||
                                  document.webkitExitFullscreen ||
                                  document.mozCancelFullScreen ||
                                  document.msExitFullscreen;

            if (exitFullscreen) {
                exitFullscreen.call(document);
            } else {
                // Reset fallback styles
                container.style.position = '';
                container.style.top = '';
                container.style.left = '';
                container.style.width = '';
                container.style.height = '';
                container.style.zIndex = '';
                container.style.maxWidth = '';
                document.body.style.overflow = '';
            }
        }
    }

    /**
     * Show help dialog
     */
    showHelp() {
        const helpContent = `
<strong>Mintaro Editor Keyboard Shortcuts:</strong><br>
<ul style="list-style-position: inside; margin: 10px 0;">
<li><strong>Ctrl+B:</strong> Bold</li>
<li><strong>Ctrl+I:</strong> Italic</li>
<li><strong>Ctrl+U:</strong> Underline</li>
<li><strong>Ctrl+Z:</strong> Undo</li>
<li><strong>Ctrl+Y:</strong> Redo</li>
<li><strong>Ctrl+A:</strong> Select All</li>
</ul>
<strong>Formatting Features:</strong><br>
<ul style="list-style-position: inside; margin: 10px 0;">
<li>Bold, Italic, Underline, Strikethrough</li>
<li>Superscript and Subscript</li>
<li>Text and Background Colors</li>
<li>Font Family, Font Size, Line Height</li>
<li>Headings, Paragraphs, Blockquotes</li>
<li>Ordered and Unordered Lists</li>
<li>Checklists, Code Blocks</li>
<li>Text Alignment (Left, Center, Right, Justify)</li>
<li>Indent and Outdent</li>
</ul>
<strong>Content Elements:</strong><br>
<ul style="list-style-position: inside; margin: 10px 0;">
<li>Insert Links, Images, Videos</li>
<li>Embed YouTube/Vimeo videos</li>
<li>Insert Tables</li>
<li>Upload Images from your computer</li>
<li>Insert Emojis</li>
<li>HTML Source Editor</li>
</ul>
        `;

        alert(helpContent);
    }
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Mintaro;
}
