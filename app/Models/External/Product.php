<?php

namespace App\Models\External;

use Illuminate\Support\Facades\Log;
use WpOrg\Requests\Requests;
use stdClass;

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
     * @return array
     */
    public static function all()
    {
        $success = self::setProductsData();

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
        $success = self::setProductsData();

        if (!$success) {
            return [
                'error' => 'Error when getting products data',
                'code' => 500
            ];
        }

        foreach (self::$productsData as $product) {
            if (empty($product['id']) || $product['id'] !== $id) {
                continue;
            }

            return $product;
        }
    }

    /**
     * Set products data from the productsUrl and attributesUrl
     *
     * @return bool Whether or not the process of setting the data was successful
     */
    protected static function setProductsData()
    {
        try {
            $productsUrlResponse = Requests::get(self::$productsUrl);
            $productsAttributeResponse = Requests::get(self::$attributesUrl);
        } catch (\Exception $e) {
            Log::warning($e->getMessage());

            return false;
        }

        if ($productsUrlResponse->status_code !== 200 || $productsAttributeResponse->status_code !== 200) {
            return false;
        }

        $products = json_decode($productsUrlResponse->body);
        $attributes = json_decode($productsAttributeResponse->body);

        self::$productsData = self::buildProductsData($products, $attributes);

        return true;
    }

    /**
     * Builds the complete products data array from provided products and attributes
     *
     * @param array $products
     * @param array $attributes
     * @return array
     */
    protected static function buildProductsData(array &$products, array $attributes)
    {
        foreach ($products as $product) {
            $product_attributes = [];

            if (empty($product->attributes) || !is_object($product->attributes)) {
                continue;
            }

            foreach ($product->attributes as $attribute_name_code => $attribute_value_code) {
                $value_codes = explode(',', $attribute_value_code);
                foreach ($value_codes as $value_code) {
                    $attribute = self::getAttribute($attribute_name_code, $value_code, $attributes);

                    if ($attribute) {
                        $product_attributes[] = $attribute;
                    }
                }
            }

            $product->attributes = $product_attributes;
        }

        return $products;
    }

    /**
     * Get attribute for product
     *
     * @param string $attribute_name_code
     * @param string $attribute_value_code
     * @param array $attributes
     * @return object
     */
    protected static function getAttribute(string $attribute_name_code, string $attribute_value_code, array $attributes)
    {
        foreach ($attributes as $attribute) {
            if ($attribute->code !== $attribute_name_code || empty($attribute->values)) {
                continue;
            }

            $name = $attribute->name;
            $value = self::getAttributeValue($attribute_value_code, $attribute->values);

            if (!$name || !$value) {
                continue;
            }

            $attribute_object = new stdClass;
            $attribute_object->name = $name;
            $attribute_object->value = $value;

            return $attribute_object;
        }
    }

    /**
     * Get attribute value from the list of attributes, found by the value code
     *
     * @param string $attribute_value_code
     * @param array $attribute_values
     * @return string|void
     */
    protected static function getAttributeValue(string $attribute_value_code, array $attribute_values)
    {
        foreach ($attribute_values as $attribute) {
            if ($attribute->code !== $attribute_value_code) {
                continue;
            }

            $value_code_parts = explode('_', $attribute_value_code);

            if (self::attributeIsNested($value_code_parts)) {
                $attributes[] = $attribute->name;

                array_pop($value_code_parts);

                $parent_attribute_code = implode('_', $value_code_parts);

                $attributes[] = self::getAttributeValue($parent_attribute_code, $attribute_values);

                return implode(
                    self::$attributeSeparator,
                    array_reverse($attributes) // Since we start with the youngest child we have to reverse it to accomodate for parent > child format
                );
            }

            return $attribute->name;
        }
    }

    /**
     * Attribute value is nested, i.e has a parent attribute to take into account
     * 
     * This method assumes nested attributes have the format of {attribute_code}_{parent}_{child},
     * but also supportes children being parents as well
     *
     * @param array $value_code_parts
     * @return bool
     */
    protected static function attributeIsNested(array $value_code_parts)
    {
        return count($value_code_parts) > 2;
    }
}
