<?php

namespace App\Dto;


use App\Entity\Category;
use App\Entity\Product;

class ItemCategorizer
{
    private Category $category;

    /**
     * @var Product[]
     */
    private array $products;

    public function __construct(Category $category, array $products)
    {
        $this->category = $category;
        $this->products = $products;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    /**
     * @return Product[]
     */
    public function getProducts(): array
    {
        return $this->products;
    }
}
