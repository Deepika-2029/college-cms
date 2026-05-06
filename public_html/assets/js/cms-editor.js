/**
 * CMS Code Editor — Self-hosted, zero-dependency syntax highlighter + editor
 * Replaces CodeMirror CDN. Supports: HTML, CSS, JavaScript, plain text.
 * API is intentionally compatible with CodeMirror 5's core subset.
 *
 * FILE: /assets/js/cms-editor.js
 */
(function (global) {
'use strict';

/* ── Tokenizers ─────────────────────────────────────────────────── */
const Tokenizers = {
  htmlmixed(code) {
    const tokens = [];
    let i = 0;
    const len = code.length;

    while (i < len) {
      // Comment
      if (code.startsWith('<!--', i)) {
        const end = code.indexOf('-->', i + 4);
        const e = end === -1 ? len : end + 3;
        tokens.push({ type: 'comment', val: code.slice(i, e) });
        i = e; continue;
      }
      // Script block
      if (code.startsWith('<script', i)) {
        const closeTag = code.indexOf('</script', i);
        const tagEnd = code.indexOf('>', i);
        if (tagEnd !== -1) {
          tokens.push({ type: 'tag', val: code.slice(i, tagEnd + 1) });
          i = tagEnd + 1;
          if (closeTag !== -1 && closeTag > i) {
            const jsCode = code.slice(i, closeTag);
            Tokenizers.javascript(jsCode).forEach(t => tokens.push(t));
            i = closeTag;
          }
          continue;
        }
      }
      // Style block
      if (code.startsWith('<style', i)) {
        const closeTag2 = code.indexOf('</style', i);
        const tEnd = code.indexOf('>', i);
        if (tEnd !== -1) {
          tokens.push({ type: 'tag', val: code.slice(i, tEnd + 1) });
          i = tEnd + 1;
          if (closeTag2 !== -1 && closeTag2 > i) {
            const cssCode = code.slice(i, closeTag2);
            Tokenizers.css(cssCode).forEach(t => tokens.push(t));
            i = closeTag2;
          }
          continue;
        }
      }
      // Tag
      if (code[i] === '<') {
        const end2 = code.indexOf('>', i);
        const e2 = end2 === -1 ? i + 1 : end2 + 1;
        const tagStr = code.slice(i, e2);
        // Tokenize tag internals: tag-name, attributes, values
        const tagToks = _tokenizeTag(tagStr);
        tagToks.forEach(t => tokens.push(t));
        i = e2; continue;
      }
      // Entity
      if (code[i] === '&') {
        const sc = code.indexOf(';', i);
        if (sc !== -1 && sc - i <= 10) {
          tokens.push({ type: 'entity', val: code.slice(i, sc + 1) });
          i = sc + 1; continue;
        }
      }
      // Plain text — collect until next tag/entity
      let j = i + 1;
      while (j < len && code[j] !== '<' && code[j] !== '&') j++;
      tokens.push({ type: 'text', val: code.slice(i, j) });
      i = j;
    }
    return tokens;
  },

  css(code) {
    const tokens = [];
    let i = 0;
    const len = code.length;
    const kwds = new Set(['important','inherit','initial','unset','auto','none','normal','bold','italic','flex','grid','block','inline','absolute','relative','fixed','sticky']);
    while (i < len) {
      if (code.startsWith('/*', i)) {
        const e = code.indexOf('*/', i + 2);
        const end = e === -1 ? len : e + 2;
        tokens.push({ type: 'comment', val: code.slice(i, end) }); i = end; continue;
      }
      if (code[i] === '"' || code[i] === "'") {
        const q = code[i]; let j = i + 1;
        while (j < len && code[j] !== q) { if (code[j] === '\\') j++; j++; }
        tokens.push({ type: 'string', val: code.slice(i, j + 1) }); i = j + 1; continue;
      }
      if (code[i] === '#' && /[0-9a-fA-F]/.test(code[i + 1] || '')) {
        let j = i + 1;
        while (j < len && /[0-9a-fA-F]/.test(code[j])) j++;
        tokens.push({ type: 'atom', val: code.slice(i, j) }); i = j; continue;
      }
      if (code[i] === '@') {
        let j = i;
        while (j < len && /[\w-]/.test(code[j])) j++;
        tokens.push({ type: 'def', val: code.slice(i, j) }); i = j; continue;
      }
      if (/[a-zA-Z_-]/.test(code[i])) {
        let j = i;
        while (j < len && /[\w-]/.test(code[j])) j++;
        const word = code.slice(i, j);
        const next = code[j];
        if (next === '(') tokens.push({ type: 'builtin', val: word });
        else if (next === ':' || (code[j - 1] === '{' || (i > 0 && /[{;]\s*$/.test(code.slice(0, i))))) tokens.push({ type: 'property', val: word });
        else if (kwds.has(word)) tokens.push({ type: 'keyword', val: word });
        else tokens.push({ type: 'text', val: word });
        i = j; continue;
      }
      if (/\d/.test(code[i]) || (code[i] === '-' && /\d/.test(code[i + 1] || ''))) {
        let j = i;
        if (code[j] === '-') j++;
        while (j < len && /[\d.]/.test(code[j])) j++;
        while (j < len && /[a-z%]/.test(code[j])) j++;
        tokens.push({ type: 'number', val: code.slice(i, j) }); i = j; continue;
      }
      tokens.push({ type: 'punctuation', val: code[i] }); i++;
    }
    return tokens;
  },

  javascript(code) {
    const tokens = [];
    let i = 0;
    const len = code.length;
    const KW = new Set(['break','case','catch','class','const','continue','debugger','default','delete','do','else','export','extends','finally','for','function','if','import','in','instanceof','let','new','of','return','static','super','switch','this','throw','try','typeof','var','void','while','with','yield','async','await','null','undefined','true','false','NaN','Infinity']);
    const BI = new Set(['console','document','window','Math','Array','Object','String','Number','Boolean','Date','RegExp','Error','JSON','Promise','Set','Map','fetch','setTimeout','setInterval','clearTimeout','clearInterval','parseInt','parseFloat','isNaN','isFinite','encodeURIComponent','decodeURIComponent','alert','confirm','prompt']);
    while (i < len) {
      if (code.startsWith('//', i)) {
        let j = i;
        while (j < len && code[j] !== '\n') j++;
        tokens.push({ type: 'comment', val: code.slice(i, j) }); i = j; continue;
      }
      if (code.startsWith('/*', i)) {
        const e = code.indexOf('*/', i + 2);
        const end = e === -1 ? len : e + 2;
        tokens.push({ type: 'comment', val: code.slice(i, end) }); i = end; continue;
      }
      if (code[i] === '`') {
        let j = i + 1;
        while (j < len) {
          if (code[j] === '\\') { j += 2; continue; }
          if (code[j] === '`') { j++; break; }
          j++;
        }
        tokens.push({ type: 'string', val: code.slice(i, j) }); i = j; continue;
      }
      if (code[i] === '"' || code[i] === "'") {
        const q = code[i]; let j = i + 1;
        while (j < len && code[j] !== q && code[j] !== '\n') { if (code[j] === '\\') j++; j++; }
        tokens.push({ type: 'string', val: code.slice(i, j + 1) }); i = j + 1; continue;
      }
      if (/[a-zA-Z_$]/.test(code[i])) {
        let j = i;
        while (j < len && /[\w$]/.test(code[j])) j++;
        const w = code.slice(i, j);
        if (KW.has(w)) tokens.push({ type: 'keyword', val: w });
        else if (BI.has(w)) tokens.push({ type: 'builtin', val: w });
        else if (code[j] === '(') tokens.push({ type: 'def', val: w });
        else tokens.push({ type: 'variable', val: w });
        i = j; continue;
      }
      if (/\d/.test(code[i]) || (code[i] === '.' && /\d/.test(code[i + 1] || ''))) {
        let j = i;
        while (j < len && /[\d.xXa-fA-FbBoOnN_]/.test(code[j])) j++;
        tokens.push({ type: 'number', val: code.slice(i, j) }); i = j; continue;
      }
      tokens.push({ type: 'punctuation', val: code[i] }); i++;
    }
    return tokens;
  },
};

function _tokenizeTag(tagStr) {
  const tokens = [];
  let i = 0;
  const len = tagStr.length;
  // Opening < and possible /
  if (tagStr[i] === '<') { tokens.push({ type: 'bracket', val: '<' }); i++; }
  if (tagStr[i] === '/') { tokens.push({ type: 'bracket', val: '/' }); i++; }
  // Tag name
  let j = i;
  while (j < len && /[\w-]/.test(tagStr[j])) j++;
  if (j > i) { tokens.push({ type: 'tag-name', val: tagStr.slice(i, j) }); i = j; }
  // Attributes
  while (i < len && tagStr[i] !== '>') {
    if (tagStr[i] === ' ' || tagStr[i] === '\n' || tagStr[i] === '\t') {
      tokens.push({ type: 'text', val: tagStr[i] }); i++; continue;
    }
    if (tagStr[i] === '/' && tagStr[i + 1] === '>') {
      tokens.push({ type: 'bracket', val: '/>' }); i += 2; break;
    }
    if (/[\w-]/.test(tagStr[i])) {
      let k = i;
      while (k < len && /[\w-]/.test(tagStr[k])) k++;
      tokens.push({ type: 'attr', val: tagStr.slice(i, k) }); i = k;
      if (tagStr[i] === '=') {
        tokens.push({ type: 'operator', val: '=' }); i++;
        if (tagStr[i] === '"' || tagStr[i] === "'") {
          const q = tagStr[i]; let m = i + 1;
          while (m < len && tagStr[m] !== q) m++;
          tokens.push({ type: 'string', val: tagStr.slice(i, m + 1) }); i = m + 1;
        }
      }
      continue;
    }
    tokens.push({ type: 'text', val: tagStr[i] }); i++;
  }
  if (i < len && tagStr[i] === '>') { tokens.push({ type: 'bracket', val: '>' }); }
  return tokens;
}

/* ── Token → CSS class map ──────────────────────────────────────── */
const TYPE_CLASS = {
  'comment':    'cme-comment',
  'tag':        'cme-tag',
  'tag-name':   'cme-tag-name',
  'bracket':    'cme-bracket',
  'attr':       'cme-attr',
  'string':     'cme-string',
  'entity':     'cme-entity',
  'keyword':    'cme-keyword',
  'builtin':    'cme-builtin',
  'def':        'cme-def',
  'number':     'cme-number',
  'property':   'cme-property',
  'atom':       'cme-atom',
  'variable':   'cme-variable',
  'operator':   'cme-operator',
  'punctuation':'cme-punctuation',
  'text':       '',
};

function highlight(code, mode) {
  const tokenizer = Tokenizers[mode] || Tokenizers.htmlmixed;
  const tokens = tokenizer(code);
  return tokens.map(t => {
    const cls = TYPE_CLASS[t.type] || '';
    const safe = t.val.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    return cls ? `<span class="${cls}">${safe}</span>` : safe;
  }).join('');
}

/* ── Main Editor class ──────────────────────────────────────────── */
function CMSEditor(textarea, options) {
  options = options || {};
  const mode      = options.mode || 'htmlmixed';
  const lineNums  = options.lineNumbers !== false;
  const readOnly  = !!options.readOnly;
  const theme     = options.theme || 'monokai';

  // Build DOM
  const wrapper = document.createElement('div');
  wrapper.className = 'cme-wrap cme-theme-' + theme;
  if (readOnly) wrapper.classList.add('cme-readonly');

  let html = '<div class="cme-inner">';
  if (lineNums) html += '<div class="cme-gutter" aria-hidden="true"></div>';
  html += '<div class="cme-scroll"><div class="cme-content" spellcheck="false" autocorrect="off" autocapitalize="off" aria-multiline="true"';
  if (!readOnly) html += ' contenteditable="true"';
  html += '></div></div></div>';
  wrapper.innerHTML = html;

  textarea.style.display = 'none';
  textarea.parentNode.insertBefore(wrapper, textarea.nextSibling);

  const content  = wrapper.querySelector('.cme-content');
  const gutter   = wrapper.querySelector('.cme-gutter');
  const scroll   = wrapper.querySelector('.cme-scroll');

  let _value     = textarea.value;
  let _onChange  = null;
  let _ignoreInput = false;

  /* ── Render ──────────────────────────────────────────────────── */
  function _render(val) {
    const lines = val.split('\n');
    const highlighted = lines.map(line => '<div class="cme-line">' + highlight(line, mode) + '\u200b</div>').join('');
    content.innerHTML = highlighted;
    if (gutter) {
      gutter.innerHTML = lines.map((_, i) => `<div class="cme-ln">${i + 1}</div>`).join('');
    }
  }

  /* ── Sync textarea ────────────────────────────────────────────── */
  function _sync() {
    // Extract plain text from content
    const lines = content.querySelectorAll('.cme-line');
    let text = '';
    lines.forEach((l, i) => {
      text += (l.innerText || l.textContent).replace(/\u200b/g, '');
      if (i < lines.length - 1) text += '\n';
    });
    textarea.value = text;
    _value = text;
    if (_onChange) _onChange(text);
  }

  /* ── Handle input ─────────────────────────────────────────────── */
  let _debounce;
  content.addEventListener('input', function () {
    if (_ignoreInput) return;
    clearTimeout(_debounce);
    _debounce = setTimeout(() => {
      const sel = _saveSel();
      _sync();
      _render(_value);
      _restoreSel(sel);
    }, 80);
  });

  /* ── Tab key ─────────────────────────────────────────────────── */
  content.addEventListener('keydown', function (e) {
    if (e.key === 'Tab') {
      e.preventDefault();
      document.execCommand('insertText', false, '  ');
    }
    if ((e.ctrlKey || e.metaKey) && e.key === 'a') {
      e.preventDefault();
      const range = document.createRange();
      range.selectNodeContents(content);
      const sel2 = window.getSelection();
      sel2.removeAllRanges();
      sel2.addRange(range);
    }
  });

  /* ── Save/restore caret position ───────────────────────────────── */
  function _saveSel() {
    const sel = window.getSelection();
    if (!sel.rangeCount) return null;
    const range = sel.getRangeAt(0);
    const pre = range.cloneRange();
    pre.selectNodeContents(content);
    pre.setEnd(range.startContainer, range.startOffset);
    return { start: pre.toString().length, end: pre.toString().length + range.toString().length };
  }

  function _restoreSel(saved) {
    if (!saved) return;
    try {
      const sel = window.getSelection();
      const range = _rangeAt(content, saved.start, saved.end);
      if (range) { sel.removeAllRanges(); sel.addRange(range); }
    } catch (e) {}
  }

  function _rangeAt(root, start, end) {
    let node, idx = 0;
    const iter = document.createNodeIterator(root, NodeFilter.SHOW_TEXT);
    const range = document.createRange();
    while ((node = iter.nextNode())) {
      const len = node.nodeValue.length;
      if (idx + len >= start && !range.startContainer.nodeType) {
        range.setStart(node, start - idx);
      }
      if (idx + len >= end) {
        range.setEnd(node, end - idx);
        return range;
      }
      idx += len;
    }
    return null;
  }

  /* ── Public API (CodeMirror 5 compatible) ──────────────────────── */
  const api = {
    getValue() { return _value; },

    setValue(val) {
      _value = String(val || '');
      textarea.value = _value;
      _render(_value);
    },

    on(event, fn) {
      if (event === 'change') _onChange = () => fn(api, { origin: '+input' });
    },

    refresh() { _render(_value); },

    setOption(key, val) {
      if (key === 'readOnly') {
        content.contentEditable = val ? 'false' : 'true';
        wrapper.classList.toggle('cme-readonly', !!val);
      }
    },

    getWrapperElement() { return wrapper; },

    focus() { content.focus(); },

    toTextArea() {
      textarea.value = _value;
      textarea.style.display = '';
      wrapper.remove();
    },
  };

  // Init
  _render(_value);

  return api;
}

/* ── fromTextArea — mirrors CodeMirror.fromTextArea() ─────────────── */
CMSEditor.fromTextArea = function (textarea, options) {
  return new CMSEditor(textarea, options);
};

/* ── Modes registration (stubs for compatibility) ───────────────── */
CMSEditor.defineMode = function () {};
CMSEditor.defineMIME = function () {};
CMSEditor.modes = {};

/* ── Export as window.CodeMirror for drop-in compatibility ──────── */
global.CodeMirror = CMSEditor;

})(typeof window !== 'undefined' ? window : this);
