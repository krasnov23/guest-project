<?php

namespace App\Service;

use App\Entity\Guest;
use App\Repository\GuestRepository;
use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GuestService
{
    public function __construct(private GuestRepository $guestRepository,
                                private EntityManagerInterface $em,
                                private ValidatorInterface $validator,
                                private SerializerInterface $serializer)
    {
    }

    public function addGuest(Request $request): JsonResponse
    {
        $requestBody = $request->getContent();

        $requestBodyAsArray = json_decode($requestBody,true);

        if (!$requestBodyAsArray)
        {
            return new JsonResponse(['ошибка' => 'указанно некорректное тело запроса'],400);
        }

        if (!array_key_exists('name',$requestBodyAsArray) || !array_key_exists('lastname',$requestBodyAsArray) ||
                                !array_key_exists('phoneNumber',$requestBodyAsArray))
        {
            return new JsonResponse(['ошибка' => 'не указан какой-то из обязательных параметров тела запроса (name,lastname,phoneNumber)'],
                400);
        }

        $name = trim($requestBodyAsArray['name']) !== "" ? $requestBodyAsArray['name'] : null;
        $lastname = trim($requestBodyAsArray['name']) !== "" ? $requestBodyAsArray['lastname']: null;
        $phoneNumber = $requestBodyAsArray['phoneNumber'];
        $email = null;
        $country = null;

        if (!$name || !$lastname)
        {
            return new JsonResponse(['ошибка' => 'В параметре name или lastname указана пустая строка'],400);
        }

        if (array_key_exists('email',$requestBodyAsArray))
        {
            $email = $requestBodyAsArray['email'];
        }

        if (array_key_exists('country',$requestBodyAsArray))
        {
            $country = $requestBodyAsArray['country'];
        }

        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $phoneUtil->parse($phoneNumber);
        } catch (NumberParseException $e) {
            return $this->returnJson(['ошибка' => 'неверно указан формат телефона, начните с +'],400);
        }

        $phoneNumberObject = $phoneUtil->parse($phoneNumber);

        if (!$phoneUtil->isValidNumber($phoneNumberObject))
        {
            return new JsonResponse(['ошибка' => 'невалидный номер телефона'],400);
        }

        if ($phoneUtil->getRegionCodeForNumber($phoneNumberObject))
        {
            $country = $phoneUtil->getRegionCodeForNumber($phoneNumberObject);
        }

        $emailConstraints = [new Email()];

        $violations = $this->validator->validate($email,$emailConstraints);

        if (count($violations) > 0)
        {
            return new JsonResponse(['ошибка' => 'невалидный email'],400);
        }

        $findByPhoneNumber = $this->guestRepository->findOneBy(['phone' => $phoneNumber]);

        if ($findByPhoneNumber)
        {
            return new JsonResponse(['ошибка' => 'Пользователь с данным номером уже есть в базе'],409);
        }

        if ($email !== null)
        {
            $findByEmail = $this->guestRepository->findOneBy(['email' => $email]);

            if ($findByEmail)
            {
                return new JsonResponse(['ошибка' => 'Пользователь с данным email уже есть в базе'],409);
            }
        }


        $guest = new Guest();
        $guest->setName($name);
        $guest->setLastname($lastname);
        $guest->setEmail($email);
        $guest->setPhone($phoneNumber);
        $guest->setCountry($country);

        $this->em->persist($guest);
        $this->em->flush();

        $findGuest = $this->guestRepository->findBy(['phone' => $phoneNumber]);

        $json = $this->serializer->serialize($findGuest, 'json', ['groups' => ['findGuest']]);

        return new JsonResponse($json,200,[],true);
    }

    public function editGuestByPhoneNumber(Request $request): JsonResponse
    {
        $requestBody = $request->getContent();

        $requestBodyAsArray = json_decode($requestBody,true);

        if (!$requestBodyAsArray)
        {
            return new JsonResponse(['ошибка' => 'указанно некорректное тело запроса'],400);
        }

        if (!array_key_exists('currentPhoneNumber',$requestBodyAsArray))
        {
            return new JsonResponse(['ошибка' => 'не указан обязательный параметр тела запроса currentPhoneNumber'],
                400);
        }

        $currentPhoneNumber = $requestBodyAsArray['currentPhoneNumber'];

        $currentGuest = $this->guestRepository->findOneBy(['phone' => $currentPhoneNumber]);

        if (!$currentGuest)
        {
            return new JsonResponse(['ошибка' => 'по данному телефону гость не найден'], 404);
        }

        if (array_key_exists('newName',$requestBodyAsArray))
        {
            $newName = trim($requestBodyAsArray['newName']) !== "" ? $requestBodyAsArray['newName'] : null;

            if ($newName)
            {
                $currentGuest->setName($requestBodyAsArray['newName']);
            }
        }

        if (array_key_exists('newLastname',$requestBodyAsArray))
        {
            $newLastname = trim($requestBodyAsArray['newLastname']) !== "" ? $requestBodyAsArray['newLastname'] : null;

            if ($newLastname)
            {
                $currentGuest->setLastname($requestBodyAsArray['newLastname']);
            }
        }

        if (array_key_exists('newPhoneNumber',$requestBodyAsArray))
        {
            $phoneNumber = $requestBodyAsArray['newPhoneNumber'];

            $phoneUtil = PhoneNumberUtil::getInstance();

            try {
                $phoneUtil->parse($phoneNumber);
            } catch (NumberParseException $e) {
                return $this->returnJson(['ошибка' => 'неверно указан формат телефона, начните с +'],400);
            }

            $phoneNumberObject = $phoneUtil->parse($phoneNumber);

            if (!$phoneUtil->isValidNumber($phoneNumberObject))
            {
                return new JsonResponse(['ошибка' => 'невалидный номер телефона'],400);
            }

            $isGuestWithNewPhoneNumberAlreadyExists = $this->guestRepository->findOneBy(['phone' => $phoneNumber]);

            if ($isGuestWithNewPhoneNumberAlreadyExists)
            {
                return new JsonResponse(['ошибка' => 'гость с данным номером телефона уже существует'],409);
            }

            $country = $phoneUtil->getRegionCodeForNumber($phoneNumberObject);

            $currentGuest->setPhone($phoneNumber);
            $currentGuest->setCountry($country);
        }

        if (array_key_exists('newEmail',$requestBodyAsArray))
        {
            $email = $requestBodyAsArray['newEmail'];
            $emailConstraints = [new Email()];

            $violations = $this->validator->validate($email,$emailConstraints);

            if (count($violations) > 0)
            {
                return new JsonResponse(['ошибка' => 'невалидный email'],400);
            }

            $isGuestWithNewEmailAlreadyExists = $this->guestRepository->findOneBy(['email' => $email]);

            if ($isGuestWithNewEmailAlreadyExists)
            {
                return new JsonResponse(['ошибка' => 'данный email уже есть в бд'],409);
            }

            $currentGuest->setEmail($email);
        }

        $this->em->persist($currentGuest);
        $this->em->flush();

        if (array_key_exists('newPhoneNumber',$requestBodyAsArray))
        {
            $findGuest = $this->guestRepository->findBy(['phone' => $requestBodyAsArray['newPhoneNumber']]);

            $json = $this->serializer->serialize($findGuest, 'json', ['groups' => ['findGuest']]);

            return new JsonResponse($json,200,[],true);
        }

        $findGuest = $this->guestRepository->findBy(['phone' => $requestBodyAsArray['currentPhoneNumber']]);

        $json = $this->serializer->serialize($findGuest, 'json', ['groups' => ['findGuest']]);

        return new JsonResponse($json,200,[],true);
    }

    public function getGuestByEmail(Request $request): JsonResponse
    {
        $email = $request->query->get('email');


        if ($email === null)
        {
            return new JsonResponse(['ошибка'=> 'не указан параметр email'],400);
        }

        $guest = $this->guestRepository->findBy(['email' => $email]);

        if (!$guest)
        {
            return new JsonResponse(['ошибка'=> 'данного email нет в базе данных'],404);
        }

        $json = $this->serializer->serialize($guest, 'json', ['groups' => ['findGuest']]);

        return new JsonResponse($json,200,[],true);
    }

    public function getGuestByPhoneNumber(Request $request): JsonResponse
    {
        $phoneNumber = $request->query->get('number');

        if ($phoneNumber === null)
        {
            return new JsonResponse(['ошибка'=> 'не указан параметр number'],400);
        }

        $phoneNumber = "+" . $phoneNumber;

        $guest = $this->guestRepository->findBy(['phone' => $phoneNumber]);

        if (!$guest)
        {
            return new JsonResponse(['ошибка'=> 'данного номера нет в базе данных либо введен некорректный параметр запроса (введите параметр без +)'],
                404);
        }

        $json = $this->serializer->serialize($guest, 'json', ['groups' => ['findGuest']]);

        return new JsonResponse($json,200,[],true);

    }

    public function getGuestById(int $id): JsonResponse
    {

        $guest = $this->guestRepository->find($id);

        if (!$guest)
        {
            return new JsonResponse(['ошибка'=> 'данного id нет в базе данных'],404);
        }

        $json = $this->serializer->serialize($guest, 'json', ['groups' => ['findGuest']]);

        return new JsonResponse($json,200,[],true);
    }

    public function deleteGuestByPhoneNumber(Request $request): JsonResponse
    {
        $phoneNumber = $request->query->get('number');

        if ($phoneNumber === null)
        {
            return new JsonResponse(['ошибка'=> 'не указан параметр number'],400);
        }

        $phoneNumber = "+" . $phoneNumber;

        $guest = $this->guestRepository->findOneBy(['phone' => $phoneNumber]);

        if (!$guest)
        {
            return new JsonResponse(['ошибка'=> 'данного номера нет в базе данных либо 
            введен некорректный параметр запроса (введите параметр без +)'],404);
        }

        $this->em->remove($guest);
        $this->em->flush();

        return new JsonResponse(['ответ'=> 'success'],200);
    }

    public function deleteGuestById(int $id): JsonResponse
    {
        $guest = $this->guestRepository->find($id);

        if (!$guest)
        {
            return new JsonResponse(['ошибка'=> 'данного id нет в базе данных'],404);
        }

        $this->em->remove($guest);
        $this->em->flush();

        return new JsonResponse(['ответ'=> 'success'],200);
    }

    private function returnJson(array $array,int $codeNumber):JsonResponse
    {
        return new JsonResponse($array,$codeNumber);
    }


}