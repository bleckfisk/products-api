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
}
