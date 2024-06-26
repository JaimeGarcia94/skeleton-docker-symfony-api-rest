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
use App\Entity\WorkEntry;
use App\Entity\User;

class WorkEntryController extends AbstractController
{
    private $em;
    private $validator;

    public function __construct(EntityManagerInterface $em, ValidatorInterface $validator)
    {
        $this->em = $em;
        $this->validator = $validator;
    }

    #[Route('v1/works-entries', name: 'app_v1_works_entries', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $worksEntries = $this->em->getRepository(WorkEntry::class)->findAll();
        $data = [];

        foreach($worksEntries as $workEntry){
            $id = $workEntry->getId();
            $user = $workEntry->getUser();
            $startDate = $workEntry->getStartDate();
            $endDate = $workEntry->getEndDate();
            $createdAt = $workEntry->getCreatedAt();
            $updatedAt = $workEntry->getUpdatedAt();

            $data[] = [
                'id' => $id,
                'user' => $user,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'createdAt' => $createdAt,
                'updatedAt' => $updatedAt
            ];
        }

        $array = [
            "data" => $data
        ];
        
        return new JsonResponse($array, Response::HTTP_OK); 
    }

    #[Route('v1/work-entry/{id}', name: 'app_v1_work_entry', methods: ['GET'])]
    public function workEntry(Request $request, $id): JsonResponse
    {
        $workEntry = $this->em->getRepository(WorkEntry::class)->findOneById($id);
        $msgError = "There is no date record in the DB with this ID. Please enter a valid one";

        if(empty($workEntry)) { 
            return new JsonResponse(['msgError' => $msgError], Response::HTTP_BAD_REQUEST);
        }

        $data = [];
        $id = $workEntry->getId();
        $user = $workEntry->getUser();
        $startDate = $workEntry->getStartDate();
        $endDate = $workEntry->getEndDate();
        $createdAt = $workEntry->getCreatedAt();
        $updatedAt = $workEntry->getUpdatedAt();

        $data[] = [
            'id' => $id,
            'user' => $user,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'createdAt' => $createdAt,
            'updatedAt' => $updatedAt
        ];

        $array = [
            "data" => $data
        ];
        
        return new JsonResponse($array, Response::HTTP_OK);    
    }

    #[Route('v1/work-entry/create', name: 'app_v1_work_entry_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $userId = $request->query->get('userId');
        $date = new DateTime();
        $startDate = empty($request->query->get('startDate')) ? '' : $request->query->get('startDate');
        $startDateObject = new DateTime($startDate);
        $endDate = $request->query->get('endDate');
        $endDateObject = empty($endDate) ? null : new DateTime($endDate);
        $user = $this->em->getRepository(User::class)->findOneById($userId);
        $msg = "The date has been created correctly";
        $msgError = ["You can't create the input date without the userId or the startDate. Check the data to be entered", "The date of departure can't be less than the date of entry."];

        if(empty($userId) || empty($startDate)){
            return new JsonResponse(['msgError' => $msgError[0]], Response::HTTP_BAD_REQUEST);
        }
        
        if(!empty($endDateObject)){
            if($endDateObject < $startDateObject) {
                return new JsonResponse(['msgError' => $msgError[1]], Response::HTTP_BAD_REQUEST);
            }
        }        

        $workEntry = new WorkEntry();
        $workEntry->setUser($user);
        $workEntry->setCreatedAt($date);
        $workEntry->setUpdatedAt($date);
        $workEntry->setDeletedAt(null);
        $workEntry->setStartDate($startDateObject);
        $workEntry->setEndDate($endDateObject);

        $errors = $this->validator->validate($workEntry);

        if (count($errors) > 0) {    
            $errorsString = (string) $errors;
            return new JsonResponse(['errorsString' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->em->persist($workEntry);
        $this->em->flush();

        return new JsonResponse(['msg' => $msg], Response::HTTP_CREATED);
    }

    #[Route('v1/work-entry/update/{id}', name: 'app_v1_work_entry_update', methods: ['PUT'])]
    public function update(Request $request, $id): JsonResponse
    {
        $date = new DateTime();
        $startDate = empty($request->query->get('startDate')) ? '' : $request->query->get('startDate');
        $startDateObject = new DateTime($startDate);
        $endDate = $request->query->get('endDate');
        $endDateObject = empty($endDate) ? null : new DateTime($endDate);
        $msg = "The date has been updated correctly";
        $msgError = ["The access date can't be updated. Check the data to be entered", "The date of departure can't be less than the date of entry."];

        if(empty($startDate)){
            return new JsonResponse(['msgError' => $msgError[0]], Response::HTTP_BAD_REQUEST);
        }
        
        if(!empty($endDateObject)){
            if($endDateObject < $startDateObject) {
                return new JsonResponse(['msgError' => $msgError[1]], Response::HTTP_BAD_REQUEST);
            }
        }        

        $workEntry = $this->em->getRepository(WorkEntry::class)->findOneById($id);
        $workEntry->setUpdatedAt($date);
        $workEntry->setDeletedAt(null);
        $workEntry->setStartDate($startDateObject);
        $workEntry->setEndDate($endDateObject);

        $errors = $this->validator->validate($workEntry);

        if (count($errors) > 0) {    
            $errorsString = (string) $errors;
            return new JsonResponse(['errorsString' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->em->persist($workEntry);
        $this->em->flush();

        return new JsonResponse(['msg' => $msg], Response::HTTP_OK);
    }

    #[Route('v1/work-entry/delete/{id}', name: 'app_v1_work_entry_delete', methods: ['DELETE'])]
    public function delete($id): JsonResponse
    {
        $msg = "The record has been successfully deleted";
        $msgError = ["Can't delete a record without an ID", "The record does not exist in the DB"];

        if(empty($id) || !is_numeric($id)){
            return new JsonResponse(['msgError' => $msgError[0]], Response::HTTP_BAD_REQUEST);
        }

        $workEntry = $this->em->getRepository(WorkEntry::class)->findOneByRow($id);

        if(is_null($workEntry)) {
            return new JsonResponse(['msgError' => $msgError[1]], Response::HTTP_BAD_REQUEST);
        }

        $this->em->remove($workEntry);
        $this->em->flush();

        return new JsonResponse(['msg' => $msg], Response::HTTP_OK);
    }
}
