import UIPopover from "./popover_controller.js";

export default class UISelect extends UIPopover {
    static targets = super.targets.concat(["item"])
    static outlets = ["ui--input", "ui--text"];
    static values = {...super.values, isMultiple: Boolean}

    connect() {
        super.connect()
        this.setSelectedItem();
    }

    remove(event) {
        event.preventDefault();
        event.stopPropagation();

        this.setValue("");
        this.setInnerText("");
        this.setSelectedItem();
    }

    setSelectedItem() {
        const selectedOptions = this.selectedItems();

        this.itemTargets.forEach((item) => {
            item.removeAttribute("data-selected");
            item.removeAttribute("aria-selected");
        });

        selectedOptions.forEach((item) => {
            item.setAttribute("data-selected", "true");
            item.setAttribute("aria-selected", "true");
        });
    }

    selectedItems() {
        return this.itemTargets.filter((item) => {
            if (this.isMultipleValue) {
                const selected = Array.from(this.uiInputOutlet.element.selectedOptions).map(option => option.value);
                return selected.includes(item.dataset.value);
            }
            return item.dataset.value === this.uiInputOutlet.getValue();
        });
    }

    selectItem(event) {
        event.preventDefault();
        const selectedValue = event.currentTarget.dataset.value;
        const selectedText = event.currentTarget.innerText;
        if (this.isMultipleValue) {
            const selectedOptions = this.uiInputOutlet.element.selectedOptions;
            const selectedValues = Array.from(selectedOptions).map(option => option.value);

            const option = this.uiInputOutlet.element.querySelector(`option[value="${selectedValue}"]`);
            const isSelected = selectedValues.includes(selectedValue);

            option.selected = !isSelected;

            this.uiInputOutlet.element.dispatchEvent(new CustomEvent('change', {bubbles: true}));
        } else {
            this.setValue(selectedValue);
            this.uiInputOutlet.element.dispatchEvent(new CustomEvent('change', {bubbles: true}));
        }

        this.setSelectedItem();
    }

    setValue(selectedValue) {
        this.uiInputOutlet.setValue(selectedValue);
    }

    setInnerText(selectedOption) {
        this.uiTextOutlet.setText(selectedOption);
    }

    removeItem(event){
        event.preventDefault();
        event.stopPropagation();

        const selectedValue = event.currentTarget.dataset.value;
        const option = this.uiInputOutlet.element.querySelector(`option[value="${selectedValue}"]`);

        option.selected = false;
        this.uiInputOutlet.element.dispatchEvent(new CustomEvent('change', {bubbles: true}));

        this.setSelectedItem();
    }
}