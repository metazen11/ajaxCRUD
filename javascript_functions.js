/**
 * ajaxCRUD JavaScript Functions - Modernized for ES6+
 * @version 7.0
 */

// Global variables
let loadingImageHtml = ''; // Set via setLoadingImageHTML()
let filterReq = '';        // Used in filtering the table
let pageReq = '';          // Used in pagination
let sortReq = '';          // Used for sorting the table
let thisPage = '';         // The PHP file loading ajaxCRUD (including all params)
let ajaxFile = '';         // The AJAX endpoint file

/**
 * Get CSRF token from meta tag
 * @returns {string} The CSRF token or empty string
 */
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

/**
 * Append CSRF token to URL
 * @param {string} url - The URL to append token to
 * @returns {string} URL with CSRF token
 */
function appendCsrfToken(url) {
    const token = getCsrfToken();
    if (!token) return url;
    const separator = url.includes('?') ? '&' : '?';
    return url + separator + 'csrf_token=' + encodeURIComponent(token);
}

// Legacy compatibility - map old var names
Object.defineProperty(window, 'loading_image_html', {
    get: () => loadingImageHtml,
    set: (v) => { loadingImageHtml = v; }
});
Object.defineProperty(window, 'this_page', {
    get: () => thisPage,
    set: (v) => { thisPage = v; }
});
Object.defineProperty(window, 'ajax_file', {
    get: () => ajaxFile,
    set: (v) => { ajaxFile = v; }
});

/**
 * Get the current page URL with appropriate parameter separator
 */
function getThisPage() {
    let returnPageName = thisPage;
    const paramChar = returnPageName.includes('?') ? '&' : '?';
    return returnPageName + paramChar;
}

/**
 * Send an AJAX update request
 * @param {string} action - The URL to send the request to
 */
async function sndUpdateReq(action) {
    try {
        const response = await fetch(appendCsrfToken(action), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': getCsrfToken()
            }
        });
        const text = await response.text();
        handleUpdateResponse(text);
    } catch (error) {
        console.error('Update request failed:', error);
    }
}

/**
 * Send an AJAX delete request
 * @param {string} action - The URL to send the request to
 */
async function sndDeleteReq(action) {
    try {
        const response = await fetch(appendCsrfToken(action), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': getCsrfToken()
            }
        });
        const returnString = await response.text();
        const brokenString = returnString.split('|');
        const table = brokenString[0];
        const id = brokenString[1];

        // Fade out the deleted row(s) - supports vertical layout
        const rows = document.querySelectorAll(`tr[id^="${table}_row_${id}"]`);
        rows.forEach(row => {
            row.style.transition = 'opacity 0.5s';
            row.style.opacity = '0';
            setTimeout(() => row.remove(), 500);
        });
    } catch (error) {
        console.error('Delete request failed:', error);
    }
}

/**
 * Send an AJAX add request
 * @param {string} action - The URL to send the request to
 * @param {string} table - The table name
 */
async function sndAddReq(action, table) {
    try {
        await fetch(appendCsrfToken(action), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': getCsrfToken()
            }
        });

        const action2 = `${ajaxFile}?ajaxAction=add&table=${encodeURIComponent(table)}`;
        const response = await fetch(appendCsrfToken(action2), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': getCsrfToken()
            }
        });
        const tableHtml = await response.text();
        const tableElement = document.getElementById(table);
        if (tableElement) {
            tableElement.innerHTML = tableHtml;
            doValidation(); // Rebind validation functions to new elements
        }
    } catch (error) {
        console.error('Add request failed:', error);
    }
}

/**
 * Send an AJAX filter request
 * @param {string} action - The URL to send the request to
 * @param {string} table - The table name
 */
async function sndFilterReq(action, table) {
    try {
        await fetch(appendCsrfToken(action), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': getCsrfToken()
            }
        });

        const filterAction = `${ajaxFile}?ajaxAction=filter&table=${encodeURIComponent(table)}`;
        const response = await fetch(appendCsrfToken(filterAction), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': getCsrfToken()
            }
        });
        const tableHtml = await response.text();
        const tableElement = document.getElementById(table);
        if (tableElement) {
            tableElement.innerHTML = tableHtml;
            doValidation(); // Rebind validation functions to new elements
        }
    } catch (error) {
        console.error('Filter request failed:', error);
    }
}

/**
 * Send an AJAX sort request
 * @param {string} action - The URL to send the request to
 * @param {string} table - The table name
 */
async function sndSortReq(action, table) {
    try {
        await fetch(appendCsrfToken(action), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': getCsrfToken()
            }
        });

        const sortAction = `${ajaxFile}?ajaxAction=sort&table=${encodeURIComponent(table)}`;
        const response = await fetch(appendCsrfToken(sortAction), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': getCsrfToken()
            }
        });
        const tableHtml = await response.text();
        const tableElement = document.getElementById(table);
        if (tableElement) {
            tableElement.innerHTML = tableHtml;
            doValidation(); // Rebind validation functions to new elements
        }
    } catch (error) {
        console.error('Sort request failed:', error);
    }
}

