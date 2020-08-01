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

namespace Cartalyst\Stripe\Tests\Api\Account;

use Cartalyst\Stripe\Tests\FunctionalTestCase;
use Cartalyst\Stripe\Exception\NotFoundException;

class CapabilitiesTest extends FunctionalTestCase
{
    /** @test */
    public function it_can_find_an_existing_capability()
    {
        $email = $this->getRandomEmail();

        $account = $this->stripe->account()->create([
            'type'                   => 'custom',
            'email'                  => $email,
            'requested_capabilities' => [
                'card_payments',
                'transfers',
            ],
        ]);

        $capability = $this->stripe->account()->capabilities()->find($account['id'], 'card_payments');

        $this->assertSame($account['id'], $capability['account']);
        $this->assertSame('card_payments', $capability['id']);
        $this->assertSame('inactive', $capability['status']);
    }

    /** @test */
    public function it_will_throw_an_exception_when_searching_for_a_non_existing_capability()
    {
        $this->expectException(NotFoundException::class);

        $email = $this->getRandomEmail();

        $account = $this->stripe->account()->create([
            'type'                   => 'custom',
            'email'                  => $email,
            'requested_capabilities' => [
                'card_payments',
                'transfers',
            ],
        ]);

        $this->stripe->account()->capabilities()->find($account['id'], 'not_found');
    }

    /** @test */
    public function it_can_update_a_capability()
    {
        $email = $this->getRandomEmail();

        $account = $this->stripe->account()->create([
            'type'                   => 'custom',
            'email'                  => $email,
            'requested_capabilities' => [
                'card_payments',
                'transfers',
            ],
        ]);

        $capability = $this->stripe->account()->capabilities()->update($account['id'], 'card_payments', [
            'requested' => true,
        ]);

        $this->assertSame($account['id'], $capability['account']);
        $this->assertSame('card_payments', $capability['id']);
        $this->assertSame('inactive', $capability['status']);
    }

    /** @test */
    public function it_can_retrieve_all_capabilities()
    {
        $email = $this->getRandomEmail();

        $account = $this->stripe->account()->create([
            'type'                   => 'custom',
            'email'                  => $email,
            'requested_capabilities' => [
                'card_payments',
                'transfers',
            ],
        ]);

        $capabilities = $this->stripe->account()->capabilities()->all($account['id']);

        $this->assertNotEmpty($capabilities['data']);
        $this->assertIsArray($capabilities['data']);
    }
}
