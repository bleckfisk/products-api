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
    protected $productsUrl = 'https://draft.grebban.com/backend/products.json';

    /**
     * URL to the external products metadata
     *
     * @var string
     */
    protected $attributesUrl = 'https://draft.grebban.com/backend/attribute_meta.json';

    /**
     * Attribute Model
     *
     * @var Attribute
     */
    protected $attributeModel;

    /**
     * Combined data from products and attributes, resulting in the finalized data
     *
     * @var array
     */
    protected $data = [];

    /**
     * Page size
     *
     * @var int
     */
    protected $pageSize = 5; // Default value

    /**
     * Page number
     *
     * @var int
     */
    protected $page = 1; // Default value

    /**
     * Constructor for product model
     *
     * @param array $args
     * @return void
     */
    public function __construct(array $args = [])
    {
        $this->pageSize = !empty($args['page_size']) ? intval($args['page_size']) : $this->pageSize;
        $this->page = !empty($args['page']) ? intval($args['page']) : $this->page;
        $this->attributeModel = new Attribute;
    }

    /**
     * Get all products
     *
     * @return array
     */
    public function all(): array
    {
        $success = $this->setData($this->pageSize, $this->page);

        if (!$success) {
            return [
                'error' => 'Error when getting products data',
                'code' => 500
            ];
        }

        return $this->data;
    }

    /**
     * Find a specific product by id
     *
     * @param int $id
     * @return array
     */
    public function find(int $id): array
    {
        $success = $this->setData(1, 1, $id); // pagesize: 1, page: 1 because 1 product can not be elsewhere

        if (!$success) {
            return [
                'error' => 'Error when getting products data',
                'code' => 500
            ];
        }

        return $this->data;
    }

    /**
     * Set products data from the productsUrl and attributesUrl
     *
     * @param int $pageSize How many products each page should contain
     * @param int $page What specific page we are trying to get
     * @param int $id A specific id we are trying to get
     * @return bool Whether or not the process of setting the data was successful
     */
    protected function setData(int $pageSize, int $page, int $id = 0): bool
    {
        try {
            $productsUrlResponse = Requests::get($this->productsUrl);
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
            $products = $this->getSingleProduct($products, $id);
        } else {
            $pages = array_chunk($products, $pageSize);
            $maxPage = count($pages);
            $page = $this->forceValidPage($page, $maxPage);
            $products = $pages[$this->getPageIndex($page, $maxPage, $pages)];
        }

        $this->data['products'] = $this->buildProductsData($products);
        $this->data['page'] = $page;
        $this->data['totalPages'] = $id ? 1 : $maxPage;

        return true;
    }

    /**
     * Get single product from product list
     *
     * @param array $products
     * @param integer $id
     * @return array
     */
    protected function getSingleProduct(array $products, int $id): array
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
     * @param int $page
     * @param int $maxPage
     * @param array $pages
     * @return int
     */
    protected function getPageIndex(int $page, int $maxPage, array $pages): int
    {
        if ($page <= 1) {
            return 0;
        }

        if ($page >= $maxPage) {
            return $maxPage - 1;
        }

        return !empty($pages[$page - 1]) ? $page - 1 : 0; // Just in case, if for some reason the index does not exists in the array
    }

    /**
     * Builds the complete products data array from provided products and attributes
     *
     * @param array $products
     * @return array
     */
    protected function buildProductsData(array &$products): array
    {
        foreach ($products as $product) {
            if (empty($product->attributes) || !is_object($product->attributes)) {
                continue;
            }

            $product->attributes = $this->handleProductAttributes($product->attributes);
        }

        return $products;
    }

    /**
     * Loops over a products attributes and initializes handling of them
     *
     * @param object $productAttributes
     * @return array
     */
    protected function handleProductAttributes(object $productAttributes): array
    {
        $attributes = [];

        foreach ($productAttributes as $attribute_name_code => $attribute_value_code) {
            $value_codes = explode(',', $attribute_value_code);
            $attributes[] = $this->attributeModel->handleAttributeValueCodes($value_codes, $attribute_name_code);
        }

        return $this->attributeModel->format($attributes);
    }

    /**
     * Force valid page 
     *
     * @param int $page
     * @param int $maxPage
     * @return int
     */
    protected function forceValidPage(int $page, int $maxPage): int
    {
        if ($page < 1) {
            return 1;
        }

        if ($page > $maxPage) {
            return $maxPage;
        }

        return $page;
    }
}