/**
 * Send a request without expecting a response
 * @param {string} action - The URL to send the request to
 */
function sndReqNoResponse(action) {
    fetch(action, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    }).catch(error => console.error('Request failed:', error));
}

/**
 * Send a synchronous request (use sparingly - async version preferred)
 * @param {string} action - The URL to send the request to
 */
function sndReqNoResponseChk(action) {
    // Using async version instead - synchronous XHR is deprecated
    sndReqNoResponse(action);
}

/**
 * Handle update response
 * @param {string} returnString - The response text from the server
 */
function handleUpdateResponse(returnString) {
    // Check for CSRF error response
    if (returnString.substring(0, 10) === 'csrf_error') {
        const brokenString = returnString.split('|');
        const errorMessage = brokenString[2] || 'Security token expired. Please refresh the page.';
        alert(errorMessage);
        return;
    }

    // Check for validation error response
    if (returnString.substring(0, 16) === 'validation_error') {
        const brokenString = returnString.split('|');
        const id = brokenString[1];
        const errorMessage = brokenString[2];

        // Show validation error to user
        showValidationError(id, errorMessage);

        // Re-show the edit form
        const showElement = document.getElementById(`${id}_show`);
        const editElement = document.getElementById(`${id}_edit`);
        const saveElement = document.getElementById(`${id}_save`);

        if (showElement) showElement.style.display = 'none';
        if (editElement) editElement.style.display = '';
        if (saveElement) saveElement.style.display = 'none';
        return;
    }

    // Check for error response
    if (returnString.substring(0, 5) === 'error') {
        const brokenString = returnString.split('|');
        const id = brokenString[1];
        const oldValue = brokenString[2];

        // Display the display section, fill it with prior content
        const showElement = document.getElementById(`${id}_show`);
        const editElement = document.getElementById(`${id}_edit`);
        const saveElement = document.getElementById(`${id}_save`);

        if (showElement) {
            showElement.innerHTML = oldValue;
            showElement.style.display = '';
        }
        if (editElement) editElement.style.display = 'none';
        if (saveElement) saveElement.style.display = 'none';
    } else {
        const brokenString = returnString.split('|');
        const id = brokenString[0];
        let replaceText = myStripSlashes(brokenString[1] || '');

        const showElement = document.getElementById(`${id}_show`);
        const editElement = document.getElementById(`${id}_edit`);
        const saveElement = document.getElementById(`${id}_save`);

        // Display the display section, fill it with new content
        if (replaceText !== '{selectbox}') {
            if (showElement) {
                showElement.innerHTML = replaceText || '';
            }
        } else {
            const selectbox = document.getElementById(id);
            if (selectbox && showElement) {
                showElement.innerHTML = selectbox.options[selectbox.selectedIndex].text;
            }
        }

        if (showElement) showElement.style.display = '';
        if (editElement) editElement.style.display = 'none';
        if (saveElement) saveElement.style.display = 'none';
    }
}

/**
 * Change sort order for a table
 * @param {string} table - The table name
 * @param {string} fieldName - The field to sort by
 * @param {string} sortDirection - The sort direction (asc/desc)
 */
function changeSort(table, fieldName, sortDirection) {
    sortReq = `&sort_field=${encodeURIComponent(fieldName)}&sort_direction=${encodeURIComponent(sortDirection)}`;
    const req = `${getThisPage()}table=${encodeURIComponent(table)}${sortReq}${filterReq}`;
    sndSortReq(req, table);
    return false;
}

/**
 * Navigate to a page in the table
 * @param {string} params - The pagination parameters
 * @param {string} table - The table name
 */
function pageTable(params, table) {
    const req = `${getThisPage()}table=${encodeURIComponent(table)}${params}${sortReq}${filterReq}`;
    pageReq = params;
    sndSortReq(req, table);
    return false;
}

/**
 * Set loading image in a table
 * @param {string} table - The table name
 */
function setLoadingImage(table) {
    const tableElement = document.getElementById(table);
    if (tableElement) {
        tableElement.innerHTML = loadingImageHtml;
    }
}

// Debounce timer storage
const filterTimers = new Map();

/**
 * Filter a table with debouncing
 * @param {HTMLElement} obj - The input element
 * @param {string} table - The table name
 * @param {string} field - The field name
 * @param {string} queryString - Additional query parameters
 */
