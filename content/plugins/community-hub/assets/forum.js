function insertMd(before, after) {
    var textareas = document.querySelectorAll('.forum-textarea');
    var textarea = null;
    for (var i = 0; i < textareas.length; i++) {
        if (textareas[i] === document.activeElement || textareas.length === 1) {
            textarea = textareas[i];
            break;
        }
    }
    if (!textarea) {
        textarea = document.getElementById('replyBody') ||
                   document.getElementById('topicBody') ||
                   document.getElementById('editBody');
    }
    if (!textarea) return;
    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    var text = textarea.value;
    var selected = text.substring(start, end);
    var replacement = before + selected + after;
    textarea.value = text.substring(0, start) + replacement + text.substring(end);
    var cursorPos;
    if (selected.length > 0) {
        cursorPos = start + replacement.length;
    } else {
        cursorPos = start + before.length;
    }
    textarea.selectionStart = cursorPos;
    textarea.selectionEnd = cursorPos;
    textarea.focus();
}
