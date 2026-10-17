<div id="gift-advisor" class="fixed inset-x-4 bottom-4 z-50 flex justify-end sm:left-auto sm:right-5">
    <button id="gift-advisor-toggle" type="button" aria-expanded="false" aria-controls="gift-advisor-panel" class="flex h-12 items-center gap-2 rounded-full bg-bloom-plum px-4 text-sm font-semibold text-white shadow-lg transition hover:bg-bloom-rose focus:outline-none focus:ring-2 focus:ring-bloom-rose focus:ring-offset-2">
        <x-customer.icon name="flower" class="h-5 w-5" />
        <span>Trợ lý BloomGift AI</span>
    </button>

    <section id="gift-advisor-panel" class="hidden fixed inset-x-3 bottom-20 flex h-[min(42rem,calc(100vh-6rem))] w-auto flex-col overflow-hidden rounded-2xl border border-bloom-line bg-white shadow-2xl sm:left-auto sm:right-5 sm:w-[26rem]" aria-labelledby="gift-advisor-title" role="dialog" aria-modal="false">
        <header class="flex items-start justify-between gap-3 border-b border-bloom-line bg-bloom-blush px-4 py-3">
            <div>
                <h2 id="gift-advisor-title" class="font-display text-lg font-semibold text-bloom-plum">Trợ lý BloomGift AI</h2>
                <p class="mt-0.5 text-xs text-bloom-muted">Tư vấn hoa &amp; quà phù hợp với bạn</p>
            </div>
            <div class="flex items-center gap-1">
                <button id="gift-advisor-new" type="button" class="rounded-full px-2 py-1 text-xs font-semibold text-bloom-muted transition hover:bg-white hover:text-bloom-plum">Chat mới</button>
                <button id="gift-advisor-close" type="button" class="rounded-full p-1.5 text-bloom-muted transition hover:bg-white hover:text-bloom-plum" aria-label="Đóng trợ lý">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="m6 6 12 12M18 6 6 18" /></svg>
                </button>
            </div>
        </header>

        <div id="gift-advisor-messages" class="min-h-0 flex-1 space-y-3 overflow-y-auto px-3 py-4" aria-live="polite">
            <div class="max-w-[92%] rounded-2xl rounded-tl-sm bg-bloom-blush px-3 py-2.5 text-sm leading-5 text-bloom-ink">
                <p>Xin chào! Mình là Trợ lý BloomGift AI 🌷</p>
                <p class="mt-1">Bạn muốn tặng hoa hoặc quà cho ai, vào dịp gì? Bạn cũng có thể cho mình biết ngân sách, màu sắc hoặc phong cách mong muốn.</p>
            </div>
        </div>
        <button id="gift-advisor-jump" type="button" class="hidden absolute bottom-24 left-1/2 -translate-x-1/2 rounded-full bg-bloom-plum px-3 py-1.5 text-xs font-semibold text-white shadow-lg">↓ Tin nhắn mới</button>

        <div class="border-t border-bloom-line bg-white px-3 py-3">
            <div class="mb-2 flex gap-1.5 overflow-x-auto pb-1 text-xs">
                @foreach (['Sinh nhật', 'Tặng người yêu', 'Tặng mẹ', 'Kỷ niệm', 'Khai trương', 'Cảm ơn'] as $prompt)
                    <button type="button" class="gift-advisor-quick shrink-0 rounded-full border border-bloom-line bg-bloom-canvas px-2.5 py-1.5 text-bloom-muted transition hover:border-bloom-rose hover:text-bloom-rose" data-query="{{ $prompt }}">{{ $prompt }}</button>
                @endforeach
            </div>
            <button id="gift-advisor-web-search" type="button" class="mb-2 rounded-full border border-bloom-line px-2.5 py-1.5 text-xs font-semibold text-bloom-plum transition hover:border-bloom-rose">🌐 Tìm thêm trên mạng</button>
            <form id="gift-advisor-form" class="flex gap-2">
                <label class="sr-only" for="gift-advisor-query">Nhắn nhu cầu của bạn cho Trợ lý BloomGift AI</label>
                <input id="gift-advisor-query" name="query" maxlength="500" required class="bloom-input h-10 min-w-0 flex-1 px-3 text-sm" placeholder="Ví dụ: Hoa sinh nhật cho bạn gái 500k">
                <button id="gift-advisor-send" class="bloom-button shrink-0 px-3 py-2 text-sm" type="submit">Gửi</button>
            </form>
        </div>
    </section>
