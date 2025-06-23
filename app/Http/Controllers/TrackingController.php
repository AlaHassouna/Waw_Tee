<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Order;

class TrackingController extends Controller
{
    public function track($trackingNumber)
    {
        try {
            // First check if it's one of our orders
            $order = Order::where('tracking_number', $trackingNumber)->first();
            
            if ($order) {
                $trackingInfo = [
                    'trackingNumber' => $trackingNumber,
                    'status' => $order->status,
                    'estimatedDelivery' => $order->estimated_delivery,
                    'deliveredAt' => $order->delivered_at,
                    'order' => [
                        'orderNumber' => $order->order_number,
                        'total' => $order->total,
                        'shippingAddress' => $order->shipping_address,
                    ],
                    'events' => json_decode($order->status_history, true) ?? [],
                ];

                // Try to get additional tracking info from external APIs
                $externalTracking = $this->getExternalTrackingInfo($trackingNumber);
                if ($externalTracking) {
                    $trackingInfo['externalTracking'] = $externalTracking;
                }

                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'tracking' => $trackingInfo,
                    ],
                ]);
            }

            // If not our order, try external tracking only
            $externalTracking = $this->getExternalTrackingInfo($trackingNumber);
            
            if ($externalTracking) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'tracking' => $externalTracking,
                    ],
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Tracking number not found',
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while tracking package',
            ], 500);
        }
    }

    private function getExternalTrackingInfo($trackingNumber)
    {
        try {
            // Try Track17 API
            $track17Response = $this->getTrack17Info($trackingNumber);
            if ($track17Response) {
                return $track17Response;
            }

            // Try TrackingMore API
            $trackingMoreResponse = $this->getTrackingMoreInfo($trackingNumber);
            if ($trackingMoreResponse) {
                return $trackingMoreResponse;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getTrack17Info($trackingNumber)
    {
        try {
            $apiKey = config('services.track17.api_key');
            if (!$apiKey) {
                return null;
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post('https://api.17track.net/track/v2.2/gettrackinfo', [
                'number' => $trackingNumber,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['code'] === 0 && !empty($data['data'])) {
                    $trackInfo = $data['data'][0];
                    
                    return [
                        'trackingNumber' => $trackingNumber,
                        'carrier' => $trackInfo['carrier'] ?? 'Unknown',
                        'status' => $this->mapTrack17Status($trackInfo['status'] ?? 0),
                        'events' => $this->formatTrack17Events($trackInfo['track'] ?? []),
                        'source' => 'track17',
                    ];
                }
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getTrackingMoreInfo($trackingNumber)
    {
        try {
            $apiKey = config('services.trackingmore.api_key');
            if (!$apiKey) {
                return null;
            }

            $response = Http::withHeaders([
                'Tracking-Api-Key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->get("https://api.trackingmore.com/v3/trackings/{$trackingNumber}");

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['code'] === 200 && !empty($data['data'])) {
                    $trackInfo = $data['data'];
                    
                    return [
                        'trackingNumber' => $trackingNumber,
                        'carrier' => $trackInfo['carrier_code'] ?? 'Unknown',
                        'status' => $trackInfo['status'] ?? 'unknown',
                        'events' => $this->formatTrackingMoreEvents($trackInfo['origin_info']['trackinfo'] ?? []),
                        'source' => 'trackingmore',
                    ];
                }
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function mapTrack17Status($status)
    {
        $statusMap = [
            0 => 'unknown',
            10 => 'in_transit',
            20 => 'expired',
            30 => 'pickup',
            35 => 'undelivered',
            40 => 'delivered',
            50 => 'alert',
        ];

        return $statusMap[$status] ?? 'unknown';
    }

    private function formatTrack17Events($events)
    {
        return array_map(function ($event) {
            return [
                'date' => $event['date'] ?? null,
                'status' => $event['status'] ?? '',
                'location' => $event['location'] ?? '',
                'details' => $event['details'] ?? '',
            ];
        }, $events);
    }

    private function formatTrackingMoreEvents($events)
    {
        return array_map(function ($event) {
            return [
                'date' => $event['Date'] ?? null,
                'status' => $event['StatusDescription'] ?? '',
                'location' => $event['Details'] ?? '',
                'details' => $event['Details'] ?? '',
            ];
        }, $events);
    }
}
