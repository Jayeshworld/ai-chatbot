<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Gateway — Chat</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-main:    #212121;
            --bg-sidebar: #171717;
            --bg-input:   #2f2f2f;
            --bg-hover:   #2a2a2a;
            --bg-msg-ai:  #2f2f2f;
            --border:     #3d3d3d;
            --text-main:  #ececec;
            --text-muted: #9b9b9b;
            --text-dim:   #6b6b6b;
            --accent:     #10a37f;
            --accent-hover: #0d8a6b;
            --danger:     #ef4444;
            --radius:     12px;
        }

        html, body { height: 100%; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: var(--bg-main); color: var(--text-main); }

        /* ── Layout ── */
        .app { display: flex; height: 100vh; overflow: hidden; }

        /* ── Sidebar ── */
        .sidebar {
            width: 260px; min-width: 260px;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            transition: transform 0.25s ease;
        }
        .sidebar-header { padding: 16px 12px 8px; }
        .btn-new-chat {
            width: 100%; padding: 10px 14px;
            background: transparent; border: 1px solid var(--border);
            color: var(--text-main); border-radius: 8px;
            font-size: 14px; cursor: pointer; display: flex; align-items: center; gap: 10px;
            transition: background 0.15s;
        }
        .btn-new-chat:hover { background: var(--bg-hover); }
        .btn-new-chat svg { flex-shrink: 0; }

        .sidebar-section { padding: 6px 8px 2px; font-size: 11px; font-weight: 600; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.5px; }

        .conversations { flex: 1; overflow-y: auto; padding: 4px 8px; }
        .conversations::-webkit-scrollbar { width: 4px; }
        .conversations::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

        .conv-item {
            padding: 9px 12px; border-radius: 8px; cursor: pointer;
            font-size: 13.5px; color: var(--text-muted); white-space: nowrap;
            overflow: hidden; text-overflow: ellipsis; transition: background 0.15s;
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 2px;
        }
        .conv-item:hover { background: var(--bg-hover); color: var(--text-main); }
        .conv-item.active { background: var(--bg-input); color: var(--text-main); }
        .conv-item .conv-delete { opacity: 0; font-size: 12px; color: var(--text-dim); padding: 2px 4px; border-radius: 4px; }
        .conv-item:hover .conv-delete { opacity: 1; }
        .conv-item .conv-delete:hover { color: var(--danger); }

        .sidebar-footer {
            padding: 12px; border-top: 1px solid var(--border);
            font-size: 12px; color: var(--text-dim);
        }
        .api-key-display {
            font-family: monospace; font-size: 11px; color: var(--text-dim);
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
            cursor: pointer;
        }
        .api-key-display:hover { color: var(--text-muted); }

        /* ── Main ── */
        .main { flex: 1; display: flex; flex-direction: column; min-width: 0; }

        /* ── Top bar ── */
        .topbar {
            padding: 12px 20px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 12px; min-height: 56px;
        }
        .model-badge {
            font-size: 12px; background: var(--bg-input); color: var(--text-muted);
            padding: 4px 10px; border-radius: 20px; border: 1px solid var(--border);
        }
        .topbar-title { font-size: 14px; font-weight: 500; color: var(--text-muted); flex: 1; }

        /* ── Messages ── */
        .messages { flex: 1; overflow-y: auto; padding: 24px 0; scroll-behavior: smooth; }
        .messages::-webkit-scrollbar { width: 6px; }
        .messages::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

        .msg-wrapper { max-width: 760px; margin: 0 auto; padding: 0 24px; }

        .msg-row { display: flex; gap: 14px; margin-bottom: 24px; animation: fadeIn 0.2s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }

        .avatar {
            width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700; margin-top: 2px;
        }
        .avatar.user { background: #5c5cff; color: #fff; }
        .avatar.ai   { background: var(--accent); color: #fff; }

        .msg-content { flex: 1; min-width: 0; }
        .msg-role { font-size: 12px; font-weight: 600; color: var(--text-dim); margin-bottom: 5px; text-transform: capitalize; }
        .msg-text {
            font-size: 15px; line-height: 1.7; color: var(--text-main);
            white-space: pre-wrap; word-break: break-word;
        }
        .msg-text code {
            background: #1a1a1a; padding: 2px 6px; border-radius: 4px;
            font-family: 'SF Mono', 'Fira Code', monospace; font-size: 13px;
            color: #e5c07b;
        }
        .msg-text pre {
            background: #1a1a1a; border: 1px solid var(--border); border-radius: 8px;
            padding: 14px 16px; margin: 8px 0; overflow-x: auto;
        }
        .msg-text pre code { background: none; padding: 0; color: #abb2bf; }

        /* cursor blink */
        .cursor { display: inline-block; width: 2px; height: 16px; background: var(--accent); margin-left: 1px; animation: blink 0.9s step-end infinite; vertical-align: text-bottom; }
        @keyframes blink { 50% { opacity: 0; } }

        /* welcome screen */
        .welcome {
            flex: 1; display: flex; flex-direction: column;
            align-items: center; justify-content: center; gap: 16px; padding: 40px;
        }
        .welcome-logo { font-size: 48px; margin-bottom: 8px; }
        .welcome h1 { font-size: 28px; font-weight: 600; }
        .welcome p { color: var(--text-muted); font-size: 15px; text-align: center; max-width: 400px; }
        .welcome-suggestions { display: flex; gap: 12px; flex-wrap: wrap; justify-content: center; margin-top: 8px; }
        .suggestion {
            padding: 10px 16px; background: var(--bg-input); border: 1px solid var(--border);
            border-radius: 8px; font-size: 13px; color: var(--text-muted); cursor: pointer;
            transition: background 0.15s, color 0.15s;
        }
        .suggestion:hover { background: var(--bg-hover); color: var(--text-main); }

        /* ── Input bar ── */
        .input-area {
            padding: 16px 24px 20px;
            border-top: 1px solid var(--border);
        }
        .input-wrapper {
            max-width: 760px; margin: 0 auto;
            background: var(--bg-input); border: 1px solid var(--border);
            border-radius: var(--radius); display: flex; align-items: flex-end; gap: 10px;
            padding: 12px 14px; transition: border-color 0.2s;
        }
        .input-wrapper:focus-within { border-color: var(--accent); }
        #messageInput {
            flex: 1; background: none; border: none; outline: none;
            color: var(--text-main); font-size: 15px; resize: none;
            max-height: 200px; line-height: 1.5; font-family: inherit;
        }
        #messageInput::placeholder { color: var(--text-dim); }
        #messageInput::-webkit-scrollbar { width: 4px; }
        #messageInput::-webkit-scrollbar-thumb { background: var(--border); }

        .btn-send {
            width: 36px; height: 36px; border-radius: 8px; border: none;
            background: var(--accent); color: #fff; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; transition: background 0.15s, opacity 0.15s;
        }
        .btn-send:hover:not(:disabled) { background: var(--accent-hover); }
        .btn-send:disabled { opacity: 0.4; cursor: not-allowed; }

        .input-hint { text-align: center; font-size: 11px; color: var(--text-dim); margin-top: 8px; }

        /* ── Auth overlay ── */
        .auth-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.85);
            display: flex; align-items: center; justify-content: center; z-index: 100;
        }
        .auth-box {
            background: var(--bg-sidebar); border: 1px solid var(--border);
            border-radius: 16px; padding: 36px 40px; width: 420px; max-width: 95vw;
        }
        .auth-box h2 { font-size: 22px; margin-bottom: 6px; }
        .auth-box p { font-size: 13px; color: var(--text-muted); margin-bottom: 24px; }
        .auth-box label { font-size: 13px; color: var(--text-muted); display: block; margin-bottom: 6px; }
        .auth-box input {
            width: 100%; padding: 10px 14px; background: var(--bg-input);
            border: 1px solid var(--border); border-radius: 8px;
            color: var(--text-main); font-size: 14px; font-family: monospace;
            outline: none; margin-bottom: 16px;
        }
        .auth-box input:focus { border-color: var(--accent); }
        .btn-primary {
            width: 100%; padding: 11px; background: var(--accent); border: none;
            border-radius: 8px; color: #fff; font-size: 15px; font-weight: 600;
            cursor: pointer; transition: background 0.15s;
        }
        .btn-primary:hover { background: var(--accent-hover); }
        .auth-error { color: var(--danger); font-size: 13px; margin-top: 8px; display: none; }
        .auth-register-link { text-align: center; margin-top: 16px; font-size: 13px; color: var(--text-muted); }
        .auth-register-link a { color: var(--accent); text-decoration: none; font-weight: 500; }
        .auth-register-link a:hover { text-decoration: underline; }
        .auth-divider { border: none; border-top: 1px solid var(--border); margin: 16px 0 0; }

        /* ── Loading dots ── */
        .typing { display: flex; gap: 5px; padding: 4px 0; }
        .typing span {
            width: 7px; height: 7px; border-radius: 50%; background: var(--text-dim);
            animation: bounce 1.2s infinite;
        }
        .typing span:nth-child(2) { animation-delay: 0.2s; }
        .typing span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes bounce { 0%,80%,100% { transform: translateY(0); } 40% { transform: translateY(-7px); } }

        /* ── Toast ── */
        .toast {
            position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
            background: #1e1e1e; border: 1px solid var(--border); border-radius: 8px;
            padding: 10px 18px; font-size: 13px; color: var(--text-muted);
            opacity: 0; transition: opacity 0.3s; pointer-events: none; z-index: 200;
        }
        .toast.show { opacity: 1; }

        /* ── Responsive ── */
        @media (max-width: 640px) {
            .sidebar { position: fixed; left: 0; top: 0; bottom: 0; z-index: 50; transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .menu-btn { display: flex !important; }
        }
        .menu-btn { display: none; background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 4px; }
    </style>
</head>
<body>

<!-- Auth overlay -->
<div class="auth-overlay" id="authOverlay">
    <div class="auth-box">
        <h2>🤖 AI Gateway</h2>
        <p>Enter your API key to start chatting with the AI.</p>
        <label>Your API Key</label>
        <input type="password" id="apiKeyInput" placeholder="ak_xxxxxxxxxxxxxxxx..." autocomplete="off">
        <div class="auth-error" id="authError">Invalid API key. Please try again.</div>
        <button class="btn-primary" onclick="loginWithKey()">Connect →</button>
        <hr class="auth-divider">
        <div class="auth-register-link">
            Don't have an API key? <a href="/register">Request Access</a>
        </div>
    </div>
</div>

<div class="app">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <button class="btn-new-chat" onclick="startNewChat()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                New Chat
            </button>
        </div>
        <div class="sidebar-section">Conversations</div>
        <div class="conversations" id="conversationList">
            <!-- populated by JS -->
        </div>
        <div class="sidebar-footer">
            <div style="font-size:11px;color:var(--text-dim);margin-bottom:4px;">API Key</div>
            <div class="api-key-display" id="apiKeyDisplay" onclick="changeApiKey()" title="Click to change">—</div>
        </div>
    </aside>

    <!-- Main -->
    <main class="main">
        <div class="topbar">
            <button class="menu-btn" id="menuBtn" onclick="toggleSidebar()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
            </button>
            <span class="topbar-title" id="chatTitle">New Conversation</span>
            <span class="model-badge" id="modelBadge">qwen3:8b</span>
        </div>

        <!-- Messages area or Welcome -->
        <div id="welcomeScreen" class="welcome">
            <div class="welcome-logo">🤖</div>
            <h1>AI Gateway</h1>
            <p>Your self-hosted AI assistant powered by Qwen3 8B running on Ollama.</p>
            <div class="welcome-suggestions">
                <div class="suggestion" onclick="sendSuggestion('Explain how Laravel queues work')">Explain Laravel queues</div>
                <div class="suggestion" onclick="sendSuggestion('Write a Python function to sort a list')">Python code example</div>
                <div class="suggestion" onclick="sendSuggestion('What are the best practices for REST API design?')">REST API best practices</div>
                <div class="suggestion" onclick="sendSuggestion('Explain machine learning in simple terms')">Explain ML simply</div>
            </div>
        </div>

        <div class="messages" id="messagesArea" style="display:none;">
            <div class="msg-wrapper" id="messagesList"></div>
        </div>

        <!-- Input -->
        <div class="input-area">
            <div class="input-wrapper">
                <textarea
                    id="messageInput"
                    placeholder="Message AI Gateway..."
                    rows="1"
                    onkeydown="handleKeydown(event)"
                    oninput="autoResize(this)"
                ></textarea>
                <button class="btn-send" id="sendBtn" onclick="sendMessage()" title="Send (Enter)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                </button>
            </div>
            <div class="input-hint">Enter to send · Shift+Enter for new line</div>
        </div>
    </main>
</div>

<div class="toast" id="toast"></div>

<script>
// ── State ──
let API_KEY = localStorage.getItem('ai_gateway_api_key') || '';
let currentConversationId = null;
let isStreaming = false;
let conversations = [];

const API_BASE = '/api/v1';

// ── Boot ──
document.addEventListener('DOMContentLoaded', () => {
    if (API_KEY) {
        verifyAndBoot(API_KEY);
    }
});

async function verifyAndBoot(key) {
    try {
        const res = await fetch(`${API_BASE}/auth/me`, {
            headers: { 'X-API-KEY': key }
        });
        if (!res.ok) throw new Error('invalid');
        const data = await res.json();
        API_KEY = key;
        localStorage.setItem('ai_gateway_api_key', key);
        document.getElementById('authOverlay').style.display = 'none';
        document.getElementById('apiKeyDisplay').textContent = key.slice(0, 20) + '...';
        await loadConversations();
        loadModelBadge();
    } catch {
        showAuthError();
    }
}

function loginWithKey() {
    const key = document.getElementById('apiKeyInput').value.trim();
    if (!key) return;
    verifyAndBoot(key);
}

document.getElementById('apiKeyInput').addEventListener('keydown', e => {
    if (e.key === 'Enter') loginWithKey();
});

function showAuthError() {
    document.getElementById('authError').style.display = 'block';
}

function changeApiKey() {
    localStorage.removeItem('ai_gateway_api_key');
    location.reload();
}

// ── Conversations ──
async function loadConversations() {
    try {
        const res = await apiFetch('/conversations?per_page=50');
        conversations = res.data || [];
        renderConversationList();
    } catch { }
}

function renderConversationList() {
    const list = document.getElementById('conversationList');
    list.innerHTML = '';
    if (!conversations.length) {
        list.innerHTML = '<div style="padding:12px;font-size:13px;color:var(--text-dim);text-align:center;">No conversations yet</div>';
        return;
    }
    conversations.forEach(conv => {
        const item = document.createElement('div');
        item.className = 'conv-item' + (conv.id === currentConversationId ? ' active' : '');
        item.dataset.id = conv.id;
        item.innerHTML = `
            <span style="overflow:hidden;text-overflow:ellipsis;">${escHtml(conv.title || 'New conversation')}</span>
            <span class="conv-delete" onclick="deleteConversation(event, ${conv.id})">✕</span>
        `;
        item.addEventListener('click', () => loadConversation(conv.id));
        list.appendChild(item);
    });
}

async function loadConversation(id) {
    currentConversationId = id;
    try {
        const res = await apiFetch(`/conversations/${id}`);
        const conv = res.data;
        document.getElementById('chatTitle').textContent = conv.title || 'Conversation';
        showMessagesArea();
        renderMessages(conv.messages || []);
        updateActiveConv(id);
        scrollToBottom();
    } catch { toast('Failed to load conversation'); }
}

async function deleteConversation(e, id) {
    e.stopPropagation();
    await apiFetch(`/conversations/${id}`, 'DELETE');
    if (currentConversationId === id) startNewChat();
    conversations = conversations.filter(c => c.id !== id);
    renderConversationList();
}

function updateActiveConv(id) {
    document.querySelectorAll('.conv-item').forEach(el => {
        el.classList.toggle('active', parseInt(el.dataset.id) === id);
    });
}

function startNewChat() {
    currentConversationId = null;
    document.getElementById('chatTitle').textContent = 'New Conversation';
    document.getElementById('messagesList').innerHTML = '';
    document.getElementById('welcomeScreen').style.display = 'flex';
    document.getElementById('messagesArea').style.display = 'none';
    updateActiveConv(null);
    document.getElementById('messageInput').focus();
}

// ── Messages ──
function renderMessages(messages) {
    const list = document.getElementById('messagesList');
    list.innerHTML = '';
    messages.filter(m => m.role !== 'system').forEach(m => appendMessage(m.role, m.content));
}

function appendMessage(role, text, streaming = false) {
    const list = document.getElementById('messagesList');
    const row = document.createElement('div');
    row.className = 'msg-row';
    row.dataset.role = role;

    const isAi = role === 'assistant';
    row.innerHTML = `
        <div class="avatar ${isAi ? 'ai' : 'user'}">${isAi ? 'AI' : 'U'}</div>
        <div class="msg-content">
            <div class="msg-role">${isAi ? 'AI Gateway' : 'You'}</div>
            <div class="msg-text">${streaming ? '' : formatText(text)}</div>
        </div>
    `;
    list.appendChild(row);
    return row.querySelector('.msg-text');
}

function appendTypingIndicator() {
    const list = document.getElementById('messagesList');
    const row = document.createElement('div');
    row.className = 'msg-row';
    row.id = 'typingIndicator';
    row.innerHTML = `
        <div class="avatar ai">AI</div>
        <div class="msg-content">
            <div class="msg-role">AI Gateway</div>
            <div class="typing"><span></span><span></span><span></span></div>
        </div>
    `;
    list.appendChild(row);
    scrollToBottom();
    return row;
}

// ── Send message ──
async function sendMessage() {
    const input = document.getElementById('messageInput');
    const text = input.value.trim();
    if (!text || isStreaming) return;

    input.value = '';
    autoResize(input);
    showMessagesArea();
    appendMessage('user', text);
    scrollToBottom();

    const typingRow = appendTypingIndicator();
    setSending(true);

    try {
        await streamMessage(text, typingRow);
    } catch (err) {
        typingRow.remove();
        appendMessage('assistant', '❌ Error: ' + err.message);
        scrollToBottom();
    } finally {
        setSending(false);
        await loadConversations();
    }
}

async function streamMessage(text, typingRow) {
    const body = { message: text };
    if (currentConversationId) body.conversation_id = currentConversationId;

    const res = await fetch(`${API_BASE}/chat/stream`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-API-KEY': API_KEY, 'Accept': 'text/event-stream' },
        body: JSON.stringify(body),
    });

    if (!res.ok) {
        const err = await res.json().catch(() => ({ error: { message: 'Unknown error' } }));
        throw new Error(err.error?.message || `HTTP ${res.status}`);
    }

    typingRow.remove();
    const textEl = appendMessage('assistant', '', true);
    textEl.innerHTML = '<span class="cursor"></span>';

    let fullText = '';
    const reader = res.body.getReader();
    const decoder = new TextDecoder();
    let buf = '';

    while (true) {
        const { done, value } = await reader.read();
        if (done) break;
        buf += decoder.decode(value, { stream: true });

        const lines = buf.split('\n');
        buf = lines.pop();

        for (const line of lines) {
            if (!line.startsWith('data: ')) continue;
            const json = line.slice(6).trim();
            if (!json) continue;
            try {
                const event = JSON.parse(json);
                if (event.type === 'start') {
                    currentConversationId = event.conversation_id;
                    updateActiveConv(currentConversationId);
                    document.getElementById('chatTitle').textContent = 'Responding...';
                } else if (event.type === 'chunk') {
                    fullText += event.content;
                    textEl.innerHTML = formatText(fullText) + '<span class="cursor"></span>';
                    scrollToBottom();
                } else if (event.type === 'done') {
                    textEl.innerHTML = formatText(fullText);
                } else if (event.type === 'error') {
                    throw new Error(event.message);
                }
            } catch (e) {
                if (e instanceof SyntaxError) continue;
                throw e;
            }
        }
    }
}

