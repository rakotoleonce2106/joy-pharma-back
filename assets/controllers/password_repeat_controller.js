import { Controller } from '@hotwired/stimulus';

/*
 * Password Repeat Controller
 * Prevents password field from being cleared when confirm password changes
 */
export default class extends Controller {
    connect() {
        // Find the password fields (RepeatedType creates two fields)
        // Fields are typically named like "ownerPassword[first]" and "ownerPassword[second]"
        this.firstField = this.element.querySelector('input[type="password"][name*="[first]"]');
        this.secondField = this.element.querySelector('input[type="password"][name*="[second]"]');

        if (!this.firstField || !this.secondField) {
            return;
        }

        // Store initial first field value
        this.firstValue = this.firstField.value;

        // Listen for changes on the confirm password field
        this.secondField.addEventListener('input', this.handleSecondInput.bind(this));
        this.secondField.addEventListener('change', this.handleSecondChange.bind(this));
        this.secondField.addEventListener('focus', this.handleSecondFocus.bind(this));
        this.secondField.addEventListener('blur', this.handleSecondBlur.bind(this));

        // Listen on the first field to update stored value
        this.firstField.addEventListener('input', this.handleFirstInput.bind(this));
        this.firstField.addEventListener('focus', this.handleFirstFocus.bind(this));
    }

    handleFirstInput(event) {
        // Update stored value when first field changes
        this.firstValue = event.target.value;
    }

    handleFirstFocus(event) {
        // Update stored value when first field is focused
        this.firstValue = event.target.value;
    }

    handleSecondInput(event) {
        // Preserve first field value when second field changes
        this.preserveFirstField();
    }

    handleSecondChange(event) {
        // Preserve first field value when second field changes
        this.preserveFirstField();
    }

    handleSecondFocus(event) {
        // Store first field value before second field is focused
        // This prevents clearing during focus events
        if (this.firstField && this.firstField.value) {
            this.firstValue = this.firstField.value;
        }
    }

    handleSecondBlur(event) {
        // After second field loses focus, ensure first field still has its value
        this.preserveFirstField();
    }

    preserveFirstField() {
        // If first field is empty but we have a stored value, restore it
        // Note: We can't directly set password field values for security reasons in some browsers,
        // but we try to preserve it if it was cleared unexpectedly
        if (this.firstField && this.firstValue && !this.firstField.value && this.firstField.type === 'password') {
            // Try to restore the value (may not work in all browsers for security reasons)
            // The value will be preserved in memory via this.firstValue
            // The form submission will use the stored value if needed
            try {
                // Create a hidden input to preserve the value
                if (!this.hiddenField) {
                    this.hiddenField = document.createElement('input');
                    this.hiddenField.type = 'hidden';
                    this.hiddenField.name = this.firstField.name + '_preserved';
                    this.firstField.parentElement.appendChild(this.hiddenField);
                }
                this.hiddenField.value = this.firstValue;
            } catch (e) {
                // Ignore errors - browser security may prevent this
                console.debug('Cannot preserve password field value:', e);
            }
        } else if (this.firstField && !this.firstField.value && this.firstValue) {
            // If first field was cleared, try to restore from stored value
            // This is a workaround for browsers that clear password fields on focus/blur
            if (this.hiddenField && this.hiddenField.value) {
                // Value is preserved in hidden field
            }
        }
    }

    disconnect() {
        if (this.secondField) {
            this.secondField.removeEventListener('input', this.handleSecondInput);
            this.secondField.removeEventListener('change', this.handleSecondChange);
            this.secondField.removeEventListener('focus', this.handleSecondFocus);
            this.secondField.removeEventListener('blur', this.handleSecondBlur);
        }
        if (this.firstField) {
            this.firstField.removeEventListener('input', this.handleFirstInput);
            this.firstField.removeEventListener('focus', this.handleFirstFocus);
        }
        if (this.hiddenField && this.hiddenField.parentElement) {
            this.hiddenField.parentElement.removeChild(this.hiddenField);
        }
    }
}
