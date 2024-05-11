<?php

namespace App\Models\External;

use Illuminate\Support\Facades\Log;
use WpOrg\Requests\Requests;
use stdClass;

class Attribute
{
    /**
     * URL to the external products metadata
     *
     * @var string
     */
    protected static $attributesUrl = 'https://draft.grebban.com/backend/attribute_meta.json';

    /**
     * Separator on nested attributes
     * 
     * Only here for easy access if it should be swapped out to another character
     *
     * @var string
     */
    protected static $attributeSeparator = ' > ';


    /**
     * Get all attributes
     *
     * @return array
     */
    public static function all()
    {

        try {
            $productsAttributeResponse = Requests::get(self::$attributesUrl);
        } catch (\Exception $e) {
            Log::warning($e->getMessage());

            return [];
        }

        if ($productsAttributeResponse->status_code !== 200) {
            return [];
        }

        $attributes = json_decode($productsAttributeResponse->body);

        if (!$attributes) {
            return [];
        }

        return $attributes;
    }

    /**
     * Gets the attribute objects as an array
     *
     * @param array $valueCodes
     * @param string $attributeNameCode
     * @return array
     */
    public static function handleAttributeValueCodes(array $valueCodes, string $attributeNameCode)
    {
        $attributes = [];

        foreach ($valueCodes as $valueCode) {
            $attribute = self::getAttribute($attributeNameCode, $valueCode);

            if ($attribute) {
                $attributes[] = $attribute;
            }
        }

        return $attributes;
    }

    /**
     * Get attribute for product
     *
     * @param string $attributeNameCode
     * @param string $attributeValueCode
     * @param array $attributes
     * @return object
     */
    protected static function getAttribute(string $attributeNameCode, string $attributeValueCode)
    {
        foreach (self::all() as $attribute) {
            if ($attribute->code !== $attributeNameCode || empty($attribute->values)) {
                continue;
            }

            $name = $attribute->name;
            $value = self::getAttributeValue($attributeValueCode, $attribute->values);

            if (!$name || !$value) {
                continue;
            }

            $attributeObject = new stdClass;
            $attributeObject->name = $name;
            $attributeObject->value = $value;

            return $attributeObject;
        }
    }


    /**
     * Get attribute value from the list of attributes, found by the value code
     *
     * @param string $attributeValueCode
     * @param array $attributeValues
     * @return string|void
     */
    protected static function getAttributeValue(string $attributeValueCode, array $attributeValues)
    {
        foreach ($attributeValues as $attribute) {
            if ($attribute->code !== $attributeValueCode) {
                continue;
            }

            $valueCodeParts = explode('_', $attributeValueCode);

            if (self::attributeIsNested($valueCodeParts)) {
                $attributes[] = $attribute->name;

                array_pop($valueCodeParts);

                $parentAttributeCode = implode('_', $valueCodeParts);

                $attributes[] = self::getAttributeValue($parentAttributeCode, $attributeValues);

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
     * This method assumes nested attributes have the format of {attributeCode}_{parent}_{child},
     * but also supportes children being parents as well
     *
     * @param array $valueCodeParts
     * @return bool
     */
    protected static function attributeIsNested(array $valueCodeParts)
    {
        return count($valueCodeParts) > 2;
    }

    /**
     * Flatten out the array to accomodate for specification format
     *
     * @param array $attributes
     * @return array
     */
    public static function format(array $attributes)
    {
        $formatted = [];

        foreach (array_keys($attributes) as $index) {
            foreach ($attributes[$index] as $attribute) {
                $formatted[] = $attribute;
            }
        }

        return $formatted;
    }
}