function sendSuggestion(text) {
    document.getElementById('messageInput').value = text;
    sendMessage();
}

// ── Helpers ──
async function apiFetch(path, method = 'GET', body = null) {
    const opts = {
        method,
        headers: { 'X-API-KEY': API_KEY, 'Content-Type': 'application/json' },
    };
    if (body) opts.body = JSON.stringify(body);
    const res = await fetch(API_BASE + path, opts);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    if (method === 'DELETE') return {};
    return res.json();
}

async function loadModelBadge() {
    try {
        const res = await apiFetch('/models');
        const models = res.data || [];
        const def = models.find(m => m.is_default) || models[0];
        if (def) document.getElementById('modelBadge').textContent = def.ollama_name;
    } catch { }
}

function showMessagesArea() {
    document.getElementById('welcomeScreen').style.display = 'none';
    document.getElementById('messagesArea').style.display = 'flex';
    document.getElementById('messagesArea').style.flexDirection = 'column';
}

function setSending(state) {
    isStreaming = state;
    const btn = document.getElementById('sendBtn');
    btn.disabled = state;
}

function scrollToBottom() {
    const area = document.getElementById('messagesArea');
    area.scrollTop = area.scrollHeight;
}

function handleKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
}

function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 200) + 'px';
}

function formatText(text) {
    // Basic markdown: code blocks, inline code, bold, italic
    let html = escHtml(text);
    // Code blocks
    html = html.replace(/```[\w]*\n?([\s\S]*?)```/g, (_, code) => `<pre><code>${code.trim()}</code></pre>`);
    // Inline code
    html = html.replace(/`([^`]+)`/g, '<code>$1</code>');
    // Bold
    html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
    // Italic
    html = html.replace(/\*([^*]+)\*/g, '<em>$1</em>');
    // Line breaks (outside pre tags)
    html = html.replace(/\n/g, '<br>');
    return html;
}

function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function toast(msg, duration = 3000) {
    const el = document.getElementById('toast');
    el.textContent = msg;
    el.classList.add('show');
    setTimeout(() => el.classList.remove('show'), duration);
}

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}
</script>
</body>
</html>
