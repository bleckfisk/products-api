<?php

namespace App\Models\External;

use Illuminate\Support\Facades\Log;
use App\Models\External\Attribute;
use WpOrg\Requests\Requests;

class Product
{

    /**
     * URL to the external products
     *
     * @var string
     */
    protected static $productsUrl = 'https://draft.grebban.com/backend/products.json';

    /**
     * URL to the external products metadata
     *
     * @var string
     */
    protected static $attributesUrl = 'https://draft.grebban.com/backend/attribute_meta.json';

    /**
     * Combined data from products and attributes, resulting in the finalized data
     *
     * @var array
     */
    protected static $productsData = [];

    /**
     * Separator on nested attributes
     * 
     * Only here for easy access if it should be swapped out to another character
     *
     * @var string
     */
    protected static $attributeSeparator = ' > ';

    /**
     * Get all products
     *
     * @param array $args
     * @return array
     */
    public static function all(array $args)
    {
        $args = [ // In case someone calls this method from somewhere unexpected
            'page_size' => intval($args['page_size']) ?: 5,
            'page' => intval($args['page']) ?: 1
        ];

        $success = self::setProductsData($args['page_size'], $args['page']);

        if (!$success) {
            return [
                'error' => 'Error when getting products data',
                'code' => 500
            ];
        }

        return self::$productsData;
    }

    /**
     * Find a specific product by id
     *
     * @param int $id
     * @return array
     */
    public static function find(int $id)
    {
        $success = self::setProductsData(1, 1, $id);

        if (!$success) {
            return [
                'error' => 'Error when getting products data',
                'code' => 500
            ];
        }

        return self::$productsData;
    }

    /**
     * Set products data from the productsUrl and attributesUrl
     *
     * @param int $page_size How many products each page should contain
     * @param int $page What specific page we are trying to get
     * @param int $id A specific id we are trying to get
     * @return bool Whether or not the process of setting the data was successful
     */
    protected static function setProductsData(int $page_size = 5, int $page = 1, int $id = 0)
    {
        try {
            $productsUrlResponse = Requests::get(self::$productsUrl);
        } catch (\Exception $e) {
            Log::warning($e->getMessage());

            return false;
        }

        if ($productsUrlResponse->status_code !== 200) {
            return false;
        }

        $products = json_decode($productsUrlResponse->body);

        if (!$products) {
            return false;
        }

        if ($id) {
            $products = self::getSingleProduct($products, $id);
        } else {
            $pages = array_chunk($products, $page_size);
            $products = $pages[self::getPageIndex($page, $pages)];
        }

        self::$productsData = self::buildProductsData($products);

        return true;
    }

    /**
     * Get single product from product list
     *
     * @param array $products
     * @param integer $id
     * @return array
     */
    protected static function getSingleProduct(array $products, int $id)
    {
        foreach ($products as $product) {
            if (empty($product->id) || $product->id !== $id) {
                continue;
            }

            return [$product];
        }

        return [];
    }

    /**
     * Get index of a page in the pages array
     *
     * @param integer $page
     * @param array $pages
     * @return int
     */
    protected static function getPageIndex(int $page, array $pages)
    {
        if ($page <= 1) {
            return 0;
        }

        $max_page = count($pages);

        if ($page >= $max_page) {
            return $max_page - 1;
        }

        return !empty($pages[$page - 1]) ? $page - 1 : 0; // Just in case, if for some reason the index does not exists in the array
    }

    /**
     * Builds the complete products data array from provided products and attributes
     *
     * @param array $products
     * @return array
     */
    protected static function buildProductsData(array &$products)
    {
        foreach ($products as $product) {
            if (empty($product->attributes) || !is_object($product->attributes)) {
                continue;
            }

            $product->attributes = self::handleProductAttributes($product->attributes);
        }

        return $products;
    }

    /**
     * Loops over a products attributes and initializes handling of them
     *
     * @param object $productAttributes
     * @return array
     */
    protected static function handleProductAttributes(object $productAttributes)
    {
        $attributes = [];

        foreach ($productAttributes as $attribute_name_code => $attribute_value_code) {
            $value_codes = explode(',', $attribute_value_code);
            $attributes[] = Attribute::handleAttributeValueCodes($value_codes, $attribute_name_code);
        }

        return Attribute::format($attributes);
    }
}
