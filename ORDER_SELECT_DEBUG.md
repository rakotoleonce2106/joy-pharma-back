# Debug Guide: Product & Store Select Not Working

## 🔍 Current Fix Applied

Changed the form collection controller to use a simpler approach:
- Uses `temp.querySelectorAll(':scope > div')` to get form rows
- Moves DOM nodes directly without cloning
- Preserves all select elements and their options

## 🧪 How to Test

### Step 1: Open Browser Console
1. Go to `/admin/order/new`
2. Open browser console (F12)
3. Go to Console tab

### Step 2: Click "Add Item"
Look for any JavaScript errors in the console

### Step 3: Check if Prototype Exists
In the console, type:
```javascript
document.querySelector('[data-prototype]')?.dataset.prototype
```

This should show you the HTML prototype.

### Step 4: Inspect the Added Item
After clicking "Add Item", in the console type:
```javascript
document.querySelectorAll('[data-form-collection-target="item"]').length
```

Should show `1` after adding first item.

## 🐛 Common Issues & Solutions

### Issue 1: Select appears but is empty

**Check:** The prototype might not include the `<option>` tags.

**Solution:** Debug in console after clicking Add Item:
```javascript
// Check if select exists
const selects = document.querySelectorAll('[data-form-collection-target="item"] select');
console.log('Number of selects:', selects.length);

// Check options in each select
selects.forEach((select, index) => {
    console.log(`Select ${index} has ${select.options.length} options`);
    console.log('Options:', Array.from(select.options).map(o => o.text));
});
```

### Issue 2: Nothing appears after clicking "Add Item"

**Check:** JavaScript error in console

**Solution:** Look for errors and share them for debugging

### Issue 3: Form submits but items not saved

**Check:** Form field names

**Debug:**
```javascript
// After adding items, check form data
const form = document.querySelector('#order-form');
const formData = new FormData(form);
for (let [key, value] of formData.entries()) {
    if (key.includes('items')) {
        console.log(key, '=', value);
    }
}
```

## 📋 Quick Diagnostic Commands

Run these in browser console after clicking "Add Item":

```javascript
// 1. Check if item was added
console.log('Items count:', document.querySelectorAll('[data-form-collection-target="item"]').length);

// 2. Check prototype content
const proto = document.querySelector('[data-prototype]')?.dataset.prototype;
console.log('Prototype HTML:', proto);

// 3. Check if selects exist in added item
const item = document.querySelector('[data-form-collection-target="item"]');
console.log('Selects in item:', item?.querySelectorAll('select').length);

// 4. Check select options
item?.querySelectorAll('select').forEach((sel, i) => {
    console.log(`Select ${i}:`, sel.name, '- Options:', sel.options.length);
});

// 5. Check select values
item?.querySelectorAll('select').forEach((sel, i) => {
    const options = Array.from(sel.options).map(o => ({text: o.text, value: o.value}));
    console.log(`Select ${i} (${sel.name}):`, options);
});
```

## 🔧 Alternative: Manual Testing

### Step 1: Check the page source
1. Go to `/admin/order/new`
2. Right-click → View Page Source
3. Search for `data-prototype`
4. Copy the prototype HTML and check if it contains `<select>` with `<option>` tags

### Step 2: Check rendered HTML after Add Item
1. Click "Add Item"
2. Right-click on the new item → Inspect Element
3. Look for `<select>` tags
4. Check if they have `<option>` children

## 🚨 If Still Not Working

### Try this alternative approach in the template

Edit `templates/components/admin/order-form.html.twig`:

Change the items section to NOT use data-controller:

```twig
{# Remove data-controller from this div #}
<div class="space-y-4">
    <div class="flex items-center justify-between pb-2 border-b">
        <div class="flex items-center gap-2">
            <twig:ux:icon name="lucide:shopping-cart" class="h-5 w-5 text-primary"/>
            <h3 class="text-lg font-semibold">Order Items</h3>
        </div>
        <button 
            type="button" 
            onclick="addOrderItem()" 
            class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary hover:bg-primary/90 rounded-lg"
        >
            Add Item
        </button>
    </div>

    <div id="order-items-container" class="space-y-3">
        {% for item in form.items %}
            {# existing items #}
        {% endfor %}
    </div>
</div>

<script>
let itemIndex = {{ form.items|length }};

function addOrderItem() {
    const container = document.getElementById('order-items-container');
    const prototype = {{ form_widget(form.items.vars.prototype)|json_encode|raw }};
    const newItem = prototype.replace(/__name__/g, itemIndex);
    
    const wrapper = document.createElement('div');
    wrapper.className = 'border rounded-lg p-4 bg-card';
    wrapper.innerHTML = newItem;
    
    container.appendChild(wrapper);
    itemIndex++;
}
</script>
```

## 📸 Screenshot Request

If still not working, please provide:
1. Screenshot of browser console after clicking "Add Item"
2. Screenshot of the page after clicking "Add Item"
3. Output of the diagnostic commands above

---

**Last Updated:** 2025-10-27  
**Status:** Awaiting Test Results

