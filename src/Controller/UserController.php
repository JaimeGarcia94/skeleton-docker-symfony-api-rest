<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;
use App\Entity\User;

class UserController extends AbstractController
{
    private $em;
    private $validator;

    public function __construct(EntityManagerInterface $em, ValidatorInterface $validator)
    {
        $this->em = $em;
        $this->validator = $validator;
    }

    #[Route('v1/user/test', name: 'app_v1_user_test', methods: ['GET'])]
    public function test(): JsonResponse
    {
        $msg = "You have just accessed the test user of the REST API.";

        return new JsonResponse(['msg' => $msg], Response::HTTP_OK);
    }

    #[Route('v1/users', name: 'app_v1_users', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $users = $this->em->getRepository(User::class)->findAll();
        $data = [];

        foreach($users as $user){
            $id = $user->getId();
            $name = $user->getName();
            $email = $user->getEmail();
            $createdAt = $user->getCreatedAt();
            $updatedAt = $user->getUpdatedAt();

            $data[] = [
                'id' => $id,
                'name' => $name,
                'email' => $email,
                'createdAt' => $createdAt,
                'updatedAt' => $updatedAt
            ];
        }

        $array = [
            "data" => $data
        ];
        
        return new JsonResponse($array, Response::HTTP_OK);   
    }

    #[Route('v1/user/{id}', name: 'app_v1_user', methods: ['GET'])]
    public function user(Request $request, $id): JsonResponse
    {
        $user = $this->em->getRepository(User::class)->findOneById($id);
        $msgError = "The user ID does not exist in the database. Please enter a valid one";

        if(empty($user)) { 
            return new JsonResponse(['msgError' => $msgError], Response::HTTP_BAD_REQUEST);
        }

        $data = [];
        $id = $user->getId();
        $name = $user->getName();
        $email = $user->getEmail();
        $createdAt = $user->getCreatedAt();
        $updatedAt = $user->getUpdatedAt();

        $data[] = [
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'createdAt' => $createdAt,
            'updatedAt' => $updatedAt
        ];

        $array = [
            "data" => $data
        ];
        
        return new JsonResponse($array, Response::HTTP_OK);   
    }

    #[Route('v1/user/create', name: 'app_v1_user_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $email = $request->query->get('email');
        $roles = ["ROL_USER"];
        $name = $request->query->get('name');
        $date = new DateTime();
        $msg = "The user has been successfully created";
        $msgError = "You can't create a user without: email or name. Check the data to be entered";

        if(empty($email) || empty($name)){
            return new JsonResponse(['msgError' => $msgError], Response::HTTP_BAD_REQUEST);
        }        

        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setName($name);
        $user->setCreatedAt($date);
        $user->setUpdatedAt($date);
        $user->setDeletedAt(null);

        $errors = $this->validator->validate($user);

        if (count($errors) > 0) {    
            $errorsString = (string) $errors;
            return new JsonResponse(['errorsString' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->em->persist($user);
        $this->em->flush();

        return new JsonResponse(['msg' => $msg], Response::HTTP_CREATED);
    }

    #[Route('v1/user/update/{id}', name: 'app_v1_user_update', methods: ['PUT'])]
    public function update(Request $request, $id): JsonResponse
    {
        $email = $request->query->get('email');
        $roles = ["ROL_USER"];
        $name = $request->query->get('name');
        $date = new DateTime();
        $msg = "The user has been successfully updated";
        $msgError = ["You can't update a user without: ID, email or name. Check the data to be entered", "The user does not exist in the DB"];

        if(empty($id) || !is_numeric($id) || empty($email) || empty($name)){
            return new JsonResponse(['msgError' => $msgError[0]], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->em->getRepository(User::class)->findOneById($id);

        if(is_null($user)) {
            return new JsonResponse(['msgError' => $msgError[1]], Response::HTTP_BAD_REQUEST);
        }

        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setName($name);
        $user->setCreatedAt($date);
        $user->setUpdatedAt($date);
        $user->setDeletedAt(null);

        $errors = $this->validator->validate($user);

        if (count($errors) > 0) {    
            $errorsString = (string) $errors;
            return new JsonResponse(['errorsString' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->em->persist($user);
        $this->em->flush();

        return new JsonResponse(['msg' => $msg], Response::HTTP_OK);
    }

    #[Route('v1/user/delete/{id}', name: 'app_v1_user_delete', methods: ['DELETE'])]
    public function delete($id): JsonResponse
    {
        $msg = "The user has been successfully deleted";
        $msgError = ["You can't delete a user without an ID", "The user does not exist in the DB"];

        if(empty($id) || !is_numeric($id)){
            return new JsonResponse(['msgError' => $msgError[0]], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->em->getRepository(User::class)->findOneByRow($id);

        if(is_null($user)) {
            return new JsonResponse(['msgError' => $msgError[1]], Response::HTTP_BAD_REQUEST);
        }

        $this->em->remove($user);
        $this->em->flush();

        return new JsonResponse(['msg' => $msg], Response::HTTP_OK);
    }
}
