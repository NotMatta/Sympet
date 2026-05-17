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

document.addEventListener('DOMContentLoaded', () => {
    wireAddToCartForms();
});
