// Markdown Editor Functions
function insertMarkdown(before, after) {
    const textarea = document.getElementById('description');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = textarea.value.substring(start, end);
    const newText = before + selectedText + after;
    
    textarea.value = textarea.value.substring(0, start) + newText + textarea.value.substring(end);
    
    // Set cursor position
    const newCursorPos = start + before.length + selectedText.length;
    textarea.setSelectionRange(newCursorPos, newCursorPos);
    textarea.focus();
}

function insertImageUrl() {
    const url = prompt('Enter image URL (CDN recommended):', 'https://cdn.example.com/image.jpg');
    if (url && url.trim()) {
        const alt = prompt('Enter alt text for the image:', 'Product image');
        const markdown = `![${alt || 'Image'}](${url})`;
        insertMarkdown(markdown, '');
    }
}

function insertLink() {
    const url = prompt('Enter URL:', 'https://example.com');
    if (url && url.trim()) {
        const text = prompt('Enter link text:', 'Link');
        const markdown = `[${text || 'Link'}](${url})`;
        insertMarkdown(markdown, '');
    }
}

function previewMarkdown() {
    const content = document.getElementById('description').value;
    if (!content.trim()) {
        alert('No content to preview');
        return;
    }
    
    // Create preview window
    const previewWindow = window.open('', '_blank', 'width=800,height=600,scrollbars=yes');
    previewWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Markdown Preview</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; line-height: 1.6; }
                h1, h2, h3, h4, h5, h6 { margin-top: 24px; margin-bottom: 16px; font-weight: 600; }
                h1 { font-size: 2em; border-bottom: 1px solid #eaecef; padding-bottom: 0.3em; }
                h2 { font-size: 1.5em; border-bottom: 1px solid #eaecef; padding-bottom: 0.3em; }
                h3 { font-size: 1.25em; }
                p { margin-bottom: 16px; }
                ul, ol { margin-bottom: 16px; padding-left: 30px; }
                li { margin-bottom: 4px; }
                blockquote { margin: 0 0 16px; padding: 0 1em; color: #6a737d; border-left: 0.25em solid #dfe2e5; }
                code { padding: 0.2em 0.4em; margin: 0; font-size: 85%; background-color: rgba(27,31,35,0.05); border-radius: 3px; }
                pre { padding: 16px; overflow: auto; font-size: 85%; line-height: 1.45; background-color: #f6f8fa; border-radius: 3px; }
                img { max-width: 100%; height: auto; }
                a { color: #0366d6; text-decoration: none; }
                a:hover { text-decoration: underline; }
            </style>
        </head>
        <body>
            <div id="preview-content"></div>
            <script src="https://cdn.jsdelivr.net/npm/marked@4.3.0/marked.min.js"></script>
            <script>
                const content = \`${content.replace(/`/g, '\\`')}\`;
                document.getElementById('preview-content').innerHTML = marked.parse(content);
            </script>
        </body>
        </html>
    `);
}

// Auto-resize textarea
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('description');
    if (textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    }
});
