<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{

    private SerializerInterface $serializer;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private UserPasswordHasherInterface $userPasswordHasher;


    public function __construct(SerializerInterface $serializer, UserRepository $userRepository, EntityManagerInterface $entityManager, ValidatorInterface $validator, UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->serializer = $serializer;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->userPasswordHasher = $userPasswordHasher;
    }

    #[Route('/api/register', name: 'register_user', methods: ['POST'])]
    public function registerUser(Request $request): Response
    {
        $content = json_decode($request->getContent(), true);

        try {
            $user = new User();

            $password = $this->userPasswordHasher->hashPassword($user, $content["password"]);

            $user->setPassword($password);
            $user->setEmail($content["email"]);
            $user->setFirstname($content["firstname"]);
            $user->setLastname($content["lastname"]);
            $user->setLogin($content["login"]);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $userJson = $this->serializer->serialize($user, 'json', ["groups" => "show_user"]);

            return new JsonResponse($userJson, Response::HTTP_CREATED, [], true);
        } catch (NotEncodableValueException $exception) {

            $error = $this->error(Response::HTTP_BAD_REQUEST, "Malformatted JSON");

            return new JsonResponse($error["message"], $error["code"], [], true);
        }
    }

    #[Route('/api/users', name: 'get_user_information', methods: ["GET"])]
    public function getUserInformation(): Response
    {
        $user = $this->getUserFromSession();

        $userJson = $this->serializer->serialize($user, 'json', ["groups" => "show_user"]);

        return new JsonResponse($userJson, Response::HTTP_OK, [], true);
    }

    #[Route('/api/users', name: 'update_user_information', methods: ["PUT"])]
    public function updatePersonalInformation(Request $request): Response
    {
        $content = json_decode($request->getContent(), true);

        try {
            $user = $this->getUserFromSession();

            if (isset($content["password"])) {
                $password = $this->userPasswordHasher->hashPassword($user, $content["password"]);
                $user->setPassword($password);
            }

            $user->setFirstname($content["firstname"]);
            $user->setLastname($content["lastname"]);
            $user->setLogin($content["login"]);

            $this->entityManager->flush();

            $userJson = $this->serializer->serialize($user, 'json', ["groups" => "show_user"]);

        } catch (Exception $e) {
            $error = $this->error(Response::HTTP_BAD_REQUEST, "Malformatted JSON");

            return new JsonResponse($error["message"], $error["code"], [], true);
        }

        return new JsonResponse($userJson, Response::HTTP_OK, [], true);
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
