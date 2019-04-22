<?php
declare(strict_types=1);

namespace Tests\Relationships;

use Gekko\Env;
use PHPUnit\Framework\TestCase;
use Gekko\Config\ConfigProvider;
use Gekko\Model\Generators\Runner;
use Gekko\Database\MySQL\MySQLConnection;
use Gekko\Model\Generators\Domain\DomainGenerator;
use Gekko\Database\MySQL\Migrations\MySQLMigration;
use Gekko\Model\Generators\MySQL\Schema\MySQLSchemaGenerator;
use Gekko\Model\Generators\MySQL\Mappers\MySQLDataMapperGenerator;
use Gekko\Model\Generators\MySQL\Repositories\MySQLRepositoryGenerator;

use \Tests\Domain\{ User, Destination, Rate, Booking, Ticket };
use \Tests\Domain\Repositories\{ UserRepository, DestinationRepository, RateRepository, BookingRepository, TicketRepository };

final class RelationshipsTest extends \Tests\BaseTestCase
{
    private static $connection;

    public static function setUpBeforeClass() : void
    {
        $dbconfig = self::$configProvider->getConfig("database");
        self::$connection = new MySQLConnection($dbconfig->get("mysql.connection.host"), $dbconfig->get("mysql.connection.name"), $dbconfig->get("mysql.connection.user"), $dbconfig->get("mysql.connection.pass"));
    }

    public function testAddUsers() : void
    {
        $userRepository = new UserRepository(self::$connection);

        $user1 = new User();
        $user1->setName("User 1");

        $user2 = new User();
        $user2->setName("User 2");

        $this->assertTrue($userRepository->add($user1));
        $this->assertTrue($userRepository->add($user2));
    }

    public function testAddDestinationsAndRates() : void
    {
        $destinationRepository = new DestinationRepository(self::$connection);

        $destination1 = new Destination();
        $destination1->setName("Dest 1");

        $destination2 = new Destination();
        $destination2->setName("Dest 2");

        $this->assertTrue($destinationRepository->add($destination1));
        $this->assertTrue($destinationRepository->add($destination2));

        $rateRepository = new RateRepository(self::$connection);

        $rate1 = new Rate();
        $rate1->setName("Rate 1");
        $rate1->setDestination($destination1);

        $rate2 = new Rate();
        $rate2->setName("Rate 2");
        $rate2->setDestination($destination2);

        $this->assertTrue($rateRepository->add($rate1));
        $this->assertTrue($rateRepository->add($rate2));
    }

    public function testAddBookingForDestination1() : void
    {
        $destinationRepository = new DestinationRepository(self::$connection);
        $rateRepository = new RateRepository(self::$connection);
        $bookingRepository = new BookingRepository(self::$connection);
        $ticketRepository = new TicketRepository(self::$connection);

        $booking = new Booking();
        $booking->setDestination($destinationRepository->get(1));

        $ticket1 = new Ticket();
        $ticket1->setBooking($booking);
        $ticket1->setRate($rateRepository->get(1));

        $ticket2 = new Ticket();
        $ticket2->setBooking($booking);
        $ticket2->setRate($rateRepository->get(1));

        $this->assertTrue($bookingRepository->add($booking));
        $this->assertTrue($ticketRepository->add($ticket1));
        $this->assertTrue($ticketRepository->add($ticket2));
    }

    public function testAddPassengerForDestination1Tickets() : void
    {
        $ticketRepository = new TicketRepository(self::$connection);
        $userRepository = new UserRepository(self::$connection);

        $ticket1 = $ticketRepository->get(1);
        $ticket2 = $ticketRepository->get(2);

        $this->assertNotNull($ticket1);
        $this->assertNotNull($ticket2);

        $ticket1->setUser($userRepository->get(1));
        $ticket2->setUser($userRepository->get(2));

        $this->assertTrue($ticketRepository->save($ticket1));
        $this->assertTrue($ticketRepository->save($ticket2));
    }
    
    public function testAddBookingForDestination2() : void
    {
        $destinationRepository = new DestinationRepository(self::$connection);
        $rateRepository = new RateRepository(self::$connection);
        $bookingRepository = new BookingRepository(self::$connection);
        $ticketRepository = new TicketRepository(self::$connection);

        $booking = new Booking();
        $booking->setDestination($destinationRepository->get(2));

        $ticket1 = new Ticket();
        $ticket1->setBooking($booking);
        $ticket1->setRate($rateRepository->get(2));

        $ticket2 = new Ticket();
        $ticket2->setBooking($booking);
        $ticket2->setRate($rateRepository->get(2));

        $this->assertTrue($bookingRepository->add($booking));
        $this->assertTrue($ticketRepository->add($ticket1));
        $this->assertTrue($ticketRepository->add($ticket2));
    }

    public function testAddPassengerForDestination2Tickets() : void
    {
        $ticketRepository = new TicketRepository(self::$connection);
        $userRepository = new UserRepository(self::$connection);

        $ticket1 = $ticketRepository->get(3);
        $ticket2 = $ticketRepository->get(4);

        $this->assertNotNull($ticket1);
        $this->assertNotNull($ticket2);

        $ticket1->setUser($userRepository->get(1));
        $ticket2->setUser($userRepository->get(2));

        $this->assertTrue($ticketRepository->save($ticket1));
        $this->assertTrue($ticketRepository->save($ticket2));
    }

    public function testQueryBuilder() : void
    {
        $tdm = new \Tests\Domain\DataMappers\TicketDataMapper(self::$connection);

        $tickets = $tdm->where([[Destination::class, "id"], "=", 1]);
    }
}