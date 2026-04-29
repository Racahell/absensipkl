<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Throwable;

class LocationResolver
{
    /**
     * @return array{label:?string,address:?string}
     */
    public static function reverseGeocode(float $latitude, float $longitude): array
    {
        try {
            $response = Http::timeout(6)
                ->acceptJson()
                ->withHeaders([
                    'User-Agent' => 'AbsensiPKL/1.0 (reverse-geocoder)',
                ])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'jsonv2',
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'zoom' => 18,
                    'addressdetails' => 1,
                ]);

            if (! $response->ok()) {
                return ['label' => null, 'address' => null];
            }

            $json = $response->json();
            if (! is_array($json)) {
                return ['label' => null, 'address' => null];
            }

            $label = trim((string) ($json['name'] ?? ''));
            if ($label === '') {
                $address = $json['address'] ?? [];
                if (is_array($address)) {
                    $label = trim((string) ($address['amenity']
                        ?? $address['shop']
                        ?? $address['building']
                        ?? $address['road']
                        ?? ''));
                }
            }

            $displayAddress = trim((string) ($json['display_name'] ?? ''));

            return [
                'label' => $label !== '' ? $label : null,
                'address' => $displayAddress !== '' ? $displayAddress : null,
            ];
        } catch (Throwable) {
            return ['label' => null, 'address' => null];
        }
    }
}

