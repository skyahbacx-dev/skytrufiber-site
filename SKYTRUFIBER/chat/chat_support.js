/* ============================================================
   ROOT THEME VARIABLES (LIGHT + DARK)
============================================================ */
:root {
    --bg: #ffffff;
    --bg-secondary: #f5f7fa;
    --text: #111;
    --text-light: #666;

    --bubble-sent: #0084ff;
    --bubble-sent-text: #fff;

    --bubble-received: #e8e8e8;
    --bubble-received-text: #111;

    --header-bg: #0084ff;
    --header-text: #fff;

    --input-bg: #ffffff;
    --input-border: #ccc;

    --disabled-bg: #f4f4f4;
    --disabled-text: #999;

    --scroll-btn-bg: #0084ff;

    --ticket-resolved: #27ae60;
    --ticket-unresolved: #ff4747;

    --system-msg-bg: #e9f5ff;
    --system-msg-text: #004b8d;

    --system-assistant-bg: #e8f3ff;
    --system-assistant-text: #003c75;

    --qr-bg: #ffffff;
    --qr-border: #ddd;
}

:root[data-theme="dark"] {
    --bg: #0f0f0f;
    --bg-secondary: #141414;
    --text: #eee;
    --text-light: #aaa;

    --bubble-sent: #1a73e8;
    --bubble-sent-text: #fff;

    --bubble-received: #262626;
    --bubble-received-text: #eee;

    --header-bg: #111;
    --header-text: #fff;

    --input-bg: #1b1b1b;
    --input-border: #333;

    --disabled-bg: #202020;
    --disabled-text: #777;

    --system-msg-bg: #1a2838;
    --system-msg-text: #7dc1ff;

    --system-assistant-bg: #1a2a3a;
    --system-assistant-text: #9fc8ff;

    --qr-bg: #1b1b1b;
    --qr-border: #333;
}

/* ============================================================
   GLOBAL
============================================================ */
body {
    margin: 0;
    background: var(--bg);
    font-family: Arial, sans-serif;
    color: var(--text);
    overflow: hidden;
}

#chat-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.55);
    backdrop-filter: blur(6px);
    display: flex;
    justify-content: center;
    align-items: center;
}

/* ============================================================
   CHAT MODAL
============================================================ */
.chat-modal {
    width: 760px;
    height: 88vh;
    background: var(--bg);
    border-radius: 22px;
    box-shadow: 0 14px 40px rgba(0,0,0,0.25);
    display: flex;
    flex-direction: column;
    position: relative;
    overflow: hidden;
}

@media (max-width: 768px) {
    .chat-modal {
        width: 100vw;
        height: 100vh;
        border-radius: 0;
    }
}

/* ============================================================
   HEADER
============================================================ */
.chat-header {
    height: 92px;
    padding: 14px 20px;
    background: var(--header-bg);
    color: var(--header-text);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.chat-header-logo {
    width: 48px;
    height: 48px;
    border-radius: 50%;
}

/* Ticket Label */
.ticket-label {
    display: inline-block;
    margin-top: 3px;
    padding: 4px 10px;
    font-size: 12px;
    border-radius: 10px;
}
.ticket-resolved {
    background: var(--ticket-resolved);
    color: #fff;
}
.ticket-unresolved {
    background: var(--ticket-unresolved);
    color: #fff;
}

/* Logout */
.logout-btn {
    margin-left: 14px;
    background: #ff4040;
    padding: 8px 14px;
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
}

/* ============================================================
   THEME TOGGLE
============================================================ */
#theme-toggle {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: rgba(255,255,255,0.25);
    border: none;
    cursor: pointer;
    display:flex;
    justify-content:center;
    align-items:center;
    position:relative;
}

.theme-icon {
    position: absolute;
    font-size: 20px;
    transition: opacity .35s, transform .35s;
}

.moon-icon { opacity: 1; }
.sun-icon { opacity: 0; transform: rotate(-160deg); }

:root[data-theme="dark"] .moon-icon { opacity: 0; transform: rotate(120deg); }
:root[data-theme="dark"] .sun-icon { opacity: 1; transform: rotate(0deg); }

/* ============================================================
   MESSAGES AREA
============================================================ */
#chat-messages {
    flex: 1;
    padding: 22px;
    background: var(--bg-secondary);
    overflow-y: auto;
    display:flex;
    flex-direction:column;
    gap:18px;
}

#chat-messages::-webkit-scrollbar { width: 6px; }
#chat-messages::-webkit-scrollbar-thumb {
    background: #8dbdff;
    border-radius: 4px;
}

.system-message {
    background: var(--system-msg-bg);
    color: var(--system-msg-text);
    padding:14px;
    border-radius: 12px;
    font-size: 15px;
    text-align:center;
    max-width:80%;
    margin:0 auto;
}

