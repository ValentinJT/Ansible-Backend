<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\ProductOrder;
use App\Entity\User;
use App\Repository\CartRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class CartController extends AbstractController
{
    private SerializerInterface $serializer;
    private OrderRepository $orderRepository;
    private ProductRepository $productRepository;
    private UserRepository $userRepository;
    private CartRepository $cartRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(SerializerInterface $serializer, OrderRepository $orderRepository, ProductRepository $productRepository, CartRepository $cartRepository, UserRepository $userRepository, EntityManagerInterface $entityManager)
    {
        $this->serializer = $serializer;
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->userRepository = $userRepository;
        $this->cartRepository = $cartRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/api/carts', name: 'get_products_in_cart', methods: ["GET"])]
    public function getProductsInCart(): Response
    {
        $userFromSession = $this->getUserFromSession();

        $cartProducts = [];

        foreach($userFromSession->getCarts() as $cartProduct) {
            $product = $this->convertProduct($cartProduct->getProduct());
            $product["amount"] = $cartProduct->getAmount();

            $cartProducts[] = $product;
        }

        return new JsonResponse(json_encode($cartProducts), Response::HTTP_OK, [], true);
    }

    #[Route('/api/carts/{id}', name: 'add_product_in_cart', requirements: ["id" => "\d+"], methods: ["POST"])]
    public function addProductInCart($id): Response
    {
        $userFromSession = $this->getUserFromSession();
        $user = $this->userRepository->find($userFromSession->getId());

        $product = $this->productRepository->find($id);

        if ($product == null) {
            $error = $this->error(Response::HTTP_NOT_FOUND, "This product does not exists.");
            return new JsonResponse($error["message"], $error["code"], [], true);

        } else if (!$product->isEnabled()) {
            $error = $this->error(Response::HTTP_UNAUTHORIZED, "This product is disabled.");
            return new JsonResponse($error["message"], $error["code"], [], true);
        }

        $cart = $this->cartRepository->findOneByProductAndUser($product, $user);

        if($cart == null) {
            $newCart = new Cart();

            $newCart->setUser($user);
            $newCart->setProduct($product);
            $newCart->setAmount(1);

            $user->addCart($newCart);

            $this->entityManager->persist($newCart);
        } else {
            $cart->addAmount(1);
        }

        $this->entityManager->flush();

        $cartProducts = [];

        foreach($userFromSession->getCarts() as $cartProduct) {
            $product = $this->convertProduct($cartProduct->getProduct());
            $product["amount"] = $cartProduct->getAmount();

            $cartProducts[] = $product;
        }

        return new JsonResponse(json_encode($cartProducts), Response::HTTP_OK, [], true);
    }

    #[Route('/api/carts/validate', name: 'validate_cart', methods: ["POST"])]
    public function validateCart(): Response
    {

        $user = $this->getUserFromSession();

        $order = new Order();
        $order->setUser($user);
        $order->setCreationDate(new \DateTimeImmutable());

        $totalPrice = 0;

        foreach($user->getCarts() as $cart) {
            $productOrder = new ProductOrder();
            $productOrder->setProduct($cart->getProduct());
            $productOrder->setOrderr($order);
            $productOrder->setAmount($cart->getAmount());

            $totalPrice += $cart->getProduct()->getPrice()*$cart->getAmount();

            $this->entityManager->remove($cart);
            $this->entityManager->persist($productOrder);
        }

        $order->setTotalPrice($totalPrice);

        $this->entityManager->flush();

        $orderJson = $this->serializer->serialize($order, "json", ["groups" => ["show_order", "show_product"]]);

        return new JsonResponse($orderJson, Response::HTTP_OK, [], true);
    }

    #[Route('/api/carts/{id}', name: 'remove_product_in_cart', methods: ["DELETE"])]
    public function removeProductInCart($id): Response
    {
        $userFromSession = $this->getUserFromSession();

        $user = $this->userRepository->find($userFromSession->getId());

        $product = $this->productRepository->find($id);

        if ($product == null) {
            $error = $this->error(Response::HTTP_NOT_FOUND, "This product does not exists.");
            return new JsonResponse($error["message"], $error["code"], [], true);
        }

        $cart = $this->cartRepository->findOneByProductAndUser($product, $user);

        if (!$user->getCarts()->contains($cart)) {
            $error = $this->error(Response::HTTP_UNAUTHORIZED, "This product is not in your cart.");
            return new JsonResponse($error["message"], $error["code"], [], true);
        }

        $cart->addAmount(-1);

        if($cart->getAmount() <= 0) {
            $this->entityManager->remove($cart);
        }

        $this->entityManager->flush();

        $cartProducts = [];

        foreach($user->getCarts() as $cartProduct) {
            $product = $this->convertProduct($cartProduct->getProduct());
            $product["amount"] = $cartProduct->getAmount();

            $cartProducts[] = $product;
        }

        return new JsonResponse(json_encode($cartProducts), Response::HTTP_OK, [], true);
    }

    private function error($code, $message)
    {
        return ["code" => $code, "message" => json_encode(["error" => $message])];
    }

    private function getUserFromSession(): User
    {
        return $this->getUser();
    }

    private function convertProduct(Product $product): array {
        return [
            "id" => $product->getId(),
            "name" => $product->getName(),
            "description" => $product->getDescription(),
            "price" => $product->getPrice(),
            "photo" => $product->getPhoto(),
            "deleted_at" => $product->getDisabledAt()
        ];
    }
}
