<div class="forum-container">
    <div class="forum-breadcrumb">
        <a href="/community">Community</a>
        <span class="breadcrumb-sep">&rsaquo;</span>
        <span>New Topic</span>
    </div>

    <div class="forum-header">
        <h1>Create New Topic</h1>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="forum-alert forum-alert-error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo escape($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="/community/topic/create" class="forum-form">
        <?php echo csrfField(); ?>
        <input type="hidden" name="_form_token" value="<?php echo escape($formToken ?? ''); ?>">
        <div style="display:none;">
            <input type="text" name="website_url" value="" tabindex="-1" autocomplete="off">
        </div>

        <div class="forum-form-group">
            <label for="topicCategory">Category</label>
            <select name="category_id" id="topicCategory" class="forum-select" required>
                <option value="">Select a category</option>
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo (int)$cat['id']; ?>"<?php echo ($selectedCategory && $selectedCategory == $cat['id']) ? ' selected' : ''; ?>>
                            <?php echo escape($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="forum-form-group">
            <label for="topicTitle">Title</label>
            <input type="text" name="title" id="topicTitle" class="forum-input" value="<?php echo escape($old['title'] ?? ''); ?>" placeholder="Enter a descriptive title" maxlength="255" required>
        </div>

        <div class="forum-form-group">
            <label for="topicBody">Body</label>
            <div class="markdown-toolbar">
                <button type="button" class="toolbar-btn" onclick="insertMd('**', '**')" title="Bold"><b>B</b></button>
                <button type="button" class="toolbar-btn" onclick="insertMd('*', '*')" title="Italic"><i>I</i></button>
                <button type="button" class="toolbar-btn" onclick="insertMd('[', '](url)')" title="Link">Link</button>
                <button type="button" class="toolbar-btn" onclick="insertMd('`', '`')" title="Code">Code</button>
                <button type="button" class="toolbar-btn" onclick="insertMd('\n- ', '')" title="List">List</button>
            </div>
            <textarea name="body" id="topicBody" class="forum-textarea" rows="10" placeholder="Write your topic content... Markdown is supported." required><?php echo escape($old['body'] ?? ''); ?></textarea>
        </div>

        <div class="forum-form-actions">
            <a href="/community" class="forum-btn">Cancel</a>
            <button type="submit" class="forum-btn forum-btn-primary">Create Topic</button>
        </div>
    </form>
</div>

<script src="/plugins/community-hub/assets/forum.js"></script>
