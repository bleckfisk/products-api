<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductsTest extends TestCase
{
    /**
     * Test for the logic provided in the task specification
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
     * Test for page in the middle of the data
     * 
     * This test proves that with page 3 as a query parameter, we get 3 on our returned page value
     *
     * @return void
     */
    public function test_application_handles_other_pages(): void
    {
        $response = $this->get('/products?page=3&page_size=2');

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertArrayHasKey('page', $data);
        $this->assertIsInt($data['page']);
        $this->assertEquals(3, $data['page']);
    }

    /**
     * Test for pagination
     * 
     * This test proves that when we as for page_size of 2, and page 1, we get 2 items in our products and with appropriate values in the other keys.
     *
     * @return void
     */
    public function test_different_pages_has_different_data(): void
    {
        // First Request
        $firstResponse = $this->get('/products?page=1&page_size=3');
        $firstResponse->assertStatus(200);
        $firstData = $firstResponse->json();

        // Assure that the data is correct so we know we can compare it to a second request
        $this->assertArrayHasKey('products', $firstData);
        $this->assertIsArray($firstData['products']);
        $this->assertEquals(3, count($firstData['products']));

        foreach ($firstData['products'] as $product) {
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

        $this->assertArrayHasKey('page', $firstData);
        $this->assertIsInt($firstData['page']);
        $this->assertEquals(1, $firstData['page']);
        $this->assertArrayHasKey('totalPages', $firstData);
        $this->assertIsInt($firstData['totalPages']);

        // Second Request
        $secondResponse = $this->get('/products?page=2&page_size=3'); // this has another page
        $secondResponse->assertStatus(200);
        $secondData = $secondResponse->json();

        // Assure that the data is correct so we can compare it to the first request
        $this->assertArrayHasKey('products', $secondData);
        $this->assertIsArray($secondData['products']);
        $this->assertEquals(3, count($secondData['products']));

        foreach ($secondData['products'] as $product) {
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

        $this->assertArrayHasKey('page', $secondData);
        $this->assertIsInt($secondData['page']);
        $this->assertEquals(2, $secondData['page']);
        $this->assertArrayHasKey('totalPages', $secondData);
        $this->assertIsInt($secondData['totalPages']);

        // Since we paginated - data should differ but not the total pages
        $this->assertNotEquals($firstData['products'], $secondData['products']);
        $this->assertNotEquals($firstData['page'], $secondData['page']);

        $this->assertEquals($firstData['totalPages'], $secondData['totalPages']);
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
