# Store Features - Visual Guide

## 🔐 Store Owner Login Section

### Location
Found in both Create and Edit Store pages, after the Store Image section.

### What It Looks Like
```
┌─────────────────────────────────────────────┐
│  🔑 Store Owner Login                       │
│  ───────────────────────────────────────    │
│                                             │
│  Login credentials for the store owner to  │
│  access their dashboard                    │
│                                             │
│  ┌──────────────────────────────────────┐  │
│  │ Login Email ⓘ                        │  │
│  │ store@example.com                     │  │
│  │ This email will be used for store     │  │
│  │ owner login                           │  │
│  └──────────────────────────────────────┘  │
│                                             │
│  ┌──────────────────────────────────────┐  │
│  │ Password                              │  │
│  │ ●●●●●●●●                               │  │
│  │ Leave empty to auto-generate or keep  │  │
│  │ existing password                     │  │
│  └──────────────────────────────────────┘  │
│                                             │
│  ┌──────────────────────────────────────┐  │
│  │ Confirm Password                      │  │
│  │ ●●●●●●●●                               │  │
│  └──────────────────────────────────────┘  │
│                                             │
│  ℹ️ Password Information                   │
│  If no password is provided, the default   │
│  password JoyPharma2025! will be used      │
└─────────────────────────────────────────────┘
```

### Features
- ✅ Email validation
- ✅ Password confirmation
- ✅ Minimum 8 characters
- ✅ Auto-generate option
- ✅ Pre-populated on edit

---

## 📦 Store Products Section

### Location
Found in Store Edit page, below the main store information form.

### What It Looks Like (With Products)
```
┌──────────────────────────────────────────────────────────────────┐
│  📦 Store Products                              12 products       │
│  Products available in this store with pricing information       │
│  ────────────────────────────────────────────────────────────    │
│                                                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ Product     │ Unit Price │ Total Price │ Stock   │ Status  │ │
│  ├────────────────────────────────────────────────────────────┤ │
│  │ [IMG] Aspir │  1,500 Ar  │  3,000 Ar  │ ✓ 50    │ ● Active│ │
│  │      Brand  │  per unit  │   total    │         │         │ │
│  ├────────────────────────────────────────────────────────────┤ │
│  │ [IMG] Parac │     -      │  2,500 Ar  │ ⚠ 5     │ ● Active│ │
│  │      Brand  │            │   total    │         │         │ │
│  ├────────────────────────────────────────────────────────────┤ │
│  │ [📦] Vitami │  5,000 Ar  │ 10,000 Ar  │ ✗ Out   │ Inactive│ │
│  │      (none) │  per unit  │   total    │   stock │         │ │
│  └────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────┘
```

### What It Looks Like (No Products)
```
┌──────────────────────────────────────────────┐
│                                              │
│              ┌─────────────┐                 │
│              │     📦      │                 │
│              └─────────────┘                 │
│                                              │
│           No Products Yet                    │
│                                              │
│  This store doesn't have any products        │
│  listed yet.                                 │
│                                              │
└──────────────────────────────────────────────┘
```

---

## 🎨 Color Coding

### Stock Status
- **Green** (>10 items): ✓ High stock - Good to go
- **Amber** (1-10 items): ⚠ Low stock - Needs attention  
- **Red** (0 items): ✗ Out of stock - Critical

### Product Status
- **Green**: ● Active - Available for sale
- **Gray**: ● Inactive - Not available

---

## 💡 Key Information

### Default Password
When creating a store without a password:
```
📧 Email: store@example.com
🔑 Password: JoyPharma2025!
👤 Role: ROLE_STORE
```

### Store Owner Account
- Automatically created when store is created
- Email used for login
- Full name = Store name + "Store Owner"
- Role: ROLE_STORE (store owner permissions)

---

## 📋 Common Tasks

### Creating a Store with Custom Login
1. Fill in store information (name, categories, description)
2. Add contact information
3. Add location
4. Upload store image
5. **Enter login email** (e.g., owner@pharmacy.com)
6. **Enter password** (min 8 chars) and confirm
7. Click "Create Store"

### Updating Store Owner Password
1. Go to Store edit page
2. Scroll to "Store Owner Login" section
3. Email is pre-filled (can be changed)
4. Enter new password and confirm
5. Leave empty to keep existing password
6. Click "Update Store"

### Viewing Store Products
1. Go to Store edit page
2. Scroll down below the form
3. View products table with:
   - Product images and names
   - Unit and total prices
   - Stock levels with color coding
   - Active/Inactive status

---

## 🔍 Product Information Display

### Price Display
- **Unit Price**: Optional, per individual item (e.g., per pill)
- **Total Price**: Required, package/bundle price (highlighted)
- Currency: Ariary (Ar)
- Format: X,XXX.XX Ar

### Stock Display
- Number of units available
- Color-coded badge
- Icon indicator
- Hover effects

### Image Display
- 48x48px thumbnail
- Product image or placeholder
- Rounded corners
- Border styling

---

## 🚀 Best Practices

### Password Management
- ✅ Use strong passwords (8+ characters)
- ✅ Include mix of letters, numbers, symbols
- ✅ Change default password on first login
- ❌ Don't share passwords
- ❌ Don't reuse passwords

### Store Setup
1. Complete all required fields
2. Provide accurate contact information
3. Set precise location coordinates
4. Upload clear store image
5. **Set custom login credentials**
6. Verify owner can login

### Product Management
- Keep stock levels updated
- Set clear unit and total prices
- Use high-quality product images
- Update status based on availability

---

## 🆘 Troubleshooting

### "Email already exists"
- User account with this email already exists
- Use a different email or link to existing user

### "Password fields must match"
- Password and confirm password are different
- Re-enter both fields

### "Products not showing"
- Store has no associated products
- Products are managed separately in Product section
- Create StoreProduct relationships

### "Can't login with credentials"
- Verify email is correct
- Check if password was changed
- Reset password if needed
- Verify user has ROLE_STORE role

---

## 📞 Support

For implementation details, see:
- `STORE_LOGIN_AND_PRODUCTS_IMPLEMENTATION.md`
- Symfony Form documentation
- Doctrine relationships documentation

---

## 🎯 Summary

**New Features:**
1. ✅ Store owner login credentials management
2. ✅ Store products listing with pricing
3. ✅ Stock status visualization
4. ✅ Product status indicators
5. ✅ Professional UI/UX

**Benefits:**
- Secure store owner authentication
- Clear product inventory visibility
- Easy credential management
- Beautiful, modern interface
- Comprehensive pricing information

