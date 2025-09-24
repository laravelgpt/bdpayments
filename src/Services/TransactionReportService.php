<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Services;

use BDPayments\LaravelPaymentGateway\Models\Payment;
use BDPayments\LaravelPaymentGateway\Models\PaymentRefund;
use BDPayments\LaravelPaymentGateway\Models\PaymentHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TransactionReportService
{
    private array $config;

    public function __construct()
    {
        $this->config = config('payment-gateway.reports', []);
    }

    /**
     * Generate comprehensive transaction report
     */
    public function generateTransactionReport(array $filters = []): array
    {
        $query = $this->buildBaseQuery($filters);
        
        $report = [
            'summary' => $this->getSummaryData($query, $filters),
            'gateway_breakdown' => $this->getGatewayBreakdown($query, $filters),
            'status_breakdown' => $this->getStatusBreakdown($query, $filters),
            'daily_transactions' => $this->getDailyTransactions($query, $filters),
            'monthly_transactions' => $this->getMonthlyTransactions($query, $filters),
            'top_customers' => $this->getTopCustomers($query, $filters),
            'refund_analysis' => $this->getRefundAnalysis($filters),
            'fraud_analysis' => $this->getFraudAnalysis($filters),
            'performance_metrics' => $this->getPerformanceMetrics($query, $filters),
        ];

        return $report;
    }

    /**
     * Generate gateway performance report
     */
    public function generateGatewayPerformanceReport(array $filters = []): array
    {
        $query = $this->buildBaseQuery($filters);
        
        $gateways = $this->getAvailableGateways();
        $report = [];

        foreach ($gateways as $gateway) {
            $gatewayQuery = clone $query;
            $gatewayQuery->where('gateway', $gateway);
            
            $report[$gateway] = [
                'total_transactions' => $gatewayQuery->count(),
                'successful_transactions' => $gatewayQuery->where('status', 'completed')->count(),
                'failed_transactions' => $gatewayQuery->where('status', 'failed')->count(),
                'pending_transactions' => $gatewayQuery->where('status', 'pending')->count(),
                'total_amount' => $gatewayQuery->sum('amount'),
                'success_rate' => $this->calculateSuccessRate($gatewayQuery),
                'average_amount' => $gatewayQuery->avg('amount'),
                'average_processing_time' => $this->calculateAverageProcessingTime($gatewayQuery),
                'refund_rate' => $this->calculateRefundRate($gateway),
            ];
        }

        return $report;
    }

    /**
     * Generate financial report
     */
    public function generateFinancialReport(array $filters = []): array
    {
        $query = $this->buildBaseQuery($filters);
        
        $report = [
            'revenue_summary' => $this->getRevenueSummary($query, $filters),
            'refund_summary' => $this->getRefundSummary($filters),
            'net_revenue' => $this->getNetRevenue($query, $filters),
            'currency_breakdown' => $this->getCurrencyBreakdown($query, $filters),
            'monthly_revenue' => $this->getMonthlyRevenue($query, $filters),
            'daily_revenue' => $this->getDailyRevenue($query, $filters),
            'top_products' => $this->getTopProducts($query, $filters),
            'customer_analysis' => $this->getCustomerAnalysis($query, $filters),
        ];

        return $report;
    }

    /**
     * Generate fraud analysis report
     */
    public function generateFraudAnalysisReport(array $filters = []): array
    {
        $query = $this->buildBaseQuery($filters);
        
        $report = [
            'fraud_indicators' => $this->getFraudIndicators($query, $filters),
            'suspicious_transactions' => $this->getSuspiciousTransactions($query, $filters),
            'risk_analysis' => $this->getRiskAnalysis($query, $filters),
            'blocked_attempts' => $this->getBlockedAttempts($filters),
            'fraud_prevention_metrics' => $this->getFraudPreventionMetrics($filters),
        ];

        return $report;
    }

    /**
     * Generate customer behavior report
     */
    public function generateCustomerBehaviorReport(array $filters = []): array
    {
        $query = $this->buildBaseQuery($filters);
        
        $report = [
            'customer_segments' => $this->getCustomerSegments($query, $filters),
            'payment_preferences' => $this->getPaymentPreferences($query, $filters),
            'customer_lifetime_value' => $this->getCustomerLifetimeValue($query, $filters),
            'retention_analysis' => $this->getRetentionAnalysis($query, $filters),
            'churn_analysis' => $this->getChurnAnalysis($query, $filters),
        ];

        return $report;
    }

    /**
     * Export report to various formats
     */
    public function exportReport(array $report, string $format = 'json', array $options = []): string
    {
        return match ($format) {
            'json' => $this->exportToJson($report, $options),
            'csv' => $this->exportToCsv($report, $options),
            'excel' => $this->exportToExcel($report, $options),
            'pdf' => $this->exportToPdf($report, $options),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
        };
    }

    /**
     * Get real-time dashboard data
     */
    public function getDashboardData(): array
    {
        $now = now();
        $today = $now->copy()->startOfDay();
        $yesterday = $now->copy()->subDay()->startOfDay();
        $thisWeek = $now->copy()->startOfWeek();
        $thisMonth = $now->copy()->startOfMonth();

        return [
            'today' => $this->getPeriodData($today, $now),
            'yesterday' => $this->getPeriodData($yesterday, $today),
            'this_week' => $this->getPeriodData($thisWeek, $now),
            'this_month' => $this->getPeriodData($thisMonth, $now),
            'real_time_stats' => $this->getRealTimeStats(),
            'alerts' => $this->getAlerts(),
        ];
    }

    /**
     * Build base query with filters
     */
    private function buildBaseQuery(array $filters)
    {
        $query = Payment::query();

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to']));
        }

        if (isset($filters['gateway'])) {
            $query->where('gateway', $filters['gateway']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['currency'])) {
            $query->where('currency', $filters['currency']);
        }

        if (isset($filters['amount_min'])) {
            $query->where('amount', '>=', $filters['amount_min']);
        }

        if (isset($filters['amount_max'])) {
            $query->where('amount', '<=', $filters['amount_max']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query;
    }

    /**
     * Get summary data
     */
    private function getSummaryData($query, array $filters): array
    {
        $totalTransactions = $query->count();
        $successfulTransactions = $query->where('status', 'completed')->count();
        $failedTransactions = $query->where('status', 'failed')->count();
        $pendingTransactions = $query->where('status', 'pending')->count();
        $totalAmount = $query->sum('amount');
        $successRate = $totalTransactions > 0 ? ($successfulTransactions / $totalTransactions) * 100 : 0;

        return [
            'total_transactions' => $totalTransactions,
            'successful_transactions' => $successfulTransactions,
            'failed_transactions' => $failedTransactions,
            'pending_transactions' => $pendingTransactions,
            'total_amount' => $totalAmount,
            'success_rate' => round($successRate, 2),
            'average_amount' => $totalTransactions > 0 ? round($totalAmount / $totalTransactions, 2) : 0,
        ];
    }

    /**
     * Get gateway breakdown
     */
    private function getGatewayBreakdown($query, array $filters): array
    {
        return $query->select('gateway', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total_amount'))
            ->groupBy('gateway')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get status breakdown
     */
    private function getStatusBreakdown($query, array $filters): array
    {
        return $query->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total_amount'))
            ->groupBy('status')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get daily transactions
     */
    private function getDailyTransactions($query, array $filters): array
    {
        return $query->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get()
            ->toArray();
    }

    /**
     * Get monthly transactions
     */
    private function getMonthlyTransactions($query, array $filters): array
    {
        return $query->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get()
            ->toArray();
    }

    /**
     * Get top customers
     */
    private function getTopCustomers($query, array $filters): array
    {
        return $query->select('user_id', DB::raw('COUNT(*) as transaction_count'), DB::raw('SUM(amount) as total_amount'))
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderBy('total_amount', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get refund analysis
     */
    private function getRefundAnalysis(array $filters): array
    {
        $refundQuery = PaymentRefund::query();

        if (isset($filters['date_from'])) {
            $refundQuery->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }

        if (isset($filters['date_to'])) {
            $refundQuery->where('created_at', '<=', Carbon::parse($filters['date_to']));
        }

        $totalRefunds = $refundQuery->count();
        $totalRefundAmount = $refundQuery->sum('amount');
        $refundRate = $this->calculateRefundRate();

        return [
            'total_refunds' => $totalRefunds,
            'total_refund_amount' => $totalRefundAmount,
            'refund_rate' => $refundRate,
            'average_refund_amount' => $totalRefunds > 0 ? round($totalRefundAmount / $totalRefunds, 2) : 0,
        ];
    }

    /**
     * Get fraud analysis
     */
    private function getFraudAnalysis(array $filters): array
    {
        $query = $this->buildBaseQuery($filters);
        
        $suspiciousTransactions = $query->where('status', 'failed')
            ->where('amount', '>', 1000)
            ->count();

        $blockedAttempts = $this->getBlockedAttempts($filters);

        return [
            'suspicious_transactions' => $suspiciousTransactions,
            'blocked_attempts' => $blockedAttempts,
            'fraud_rate' => $this->calculateFraudRate($query),
        ];
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics($query, array $filters): array
    {
        $avgProcessingTime = $this->calculateAverageProcessingTime($query);
        $successRate = $this->calculateSuccessRate($query);
        $refundRate = $this->calculateRefundRate();

        return [
            'average_processing_time' => $avgProcessingTime,
            'success_rate' => $successRate,
            'refund_rate' => $refundRate,
            'customer_satisfaction' => $this->calculateCustomerSatisfaction($query),
        ];
    }

    /**
     * Calculate success rate
     */
    private function calculateSuccessRate($query): float
    {
        $total = $query->count();
        $successful = $query->where('status', 'completed')->count();
        
        return $total > 0 ? round(($successful / $total) * 100, 2) : 0;
    }

    /**
     * Calculate refund rate
     */
    private function calculateRefundRate(string $gateway = null): float
    {
        $query = Payment::query();
        
        if ($gateway) {
            $query->where('gateway', $gateway);
        }

        $totalPayments = $query->count();
        $refundedPayments = $query->where('status', 'refunded')->count();
        
        return $totalPayments > 0 ? round(($refundedPayments / $totalPayments) * 100, 2) : 0;
    }

    /**
     * Calculate fraud rate
     */
    private function calculateFraudRate($query): float
    {
        $total = $query->count();
        $fraudulent = $query->where('status', 'failed')
            ->where('amount', '>', 1000)
            ->count();
        
        return $total > 0 ? round(($fraudulent / $total) * 100, 2) : 0;
    }

    /**
     * Calculate average processing time
     */
    private function calculateAverageProcessingTime($query): float
    {
        $completedPayments = $query->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->get();

        if ($completedPayments->isEmpty()) {
            return 0;
        }

        $totalTime = 0;
        foreach ($completedPayments as $payment) {
            $totalTime += $payment->created_at->diffInMinutes($payment->completed_at);
        }

        return round($totalTime / $completedPayments->count(), 2);
    }

    /**
     * Calculate customer satisfaction
     */
    private function calculateCustomerSatisfaction($query): float
    {
        $total = $query->count();
        $satisfied = $query->where('status', 'completed')->count();
        
        return $total > 0 ? round(($satisfied / $total) * 100, 2) : 0;
    }

    /**
     * Get available gateways
     */
    private function getAvailableGateways(): array
    {
        return ['nagad', 'bkash', 'binance', 'paypal', 'rocket', 'upay', 'surecash', 'ucash', 'mcash', 'mycash', 'aamarpay', 'shurjopay', 'sslcommerz'];
    }

    /**
     * Export to JSON
     */
    private function exportToJson(array $report, array $options): string
    {
        return json_encode($report, JSON_PRETTY_PRINT);
    }

    /**
     * Export to CSV
     */
    private function exportToCsv(array $report, array $options): string
    {
        // Implementation for CSV export
        return 'CSV export not implemented yet';
    }

    /**
     * Export to Excel
     */
    private function exportToExcel(array $report, array $options): string
    {
        // Implementation for Excel export
        return 'Excel export not implemented yet';
    }

    /**
     * Export to PDF
     */
    private function exportToPdf(array $report, array $options): string
    {
        // Implementation for PDF export
        return 'PDF export not implemented yet';
    }

    /**
     * Get period data
     */
    private function getPeriodData(Carbon $start, Carbon $end): array
    {
        $query = Payment::whereBetween('created_at', [$start, $end]);
        
        return [
            'transactions' => $query->count(),
            'amount' => $query->sum('amount'),
            'successful' => $query->where('status', 'completed')->count(),
            'failed' => $query->where('status', 'failed')->count(),
        ];
    }

    /**
     * Get real-time stats
     */
    private function getRealTimeStats(): array
    {
        $lastHour = now()->subHour();
        
        return [
            'transactions_last_hour' => Payment::where('created_at', '>=', $lastHour)->count(),
            'amount_last_hour' => Payment::where('created_at', '>=', $lastHour)->sum('amount'),
            'active_gateways' => Payment::where('created_at', '>=', $lastHour)->distinct('gateway')->count(),
        ];
    }

    /**
     * Get alerts
     */
    private function getAlerts(): array
    {
        $alerts = [];
        
        // Check for high failure rate
        $failureRate = $this->calculateFailureRate();
        if ($failureRate > 10) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "High failure rate detected: {$failureRate}%",
                'action' => 'Check gateway configurations',
            ];
        }
        
        // Check for suspicious activity
        $suspiciousCount = Payment::where('created_at', '>=', now()->subHour())
            ->where('amount', '>', 10000)
            ->count();
            
        if ($suspiciousCount > 5) {
            $alerts[] = [
                'type' => 'danger',
                'message' => "Suspicious activity detected: {$suspiciousCount} high-value transactions in the last hour",
                'action' => 'Review transactions for fraud',
            ];
        }
        
        return $alerts;
    }

    /**
     * Calculate failure rate
     */
    private function calculateFailureRate(): float
    {
        $total = Payment::where('created_at', '>=', now()->subDay())->count();
        $failed = Payment::where('created_at', '>=', now()->subDay())->where('status', 'failed')->count();
        
        return $total > 0 ? round(($failed / $total) * 100, 2) : 0;
    }

    // Additional helper methods for specific report sections
    private function getRevenueSummary($query, array $filters): array 
    { 
        $totalRevenue = $query->where('status', 'completed')->sum('amount');
        $refundedAmount = PaymentRefund::whereHas('payment', function($q) use ($filters) {
            $this->applyFilters($q, $filters);
        })->sum('amount');
        
        return [
            'total_revenue' => $totalRevenue,
            'refunded_amount' => $refundedAmount,
            'net_revenue' => $totalRevenue - $refundedAmount,
        ];
    }
    
    private function getRefundSummary(array $filters): array 
    { 
        $refundQuery = PaymentRefund::query();
        $this->applyFilters($refundQuery, $filters);
        
        return [
            'total_refunds' => $refundQuery->count(),
            'total_refund_amount' => $refundQuery->sum('amount'),
            'average_refund_amount' => $refundQuery->avg('amount'),
        ];
    }
    
    private function getNetRevenue($query, array $filters): array 
    { 
        $grossRevenue = $query->where('status', 'completed')->sum('amount');
        $refundedAmount = PaymentRefund::whereHas('payment', function($q) use ($filters) {
            $this->applyFilters($q, $filters);
        })->sum('amount');
        
        return [
            'gross_revenue' => $grossRevenue,
            'refunded_amount' => $refundedAmount,
            'net_revenue' => $grossRevenue - $refundedAmount,
        ];
    }
    
    private function getCurrencyBreakdown($query, array $filters): array 
    { 
        return $query->select('currency', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total_amount'))
            ->groupBy('currency')
            ->orderBy('total_amount', 'desc')
            ->get()
            ->toArray();
    }
    
    private function getMonthlyRevenue($query, array $filters): array 
    { 
        return $query->where('status', 'completed')
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(amount) as revenue')
            )
            ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get()
            ->toArray();
    }
    
    private function getDailyRevenue($query, array $filters): array 
    { 
        return $query->where('status', 'completed')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as revenue')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get()
            ->toArray();
    }
    
    private function getTopProducts($query, array $filters): array 
    { 
        return $query->select('product_name', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total_amount'))
            ->whereNotNull('product_name')
            ->groupBy('product_name')
            ->orderBy('total_amount', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }
    
    private function getCustomerAnalysis($query, array $filters): array 
    { 
        return [
            'total_customers' => $query->distinct('user_id')->count('user_id'),
            'new_customers' => $query->where('created_at', '>=', now()->subMonth())->distinct('user_id')->count('user_id'),
            'repeat_customers' => $query->select('user_id')
                ->groupBy('user_id')
                ->havingRaw('COUNT(*) > 1')
                ->count(),
        ];
    }
    
    private function getFraudIndicators($query, array $filters): array 
    { 
        return [
            'high_value_transactions' => $query->where('amount', '>', 10000)->count(),
            'failed_transactions' => $query->where('status', 'failed')->count(),
            'suspicious_patterns' => $query->where('amount', '>', 5000)
                ->where('status', 'failed')
                ->count(),
        ];
    }
    
    private function getSuspiciousTransactions($query, array $filters): array 
    { 
        return $query->where('amount', '>', 10000)
            ->where('status', 'failed')
            ->orderBy('amount', 'desc')
            ->limit(20)
            ->get()
            ->toArray();
    }
    
    private function getRiskAnalysis($query, array $filters): array 
    { 
        $totalTransactions = $query->count();
        $highRiskTransactions = $query->where('amount', '>', 5000)->where('status', 'failed')->count();
        
        return [
            'total_transactions' => $totalTransactions,
            'high_risk_transactions' => $highRiskTransactions,
            'risk_percentage' => $totalTransactions > 0 ? round(($highRiskTransactions / $totalTransactions) * 100, 2) : 0,
        ];
    }
    
    private function getBlockedAttempts(array $filters): int 
    { 
        return Payment::where('status', 'blocked')
            ->when(isset($filters['date_from']), function($q) use ($filters) {
                $q->where('created_at', '>=', Carbon::parse($filters['date_from']));
            })
            ->when(isset($filters['date_to']), function($q) use ($filters) {
                $q->where('created_at', '<=', Carbon::parse($filters['date_to']));
            })
            ->count();
    }
    
    private function getFraudPreventionMetrics(array $filters): array 
    { 
        return [
            'blocked_attempts' => $this->getBlockedAttempts($filters),
            'fraud_detection_rate' => $this->calculateFraudDetectionRate($filters),
            'prevention_success_rate' => $this->calculatePreventionSuccessRate($filters),
        ];
    }
    
    private function getCustomerSegments($query, array $filters): array 
    { 
        return [
            'high_value_customers' => $query->select('user_id', DB::raw('SUM(amount) as total_spent'))
                ->groupBy('user_id')
                ->having('total_spent', '>', 10000)
                ->count(),
            'frequent_customers' => $query->select('user_id')
                ->groupBy('user_id')
                ->havingRaw('COUNT(*) > 5')
                ->count(),
            'new_customers' => $query->where('created_at', '>=', now()->subMonth())->distinct('user_id')->count('user_id'),
        ];
    }
    
    private function getPaymentPreferences($query, array $filters): array 
    { 
        return $query->select('gateway', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total_amount'))
            ->groupBy('gateway')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }
    
    private function getCustomerLifetimeValue($query, array $filters): array 
    { 
        return $query->select('user_id', DB::raw('SUM(amount) as lifetime_value'))
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderBy('lifetime_value', 'desc')
            ->limit(20)
            ->get()
            ->toArray();
    }
    
    private function getRetentionAnalysis($query, array $filters): array 
    { 
        $totalCustomers = $query->distinct('user_id')->count('user_id');
        $returningCustomers = $query->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();
            
        return [
            'total_customers' => $totalCustomers,
            'returning_customers' => $returningCustomers,
            'retention_rate' => $totalCustomers > 0 ? round(($returningCustomers / $totalCustomers) * 100, 2) : 0,
        ];
    }
    
    private function getChurnAnalysis($query, array $filters): array 
    { 
        $activeCustomers = $query->where('created_at', '>=', now()->subMonth())->distinct('user_id')->count('user_id');
        $totalCustomers = $query->distinct('user_id')->count('user_id');
        
        return [
            'active_customers' => $activeCustomers,
            'total_customers' => $totalCustomers,
            'churn_rate' => $totalCustomers > 0 ? round((($totalCustomers - $activeCustomers) / $totalCustomers) * 100, 2) : 0,
        ];
    }
    
    /**
     * Apply filters to query
     */
    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to']));
        }

        if (isset($filters['gateway'])) {
            $query->where('gateway', $filters['gateway']);
        }
    }
    
    /**
     * Calculate fraud detection rate
     */
    private function calculateFraudDetectionRate(array $filters): float
    {
        $totalTransactions = Payment::when(isset($filters['date_from']), function($q) use ($filters) {
            $q->where('created_at', '>=', Carbon::parse($filters['date_from']));
        })->when(isset($filters['date_to']), function($q) use ($filters) {
            $q->where('created_at', '<=', Carbon::parse($filters['date_to']));
        })->count();
        
        $detectedFraud = Payment::where('status', 'blocked')
            ->when(isset($filters['date_from']), function($q) use ($filters) {
                $q->where('created_at', '>=', Carbon::parse($filters['date_from']));
            })
            ->when(isset($filters['date_to']), function($q) use ($filters) {
                $q->where('created_at', '<=', Carbon::parse($filters['date_to']));
            })
            ->count();
            
        return $totalTransactions > 0 ? round(($detectedFraud / $totalTransactions) * 100, 2) : 0;
    }
    
    /**
     * Calculate prevention success rate
     */
    private function calculatePreventionSuccessRate(array $filters): float
    {
        $blockedAttempts = $this->getBlockedAttempts($filters);
        $totalAttempts = Payment::when(isset($filters['date_from']), function($q) use ($filters) {
            $q->where('created_at', '>=', Carbon::parse($filters['date_from']));
        })->when(isset($filters['date_to']), function($q) use ($filters) {
            $q->where('created_at', '<=', Carbon::parse($filters['date_to']));
        })->count();
        
        return $totalAttempts > 0 ? round(($blockedAttempts / $totalAttempts) * 100, 2) : 0;
    }
}
