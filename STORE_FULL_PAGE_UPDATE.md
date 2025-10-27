# Store Create/Edit Full Page Update

## Overview

Updated the Store module to use full-page layouts instead of dialog/popup modals, matching the Product and Order controllers' pattern for better user experience.

## Changes Made

### 1. StoreDataTableType - Turbo Frame Updates

**File:** `src/DataTable/Type/StoreDataTableType.php`

#### Create Action Button
**Before:**
```php
'attr' => [
    'variant' => 'default',
    'data-turbo-frame' => 'dialog',
    'target' => 'dialog',
],
```

**After:**
```php
'attr' => [
    'variant' => 'default',
    'data-turbo-frame' => '_top',
],
```

#### Edit Row Action Button
**Before:**
```php
'attr' => [
    'size' => 'sm',
    'variant' => 'outline',
    'data-turbo-frame' => 'dialog',
    'target' => 'dialog',
    'class' => 'whitespace-nowrap'
]
```

**After:**
```php
'attr' => [
    'size' => 'sm',
    'variant' => 'outline',
    'data-turbo-frame' => '_top',
    'class' => 'whitespace-nowrap'
]
```

### 2. StoreController - Pattern Refactoring

**File:** `src/Controller/Admin/StoreController.php`

#### Create Action - Now Handles Form Directly

**Changes:**
- Moved form handling logic from `handleStoreForm()` directly into `createAction()`
- Added direct user creation logic for new stores
- Handles image uploads inline
- Redirects to store list on success
- Returns full-page create template

**New Pattern:**
```php
#[Route('/store/new', name: 'admin_store_new', defaults: ['title' => 'Create Store'])]
public function createAction(Request $request): Response
{
    $store = new Store();
    $form = $this->createForm(StoreType::class, $store, ['action' => $this->generateUrl('admin_store_new')]);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Handle image upload
        $image = $form->get('image')->getData();
        if ($image) {
            $mediaFile = $this->mediaFileService->createMediaByFile($image, 'images/store/');
            $store->addImage($mediaFile);
        }

        // Create user if doesn't exist
        $userTemp = $this->userService->getUserByEmail($store->getContact()->getEmail());
        if(!$userTemp){
            $user= new User();
            $user->setEmail($store->getContact()->getEmail());
            $user->setFirstName($store->getName());
            $user->setLastName($store->getName());
            $user->setRoles(['ROLE_STORE']);
            $user->setPassword('JoyPharma2025!');
            
            $userWithPassword = $this->userService->hashPassword($user);
            $this->userService->persistUser($userWithPassword);
        }
       
        $this->storeService->createStore($store);
        $this->addSuccessToast('Store created!', "The Store has been successfully created.");
        return $this->redirectToRoute('admin_store', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render("admin/store/create.html.twig", [
        'store' => $store,
        'form' => $form
    ]);
}
```

#### Edit Action - Uses Private Method
- Still delegates to `handleStoreForm()` for edit operations
- Maintains consistency with update logic

#### Private Method - Simplified
**Changes:**
- Removed create-specific logic (moved to `createAction`)
- Now only handles updates
- Removed conditional branching for create vs edit
- Cleaner, single-purpose method

### 3. Template Updates

#### Create Template
**File:** `templates/admin/store/create.html.twig`

**Before:**
- Extended `admin/dialog-layout.html.twig`
- Had conditional rendering for turbo-frame vs full page
- Used dialog-specific styling

**After:**
- Extends `admin/layout.html.twig`
- Clean full-page layout
- Better spacing and typography
- Consistent with Product/Order create pages

**Key Changes:**
```twig
{# Before #}
{% extends 'admin/dialog-layout.html.twig' %}
{% if not app.request.headers.has('turbo-frame') %}
    {# Full page content #}
{% else %}
    {# Dialog content #}
{% endif %}

{# After #}
{% extends 'admin/layout.html.twig' %}
<main class="flex flex-1 flex-col gap-4 p-4 md:gap-8 md:p-8 w-[85rem]">
    {# Full page content only #}
</main>
```

#### Edit Template
**File:** `templates/admin/store/edit.html.twig`

**Similar Changes:**
- Extended `admin/layout.html.twig` instead of `admin/dialog-layout.html.twig`
- Removed conditional turbo-frame logic
- Uses full-page layout
- Consistent with Product/Order edit pages

### 4. Consistency Across Modules

