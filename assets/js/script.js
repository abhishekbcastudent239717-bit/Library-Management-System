/**
 * Library Management System - Client-Side JavaScript
 * 
 * This file contains all client-side validation functions and UI interactions.
 * Provides immediate feedback to users before form submission to improve UX.
 * 
 * Key Features:
 * - Form validation (email, password, phone, ISBN)
 * - Real-time error/success messages
 * - Mobile-responsive sidebar toggle
 * - Password visibility toggle
 * - Input field validations
 * 
 * Security:
 * - Client-side validation only (server-side validation still required)
 * - Helps catch errors early and reduce server load
 * - Improves user experience with immediate feedback
 * 
 * @author Library Management System
 * @version 1.0
 */

// ==========================================
// NAVIGATION FUNCTIONS
// ==========================================

/**
 * Toggle Sidebar Menu (Mobile/Tablet)
 * 
 * Shows/hides the navigation sidebar on smaller screens.
 * Adds 'active' class to both sidebar and hamburger icon.
 * 
 * @returns void
 */
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const hamburger = document.querySelector('.hamburger');
    
    // Toggle active class to show/hide sidebar
    sidebar.classList.toggle('active');
    hamburger.classList.toggle('active');  // Animate hamburger icon
}

/**
 * Close Sidebar on Outside Click (Mobile)
 * 
 * Event listener that closes the sidebar when user clicks
 * outside of it on mobile devices. Improves mobile UX.
 */
document.addEventListener('click', function(event) {
    const sidebar = document.querySelector('.sidebar');
    const hamburger = document.querySelector('.hamburger');
    
    // Only on mobile/tablet screens (width <= 768px)
    if (sidebar && hamburger && window.innerWidth <= 768) {
        // Check if click was outside sidebar and hamburger
        if (!sidebar.contains(event.target) && !hamburger.contains(event.target)) {
            sidebar.classList.remove('active');  // Close sidebar
            hamburger.classList.remove('active');  // Reset hamburger icon
        }
    }
});

// ==========================================
// VALIDATION HELPER FUNCTIONS
// ==========================================

/**
 * Validate Email Format
 * 
 * Checks if email follows standard format: name@domain.extension
 * Uses regular expression for pattern matching.
 * 
 * @param {string} email - Email address to validate
 * @returns {boolean} - True if valid email format, false otherwise
 */
function validateEmail(email) {
    // Regex: one or more chars + @ + one or more chars + . + one or more chars
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Validate Password Strength
 * 
 * Ensures password meets minimum length requirement.
 * Minimum: 6 characters (security best practice)
 * 
 * @param {string} password - Password to validate
 * @returns {boolean} - True if password >= 6 chars, false otherwise
 */
function validatePassword(password) {
    return password.length >= 6;
}

/**
 * Validate Phone Number
 * 
 * Checks if phone number is exactly 10 digits (Indian standard).
 * Only accepts numeric characters.
 * 
 * @param {string} phone - Phone number to validate
 * @returns {boolean} - True if valid 10-digit number, false otherwise
 */
function validatePhone(phone) {
    // Regex: exactly 10 numeric digits
    const re = /^[0-9]{10}$/;
    return re.test(phone);
}

/**
 * Validate ISBN
 * 
 * Checks if ISBN field is not empty.
 * (Note: Could be enhanced to validate ISBN-10 or ISBN-13 format)
 * 
 * @param {string} isbn - ISBN to validate
 * @returns {boolean} - True if not empty, false otherwise
 */
function validateISBN(isbn) {
    return isbn.trim().length > 0;
}

// ==========================================
// ERROR MESSAGE DISPLAY FUNCTIONS
// ==========================================

/**
 * Show Error Message Below Input Field
 * 
 * Displays a red error message below the input field
 * and changes border color to red for visual feedback.
 * 
 * @param {HTMLElement} inputElement - The input field with error
 * @param {string} message - Error message to display
 * @returns void
 */
function showError(inputElement, message) {
    const formGroup = inputElement.parentElement;
    let errorDiv = formGroup.querySelector('.error-text');
    
    // Create error div if doesn't exist
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'error-text';
        errorDiv.style.color = '#e74c3c';      // Red color
        errorDiv.style.fontSize = '12px';      // Smaller font
        errorDiv.style.marginTop = '5px';      // Spacing from input
        formGroup.appendChild(errorDiv);
    }
    
    errorDiv.textContent = message;            // Set error message
    inputElement.style.borderColor = '#e74c3c'; // Red border
}

/**
 * Clear Error Message from Input Field
 * 
 * Removes error message div and resets border color to normal.
 * Called when user corrects the input.
 * 
 * @param {HTMLElement} inputElement - The input field to clear error from
 * @returns void
 */
function clearError(inputElement) {
    const formGroup = inputElement.parentElement;
    const errorDiv = formGroup.querySelector('.error-text');
    
    // Remove error div if exists
    if (errorDiv) {
        errorDiv.remove();
    }
    
    inputElement.style.borderColor = '#ddd';  // Reset to normal border color
}

// ==========================================
// FORM VALIDATION FUNCTIONS
// ==========================================

/**
 * Validate Login Form
 * 
 * Validates login form before submission:
 * - Email must be valid format
 * - Password must be filled
 * 
 * Prevents form submission if validation fails.
 * 
 * @param {Event} event - Form submit event
 * @returns {boolean} - True if valid, false if errors found
 */
