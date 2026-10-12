@if (\App\Support\ChatbotAssistant::enabled())
@php
    $botName = \App\Models\Setting::get('site_name', 'Texpert Deals');
    $greeting = \App\Models\Setting::get('chatbot_greeting') ?: "Hi! I'm the {$botName} assistant. Ask me about products, your order, shipping or returns.";
@endphp
<div data-chatbot data-endpoint="{{ route('chat') }}" class="fixed bottom-5 right-5 z-50">

    {{-- launcher --}}
    <button type="button" data-chat-toggle aria-label="Chat with us"
            class="flex h-14 w-14 items-center justify-center rounded-full bg-amber-500 text-slate-900 shadow-lg shadow-black/30 transition hover:bg-amber-400">
        <svg data-chat-open-icon class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
        <svg data-chat-close-icon class="hidden h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>

    {{-- panel --}}
    <div data-chat-panel class="absolute bottom-16 right-0 hidden h-[32rem] max-h-[calc(100dvh_-_6.5rem)] w-[22rem] max-w-[calc(100vw_-_1.5rem)] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <div class="flex items-center gap-3 bg-slate-900 px-4 py-3 text-white">
            <span class="grid h-9 w-9 place-items-center rounded-full bg-amber-500 text-slate-900">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
            </span>
            <div>
                <div class="text-sm font-bold leading-tight">{{ $botName }} Assistant</div>
                <div class="text-[11px] text-slate-400">Typically replies instantly</div>
            </div>
        </div>

        <div data-chat-log class="flex-1 space-y-3 overflow-y-auto bg-slate-50 p-4 text-sm"></div>

        <form data-chat-form class="flex items-center gap-2 border-t border-slate-200 bg-white p-3">
            <input data-chat-input type="text" autocomplete="off" placeholder="Type your message…"
                   class="w-full rounded-full border border-slate-200 px-4 py-2.5 text-sm text-slate-800 outline-none focus:border-amber-400">
            <button type="submit" aria-label="Send" class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-amber-500 text-slate-900 transition hover:bg-amber-400">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const root = document.querySelector('[data-chatbot]');
    if (!root) return;
    const endpoint = root.dataset.endpoint;
    const toggle = root.querySelector('[data-chat-toggle]');
    const panel = root.querySelector('[data-chat-panel]');
    const openIcon = root.querySelector('[data-chat-open-icon]');
    const closeIcon = root.querySelector('[data-chat-close-icon]');
    const log = root.querySelector('[data-chat-log]');
    const form = root.querySelector('[data-chat-form]');
    const input = root.querySelector('[data-chat-input]');
    const token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    const greeting = @json($greeting);

    let history = [];       // API turns: {role, content}
    let started = false;
    let busy = false;

    // The widget is re-created on every page load, so the conversation is kept in
    // sessionStorage: it survives browsing around the site but not closing the tab.
    const STORE_KEY = 'td_chat_v1';
    const store = {
        read() {
            try { return JSON.parse(sessionStorage.getItem(STORE_KEY)); } catch (e) { return null; }
        },
        write(data) {
            try { sessionStorage.setItem(STORE_KEY, JSON.stringify(data)); } catch (e) { /* private mode */ }
        },
    };
    const persist = () => store.write({ history, open: !panel.classList.contains('hidden') });

    const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
    const linkify = (s) => {
        const links = [];
        let out = esc(s);
        // Markdown links [text](url) -> stash so bare-url pass can't touch them
        out = out.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, (m, t, u) => {
            links.push('<a href="' + u + '" target="_blank" rel="noopener" class="font-semibold text-amber-600 underline">' + t + '</a>');
            return '@@L' + (links.length - 1) + 'L@@';
        });
        // Bare URLs — stop before trailing punctuation (fixes the trailing "," breaking links)
        out = out.replace(/(https?:\/\/[^\s<]*[^\s<.,;:!?)\]"'])/g, '<a href="$1" target="_blank" rel="noopener" class="font-semibold text-amber-600 underline">$1</a>');
        out = out.replace(/@@L(\d+)L@@/g, (m, i) => links[+i]);
        // Bulleted lines: "- x" / "* x" / "• x" -> proper bullet rows
        out = out.replace(/^[\t ]*[-*•]\s+/gm, '<span class="mr-1.5 text-amber-500">•</span>');
        return out.replace(/\n/g, '<br>');
    };

    function bubble(role, text, typing) {
        const wrap = document.createElement('div');
        wrap.className = role === 'user' ? 'flex justify-end' : 'flex justify-start';
        const b = document.createElement('div');
        b.className = role === 'user'
            ? 'max-w-[80%] rounded-2xl rounded-br-sm bg-slate-900 px-3.5 py-2 text-white'
            : 'max-w-[80%] rounded-2xl rounded-bl-sm bg-white px-3.5 py-2 text-slate-800 shadow-sm ring-1 ring-slate-200';
        b.innerHTML = typing ? '<span class="inline-flex gap-1"><span class="chat-dot"></span><span class="chat-dot"></span><span class="chat-dot"></span></span>' : linkify(text);
        wrap.appendChild(b);
        log.appendChild(wrap);
        log.scrollTop = log.scrollHeight;
        return wrap;
    }

    function openPanel() {
        panel.classList.remove('hidden');
        panel.classList.add('flex');
        openIcon.classList.add('hidden');
        closeIcon.classList.remove('hidden');
        if (!started) { started = true; bubble('assistant', greeting); }
        setTimeout(() => input.focus(), 50);
        persist();
    }
    function closePanel() {
        panel.classList.add('hidden');
        panel.classList.remove('flex');
        openIcon.classList.remove('hidden');
        closeIcon.classList.add('hidden');
        persist();
    }
    toggle.addEventListener('click', () => panel.classList.contains('hidden') ? openPanel() : closePanel());

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const text = input.value.trim();
        if (!text || busy) return;
        input.value = '';
        bubble('user', text);
        history.push({ role: 'user', content: text });
        persist();
        busy = true;
        const typing = bubble('assistant', '', true);
        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ messages: history }),
            });
            const data = await res.json();
            typing.remove();
            const reply = (data && data.reply) ? data.reply : 'Sorry, something went wrong. Please try again.';
            bubble('assistant', reply);
            history.push({ role: 'assistant', content: reply });
            if (history.length > 24) history = history.slice(-24);
            persist();
        } catch (err) {
            typing.remove();
            bubble('assistant', 'Sorry, I could not reach the server. Please try again.');
        } finally {
            busy = false;
            input.focus();
        }
    });

    // ---- restore the conversation from the previous page ----
    (function restore() {
        const saved = store.read();
        if (!saved) return;

        if (Array.isArray(saved.history) && saved.history.length) {
            history = saved.history.slice(-24);
            started = true;
            bubble('assistant', greeting);          // the greeting is never part of history
            history.forEach((m) => bubble(m.role === 'user' ? 'user' : 'assistant', m.content));
        }

        if (saved.open) openPanel();
    })();
})();
</script>
<style>
    .chat-dot { width:6px; height:6px; border-radius:50%; background:#94a3b8; display:inline-block; animation: chatb 1s infinite; }
    .chat-dot:nth-child(2){ animation-delay:.15s } .chat-dot:nth-child(3){ animation-delay:.3s }
    @keyframes chatb { 0%,60%,100%{ opacity:.3; transform:translateY(0) } 30%{ opacity:1; transform:translateY(-3px) } }
</style>
@endpush
@endif
