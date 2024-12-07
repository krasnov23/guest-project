<?php

namespace App\Tests\Service;


use App\Entity\Guest;
use App\Service\GuestService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class AddGuestTest extends AbstractGuestTestCase
{

    public function testAddGuestWithIncorrectBody(): void
    {
        $request = new Request(content: '{"key":"value",}');
        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $this->assertEquals((new JsonResponse(['ошибка' => 'указанно некорректное тело запроса'],400))
            ->setEncodingOptions(JSON_UNESCAPED_UNICODE),
            $guestService->addGuest($request)->setEncodingOptions(JSON_UNESCAPED_UNICODE)
        );
    }

    public function testAddGuestWithOutRequiredParameterOfBody(): void
    {
        $request = new Request(content: '{"name":"test","lastname":"testov"}');

        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $this->assertEquals(
            (new JsonResponse(['ошибка' => 'не указан какой-то из обязательных параметров тела запроса (name,lastname,phoneNumber)'],400))
            ->setEncodingOptions(JSON_UNESCAPED_UNICODE),
            $guestService->addGuest($request)->setEncodingOptions(JSON_UNESCAPED_UNICODE)
        );

    }

    public function testAddGuestWithEmptyStringInRequiredParametersOfBody(): void
    {
        $request = new Request(content: '{"name":"test","lastname":"     ","phoneNumber":"+79297169752"}');

        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $this->assertEquals(
            (new JsonResponse(['ошибка' => 'В параметре name или lastname указана пустая строка'],400))
                ->setEncodingOptions(JSON_UNESCAPED_UNICODE),
            $guestService->addGuest($request)->setEncodingOptions(JSON_UNESCAPED_UNICODE)
        );
    }

    public function testAddGuestWithIncorrectFormatPhoneNumber(): void
    {
        $request = new Request(content: '{"name":"test","lastname":"testov","phoneNumber":"9297169752"}');

        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $this->assertEquals(
            (new JsonResponse(['ошибка' => 'неверно указан формат телефона, начните с +'],400))
                ->setEncodingOptions(JSON_UNESCAPED_UNICODE),
            $guestService->addGuest($request)->setEncodingOptions(JSON_UNESCAPED_UNICODE)
        );

    }

    public function testAddGuestWithInvalidPhoneNumber(): void
    {
        $request = new Request(content: '{"name":"test","lastname":"testov","phoneNumber":"+70000000000"}');

        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $this->assertEquals(
            (new JsonResponse(['ошибка' => 'невалидный номер телефона'],400))
                ->setEncodingOptions(JSON_UNESCAPED_UNICODE),
            $guestService->addGuest($request)->setEncodingOptions(JSON_UNESCAPED_UNICODE)
        );

    }

    public function testAddGuestWithInvalidEmail(): void
    {
        $request = new Request(content: '{"name":"test","lastname":"testov","phoneNumber":"+79297169752","email":"test@test"}');

        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with('test@test',[new Email()])
            ->willReturn(new ConstraintViolationList([
                new ConstraintViolation('error',null,[],null,'email',null)
            ]));


        $this->assertEquals(
            (new JsonResponse(['ошибка' => 'невалидный email'],400))
                ->setEncodingOptions(JSON_UNESCAPED_UNICODE),
            $guestService->addGuest($request)->setEncodingOptions(JSON_UNESCAPED_UNICODE)
        );
    }

    public function testAddGuestWithNumberWhichAlreadyExistsInDatabase(): void
    {
        $request = new Request(content: '{"name":"test","lastname":"testov","phoneNumber":"+79297169752","email":"test@test.ru"}');

        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with('test@test.ru',[new Email()])
            ->willReturn(new ConstraintViolationList([]));

        $this->guestRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['phone' => '+79297169752'])
            ->willReturn((new Guest())->setPhone('+79297169752'));

        $this->assertEquals(
            (new JsonResponse(['ошибка' => 'Пользователь с данным номером уже есть в базе'],409))
                ->setEncodingOptions(JSON_UNESCAPED_UNICODE),
            $guestService->addGuest($request)->setEncodingOptions(JSON_UNESCAPED_UNICODE)
        );

    }

    public function testAddGuestWithEmailWhichAlreadyExistsInDatabase(): void
    {
        $request = new Request(content: '{"name":"test","lastname":"testov","phoneNumber":"+79297169752","email":"test@test.ru"}');

        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with('test@test.ru',[new Email()])
            ->willReturn(new ConstraintViolationList([]));


        $this->guestRepository->expects($this->exactly(2))
            ->method('findOneBy')
            ->will($this->returnCallback(function($criteria) {
                if (array_key_exists('phone',$criteria))
                {
                    if ($criteria['phone'] === '+79297169752') {
                        return null; // Возвращаем null для телефона
                    }
                }

                if (array_key_exists('email',$criteria))
                {
                    if ($criteria['email'] === 'test@test.ru')
                    {
                        return (new Guest())->setEmail('test@test.ru');
                    }
                }

                return null; // По умолчанию возвращаем null
            }));

        $this->assertEquals(
            (new JsonResponse(['ошибка' => 'Пользователь с данным email уже есть в базе'],409))
                ->setEncodingOptions(JSON_UNESCAPED_UNICODE),
            $guestService->addGuest($request)->setEncodingOptions(JSON_UNESCAPED_UNICODE)
        );
    }

    public function testAddGuestGetSuccessResponse(): void
    {
        $request = new Request(content: '{"name":"test","lastname":"testov","phoneNumber":"+79297169752","email":"test@test.ru"}');

        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $guest = (new Guest())->setPhone("+79297169752")->setEmail("test@test.ru")
            ->setName("test")->setLastname("testov")->setCountry('RU');

        $this->validator->expects($this->once())
            ->method('validate')
            ->with('test@test.ru',[new Email()])
            ->willReturn(new ConstraintViolationList([]));

        $this->guestRepository->expects($this->exactly(2))
            ->method('findOneBy')
            ->will($this->returnCallback(function($criteria) {
                if (array_key_exists('phone',$criteria))
                {
                    if ($criteria['phone'] === '+79297169752') {
                        return null; // Возвращаем null для телефона
                    }
                }

                if (array_key_exists('email',$criteria))
                {
                    if ($criteria['email'] === 'test@test.ru')
                    {
                        return null;
                    }
                }

                return null;
            }));


        $this->em->expects($this->once())
            ->method('persist')
            ->with($guest);

        $this->em->expects($this->once())
            ->method('flush');

        $this->guestRepository->expects($this->once())
            ->method('findBy')
            ->with(['phone' => '+79297169752'])
            ->willReturn([$guest]);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with([$guest], 'json', ['groups' => ['findGuest']])
            ->willReturn('[{"id":null,"name":"test","lastname":"testov",
            "phone":"+79297169752","email":"test@test.ru,"country":"RU"}]');


        $this->assertEquals((new JsonResponse('[{"id":null,"name":"test","lastname":"testov",
            "phone":"+79297169752","email":"test@test.ru,"country":"RU"}]',200,[],true)),
            $guestService->addGuest($request)
        );
    }












}
