<script>
    (() => {
        if (window.__blogAiImageGeneratorBound) {
            return;
        }

        window.__blogAiImageGeneratorBound = true;

        const route = @json($route);
        const postId = @json($postId);

        const getContentValue = () => {
            if (window.EDITOR?.CKEDITOR?.content && typeof window.EDITOR.CKEDITOR.content.getData === 'function') {
                return window.EDITOR.CKEDITOR.content.getData();
            }

            return document.getElementById('content')?.value
                || document.querySelector('[name="content"]')?.value
                || '';
        };

        const syncImageBox = (slot, path, url) => {
            const box = document.querySelector(`.image-box-${slot}`);

            if (!box) {
                return;
            }

            const input = box.querySelector('input.image-data');
            const preview = box.querySelector('img.preview-image');
            const removeButton = box.querySelector('[data-bb-toggle="image-picker-remove"]');

            if (input) {
                input.value = path;
            }

            if (preview) {
                preview.src = url;
                preview.classList.remove('default-image');
            }

            if (removeButton) {
                removeButton.style.display = '';
            }
        };

        document.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-bb-blog-ai-generate]');

            if (!button) {
                return;
            }

            event.preventDefault();

            const slot = button.dataset.slot;
            const title = document.querySelector('input[name="name"]')?.value?.trim() || '';

            if (!title) {
                Botble.showError('Enter the blog post title before generating an image.');

                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            Botble.showButtonLoading(button);

            try {
                const response = await fetch(route, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        post_id: postId,
                        slot_type: slot,
                        title,
                        description: document.querySelector('[name="description"]')?.value || '',
                        content: getContentValue(),
                    }),
                });

                const result = await response.json();

                if (!response.ok || result.error) {
                    throw new Error(result.message || 'Image generation failed.');
                }

                syncImageBox(slot, result.data.path, result.data.url);
                Botble.showSuccess(result.message || 'Image generated successfully.');
            } catch (error) {
                Botble.showError(error.message || 'Image generation failed.');
            } finally {
                Botble.hideButtonLoading(button);
            }
        });
    })();
</script>
