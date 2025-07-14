<?php

declare(strict_types=1);

namespace App\Http\Controllers\Order;

use App\Application\Order\Commands\AddToCartCommand;
use App\Application\Order\Commands\SubmitCartCommand;
use App\Application\Order\Contracts\CartRepositoryInterface;
use App\Application\Order\UseCases\AddToCartUseCase;
use App\Application\Order\UseCases\SubmitCartUseCase;
use App\Domain\Agent\Contracts\AgentRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\AddToCartRequest;
use App\Http\Requests\Order\SubmitCartRequest;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrderController extends Controller
{
    public function __construct(
        private readonly AddToCartUseCase $addToCartUseCase,
        private readonly SubmitCartUseCase $submitCartUseCase,
        private readonly CartRepositoryInterface $cartRepository
    ) {}

    /**
     * Add a bet to the cart
     */
    public function addToCart(AddToCartRequest $request): JsonResponse
    {
        try {
            $command = new AddToCartCommand(
                agentId: $request->attributes->get('agent_id'),
                period: $request->validated('period'),
                type: $request->validated('type'),
                channels: (array) $request->validated('channels'),
                option: $request->validated('option'),
                number: $request->validated('number'),
                amount: (float) $request->validated('amount')
            );

            $result = $this->addToCartUseCase->execute($command);

            return response()->json([
                'success' => true,
                'message' => 'Item added to cart successfully',
                'data' => $result,
            ], 201);
        } catch (Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add item to cart',
                'error' => $exception->getMessage(),
            ], 400);
        }
    }

    /**
     * Get current cart contents
     */
    public function getCart(Request $request): JsonResponse
    {
        try {
            $agentId = $request->attributes->get('agent_id');

            // Get cart items using the repository
            $agent = app(AgentRepositoryInterface::class)->findById($agentId);

            if (! $agent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Agent not found',
                ], 404);
            }

            $cartItems = $this->cartRepository->getItems($agent);

            // Calculate totals
            $totalAmount = 0;
            $currency = 'KHR';
            $totalItems = count($cartItems);

            foreach ($cartItems as $item) {
                $totalAmount += $item['total_amount'] ?? 0;
            }

            return response()->json([
                'success' => true,
                'message' => 'Cart retrieved successfully',
                'data' => [
                    'items' => $cartItems,
                    'summary' => [
                        'total_items' => $totalItems,
                        'total_amount' => $totalAmount,
                        'currency' => $currency,
                    ],
                ],
            ]);
        } catch (Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve cart',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Submit cart as order
     */
    public function submitCart(SubmitCartRequest $request): JsonResponse
    {
        try {
            $command = new SubmitCartCommand(agentId: $request->attributes->get('agent_id'));

            $result = $this->submitCartUseCase->execute($command);

            return response()->json([
                'success' => true,
                'message' => 'Cart submitted successfully',
                'data' => $result,
            ], 201);
        } catch (Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit cart',
                'error' => $exception->getMessage(),
            ], 400);
        }
    }

    /**
     * Clear cart
     */
    public function clearCart(Request $request): JsonResponse
    {
        try {
            $agentId = $request->attributes->get('agent_id');

            $agent = app(AgentRepositoryInterface::class)->findById($agentId);

            if (! $agent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Agent not found',
                ], 404);
            }

            $this->cartRepository->clearCart($agent);

            return response()->json([
                'success' => true,
                'message' => 'Cart cleared successfully',
            ]);
        } catch (Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cart',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart(Request $request, int $itemId): JsonResponse
    {
        try {
            $agentId = $request->attributes->get('agent_id');

            $agent = app(AgentRepositoryInterface::class)->findById($agentId);

            if (! $agent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Agent not found',
                ], 404);
            }

            $this->cartRepository->removeItem($agent, $itemId);

            return response()->json([
                'success' => true,
                'message' => 'Item removed from cart successfully',
            ]);
        } catch (Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove item from cart',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Get order history
     */
    public function getOrderHistory(Request $request, AgentRepositoryInterface $agentRepository): JsonResponse
    {
        try {
            $agentId = $request->attributes->get('agent_id');

            $agent = $agentRepository->findById($agentId);

            if (!$agent instanceof \App\Domain\Agent\Models\Agent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Agent not found',
                ], 404);
            }

            $orderRepository = app(\App\Application\Order\Contracts\OrderRepositoryInterface::class);

            $filters = [
                'status' => $request->query('status'),
                'date_from' => $request->query('date_from'),
                'date_to' => $request->query('date_to'),
                'type' => $request->query('type'),
                'period' => $request->query('period'),
            ];

            $limit = (int) $request->query('per_page', 20);
            $offset = (int) $request->query('page_size', 0);

            $orders = $orderRepository->findByAgent($agent, $filters, $limit, $offset);

            return response()->json([
                'success' => true,
                'message' => 'Order history retrieved successfully',
                'data' => [
                    'orders' => $orders,
                    'pagination' => [
                        'per_page' => $limit,
                        'page_size' => $offset,
                        'total' => count($orders),
                    ],
                ],
            ]);
        } catch (Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve order history',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Get order by ID
     */
    public function getOrder(Request $request, int $orderId): JsonResponse
    {
        try {
            $orderRepository = app(\App\Application\Order\Contracts\OrderRepositoryInterface::class);

            $order = $orderRepository->findById($orderId);

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            // Check if the order belongs to the authenticated agent
            if ($order->agentId() !== $request->attributes->get('agent_id')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order retrieved successfully',
                'data' => [
                    'id' => $order->id(),
                    'order_number' => $order->orderNumber()->value(),
                    'group_id' => $order->groupId()->value(),
                    'agent_id' => $order->agentId(),
                    'bet_data' => $order->betData(),
                    'expanded_numbers' => $order->expandedNumbers(),
                    'channel_weights' => $order->channelWeights(),
                    'total_amount' => $order->totalAmount()->amount(),
                    'currency' => $order->totalAmount()->currency(),
                    'status' => $order->status(),
                    'is_printed' => $order->isPrinted(),
                    'placed_at' => $order->placedAt()->format('Y-m-d H:i:s'),
                    'created_at' => $order->createdAt()->format('Y-m-d H:i:s'),
                    'updated_at' => $order->updatedAt()->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve order',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }
}
