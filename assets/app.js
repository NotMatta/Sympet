import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';

const updateCartBadge = (count) => {
    document.querySelectorAll('[data-cart-count]').forEach((badge) => {
        badge.textContent = `${count}`;
        badge.classList.toggle('hidden', count <= 0);
    });
};

const updateSavedBadge = (count) => {
    document.querySelectorAll('[data-saved-count]').forEach((badge) => {
        badge.textContent = `${count}`;
        badge.classList.toggle('hidden', count <= 0);
    });
};

const showToast = (message, type = 'success') => {
    const toast = document.createElement('div');
    toast.className = `fixed right-4 top-4 z-50 rounded-md px-4 py-3 text-sm font-medium shadow-lg transition ${
        type === 'error' ? 'bg-red-600 text-white' : 'bg-emerald-600 text-white'
    }`;
    toast.textContent = message;
    document.body.appendChild(toast);

    window.setTimeout(() => {
        toast.remove();
    }, 2200);
};

const wireAddToCartForms = () => {
    document.querySelectorAll('.js-add-to-cart').forEach((form) => {
        if (form.dataset.cartBound === '1') {
            return;
        }
        form.dataset.cartBound = '1';

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const submitButton = form.querySelector('button[type="submit"]');
            const defaultLabel = submitButton?.dataset.defaultLabel ?? 'Add to cart';
            const addedLabel = submitButton?.dataset.addedLabel ?? 'Added!';

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                    body: new FormData(form),
                });
                const payload = await response.json();

                if (!response.ok || !payload.success) {
                    showToast(payload.message ?? 'Unable to add product to cart.', 'error');
                    return;
                }

                updateCartBadge(payload.cartCount ?? 0);
                showToast(payload.message ?? 'Added to cart.');

                if (submitButton) {
                    submitButton.textContent = addedLabel;
                    window.setTimeout(() => {
                        submitButton.textContent = defaultLabel;
                    }, 1200);
                }
            } catch {
                showToast('Network error. Please try again.', 'error');
            }
        });
    });
};

const wireSaveForms = () => {
    document.querySelectorAll('.js-save-item').forEach((form) => {
        if (form.dataset.saveBound === '1') {
            return;
        }
        form.dataset.saveBound = '1';

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = form.querySelector('button[type="submit"]');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                    body: new FormData(form),
                });
                const payload = await response.json();

                if (!response.ok) {
                    showToast(payload.message ?? 'Could not update saved items.', 'error');
                    return;
                }

                updateSavedBadge(payload.savedItemsCount ?? 0);
                showToast(payload.saved ? 'Item saved.' : 'Item removed from saved.');

                if (button) {
                    button.dataset.saved = payload.saved ? '1' : '0';
                    const icon = button.querySelector('svg');
                    if (icon) {
                        icon.innerHTML = payload.saved
                            ? '<path fill="currentColor" d="m12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5C2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3C19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54z"/>'
                            : '<path fill="currentColor" d="m12.1 18.55l-.1.1l-.11-.1C7.14 14.24 4 11.39 4 8.5C4 6.5 5.5 5 7.5 5c1.54 0 3.04.99 3.57 2.36h1.87C13.46 5.99 14.96 5 16.5 5C18.5 5 20 6.5 20 8.5c0 2.89-3.14 5.74-7.9 10.05m4.4-15.55c-1.74 0-3.41.81-4.5 2.09C10.91 3.81 9.24 3 7.5 3C4.42 3 2 5.42 2 8.5c0 3.78 3.4 6.86 8.55 12.54L12 22.35l1.45-1.32C18.6 15.36 22 12.28 22 8.5C22 5.42 19.58 3 16.5 3"/>';
                    }
                }
            } catch {
                showToast('Network error. Please try again.', 'error');
            }
        });
    });
};

document.addEventListener('DOMContentLoaded', () => {
    wireAddToCartForms();
    wireSaveForms();
});
