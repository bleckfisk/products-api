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
    protected $attributesUrl = 'https://draft.grebban.com/backend/attribute_meta.json';

    /**
     * Separator on nested attributes
     * 
     * Only here for easy access if it should be swapped out to another character
     *
     * @var string
     */
    protected $attributeSeparator = ' > ';

    /**
     * Array of attributes
     *
     * @var array
     */
    protected $data;

    /**
     * Construct for Attribute model
     */
    public function __construct()
    {
        $this->data = $this->all();
    }

    /**
     * Get all attributes
     *
     * @return array
     */
    public function all(): array
    {
        try {
            $productsAttributeResponse = Requests::get($this->attributesUrl);
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
    public function handleAttributeValueCodes(array $valueCodes, string $attributeNameCode): array
    {
        $attributes = [];

        foreach ($valueCodes as $valueCode) {
            $attribute = $this->getAttribute($attributeNameCode, $valueCode);

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
    protected function getAttribute(string $attributeNameCode, string $attributeValueCode): object|false
    {
        foreach ($this->data as $attribute) {
            if ($attribute->code !== $attributeNameCode || empty($attribute->values)) {
                continue;
            }

            $name = $attribute->name;
            $value = $this->getAttributeValue($attributeValueCode, $attribute->values);

            if (!$name || !$value) {
                continue;
            }

            $attributeObject = new stdClass;
            $attributeObject->name = $name;
            $attributeObject->value = $value;

            return $attributeObject;
        }

        return false;
    }


    /**
     * Get attribute value from the list of attributes, found by the value code
     *
     * @param string $attributeValueCode
     * @param array $attributeValues
     * @return string
     */
    protected function getAttributeValue(string $attributeValueCode, array $attributeValues): string
    {
        foreach ($attributeValues as $attribute) {
            if ($attribute->code !== $attributeValueCode) {
                continue;
            }

            $valueCodeParts = explode('_', $attributeValueCode);

            if ($this->attributeIsNested($valueCodeParts)) {
                $attributes[] = $attribute->name;

                array_pop($valueCodeParts);

                $parentAttributeCode = implode('_', $valueCodeParts);

                $attributes[] = $this->getAttributeValue($parentAttributeCode, $attributeValues);

                return implode(
                    $this->attributeSeparator,
                    array_reverse($attributes) // Since we start with the youngest child we have to reverse it to accomodate for parent > child format
                );
            }

            return $attribute->name;
        }

        return '';
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
    protected function attributeIsNested(array $valueCodeParts): bool
    {
        return count($valueCodeParts) > 2;
    }

    /**
     * Flatten out the array to accomodate for specification format
     *
     * @param array $attributes
     * @return array
     */
    public function format(array $attributes): array
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
