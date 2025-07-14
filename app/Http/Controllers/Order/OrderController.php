<?php

namespace App\Http\Controllers\Order;

use App\Application\Order\Commands\AddToCartCommand;
use App\Application\Order\Commands\SubmitCartCommand;
use App\Application\Order\Contracts\CartRepositoryInterface;
use App\Application\Order\UseCases\AddToCartUseCase;
use App\Application\Order\UseCases\SubmitCartUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\AddToCartRequest;
use App\Http\Requests\Order\SubmitCartRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
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
                agentId: Auth::id(),
                period: $request->validated('period'),
                type: $request->validated('type'),
                channels: $request->validated('channels'),
                option: $request->validated('option'),
                number: $request->validated('number'),
                amount: $request->validated('amount')
            );

            $result = $this->addToCartUseCase->execute($command);

            return response()->json([
                'success' => true,
                'message' => 'Item added to cart successfully',
                'data' => $result
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add item to cart',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get current cart contents
     */
    public function getCart(): JsonResponse
    {
        try {
            $agentId = Auth::id();

            // Get cart items using the repository
            $agent = app(\App\Domain\Agent\Contracts\AgentRepositoryInterface::class)->findById($agentId);

            if (!$agent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Agent not found'
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
                        'currency' => $currency
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit cart as order
     */
    public function submitCart(SubmitCartRequest $request): JsonResponse
    {
        try {
            $command = new SubmitCartCommand(agentId: Auth::id());

            $result = $this->submitCartUseCase->execute($command);

            return response()->json([
                'success' => true,
                'message' => 'Cart submitted successfully',
                'data' => $result
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit cart',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Clear cart
     */
    public function clearCart(): JsonResponse
    {
        try {
            $agentId = Auth::id();

            $agent = app(\App\Domain\Agent\Contracts\AgentRepositoryInterface::class)->findById($agentId);

            if (!$agent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Agent not found'
                ], 404);
            }

            $this->cartRepository->clearCart($agent);

            return response()->json([
                'success' => true,
                'message' => 'Cart cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart(Request $request, int $itemId): JsonResponse
    {
        try {
            $agentId = Auth::id();

            $agent = app(\App\Domain\Agent\Contracts\AgentRepositoryInterface::class)->findById($agentId);

            if (!$agent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Agent not found'
                ], 404);
            }

            $this->cartRepository->removeItem($agent, $itemId);

            return response()->json([
                'success' => true,
                'message' => 'Item removed from cart successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove item from cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order history
     */
    public function getOrderHistory(Request $request): JsonResponse
    {
        try {
            $agentId = Auth::id();

            $agent = app(\App\Domain\Agent\Contracts\AgentRepositoryInterface::class)->findById($agentId);

            if (!$agent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Agent not found'
                ], 404);
            }

            $orderRepository = app(\App\Application\Order\Contracts\OrderRepositoryInterface::class);

            $filters = [
                'status' => $request->query('status'),
                'date_from' => $request->query('date_from'),
                'date_to' => $request->query('date_to'),
                'type' => $request->query('type'),
                'period' => $request->query('period')
            ];

            $limit = $request->query('limit', 20);
            $offset = $request->query('offset', 0);

            $orders = $orderRepository->findByAgent($agent, $filters, $limit, $offset);

            return response()->json([
                'success' => true,
                'message' => 'Order history retrieved successfully',
                'data' => [
                    'orders' => $orders,
                    'pagination' => [
                        'limit' => $limit,
                        'offset' => $offset,
                        'total' => count($orders)
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve order history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order by ID
     */
    public function getOrder(int $orderId): JsonResponse
    {
        try {
            $orderRepository = app(\App\Application\Order\Contracts\OrderRepositoryInterface::class);

            $order = $orderRepository->findById($orderId);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            // Check if the order belongs to the authenticated agent
            if ($order->agentId() !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
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
                    'updated_at' => $order->updatedAt()->format('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve order',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
