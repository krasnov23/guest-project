<?php

namespace App\Tests\Service;

use App\Entity\Guest;
use App\Service\GuestService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class EditGuestTest extends AbstractGuestTestCase
{

    public function testEditGuestByIncorrectBody(): void
    {
        $request = new Request(content: '{"key":"value",}');
        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $this->assertEquals((new JsonResponse(['ошибка' => 'указанно некорректное тело запроса'],400))
            ->setEncodingOptions(JSON_UNESCAPED_UNICODE),
            $guestService->editGuestByPhoneNumber($request)->setEncodingOptions(JSON_UNESCAPED_UNICODE)
        );
    }

    public function testEditGuestWithOutRequiredParameters(): void
    {
        $request = new Request(content: '{"key":"value"}');
        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $this->assertEquals((new JsonResponse(['ошибка' => 'не указан обязательный параметр тела запроса currentPhoneNumber']
            ,400))
            ->setEncodingOptions(JSON_UNESCAPED_UNICODE),
            $guestService->editGuestByPhoneNumber($request)->setEncodingOptions(JSON_UNESCAPED_UNICODE)
        );
    }

    public function testEditGuestWhichDoesntExists(): void
    {
        $request = new Request(content: '{"currentPhoneNumber":"+79297169752"}');
        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $this->guestRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['phone' => '+79297169752'])
            ->willReturn(null);

        $this->assertEquals((new JsonResponse(['ошибка' => 'по данному телефону гость не найден'],404))
            ->setEncodingOptions(JSON_UNESCAPED_UNICODE),
            $guestService->editGuestByPhoneNumber($request)->setEncodingOptions(JSON_UNESCAPED_UNICODE)
        );
    }

    public function testEditGuestWithIncorrectFormatOfPhoneNumber(): void
    {
        $request = new Request(content: '{"currentPhoneNumber":"+79297169752","newPhoneNumber":"89297169750"}');
        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $guest = (new Guest())->setPhone('+79297169752');

        $this->guestRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['phone' => '+79297169752'])
            ->willReturn($guest);

        $this->assertEquals((new JsonResponse(['ошибка' => 'неверно указан формат телефона, начните с +'],400))
            ->setEncodingOptions(JSON_UNESCAPED_UNICODE),
            $guestService->editGuestByPhoneNumber($request)->setEncodingOptions(JSON_UNESCAPED_UNICODE)
        );
    }

    public function testEditGuestWithInvalidNewPhoneNumber(): void
    {
        $request = new Request(content: '{"currentPhoneNumber":"+79297169752","newPhoneNumber":"+777"}');
        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $guest = (new Guest())->setPhone('+79297169752');

        $this->guestRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['phone' => '+79297169752'])
            ->willReturn($guest);

        $this->assertEquals((new JsonResponse(['ошибка' => 'невалидный номер телефона'],400))
            ->setEncodingOptions(JSON_UNESCAPED_UNICODE),
            $guestService->editGuestByPhoneNumber($request)->setEncodingOptions(JSON_UNESCAPED_UNICODE)
        );
    }

    public function testEditGuestWithNewNumberWhichGuestAlreadyExists():void
    {
        $request = new Request(content: '{"currentPhoneNumber":"+79297169752","newPhoneNumber":"+79297169750"}');
        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $this->guestRepository->expects($this->exactly(2))
            ->method('findOneBy')
            ->will($this->returnCallback(function ($criteria){
                if (array_key_exists('phone',$criteria))
                {
                    if ($criteria['phone'] === '+79297169752') {
                        return (new Guest())->setPhone('+79297169752');
                    }elseif ($criteria['phone'] === '+79297169750')
                    {
                        return (new Guest())->setPhone('+79297169750');
                    }

                    return null;
                }

                return null;
            }));

        $this->assertEquals((new JsonResponse(['ошибка' => 'гость с данным номером телефона уже существует'],409))
            ->setEncodingOptions(JSON_UNESCAPED_UNICODE),
            $guestService->editGuestByPhoneNumber($request)->setEncodingOptions(JSON_UNESCAPED_UNICODE)
        );

    }

    public function testEditGuestWithIncorrectEmail():void
    {
        $request = new Request(content: '{"currentPhoneNumber":"+79297169752","newPhoneNumber":"+79297169750",
                                            "newEmail":"test@test"}');
        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $this->guestRepository->expects($this->exactly(2))
            ->method('findOneBy')
            ->will($this->returnCallback(function ($criteria){
                if (array_key_exists('phone',$criteria))
                {
                    if ($criteria['phone'] === '+79297169752') {
                        return (new Guest())->setPhone('+79297169752');
                    }elseif ($criteria['phone'] === '+79297169750')
                    {
                        return null;
                    }

                    return null;
                }

                return null;
            }));


        $this->validator->expects($this->once())
            ->method('validate')
            ->with('test@test',[new Email()])
            ->willReturn(new ConstraintViolationList([
                new ConstraintViolation('error',null,[],null,'email',null)
            ]));

        $this->assertEquals((new JsonResponse(['ошибка' => 'невалидный email'],400))
            ->setEncodingOptions(JSON_UNESCAPED_UNICODE),
            $guestService->editGuestByPhoneNumber($request)->setEncodingOptions(JSON_UNESCAPED_UNICODE)
        );

    }

    public function testEditGuestWithEmailWhichGuestAlreadyExists(): void
    {
        $request = new Request(content: '{"currentPhoneNumber":"+79297169752","newPhoneNumber":"+79297169750",
                                            "newEmail":"test@test.ru"}');

        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $this->guestRepository->expects($this->exactly(3))
            ->method('findOneBy')
            ->will($this->returnCallback(function ($criteria){
                if (array_key_exists('phone',$criteria))
                {
                    if ($criteria['phone'] === '+79297169752') {
                        return (new Guest())->setPhone('+79297169752');
                    }elseif ($criteria['phone'] === '+79297169750')
                    {
                        return null;
                    }

                    return null;
                }

                if (array_key_exists('email',$criteria))
                {
                    if ($criteria['email'] === 'test@test.ru')
                    {
                        return (new Guest())->setEmail('test@test.ru');
                    }

                    return null;
                }

                return null;
            }));

        $this->validator->expects($this->once())
            ->method('validate')
            ->with('test@test.ru',[new Email()])
            ->willReturn(new ConstraintViolationList([]));

        $this->assertEquals((new JsonResponse(['ошибка' => 'данный email уже есть в бд'],409))
            ->setEncodingOptions(JSON_UNESCAPED_UNICODE),
            $guestService->editGuestByPhoneNumber($request)->setEncodingOptions(JSON_UNESCAPED_UNICODE)
        );
    }

    public function testEditGuestWithNewPhoneNumber(): void
    {
        $request = new Request(content: '{"currentPhoneNumber":"+79297169752","newPhoneNumber":"+79297169750",
                                            "newEmail":"test@test.ru","newName":"Борис","newLastname":"Борисов"}');

        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $guest = (new Guest())->setPhone("+79297169750")->setEmail("test@test.ru")
            ->setCountry("RU")->setName("Борис")->setLastname("Борисов");
        
        $this->guestRepository->expects($this->exactly(3))
            ->method('findOneBy')
            ->will($this->returnCallback(function ($criteria){
                if (array_key_exists('phone',$criteria))
                {
                    if ($criteria['phone'] === '+79297169752') {
                        return (new Guest())->setPhone('+79297169752');
                    }elseif ($criteria['phone'] === '+79297169750')
                    {
                        return null;
                    }

                    return new Guest();
                }

                if (array_key_exists('email',$criteria))
                {
                    if ($criteria['email'] === 'test@test.ru')
                    {
                        return null;
                    }

                    return new Guest();
                }

                return null;
            }));

        $this->validator->expects($this->once())
            ->method('validate')
            ->with('test@test.ru',[new Email()])
            ->willReturn(new ConstraintViolationList([]));

        $this->em->expects($this->once())
            ->method('persist')
            ->with($guest);
        
        $this->em->expects($this->once())
            ->method('flush');
        
        $this->guestRepository->expects($this->once())
            ->method('findBy')
            ->with(['phone' => '+79297169750'])
            ->willReturn([$guest]);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with([$guest],'json',['groups' => ['findGuest']])
            ->willReturn('[{"id":null,"name":"Борис","lastname":"Борисов",
            "phone":"+79297169750","email":"test@test.ru,"country":"RU"}]');


        $this->assertEquals((new JsonResponse('[{"id":null,"name":"Борис","lastname":"Борисов",
            "phone":"+79297169750","email":"test@test.ru,"country":"RU"}]',200,[],true)),
            $guestService->editGuestByPhoneNumber($request)
        );
    }

    public function testEditGuestWithOutNewPhone(): void
    {
        $request = new Request(content: '{"currentPhoneNumber":"+79297169752",
                                            "newEmail":"test@test.ru","newName":"Борис","newLastname":"Борисов"}');

        $guestService = new GuestService($this->guestRepository,$this->em,$this->validator,$this->serializer);

        $guest = (new Guest())->setPhone("+79297169752")->setEmail("test@test.ru")
            ->setName("Борис")->setLastname("Борисов");

        $guestWithCountry = (new Guest())->setPhone("+79297169752")->setEmail("test@test.ru")
            ->setName("Борис")->setLastname("Борисов")->setCountry('RU');

        $this->guestRepository->expects($this->exactly(2))
            ->method('findOneBy')
            ->will($this->returnCallback(function ($criteria){
                if (array_key_exists('phone',$criteria))
                {
                    if ($criteria['phone'] === '+79297169752') {
                        return (new Guest())->setPhone('+79297169752')->setEmail("test@test.ru")
                            ->setName("Борис")->setLastname("Борисов");
                    }

                    return null;
                }

                if (array_key_exists('email',$criteria))
                {
                    if ($criteria['email'] === 'test@test.ru')
                    {
                        return null;
                    }

                    return new Guest();
                }

                return null;
            }));

        $this->validator->expects($this->once())
            ->method('validate')
            ->with('test@test.ru',[new Email()])
            ->willReturn(new ConstraintViolationList([]));

        $this->em->expects($this->once())
            ->method('persist')
            ->with($guest);

        $this->em->expects($this->once())
            ->method('flush');

        $this->guestRepository->expects($this->once())
            ->method('findBy')
            ->with(['phone' => '+79297169752'])
            ->willReturn([$guestWithCountry]);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with([$guestWithCountry],'json',['groups' => ['findGuest']])
            ->willReturn('[{"id":null,"name":"Борис","lastname":"Борисов",
            "phone":"+79297169752","email":"test@test.ru,"country":"RU"}]');

        $this->assertEquals((new JsonResponse('[{"id":null,"name":"Борис","lastname":"Борисов",
            "phone":"+79297169752","email":"test@test.ru,"country":"RU"}]',200,[],true)),
            $guestService->editGuestByPhoneNumber($request)
        );

    }

}
