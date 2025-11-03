import { Controller } from '@hotwired/stimulus';

/**
 * Store Product Prices Controller
 * Displays product prices when a product is selected
 */
export default class extends Controller {
    static targets = ['unitPriceDisplay', 'totalPriceDisplay', 'unitPriceInput', 'totalPriceInput', 'unitPriceValue', 'totalPriceValue'];
    
    connect() {
        // Find the hidden select element within the form
        const form = this.element.closest('form');
        if (form) {
            const selectElement = form.querySelector('select[id*="product"]');
            if (selectElement) {
                console.log('Store product prices controller connected', { 
                    selectId: selectElement.id,
                    optionsCount: selectElement.options.length 
                });
                
                // Listen for product selection change on hidden select
                selectElement.addEventListener('change', this.handleProductChange.bind(this));
                
                // Check if product is already selected on load
                // Use multiple timeouts to ensure DOM is ready
                setTimeout(() => {
                    if (selectElement.value) {
                        console.log('🔍 Product already selected on load:', selectElement.value);
                        this.updatePricesForSelectedProduct(selectElement);
                    }
                }, 100);
                
                // Also try after a longer delay to catch any late DOM updates
                setTimeout(() => {
                    if (selectElement.value) {
                        console.log('🔍 Retry: Product already selected on load:', selectElement.value);
                        this.updatePricesForSelectedProduct(selectElement);
                    }
                }, 500);
            }
            
            // Also listen to any change event that might affect the product select
            // This includes changes from the UI select component
            form.addEventListener('change', (e) => {
                // Check if it's related to product selection
                const selectElement = form.querySelector('select[id*="product"]');
                if (selectElement && (e.target === selectElement || e.target.closest('[data-ui--select-target]'))) {
                    setTimeout(() => {
                        this.updatePricesForSelectedProduct(selectElement);
                    }, 150);
                }
            }, true);
            
            // Listen to click events on UI select items to catch selection
            form.addEventListener('click', (e) => {
                const item = e.target.closest('[data-ui--select-target="item"][data-value]');
                if (item) {
                    setTimeout(() => {
                        const selectElement = form.querySelector('select[id*="product"]');
                        if (selectElement) {
                            this.updatePricesForSelectedProduct(selectElement);
                        }
                    }, 200);
                }
            }, true);
        }
    }
    
    handleProductChange(event) {
        const selectElement = event.target;
        this.updatePricesForSelectedProduct(selectElement);
    }
    
    updatePricesForSelectedProduct(selectElement) {
        if (!selectElement) {
            console.error('❌ No select element provided');
            return;
        }
        
        const selectedIndex = selectElement.selectedIndex;
        console.log('📦 Updating prices for product', {
            selectedIndex,
            selectValue: selectElement.value,
            optionsCount: selectElement.options.length
        });
        
        if (selectedIndex < 0 || !selectElement.value) {
            console.log('⚠️ No product selected');
            this.hidePrices();
            return;
        }
        
        const selectedOption = selectElement.options[selectedIndex];
        
        if (!selectedOption) {
            console.error('❌ Selected option not found');
            return;
        }
        
        console.log('📋 Selected option:', {
            value: selectedOption.value,
            text: selectedOption.text,
            allAttributes: Array.from(selectedOption.attributes).map(a => ({name: a.name, value: a.value}))
        });
        
        // Get prices from data attributes on the option element
        let unitPrice = selectedOption.getAttribute('data-unit-price');
        let totalPrice = selectedOption.getAttribute('data-total-price');
        
        console.log('💰 Prices from option:', { 
            unitPrice, 
            totalPrice,
            optionValue: selectedOption.value,
            allDataAttrs: Array.from(selectedOption.attributes)
                .filter(a => a.name.startsWith('data-'))
                .map(a => ({name: a.name, value: a.value}))
        });
        
        // If not found on option, try to find from UI select item element
        if ((!unitPrice || unitPrice === '' || !totalPrice || totalPrice === '') && selectedIndex >= 0) {
            const form = selectElement.closest('form');
            if (form) {
                // Find the UI select item that matches the selected value
                const selectedValue = selectedOption.value;
                const uiSelectItem = form.querySelector(`[data-value="${selectedValue}"][data-ui--select-target="item"]`);
                if (uiSelectItem) {
                    console.log('✅ Found UI select item:', {
                        value: uiSelectItem.getAttribute('data-value'),
                        allAttributes: Array.from(uiSelectItem.attributes).map(a => ({name: a.name, value: a.value}))
                    });
                    const itemUnitPrice = uiSelectItem.getAttribute('data-unit-price');
                    const itemTotalPrice = uiSelectItem.getAttribute('data-total-price');
                    if (!unitPrice || unitPrice === '') {
                        unitPrice = itemUnitPrice;
                        console.log('📌 Got unit price from UI item:', unitPrice);
                    }
                    if (!totalPrice || totalPrice === '') {
                        totalPrice = itemTotalPrice;
                        console.log('📌 Got total price from UI item:', totalPrice);
                    }
                } else {
                    console.log('⚠️ UI select item not found for value:', selectedValue);
                }
            }
        }
        
        console.log('🎯 Final retrieved prices', { unitPrice, totalPrice });
        
        // Display prices
        this.displayPrices(unitPrice, totalPrice);
        
        // Fill form fields with product prices
        this.fillPriceInputs(unitPrice, totalPrice);
    }
    
