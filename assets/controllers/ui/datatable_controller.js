import {Controller} from "@hotwired/stimulus";
import '@kanety/stimulus-static-actions';

export default class UIDatatable extends Controller {
    static outlets = ['ui--checkbox', 'ui--datatable']
    static targets = ['selectAll', 'selectRow']
    static actions = [
        ['selectAll', 'click->selectAll'],
        ['selectRow', 'click->selectRow']
    ]

    selectAll(event) {
        event.preventDefault();
        const state = event.currentTarget.dataset.state;
        this.uiCheckboxOutlets.forEach((checkbox) => {
            this.updateCheckbox(checkbox, state);
        });
    }

    selectRow(event) {
        event.preventDefault();

        const checked = this.uiDatatableOutletElements.filter((checkbox) => {
            return checkbox.dataset.state === 'checked';
        });

        let state = checked.length > 0 ? 'indeterminate' : 'unchecked';
        state = checked.length === this.uiDatatableOutletElements.length ? 'checked' : state;
        this.updateCheckbox(this.uiCheckboxOutlet, state);
    }

    updateCheckbox(checkbox, state){
        checkbox.state = state;
        checkbox.updateCheckboxAttributes(state);
        checkbox.handleState();
    }
}