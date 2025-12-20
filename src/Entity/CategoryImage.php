<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

/**
 * MediaObject for Category Images
 * Files are stored in public/data/images/categories/
 */
#[Vich\Uploadable]
class CategoryImage extends MediaObject
{
    #[Vich\UploadableField(mapping: 'category_images', fileNameProperty: 'filePath')]
    public ?\Symfony\Component\HttpFoundation\File\File $file = null;

    public function __construct()
    {
        parent::__construct();
        $this->setMapping('category_images');
    }
}

