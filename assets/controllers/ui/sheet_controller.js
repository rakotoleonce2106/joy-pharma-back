import '@kanety/stimulus-static-actions'
import UIDialog from "./dialog_controller.js";

export default class UISheet extends UIDialog {
    static values = {
        side: String
    }

    initialize() {
        if (!['left', 'right', 'top', 'bottom'].includes(this.sideValue)) {
            throw new Error('Invalid side value')
        }
    }
}