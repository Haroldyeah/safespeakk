/**
 * Password Strength Indicator
 * Real-time password strength feedback
 */

// Monitor dark mode changes
function setupDarkModeListener() {
    // Check system preference
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
    
    prefersDark.addEventListener('change', (e) => {
        // Refresh indicator colors when system preference changes
        console.log('Dark mode preference changed:', e.matches);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Setup dark mode listener
    setupDarkModeListener();
    
    // Find all password input fields
    const passwordInputs = document.querySelectorAll('input[type="password"][data-strength="true"]');
    
    passwordInputs.forEach(input => {
        // Check if already wrapped
        if (input.parentElement.classList.contains('password-field-wrapper')) {
            return; // Already processed
        }
        
        // Add password visibility toggle
        addPasswordToggle(input);
        
        // Add event listener for real-time feedback
        input.addEventListener('input', function() {
            // Get the wrapper parent
            const wrapper = this.parentElement;
            
            // Create and show indicator only when user starts typing
            if (this.value.length > 0) {
                let indicator = wrapper.nextElementSibling;
                if (!indicator || !indicator.classList.contains('password-strength-indicator')) {
                    indicator = document.createElement('div');
                    indicator.className = 'password-strength-indicator';
                    indicator.innerHTML = `
                        <div class="strength-bar-container">
                            <div class="strength-bar"></div>
                        </div>
                        <div class="strength-text"></div>
                        <div class="strength-requirements">
                            <div class="requirement" data-requirement="length8">
                                <span class="requirement-icon">✗</span>
                                <span class="requirement-text">At least 8 characters</span>
                            </div>
                            <div class="requirement" data-requirement="uppercase">
                                <span class="requirement-icon">✗</span>
                                <span class="requirement-text">At least one uppercase letter (A-Z)</span>
                            </div>
                            <div class="requirement" data-requirement="lowercase">
                                <span class="requirement-icon">✗</span>
                                <span class="requirement-text">At least one lowercase letter (a-z)</span>
                            </div>
                            <div class="requirement" data-requirement="number">
                                <span class="requirement-icon">✗</span>
                                <span class="requirement-text">At least one number (0-9)</span>
                            </div>
                            <div class="requirement" data-requirement="special">
                                <span class="requirement-icon">✗</span>
                                <span class="requirement-text">At least one special character (!@#$%^&*)</span>
                            </div>
                        </div>
                    `;
                    wrapper.parentNode.insertBefore(indicator, wrapper.nextSibling);
                }
                updatePasswordStrength(this);
            } else {
                // Remove indicator when field is empty
                let indicator = wrapper.nextElementSibling;
                if (indicator && indicator.classList.contains('password-strength-indicator')) {
                    indicator.remove();
                }
            }
        });
    });
});

function addPasswordToggle(input) {
    // Check if wrapper already exists
    if (input.parentElement.classList.contains('password-field-wrapper')) {
        return;
    }
    
    // Create wrapper for password field with icon
    const wrapper = document.createElement('div');
    wrapper.className = 'password-field-wrapper';
    
    // Move input into wrapper
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);
    
    // Create toggle button
    const toggleBtn = document.createElement('button');
    toggleBtn.type = 'button';
    toggleBtn.className = 'password-toggle-btn';
    toggleBtn.innerHTML = '👁️';
    toggleBtn.setAttribute('aria-label', 'Toggle password visibility');
    
    wrapper.appendChild(toggleBtn);
    
    // Toggle password visibility
    toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (input.type === 'password') {
            input.type = 'text';
            toggleBtn.innerHTML = '👁️‍🗨️';
        } else {
            input.type = 'password';
            toggleBtn.innerHTML = '👁️';
        }
    });
}

function updatePasswordStrength(input) {
    const password = input.value;
    const wrapper = input.parentElement;
    const indicator = wrapper.nextElementSibling;
    
    if (!indicator || !indicator.classList.contains('password-strength-indicator')) {
        return;
    }
    
    // Calculate strength
    const strength = calculatePasswordStrength(password);
    
    // Update visual indicators
    const strengthBar = indicator.querySelector('.strength-bar');
    const strengthText = indicator.querySelector('.strength-text');
    
    strengthBar.className = 'strength-bar strength-' + strength;
    strengthText.className = 'strength-text strength-' + strength;
    
    if (password.length === 0) {
        strengthText.textContent = '';
    } else if (strength === 'weak') {
        strengthText.textContent = '🔴 Weak - This password is too weak';
    } else if (strength === 'medium') {
        strengthText.textContent = '🟡 Medium - This password is acceptable';
    } else if (strength === 'strong') {
        strengthText.textContent = '🟢 Strong - This is a secure password';
    }
    
    // Update requirements
    updateRequirements(password, indicator);
}

function calculatePasswordStrength(password) {
    let score = 0;
    
    // Length checks
    if (password.length >= 8) score += 1;
    if (password.length >= 12) score += 1;
    if (password.length >= 16) score += 1;
    
    // Character variety checks
    if (/[a-z]/.test(password)) score += 1;
    if (/[A-Z]/.test(password)) score += 1;
    if (/[0-9]/.test(password)) score += 1;
    if (/[!@#$%^&*()_+\-=\[\]{};:'"",.<>?\/\\|`~]/.test(password)) score += 2;
    
    // Determine strength
    if (score >= 5) {
        return 'strong';
    } else if (score >= 3) {
        return 'medium';
    } else {
        return 'weak';
    }
}

function updateRequirements(password, indicator) {
    const requirements = {
        length8: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[!@#$%^&*()_+\-=\[\]{};:'"",.<>?\/\\|`~]/.test(password)
    };
    
    Object.keys(requirements).forEach(req => {
        const element = indicator.querySelector(`[data-requirement="${req}"]`);
        if (element) {
            if (requirements[req]) {
                element.classList.add('met');
                element.querySelector('.requirement-icon').textContent = '✓';
            } else {
                element.classList.remove('met');
                element.querySelector('.requirement-icon').textContent = '✗';
            }
        }
    });
}
