<?php

namespace App\Tests\Service;

use App\Entity\Guest;
use App\Service\GuestService;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DeleteGuestTest extends AbstractGuestTestCase
{
    public function testDeleteGuestWithOutPhoneParameter(): void
    {
        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $request = new Request();
        $inputData = new InputBag();
        $inputData->set('something','another');
        $request->query = $inputData;

        $this->assertEquals(
            (new JsonResponse(['ошибка'=> 'не указан параметр number'],400))
                ->setEncodingOptions(JSON_UNESCAPED_UNICODE),
            $guestService->deleteGuestByPhoneNumber($request)->setEncodingOptions(JSON_UNESCAPED_UNICODE)
        );

    }

    public function testDeleteGuestByPhoneParameterGuestDoesntExists(): void
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
            $guestService->deleteGuestByPhoneNumber($request)->setEncodingOptions(JSON_UNESCAPED_UNICODE)
        );

    }

    public function testDeleteGuestByPhoneNumber(): void
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

        $this->em->expects($this->once())
            ->method('remove')
            ->with($guest);

        $this->em->expects($this->once())
            ->method('flush');

        $this->assertEquals(
            (new JsonResponse(['ответ'=> 'success'],200,))
                ->setEncodingOptions(JSON_UNESCAPED_UNICODE),
            $guestService->deleteGuestByPhoneNumber($request)->setEncodingOptions(JSON_UNESCAPED_UNICODE)
        );

    }

    public function testDeleteByIdNotFoundGuest(): void
    {
        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $this->guestRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(null);


        $this->assertEquals(
            (new JsonResponse(['ошибка'=> 'данного id нет в базе данных'],404))
                ->setEncodingOptions(JSON_UNESCAPED_UNICODE),
            $guestService->deleteGuestById(1)->setEncodingOptions(JSON_UNESCAPED_UNICODE)
        );
    }

    public function testDeleteById(): void
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

        $this->em->expects($this->once())
            ->method('remove')
            ->with($guest);

        $this->em->expects($this->once())
            ->method('flush');

        $this->assertEquals(
            (new JsonResponse(['ответ'=> 'success'],200,))
                ->setEncodingOptions(JSON_UNESCAPED_UNICODE),
            $guestService->deleteGuestById(1)->setEncodingOptions(JSON_UNESCAPED_UNICODE)
        );

    }

}
