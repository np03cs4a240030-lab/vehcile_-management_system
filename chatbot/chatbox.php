<?php
// chatbot/chatbox.php - Drop-in chatbot widget
// Include this ANYWHERE in your PHP file (top or bottom, doesn't matter)
?>

<!-- ===== CHATBOX WIDGET ===== -->
<style>

#vms-chat-btn:hover {
    transform: scale(1.12) !important;
    box-shadow: 0 8px 32px rgba(249,115,22,0.7) !important;
}
#vms-chat-pulse {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 14px;
    height: 14px;
    background: #22c55e;
    border-radius: 50%;
    border: 2px solid #fff;
    animation: vms-pulse 2s infinite;
}
@keyframes vms-pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.3); opacity: 0.7; }
}
#vms-chat-window {
    position: fixed !important;
    bottom: 108px !important;
    right: 30px !important;
    z-index: 999998 !important;
    width: 370px !important;
    height: 530px !important;
    border-radius: 20px !important;
    overflow: hidden !important;
    box-shadow: 0 20px 60px rgba(0,0,0,0.25) !important;
    display: none;
    flex-direction: column !important;
    font-family: 'DM Sans', 'Segoe UI', sans-serif !important;
    font-size: 14px !important;
    background: #f7f9fc !important;
    border: 1px solid rgba(249,115,22,0.15) !important;
}
#vms-chat-window.vms-open {
    display: flex !important;
    animation: vms-slideup 0.3s cubic-bezier(.34,1.56,.64,1);
}
@keyframes vms-slideup {
    from { opacity: 0; transform: translateY(24px) scale(0.96); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

/* Header */
#vms-header {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    padding: 14px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}
#vms-header-left { display: flex; align-items: center; gap: 10px; }
#vms-avatar {
    font-size: 26px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    width: 42px; height: 42px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
#vms-title { color: #fff; font-weight: 700; font-size: 15px; }
#vms-subtitle { color: rgba(255,255,255,0.8); font-size: 12px; display: flex; align-items: center; gap: 5px; margin-top: 1px; }
#vms-online-dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: #86efac;
    box-shadow: 0 0 6px #86efac;
    display: inline-block;
}
#vms-header-btns { display: flex; gap: 4px; }
.vms-hbtn {
    background: rgba(255,255,255,0.18) !important;
    border: none !important;
    color: #fff !important;
    cursor: pointer !important;
    width: 30px !important; height: 30px !important;
    border-radius: 8px !important;
    display: flex !important; align-items: center !important; justify-content: center !important;
    font-size: 16px !important;
    transition: background 0.18s !important;
    padding: 0 !important;
}
.vms-hbtn:hover { background: rgba(255,255,255,0.32) !important; }

/* Messages */
#vms-messages {
    flex: 1;
    overflow-y: auto;
    padding: 14px 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    scroll-behavior: smooth;
    background: #f7f9fc;
}
#vms-messages::-webkit-scrollbar { width: 4px; }
#vms-messages::-webkit-scrollbar-thumb { background: #dde3ef; border-radius: 4px; }