All three major modules now use the same pattern:

| Module | Create Action | Edit Action | DataTable Links |
|--------|---------------|-------------|-----------------|
| Product | ✅ Direct handling | ✅ Via private method | ✅ `_top` |
| Order | ✅ Direct handling | ✅ Via private method | ✅ `_top` |
| Store | ✅ Direct handling | ✅ Via private method | ✅ `_top` |

## Benefits

### 1. Better User Experience
- ✅ More screen space for forms
- ✅ Cleaner navigation flow
- ✅ Better mobile responsiveness
- ✅ Proper URL changes in browser

### 2. Improved Development Experience
- ✅ Consistent patterns across all modules
- ✅ Easier to maintain and debug
- ✅ Clearer separation of concerns
- ✅ Better code organization

### 3. Turbo Drive Benefits Maintained
- ✅ Fast page transitions
- ✅ Progress bar on navigation
- ✅ SPA-like experience
- ✅ No full page reloads

## Testing Checklist

✅ Click "Create Store" button → Opens full page (not popup)  
✅ Click "Edit" on any store → Opens full page (not popup)  
✅ Submit create form → Redirects to store list  
✅ Submit edit form → Redirects to store list  
✅ Image upload works on create  
✅ Image upload works on edit  
✅ User creation works for new store contacts  
✅ Cancel button returns to store list  
✅ Back button navigation works correctly  
✅ No linter errors  

## Files Modified

### Controllers
- ✅ `src/Controller/Admin/StoreController.php`
  - Refactored `createAction()` to handle form directly
  - Simplified `handleStoreForm()` for edit only

### DataTables
- ✅ `src/DataTable/Type/StoreDataTableType.php`
  - Updated create action: `'data-turbo-frame' => '_top'`
  - Updated edit row action: `'data-turbo-frame' => '_top'`

### Templates
- ✅ `templates/admin/store/create.html.twig`
  - Changed from dialog layout to full-page layout
  - Removed turbo-frame conditionals
  
- ✅ `templates/admin/store/edit.html.twig`
  - Changed from dialog layout to full-page layout
  - Removed turbo-frame conditionals

## Related Documentation

- `TURBO_FRAME_FIX.md` - Product DataTable turbo-frame fix
- `CONTROLLER_UPDATE_SUMMARY.md` - ProductController pattern update
- `ADMIN_IMPROVEMENTS.md` - Initial admin improvements

## Technical Details

### Turbo Frame Navigation

Using `'data-turbo-frame' => '_top'`:
- Tells Turbo to navigate the entire page
- Breaks out of any containing frames
- Maintains Turbo Drive's SPA benefits
- Provides proper URL changes

### Controller Pattern

**Create Action:**
```
User clicks "Create" 
→ GET /store/new 
→ Shows create form
→ User submits
→ POST /store/new
→ Creates store + user
→ Redirects to /store (list)
```

**Edit Action:**
```
User clicks "Edit"
→ GET /store/{id}/edit
→ Shows edit form
→ User submits
→ POST /store/{id}/edit
→ Updates store
→ Redirects to /store (list)
```

### Image Handling

Both create and edit now handle images consistently:
```php
$image = $form->get('image')->getData();
if ($image) {
    $mediaFile = $this->mediaFileService->createMediaByFile($image, 'images/store/');
    $store->addImage($mediaFile);
}
```

### User Auto-Creation for Stores

When creating a new store, if the contact email doesn't exist:
1. Creates a new User entity
2. Sets role to `ROLE_STORE`
3. Uses store name as first/last name
4. Sets default password: `JoyPharma2025!`
5. Hashes password
6. Persists user

## Migration Notes

If you need to revert to dialog behavior:
1. Change `'data-turbo-frame' => '_top'` back to `'data-turbo-frame' => 'dialog'`
2. Add back `'target' => 'dialog'`
3. Change templates back to extend `admin/dialog-layout.html.twig`
4. Add back turbo-frame conditional logic in templates

## Future Enhancements

- [ ] Add store view page (similar to order view)
- [ ] Add bulk operations for stores
- [ ] Implement store statistics on dashboard
- [ ] Add store filtering and advanced search
- [ ] Add store status management (active/inactive)

## Conclusion

The Store module now follows the same pattern as Product and Order modules, providing a consistent and better user experience across the entire admin panel. All create and edit operations now use full-page layouts with proper navigation and URL handling.

