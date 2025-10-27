import { Controller } from '@hotwired/stimulus';

/**
 * Order Total Calculator Controller
 * Automatically calculates order total based on items
 */
export default class extends Controller {
    static targets = ['totalAmount', 'itemsContainer'];

    connect() {
        console.log('Order total calculator connected');
        
        // Initial calculation
        this.calculateTotal();
        
        // Listen to form-collection events
        this.element.addEventListener('form-collection:itemAdded', () => {
            console.log('Item added event received');
            setTimeout(() => this.calculateTotal(), 100);
        });
        
        this.element.addEventListener('form-collection:itemRemoved', () => {
            console.log('Item removed event received');
            setTimeout(() => this.calculateTotal(), 100);
        });
        
        // Watch for changes in items container with MutationObserver
        if (this.hasItemsContainerTarget) {
            this.setupMutationObserver();
        }
    }

    setupMutationObserver() {
        this.observer = new MutationObserver((mutations) => {
            // Check if mutations actually changed the DOM structure
            const hasStructuralChanges = mutations.some(mutation => 
                mutation.type === 'childList' && mutation.addedNodes.length > 0
            );
            
            if (hasStructuralChanges) {
                console.log('DOM structure changed, recalculating total');
                this.calculateTotal();
            }
        });
        
        this.observer.observe(this.itemsContainerTarget, {
            childList: true,
            subtree: true,
            attributes: false
        });
    }

    disconnect() {
        if (this.observer) {
            this.observer.disconnect();
        }
    }

    calculateTotal() {
        let total = 0;
        let itemCount = 0;
        
        // Find all item containers
        const items = this.element.querySelectorAll('[data-form-collection-target="item"]');
        
        console.log(`Calculating total for ${items.length} items`);
        
        items.forEach((item, index) => {
            const quantityInput = item.querySelector('input[id*="quantity"]');
            const productSelect = item.querySelector('select[id*="product"]');
            
            if (!quantityInput || !productSelect) {
                console.warn(`Item ${index}: Missing quantity or product input`);
                return;
            }
            
            const quantity = parseInt(quantityInput.value) || 0;
            const productId = productSelect.value;
            
            if (quantity > 0 && productId) {
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                const productPrice = parseFloat(selectedOption.dataset.price) || 0;
                
                if (productPrice > 0) {
                    const itemTotal = productPrice * quantity;
                    total += itemTotal;
                    itemCount++;
                    
                    console.log(`Item ${index}: ${quantity} x ${productPrice} = ${itemTotal}`);
                } else {
                    console.warn(`Item ${index}: Product has no price`);
                }
            }
        });
        
        console.log(`Total calculated: ${total} (from ${itemCount} items)`);
        
        // Update the total amount field
        if (this.hasTotalAmountTarget) {
            this.totalAmountTarget.value = total.toFixed(2);
        }
        
        // Update the formatted display
        this.updateDisplay(total);
        
        // Dispatch custom event
        this.dispatch('totalCalculated', { 
            detail: { 
                total, 
                itemCount,
                formattedTotal: this.formatCurrency(total)
            } 
        });
    }

    updateDisplay(total) {
        const displayElement = document.querySelector('[data-order-total-display]');
        if (displayElement) {
            displayElement.textContent = this.formatCurrency(total);
            
            // Add visual feedback on change
            displayElement.classList.add('scale-110');
            displayElement.classList.add('text-green-600');
            
            setTimeout(() => {
                displayElement.classList.remove('scale-110');
                displayElement.classList.remove('text-green-600');
            }, 300);
        }
    }

    formatCurrency(amount) {
        try {
            return new Intl.NumberFormat('fr-MG', {
                style: 'decimal',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount) + ' Ar';
        } catch (e) {
            // Fallback formatting
            return Math.round(amount).toLocaleString() + ' Ar';
        }
    }

    // Called when quantity or product changes (from template data-action)
    itemChanged(event) {
        console.log('Item changed event:', event.target.name || event.target.id);
        this.calculateTotal();
    }

    // Custom event dispatcher
    dispatch(eventName, detail = {}) {
        const event = new CustomEvent(`order-total:${eventName}`, {
            bubbles: true,
            cancelable: true,
            detail
        });
        this.element.dispatchEvent(event);
    }
}
