<?php

final class Account
{
    public $status;
    public $balance;
}

abstract class Event
{
    /**
     * @var Account
     */
    public $account;

    public function __construct(Account $account)
    {
        $this->account = $account;
    }
}

class EventProcessor
{
    public function addEvent(Event $event): void
    {
        switch (\get_class($event))
        {
            case OpenAccount::class:
                /** @var OpenAccount $event */
                $event->account->status = $event->status;
                $event->account->balance = $event->balance;
                return;
            case DepositMoney::class:
                /** @var DepositMoney $event */
                $event->account->balance += $event->amount;
                return;
            case WithdrawMoney::class:
                /** @var WithdrawMoney $event */
                if ($event->amount > $event->account->balance) {
                    throw new \UnexpectedValueException('Insufficient funds');
                }

                $event->account->balance -= $event->amount;
                return;
        }

        throw new \InvalidArgumentException(\sprintf('Unknown event: %s', \get_class($event)));
    }
}

final class OpenAccount extends Event
{
    /**
     * @var string
     */
    public $status;
    /**
     * @var int
     */
    public $balance;

    public function __construct(Account $account, string $status, int $balance)
    {
        parent::__construct($account);
        $this->status = $status;
        $this->balance = $balance;
    }
}

final class DepositMoney extends Event
{
    /**
     * @var int
     */
    public $amount;

    public function __construct(Account $account, int $amount, string $title)
    {
        parent::__construct($account);
        $this->amount = $amount;
    }
}

final class WithdrawMoney extends Event
{
    /**
     * @var int
     */
    public $amount;

    public function __construct(Account $account, int $amount, string $title, string $method)
    {
        parent::__construct($account);
        $this->amount = $amount;
    }
}

class Test
{
    public $testsExecuted = 0;
    public $testsPassed = 0;

    /**
     * @var EventProcessor
     */
    private $eventProcessor;

    public function __construct()
    {
        $this->eventProcessor = new EventProcessor();
    }

    public function testOpensAccount()
    {
        $account = new Account();
        $this->eventProcessor->addEvent(new OpenAccount($account, "OPEN", 0));
        $this->assertEquals("OPEN", $account->status);
        $this->assertEquals(0, $account->balance);
    }

    public function testDeposit()
    {
        $account = new Account();
        $this->eventProcessor->addEvent(new OpenAccount($account, "OPEN", 0));
        $this->eventProcessor->addEvent(new DepositMoney($account, 100, "Wpłata 1 zł"));
        $this->assertEquals(100, $account->balance);
    }

    public function testWithdraw()
    {
        $account = new Account();
        $this->eventProcessor->addEvent(new OpenAccount($account, "OPEN", 0));
        $this->eventProcessor->addEvent(new DepositMoney($account, 100, "Wpłata 1 zł"));
        $this->eventProcessor->addEvent(new DepositMoney($account, 200, "Wpłata 2 zł"));
        $this->eventProcessor->addEvent(new WithdrawMoney($account, 50, "Wypłata środków", "bank_transfer"));
        $this->assertEquals(250, $account->balance);
    }

    public function assertEquals($expected, $actual): bool
    {
        $this->testsExecuted++;

        if ($expected != $actual) {
            echo \sprintf('Assertion failed: %s is not equal %s', $expected, $actual) . PHP_EOL;

            return false;
        }

        echo \sprintf('%s is equal %s', $expected, $actual) . PHP_EOL;
        $this->testsPassed++;

        return true;
    }
}

$successful = 0;
$test = new Test();
$test->testOpensAccount();
$test->testDeposit();
$test->testWithdraw();

if ($test->testsPassed === $test->testsExecuted) {
    echo 'All tests passed!';
}
