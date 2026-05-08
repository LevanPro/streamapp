@once
    <div id="resourcePreviewModal" style="display:none; position:fixed; inset:0; z-index:1200; background:rgba(3, 11, 18, 0.65); padding:18px;">
        <div class="panel" style="width:min(960px, 98vw); margin:0 auto; max-height:95vh; display:flex; flex-direction:column;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; padding:12px 14px; border-bottom:1px solid var(--line);">
                <strong id="resourcePreviewTitle">Preview</strong>
                <button type="button" id="resourcePreviewClose" class="btn btn-outline" style="padding:6px 10px;">Close</button>
            </div>
            <div style="padding:12px 14px; overflow:auto; min-height:240px;">
                <pre id="resourcePreviewContent" style="margin:0; font-size:0.9rem; line-height:1.45; white-space:pre-wrap; word-break:break-word;"></pre>
            </div>
            <div id="resourcePreviewMeta" class="muted" style="padding:8px 14px 12px; font-size:0.8rem;"></div>
        </div>
    </div>

    <script>
        (() => {
            if (window.__resourcePreviewBound) return;
            window.__resourcePreviewBound = true;

            const modal = document.getElementById('resourcePreviewModal');
            const closeButton = document.getElementById('resourcePreviewClose');
            const titleNode = document.getElementById('resourcePreviewTitle');
            const contentNode = document.getElementById('resourcePreviewContent');
            const metaNode = document.getElementById('resourcePreviewMeta');

            if (!modal || !closeButton || !titleNode || !contentNode || !metaNode) return;

            const closeModal = () => {
                modal.style.display = 'none';
            };

            const openModal = () => {
                modal.style.display = 'block';
            };

            closeButton.addEventListener('click', closeModal);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) closeModal();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.style.display === 'block') closeModal();
            });

            document.addEventListener('click', async (event) => {
                const trigger = event.target.closest('.js-resource-preview');
                if (!trigger) return;

                event.preventDefault();
                const url = trigger.getAttribute('data-preview-url');
                const title = trigger.getAttribute('data-resource-title') || 'Preview';
                if (!url) return;

                titleNode.textContent = title;
                contentNode.textContent = 'Loading...';
                metaNode.textContent = '';
                openModal();

                try {
                    const response = await fetch(url, {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' },
                    });

                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        contentNode.textContent = payload.error || 'Unable to preview this file.';
                        metaNode.textContent = '';
                        return;
                    }

                    contentNode.textContent = payload.content || '';
                    if (payload.error) {
                        metaNode.textContent = payload.error;
                    } else if (payload.truncated) {
                        metaNode.textContent = 'Showing first 512 KB of file.';
                    } else {
                        metaNode.textContent = 'Full file preview.';
                    }
                } catch (error) {
                    contentNode.textContent = 'Preview request failed.';
                    metaNode.textContent = '';
                }
            });
        })();
    </script>
@endonce
