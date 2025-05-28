import {Controller} from "@hotwired/stimulus";
import '@kanety/stimulus-static-actions'

export default class UIDialog extends Controller {
    static targets = ['trigger', 'wrapper', 'content', 'close', 'overlay', 'dynamicContent', 'loadingTemplate']
    static actions = [
        ['trigger', 'click->open'],
        ['close', 'click->close'],
        ['overlay', 'click->close']
    ]

    observer = null;
    opened = false;

    connect() {
        if (this.hasDynamicContentTarget) {
            // when the content changes, call this.open()
            this.observer = new MutationObserver(() => {
                const shouldOpen = this.dynamicContentTarget.innerHTML.trim().length > 0;

                if (shouldOpen && !this.opened) {
                    this.open();
                } else if (!shouldOpen && this.opened) {
                    this.close();
                }
            });

            this.observer.observe(this.dynamicContentTarget, {
                childList: true,
                characterData: true,
                subtree: true
            });
        }
    }

    disconnect() {
        this.setElementsState('closed', true);

        if (this.observer) {
            this.observer.disconnect();
        }
    }

    open() {
        this.wrapperTarget.classList.remove('hidden')
        this.setElementsState('open');
        document.body.classList.add('overflow-hidden');

        this.opened = true;
    }

    close() {
        this.setElementsState('closed', true);
        document.body.classList.remove('overflow-hidden');

        this.opened = false;
    }

    setElementsState(state, isClosing = false, delay = 120) {

        const elements = [this.element]

        if (this.hasOverlayTarget) {
            elements.push(this.overlayTarget);
        }

        if (this.hasTriggerTarget) {
            elements.push(this.triggerTarget);
        }

        if (this.hasContentTarget) {
            elements.push(this.contentTarget);
        }

        if (this.hasCloseTarget) {
            elements.push(...this.closeTargets);
        }

        if (!isClosing) { // if not closing, add wrapperTarget to elements
            elements.push(this.wrapperTarget);
        }

        elements.forEach((target) => {
            target.dataset.state = state;
        })

        if (isClosing) {
            this.delayedCloseWrapper(state, delay);
        }
    }

    delayedCloseWrapper(state, delay) {
        setTimeout(() => {
            this.wrapperTarget.dataset.state = state;
            this.wrapperTarget.classList.add('hidden');
        }, delay);
    }

    removeAction(name, descriptor) {
        this.context.actionSet.actions.forEach((action) => {
            if (action.name === name && action.descriptor === descriptor) {
                const target = `this.${name}Target`
                this.context.actionSet.removeAction(eval(target), action)
            }
        })
    }

    showLoading() {
        // do nothing if the dialog is already open
        if (this.opened) {
            return;
        }

        this.dynamicContentTarget.innerHTML = this.loadingTemplateTarget.innerHTML;
    }
}