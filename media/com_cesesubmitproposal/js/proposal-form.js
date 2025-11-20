/**
 * @package     Joomla.Site
 * @subpackage  com_cesesubmitproposal
 *
 * @copyright   KAINOTOMO PH LTD - All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        
        // Form validation
        const forms = document.querySelectorAll('.form-validate');
        forms.forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!validateForm(form)) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });

        // Toggle optional author fields
        initAuthorFieldToggle();

        // Handle submission type changes
        initSubmissionTypeToggle();
    });

    /**
     * Validate form fields
     */
    function validateForm(form) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(function(field) {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }

            // Email validation
            if (field.type === 'email' && field.value.trim()) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(field.value)) {
                    isValid = false;
                    field.classList.add('is-invalid');
                }
            }
        });

        // Validate at least one author
        const author1Name = form.querySelector('[name="jform[author1_name]"]');
        const author1Surname = form.querySelector('[name="jform[author1_surname]"]');
        const author1Email = form.querySelector('[name="jform[author1_email]"]');

        if (author1Name && author1Surname && author1Email) {
            if (!author1Name.value.trim() || !author1Surname.value.trim() || !author1Email.value.trim()) {
                isValid = false;
                if (!author1Name.value.trim()) author1Name.classList.add('is-invalid');
                if (!author1Surname.value.trim()) author1Surname.classList.add('is-invalid');
                if (!author1Email.value.trim()) author1Email.classList.add('is-invalid');
            }
        }

        return isValid;
    }

    /**
     * Initialize author field toggle functionality
     */
    function initAuthorFieldToggle() {
        // All author sections are visible by default
        // This function is kept for future enhancements if needed
        for (let i = 1; i <= 4; i++) {
            const authorSection = document.querySelector('.author-section:nth-of-type(' + i + ')');
            if (authorSection) {
                // Ensure all sections are visible
                authorSection.style.display = 'block';
            }
        }
    }



    /**
     * Initialize submission type toggle
     */
    function initSubmissionTypeToggle() {
        const submissionTypeRadios = document.querySelectorAll('input[name="jform[submission_type]"]');
        
        if (submissionTypeRadios.length > 0) {
            submissionTypeRadios.forEach(function(radio) {
                radio.addEventListener('change', function() {
                    // Save current form data to session storage
                    const formData = new FormData(document.getElementById('proposalForm'));
                    const dataObj = {};
                    
                    for (let [key, value] of formData.entries()) {
                        dataObj[key] = value;
                    }
                    
                    sessionStorage.setItem('proposalFormData', JSON.stringify(dataObj));
                    
                    // Submit form to reload with new submission type
                    const form = document.getElementById('proposalForm');
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'reload_submission_type';
                    input.value = this.value;
                    form.appendChild(input);
                    
                    // Prevent normal submission, just reload the page
                    form.action = window.location.href;
                    form.submit();
                });
            });

            // Restore form data from session storage if available
            const savedData = sessionStorage.getItem('proposalFormData');
            if (savedData) {
                const dataObj = JSON.parse(savedData);
                for (let [key, value] of Object.entries(dataObj)) {
                    const input = document.querySelector('[name="' + key + '"]');
                    if (input && input.type !== 'radio' && input.type !== 'checkbox') {
                        input.value = value;
                    }
                }
                sessionStorage.removeItem('proposalFormData');
            }
        }
    }

})();
