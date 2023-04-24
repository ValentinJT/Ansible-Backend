<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductController extends AbstractController
{

    private SerializerInterface $serializer;
    private ProductRepository $productRepository;
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;


    public function __construct(SerializerInterface $serializer, ProductRepository $productRepository, EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        $this->serializer = $serializer;
        $this->productRepository = $productRepository;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    #[Route('/api/products', name: 'get_products', methods: ["GET"])]
    public function getProducts(): Response
    {
        $products = $this->productRepository->findBy(["enabled" => true]);

        $productsJson = $this->serializer->serialize($products, 'json', ["groups" => "show_products_list"]);

        return new JsonResponse($productsJson, Response::HTTP_OK, [], true);
    }

    #[Route('/api/products/{id}', name: 'get_product', methods: ["GET"])]
    public function getProduct($id): Response
    {
        $product = $this->productRepository->find($id);

        if ($product == null) {
            $error = $this->error(Response::HTTP_NOT_FOUND, "Product does not exists");

            return new JsonResponse($error["message"], $error["code"], [], true);
        }

        $productJson = $this->serializer->serialize($product, 'json', ["groups" => "show_product"]);

        return new JsonResponse($productJson, Response::HTTP_OK, [], true);
    }

    #[Route('/api/products', name: 'add_products', methods: ["POST"])]
    public function addProduct(Request $request): Response
    {
        $content = json_decode($request->getContent(), true);

        try {

            $product = new Product();

            $product->setName($content["name"]);
            $product->setPhoto($content["photo"]);
            $product->setPrice($content["price"]);
            $product->setDescription($content["description"]);
            $product->setEnabled(true);

            $this->entityManager->persist($product);
            $this->entityManager->flush();

            $productJson = $this->serializer->serialize($product, "json", ["groups" => "show_product"]);

        } catch (Exception $e) {
            $error = $this->error(Response::HTTP_BAD_REQUEST, "Malformatted JSON");

            return new JsonResponse($error["message"], $error["code"], [], true);
        }

        return new JsonResponse($productJson, Response::HTTP_OK, [], true);
    }

    #[Route('/api/products/{id}', name: 'delete_product', methods: ["DELETE"])]
    public function deleteProduct($id): Response
    {

        $product = $this->productRepository->findOneBy([ "id" => $id ]);

        if(!$product->isEnabled()) {
            $error = $this->error(Response::HTTP_UNAUTHORIZED, "Product already disabled");

            return new JsonResponse($error["message"], $error["code"], [], true);
        } else {

            $product->setEnabled(false);
            $product->setDisabledAt(new \DateTimeImmutable());

            $this->entityManager->flush();

            return new Response(null, Response::HTTP_OK);
        }
    }

    #[Route('/api/products/{id}', name: 'update_product', methods: ["PUT"])]
    public function updateProduct(Request $request, $id): Response
    {
        $content = json_decode($request->getContent(), true);

        try {

            $product = $this->productRepository->find($id);

            $product->setName($content["name"]);
            $product->setPhoto($content["photo"]);
            $product->setPrice($content["price"]);
            $product->setDescription($content["description"]);

            if ($content["enabled"]) {
                $product->setDisabledAt(null);
            } else {
                $product->setDisabledAt(new \DateTimeImmutable());
            }

            $product->setEnabled($content["enabled"]);

            $this->entityManager->flush();

            $productJson = $this->serializer->serialize($product, "json", ["groups" => "show_product"]);

        } catch (Exception $e) {
            $error = $this->error(Response::HTTP_BAD_REQUEST, "Malformatted JSON");

            return new JsonResponse($error["message"], $error["code"], [], true);
        }

        return new JsonResponse($productJson, Response::HTTP_OK, [], true);
    }

    private function error($code, $message)
    {
        return ["code" => $code, "message" => json_encode(["error" => $message])];
    }

}
