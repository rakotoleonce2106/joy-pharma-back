<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

/**
 * MediaObject for Category Icons/SVG
 * Files are stored in public/data/icons/categories/
 */
#[Vich\Uploadable]
class CategoryIcon extends MediaObject
{
    #[Vich\UploadableField(mapping: 'category_icons', fileNameProperty: 'filePath')]
    public ?\Symfony\Component\HttpFoundation\File\File $file = null;

    public function __construct()
    {
        parent::__construct();
        $this->setMapping('category_icons');
    }
}

