<div class="forum-container">
    <div class="forum-breadcrumb">
        <a href="/community">Community</a>
        <span class="breadcrumb-sep">&rsaquo;</span>
        <?php if (!empty($topic)): ?>
            <a href="/community/topic/<?php echo escape($topic['slug']); ?>"><?php echo escape($topic['title']); ?></a>
            <span class="breadcrumb-sep">&rsaquo;</span>
        <?php endif; ?>
        <span>Edit <?php echo ($postType === 'topic') ? 'Topic' : 'Reply'; ?></span>
    </div>

    <div class="forum-header">
        <h1>Edit <?php echo ($postType === 'topic') ? 'Topic' : 'Reply'; ?></h1>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="forum-alert forum-alert-error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo escape($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="/community/edit/<?php echo escape($postType); ?>/<?php echo (int)$post['id']; ?>" class="forum-form">
        <?php echo csrfField(); ?>
        <input type="hidden" name="type" value="<?php echo escape($postType); ?>">

        <?php if ($postType === 'topic'): ?>
            <div class="forum-form-group">
                <label for="editTitle">Title</label>
                <input type="text" name="title" id="editTitle" class="forum-input" value="<?php echo escape($post['title']); ?>" maxlength="255" required>
            </div>
        <?php endif; ?>

        <div class="forum-form-group">
            <label for="editBody">Body</label>
            <div class="markdown-toolbar">
                <button type="button" class="toolbar-btn" onclick="insertMd('**', '**')" title="Bold"><b>B</b></button>
                <button type="button" class="toolbar-btn" onclick="insertMd('*', '*')" title="Italic"><i>I</i></button>
                <button type="button" class="toolbar-btn" onclick="insertMd('[', '](url)')" title="Link">Link</button>
                <button type="button" class="toolbar-btn" onclick="insertMd('`', '`')" title="Code">Code</button>
                <button type="button" class="toolbar-btn" onclick="insertMd('\n- ', '')" title="List">List</button>
            </div>
            <textarea name="body" id="editBody" class="forum-textarea" rows="10" required><?php echo escape($post['body']); ?></textarea>
        </div>

        <div class="forum-form-group">
            <label for="editReason">Edit Reason (optional)</label>
            <input type="text" name="edit_reason" id="editReason" class="forum-input" placeholder="Briefly describe what you changed" maxlength="255">
        </div>

        <div class="forum-form-actions">
            <?php if ($postType === 'topic' && !empty($topic['slug'])): ?>
                <a href="/community/topic/<?php echo escape($topic['slug']); ?>" class="forum-btn">Cancel</a>
            <?php else: ?>
                <a href="javascript:history.back()" class="forum-btn">Cancel</a>
            <?php endif; ?>
            <button type="submit" class="forum-btn forum-btn-primary">Save Changes</button>
        </div>
    </form>
</div>

<script src="/plugins/community-hub/assets/forum.js"></script>