function filterTable(obj, table, field, queryString) {
    const filterForm = document.getElementById(`${table}_filter_form`);
    const filterFields = filterForm ? getFormValues(filterForm, '') : '';

    let req;
    if (filterFields) {
        req = `${getThisPage()}${filterFields}&${queryString}`;
        filterReq = `&${filterFields}&${queryString}`;
    } else {
        req = `${getThisPage()}action=unfilter`;
        filterReq = '&action=unfilter';
    }

    // Clear existing timeout
    const existingTimer = filterTimers.get(obj);
    if (existingTimer) {
        clearTimeout(existingTimer);
    }

    // Set debounced filter request
    const timer = setTimeout(() => {
        setLoadingImage(table);
        sndFilterReq(req, table);
        filterTimers.delete(obj);
    }, 1200);

    filterTimers.set(obj, timer);
}

/**
 * Confirm and delete a row
 * @param {string|number} id - The row ID
 * @param {string} table - The table name
 * @param {string} pk - The primary key field name
 */
function confirmDelete(id, table, pk) {
    if (confirm('Are you sure you want to delete this item from the database? This cannot be undone.')) {
        ajaxDeleteRow(id, table, pk);
    }
}

/**
 * Delete a row via AJAX
 * @param {string|number} id - The row ID
 * @param {string} table - The table name
 * @param {string} pk - The primary key field name
 */
function ajaxDeleteRow(id, table, pk) {
    const req = `${ajaxFile}?ajaxAction=delete&id=${encodeURIComponent(id)}&table=${encodeURIComponent(table)}&pk=${encodeURIComponent(pk)}`;
    sndDeleteReq(req);
}

// Legacy compatibility alias
const ajax_deleteRow = ajaxDeleteRow;

/**
 * Delete a file
 * @param {string} field - The field name
 * @param {string|number} id - The row ID
 */
function deleteFile(field, id) {
    if (confirm('Are you sure you want to delete this file? This cannot be undone.')) {
        window.location.href = `?action=delete_file&field_name=${encodeURIComponent(field)}&id=${encodeURIComponent(id)}`;
    }
}

/**
 * Get form values as query string
 * @param {HTMLFormElement} fobj - The form element
 * @param {string} valFunc - Optional validation function name (deprecated)
 * @returns {string} The form values as a query string
 */
function getFormValues(fobj, valFunc) {
    const params = new URLSearchParams();

    for (const element of fobj.elements) {
        const type = element.type;
        const name = element.name;

        if (!name) continue;

        if (type === 'text' || type === 'textarea') {
            params.append(name, element.value);
        } else if (type === 'select-one') {
            params.append(name, element.options[element.selectedIndex]?.value || '');
        } else if (type === 'checkbox') {
            params.append(name, element.checked ? element.value : '');
        }
    }

    return params.toString();
}

/**
 * Clear a form
 * @param {string} formIdent - The form ID
 */
function clearForm(formIdent) {
    const form = document.getElementById(formIdent);
    if (!form) return;

    // Clear text inputs
    form.querySelectorAll('input[type="text"]').forEach(el => el.value = '');

    // Uncheck checkboxes
    form.querySelectorAll('input[type="checkbox"]').forEach(el => el.checked = false);

    // Reset selects
    form.querySelectorAll('select').forEach(el => el.selectedIndex = 0);

    // Clear textareas
    form.querySelectorAll('textarea').forEach(el => el.value = '');
}

/**
 * Validate numeric input
 * @param {KeyboardEvent} evento - The keyboard event
 * @param {HTMLElement} elemento - The input element
 * @param {string} dec - Whether decimals are allowed ('y' or 'n')
 * @returns {boolean} Whether the input is valid
 */
function fn_validateNumeric(evento, elemento, dec) {
    const valor = elemento.value;
    const keyCode = evento.which || evento.keyCode;

    // Allow: backspace, tab, left/right arrows, delete, enter
    const allowedKeys = [8, 9, 37, 39, 46, 13];
    const isNumber = keyCode >= 48 && keyCode <= 57;
    const isDecimalPoint = keyCode === 46 || evento.key === '.';

    if (isNumber || allowedKeys.includes(keyCode) || isDecimalPoint) {
        // Don't allow decimal if dec='n'
        if (dec === 'n' && isDecimalPoint) {
            return false;
        }
        // Don't allow multiple decimal points
        if (valor.includes('.') && isDecimalPoint) {
            return false;
        }
        return true;
    }

    return false;
}

/**
 * Escape quotes in a string
 * @param {string} str - The string to escape
 * @returns {string} The escaped string
 */
