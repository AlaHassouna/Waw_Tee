<?php

namespace App\Http\Controllers;

use App\Models\ShippingRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShippingController extends Controller
{
    public function index(Request $request)
    {
        try {
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 20);
            $active = $request->get('active');

            $query = ShippingRate::query();

            if ($active === 'true') {
                $query->where('is_active', true);
            }

            $rates = $query->orderBy('created_at', 'desc')
                          ->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'rates' => $rates->items(),
                    'pagination' => [
                        'page' => $rates->currentPage(),
                        'limit' => $rates->perPage(),
                        'total' => $rates->total(),
                        'pages' => $rates->lastPage(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while fetching shipping rates',
            ], 500);
        }
    }

    public function getRates()
    {
        try {
            $rates = ShippingRate::where('is_active', true)->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'rates' => $rates,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while fetching shipping rates',
            ], 500);
        }
    }

    public function calculateShipping(Request $request)
{
    $validator = Validator::make($request->all(), [
        'country' => 'required|string',
        'city' => 'sometimes|string',
        'orderTotal' => 'required|numeric|min:0',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => $validator->errors()->first(),
        ], 400);
    }

    try {
        $country = $request->country;
        $city = $request->city;
        $orderTotal = $request->orderTotal;

        \Log::info('Calculating shipping for:', [
            'country' => $country,
            'city' => $city,
            'orderTotal' => $orderTotal
        ]);

        // Find shipping rate for the country
        $shippingRates = ShippingRate::where('is_active', true)->get();
        
        $shippingRate = null;
        foreach ($shippingRates as $rate) {
            $countryData = is_string($rate->country) ? json_decode($rate->country, true) : $rate->country;
            
            if (isset($countryData['code']) && $countryData['code'] === $country) {
                $shippingRate = $rate;
                break;
            }
        }

        if (!$shippingRate) {
            \Log::warning('No shipping rate found for country: ' . $country);
            return response()->json([
                'status' => 'error',
                'message' => 'Shipping not available for this country',
            ], 404);
        }

        $shippingInfo = $shippingRate->calculateShipping($city, $orderTotal);

        if (!$shippingInfo) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to calculate shipping',
            ], 400);
        }

        \Log::info('Shipping calculated successfully:', $shippingInfo);

        return response()->json([
            'status' => 'success',
            'data' => [
                'shipping' => $shippingInfo,
                'rate' => $shippingRate,
            ],
        ]);
    } catch (\Exception $e) {
        \Log::error('Shipping calculation error: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => 'Server error while calculating shipping: ' . $e->getMessage(),
        ], 500);
    }
}

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country' => 'required|array',
            'default_rate' => 'required|numeric|min:0',
            'free_shipping_threshold' => 'sometimes|numeric|min:0',
            'estimated_days' => 'required|array',
            'cities' => 'sometimes|array',
            'is_active' => 'sometimes|boolean',
            'currency' => 'sometimes|string|size:3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            $rate = ShippingRate::create($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Shipping rate created successfully',
                'data' => [
                    'rate' => $rate,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while creating shipping rate',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $rate = ShippingRate::find($id);

            if (!$rate) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Shipping rate not found',
                ], 404);
            }

            $rate->update($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Shipping rate updated successfully',
                'data' => [
                    'rate' => $rate,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while updating shipping rate',
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $rate = ShippingRate::find($id);

            if (!$rate) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Shipping rate not found',
                ], 404);
            }

            $rate->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Shipping rate deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while deleting shipping rate',
            ], 500);
        }
    }
}
