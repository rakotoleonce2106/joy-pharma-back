import { Controller } from '@hotwired/stimulus';

/**
 * Enhanced Form Collection Controller
 * Manages dynamic addition and removal of form items
 */
export default class extends Controller {
    static targets = ['container', 'item'];
    static values = {
        index: Number
    };

    connect() {
        // Initialize index from existing items
        this.indexValue = this.itemTargets.length;
        console.log('Form collection controller connected. Initial items:', this.indexValue);
    }

    addItem(event) {
        event.preventDefault();
        
        const button = event.currentTarget;
        const prototype = button.dataset.prototype;
        
        if (!prototype) {
            console.error('No prototype found in button dataset');
            return;
        }

        console.log('Adding new item with index:', this.indexValue);

        // Remove empty state if it exists
        this.removeEmptyState();

        // Replace __name__ placeholder with actual index
        const newItemHtml = prototype.replace(/__name__/g, this.indexValue);
        
        // Create the item wrapper
        const itemWrapper = this.createItemWrapper();
        
        // Create grid for form fields
        const grid = this.createGrid();
        grid.innerHTML = newItemHtml; // Insert the prototype HTML directly
        
        itemWrapper.appendChild(grid);
        
        // Add remove button
        const removeButton = this.createRemoveButton();
        itemWrapper.appendChild(removeButton);
        
        // Add to container with animation
        this.containerTarget.appendChild(itemWrapper);
        
        // Trigger animation
        requestAnimationFrame(() => {
            itemWrapper.style.opacity = '1';
            itemWrapper.style.transform = 'translateY(0)';
        });
        
        // Increment index for next item
        this.indexValue++;
        
        // Trigger a custom event for other controllers (like order-total)
        this.dispatch('itemAdded', { detail: { index: this.indexValue - 1 } });
        
        // Add event listeners for real-time total calculation
        this.attachCalculationListeners(itemWrapper);
        
        console.log('Item added successfully. Total items:', this.itemTargets.length);
    }

    removeItem(event) {
        event.preventDefault();
        
        const button = event.currentTarget;
        const item = button.closest('[data-form-collection-target="item"]');
        
        if (!item) {
            console.error('Could not find item to remove');
            return;
        }

        console.log('Removing item. Current count:', this.itemTargets.length);

        // Trigger custom event before removal
        this.dispatch('itemRemoving', { detail: { item } });

        // Animate out
        item.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
        item.style.opacity = '0';
        item.style.transform = 'scale(0.95)';
        
        setTimeout(() => {
            item.remove();
            
            console.log('Item removed. Remaining items:', this.itemTargets.length);
            
            // Trigger custom event after removal
            this.dispatch('itemRemoved');
            
            // Show empty state if no items left
            if (this.itemTargets.length === 0) {
                this.showEmptyState();
            }
        }, 300);
    }

    // Helper methods

    createItemWrapper() {
        const wrapper = document.createElement('div');
        wrapper.dataset.formCollectionTarget = 'item';
        wrapper.className = 'border rounded-lg p-4 bg-card hover:shadow-md transition-all';
        wrapper.style.opacity = '0';
        wrapper.style.transform = 'translateY(-10px)';
        wrapper.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
        return wrapper;
    }

    createGrid() {
        const grid = document.createElement('div');
        grid.className = 'grid gap-4 md:grid-cols-4';
        return grid;
    }

    createRemoveButton() {
        const buttonContainer = document.createElement('div');
        buttonContainer.className = 'mt-3 flex justify-end';
        
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-white bg-destructive hover:bg-destructive/90 rounded-md transition-all shadow-sm';
        button.dataset.action = 'form-collection#removeItem';
        button.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 6h18"></path>
                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
            </svg>
            Remove
        `;
        
        buttonContainer.appendChild(button);
        return buttonContainer;
    }

    removeEmptyState() {
        const emptyState = this.containerTarget.querySelector('.empty-state-placeholder');
        if (emptyState) {
            emptyState.style.transition = 'opacity 0.3s ease-out';
            emptyState.style.opacity = '0';
            setTimeout(() => emptyState.remove(), 300);
        }
    }

    showEmptyState() {
        // Check if empty state already exists
        if (this.containerTarget.querySelector('.empty-state-placeholder')) {
            return;
        }
        
        const emptyState = document.createElement('div');
        emptyState.className = 'border-2 border-dashed rounded-lg p-8 text-center hover:border-primary transition-colors bg-muted/30 empty-state-placeholder';
        emptyState.style.opacity = '0';
        emptyState.innerHTML = `
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary/10 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary">
                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/>
                    <path d="M3 6h18"/>
                    <path d="M16 10a4 4 0 0 1-8 0"/>
                </svg>
            </div>
            <p class="text-sm font-semibold mb-1">No items added yet</p>
            <p class="text-xs text-muted-foreground">Click "Add Item" button above to add products to this order</p>
        `;
        
        this.containerTarget.appendChild(emptyState);
        
        // Animate in
        requestAnimationFrame(() => {
            emptyState.style.transition = 'opacity 0.3s ease-in';
            emptyState.style.opacity = '1';
        });
    }

    attachCalculationListeners(item) {
        // Find quantity and product inputs
        const quantityInput = item.querySelector('input[id*="quantity"]');
        const productSelect = item.querySelector('select[id*="product"]');
        const storeSelect = item.querySelector('select[id*="store"]');
        
        // Attach listeners for order-total controller
        if (quantityInput) {
            quantityInput.dataset.action = 'change->order-total#itemChanged input->order-total#itemChanged';
        }
        if (productSelect) {
            productSelect.dataset.action = 'change->order-total#itemChanged';
        }
        if (storeSelect) {
            storeSelect.dataset.action = 'change->order-total#itemChanged';
        }
    }

    // Custom event dispatcher
    dispatch(eventName, detail = {}) {
        const event = new CustomEvent(`form-collection:${eventName}`, {
            bubbles: true,
            cancelable: true,
            detail
        });
        this.element.dispatchEvent(event);
    }
}
