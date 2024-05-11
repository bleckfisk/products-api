<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\External\Product;

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
    public function index(Request $request)
    {
        $args = [
            'page_size' => intval($request->query('page_size')) ?: 5,
            'page' => intval($request->query('page')) ?: 1
        ];

        $data = Product::all($args);

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
    public function get(Request $request, int $id)
    {
        $data = Product::find($id);

        $data = !empty($data['products'][0]) ? $data['products'][0] : [];

        return response()->json($data);
    }
}