    displayPrices(unitPrice, totalPrice) {
        // Display unit price
        if (this.hasUnitPriceDisplayTarget) {
            if (this.hasUnitPriceValueTarget) {
                if (unitPrice && unitPrice !== '' && parseFloat(unitPrice) > 0) {
                    this.unitPriceValueTarget.textContent = this.formatPrice(unitPrice);
                    this.unitPriceDisplayTarget.classList.remove('hidden');
                    this.unitPriceDisplayTarget.style.display = 'flex';
                } else {
                    this.unitPriceValueTarget.textContent = 'Not set';
                    this.unitPriceDisplayTarget.classList.remove('hidden');
                    this.unitPriceDisplayTarget.style.display = 'flex';
                }
            } else {
                // Fallback if target structure changed
                const priceElement = this.unitPriceDisplayTarget.querySelector('[data-store-product-prices-target="unitPriceValue"]') || 
                                    this.unitPriceDisplayTarget.querySelector('p:last-child');
                if (priceElement) {
                    priceElement.textContent = unitPrice && unitPrice !== '' ? this.formatPrice(unitPrice) : 'Not set';
                    this.unitPriceDisplayTarget.classList.remove('hidden');
                    this.unitPriceDisplayTarget.style.display = 'flex';
                }
            }
        }
        
        // Display total price
        if (this.hasTotalPriceDisplayTarget) {
            if (this.hasTotalPriceValueTarget) {
                if (totalPrice && totalPrice !== '' && parseFloat(totalPrice) > 0) {
                    this.totalPriceValueTarget.textContent = this.formatPrice(totalPrice);
                    this.totalPriceDisplayTarget.classList.remove('hidden');
                    this.totalPriceDisplayTarget.style.display = 'flex';
                } else {
                    this.totalPriceValueTarget.textContent = 'Not set';
                    this.totalPriceDisplayTarget.classList.remove('hidden');
                    this.totalPriceDisplayTarget.style.display = 'flex';
                }
            } else {
                // Fallback if target structure changed
                const priceElement = this.totalPriceDisplayTarget.querySelector('[data-store-product-prices-target="totalPriceValue"]') || 
                                    this.totalPriceDisplayTarget.querySelector('p:last-child');
                if (priceElement) {
                    priceElement.textContent = totalPrice && totalPrice !== '' ? this.formatPrice(totalPrice) : 'Not set';
                    this.totalPriceDisplayTarget.classList.remove('hidden');
                    this.totalPriceDisplayTarget.style.display = 'flex';
                }
            }
        }
    }
    
