<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class OrderController extends AbstractController
{

    private SerializerInterface $serializer;
    private OrderRepository $orderRepository;

    public function __construct(SerializerInterface $serializer, OrderRepository $orderRepository)
    {
        $this->serializer = $serializer;
        $this->orderRepository = $orderRepository;
    }

    #[Route('/api/orders', name: 'get_orders', methods: ["GET"])]
    public function getOrders(): Response
    {
        $orders = $this->orderRepository->findBy(["user" => $this->getUserFromSession()]);

        $ordersJson = $this->serializer->serialize($orders, 'json', ["groups" => "show_order"]);

        return new JsonResponse($ordersJson, Response::HTTP_OK, [], true);
    }

    #[Route('/api/orders/{id}', name: 'get_order', methods: ["GET"])]
    public function getOrder($id): Response
    {
        $order = $this->orderRepository->findOneByIdAndUser($id, $this->getUserFromSession());

        if ($order == null) {
            $error = $this->error(Response::HTTP_NOT_FOUND, "This order does not exists.");

            return new JsonResponse($error["message"], $error["code"], [], true);
        }

        $orderJson = $this->serializer->serialize($order, 'json', ["groups" => "show_order"]);

        return new JsonResponse($orderJson, Response::HTTP_OK, [], true);
    }

    private function error($code, $message)
    {
        return ["code" => $code, "message" => json_encode(["error" => $message])];
    }


    private function getUserFromSession(): User
    {
        return $this->getUser();
    }
}
