(() => {
    const forms = document.querySelectorAll('form[enctype="multipart/form-data"]');

    const formatDuration = (seconds) => {
        if (!Number.isFinite(seconds)) {
            return 'جارٍ التقدير';
        }

        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;

        if (minutes === 0) {
            return `${remainingSeconds} ثانية`;
        }

        return remainingSeconds === 0
            ? `${minutes} دقيقة`
            : `${minutes} دقيقة و${remainingSeconds} ثانية`;
    };

    const overlay = document.createElement('div');
    overlay.className = 'media-loading-overlay';
    overlay.setAttribute('role', 'status');
    overlay.setAttribute('aria-live', 'polite');
    overlay.innerHTML = `
        <div class="media-loading-overlay__spinner" aria-hidden="true"></div>
        <div class="media-loading-overlay__status" data-overlay-status>جارٍ التحميل...</div>
        <div class="media-loading-overlay__hint" data-overlay-hint></div>
    `;
    document.body.appendChild(overlay);

    const overlayStatus = overlay.querySelector('[data-overlay-status]');
    const overlayHint = overlay.querySelector('[data-overlay-hint]');

    const showOverlay = () => overlay.classList.add('is-visible');
    const hideOverlay = () => overlay.classList.remove('is-visible');
    const setOverlayStatus = (text) => {
        overlayStatus.textContent = text;
    };
    const setOverlayHint = (text) => {
        overlayHint.textContent = text;
    };

    forms.forEach((form) => {
        const progress = form.querySelector('[data-upload-progress]');
        if (!progress) {
            return;
        }

        const bar = progress.querySelector('[data-upload-progress-bar]');
        const status = progress.querySelector('[data-upload-status]');
        const percent = progress.querySelector('[data-upload-percent]');
        const elapsedLabel = progress.querySelector('[data-upload-elapsed]');
        const remainingLabel = progress.querySelector('[data-upload-remaining]');
        const error = progress.querySelector('[data-upload-error]');
        let uploading = false;

        const uploadInChunks = async (file, submitButtons, previousDisabledStates, uploadUrl) => {
            const chunkSize = 8 * 1024 * 1024;
            const totalChunks = Math.ceil(file.size / chunkSize);
            const csrfToken = form.querySelector('input[name="_token"]')?.value;
            const startedAt = performance.now();

            const setProgress = (uploadedBytes) => {
                const loadedPercent = Math.min(99, Math.floor((uploadedBytes / file.size) * 100));
                const elapsedSeconds = Math.max(1, Math.floor((performance.now() - startedAt) / 1000));
                const bytesPerSecond = uploadedBytes / elapsedSeconds;
                const remainingSeconds = bytesPerSecond > 0
                    ? Math.ceil((file.size - uploadedBytes) / bytesPerSecond)
                    : Number.POSITIVE_INFINITY;

                bar.value = loadedPercent;
                percent.textContent = `${loadedPercent}%`;
                elapsedLabel.textContent = `الوقت المنقضي: ${formatDuration(elapsedSeconds)}`;
                remainingLabel.textContent = `الوقت المتبقي: ${formatDuration(remainingSeconds)}`;
                status.textContent = `جارٍ رفع الفيديو... ${loadedPercent}%`;
                setOverlayStatus(`جارٍ رفع الفيديو... ${loadedPercent}%`);
                setOverlayHint(`الوقت المتبقي: ${formatDuration(remainingSeconds)}`);
            };

            const responseMessage = (data, statusCode) => {
                const validationMessage = data.errors
                    ? Object.values(data.errors).flat()[0]
                    : null;

                if (statusCode === 413) {
                    return 'الخادم رفض جزءًا من الفيديو. يلزم زيادة حد الطلبات في إعدادات الخادم.';
                }

                return validationMessage
                    || data.message
                    || `تعذر إتمام رفع الفيديو (رمز الخطأ ${statusCode}). أرسل الرمز للدعم إذا تكرر الخطأ.`;
            };

            const sendJson = async (url, payload) => {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });
                if (response.redirected) {
                    throw new Error('انتهت صلاحية الجلسة. حدّث الصفحة ثم سجّل الدخول وحاول مرة أخرى.');
                }

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(responseMessage(data, response.status));
                }

                return data;
            };

            const sendChunk = (url, chunk, chunkIndex, uploadedBeforeChunk) => new Promise((resolve, reject) => {
                const request = new XMLHttpRequest();
                const body = new FormData();
                body.append('video_chunk', chunk, file.name);
                body.append('chunk_index', String(chunkIndex));
                body.append('total_chunks', String(totalChunks));
                request.open('POST', url);
                request.setRequestHeader('Accept', 'application/json');
                request.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                request.upload.addEventListener('progress', (event) => {
                    if (event.lengthComputable) {
                        setProgress(uploadedBeforeChunk + Math.min(event.loaded, chunk.size));
                    }
                });

                request.addEventListener('load', () => {
                    let data = {};
                    try {
                        data = JSON.parse(request.responseText);
                    } catch {
                        data = {};
                    }

                    if (
                        request.status < 200
                        || request.status >= 300
                        || !request.getResponseHeader('Content-Type')?.includes('application/json')
                    ) {
                        reject(new Error(responseMessage(data, request.status)));
                        return;
                    }

                    resolve(data);
                });

                request.addEventListener('error', () => reject(new Error('انقطع الاتصال أثناء رفع جزء من الفيديو. حاول مرة أخرى.')));
                request.addEventListener('abort', () => reject(new Error('تم إيقاف رفع الفيديو.')));
                request.send(body);
            });

            const restoreButtons = () => {
                submitButtons.forEach((button, index) => {
                    button.disabled = previousDisabledStates[index];
                });
                uploading = false;
            };

            try {
                if (!file.size) {
                    throw new Error('ملف الفيديو فارغ.');
                }

                progress.hidden = false;
                error.hidden = true;
                bar.value = 0;
                percent.textContent = '0%';
                status.textContent = 'جارٍ تجهيز الرفع...';
                remainingLabel.textContent = 'الوقت المتبقي: جارٍ التقدير';
                showOverlay();
                setOverlayStatus('جارٍ تجهيز رفع الفيديو...');
                setOverlayHint('سيُرفع الفيديو على أجزاء صغيرة وآمنة');

                const courseId = form.querySelector('[name="course_id"]')?.value;
                const startUrl = uploadUrl.replace('__COURSE_ID__', encodeURIComponent(courseId || ''));
                const values = new FormData(form);
                const upload = await sendJson(startUrl, {
                    title: values.get('title'),
                    content: values.get('content'),
                    order: values.get('order'),
                    video_duration_seconds: values.get('video_duration_seconds'),
                });
                const uploadUrlForChunk = `${startUrl}/${encodeURIComponent(upload.upload_id)}/chunks`;

                for (let index = 0; index < totalChunks; index++) {
                    const start = index * chunkSize;
                    const chunk = file.slice(start, Math.min(start + chunkSize, file.size));
                    await sendChunk(uploadUrlForChunk, chunk, index, start);
                }

                status.textContent = 'اكتمل الرفع، جارٍ تجميع الفيديو وحفظ المحاضرة...';
                setOverlayStatus('اكتمل رفع الفيديو، جارٍ تجهيزه...');
                setOverlayHint('قد تستغرق هذه الخطوة بعض الوقت للملفات الكبيرة');
                const completed = await sendJson(
                    `${startUrl}/${encodeURIComponent(upload.upload_id)}/complete`,
                    {},
                );

                bar.value = 100;
                percent.textContent = '100%';
                setOverlayHint('جارٍ فتح قائمة المحاضرات...');
                window.location.assign(completed.redirect || form.action);
            } catch (uploadError) {
                hideOverlay();
                progress.hidden = false;
                status.textContent = 'لم يكتمل رفع الفيديو';
                error.textContent = uploadError instanceof Error
                    ? uploadError.message
                    : 'تعذر إتمام رفع الفيديو. حاول مرة أخرى.';
                error.hidden = false;
                restoreButtons();
            }
        };

        form.addEventListener('submit', (event) => {
            if (uploading) {
                event.preventDefault();
                return;
            }

            const files = Array.from(form.querySelectorAll('input[type="file"]'))
                .flatMap((input) => Array.from(input.files || []));
            if (files.length === 0) {
                return;
            }

            const uploadUrl = form.dataset.chunkUploadUrl
                || form.dataset.chunkUploadUrlTemplate;
            if (uploadUrl) {
                event.preventDefault();
                uploading = true;
                const submitButtons = Array.from(form.querySelectorAll(
                    'button[type="submit"], input[type="submit"]',
                ));
                const previousDisabledStates = submitButtons.map((button) => button.disabled);
                submitButtons.forEach((button) => {
                    button.disabled = true;
                });
                void uploadInChunks(files[0], submitButtons, previousDisabledStates, uploadUrl);
                return;
            }

            event.preventDefault();
            uploading = true;
            progress.hidden = true;
            error.hidden = true;
            showOverlay();
            setOverlayStatus('جارٍ رفع الملفات...');
            setOverlayHint('الرجاء عدم إغلاق الصفحة أو الانتقال إليها');
            bar.value = 0;
            percent.textContent = '0%';
            status.textContent = 'جارٍ رفع الملفات...';
            remainingLabel.textContent = 'الوقت المتبقي: جارٍ التقدير';

            const submitButtons = Array.from(form.querySelectorAll(
                'button[type="submit"], input[type="submit"]',
            ));
            const previousDisabledStates = submitButtons.map((button) => button.disabled);
            submitButtons.forEach((button) => {
                button.disabled = true;
            });

            const startTime = performance.now();
            const request = new XMLHttpRequest();
            request.open(form.method || 'POST', form.action);
            request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            request.setRequestHeader('Accept', 'text/html');

            const restoreButtons = () => {
                submitButtons.forEach((button, index) => {
                    button.disabled = previousDisabledStates[index];
                });
                uploading = false;
            };

            request.upload.addEventListener('progress', (progressEvent) => {
                if (!progressEvent.lengthComputable || progressEvent.total === 0) {
                    return;
                }

                const loadedPercent = Math.min(
                    100,
                    Math.round((progressEvent.loaded / progressEvent.total) * 100),
                );
                const elapsedSeconds = Math.max(
                    1,
                    Math.floor((performance.now() - startTime) / 1000),
                );
                const bytesPerSecond = progressEvent.loaded / elapsedSeconds;
                const remainingSeconds = bytesPerSecond > 0
                    ? Math.ceil((progressEvent.total - progressEvent.loaded) / bytesPerSecond)
                    : Number.POSITIVE_INFINITY;

                bar.value = loadedPercent;
                percent.textContent = `${loadedPercent}%`;
                elapsedLabel.textContent = `الوقت المنقضي: ${formatDuration(elapsedSeconds)}`;
                if (loadedPercent === 100) {
                    status.textContent = 'وصلت الملفات، جارٍ حفظ البيانات...';
                    remainingLabel.textContent = 'الوقت المتبقي: بانتظار تأكيد الخادم';
                    setOverlayStatus('جارٍ حفظ البيانات...');
                    setOverlayHint('بانتظار تأكيد الخادم');
                } else {
                    remainingLabel.textContent = `الوقت المتبقي: ${formatDuration(remainingSeconds)}`;
                    setOverlayStatus(`جارٍ رفع الملفات... ${loadedPercent}%`);
                    setOverlayHint(`الوقت المتبقي: ${formatDuration(remainingSeconds)}`);
                }
            });

            request.addEventListener('load', () => {
                if (request.status < 200 || request.status >= 400) {
                    restoreButtons();
                    hideOverlay();
                    progress.hidden = false;
                    error.textContent = request.status === 413
                        ? 'حجم الملف تجاوز الحد الذي تسمح به إعدادات الخادم. تواصل مع إدارة الموقع لزيادة الحد.'
                        : request.status === 422
                            ? 'تعذر قبول بيانات المحاضرة. تحقق من نوع الفيديو ومدته والحقول المطلوبة.'
                            : request.status === 419
                                ? 'انتهت صلاحية الجلسة. حدّث الصفحة ثم حاول الرفع مرة أخرى.'
                                : `تعذر إتمام الرفع (رمز الخطأ ${request.status}). حاول مرة أخرى، وإذا تكرر الخطأ أرسل الرمز للدعم.`;
                    error.hidden = false;
                    status.textContent = 'لم يكتمل الرفع';
                    return;
                }

                bar.value = 100;
                percent.textContent = '100%';
                status.textContent = 'اكتمل الرفع، جارٍ حفظ البيانات...';
                remainingLabel.textContent = 'الوقت المتبقي: اكتمل الرفع';
                setOverlayStatus('اكتمل الرفع، جارٍ حفظ البيانات...');
                setOverlayHint('جارٍ تحويلك للصفحة...');
                window.location.assign(request.responseURL || window.location.href);
            });

            request.addEventListener('error', () => {
                restoreButtons();
                hideOverlay();
                progress.hidden = false;
                error.textContent = 'انقطع الاتصال قبل اكتمال الرفع. حاول مرة أخرى.';
                error.hidden = false;
                status.textContent = 'لم يكتمل الرفع';
            });

            request.addEventListener('abort', () => {
                restoreButtons();
                hideOverlay();
                progress.hidden = false;
                error.textContent = 'تم إيقاف الرفع قبل اكتماله.';
                error.hidden = false;
                status.textContent = 'تم إيقاف الرفع';
            });

            request.send(new FormData(form));
        });
    });
})();
