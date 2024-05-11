<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductsTest extends TestCase
{
    /**
     * Test for the logic provided in the task specification
     * 
     * Data is different than in the specification, however the logic and structure is the same.
     * 
     * This test proves that when we as for page_size of 2, and page 1, we get 2 items in our products and with appropriate values in the other keys.
     *
     * @return void
     */
    public function test_application_behaves_as_specified(): void
    {
        $response = $this->get('/products?page=1&page_size=2');

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertArrayHasKey('products', $data);
        $this->assertIsArray($data['products']);
        $this->assertEquals(2, count($data['products']));

        foreach ($data['products'] as $product) {
            $this->assertArrayHasKey('id', $product);
            $this->assertIsInt($product['id']);

            $this->assertArrayHasKey('name', $product);
            $this->assertIsString($product['name']);

            $this->assertArrayHasKey('attributes', $product);
            $this->assertIsArray($product['attributes']);

            foreach ($product['attributes'] as $attribute) {
                $this->assertArrayHasKey('name', $attribute);
                $this->assertIsString($attribute['name']);

                $this->assertArrayHasKey('value', $attribute);
                $this->assertIsString($attribute['value']);
            }
        }

        $this->assertArrayHasKey('page', $data);
        $this->assertIsInt($data['page']);
        $this->assertEquals(1, $data['page']);
        $this->assertArrayHasKey('totalPages', $data);
        $this->assertIsInt($data['totalPages']);
    }

    /**
     * Test for default values when no page or page_size is provided
     * 
     * This test proves that even if the parameters are excluded, default values are applied to avoid getting all products and pagination keys are
     * in the response data to show that the user has to paginate to see more
     *
     * @return void
     */
    public function test_application_returns_default_values_on_missing_query_parameters(): void
    {
        $response = $this->get('/products');

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertArrayHasKey('products', $data);
        $this->assertIsArray($data['products']);
        $this->assertEquals(5, count($data['products'])); // 5 is default page size

        foreach ($data['products'] as $product) {
            $this->assertArrayHasKey('id', $product);
            $this->assertIsInt($product['id']);

            $this->assertArrayHasKey('name', $product);
            $this->assertIsString($product['name']);

            $this->assertArrayHasKey('attributes', $product);
            $this->assertIsArray($product['attributes']);

            foreach ($product['attributes'] as $attribute) {
                $this->assertArrayHasKey('name', $attribute);
                $this->assertIsString($attribute['name']);

                $this->assertArrayHasKey('value', $attribute);
                $this->assertIsString($attribute['value']);
            }
        }

        $this->assertArrayHasKey('page', $data);
        $this->assertIsInt($data['page']);
        $this->assertEquals(1, $data['page']);
        $this->assertArrayHasKey('totalPages', $data);
        $this->assertIsInt($data['totalPages']);
    }

    /**
     * Test for application when products/{id} endpoint is used
     * 
     * This test (at least tries to) prove that when providing an ID to the /products/id endpoint, we get that product back with no other data
     * 
     * Please note that the application uses live data which means that if the data is changed on the external server, this test may or may not fail
     * and thus may not be a reliable case in the long run.
     *
     * @return void
     */
    public function test_application_returns_single_product_on_specific_request(): void
    {
        $response = $this->get('/products/2846132');

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertArrayHasKey('id', $data);
        $this->assertEquals(2846132, $data['id']);
        $this->assertArrayHasKey('name', $data);
        $this->assertIsString($data['name']);
        $this->assertArrayHasKey('attributes', $data);
        $this->assertIsArray($data['attributes']);

        foreach ($data['attributes'] as $attribute) {
            $this->assertArrayHasKey('name', $attribute);
            $this->assertIsString($attribute['name']);

            $this->assertArrayHasKey('value', $attribute);
            $this->assertIsString($attribute['value']);
        }

        $this->assertArrayNotHasKey('products', $data);
        $this->assertArrayNotHasKey('page', $data);
        $this->assertArrayNotHasKey('totalPages', $data);
    }
}
