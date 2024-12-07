<?php

namespace App\Tests\Service;

use App\Repository\GuestRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AbstractGuestTestCase extends TestCase
{

    protected GuestRepository $guestRepository;
    protected EntityManagerInterface $em;
    protected ValidatorInterface $validator;
    protected SerializerInterface $serializer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guestRepository = $this->createMock(GuestRepository::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
    }


}