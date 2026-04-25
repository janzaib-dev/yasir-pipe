import './bootstrap';

import Alpine from 'alpinejs';

import { initGlobalValidation } from './services/ValidationService';

window.Alpine = Alpine;

Alpine.start();

// Initialize Global Validation
initGlobalValidation();

// Expose the HOC globally for manual usage if needed
import { withAjaxValidation } from './services/ValidationService';
window.withAjaxValidation = withAjaxValidation;
