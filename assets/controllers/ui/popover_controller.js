import {Controller} from "@hotwired/stimulus";
import {createPopper} from "@popperjs/core";
import {useClickOutside} from "stimulus-use";

export default class UIPopover extends Controller {
    static targets = ['trigger', 'content', 'wrapper'];

    static values = {
        placement: String,
        trigger: String,
        dismissAfter: Number,
        matchWidth: Boolean,
        offset: Array,
    }

    initialize() {
        if (!['click', 'hover'].includes(this.triggerValue)) {
            throw new Error('Invalid trigger value');
        }

        if (!['auto', 'auto-start', 'auto-end', 'top', 'top-start', 'top-end', 'bottom', 'bottom-start', 'bottom-end', 'right', 'right-start', 'right-end', 'left', 'left-start', 'left-end'].includes(this.placementValue)) {
            throw new Error('Invalid placement value');
        }
    }

    connect() {
        useClickOutside(this);

        if (this.triggerValue === "click") {
            this.triggerTarget.addEventListener("click", this.toggle.bind(this));
        }

        if (this.triggerValue === "hover") {
            this.triggerTarget.addEventListener('mouseenter', this.mouseEnter.bind(this));
            this.triggerTarget.addEventListener('mouseleave', this.delayedHide.bind(this));
            this.wrapperTarget.addEventListener('mouseenter', this.cancelDelayedHide.bind(this));
            this.wrapperTarget.addEventListener('mouseleave', this.delayedHide.bind(this));
        }

        if (this.matchWidthValue) {
            this.contentTarget.style.width = this.triggerTarget.offsetWidth + "px";
        }

        this.popperInstance = createPopper(this.triggerTarget, this.wrapperTarget, {
            placement: this.contentTarget.dataset.side || "bottom",
            modifiers: [
                {
                    name: "offset",
                    options: {
                        offset: this.offsetValue.length ? this.offsetValue : [0, 4],
                    },
                }
            ],
        });
    }

    // Show the popover
    show() {
        this.popperInstance.update();

        this.wrapperTarget.classList.remove("hidden");
        this.contentTarget.classList.remove("hidden");
        this.contentTarget.dataset.state = "open";
        this.triggerTarget.dataset.state = "open";
        this.element.dataset.state = "open";
    }

    // Hide the popover
    hide() {
        this.popperInstance.update();

        this.wrapperTarget.classList.add("hidden");
        this.contentTarget.classList.add("hidden");
        this.contentTarget.dataset.state = "closed";
        this.triggerTarget.dataset.state = "closed";
        this.element.dataset.state = "closed";
    }

    clickOutside(event) {
        this.hide();
    }

    // Toggle the popover on demand
    toggle(event) {
        if (this.wrapperTarget.classList.contains("hidden")) {
            this.show();

            if (this.hasDismissAfterValue) {
                setTimeout(() => {
                    this.hide();
                }, this.dismissAfterValue);
            }
        } else {
            this.hide();
        }
    }

    // initiate a delayed hiding of the popover
    delayedHide() {
        this.timeoutId = setTimeout(() => {
            this.hide();
        }, 200);
    }

    // cancel a delayed hide if it exists
    cancelDelayedHide() {
        if (this.timeoutId) {
            clearTimeout(this.timeoutId);
        }
    }

    mouseEnter() {
        this.show();
        // also good idea to cancel any delayed hiding here
        this.cancelDelayedHide();
    }
}