.vms-row { display: flex; align-items: flex-end; gap: 6px; }
.vms-row.vms-user { flex-direction: row-reverse; }
.vms-ico {
    width: 28px; height: 28px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; flex-shrink: 0;
    background: rgba(249,115,22,0.12);
}
.vms-row.vms-user .vms-ico {
    background: linear-gradient(135deg, #f97316, #ea580c);
    color: #fff; font-size: 11px; font-weight: 700;
}
.vms-bubble {
    max-width: 78%;
    padding: 10px 14px;
    border-radius: 18px;
    line-height: 1.55;
    font-size: 13.5px;
    word-wrap: break-word;
}
.vms-bot-bub {
    background: #fff;
    color: #1e293b;
    border-bottom-left-radius: 4px;
    box-shadow: 0 1px 6px rgba(0,0,0,0.07);
}
.vms-user-bub {
    background: linear-gradient(135deg, #f97316, #ea580c);
    color: #fff;
    border-bottom-right-radius: 4px;
    box-shadow: 0 2px 10px rgba(249,115,22,0.3);
}
.vms-time {
    text-align: center;
    font-size: 10px;
    color: #94a3b8;
    margin: 0 auto;
    user-select: none;
}
.vms-dot { width: 7px; height: 7px; border-radius: 50%; background: #fb923c; animation: vms-bounce 1.2s infinite; display: inline-block; margin: 0 2px; }
.vms-dot:nth-child(2) { animation-delay: 0.2s; }
.vms-dot:nth-child(3) { animation-delay: 0.4s; }
@keyframes vms-bounce {
    0%,80%,100% { transform: translateY(0); }
    40% { transform: translateY(-7px); }
}

/* Quick replies */
#vms-quick {
    display: flex;
    gap: 6px;
    padding: 8px 10px;
    overflow-x: auto;
    flex-shrink: 0;
    background: #f7f9fc;
    border-top: 1px solid #eef1f8;
}
#vms-quick::-webkit-scrollbar { display: none; }
.vms-qbtn {
    background: #fff !important;
    border: 1.5px solid rgba(249,115,22,0.3) !important;
    color: #ea580c !important;
    border-radius: 20px !important;
    padding: 5px 12px !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    cursor: pointer !important;
    white-space: nowrap !important;
    transition: all 0.18s !important;
    flex-shrink: 0 !important;
    font-family: inherit !important;
}
.vms-qbtn:hover {
    background: #f97316 !important;
    color: #fff !important;
    border-color: #f97316 !important;
}

/* Input */
#vms-input-area {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    background: #fff;
    border-top: 1px solid #eef1f8;
    flex-shrink: 0;
}
#vms-input {
    flex: 1 !important;
    border: 1.5px solid #e2e8f0 !important;
    border-radius: 24px !important;
    padding: 10px 16px !important;
    font-size: 13.5px !important;
    outline: none !important;
    color: #1e293b !important;
    background: #f8fafc !important;
    transition: border-color 0.2s, box-shadow 0.2s !important;
    font-family: inherit !important;
    box-shadow: none !important;
}
#vms-input:focus {
    border-color: #f97316 !important;
    background: #fff !important;
    box-shadow: 0 0 0 3px rgba(249,115,22,0.12) !important;
}
#vms-send {
    width: 42px !important; height: 42px !important;
    border-radius: 50% !important; border: none !important;
    background: linear-gradient(135deg, #f97316, #ea580c) !important;
    color: #fff !important; cursor: pointer !important;
    display: flex !important; align-items: center !important; justify-content: center !important;
    transition: transform 0.2s, box-shadow 0.2s !important;
    flex-shrink: 0 !important;
    box-shadow: 0 2px 10px rgba(249,115,22,0.4) !important;
    padding: 0 !important;
}
#vms-send:hover { transform: scale(1.1) !important; }
#vms-send:disabled { opacity: 0.5 !important; cursor: not-allowed !important; transform: none !important; }

#vms-footer {
    text-align: center;
    font-size: 10px;
    color: #94a3b8;
    padding: 4px 0 6px;
    background: #fff;
    border-top: 1px solid #f1f5f9;
    letter-spacing: 0.3px;
    flex-shrink: 0;
}

@media (max-width: 480px) {
    #vms-chat-window { width: calc(100vw - 20px) !important; right: 10px !important; height: 72vh !important; }
    #vms-chat-btn { bottom: 18px !important; right: 16px !important; }
}

#vms-chat-btn {
    position: fixed !important;
    bottom: 100px !important;
    right: 25px !important;
    z-index: 999999 !important;
    width: 62px !important;
    height: 62px !important;
    border-radius: 50% !important;
    border: none !important;
    background: linear-gradient(135deg, #f97316, #ea580c) !important;
    color: #fff !important;
    cursor: pointer !important;
    box-shadow: 0 4px 24px rgba(249,115,22,0.55) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    transition: transform 0.25s cubic-bezier(.34,1.56,.64,1), box-shadow 0.25s !important;
    font-size: 26px !important;
    line-height: 1 !important;
}
</style>

<!-- Toggle Button -->
<button id="vms-chat-btn" onclick="vmsToggle()" title="Chat with us" type="button">
    <span id="vms-btn-icon">🚗</span>
    <span id="vms-chat-pulse"></span>
</button>

<!-- Chat Window -->
<div id="vms-chat-window">
    <div id="vms-header">
        <div id="vms-header-left">
            <div id="vms-avatar">🚗</div>
            <div>
                <div id="vms-title">भटभटे Assistant</div>
                <div id="vms-subtitle">
                    <span id="vms-online-dot"></span> Online — Ready to help
                </div>
            </div>
        </div>
        <div id="vms-header-btns">
            <button class="vms-hbtn" onclick="vmsClear()" title="New conversation">↺</button>
            <button class="vms-hbtn" onclick="vmsToggle()" title="Close">✕</button>
        </div>
    </div>

    <div id="vms-messages"></div>

    <div id="vms-quick">
        <button class="vms-qbtn" onclick="vmsQuick('Available vehicles')">🚗 Vehicles</button>
        <button class="vms-qbtn" onclick="vmsQuick('How to book a vehicle?')">📅 Booking</button>
        <button class="vms-qbtn" onclick="vmsQuick('What are the prices?')">💰 Pricing</button>
        <button class="vms-qbtn" onclick="vmsQuick('My booking status')">📋 My Booking</button>
        <button class="vms-qbtn" onclick="vmsQuick('Locations available')">📍 Locations</button>
    </div>

    <div id="vms-input-area">
        <input
            type="text"
            id="vms-input"
            placeholder="Ask me anything..."
            maxlength="1000"
            autocomplete="off"
        />
        <button id="vms-send" type="button">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="22" y1="2" x2="11" y2="13"/>
                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
            </svg>
        </button>
    </div>

    <div id="vms-footer">Powered by Claude AI · भटभटे Rental</div>
