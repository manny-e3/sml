<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Security;
use App\Models\AuctionResult;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('q');
        
        if (!$query) {
            return view('search.results', [
                'securities' => collect(), 
                'auctions' => collect(), 
                'query' => null
            ]);
        }
        
        if (strlen($query) < 2) {
             return redirect()->back()->with('warning', 'Please enter at least 2 characters to search.');
        }

        $securities = Security::where('security_name', 'like', "%{$query}%")
                        ->orWhere('isin', 'like', "%{$query}%")
                        ->orWhere('issuer', 'like', "%{$query}%")
                        ->latest()
                        ->limit(20)->get();

        $auctions = AuctionResult::where('auction_number', 'like', "%{$query}%")
                        ->orWhereHas('security', function($q) use ($query) {
                            $q->where('security_name', 'like', "%{$query}%")
                              ->orWhere('isin', 'like', "%{$query}%");
                        })
                        ->with('security')
                        ->latest('auction_date')
                        ->limit(20)->get();

        return view('search.results', compact('securities', 'auctions', 'query'));
    }
}
