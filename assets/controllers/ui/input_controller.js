import {Controller} from "@hotwired/stimulus";

export default class UIInput extends Controller {
    setValue(newValue) {
        // Les inputs de type file ne peuvent pas avoir leur valeur définie programmatiquement
        // (sauf pour la chaîne vide) pour des raisons de sécurité
        if (this.element.type === 'file') {
            console.warn('Cannot set value on file input element');
            return;
        }

        console.log('newValue', newValue)

        this.element.value = newValue;
        this.element.dispatchEvent(new CustomEvent('change', {bubbles: true}));

        this.oldValue = newValue;
    }

    getValue() {
        return this.element.value;
    }

    connect() {
        // Ne pas écouter les événements focusout pour les inputs de type file
        // car leur valeur ne peut pas être modifiée programmatiquement
        if (this.element.type !== 'file') {
            this.element.addEventListener('focusout', this.handleChange);
            this.oldValue = this.element.value;
        }
    }

    handleChange = (event) => {
        // Ignorer les inputs de type file
        if (event.target.type === 'file') {
            return;
        }

        if (this.oldValue !== event.target.value) {
            this.setValue(event.target.value);
        }
    }
}