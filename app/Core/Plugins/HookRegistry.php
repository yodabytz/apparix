<?php

namespace App\Core\Plugins;

/**
 * Registry for plugin hooks
 * Allows plugins to register callbacks and trigger events
 */
class HookRegistry
{
    /**
     * Registered hooks
     * @var array<string, array<array{callback: callable, priority: int}>>
     */
    private static array $hooks = [];

    /**
     * Register a callback for a hook
     *
     * @param string $hook Hook name
     * @param callable $callback Function to call
     * @param int $priority Lower priority runs first (default 10)
     */
    public static function add(string $hook, callable $callback, int $priority = 10): void
    {
        if (!isset(self::$hooks[$hook])) {
            self::$hooks[$hook] = [];
        }

        self::$hooks[$hook][] = [
            'callback' => $callback,
            'priority' => $priority
        ];

        // Sort by priority
        usort(self::$hooks[$hook], fn($a, $b) => $a['priority'] <=> $b['priority']);
    }

    /**
     * Remove a callback from a hook
     */
    public static function remove(string $hook, callable $callback): void
    {
        if (!isset(self::$hooks[$hook])) {
            return;
        }

        self::$hooks[$hook] = array_filter(
            self::$hooks[$hook],
            fn($h) => $h['callback'] !== $callback
        );
    }

    /**
     * Check if a hook has any callbacks registered
     */
    public static function has(string $hook): bool
    {
        return !empty(self::$hooks[$hook]);
    }

    /**
     * Execute all callbacks for a hook (action)
     * Does not return a value
     *
     * @param string $hook Hook name
     * @param mixed ...$args Arguments to pass to callbacks
     */
    public static function doAction(string $hook, ...$args): void
    {
        if (!isset(self::$hooks[$hook])) {
            return;
        }

        foreach (self::$hooks[$hook] as $registered) {
            call_user_func_array($registered['callback'], $args);
        }
    }

    /**
     * Execute all callbacks for a hook (filter)
     * Returns the filtered value
     *
     * @param string $hook Hook name
     * @param mixed $value Value to filter
     * @param mixed ...$args Additional arguments
     * @return mixed Filtered value
     */
    public static function applyFilters(string $hook, $value, ...$args): mixed
    {
        if (!isset(self::$hooks[$hook])) {
            return $value;
        }

        foreach (self::$hooks[$hook] as $registered) {
            $value = call_user_func_array($registered['callback'], [$value, ...$args]);
        }

        return $value;
    }

    /**
     * Get all registered hooks (for debugging)
     */
    public static function getAll(): array
    {
        return self::$hooks;
    }

    /**
     * Clear all hooks (mainly for testing)
     */
    public static function clear(): void
    {
        self::$hooks = [];
    }

    /**
     * Clear hooks for a specific name
     */
    public static function clearHook(string $hook): void
    {
        unset(self::$hooks[$hook]);
    }
}

/**
 * Available hooks:
 *
 * PAYMENT HOOKS:
 * - payment_providers_list: Filter the list of available payment providers
 * - before_payment_create: Action before creating payment session
 * - after_payment_verify: Action after payment verification
 * - payment_webhook_received: Action when webhook is received
 *
 * CHECKOUT HOOKS:
 * - checkout_payment_methods: Filter payment methods shown at checkout
 * - before_order_create: Action before order is created
 * - after_order_create: Action after order is created
 * - order_status_changed: Action when order status changes
 *
 * THEME HOOKS:
 * - theme_css_variables: Filter CSS variables
 * - theme_head_scripts: Filter scripts in <head>
 * - theme_footer_scripts: Filter scripts before </body>
 * - navbar_menu_items: Filter navigation menu items
 * - footer_menu_items: Filter footer menu items
 *
 * ADMIN HOOKS:
 * - admin_dashboard_widgets: Add widgets to admin dashboard
 * - admin_sidebar_menu: Add items to admin sidebar
 */
