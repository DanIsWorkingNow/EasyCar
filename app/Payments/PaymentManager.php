<?php

namespace App\Payments;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\Gateways\BillplzGateway;
use App\Payments\Gateways\CounterGateway;
use App\Payments\Gateways\SandboxGateway;
use App\Payments\Gateways\StripeGateway;
use App\Payments\Gateways\ToyyibPayGateway;
use Illuminate\Support\Manager;
use InvalidArgumentException;

/**
 * Resolves payment drivers by name: app(PaymentManager::class)->driver('stripe').
 * Third-party drivers can be registered with ->extend('ipay88', fn () => ...).
 *
 * @method PaymentGateway driver(?string $driver = null)
 */
class PaymentManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return (string) $this->config->get('payments.default', 'sandbox');
    }

    public function createStripeDriver(): PaymentGateway
    {
        return new StripeGateway($this->gatewayConfig('stripe'));
    }

    public function createBillplzDriver(): PaymentGateway
    {
        return new BillplzGateway($this->gatewayConfig('billplz'));
    }

    public function createToyyibpayDriver(): PaymentGateway
    {
        return new ToyyibPayGateway($this->gatewayConfig('toyyibpay'));
    }

    public function createCounterDriver(): PaymentGateway
    {
        return new CounterGateway;
    }

    public function createSandboxDriver(): PaymentGateway
    {
        return new SandboxGateway((string) $this->config->get('app.key'));
    }

    /**
     * Gateways a customer can actually pay with right now.
     *
     * @return array<string, array{label: string, description: string}>
     */
    public function available(): array
    {
        $available = [];

        foreach ((array) $this->config->get('payments.gateways', []) as $name => $settings) {
            if (! ($settings['enabled'] ?? false) || ! $this->isAvailable($name)) {
                continue;
            }

            $available[$name] = [
                'label' => $settings['label'] ?? $name,
                'description' => $settings['description'] ?? '',
            ];
        }

        return $available;
    }

    public function isAvailable(string $name): bool
    {
        if (! $this->config->get("payments.gateways.{$name}.enabled")) {
            return false;
        }

        try {
            return $this->driver($name)->isConfigured();
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Like driver(), but for gateway names arriving from URLs — unknown
     * names become a 404 instead of an exception.
     */
    public function resolve(string $name): PaymentGateway
    {
        abort_unless(array_key_exists($name, (array) $this->config->get('payments.gateways', [])), 404);

        return $this->driver($name);
    }

    private function gatewayConfig(string $name): array
    {
        return (array) $this->config->get("payments.gateways.{$name}", []);
    }
}
