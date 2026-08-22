<?php

namespace App\Http\Repositories;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class SearchRepository
{
    /**
     * Alternative implementation using JSON_CONTAINS for better performance
     * This requires MySQL 5.7+ or MariaDB 10.2+
     */
    public function search($keyword)
    {
        $results = Product::has('variants')
            ->where(function($query) use ($keyword) {
            $keyword = strtolower($keyword); // normalize input
            $query->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.en"))) LIKE ?', ['%' . $keyword . '%'])
                  ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.ar"))) LIKE ?', ['%' . $keyword . '%']);
        })
        // Add relevance-based ordering
        ->orderByRaw('
            CASE 
                WHEN LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.en"))) LIKE ? THEN 1
                WHEN LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.en"))) LIKE ? THEN 2
                ELSE 3
            END', [$keyword . '%', '%' . $keyword . '%'])
        ->take(7)
        ->get();
        
        
        
        return $results;
    }

    public function searchKeyword($keyword)
    {
        $keyword = strtolower($keyword); // normalize input
    
        $results = Product::has('variants')
            ->where(function ($query) use ($keyword) {
            $query->whereRaw(
                    'LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.en"))) LIKE ?', 
                    [$keyword . '%']
                )
                ->orWhereRaw(
                    'LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.ar"))) LIKE ?', 
                    [$keyword . '%']
                );
        })
        ->with('industrialCategory')
        ->orderByRaw('
            CASE 
                WHEN LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.en"))) LIKE ? THEN 1
                WHEN LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.ar"))) LIKE ? THEN 2
                ELSE 3
            END', [$keyword . '%', $keyword . '%'])
        ->paginate(16);
    
        return $results;
    }
    
}