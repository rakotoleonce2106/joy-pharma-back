import { Controller } from "@hotwired/stimulus";
import { createPopper } from "@popperjs/core";
import { useDebounce, useHover } from "stimulus-use";

export default  class UITooltip extends Controller {
    static debounces = ["mouseEnter", "mouseLeave"];
    static values = {
        placement: String
    }
    static targets = ["content", "wrapper", "trigger"];

    initialize() {
        if (!['top','bottom','right','left'].includes(this.placementValue)) {
            throw new Error('Invalid placement value');
        }
    }

    disconnect() {
        this.popperInstance.destroy();

        this.wrapperTarget.classList.add("hidden");
        this.contentTarget.classList.add("hidden");
        this.contentTarget.dataset.state = "closed";
        this.triggerTarget.dataset.state = "closed";
    }

    connect() {
        useDebounce(this);
        useHover(this, { element: this.triggerTarget });
        this.popperInstance = createPopper(this.triggerTarget, this.wrapperTarget, {
            placement: this.placementValue || "top",
            modifiers: [
                {
                    name: "offset",
                    options: {
                        offset: [0, 4],
                    },
                },
            ],
        });
    }

    mouseEnter() {
        this.updateTooltip();
        this.showTooltip();
    }

    mouseLeave() {
        this.updateTooltip();
        this.hideTooltip();
    }

    updateTooltip() {
        this.popperInstance.update();
    }

    showTooltip() {
        this.updateTooltip();
        this.wrapperTarget.classList.remove("hidden");
        this.contentTarget.classList.remove("hidden");
        this.contentTarget.dataset.state = "open";
        this.triggerTarget.dataset.state = "open";
    }

    hideTooltip() {
        this.updateTooltip();
        this.wrapperTarget.classList.add("hidden");
        this.contentTarget.classList.add("hidden");
        this.contentTarget.dataset.state = "closed";
        this.triggerTarget.dataset.state = "closed";
    }
}