function validateLoginForm(event) {
    event.preventDefault();  // Stop form submission
    
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    let isValid = true;  // Track overall validation status
    
    // Clear any previous error messages
    clearError(email);
    clearError(password);
    
    // ==========================================
    // Validate Email Field
    // ==========================================
    if (!email.value.trim()) {
        showError(email, 'Email is required');
        isValid = false;
    } else if (!validateEmail(email.value)) {
        showError(email, 'Invalid email format');
        isValid = false;
    }
    
    // ==========================================
    // Validate Password Field
    // ==========================================
    if (!password.value.trim()) {
        showError(password, 'Password is required');
        isValid = false;
    }
    
    if (isValid) {
        event.target.submit();
    }
}

// Book Form Validation
function validateBookForm(event) {
    event.preventDefault();
    
    const form = event.target;
    const title = form.querySelector('input[name="title"]');
    const author = form.querySelector('input[name="author"]');
    const isbn = form.querySelector('input[name="isbn"]');
    const quantity = form.querySelector('input[name="quantity"]');
    let isValid = true;
    
    // Clear previous errors
    if (title) clearError(title);
    if (author) clearError(author);
    if (isbn) clearError(isbn);
    if (quantity) clearError(quantity);
    
    // Validate title
    if (!title || !title.value.trim()) {
        if (title) showError(title, 'Title is required');
        isValid = false;
    }
    
    // Validate author
    if (!author || !author.value.trim()) {
        if (author) showError(author, 'Author is required');
        isValid = false;
    }
    
    // Validate ISBN
    if (!isbn || !isbn.value.trim()) {
        if (isbn) showError(isbn, 'ISBN is required');
        isValid = false;
    }
    
    // Validate quantity
    if (!quantity || !quantity.value || quantity.value < 1) {
        if (quantity) showError(quantity, 'Quantity must be at least 1');
        isValid = false;
    }
    
    if (isValid) {
        event.target.submit();
    }
}

// Student Form Validation
function validateStudentForm(event) {
    event.preventDefault();
    
    const form = event.target;
    const name = form.querySelector('input[name="name"]');
    const email = form.querySelector('input[name="email"]');
    const password = form.querySelector('input[name="password"]');
    const phone = form.querySelector('input[name="phone"]');
    const action = form.querySelector('input[name="action"]');
    let isValid = true;
    
    // Clear previous errors
    if (name) clearError(name);
    if (email) clearError(email);
    if (password) clearError(password);
    if (phone) clearError(phone);
    
    // Validate name
    if (!name || !name.value.trim()) {
        if (name) showError(name, 'Name is required');
        isValid = false;
    }
    
    // Validate email
    if (!email || !email.value.trim()) {
        if (email) showError(email, 'Email is required');
        isValid = false;
    } else if (!validateEmail(email.value)) {
        showError(email, 'Invalid email format');
        isValid = false;
    }
    
    // Validate password (only required for new students, optional for edit)
    if (action && action.value === 'add') {
        if (!password || !password.value.trim()) {
            if (password) showError(password, 'Password is required for new students');
            isValid = false;
        } else if (!validatePassword(password.value)) {
            showError(password, 'Password must be at least 6 characters');
            isValid = false;
        }
    } else if (password && password.value.trim()) {
        // If editing and password provided, validate it
        if (!validatePassword(password.value)) {
            showError(password, 'Password must be at least 6 characters');
            isValid = false;
        }
    }
    
    // Validate phone
    if (phone && phone.value.trim() && !validatePhone(phone.value)) {
        showError(phone, 'Phone must be 10 digits');
        isValid = false;
    }
    
    if (isValid) {
        event.target.submit();
    }
}

// Category Form Validation
function validateCategoryForm(event) {
    event.preventDefault();
    
    const form = event.target;
    const name = form.querySelector('input[name="name"]');
    let isValid = true;
    
    // Clear previous errors
    if (name) clearError(name);
    
    // Validate name
    if (!name || !name.value.trim()) {
        if (name) showError(name, 'Category name is required');
        isValid = false;
    }
    
    if (isValid) {
        event.target.submit();
    }
}

// Issue Book Form Validation
function validateIssueForm(event) {
    event.preventDefault();
    
    const studentId = document.getElementById('student_id');
    const bookId = document.getElementById('book_id');
    const returnDate = document.getElementById('return_date');
    let isValid = true;
    
    // Clear previous errors
    [studentId, bookId, returnDate].forEach(input => clearError(input));
    
    // Validate student
    if (!studentId.value) {
        showError(studentId, 'Please select a student');
        isValid = false;
    }
    
    // Validate book
    if (!bookId.value) {
        showError(bookId, 'Please select a book');
        isValid = false;
    }
    
    // Validate return date
    if (!returnDate.value) {
        showError(returnDate, 'Return date is required');
        isValid = false;
    } else {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const selectedDate = new Date(returnDate.value);
        
        if (selectedDate <= today) {
            showError(returnDate, 'Return date must be in the future');
            isValid = false;
        }
    }
    
    if (isValid) {
        event.target.submit();
    }
}

// Confirm Delete
function confirmDelete(itemName) {
    return confirm(`Are you sure you want to delete ${itemName}?`);
}

// Search Table
function searchTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toUpperCase();
    const table = document.getElementById(tableId);
    const tr = table.getElementsByTagName('tr');
    
    for (let i = 1; i < tr.length; i++) {
        let found = false;
        const td = tr[i].getElementsByTagName('td');
        
        for (let j = 0; j < td.length; j++) {
            if (td[j]) {
                const txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        tr[i].style.display = found ? '' : 'none';
    }
}

// Modal Functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
    }
}

// Auto-hide messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const messages = document.querySelectorAll('.error-message, .success-message');
    messages.forEach(function(message) {
        setTimeout(function() {
            message.style.display = 'none';
        }, 5000);
    });
});
