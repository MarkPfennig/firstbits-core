<?php
/**
 * Currency Agnostic Firstbits Implementation
 * 
 * @author MarkPfennig <mark@bitmark.co>
 * @license UNLICENSE
 */

class firstbits {

  /**
   * maximum length of firstbits to consider 
   * 
   * @var int
   */
  const FIRSTBITS_MAX_LENGTH = 20;

  /**
   * PDO database handle
   *
   * @var PDO
   */
  protected $fbdb;

  /**
   * constructor, inject the PDO database handle
   *
   * @param PDO $pdo
   */
  public function __construct($pdo) {
    $this->fbdb = $pdo;
  }

  /**
   * For any given address returns all possible firstbits up to FIRSTBITS_MAX_LENGTH
   *
   * @param string $address
   * @return array
   */
  public function getAddressVarients($address) {
    $varients = array();
    $lower = strtolower($address);
    for($i=0; $i<self::FIRSTBITS_MAX_LENGTH; $i++)
      $varients[] = substr($lower, 0, $i+1);
    $varients[] = $address;
    return $varients;
  }

  /**
   * Given any two addresses / array of varients, will return the shortest unused firstbits of $new
   *
   * @param mixed $varients
   * @param mixed $existing
   * @return string
   */
  public function getShortestFirstbits($new, $existing) {
    if(!is_array($new)) $new = $this->getAddressVarients($new);
    if(!is_array($existing)) $existing = $this->getAddressVarients($existing);
    $diff = array_diff($new, $existing);
    usort($diff, function($a, $b) {
      return strlen($b)-strlen($a);
    });
    return array_pop($diff);
  }

  /**
   * recases the firstbits in a uniform object
   *
   * @param object $object
   * @return object
   */
  public function recase($object) {
    $object->fb = substr($object->address, 0, strlen($object->fb));
    return $object;
  }

  /**
   * get the firstbits from db, returns an object {fb,address,time} or void
   * 
   * @param string $address
   * @return void|object
   */
  public function get($address) {
    if(!($object = $this->fbdb->query("SELECT * FROM firstbits WHERE address='$address'")->fetch(PDO::FETCH_OBJ)))
      return;
    return $this->recase($object);
  }
  
  /**
   * store as an address if it does not exist, always returns an object {fb,address,time}
   * @param string $address
   * @return object
   */
  public function storeAndReturn($address) {
    // grab the varients for the address, implode them in to a search string
    $varients = $this->getAddressVarients($address);
    $in = "'".implode("','",$varients)."'";
    // retrieve the longest matching firstbits from the database
    if($result = $this->fbdb->query("SELECT * FROM firstbits WHERE fb IN ($in) ORDER BY LENGTH(fb) DESC LIMIT 1")->fetch(PDO::FETCH_OBJ)) {
      // if the addresses are the same, skip it's a duplicate just return the uniform object
      if($result->address == $address) return $this->recase($result);
      // otherwise compare case insensitively to get the shortest firstbits of our to be inserted address
      $fb = $this->getShortestFirstbits($varients, $result->address);
    } else {
      // if nothing exists use the shortest possible firstbits
      $fb = $varients[0];
    }

    // insert in to firstbits
    $st = $this->fbdb->prepare("INSERT INTO firstbits (fb, address, created) VALUES (:fb, :address, :created)");
    $st->bindParam(':fb', $fb);
    $st->bindParam(':address', $address);
    $st->bindParam(':created', $time = time());
    $st->execute();
    // just to be sure of no memory leaks
    $st = null;
    // return a uniform object
    return $this->recase((object)array(
        'fb' => $fb,
        'address' => $address,
        'time' => $time
    ));
  }

}
