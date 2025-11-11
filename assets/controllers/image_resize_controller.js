import { Controller } from "@hotwired/stimulus";

export default class ImageResizeController extends Controller {
    static targets = ["image", "resizeHandle"];
    static values = {
        minWidth: { type: Number, default: 100 },
        minHeight: { type: Number, default: 100 },
        maxWidth: { type: Number, default: 800 },
        maxHeight: { type: Number, default: 800 },
    };

    connect() {
        this.isResizing = false;
        this.startX = 0;
        this.startY = 0;
        this.startWidth = 0;
        this.startHeight = 0;
        
        // Bind methods to preserve context
        this.handleResize = this.handleResize.bind(this);
        this.stopResize = this.stopResize.bind(this);
    }

    disconnect() {
        this.stopResize();
    }

    startResize(event) {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
        
        this.isResizing = true;
        this.startX = event.clientX;
        this.startY = event.clientY;
        
        if (this.hasImageTarget) {
            const rect = this.imageTarget.getBoundingClientRect();
            this.startWidth = rect.width;
            this.startHeight = rect.height;
        }

        // Add class to body to prevent text selection during resize
        document.body.style.userSelect = 'none';
        document.body.style.cursor = 'nwse-resize';

        document.addEventListener("mousemove", this.handleResize, { passive: false });
        document.addEventListener("mouseup", this.stopResize, { passive: false });
    }

    handleResize(event) {
        if (!this.isResizing || !this.hasImageTarget) return;

        event.preventDefault();
        event.stopPropagation();

        const deltaX = event.clientX - this.startX;
        const deltaY = event.clientY - this.startY;

        let newWidth = this.startWidth + deltaX;
        let newHeight = this.startHeight + deltaY;

        // Respecter les limites min/max
        newWidth = Math.max(this.minWidthValue, Math.min(this.maxWidthValue, newWidth));
        newHeight = Math.max(this.minHeightValue, Math.min(this.maxHeightValue, newHeight));

        this.imageTarget.style.width = `${newWidth}px`;
        this.imageTarget.style.height = `${newHeight}px`;
    }

    stopResize() {
        if (!this.isResizing) return;
        
        this.isResizing = false;
        
        // Restore body styles
        document.body.style.userSelect = '';
        document.body.style.cursor = '';
        
        document.removeEventListener("mousemove", this.handleResize);
        document.removeEventListener("mouseup", this.stopResize);
    }
}

