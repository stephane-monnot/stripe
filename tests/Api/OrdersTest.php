<?php

declare(strict_types=1);

/*
 * Part of the Stripe package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.
 *
 * @package    Stripe
 * @version    3.0.0
 * @author     Cartalyst LLC
 * @license    BSD License (3-clause)
 * @copyright  (c) 2011-2020, Cartalyst LLC
 * @link       https://cartalyst.com
 */

namespace Cartalyst\Stripe\Tests\Api;

use Cartalyst\Stripe\Tests\FunctionalTestCase;
use Cartalyst\Stripe\Exception\NotFoundException;

class OrdersTest extends FunctionalTestCase
{
    /** @test */
    public function it_can_create_a_new_order()
    {
        $product = $this->createProductForOrder();

        $sku = $this->createProductSku($product['id']);

        $items = [
            ['type' => 'sku', 'parent' => $sku['id']],
        ];

        $order = $this->createOrder($items);

        $this->assertSame('created', $order['status']);
    }

    /** @test */
    public function it_can_find_an_order()
    {
        $product = $this->createProductForOrder();

        $sku = $this->createProductSku($product['id']);

        $items = [
            ['type' => 'sku', 'parent' => $sku['id']],
        ];

        $order = $this->createOrder($items);

        $order = $this->stripe->orders()->find($order['id']);

        $this->assertSame('created', $order['status']);
    }

    /** @test */
    public function it_will_throw_an_exception_when_searching_for_a_non_existing_order()
    {
        $this->expectException(NotFoundException::class);

        $this->stripe->orders()->find('not_found');
    }

    /** @test */
    public function it_can_update_an_order()
    {
        $product = $this->createProductForOrder();

        $sku = $this->createProductSku($product['id']);

        $items = [
            ['type' => 'sku', 'parent' => $sku['id']],
        ];

        $order = $this->createOrder($items);

        $order = $this->stripe->orders()->update($order['id'], [
            'metadata' => ['foo' => 'Bar'],
        ]);

        $this->assertSame(['foo' => 'Bar'], $order['metadata']);
    }

    /** @test */
    public function it_can_pay_an_order()
    {
        $customer = $this->createCustomer();

        $this->createCardThroughToken($customer['id']);

        $product = $this->createProductForOrder();

        $sku = $this->createProductSku($product['id']);

        $items = [
            ['type' => 'sku', 'parent' => $sku['id']],
        ];

        $order = $this->createOrder($items);

        $order = $this->stripe->orders()->pay($order['id'], [
            'customer' => $customer['id'],
        ]);

        $this->assertSame('paid', $order['status']);
    }

    /** @test */
    public function it_can_return_an_order_partially()
    {
        $customer = $this->createCustomer();

        $this->createCardThroughToken($customer['id']);

        $product1 = $this->createProductForOrder();
        $sku1     = $this->createProductSku($product1['id']);

        $product2 = $this->createProductForOrder();
        $sku2     = $this->createProductSku($product2['id']);

        $items = [
            ['type' => 'sku', 'parent' => $sku1['id']],
            ['type' => 'sku', 'parent' => $sku2['id']],
        ];

        $order = $this->createOrder($items);

        $orderId = $order['id'];

        $order = $this->stripe->orders()->pay($orderId, [
            'customer' => $customer['id'],
        ]);

        $this->stripe->refunds()->create($order['charge']);

        $this->stripe->orders()->returnItems($orderId, [
            ['type' => 'sku', 'parent' => $sku2['id']],
        ]);

        $order = $this->stripe->orders()->find($orderId);

        $this->assertCount(4, $order['items']);
        $this->assertSame('paid', $order['status']);
        $this->assertCount(1, $order['returns']['data']);
    }

    /** @test */
    public function it_can_return_an_order_completely()
    {
        $customer = $this->createCustomer();

        $this->createCardThroughToken($customer['id']);

        $product1 = $this->createProductForOrder();
        $sku1     = $this->createProductSku($product1['id']);

        $product2 = $this->createProductForOrder();
        $sku2     = $this->createProductSku($product2['id']);

        $items = [
            ['type' => 'sku', 'parent' => $sku1['id']],
            ['type' => 'sku', 'parent' => $sku2['id']],
        ];

        $order = $this->createOrder($items);

        $orderId = $order['id'];

        $order = $this->stripe->orders()->pay($orderId, [
            'customer' => $customer['id'],
        ]);

        $this->stripe->refunds()->create($order['charge']);

        $this->stripe->orders()->returnItems($orderId);

        $order = $this->stripe->orders()->find($orderId);

        $this->assertCount(4, $order['items']);
        $this->assertSame('canceled', $order['status']);
    }

    /** @test */
    public function it_can_retrieve_all_orders()
    {
        $orders = $this->stripe->orders()->all();

        $this->assertNotEmpty($orders['data']);
        $this->assertIsArray($orders['data']);
    }

    /** @test */
    public function it_can_iterate_all_orders()
    {
        $product = $this->createProductForOrder();

        $sku = $this->createProductSku($product['id']);

        $ids = [];

        for ($i = 0; $i < 5; $i++) {
            $ids[] = $this->createOrder([
                ['type' => 'sku', 'parent' => $sku['id']],
            ])['id'];
        }

        $orders = $this->stripe->ordersIterator(['ids' => $ids]);

        $this->assertCount(5, $orders);
    }
}
