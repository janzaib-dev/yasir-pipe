import axios from 'axios';

/**
 * Higher-Order Function to enhance a form with AJAX validation and handling.
 * 
 * @param {HTMLFormElement|string} formTarget - The form element or selector string
 * @param {Object} options - Custom configuration options
 * @returns {Object} - Controller object with destroy method
 */
export function withAjaxValidation(formTarget, options = {}) {
    const form = typeof formTarget === 'string' ? document.querySelector(formTarget) : formTarget;

    if (!form) {
        console.warn('Form not found for AJAX validation:', formTarget);
        return;
    }

    const config = {
        validateOnBlur: true, // Validate individual fields on blur
        errorClass: 'is-invalid', // Bootstrap error class
        feedbackClass: 'invalid-feedback', // Bootstrap feedback class
        submitBtnSelector: '[type="submit"]', 
        loadingText: '<i class="fa fa-spinner fa-spin"></i> Processing...',
        resetOnSuccess: false,
        ...options
    };

    // Store original button content to restore later
    const submitBtn = form.querySelector(config.submitBtnSelector);
    let originalBtnContent = submitBtn ? submitBtn.innerHTML : 'Submit';

    // --- Helper Functions ---

    const clearErrors = () => {
        form.querySelectorAll(`.${config.errorClass}`).forEach(el => el.classList.remove(config.errorClass));
        form.querySelectorAll(`.${config.feedbackClass}`).forEach(el => el.remove());
    };

    const clearFieldError = (input) => {
        input.classList.remove(config.errorClass);
        const feedback = input.parentNode.querySelector(`.${config.feedbackClass}`);
        if (feedback) feedback.remove();
    };

    const showFieldError = (fieldName, message) => {
        // Try to find input by name, accounting for arrays like items[0][name] if needed, 
        // though standard Laravel validation returns dot notation "items.0.name"
        
        let input = form.querySelector(`[name="${fieldName}"]`);
        
        // Handle dot notation for arrays -> brackets
        if (!input && fieldName.includes('.')) {
            const arrayName = fieldName.replace(/\.(\d+)\./, '[$1][').replace(/\.(\d+)$/, '[$1]');
            input = form.querySelector(`[name="${arrayName}"]`) || form.querySelector(`[name="${fieldName}"]`);
        }

        if (input) {
            input.classList.add(config.errorClass);
            
            // Check if feedback element exists
            let feedback = input.parentNode.querySelector(`.${config.feedbackClass}`);
            if (!feedback) {
                feedback = document.createElement('div');
                feedback.className = config.feedbackClass;
                input.parentNode.appendChild(feedback);
            }
            feedback.textContent = message;
        }
    };

    const setLoading = (loading) => {
        if (!submitBtn) return;
        submitBtn.disabled = loading;
        submitBtn.innerHTML = loading ? config.loadingText : originalBtnContent;
    };

    // --- Validation Logic ---

    const handleBlur = async (e) => {
        if (!e.target.name) return;
        
        // Only validate if we have a specific endpoint for field validation, 
        // OR we just clear the error on interaction. 
        // True "server-side validation on blur" requires a specific endpoint usually.
        // For now, we will just clear the error to improve UX.
        clearFieldError(e.target);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        clearErrors();
        setLoading(true);

        const formData = new FormData(form);

        try {
            const response = await axios.post(form.action, formData, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest', // Ensure JSON response
                    'Content-Type': 'multipart/form-data'
                }
            });

            // Success
            if (config.onSuccess) {
                config.onSuccess(response.data);
            } else {
                // Default Success Behavior
                if (response.data.message) {
                    // You might replace this with a toast notification
                    alert('Success: ' + response.data.message);
                }
                
                if (response.data.redirect_url || response.data.redirect) {
                    window.location.href = response.data.redirect_url || response.data.redirect;
                } else if (config.resetOnSuccess) {
                    form.reset();
                } else {
                    window.location.reload();
                }
            }
        } catch (error) {
            if (error.response && error.response.status === 422) {
                const errors = error.response.data.errors;
                Object.keys(errors).forEach(field => {
                    showFieldError(field, errors[field][0]);
                });
                
                if (config.onError) config.onError(errors);
            } else {
                console.error('Submission error:', error);
                alert('An error occurred. Please try again.');
            }
        } finally {
            setLoading(false);
        }
    };

    // --- Attach Listeners ---

    form.addEventListener('submit', handleSubmit);
    if (config.validateOnBlur) {
        form.addEventListener('focusout', handleBlur); // Bubbles, unlike blur
    }

    // Return control object
    return {
        destroy: () => {
            form.removeEventListener('submit', handleSubmit);
            form.removeEventListener('focusout', handleBlur);
        }
    };
}

/**
 * Globally initialize validation for all forms with 'data-ajax-validate' attribute.
 */
export function initGlobalValidation() {
    const forms = document.querySelectorAll('form[data-ajax-validate]');
    forms.forEach(form => {
        // Check if already initialized to avoid double binding
        if (form.dataset.validationInitialized) return;
        
        withAjaxValidation(form);
        form.dataset.validationInitialized = 'true';
    });
}
