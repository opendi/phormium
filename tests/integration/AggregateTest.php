<?php

namespace Phormium\Tests\Integration;

use Phormium\Exception\InvalidQueryException;
use Phormium\Tests\Models\Trade;

/**
 * @group integration
 * @group aggregate
 */
class AggregateTest extends DbTest {
    public function testAggregates() {
        $tradedate = date('Y-m-d');
        $count = 10;

        $qs = Trade::objects()->filter('tradedate', '=', $tradedate);

        // Delete any existing trades for today
        $qs->delete();

        // Create trades with random prices and quantitities
        $prices = [];
        $quantities = [];

        foreach (range(1, $count) as $tradeno) {
            $price = rand(100, 100000) / 100;
            $quantity = rand(1, 10000);

            $t = new Trade();
            $t->merge(compact('tradedate', 'tradeno', 'price', 'quantity'));
            $t->insert();

            $prices[] = $price;
            $quantities[] = $quantity;
        }

        // Calculate expected values
        $avgPrice = round(array_sum($prices) / count($prices), 3);
        $maxPrice = max($prices);
        $minPrice = min($prices);
        $sumPrice = round(array_sum($prices), 3);

        $avgQuantity = array_sum($quantities) / count($quantities);
        $maxQuantity = max($quantities);
        $minQuantity = min($quantities);
        $sumQuantity = array_sum($quantities);

        $this->assertSame($count, $qs->count());

        $this->assertEquals($avgPrice, $qs->avg('price'), "avg function");
        $this->assertEquals($minPrice, $qs->min('price'), "min function");
        $this->assertEquals($maxPrice, $qs->max('price'), "max function");
        $this->assertEquals($sumPrice, $qs->sum('price'), "sum function");

        $this->assertEquals($avgQuantity, $qs->avg('quantity'), "avg function");
        $this->assertEquals($minQuantity, $qs->min('quantity'), "min function");
        $this->assertEquals($maxQuantity, $qs->max('quantity'), "max function");
        $this->assertEquals($sumQuantity, $qs->sum('quantity'), "sum function");
    }

    public function testInvalidColumn() {
        $this->expectExceptionMessage("Error forming aggregate query. Column [xxx] does not exist in table [trade].");
        $this->expectException(InvalidQueryException::class);
        Trade::objects()->avg('xxx');
    }
}
