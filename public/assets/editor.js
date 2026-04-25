const langToMode = {
    php:        'application/x-httpd-php',
    javascript: 'javascript',
    typescript: 'javascript',
    python:     'python',
    html:       'htmlmixed',
    css:        'css',
    sql:        'text/x-sql',
    bash:       'shell',
    json:       'javascript',
    xml:        'xml',
    go:         'go',
    rust:       null,
    java:       'text/x-java',
    c:          'text/x-csrc',
    cpp:        'text/x-c++src',
    markdown:   'markdown',
    plaintext:  null,
};

const detectPatterns = [
    { lang: 'php',        re: /^\s*<\?php/m },
    { lang: 'python',     re: /^\s*(def |import |from .+ import|if __name__)/m },
    { lang: 'typescript', re: /^\s*(interface |type |: string|: number|: boolean)/m },
    { lang: 'javascript', re: /^\s*(const |let |var |function |=>|require\(|import )/m },
    { lang: 'sql',        re: /^\s*(SELECT|INSERT|UPDATE|DELETE|CREATE TABLE)/im },
    { lang: 'html',       re: /^\s*<!DOCTYPE|<html/i },
    { lang: 'css',        re: /^\s*[\w.#*:@][^{]*\{[^}]*\}/s },
    { lang: 'bash',       re: /^\s*(#!\/bin\/|echo |export |apt-get|sudo )/m },
    { lang: 'go',         re: /^\s*(package |func |import ")/m },
    { lang: 'rust',       re: /^\s*(fn |use |let mut |impl |pub fn)/m },
    { lang: 'java',       re: /^\s*(public class|import java\.|@Override)/m },
    { lang: 'cpp',        re: /^\s*(#include\s*<|std::|cout\s*<<)/m },
    { lang: 'c',          re: /^\s*(#include\s*<stdio|int main\s*\()/m },
    { lang: 'json',       re: /^\s*[\[{]/ },
    { lang: 'markdown',   re: /^#{1,6} |^\*\*|^- \[/m },
];

const editor = CodeMirror.fromTextArea(document.getElementById('editor'), {
    theme:          'dracula',
    lineNumbers:    true,
    lineWrapping:   true,
    mode:           null,
    autofocus:      true,
    tabSize:        4,
    indentWithTabs: false,
});

document.getElementById('paste-form').addEventListener('submit', () => {
    editor.save();
});

document.getElementById('language-select').addEventListener('change', function () {
    editor.setOption('mode', langToMode[this.value] ?? null);
});

function detectLanguage() {
    const code = editor.getValue().slice(0, 500);
    for (const { lang, re } of detectPatterns) {
        if (re.test(code)) {
            document.getElementById('language-select').value = lang;
            try {
                editor.setOption('mode', langToMode[lang] ?? null);
            } catch (err) {
                console.error('Failed to set mode for ' + lang + ':', err);
                editor.setOption('mode', null);
            }
            return;
        }
    }
}

document.getElementById('detect-btn').addEventListener('click', detectLanguage);

let detectTimeout;
editor.on('change', () => {
    const content = editor.getValue();
    if (content.length < 10) return;

    clearTimeout(detectTimeout);
    detectTimeout = setTimeout(detectLanguage, 500);
});
