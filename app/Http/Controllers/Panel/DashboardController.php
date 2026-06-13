<?php
namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $company = Company::getDefault();
        $currency = $company?->currency_symbol ?? '₹';
        $totalCustomers = Party::where('type', 'customer')->where('is_active', true)->count();
        $totalVendors = Party::where('type', 'vendor')->where('is_active', true)->count();
        $totalProducts = Product::where('is_active', true)->count();
        $totalAccounts = Account::where('is_active', true)->count();
        $totalUsers = User::where('is_active', true)->count();
        $newCustomers = Party::where('type', 'customer')->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $newVendors = Party::where('type', 'vendor')->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $newProducts = Product::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $lowStock = Product::where('track_inventory', true)->where('is_active', true)->whereColumn('opening_stock', '<=', 'reorder_level')->where('reorder_level', '>', 0)->count();
        $lowStockProducts = Product::with('unit')->where('track_inventory', true)->where('is_active', true)->whereColumn('opening_stock', '<=', 'reorder_level')->where('reorder_level', '>', 0)->orderBy('opening_stock')->limit(5)->get();
        $goodsCount = Product::where('type', 'goods')->where('is_active', true)->count();
        $serviceCount = Product::where('type', 'service')->where('is_active', true)->count();
        $recentCustomers = Party::where('type', 'customer')->where('is_active', true)->latest()->limit(5)->get();
        $recentVendors = Party::where('type', 'vendor')->where('is_active', true)->latest()->limit(5)->get();
        $recentProducts = Product::with('unit')->where('is_active', true)->latest()->limit(5)->get();
        $debitAccounts = Account::where('balance_type', 'debit')->where('is_active', true)->count();
        $creditAccounts = Account::where('balance_type', 'credit')->where('is_active', true)->count();
        $monthlyGrowth = collect(range(5, 0))->map(function ($i) {
            $date = now()->subMonths($i);
            return [
                'month' => $date->format('M'),
                'customers' => Party::where('type', 'customer')->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count(),
                'vendors' => Party::where('type', 'vendor')->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count(),
            ];
        });
        $customerGrowth = $monthlyGrowth->map(fn($m) => ['month' => $m['month'], 'count' => $m['customers']]);
        $totalAccountGroups = AccountGroup::where('is_active', true)->count();
        $currentFY = $company ? FinancialYear::where('company_id', $company->id)->where('is_current', true)->first() : null;
        $fyProgress = null;
        if ($currentFY) {
            $total = $currentFY->start_date->diffInDays($currentFY->end_date) ?: 1;
            $elapsed = $currentFY->start_date->diffInDays(now()->min($currentFY->end_date));
            $fyProgress = round(($elapsed / $total) * 100);
        }
        return view('dashboard', compact('company', 'currency', 'totalCustomers', 'totalVendors', 'totalProducts', 'totalAccounts', 'totalUsers', 'newCustomers', 'newVendors', 'newProducts', 'lowStock', 'goodsCount', 'serviceCount', 'recentCustomers', 'recentVendors', 'recentProducts', 'debitAccounts', 'creditAccounts', 'monthlyGrowth', 'customerGrowth', 'lowStockProducts', 'totalAccountGroups', 'currentFY', 'fyProgress'));
    }
}
