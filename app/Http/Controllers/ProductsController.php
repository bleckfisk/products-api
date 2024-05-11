<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\External\Product;
use Illuminate\Http\JsonResponse;

class ProductsController extends Controller
{
    /**
     * Index method
     * 
     * Called on GET requests towards '/products' route
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page_size' => 'integer|min:1',
            'page' => 'integer|min:1',
        ]);

        $productModel = new Product($validated);

        $data = $productModel->all();

        if (!empty($data['error'])) {
            return response()->json($data)->setStatusCode($data['code']);
        }

        return response()->json($data);
    }

    /**
     * Get method
     * 
     * Called on GET requests towards '/products/{id}' route
     *
     * @param Request $request
     * @param integer $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request, int $id): JsonResponse
    {
        $productModel = new Product;
        $data = $productModel->find($id);
        $data = !empty($data['products'][0]) ? $data['products'][0] : [];

        return response()->json($data);
    }
}
