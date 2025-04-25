<?php

namespace crawler\models\parser;

/**
 * ParserSettlingInterface defines methods processing parsed data and saving (settling) it properly to the db.
 */
interface ParserSettlingInterface
{
    /**
     * Checks existence & saves categories to `category` and `category_source` according to the tree structure.
     * @param array $parsedCategories structured array if categories and their data.
     * @param integer $sourceId ID of relevant source.
     * @return array
     */
	public function saveCategories($parsedCategories);

    /**
     * Checks existence & saves descriptions to `product_description`.
     * @param array $descriptionData structured array if descriptions and their titles.
     * @param integer $productId ID of relevant product item.
     */
	public function saveDescriptions(array $descriptionData, int $productId);

    /**
     * Checks existence & saves attributes and ther values to `attribute`, `attribute_value` and `product_attribute`.
     * @param array $attributeData structured array if attributes and their values.
     * @param integer $productId ID of relevant product item.
     */
	public function saveAttributes(array $attributeData, int $productId);

    /**
     * Checks existence & saves images to `image` and `product_image`.
     * @param array $imageData structured array if images and their thumbs.
     * @param integer $productId ID of relevant product item.
     */
	public function saveImages(array $imageData, int $productId);

}