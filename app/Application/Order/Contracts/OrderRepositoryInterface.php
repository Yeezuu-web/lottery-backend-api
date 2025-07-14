<?php

namespace App\Application\Order\Contracts;

use App\Domain\Agent\Models\Agent;
use App\Domain\Order\Models\Order;
use App\Domain\Order\ValueObjects\GroupId;
use App\Domain\Order\ValueObjects\OrderNumber;

interface OrderRepositoryInterface
{
    /**
     * Save an order
     *
     * @param  Order  $order  The order to save
     * @return Order The saved order with ID
     */
    public function save(Order $order): Order;

    /**
     * Find an order by ID
     *
     * @param  int  $id  The order ID
     * @return Order|null The order or null if not found
     */
    public function findById(int $id): ?Order;

    /**
     * Find an order by order number
     *
     * @param  OrderNumber  $orderNumber  The order number
     * @return Order|null The order or null if not found
     */
    public function findByOrderNumber(OrderNumber $orderNumber): ?Order;

    /**
     * Find orders by group ID
     *
     * @param  GroupId  $groupId  The group ID
     * @return array Array of orders
     */
    public function findByGroupId(GroupId $groupId): array;

    /**
     * Find orders for an agent
     *
     * @param  Agent  $agent  The agent
     * @param  array  $filters  Optional filters
     * @param  int  $limit  Number of orders to return
     * @param  int  $offset  Offset for pagination
     * @return array Array of orders
     */
    public function findByAgent(Agent $agent, array $filters = [], int $limit = 10, int $offset = 0): array;

    /**
     * Delete an order
     *
     * @param  Order  $order  The order to delete
     * @return bool True if deleted, false otherwise
     */
    public function delete(Order $order): bool;

    /**
     * Get orders by status
     *
     * @param  string  $status  The order status
     * @param  int  $limit  Number of orders to return
     * @param  int  $offset  Offset for pagination
     * @return array Array of orders
     */
    public function findByStatus(string $status, int $limit = 10, int $offset = 0): array;

    /**
     * Get orders by date range
     *
     * @param  \DateTime  $startDate  Start date
     * @param  \DateTime  $endDate  End date
     * @param  int  $limit  Number of orders to return
     * @param  int  $offset  Offset for pagination
     * @return array Array of orders
     */
    public function findByDateRange(\DateTime $startDate, \DateTime $endDate, int $limit = 10, int $offset = 0): array;

    /**
     * Count orders for an agent
     *
     * @param  Agent  $agent  The agent
     * @param  array  $filters  Optional filters
     * @return int Number of orders
     */
    public function countByAgent(Agent $agent, array $filters = []): int;

    /**
     * Execute a transaction
     *
     * @param  callable  $callback  The transaction callback
     * @return mixed The result of the callback
     */
    public function transaction(callable $callback): mixed;

    /**
     * Get orders that can be printed
     *
     * @param  Agent  $agent  The agent
     * @return array Array of orders that can be printed
     */
    public function findPrintableOrders(Agent $agent): array;

    /**
     * Bulk update order status
     *
     * @param  array  $orderIds  Array of order IDs
     * @param  string  $status  New status
     * @return bool True if updated, false otherwise
     */
    public function bulkUpdateStatus(array $orderIds, string $status): bool;
}