function myAddSlashes(str) {
    return str.replace(/"/g, '\\"');
}

/**
 * Unescape quotes in a string
 * @param {string} str - The string to unescape
 * @returns {string} The unescaped string
 */
function myStripSlashes(str) {
    if (!str) return '';
    return str.replace(/\\'/g, "'").replace(/\\"/g, '"');
}

/**
 * Hover effect for table cells
 * @param {HTMLElement} obj - The element to apply hover to
 */
function hover(obj) {
    obj.style.backgroundColor = '#FFFF99';
}

/**
 * Remove hover effect
 * @param {HTMLElement} obj - The element to remove hover from
 */
function unHover(obj) {
    obj.className = '';
}

/**
 * Set all checkboxes with a given name
 * @param {string} str - The checkbox name
 * @param {boolean} ck - The checked state to set
 */
function setAllCheckboxes(str, ck) {
    const ckboxes = document.getElementsByName(str);
    for (const checkbox of ckboxes) {
        if (checkbox.checked === ck) {
            checkbox.checked = ck;
            checkbox.click();
        }
    }
}

// Legacy compatibility: Add findIndex to Array if not exists
if (typeof Array.prototype.findIndex !== 'function') {
    Array.prototype.findIndex = function(value) {
        for (let i = 0; i < this.length; i++) {
            if (this[i] == value) {
                return i;
            }
        }
        return '';
    };
}

/**
 * Validation patterns for client-side validation
 */
const validationPatterns = {
    email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    tel: /^[+]?[(]?[0-9]{1,4}[)]?[-\s./0-9]{6,}$/,
    time: /^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/,
    date: /^\d{4}-\d{2}-\d{2}$/,
    datetime: /^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}(:\d{2})?$/,
    color: /^#[0-9A-Fa-f]{6}$/,
    url: /^https?:\/\/.+/,
    number: /^-?\d+$/,
    decimal: /^-?\d*\.?\d+$/
};

/**
 * Validation error messages
 */
const validationMessages = {
    email: 'Please enter a valid email address',
    tel: 'Please enter a valid phone number',
    time: 'Please enter a valid time (HH:MM)',
    date: 'Please enter a valid date (YYYY-MM-DD)',
    datetime: 'Please enter a valid date and time',
    color: 'Please enter a valid color (#RRGGBB)',
    url: 'Please enter a valid URL (https://...)',
    number: 'Please enter a valid whole number',
    decimal: 'Please enter a valid number'
};

/**
 * Validate a field value based on its type
 * @param {string} value - The value to validate
 * @param {string} type - The field type
 * @returns {string|null} Error message or null if valid
 */
function validateFieldValue(value, type) {
    // Empty values are allowed (unless required is set)
    if (!value || value.trim() === '') return null;

    const pattern = validationPatterns[type];
    if (pattern && !pattern.test(value)) {
        return validationMessages[type] || 'Invalid value';
    }
    return null;
}

/**
 * Show validation error to user
 * @param {string} fieldId - The field identifier
 * @param {string} message - The error message
 */
function showValidationError(fieldId, message) {
    // Remove any existing error
    hideValidationError(fieldId);

    // Find the edit container
    const editElement = document.getElementById(`${fieldId}_edit`);
    if (!editElement) {
        alert(message); // Fallback to alert
        return;
    }

    // Create error element
    const errorDiv = document.createElement('div');
    errorDiv.id = `${fieldId}_error`;
    errorDiv.className = 'validation-error';
    errorDiv.textContent = message;
    errorDiv.style.cssText = 'color: #dc3545; font-size: 12px; margin-top: 4px; padding: 4px 8px; background: #f8d7da; border-radius: 4px; display: block;';

    // Insert after the form
    editElement.appendChild(errorDiv);

    // Also add error styling to the input
    const input = editElement.querySelector('input, select, textarea');
    if (input) {
        input.style.borderColor = '#dc3545';
        input.style.boxShadow = '0 0 0 2px rgba(220, 53, 69, 0.25)';
    }
}

/**
 * Hide validation error
 * @param {string} fieldId - The field identifier
 */
function hideValidationError(fieldId) {
    const errorElement = document.getElementById(`${fieldId}_error`);
    if (errorElement) {
        errorElement.remove();
    }

    // Remove error styling from input
    const editElement = document.getElementById(`${fieldId}_edit`);
    if (editElement) {
        const input = editElement.querySelector('input, select, textarea');
        if (input) {
            input.style.borderColor = '';
            input.style.boxShadow = '';
        }
    }
}

/**
 * Create a client-side validation function for a field
 * @param {string} fieldId - The field identifier
 * @param {string} inputId - The input element ID
 * @param {string} type - The field type
 * @returns {Function} Validation function
 */
function createFieldValidator(fieldId, inputId, type) {
    return function() {
        const input = document.getElementById(inputId);
        if (!input) return true;

        const value = input.value;
        const error = validateFieldValue(value, type);

        if (error) {
            showValidationError(fieldId, error);
            input.focus();
            return false;
        }

        hideValidationError(fieldId);
        return true;
    };
}

/* javascript_functions.js - Modernized version 7.0 */
