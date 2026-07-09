(function () {
    if (typeof melodiqEvents === 'undefined') {
        return;
    }

    const bindLikeButtons = (selector, config) => {
        const buttons = document.querySelectorAll(selector);

        if (!buttons.length) {
            return;
        }

        buttons.forEach((button) => {
            const postId = button.getAttribute(config.idAttribute);
            const storageKey = `${config.storagePrefix}_${postId}`;
            const count = button.querySelector('b');

            if (!postId) {
                return;
            }

            const setButtonState = (targetButton, likes, isLiked) => {
                const targetCount = targetButton.querySelector('b');
                const targetIcon = targetButton.querySelector('span');

                if (targetCount && typeof likes !== 'undefined') {
                    targetCount.textContent = likes;
                }

                if (targetIcon) {
                    targetIcon.textContent = isLiked ? '♥' : '♡';
                }

                targetButton.classList.toggle('is-liked', isLiked);
                targetButton.setAttribute('aria-pressed', isLiked ? 'true' : 'false');
            };
            const syncButtons = (likes, isLiked) => {
                document.querySelectorAll(`${selector}[${config.idAttribute}="${postId}"]`).forEach((targetButton) => {
                    setButtonState(targetButton, likes, isLiked);
                });
            };

            if (window.localStorage.getItem(storageKey)) {
                setButtonState(button, count ? count.textContent : undefined, true);
            } else {
                setButtonState(button, count ? count.textContent : undefined, false);
            }

            button.addEventListener('click', () => {
                if (button.classList.contains('is-loading')) {
                    return;
                }

                const isLiked = Boolean(window.localStorage.getItem(storageKey));
                const formData = new FormData();
                formData.append('action', config.action);
                formData.append('nonce', config.nonce);
                formData.append('post_id', postId);
                formData.append('direction', isLiked ? 'unlike' : 'like');

                button.classList.add('is-loading');

                fetch(melodiqEvents.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData,
                })
                    .then((response) => response.json())
                    .then((response) => {
                        if (!response.success || !response.data) {
                            return;
                        }

                        syncButtons(response.data.likes, response.data.liked);

                        if (response.data.liked) {
                            window.localStorage.setItem(storageKey, '1');
                        } else {
                            window.localStorage.removeItem(storageKey);
                        }
                    })
                    .finally(() => {
                        button.classList.remove('is-loading');
                    });
            });
        });
    };

    bindLikeButtons('.event-like-button[data-event-id]', {
        idAttribute: 'data-event-id',
        storagePrefix: 'melodiq_event_liked',
        action: 'melodiq_like_event',
        nonce: melodiqEvents.eventNonce || melodiqEvents.nonce,
    });

    bindLikeButtons('.artist-like-button[data-artist-id]', {
        idAttribute: 'data-artist-id',
        storagePrefix: 'melodiq_artist_liked',
        action: 'melodiq_like_artist',
        nonce: melodiqEvents.artistNonce,
    });
}());
