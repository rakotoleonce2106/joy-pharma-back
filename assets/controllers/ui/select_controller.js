import UIPopover from "./popover_controller.js";

export default class UISelect extends UIPopover {
    static targets = super.targets.concat(["item", "search"])
    static outlets = ["ui--input", "ui--text"];
    static values = {...super.values, isMultiple: Boolean, searchable: Boolean}

    connect() {
        super.connect()
        this.setSelectedItem();
        this.searchInitialized = false;
    }
    
    initializeSearch() {
        if (this.searchInitialized) return;
        
        // Check if searchable - handle both boolean and string values
        const isSearchable = this.searchableValue === true || this.searchableValue === 'true' || (this.hasSearchTarget && this.element.hasAttribute('data-searchable'));
        
        if (isSearchable && this.hasSearchTarget) {
            console.log('Initializing search for select', { 
                searchableValue: this.searchableValue, 
                hasSearchTarget: this.hasSearchTarget,
                itemCount: this.itemTargets.length 
            });
            
            this.searchTarget.addEventListener('input', this.filterItems.bind(this));
            this.searchTarget.addEventListener('keydown', this.handleSearchKeydown.bind(this));
            // Prevent select from closing when clicking in search input
            this.searchTarget.addEventListener('click', (e) => e.stopPropagation());
            this.searchInitialized = true;
        }
    }
    
    filterItems(event) {
        if (!event || !event.target) {
            console.error('filterItems called without valid event');
            return;
        }
        
        const searchTerm = event.target.value.toLowerCase().trim();
        const items = this.itemTargets;
        
        console.log('Filtering items', { 
            searchTerm, 
            itemCount: items.length,
            hasItems: items.length > 0,
            firstItem: items.length > 0 ? items[0].textContent : 'none'
        });
        
        if (!items || items.length === 0) {
            console.warn('No items found to filter');
            return;
        }
        
        if (searchTerm === '') {
            // Show all items if search is empty
            items.forEach((item) => {
                item.style.display = '';
                item.classList.remove('hidden');
            });
            return;
        }
        
        let visibleCount = 0;
        items.forEach((item, index) => {
            if (!item) return;
            
            const text = (item.textContent || '').toLowerCase().trim();
            const matches = text.includes(searchTerm);
            
            if (matches) {
                item.style.display = '';
                item.classList.remove('hidden');
                visibleCount++;
            } else {
                item.style.display = 'none';
                item.classList.add('hidden');
            }
        });
        
        console.log('Filter complete', { visibleCount, total: items.length });
    }
    
    handleSearchKeydown(event) {
        // Prevent closing select when typing in search
        if (event.key === 'Escape') {
            event.stopPropagation();
            this.hide();
        } else if (event.key === 'Enter') {
            event.preventDefault();
            // Select first visible item
            const firstVisible = this.itemTargets.find(item => item.style.display !== 'none');
            if (firstVisible) {
                firstVisible.click();
            }
        }
    }
    
    show() {
        super.show();
        
        // Small delay to ensure DOM is ready
        setTimeout(() => {
            // Initialize search on first show (since search input is inside hidden wrapper)
            this.initializeSearch();
            
            // Check if searchable
            const isSearchable = this.searchableValue === true || this.searchableValue === 'true' || (this.hasSearchTarget && this.element.hasAttribute('data-searchable'));
            
            // Focus search input if searchable
            if (isSearchable && this.hasSearchTarget) {
                if (this.searchTarget) {
                    this.searchTarget.focus();
                    // Reset filter when opening
                    this.itemTargets.forEach((item) => {
                        item.style.display = '';
                        item.classList.remove('hidden');
                    });
                }
            }
        }, 100);
    }
    
    hide() {
        super.hide();
        
        // Check if searchable
        const isSearchable = this.searchableValue === true || this.searchableValue === 'true' || (this.hasSearchTarget && this.element.hasAttribute('data-searchable'));
        
        // Clear search when hiding if searchable
        if (isSearchable && this.hasSearchTarget) {
            if (this.searchTarget) {
                this.searchTarget.value = '';
            }
            // Reset all items visibility
            this.itemTargets.forEach((item) => {
                item.style.display = '';
            });
        }
    }

    remove(event) {
        event.preventDefault();
        event.stopPropagation();

        this.setValue("");
        this.setInnerText("");
        this.setSelectedItem();
    }

    setSelectedItem() {
        const selectedOptions = this.selectedItems();

        this.itemTargets.forEach((item) => {
            item.removeAttribute("data-selected");
            item.removeAttribute("aria-selected");
        });

        selectedOptions.forEach((item) => {
            item.setAttribute("data-selected", "true");
            item.setAttribute("aria-selected", "true");
        });
    }

    selectedItems() {
        return this.itemTargets.filter((item) => {
            if (this.isMultipleValue) {
                const selected = Array.from(this.uiInputOutlet.element.selectedOptions).map(option => option.value);
                return selected.includes(item.dataset.value);
            }
            return item.dataset.value === this.uiInputOutlet.getValue();
        });
    }

    selectItem(event) {
        event.preventDefault();
        const selectedValue = event.currentTarget.dataset.value;
        const selectedText = event.currentTarget.innerText;
        if (this.isMultipleValue) {
            const selectedOptions = this.uiInputOutlet.element.selectedOptions;
            const selectedValues = Array.from(selectedOptions).map(option => option.value);

            const option = this.uiInputOutlet.element.querySelector(`option[value="${selectedValue}"]`);
            const isSelected = selectedValues.includes(selectedValue);

            option.selected = !isSelected;

            this.uiInputOutlet.element.dispatchEvent(new CustomEvent('change', {bubbles: true}));
        } else {
            this.setValue(selectedValue);
            this.uiInputOutlet.element.dispatchEvent(new CustomEvent('change', {bubbles: true}));
        }

        this.setSelectedItem();
    }

    setValue(selectedValue) {
        this.uiInputOutlet.setValue(selectedValue);
    }

    setInnerText(selectedOption) {
        this.uiTextOutlet.setText(selectedOption);
    }

    removeItem(event){
        event.preventDefault();
        event.stopPropagation();

        const selectedValue = event.currentTarget.dataset.value;
        const option = this.uiInputOutlet.element.querySelector(`option[value="${selectedValue}"]`);

        option.selected = false;
        this.uiInputOutlet.element.dispatchEvent(new CustomEvent('change', {bubbles: true}));

        this.setSelectedItem();
    }
}