/* ============================================================
   SYSTEM ASSISTANT BUBBLE (Suggestions)
============================================================ */
.system-suggest .message-bubble {
    background: var(--system-assistant-bg) !important;
    color: var(--system-assistant-text) !important;
    border-radius: 16px;
    padding: 14px 18px;
}

.system-suggest .suggest-buttons {
    margin-top: 12px;
    display:flex;
    flex-wrap:wrap;
    gap:8px;
}

.system-suggest .suggest-btn {
    padding: 6px 12px;
    font-size: 13px;
    border-radius: 10px;
    background: #fff;
    border: 1px solid #ccc;
    cursor:pointer;
    white-space: nowrap;
}

.system-suggest .suggest-btn:hover {
    background:#f0f0f0;
}

/* ============================================================
   MESSAGE BUBBLES
============================================================ */
.message {
    width:100%;
    display:flex;
    gap:10px;
    align-items:flex-end;
    opacity:0;
    transform:translateY(6px);
    animation:msgFade .25s ease forwards;
}

@keyframes msgFade {
    to { opacity:1; transform:translateY(0); }
}

.message.sent { justify-content:flex-end; }
.message.received { justify-content:flex-start; }

.message-avatar img {
    width:34px;
    height:34px;
    border-radius:50%;
}

.message-content { max-width:70%; }

.message-bubble {
    padding:13px 20px;
    border-radius:20px;
    font-size:15px;
    background:var(--bubble-received);
    color:var(--bubble-received-text);
}

.message.sent .message-bubble {
    background:var(--bubble-sent);
    color:var(--bubble-sent-text);
    border-radius:20px 20px 4px 20px;
}

.message.received .message-bubble {
    border-radius:20px 20px 20px 4px;
}

.message-time {
    font-size:11px;
    color:var(--text-light);
    margin-top:4px;
}

/* ============================================================
   ACTION TOOLBAR
============================================================ */
.action-toolbar {
    position:absolute;
    top:6px;
    right:-40px;
    opacity:0;
    pointer-events:none;
    transition:opacity .25s;
    z-index:9999;
}

.message.sent .action-toolbar {
    left:-40px;
    right:auto;
}

.message:hover .action-toolbar {
    opacity:1;
    pointer-events:auto;
}

.more-btn {
    width:28px;
    height:28px;
    border-radius:50%;
    background:#fff;
    border:none;
    box-shadow:0 1px 5px rgba(0,0,0,0.25);
    font-size:16px;
}

/* ============================================================
   POPUP MENU
============================================================ */
#msg-action-popup {
    position:absolute;
    background:var(--bg);
    border-radius:12px;
    padding:6px 0;
    border:1px solid rgba(0,0,0,0.15);
    display:none;
    z-index:999999;
}

#msg-action-popup button {
    width:100%;
    padding:10px 16px;
    text-align:left;
    background:none;
    border:none;
}

/* ============================================================
   INPUT AREA
============================================================ */
.chat-input-area {
    padding:14px;
    display:flex;
    gap:10px;
    align-items:center;
    border-top:1px solid var(--input-border);
    background:var(--input-bg);
}

.chat-input-area.disabled {
    background:var(--disabled-bg);
    opacity:.7;
    pointer-events:none;
}

.chat-input-box {
    flex:1;
    border-radius:22px;
    border:1px solid var(--input-border);
    padding:10px 14px;
}

.chat-input-box input {
    width:100%;
    border:none;
    outline:none;
    background:none;
}

#send-btn {
    width:48px;
    height:48px;
    border-radius:50%;
    border:none;
    background:var(--bubble-sent);
    color:white;
    cursor:pointer;
}

/* ============================================================
   SCROLL BUTTON
============================================================ */
.scroll-bottom-btn {
    position:absolute;
    right:18px;
    bottom:94px;
    width:46px;
    height:46px;
    border-radius:50%;
    background:var(--scroll-btn-bg);
    color:white;
    justify-content:center;
    align-items:center;
    display:flex;
    font-size:18px;
    opacity:0;
    pointer-events:none;
    transition:.25s;
}

.scroll-bottom-btn.show {
    opacity:1;
    pointer-events:auto;
}

/* ============================================================
   MOBILE (COMPACT MODE)
============================================================ */
@media (max-width:768px) {

    .chat-header { height:70px; padding:10px 14px; }

    .chat-header-logo { width:38px; height:38px; }

    #chat-messages { padding:16px; gap:12px; }

    .message-bubble { padding:10px 14px; font-size:14px; }

    .suggest-btn { font-size:12px; padding:5px 10px; }

    #send-btn {
        width:42px;
        height:42px;
        font-size:16px;
    }

    .scroll-bottom-btn {
        bottom:80px;
        width:40px;
        height:40px;
        font-size:16px;
    }

    #msg-action-popup {
        left:50% !important;
        transform:translateX(-50%);
    }
}
