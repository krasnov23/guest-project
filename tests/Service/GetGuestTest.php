<?php

namespace App\Tests\Service;


use App\Entity\Guest;
use App\Service\GuestService;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class GetGuestTest extends AbstractGuestTestCase
{
    public function testGetGuestWithOutEmailParameter(): void
    {
        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $request = new Request();
        $inputData = new InputBag();
        $inputData->set('something','another');
        $request->query = $inputData;

        $this->assertEquals(
            (new JsonResponse(['ошибка'=> 'не указан параметр email'],400))
                ->setEncodingOptions(JSON_UNESCAPED_UNICODE),
            $guestService->getGuestByEmail($request)->setEncodingOptions(JSON_UNESCAPED_UNICODE)
        );

    }

    public function testGetGuestByEmailParameterGuestDoesntExists(): void
    {
        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $request = new Request();
        $inputData = new InputBag();
        $inputData->set('email','test@test.ru');
        $request->query = $inputData;

        $this->guestRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'test@test.ru'])
            ->willReturn(null);

        $this->assertEquals(
            (new JsonResponse(['ошибка'=> 'данного email нет в базе данных'],404))
                ->setEncodingOptions(JSON_UNESCAPED_UNICODE),
            $guestService->getGuestByEmail($request)->setEncodingOptions(JSON_UNESCAPED_UNICODE)
        );

    }

    public function testGetGuestByEmailParameter(): void
    {
        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $request = new Request();
        $inputData = new InputBag();
        $inputData->set('email','test@test.ru');
        $request->query = $inputData;

        $guest = new Guest();
        $guest->setName('Борис')->setLastname('Борисов')
            ->setPhone('+79297169752')
            ->setCountry('RU')
            ->setEmail('test@test.ru');


        $this->guestRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'test@test.ru'])
            ->willReturn($guest);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($guest,'json',['groups' => ['findGuest']])
            ->willReturn('[{"id":null,"name":"Борис","lastname":"Борисов",
            "phone":"+79297169752","email":"test@test.ru,"country":"RU"}]');

        $this->assertEquals((new JsonResponse('[{"id":null,"name":"Борис","lastname":"Борисов",
            "phone":"+79297169752","email":"test@test.ru,"country":"RU"}]',200,[],true)),
            $guestService->getGuestByEmail($request));

    }

    public function testGetGuestWithOutPhoneParameter(): void
    {
        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $request = new Request();
        $inputData = new InputBag();
        $inputData->set('something','another');
        $request->query = $inputData;

        $this->assertEquals(
            (new JsonResponse(['ошибка'=> 'не указан параметр number'],400))
                ->setEncodingOptions(JSON_UNESCAPED_UNICODE),
            $guestService->getGuestByPhoneNumber($request)->setEncodingOptions(JSON_UNESCAPED_UNICODE)
        );

    }

    public function testGetGuestByPhoneParameterGuestDoesntExists(): void
    {
        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $request = new Request();
        $inputData = new InputBag();
        $inputData->set('number','79297169752');
        $request->query = $inputData;

        $this->guestRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['phone' => '+79297169752'])
            ->willReturn(null);

        $this->assertEquals(
            (new JsonResponse(['ошибка'=> 'данного номера нет в базе данных либо введен некорректный параметр запроса (введите параметр без +)'],404))
                ->setEncodingOptions(JSON_UNESCAPED_UNICODE),
            $guestService->getGuestByPhoneNumber($request)->setEncodingOptions(JSON_UNESCAPED_UNICODE)
        );

    }

    public function testGetGuestByPhone(): void
    {
        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $guest = new Guest();
        $guest->setName('Борис')->setLastname('Борисов')
            ->setPhone('+79297169752')
            ->setCountry('RU')
            ->setEmail('test@test.ru');

        $request = new Request();
        $inputData = new InputBag();
        $inputData->set('number','79297169752');
        $request->query = $inputData;

        $this->guestRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['phone' => '+79297169752'])
            ->willReturn($guest);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($guest,'json',['groups' => ['findGuest']])
            ->willReturn('[{"id":null,"name":"Борис","lastname":"Борисов",
            "phone":"+79297169752","email":"test@test.ru,"country":"RU"}]');

        $this->assertEquals((new JsonResponse('[{"id":null,"name":"Борис","lastname":"Борисов",
            "phone":"+79297169752","email":"test@test.ru,"country":"RU"}]',200,[],true)),
            $guestService->getGuestByPhoneNumber($request));
    }

    public function testGetGuestByIdParameterGuestDoesntExists(): void
    {
        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $this->guestRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(null);


        $this->assertEquals(
            (new JsonResponse(['ошибка'=> 'данного id нет в базе данных'],404))
                ->setEncodingOptions(JSON_UNESCAPED_UNICODE),
            $guestService->getGuestById(1)->setEncodingOptions(JSON_UNESCAPED_UNICODE)
        );

    }

    public function testGetGuestById(): void
    {
        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $guest = new Guest();
        $guest->setName('Борис')->setLastname('Борисов')
            ->setPhone('+79297169752')
            ->setCountry('RU')
            ->setEmail('test@test.ru');

        $this->guestRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($guest);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($guest,'json',['groups' => ['findGuest']])
            ->willReturn('[{"id":null,"name":"Борис","lastname":"Борисов",
            "phone":"+79297169752","email":"test@test.ru,"country":"RU"}]');

        $this->assertEquals((new JsonResponse('[{"id":null,"name":"Борис","lastname":"Борисов",
            "phone":"+79297169752","email":"test@test.ru,"country":"RU"}]',200,[],true)),
            $guestService->getGuestById(1));
    }








}
