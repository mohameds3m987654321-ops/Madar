(() => {
    if (!document.body || document.querySelector('.assistant-widget')) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const script = document.currentScript;
    const endpoints = {
        message: script?.dataset.messageUrl,
        escalate: script?.dataset.escalateUrl,
        conversation: script?.dataset.conversationUrl,
    };
    const isAuthenticated = script?.dataset.authenticated === 'true';
    const root = document.createElement('section');
    root.className = 'assistant-widget';
    root.setAttribute('aria-label', 'مساعد مدار');
    root.innerHTML = `
        <button class="assistant-launcher" type="button" aria-expanded="false" aria-controls="assistantPanel">
            <span aria-hidden="true">✦</span><span>اسأل مدار</span>
        </button>
        <section class="assistant-panel" id="assistantPanel" aria-label="محادثة المساعد" hidden>
            <header class="assistant-header">
                <div><h2>مساعد مدار</h2><p>إجابات سريعة عن المنصة</p></div>
                <div class="assistant-header-actions">
                    <button class="assistant-clear" type="button">مسح المحادثة</button>
                    <button class="assistant-close" type="button" aria-label="إغلاق المحادثة">×</button>
                </div>
            </header>
            <div class="assistant-messages" role="log" aria-live="polite" aria-relevant="additions text"></div>
            <p class="assistant-status" role="status"></p>
            <p class="assistant-privacy">تُحفظ المحادثة عند تحويلها للدعم. تُرفق بيانات الحساب للمستخدم المسجّل، ويمكن للزائر إضافة بيانات تواصل اختيارية.</p>
            <form class="assistant-compose">
                <label class="sr-only" for="assistantQuestion">اكتب سؤالك</label>
                <textarea id="assistantQuestion" name="question" maxlength="500" rows="1" placeholder="اكتب سؤالك..." required></textarea>
                <button type="submit">إرسال</button>
            </form>
        </section>`;

    document.body.append(root);

    const launcher = root.querySelector('.assistant-launcher');
    const panel = root.querySelector('.assistant-panel');
    const close = root.querySelector('.assistant-close');
    const clear = root.querySelector('.assistant-clear');
    const messages = root.querySelector('.assistant-messages');
    const status = root.querySelector('.assistant-status');
    const form = root.querySelector('.assistant-compose');
    const input = root.querySelector('textarea');
    const send = form.querySelector('button');
    let conversationToken = sessionStorage.getItem('madar-support-conversation');
    let conversationMessageCount = 0;
    let isSyncingConversation = false;
    let chatHistory = [];
    const faqPrompts = [
        'كيف أنشئ حسابًا؟',
        'كيف أفعّل بريدي الإلكتروني؟',
        'كيف أجد الدورات؟',
        'كيف أطلب استردادًا؟',
    ];
    let isAsking = false;

    function addMessage(text, sender = 'assistant') {
        const message = document.createElement('div');
        message.className = `assistant-message ${sender}`;
        message.textContent = text;
        messages.append(message);
        messages.scrollTop = messages.scrollHeight;
        return message;
    }

    function renderConversationMessage(message) {
        const sender = message.role === 'support' ? 'support' : message.role;
        addMessage(message.text, sender);
        conversationMessageCount += 1;
    }

    async function syncConversation() {
        if (!conversationToken || isSyncingConversation) return;
        isSyncingConversation = true;
        try {
            const thread = await postJson(
                `${endpoints.conversation}/${encodeURIComponent(conversationToken)}`,
                undefined,
                'GET',
            );
            if (!Array.isArray(thread.messages) || !thread.messages.every((message) =>
                message &&
                ['user', 'assistant', 'support'].includes(message.role) &&
                typeof message.text === 'string'
            )) {
                throw new Error('تعذر قراءة المحادثة مع فريق الدعم.');
            }

            thread.messages.slice(conversationMessageCount).forEach(renderConversationMessage);
            chatHistory = thread.messages
                .filter((message) => message.role === 'user' || message.role === 'assistant')
                .map(({ role, text }) => ({ role, text }));
        } finally {
            isSyncingConversation = false;
        }
    }

    function showSuggestions(prompts = faqPrompts) {
        if (!Array.isArray(prompts) || prompts.length === 0) return;
        const list = document.createElement('div');
        list.className = 'assistant-suggestions';
        list.setAttribute('aria-label', 'أسئلة شائعة');
        prompts.slice(0, 4).forEach((prompt) => {
            if (typeof prompt !== 'string' || prompt.length === 0) return;
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = prompt;
            button.addEventListener('click', () => ask(prompt));
            list.append(button);
        });
        messages.append(list);
    }

    function addEscalationAction(question) {
        const wrapper = document.createElement('div');
        wrapper.className = 'assistant-escalation';
        const action = document.createElement('button');
        action.type = 'button';
        action.className = 'assistant-action';
        action.textContent = 'تحويل المحادثة لفريق الدعم';
        wrapper.append(action);
        if (isAuthenticated) {
            action.addEventListener('click', () => escalate(question, wrapper, action));
        } else {
            action.addEventListener('click', () => {
                action.hidden = true;
                const contactForm = document.createElement('form');
                contactForm.className = 'assistant-contact-form';
                contactForm.innerHTML = `
                    <p>بيانات التواصل اختيارية، وتساعد فريق الدعم على الرد عليك.</p>
                    <label>الاسم (اختياري)<input name="name" maxlength="255" autocomplete="name"></label>
                    <label>البريد الإلكتروني (اختياري)<input name="email" type="email" maxlength="255" autocomplete="email"></label>
                    <label>رقم التواصل (اختياري)<input name="phone" type="tel" maxlength="30" autocomplete="tel"></label>
                    <div class="assistant-contact-actions">
                        <button type="submit">إرسال للدعم</button>
                        <button type="button" class="assistant-contact-skip">تخطي</button>
                    </div>`;
                contactForm.addEventListener('submit', (event) => {
                    event.preventDefault();
                    const formData = new FormData(contactForm);
                    escalate(question, wrapper, contactForm.querySelector('[type="submit"]'), {
                        name: formData.get('name'),
                        email: formData.get('email'),
                        phone: formData.get('phone'),
                    });
                });
                contactForm.querySelector('.assistant-contact-skip').addEventListener('click', () => {
                    escalate(question, wrapper, contactForm.querySelector('.assistant-contact-skip'));
                });
                wrapper.append(contactForm);
                messages.scrollTop = messages.scrollHeight;
            });
        }
        messages.append(wrapper);
        messages.scrollTop = messages.scrollHeight;
    }

    async function postJson(url, payload, method = 'POST') {
        const response = await fetch(url, {
            method,
            headers: {
                'Accept': 'application/json',
                ...(payload ? { 'Content-Type': 'application/json' } : {}),
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
            },
            ...(payload ? { body: JSON.stringify(payload) } : {}),
            credentials: 'same-origin',
        });
        let data;
        try {
            data = await response.json();
        } catch {
            throw new Error('وصل رد غير متوقع من الخادم. حاول مرة أخرى.');
        }
        if (!response.ok) {
            throw new Error(data.message || 'تعذر إرسال الطلب الآن. حاول مرة أخرى.');
        }
        if (!data || typeof data !== 'object') {
            throw new Error('وصل رد غير صالح من الخادم. حاول مرة أخرى.');
        }
        return data;
    }

    async function ask(question) {
        if (isAsking) return;
        const cleanQuestion = question.trim();
        if (cleanQuestion.length < 2) return;
        messages.querySelectorAll('.assistant-suggestions').forEach((item) => item.remove());
        addMessage(cleanQuestion, 'user');
        chatHistory.push({ role: 'user', text: cleanQuestion });
        status.textContent = 'جاري البحث عن إجابة...';
        isAsking = true;
        send.disabled = true;
        try {
            if (conversationToken) {
                conversationMessageCount += 1;
                let result;
                try {
                    result = await postJson(
                        `${endpoints.conversation}/${encodeURIComponent(conversationToken)}/messages`,
                        { message: cleanQuestion },
                    );
                } catch (error) {
                    conversationMessageCount -= 1;
                    throw error;
                }
                if (!Array.isArray(result.messages)) {
                    throw new Error('تعذر إرسال رسالتك لفريق الدعم.');
                }
                result.messages.slice(conversationMessageCount).forEach(renderConversationMessage);
                chatHistory = result.messages
                    .filter((message) => message.role === 'user' || message.role === 'assistant')
                    .map(({ role, text }) => ({ role, text }));
                return;
            }

            const result = await postJson(endpoints.message, { question: cleanQuestion });
            if (
                typeof result.answer !== 'string' ||
                typeof result.matched !== 'boolean' ||
                !Array.isArray(result.suggestions) ||
                !result.suggestions.every((suggestion) => typeof suggestion === 'string')
            ) {
                throw new Error('تعذر قراءة إجابة المساعد. حاول مرة أخرى.');
            }
            addMessage(result.answer);
            chatHistory.push({ role: 'assistant', text: result.answer });
            if (!result.matched) addEscalationAction(cleanQuestion);
            showSuggestions(result.suggestions);
        } catch (error) {
            addMessage(error instanceof Error ? error.message : 'تعذر إرسال السؤال الآن. حاول مرة أخرى.');
        } finally {
            status.textContent = '';
            send.disabled = false;
            isAsking = false;
        }
    }

    async function escalate(question, wrapper, action, contact = null) {
        if (!question || action.disabled) return;
        action.disabled = true;
        status.textContent = 'جاري إرسال سؤالك للدعم...';
        try {
            const payload = {
                question,
                messages: chatHistory.slice(-50),
            };
            if (contact) payload.contact = contact;
            const result = await postJson(endpoints.escalate, payload);
            if (typeof result.message !== 'string') {
                throw new Error('تعذر تأكيد إرسال السؤال للدعم.');
            }
            if (typeof result.conversation_token !== 'string') {
                throw new Error('تم إرسال السؤال، لكن تعذر تفعيل متابعة المحادثة.');
            }
            conversationToken = result.conversation_token;
            sessionStorage.setItem('madar-support-conversation', conversationToken);
            conversationMessageCount = chatHistory.length;
            const lastMessage = chatHistory.at(-1);
            if (lastMessage?.role !== 'user' || lastMessage.text !== question) {
                conversationMessageCount += 1;
            }
            await syncConversation();
            addMessage(result.message);
            wrapper.remove();
        } catch (error) {
            action.disabled = false;
            addMessage(error.message);
        } finally {
            status.textContent = '';
        }
    }

    launcher.addEventListener('click', () => {
        panel.hidden = !panel.hidden;
        launcher.setAttribute('aria-expanded', String(!panel.hidden));
        if (!panel.hidden) {
            if (conversationToken) {
                status.textContent = 'جاري استعادة محادثتك مع فريق الدعم...';
                syncConversation()
                    .then(() => {
                        if (messages.childElementCount === 0) {
                            addMessage('أهلًا بيك! اكتب رسالتك لمتابعة المحادثة مع فريق الدعم.');
                        }
                    })
                    .catch((error) => {
                        sessionStorage.removeItem('madar-support-conversation');
                        conversationToken = null;
                        addMessage(error instanceof Error ? error.message : 'تعذر استعادة المحادثة.');
                    })
                    .finally(() => {
                        status.textContent = '';
                    });
            } else if (messages.childElementCount === 0) {
                addMessage('أهلًا بيك! اختار سؤالًا شائعًا أو اكتب استفسارك.');
                showSuggestions();
            }
            input.focus();
        }
    });

    close.addEventListener('click', () => {
        panel.hidden = true;
        launcher.setAttribute('aria-expanded', 'false');
        launcher.focus();
    });

    clear.addEventListener('click', () => {
        if (conversationToken) {
            addMessage('المحادثة مرتبطة بطلب دعم مفتوح. يمكنك إغلاق النافذة والعودة لمتابعة الرد.');
            return;
        }
        messages.replaceChildren();
        chatHistory = [];
        conversationMessageCount = 0;
        status.textContent = '';
        addMessage('أهلًا بيك! اختار سؤالًا شائعًا أو اكتب استفسارك.');
        showSuggestions();
        input.focus();
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const question = input.value;
        input.value = '';
        ask(question);
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    window.setInterval(() => {
        if (!conversationToken || panel.hidden) return;
        syncConversation().catch((error) => {
            status.textContent = error instanceof Error ? error.message : 'تعذر تحديث المحادثة.';
        });
    }, 5000);
})();
