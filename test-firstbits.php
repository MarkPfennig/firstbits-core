<?php
require_once './firstbits.php';

define('FIRSTBITS_STORAGE', __DIR__ .'/address.store');

class test_firstbits extends firstbits {
  
  /**
   * upon instantiation create a new test database
   */
  public function __construct() {
    echo 'Creating test database... ';
    // create new local sqlite database
    $fbdb = new PDO('sqlite:' . FIRSTBITS_STORAGE, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
    $fbdb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo FIRSTBITS_STORAGE . ' created.' . PHP_EOL;
    // create test table
    $fbdb->exec("CREATE TABLE IF NOT EXISTS firstbits ( fb TEXT UNIQUE, address TEXT UNIQUE, created INTEGER )");
    // make firstbits aware of our database
    parent::__construct($fbdb);
    // start testing
    $this->test();
  }

  /**
   * class destructor, empty close delete
   */
  public function __destruct() {
    $this->fbdb->exec("DROP TABLE firstbits");
    $this->fbdb = null;
    unlink(FIRSTBITS_STORAGE);
    echo FIRSTBITS_STORAGE . ' destroyed.' . PHP_EOL;
  }
  
  
  public function test() {
    echo 'generating test data... ';
    // set up some test data, make a lot of random addresses and stick in some well knowns and duplicates
    $tests = array();
    for($i=0;$i<5000;$i++) $tests[] = $this->generateRandomAddress();
    $tests = array_merge($tests, array(
        '1SgTspiKe5HHkjdSeD72q9WsiJhRiaxf9',
        '18rai2ichzUfXG6PVmUQLNPqBjtctnVRAD',
        '1SgTspiKe5HHkjdSeD72q9WsiJhRiaxf9',
        '18rai2ichzUfXG6PVmUQLNPqBjtctnVRAD',
    ));
    shuffle($tests); // randomize order
    
    echo count($tests) . ' tests addresses created.' . PHP_EOL;
    echo 'storing... ';
    
    // store all test data
    foreach($tests as $address) $this->storeAndReturn($address);
    
    echo count($tests) . ' saved to database.' . PHP_EOL;
    
    // truncate to 20 random test addresses
    shuffle($tests); array_splice($tests, 20);
    
    echo count($tests) . ' random test cases selected.' . PHP_EOL;
    
    // get the test addresses from the db
    while($test = array_pop($tests)) {
      $firstbits = $this->get($test);
      if( !$firstbits || $firstbits->address !== $test )
        throw new Exception('test address was not found');
      print_r($firstbits);
    }
    // sanity check false positives
    if( $this->get('111111111111111111') )
      throw new Exception('non existent address found');
    
    echo 'TESTS PASSED' . PHP_EOL;
  }
  
  /**
   * Return a random btc address like string
   * The Firstbits system is address format agnostic, it just compares strings
   *
   * @return string
   */
  public function generateRandomAddress() {
    $characters = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
    $randomString = '1';
    for ($i = 0; $i < 33; $i++) $randomString .= $characters[rand(0, strlen($characters) - 1)];
    return $randomString;
  }
  
}
