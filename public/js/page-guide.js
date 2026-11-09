(() => {
    const guide = window.madarPageGuide;
    const guideUrl = window.madarPageGuideUrl;

    if (!guide || !guideUrl || !document.body) {
        return;
    }

    const trigger = document.createElement('button');
    trigger.className = 'page-guide-trigger';
    trigger.type = 'button';
    trigger.textContent = 'شرح الصفحة';
    trigger.setAttribute('aria-haspopup', 'dialog');

    const dialog = document.createElement('dialog');
    dialog.className = 'page-guide-dialog';
    dialog.setAttribute('aria-labelledby', 'page-guide-title');

    const content = document.createElement('div');
    content.className = 'page-guide-content';

    const header = document.createElement('div');
    header.className = 'page-guide-header';

    const headingGroup = document.createElement('div');
    const heading = document.createElement('h2');
    heading.id = 'page-guide-title';
    heading.textContent = `شرح صفحة ${guide.title}`;

    const summary = document.createElement('p');
    summary.textContent = guide.summary;
    headingGroup.append(heading, summary);

    const close = document.createElement('button');
    close.className = 'page-guide-close';
    close.type = 'button';
    close.textContent = '×';
    close.setAttribute('aria-label', 'إغلاق الشرح');
    header.append(headingGroup, close);

    const featuresHeading = document.createElement('h3');
    featuresHeading.textContent = 'ماذا يمكنك أن تفعل هنا؟';

    const featureList = document.createElement('ul');
    featureList.className = 'page-guide-list';
    guide.features.forEach((feature) => {
        const item = document.createElement('li');
        item.textContent = feature;
        featureList.append(item);
    });

    const scriptHeading = document.createElement('h3');
    scriptHeading.textContent = 'نص مقترح للفيديو';

    const script = document.createElement('p');
    script.className = 'page-guide-script';
    script.textContent = guide.script;

    const fullGuide = document.createElement('a');
    fullGuide.className = 'page-guide-link';
    fullGuide.href = guideUrl;
    fullGuide.textContent = 'فتح دليل كل الصفحات';

    content.append(header, featuresHeading, featureList, scriptHeading, script, fullGuide);
    dialog.append(content);
    document.body.append(trigger, dialog);

    trigger.addEventListener('click', () => dialog.showModal());
    close.addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });
})();
