<?php

	require_once 'vendor/autoload.php';

	use Stripe\StripeClient;

	$conn = new mysqli('127.0.0.1', 'snapps', '', 'gonzy', 8889);

	if ($conn->connect_error) {
		die("❌ Connection failed: " . $conn->connect_error);
	}

	$oldStripe = new StripeClient('sk_live_...');
	$newStripe = new StripeClient('sk_live_...');

	$subscriptions = $oldStripe->subscriptions->all(['limit' => 100]);

	foreach ($subscriptions->data as $subscription) {
		$subscriptionId = $subscription->id;

		$subscription = $oldStripe->subscriptions->retrieve($subscriptionId);

		$oldCustomerId = $subscription->customer;
		$status = $subscription->status;
		$planId = $subscription->items->data[0]->price->id;

		echo 'Subscription ID: ' . $subscriptionId;
		echo '<br>';
		echo '<br>';
		echo '--------------------------------';
		echo '<br>';
		echo '<br>';

	try {
		// Retrieve customer email
		$oldCustomer = $oldStripe->customers->retrieve($oldCustomerId);
		$email = $oldCustomer->email;

		// Lookup customer in new Stripe
		$newCustomers = $newStripe->customers->all(['email' => $email, 'limit' => 1]);
		if (count($newCustomers->data) === 0) {
			echo "Skipping {$subscriptionId}: No matching customer for {$email}\n";
			continue;
		}

		$newCustomerId = $newCustomers->data[0]->id;

		// Common subscription payload
		$params = [
			'customer' => $newCustomerId,
			'items' => [['price' => $planId]],
			'metadata' => ['old_subscription_id' => $subscriptionId]
		];

		if ($status === 'trialing') {
			$params['trial_end'] = $subscription->trial_end;
		} elseif ($status === 'active') {
			$params['trial_end'] = $subscription->items->data[0]->current_period_end;
		} elseif ($status === 'past_due') {
			$params['trial_end'] = time() + 86400; // 1 day from now
		} else {
			echo "Skipping {$subscriptionId}: status = {$status}\n";
			continue;
		}

		$discount_id = null;
		foreach ($subscription->discounts as $discount) {
			$discount_id = $discount;
		}

		// Create new subscription
		$newSub = $newStripe->subscriptions->create($params);

		// Cancels old subscription
		$oldStripe->subscriptions->cancel($subscription->id, [
			'invoice_now' => false,
			'prorate' => false
		]);

	} catch (Exception $e) {
		echo "Error on {$subscriptionId}: {$e->getMessage()}\n";
	}

}
