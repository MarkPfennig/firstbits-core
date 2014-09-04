## Firstbits

A simple, tested, PHP implementation of all fisrt bits logic, with generic SQL and PDO drivers for PHP.

    $firstbits = new firstbits($pdo);
    // store an address, firstbits are generated, duplicates ignored
    $firstbits->storeAndReturn($address);
    // get the firstbits for an address if it exists
    $firstbits->get($address);

To test

    php -f test-firstbits.php


