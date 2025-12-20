# Répertoires de stockage des fichiers

## Structure

```
public/
├── data/
│   ├── images/
│   │   └── categories/    ← Images des catégories
│   └── icons/
│       └── categories/    ← Icons/SVG des catégories
├── images/
│   └── products/          ← Images des produits
└── media/                 ← Autres fichiers média
```

## Configuration VichUploader

- **Images de catégories:** `category_images` → `/data/images/categories/`
- **Icons de catégories:** `category_icons` → `/data/icons/categories/`
- **Images de produits:** `product_images` → `/images/products/`
- **Autres médias:** `media_object` → `/media/`

## Accès URL

Les fichiers sont accessibles via :
- Images catégories: `https://domain.com/data/images/categories/{filename}`
- Icons catégories: `https://domain.com/data/icons/categories/{filename}`

