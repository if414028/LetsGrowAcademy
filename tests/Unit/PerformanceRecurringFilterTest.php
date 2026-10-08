<?php

namespace Tests\Unit;

use App\Http\Controllers\PerformanceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class PerformanceRecurringFilterTest extends TestCase
{
    public function test_it_accepts_only_supported_recurring_status_values(): void
    {
        $controller = app(PerformanceController::class);
        $method = new ReflectionMethod($controller, 'selectedRecurringStatus');

        $this->assertSame('not_recurring', $method->invoke($controller, Request::create('/', 'GET', [
            'recurring_status' => 'not_recurring',
        ])));
        $this->assertSame('recurring', $method->invoke($controller, Request::create('/', 'GET', [
            'recurring_status' => 'recurring',
        ])));
        $this->assertNull($method->invoke($controller, Request::create('/', 'GET', [
            'recurring_status' => 'unsupported',
        ])));
    }

    public function test_it_builds_the_expected_recurring_filters(): void
    {
        $controller = app(PerformanceController::class);
        $method = new ReflectionMethod($controller, 'applyRecurringStatusFilter');

        $notRecurringQuery = DB::table('sales_orders as so');
        $method->invoke($controller, $notRecurringQuery, 'not_recurring', 'so');

        $recurringQuery = DB::table('sales_orders as so');
        $method->invoke($controller, $recurringQuery, 'recurring', 'so');

        $this->assertStringContainsString('COALESCE(so.is_recurring, 0) = 0', $notRecurringQuery->toSql());
        $this->assertStringContainsString('COALESCE(so.is_recurring, 0) = 1', $recurringQuery->toSql());
    }
}