    fillPriceInputs(unitPrice, totalPrice) {
        // Find the actual input fields (money fields use type="number")
        const form = this.element.closest('form');
        if (!form) return;
        
        // Find unit price input - StoreProduct uses 'unitPrice'
        let unitPriceInput = form.querySelector('input[id*="unitPrice"], input[name*="unitPrice"]');
        if (!unitPriceInput) {
            // Try alternative selectors
            unitPriceInput = form.querySelector('input[type="number"][id*="unit"]');
        }
        
        // Find total price input - StoreProduct uses 'price' field (not 'totalPrice')
        // Try multiple selectors to find the price field
        totalPriceInput = form.querySelector('input[id*="price"]:not([id*="unit"]):not([id*="stock"])');
        if (!totalPriceInput) {
            totalPriceInput = form.querySelector('input[name*="[price]"]:not([name*="unit"]):not([name*="stock"])');
        }
        if (!totalPriceInput) {
            // Try alternative: find input after unitPrice in the form
            const inputs = Array.from(form.querySelectorAll('input[type="number"]'));
            const unitPriceIndex = unitPriceInput ? inputs.indexOf(unitPriceInput) : -1;
            if (unitPriceIndex >= 0 && inputs[unitPriceIndex + 1]) {
                totalPriceInput = inputs[unitPriceIndex + 1];
            } else {
                // Last resort: find any number input that's not unitPrice or stock
                const allNumberInputs = Array.from(form.querySelectorAll('input[type="number"]'));
                totalPriceInput = allNumberInputs.find(input => 
                    input !== unitPriceInput && 
                    !input.id.includes('stock') && 
                    !input.name.includes('stock')
                );
            }
        }
        
        console.log('🔍 Filling prices', { 
            unitPrice, 
            totalPrice, 
            unitPriceInput: !!unitPriceInput, 
            totalPriceInput: !!totalPriceInput,
            unitPriceInputId: unitPriceInput?.id,
            unitPriceInputName: unitPriceInput?.name,
            totalPriceInputId: totalPriceInput?.id,
            totalPriceInputName: totalPriceInput?.name,
            allNumberInputs: Array.from(form.querySelectorAll('input[type="number"]')).map(i => ({id: i.id, name: i.name, value: i.value}))
        });
        
        // Always fill unit price with product default (user can modify later)
        if (unitPriceInput) {
            if (unitPrice !== null && unitPrice !== '' && unitPrice !== undefined) {
                const priceValue = parseFloat(unitPrice);
                if (!isNaN(priceValue) && priceValue > 0) {
                    // Always set the value from product (even if field already has value)
                    unitPriceInput.value = priceValue.toFixed(2);
                    // Trigger change event to update any dependent fields
                    unitPriceInput.dispatchEvent(new Event('input', { bubbles: true }));
                    unitPriceInput.dispatchEvent(new Event('change', { bubbles: true }));
                    console.log('✅ Filled unit price:', unitPriceInput.value, 'from product price:', unitPrice);
                } else {
                    // If price is 0 or invalid, leave as is (don't clear)
                    console.log('⚠️ Unit price is 0 or invalid, keeping current value');
                }
            } else {
                console.log('⚠️ Unit price is null or empty');
            }
        } else {
            console.error('❌ Unit price input not found!');
        }
        
        // Always fill total price with product default (user can modify later)
        if (totalPriceInput) {
            if (totalPrice !== null && totalPrice !== '' && totalPrice !== undefined) {
                const priceValue = parseFloat(totalPrice);
                if (!isNaN(priceValue) && priceValue > 0) {
                    // Always set the value from product (even if field already has value)
                    totalPriceInput.value = priceValue.toFixed(2);
                    // Trigger change event to update any dependent fields
                    totalPriceInput.dispatchEvent(new Event('input', { bubbles: true }));
                    totalPriceInput.dispatchEvent(new Event('change', { bubbles: true }));
                    console.log('✅ Filled total price:', totalPriceInput.value, 'from product price:', totalPrice);
                } else {
                    // If price is 0 or invalid, leave as is (don't clear)
                    console.log('⚠️ Total price is 0 or invalid, keeping current value');
                }
            } else {
                console.log('⚠️ Total price is null or empty');
            }
        } else {
            console.error('❌ Total price input not found!');
        }
    }
    
    hidePrices() {
        if (this.hasUnitPriceDisplayTarget) {
            this.unitPriceDisplayTarget.classList.add('hidden');
            this.unitPriceDisplayTarget.style.display = 'none';
        }
        if (this.hasTotalPriceDisplayTarget) {
            this.totalPriceDisplayTarget.classList.add('hidden');
            this.totalPriceDisplayTarget.style.display = 'none';
        }
    }
    
    formatPrice(price) {
        const numPrice = parseFloat(price);
        if (isNaN(numPrice)) return '0.00';
        return numPrice.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + ' MGA';
    }
}