</div>

<script>
(function() {
    var vmsOpen = false;
    var vmsBusy = false;
    var vmsHistory = [];

    /* ── Init ── */
    window.addEventListener('load', function() {
        vmsAddBot("Namaste! 🙏 I'm the <strong>भटभटे Assistant</strong>.<br>I can help with vehicles, bookings, pricing & more.<br><br>How can I help you today?");
    });

    /* ── Toggle ── */
    window.vmsToggle = function() {
        vmsOpen = !vmsOpen;
        var win = document.getElementById('vms-chat-window');
        var icon = document.getElementById('vms-btn-icon');
        var pulse = document.getElementById('vms-chat-pulse');
        if (vmsOpen) {
            win.classList.add('vms-open');
            icon.textContent = '✕';
            pulse.style.display = 'none';
            setTimeout(function() { document.getElementById('vms-input').focus(); }, 320);
        } else {
            win.classList.remove('vms-open');
            icon.textContent = '🚗';
        }
    };

    /* ── Clear ── */
    window.vmsClear = function() {
        document.getElementById('vms-messages').innerHTML = '';
        vmsHistory = [];
        document.getElementById('vms-quick').style.display = 'flex';
        vmsAddBot("Conversation cleared! 👋 How can I help you?");
    };

    /* ── Quick reply ── */
    window.vmsQuick = function(text) {
        document.getElementById('vms-quick').style.display = 'none';
        document.getElementById('vms-input').value = text;
        vmsSend();
    };

    /* ── Send ── */
    window.vmsSend = function() {
        if (vmsBusy) return;
        var inp = document.getElementById('vms-input');
        var txt = inp.value.trim();
        if (!txt) return;

        inp.value = '';
        vmsBusy = true;
        document.getElementById('vms-send').disabled = true;
        document.getElementById('vms-quick').style.display = 'none';

        vmsAddUser(txt);
        vmsHistory.push({ role: 'user', content: txt });

        var typId = vmsTyping();

        fetch('chatbot/ai-engine.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: txt, history: vmsHistory.slice(-20) })
        })
        .then(function(r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function(d) {
            vmsRemoveTyping(typId);
            var reply = (d && d.reply) ? d.reply : 'Sorry, I could not respond. Please try again.';
            vmsAddBot(reply);
            vmsHistory.push({ role: 'assistant', content: reply });
        })
        .catch(function() {
            vmsRemoveTyping(typId);
            vmsAddBot('⚠️ Could not connect. Please check your internet and try again.');
        })
        .finally(function() {
            vmsBusy = false;
            document.getElementById('vms-send').disabled = false;
            document.getElementById('vms-input').focus();
        });
    };

    /* ── UI helpers ── */
    function vmsAddBot(html) {
        var area = document.getElementById('vms-messages');
        var row = document.createElement('div');
        row.className = 'vms-row vms-bot';
        row.innerHTML = '<div class="vms-ico">🚗</div><div class="vms-bubble vms-bot-bub">' + html + '</div>';
        area.appendChild(row);
        vmsScroll();
    }

    function vmsAddUser(txt) {
        var area = document.getElementById('vms-messages');
        var row = document.createElement('div');
        row.className = 'vms-row vms-user';
        row.innerHTML = '<div class="vms-ico">You</div><div class="vms-bubble vms-user-bub">' + vmsEsc(txt) + '</div>';
        area.appendChild(row);
        area.appendChild(vmsTimestamp());
        vmsScroll();
    }

    function vmsTyping() {
        var area = document.getElementById('vms-messages');
        var id = 'vt' + Date.now();
        var row = document.createElement('div');
        row.className = 'vms-row vms-bot';
        row.id = id;
        row.innerHTML = '<div class="vms-ico">🚗</div><div class="vms-bubble vms-bot-bub" style="padding:12px 16px"><span class="vms-dot"></span><span class="vms-dot"></span><span class="vms-dot"></span></div>';
        area.appendChild(row);
        vmsScroll();
        return id;
    }

    function vmsRemoveTyping(id) {
        var el = document.getElementById(id);
        if (el) el.remove();
    }

    function vmsTimestamp() {
        var d = document.createElement('div');
        d.className = 'vms-time';
        d.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        return d;
    }

    function vmsScroll() {
        var a = document.getElementById('vms-messages');
        a.scrollTop = a.scrollHeight;
    }

    function vmsEsc(s) {
        return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /* ── Keyboard ── */
    document.addEventListener('DOMContentLoaded', function() {
        var inp = document.getElementById('vms-input');
        var btn = document.getElementById('vms-send');
        if (inp) {
            inp.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); vmsSend(); }
            });
        }
        if (btn) {
            btn.addEventListener('click', vmsSend);
        }
    });
})();
</script>