</div>

<script>
    (() => {
        const form = document.getElementById('gift-advisor-form');
        const queryInput = document.getElementById('gift-advisor-query');
        const messages = document.getElementById('gift-advisor-messages');
        const panel = document.getElementById('gift-advisor-panel');
        const toggle = document.getElementById('gift-advisor-toggle');
        const close = document.getElementById('gift-advisor-close');
        const newChat = document.getElementById('gift-advisor-new');
        const jumpToLatest = document.getElementById('gift-advisor-jump');
        const webSearch = document.getElementById('gift-advisor-web-search');
        const send = document.getElementById('gift-advisor-send');
        if (!form || !queryInput || !messages || !panel || !toggle || !close || !send) return;

        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        const money = value => new Intl.NumberFormat('vi-VN').format(value) + '₫';
        // This state deliberately exists only in this browser page. Reloading
        // BloomGift starts a fresh chat and does not recover old messages.
        let conversation = [];
        let recentProductIds = [];
        let recentCustomConceptKeys = [];
        let rejectedSuggestions = [];
        let recentConcepts = [];
        let lastResponseItems = [];
        let selectedConcept = null;
        let selectedItem = null;
        let followLatest = true;

        function openPanel() {
            panel.classList.remove('hidden');
            toggle.setAttribute('aria-expanded', 'true');
            window.setTimeout(() => queryInput.focus(), 0);
        }

        function closePanel() {
            panel.classList.add('hidden');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.focus();
        }

        function bubble(text, role = 'assistant') {
            const message = document.createElement('article');
            message.className = role === 'customer'
                ? 'ml-auto max-w-[92%] rounded-2xl rounded-tr-sm bg-bloom-plum px-3 py-2.5 text-sm leading-5 text-white'
                : 'max-w-[92%] rounded-2xl rounded-tl-sm bg-bloom-blush px-3 py-2.5 text-sm leading-5 text-bloom-ink';
            const body = document.createElement('p');
            body.textContent = text;
            message.appendChild(body);
            messages.appendChild(message);
            if (followLatest) messages.scrollTop = messages.scrollHeight;
            else if (role === 'assistant') jumpToLatest.classList.remove('hidden');
            return message;
        }

        function action(label, href, className = 'bloom-button bloom-button--ghost bloom-button--small') {
            const link = document.createElement('a');
            link.className = className;
            link.href = href;
            link.textContent = label;
            return link;
        }

        function detailLine(label, value) {
            const line = document.createElement('p');
            line.className = 'text-[11px] leading-4 text-bloom-muted';
            line.textContent = label + ': ' + value;
            return line;
        }

        function resultCard(item) {
            const card = document.createElement('article');
            card.className = 'rounded-xl border border-bloom-line bg-white p-2.5';
            const top = document.createElement('div');
            top.className = 'flex gap-2.5';

            const image = document.createElement('img');
            image.className = 'h-14 w-14 shrink-0 rounded-lg object-cover';
            image.alt = item.name || 'Sản phẩm BloomGift';
            image.onerror = () => {
                const placeholder = document.createElement('div');
                placeholder.className = 'flex h-14 w-14 shrink-0 items-center justify-center rounded-lg bg-bloom-blush text-xl text-bloom-rose';
                placeholder.setAttribute('role', 'img');
                placeholder.setAttribute('aria-label', 'Chưa có ảnh cho ' + (item.name || 'sản phẩm này'));
                placeholder.textContent = '🌷';
                image.replaceWith(placeholder);
            };
            top.appendChild(image);
            if (item.image_url) {
                image.src = item.image_url;
            } else {
                image.onerror();
            }

            const copy = document.createElement('div');
            copy.className = 'min-w-0 flex-1';
            if (item.recommendation_badge) {
                const badge = document.createElement('p');
                badge.className = 'mb-1 inline-flex rounded-full bg-bloom-blush px-2 py-0.5 text-[10px] font-bold text-bloom-plum';
                badge.textContent = item.recommendation_badge;
                copy.appendChild(badge);
            }
            const title = document.createElement('h4');
            title.className = 'font-display text-sm font-semibold leading-5 text-bloom-plum';
            title.textContent = item.name;
            copy.appendChild(title);

            const availability = document.createElement('p');
            availability.className = 'mt-0.5 text-[11px] font-semibold ' + (item.available ? 'text-emerald-700' : 'text-amber-700');
            availability.textContent = item.availability;
            copy.appendChild(availability);

            if (item.price === null) {
                const customPrice = document.createElement('p');
                customPrice.className = 'mt-1 text-xs font-semibold text-bloom-ink';
                customPrice.textContent = 'Giá sẽ được BloomGift tư vấn theo thiết kế thực tế.';
                copy.appendChild(customPrice);
            } else {
                const hasSale = item.sale_price !== null && item.regular_price > item.sale_price;
                const price = document.createElement('p');
                price.className = 'mt-1 text-xs font-semibold text-bloom-ink';
                price.textContent = (hasSale ? 'Giá sale: ' : 'Giá: ') + money(item.price);
                if (hasSale) {
                    const regular = document.createElement('span');
                    regular.className = 'mt-0.5 block whitespace-nowrap text-[11px] font-normal text-bloom-muted line-through';
                    regular.textContent = 'Giá gốc: ' + money(item.regular_price);
                    price.appendChild(regular);
                }
                copy.appendChild(price);
            }

            top.appendChild(copy);
            card.appendChild(top);

            if (item.short_description || item.description) {
                const descriptionText = (item.short_description || item.description || '').trim();
                const normalizedTitle = (item.name || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '');
                const normalizedDescription = descriptionText.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '');
                if (descriptionText && normalizedDescription !== normalizedTitle) {
                    const description = document.createElement('p');
                    description.className = 'mt-2 text-xs leading-5 text-bloom-muted';
                    description.textContent = descriptionText;
                    card.appendChild(description);
                }
            }

            if (item.why_recommended) {
                const why = document.createElement('p');
                why.className = 'mt-2 rounded-lg bg-bloom-canvas px-2 py-1.5 text-xs leading-5 text-bloom-ink';
                why.textContent = 'Vì sao phù hợp: ' + item.why_recommended;
                card.appendChild(why);
            }

            const details = document.createElement('div');
            details.className = 'mt-2 grid gap-0.5 border-t border-bloom-line pt-2';
            details.hidden = true;
            const info = item.details || {};
            [
                ['Loại', info.type],
                ['Thành phần', info.components],
                ['Màu chủ đạo', info.color_palette || info.color],
                ['Dáng thiết kế', info.design_form],
                ['Cách gói / hộp', info.wrapping],
                ['Phong cách', info.style],
                ['Cảm giác tổng thể', info.visual_feeling],
                ['Dịp phù hợp', info.occasion],
                ['Người nhận', info.recipient],
                ['Loại quà', info.gift_type],
                ['Món chính', info.main_items],
                ['Món đi kèm', info.supporting_items],
                ['Vật chứa', info.container],
                ['Hoa', info.flowers],
                ['Quà', info.gifts],
                ['Cá nhân hoá', info.personalization],
                ['Ý nghĩa', info.meaning],
                ['Có thể tùy chỉnh', info.customization_options],
                ['Ngân sách', item.budget_fit],
            ].filter(([, value]) => value).forEach(([label, value]) => details.appendChild(detailLine(label, value)));
            card.appendChild(details);

            const actions = document.createElement('div');
            actions.className = 'mt-2 flex flex-wrap gap-2';
            const detailsToggle = document.createElement('button');
            detailsToggle.type = 'button';
            detailsToggle.className = 'bloom-button bloom-button--ghost bloom-button--small';
            detailsToggle.textContent = 'Xem chi tiết';
            detailsToggle.addEventListener('click', () => {
                details.hidden = !details.hidden;
                detailsToggle.textContent = details.hidden ? 'Xem chi tiết' : 'Thu gọn';
            });
            actions.appendChild(detailsToggle);
            if (item.product_url) actions.appendChild(action('Xem sản phẩm', item.product_url));
            if (item.available && item.cart_url) {
                const cartForm = document.createElement('form');
                cartForm.method = 'POST';
                cartForm.action = item.cart_url;
                const token = document.createElement('input');
                token.type = 'hidden';
                token.name = '_token';
                token.value = csrf;
                const quantity = document.createElement('input');
                quantity.type = 'hidden';
                quantity.name = 'quantity';
                quantity.value = '1';
                const cartButton = document.createElement('button');
                cartButton.type = 'submit';
                cartButton.className = 'bloom-button bloom-button--small';
                cartButton.textContent = 'Thêm vào giỏ';
                cartForm.append(token, quantity, cartButton);
                actions.appendChild(cartForm);
            }
            if (!item.available || item.kind === 'custom') actions.appendChild(action('Đặt theo yêu cầu', item.custom_order_url, 'bloom-button bloom-button--small'));
            if (item.kind === 'custom') {
                const edit = document.createElement('button');
                edit.type = 'button';
                edit.className = 'bloom-button bloom-button--ghost bloom-button--small';
                edit.textContent = 'Chỉnh mẫu này';
                edit.addEventListener('click', () => {
                    selectedItem = item;
                    selectedConcept = {
                        name: item.name,
                        recommendation_type: item.recommendation_type,
                        ...(item.details || {}),
                        design_description: item.details?.design_description || item.description,
                        why_recommended: item.why_recommended || '',
                    };
                    queryInput.placeholder = 'Bạn muốn chỉnh màu, dáng hay thành phần nào?';
                    bubble(`Mình đã chọn concept “${item.name}”. Bạn muốn thay đổi chi tiết nào?`);
                    queryInput.focus();
                });
                actions.appendChild(edit);
            } else if (item.kind === 'product') {
                const edit = document.createElement('button');
                edit.type = 'button';
                edit.className = 'bloom-button bloom-button--ghost bloom-button--small';
                edit.textContent = 'Tạo bản đặt riêng';
                edit.addEventListener('click', () => {
                    selectedItem = item;
                    selectedConcept = null;
                    queryInput.placeholder = 'Bạn muốn đổi tông màu, kiểu dáng hay thành phần nào?';
                    bubble(`Mẫu có sẵn “${item.name}” không đổi cấu hình trực tiếp. Mình có thể tạo một phương án đặt riêng dựa trên mẫu này.`);
                    queryInput.focus();
                });
                actions.appendChild(edit);
            }
            if (item.recommendation_key) {
                const reject = document.createElement('button');
                reject.type = 'button';
                reject.className = 'bloom-button bloom-button--ghost bloom-button--small';
                reject.textContent = 'Bỏ qua mẫu này';
                reject.addEventListener('click', () => {
                    if (!rejectedSuggestions.includes(item.recommendation_key)) rejectedSuggestions = [...rejectedSuggestions, item.recommendation_key].slice(-60);
                    reject.textContent = 'Đã bỏ qua';
                    reject.disabled = true;
                });
                actions.appendChild(reject);
            }
            card.appendChild(actions);

            if (item.kind === 'custom' && Array.isArray(item.sources) && item.sources.length) {
                const reference = document.createElement('p');
                reference.className = 'mt-2 text-[10px] leading-4 text-bloom-muted';
                reference.appendChild(document.createTextNode('Cảm hứng tham khảo: '));
                item.sources.slice(0, 2).forEach((source, index) => {
                    if (index) reference.appendChild(document.createTextNode(' · '));
                    const link = document.createElement('a');
                    link.href = source.url;
                    link.target = '_blank';
                    link.rel = 'noopener noreferrer';
                    link.className = 'underline';
                    link.textContent = source.domain || 'Nguồn tham khảo';
                    reference.appendChild(link);
                });
                card.appendChild(reference);
            }

            return card;
        }

        function resultSection(title, items, emptyMessage) {
            const section = document.createElement('section');
            section.className = 'mt-3 rounded-xl border border-bloom-line/80 bg-white/70 p-2.5';
            const heading = document.createElement('h4');
            heading.className = 'font-display text-sm font-semibold text-bloom-plum';
            heading.textContent = title;
            section.appendChild(heading);
            if (!items.length) {
                const empty = document.createElement('p');
                empty.className = 'mt-1 text-xs leading-5 text-bloom-muted';
                empty.textContent = emptyMessage;
                section.appendChild(empty);
            } else {
                const cards = document.createElement('div');
                cards.className = 'mt-2 space-y-2';
                items.forEach(item => cards.appendChild(resultCard(item)));
                section.appendChild(cards);
            }
            return section;
        }

        async function revealFinalText(element, text) {
            const paragraph = element.querySelector('p');
            if (paragraph.textContent === text) return;

            let current = paragraph.textContent;
            if (text.startsWith(current)) {
                const remainder = Array.from(text.slice(current.length));
                for (let index = 0; index < remainder.length; index += 14) {
                    paragraph.textContent += remainder.slice(index, index + 14).join('');
                    await new Promise(resolve => requestAnimationFrame(resolve));
                }
                return;
            }

            paragraph.textContent = '';
            const characters = Array.from(text);
            for (let index = 0; index < characters.length; index += 14) {
                paragraph.textContent = characters.slice(0, index + 14).join('');
                await new Promise(resolve => requestAnimationFrame(resolve));
            }
        }

        async function assistantResult(data, streamedMessage = null) {
            const finalMessage = data.message || data.guidance || 'Mình đã tìm được vài gợi ý phù hợp.';
            const message = streamedMessage || bubble(finalMessage);
            if (streamedMessage) await revealFinalText(message, finalMessage);
            if (data.web_search?.requested_by_ai && !data.web_search.active) {
                const note = document.createElement('p');
                note.className = 'mt-2 text-xs leading-5 text-bloom-muted';
                const searchReason = data.web_search.root_cause === 'missing_api_key'
                    ? 'BloomGift chưa cấu hình khóa truy cập dịch vụ tìm kiếm.'
                    : data.web_search.root_cause === 'authentication_failed'
                        ? 'Dịch vụ tìm kiếm từ chối xác thực.'
                        : data.web_search.root_cause === 'quota_exceeded'
                            ? 'Dịch vụ tìm kiếm đã hết hạn mức hiện tại.'
                            : 'Dịch vụ tìm kiếm hiện không trả về nguồn tham khảo.';
                note.textContent = `Lượt này chưa có kết quả web vì ${searchReason} Mình không gắn kiến thức sẵn có thành kết quả tìm kiếm.`;
                message.appendChild(note);
            }
            if (Array.isArray(data.quick_replies) && data.quick_replies.length) {
                const quickReplies = document.createElement('div');
                quickReplies.className = 'mt-2 flex flex-wrap gap-1.5';
                data.quick_replies.forEach(reply => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'rounded-full border border-bloom-line bg-white px-2.5 py-1 text-xs font-semibold text-bloom-plum hover:border-bloom-rose';
                    button.textContent = reply;
                    button.addEventListener('click', () => suggest(reply));
                    quickReplies.appendChild(button);
                });
                message.appendChild(quickReplies);
            }
            if (data.follow_up_question) {
                const followUp = document.createElement('p');
                followUp.className = 'mt-2 text-xs leading-5 text-bloom-ink';
                followUp.textContent = data.follow_up_question;
                message.appendChild(followUp);
            }
            if (data.recommendation_mode === 'selection') {
                if (data.selected_item) message.append(resultSection('Mẫu bạn chọn', [data.selected_item], ''));
                if (followLatest) messages.scrollTop = messages.scrollHeight;
                return;
            }
            if (data.recommendation_mode === 'ordinal_invalid' || data.recommendation_mode === 'selected_concept_edit') {
                if (data.recommendation_mode === 'selected_concept_edit' && data.selected_item) {
                    const title = data.selected_item.kind === 'custom' ? 'Concept đã chỉnh' : 'Phương án đặt riêng từ mẫu bạn chọn';
                    message.append(resultSection(title, [data.selected_item], ''));
                }
                if (followLatest) messages.scrollTop = messages.scrollHeight;
                return;
            }
            if (data.recommendation_mode === 'clarification' || data.recommendation_mode === 'direct_answer') {
                if (followLatest) messages.scrollTop = messages.scrollHeight;
                return;
            }
            if (data.intent?.target_entity && data.intent?.allow_category_expansion === false) {
                const exactMatches = data.in_store_suggestions || [];
                if (exactMatches.length) {
                    const entityLabel = data.intent.target_entity_label || data.intent.target_entity.replaceAll('_', ' ');
                    message.append(resultSection(`Mẫu ${entityLabel} có sẵn tại BloomGift`, exactMatches, ''));
                }
                if ((data.custom_suggestions || []).length) {
                    const entityLabel = data.intent.target_entity_label || data.intent.target_entity.replaceAll('_', ' ');
                    message.append(resultSection(`Thiết kế ${entityLabel} theo yêu cầu`, data.custom_suggestions, ''));
                }
                if (followLatest) messages.scrollTop = messages.scrollHeight;
                return;
            }
            const type = data.intent?.requested_type;
            const names = type === 'gift' ? ['Quà có sẵn tại BloomGift', 'Mẫu quà đặt theo yêu cầu']
                : type === 'set' ? ['Set có sẵn tại BloomGift', 'Set chưa có sẵn – đặt theo yêu cầu']
                : ['Hoa có sẵn tại BloomGift', 'Mẫu hoa chưa có sẵn – đặt theo yêu cầu'];
            message.append(
                resultSection(names[0], data.in_store_suggestions || [], data.product_catalog_exhausted ? 'Đã hết mẫu có sẵn khác khớp với yêu cầu trong cuộc trò chuyện này.' : 'BloomGift chưa có mẫu sẵn khớp hoàn toàn.'),
                resultSection(names[1], data.custom_suggestions || [], 'Bạn có thể gửi yêu cầu riêng để BloomGift tư vấn.')
            );
            if (followLatest) messages.scrollTop = messages.scrollHeight;
        }

        function fallback(message) {
            const reply = bubble(message || 'Trợ lý đang tạm thời không thể kết nối. Bạn vẫn có thể xem sản phẩm hoặc gửi yêu cầu riêng.');
            const actions = document.createElement('div');
            actions.className = 'mt-3 flex flex-wrap gap-2';
            actions.append(
                action('Xem sản phẩm', '{{ route('products.index') }}'),
                action('Đặt theo yêu cầu', '{{ route('custom.order') }}', 'bloom-button bloom-button--small')
            );
            reply.appendChild(actions);
            messages.scrollTop = messages.scrollHeight;
        }

        async function suggest(query = queryInput.value.trim()) {
            if (!query) return;
            openPanel();
            bubble(query, 'customer');
            queryInput.value = '';
            queryInput.disabled = true;
            send.disabled = true;
            const loading = bubble('BloomGift AI đang tìm những lựa chọn phù hợp...');

            try {
                if (/mẫu khác|loại khác|khác hẳn|tìm thêm|quà khác đi/i.test(query)) { selectedConcept = null; selectedItem = null; }
                const response = await fetch('{{ route('gift-advisor.suggest') }}', {
                    method: 'POST',
                    headers: { 'Accept': 'text/event-stream, application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ query, context: conversation.slice(-60), last_response_items: lastResponseItems, selected_item: selectedItem, recent_product_ids: recentProductIds, recent_custom_concept_keys: recentCustomConceptKeys, rejected_suggestions: rejectedSuggestions, recent_concept_names: recentConcepts.map(concept => concept.name).filter(Boolean).slice(0, 12), selected_concept: selectedConcept }),
                });
                let data = null;
                let streamedMessage = null;
                if (response.headers.get('content-type')?.includes('text/event-stream')) {
                    const reader = response.body.getReader();
                    const decoder = new TextDecoder();
                    let buffer = '';
                    while (true) {
                        const { value, done } = await reader.read();
                        buffer += decoder.decode(value || new Uint8Array(), { stream: !done });
                        let boundary;
                        while ((boundary = buffer.indexOf('\n\n')) >= 0) {
                            const frame = buffer.slice(0, boundary);
                            buffer = buffer.slice(boundary + 2);
                            const line = frame.split('\n').find(item => item.startsWith('data:'));
                            if (!line) continue;
                            const event = JSON.parse(line.slice(5).trim());
                            if (event.event === 'text') {
                                if (!streamedMessage) {
                                    loading.remove();
                                    streamedMessage = bubble('');
                                }
                                streamedMessage.querySelector('p').textContent += event.data.delta || '';
                                // Yield a paint opportunity after each real SSE
                                // delta so successive chunks become visible as
                                // they arrive instead of only after the loop ends.
                                await new Promise(resolve => requestAnimationFrame(resolve));
                            } else if (event.event === 'result') {
                                data = event.data;
                            } else if (event.event === 'error') {
                                throw new Error(event.data.message || 'Trợ lý đang gặp sự cố.');
                            }
                        }
                        if (done) break;
                    }
                    if (!data) throw new Error('Kết nối tư vấn đã kết thúc trước khi có kết quả.');
                } else {
                    data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'Không thể gửi yêu cầu tư vấn.');
                }
                loading.remove();
                conversation = [...conversation, query].slice(-60);
                recentProductIds = Array.from(new Set([...(recentProductIds || []), ...(Array.isArray(data.recent_product_ids) ? data.recent_product_ids : [])])).slice(-60);
                recentCustomConceptKeys = Array.from(new Set([...(recentCustomConceptKeys || []), ...(Array.isArray(data.recent_custom_concept_keys) ? data.recent_custom_concept_keys : [])])).slice(-60);
                recentConcepts = Array.isArray(data.suggestions) ? data.suggestions.slice(0, 6) : [];
                lastResponseItems = Array.isArray(data.last_response_items) ? data.last_response_items : recentConcepts;
                if (Object.prototype.hasOwnProperty.call(data, 'selected_item')) selectedItem = data.selected_item;
                if (Object.prototype.hasOwnProperty.call(data, 'selected_concept')) selectedConcept = data.selected_concept;
                await assistantResult(data, streamedMessage);
            } catch (error) {
                loading.remove();
                fallback(error.message);
            } finally {
                queryInput.disabled = false;
                send.disabled = false;
                queryInput.focus();
            }
        }

        messages.addEventListener('scroll', () => {
            followLatest = messages.scrollHeight - messages.scrollTop - messages.clientHeight < 100;
            if (followLatest) jumpToLatest.classList.add('hidden');
        });
        jumpToLatest.addEventListener('click', () => {
            followLatest = true;
            messages.scrollTop = messages.scrollHeight;
            jumpToLatest.classList.add('hidden');
        });

        form.addEventListener('submit', event => {
            event.preventDefault();
            suggest();
        });
        toggle.addEventListener('click', () => panel.classList.contains('hidden') ? openPanel() : closePanel());
        close.addEventListener('click', closePanel);
        newChat?.addEventListener('click', () => {
            conversation = [];
            recentProductIds = [];
            recentCustomConceptKeys = [];
            rejectedSuggestions = [];
            recentConcepts = [];
            lastResponseItems = [];
            selectedConcept = null;
            selectedItem = null;
            followLatest = true;
            queryInput.placeholder = 'Ví dụ: Hoa sinh nhật cho bạn gái 500k';
            jumpToLatest.classList.add('hidden');
            messages.replaceChildren();
            bubble('Xin chào! Mình là Trợ lý BloomGift AI 🌷 Bạn muốn tặng hoa hoặc quà cho ai, vào dịp gì?', 'assistant');
            queryInput.focus();
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && !panel.classList.contains('hidden')) closePanel();
        });
        document.querySelectorAll('.gift-advisor-quick').forEach(button => button.addEventListener('click', () => suggest(button.dataset.query)));
        webSearch.addEventListener('click', () => {
            selectedConcept = null;
            suggest('Tìm thêm trên mạng');
        });
    })();
</script>
