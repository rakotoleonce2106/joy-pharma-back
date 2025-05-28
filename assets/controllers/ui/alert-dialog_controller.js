import '@kanety/stimulus-static-actions';
import UIDialog from "./dialog_controller.js";

export default class UIAlertDialog extends UIDialog {
    static targets = ['trigger', 'content', 'action', 'cancel', 'overlay', 'wrapper'];
    static actions = [
        ['trigger', 'click->open'],
        ['cancel', 'click->close'],
        ['action', 'click->close'],
    ];

    connect() {
        this.removeAction('overlay', 'click->close')
    }
}