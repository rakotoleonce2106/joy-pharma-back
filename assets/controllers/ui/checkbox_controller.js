import {Controller} from "@hotwired/stimulus";
import '@kanety/stimulus-static-actions';

export default class UICheckbox extends Controller {
    static targets = ['button', 'indicator'];
    static actions = [['button', 'click->toggle']];
    static values = {id: String};

    initialize() {
        this.state = this.element.dataset.state;
        this.label = null;
        this.input = null;
        if (this.idValue) {
            this.label = document.querySelector(`label[for="${this.idValue}"]`);
            this.input = document.getElementById(this.idValue);
            this.label && this.label.addEventListener('click', () => this.toggle());

            if (this.input) {
                this.state = this.input.checked ? 'checked' : 'unchecked';
                this.updateCheckboxAttributes(this.state);
            }
        }
        this.handleState();
    }

    updateCheckboxAttributes(state) {
        this.buttonTarget.dataset.state = state;
        this.buttonTarget.setAttribute('aria-checked', state === 'checked' ? 'true' : 'false');
        this.indicatorTarget.dataset.state = state;
    }

    getCheckboxState() {
        return this.state === 'checked' ? 'unchecked' : 'checked';
    }

    handleState() {
        this.state === 'checked' ? this.handleChecked() : this.handleUnchecked();
        this.state === 'indeterminate' ? this.handleIndeterminate() : null;
    }

    handleChecked() {
        this.indicatorTarget.style.removeProperty('display');
    }

    handleUnchecked() {
        this.indicatorTarget.style.display = 'none';
    }

    toggle() {
        this.state = this.getCheckboxState();
        this.updateCheckboxAttributes(this.state);
        this.handleState();

        if (this.input) {
            this.input.checked = this.state === 'checked';

            this.input.dispatchEvent(new Event('input', {bubbles: true}));
            this.input.dispatchEvent(new Event('change', {bubbles: true}));
        }
    }

    handleIndeterminate() {
        this.indicatorTarget.style.removeProperty('display');
    }
}