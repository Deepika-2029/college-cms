document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.code-editor').forEach(e => e.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('editor-' + btn.dataset.target).classList.add('active');
    });
});

// Tab key support in editors
document.querySelectorAll('.code-editor').forEach(editor => {
    editor.addEventListener('keydown', e => {
        if (e.key === 'Tab') {
            e.preventDefault();
            const start = editor.selectionStart;
            const end   = editor.selectionEnd;
            editor.value = editor.value.substring(0, start) + '  ' + editor.value.substring(end);
            editor.selectionStart = editor.selectionEnd = start + 2;
        }
    });
});
