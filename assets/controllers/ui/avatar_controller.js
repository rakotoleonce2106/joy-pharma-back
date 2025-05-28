import {Controller} from "@hotwired/stimulus";

const LOADING_STATUS = ['idle', 'loading', 'loaded', 'failed']

export default class UIAvatar extends Controller {
    static targets = ['image', 'fallback']
    static values = {
        status: {type: String, default: LOADING_STATUS[0]},
    }

    initialize() {
        this.src = this.imageTarget.src
        this.delay = this.fallbackTarget.dataset.delay
    }

    async connect() {
        this.waitAndLoadImage(this.delay, this.src)
    }

    waitAndLoadImage(delay, src) {
        if (Number(delay) !== 0) {
            window.setTimeout(() => {
                this.imageLoadingStatus(src)
            }, delay)
        } else {
            this.imageLoadingStatus(src)
        }
    }

    statusValueChanged() {
        this.handleVisibility(LOADING_STATUS[2], false, true)
        this.handleVisibility(LOADING_STATUS[3], true, false)
    }

    handleVisibility(status, imageHidden, fallbackHidden) {
        if (this.statusValue === status) {
            this.imageTarget.hidden = imageHidden
            this.fallbackTarget.hidden = fallbackHidden
        }
    }

    imageLoadingStatus(src) {
        if (!src) {
            this.statusValue = LOADING_STATUS[3];
            return;
        }
        this.loadImage(src)
    }

    loadImage(src) {
        const image = new window.Image()
        const updateStatus = (status) => {
            if (this.statusValue === LOADING_STATUS[3]) return;
            this.statusValue = status
        }

        this.statusValue = LOADING_STATUS[1]
        image.onload = () => updateStatus(LOADING_STATUS[2])
        image.onerror = () => updateStatus(LOADING_STATUS[3])
        image.src = src
    }
}