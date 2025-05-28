import {Controller} from "@hotwired/stimulus";

export default class UIInput extends Controller {
    setValue(newValue) {

        console.log('newValue', newValue)

        this.element.value = newValue;
        this.element.dispatchEvent(new CustomEvent('change', {bubbles: true}));

        this.oldValue = newValue;
    }

    getValue() {
        return this.element.value;
    }

    connect() {
        this.element.addEventListener('focusout', this.handleChange);
        this.oldValue = this.element.value;
    }

    handleChange = (event) => {
        if (this.oldValue !== event.target.value) {
            this.setValue(event.target.value);
        }
    }
}