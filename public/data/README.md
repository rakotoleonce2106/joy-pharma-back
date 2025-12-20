# Répertoires de stockage des fichiers

## Structure

```
public/
├── images/
│   ├── categories/        ← Images des catégories
│   └── products/         ← Images des produits
├── icons/
│   └── categories/       ← Icons/SVG des catégories
└── media/                ← Autres fichiers média
```

## Configuration VichUploader

- **Images de catégories:** `category_images` → `/images/categories/`
- **Icons de catégories:** `category_icons` → `/icons/categories/`
- **Images de produits:** `product_images` → `/images/products/`
- **Autres médias:** `media_object` → `/media/`

## Accès URL

Les fichiers sont accessibles via :
- Images catégories: `https://domain.com/images/categories/{filename}`
- Icons catégories: `https://domain.com/icons/categories/{filename}`

